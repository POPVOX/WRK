<div class="desk-page">
    <x-congress-nav />
    <a href="{{ route('congress.index') }}" wire:navigate class="desk-link">← Congress directory</a>

    <section class="app-surface p-6">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $profile->display_name }}</h1>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $profile->chamber === 'Senate' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800' }}">
                        {{ $profile->chamber }}
                    </span>
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                        {{ Str::headline($profile->review_status) }} identity
                    </span>
                </div>
                <p class="mt-2 text-gray-600">
                    Seen {{ $profile->first_seen_at?->format('M Y') ?? 'date unknown' }}–{{ $profile->last_seen_at?->format('M Y') ?? 'present' }}
                    · Latest source period {{ $profile->latest_period_end?->format('M j, Y') ?? 'unknown' }}
                </p>
            </div>

            @if($profile->person)
                <a href="{{ route('contacts.show', $profile->person) }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Open linked contact
                </a>
            @else
                <div class="max-w-sm rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Not linked to Contacts. This prevents bulk source data from overwhelming the relationship database.
                </div>
            @endif
        </div>
    </section>

    <section class="app-surface overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Email evidence</h2>
            <p class="mt-1 text-sm app-muted">Record sourced, observed, or provisional addresses. Adding evidence never sends an email.</p>
        </div>

        <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-3">
                @forelse($profile->emails as $staffEmail)
                    @php
                        $eligibility = $emailEligibility[$staffEmail->id];
                        $tierClass = match($eligibility['tier']) {
                            'eligible' => 'bg-emerald-100 text-emerald-800',
                            'limited' => 'bg-amber-100 text-amber-800',
                            default => 'bg-red-100 text-red-800',
                        };
                    @endphp
                    <article class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="mailto:{{ $staffEmail->email }}" class="break-all font-semibold text-indigo-700 hover:underline">{{ $staffEmail->email }}</a>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $tierClass }}">{{ Str::headline($eligibility['tier']) }}</span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ Str::headline($staffEmail->source_type) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-600">{{ $eligibility['reason'] }}</p>
                                <p class="mt-1 text-xs app-muted">Evidence status: {{ Str::headline($staffEmail->verification_status) }}</p>
                                @if($staffEmail->source_url)
                                    <a href="{{ $staffEmail->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex text-xs font-medium text-indigo-600 hover:underline">View address source ↗</a>
                                @endif
                                @if($staffEmail->source_notes)<p class="mt-2 text-sm text-gray-700">{{ $staffEmail->source_notes }}</p>@endif
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if($eligibility['tier'] !== 'blocked' && $staffEmail->verification_status !== 'confirmed')
                                    <button type="button" wire:click="markEmailConfirmed({{ $staffEmail->id }})" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Mark confirmed</button>
                                @endif
                                @if(($suppressionReasons[$staffEmail->email_normalized] ?? null) === 'manual')
                                    <button type="button" wire:click="restoreEmail({{ $staffEmail->id }})" wire:confirm="Remove the manual suppression for this address?" class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Restore</button>
                                @elseif($eligibility['tier'] !== 'blocked')
                                    <button type="button" wire:click="suppressEmail({{ $staffEmail->id }})" wire:confirm="Suppress this address from all outreach?" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Suppress</button>
                                @endif
                            </div>
                        </div>

                        @if($staffEmail->events->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                                @foreach($staffEmail->events->take(4) as $event)
                                    <span class="rounded-md bg-gray-50 px-2 py-1 text-xs text-gray-600" title="{{ $event->occurred_at?->format('M j, Y g:i A') }}">
                                        {{ Str::headline($event->event_type) }} · {{ Str::headline($event->evidence_strength) }} evidence
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 px-5 py-8 text-center text-sm text-gray-500">No email evidence has been recorded for this staff profile.</div>
                @endforelse
            </div>

            <form wire:submit="addEmail" class="h-fit space-y-3 rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                <div>
                    <h3 class="font-semibold text-indigo-950">Add email evidence</h3>
                    <p class="mt-1 text-xs text-indigo-800">Guessed and manual addresses remain limited until stronger evidence appears.</p>
                </div>
                <label class="block text-sm font-medium text-gray-700">
                    Email address
                    <input type="email" wire:model.defer="emailAddress" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="staffer@house.gov">
                </label>
                @error('emailAddress') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <label class="block text-sm font-medium text-gray-700">
                    How we know it
                    <select wire:model.defer="emailSourceType" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="guessed">Guessed pattern</option>
                        <option value="observed">Observed in correspondence</option>
                        <option value="redirected">Provided as a replacement</option>
                        <option value="sourced">Published source</option>
                        <option value="manual">Manually entered</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-gray-700">
                    Source URL <span class="font-normal text-gray-500">(optional)</span>
                    <input type="url" wire:model.defer="emailSourceUrl" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="https://…">
                </label>
                @error('emailSourceUrl') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <label class="block text-sm font-medium text-gray-700">
                    Evidence note <span class="font-normal text-gray-500">(optional)</span>
                    <textarea rows="3" wire:model.defer="emailSourceNotes" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="Where this address came from"></textarea>
                </label>
                @error('emailSourceNotes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Add evidence</button>
            </form>
        </div>
    </section>

    <section class="app-surface overflow-hidden">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div>
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-semibold text-gray-900">Activity timeline</h2>
                    <p class="mt-1 text-sm app-muted">Campaign emails and Gmail correspondence are logged automatically. Meetings appear when this profile is linked to a contact.</p>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($profile->contactActivities as $activity)
                        <article class="px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">{{ Str::headline($activity->activity_type) }}</span>
                                        @if($activity->direction)<span class="text-xs text-gray-500">{{ Str::headline($activity->direction) }}</span>@endif
                                        @if($activity->source_type !== 'manual')<span class="text-xs text-gray-400">via {{ Str::headline($activity->source_type) }}</span>@endif
                                    </div>
                                    @if($activity->subject)<p class="mt-2 font-medium text-gray-900">{{ $activity->subject }}</p>@endif
                                    @if($activity->summary)<p class="mt-1 text-sm text-gray-600">{{ $activity->summary }}</p>@endif
                                    @if($activity->campaignRecipient?->campaign)<p class="mt-1 text-xs text-gray-500">Campaign: {{ $activity->campaignRecipient->campaign->name }}</p>@endif
                                </div>
                                <time class="shrink-0 text-xs text-gray-500">{{ $activity->occurred_at?->format('M j, Y g:i A') }}</time>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-gray-500">No correspondence has been logged yet.</div>
                    @endforelse

                    @foreach($profile->person?->meetings ?? collect() as $meeting)
                        <article class="px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div><span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Meeting</span><a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="mt-2 block font-medium text-gray-900 hover:text-indigo-700">{{ $meeting->title }}</a></div>
                                <time class="shrink-0 text-xs text-gray-500">{{ $meeting->meeting_date?->format('M j, Y') }}</time>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <form wire:submit="addActivity" class="space-y-3 border-t border-gray-200 bg-gray-50 p-5 lg:border-l lg:border-t-0">
                <div><h3 class="font-semibold text-gray-900">Log activity</h3><p class="mt-1 text-xs text-gray-500">Add a note, call, meeting, email, or LinkedIn interaction.</p></div>
                <div class="grid grid-cols-2 gap-2">
                    <select wire:model="activityType" class="rounded-lg border-gray-300 text-sm">
                        @foreach(['note' => 'Note', 'email' => 'Email', 'meeting' => 'Meeting', 'call' => 'Call', 'linkedin' => 'LinkedIn', 'other' => 'Other'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                    <select wire:model="activityDirection" class="rounded-lg border-gray-300 text-sm"><option value="">No direction</option><option value="inbound">Inbound</option><option value="outbound">Outbound</option></select>
                </div>
                <input type="text" wire:model="activitySubject" class="block w-full rounded-lg border-gray-300 text-sm" placeholder="Subject (optional)">
                <textarea wire:model="activitySummary" rows="4" class="block w-full rounded-lg border-gray-300 text-sm" placeholder="What happened?"></textarea>
                @error('activitySummary')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <input type="datetime-local" wire:model="activityOccurredAt" class="block w-full rounded-lg border-gray-300 text-sm">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Log activity</button>
            </form>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="app-surface overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Reported role history</h2>
                <p class="text-sm app-muted">Positions are grouped from public disbursement observations.</p>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($profile->positions as $position)
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $position->title }}</h3>
                                    @if($position->is_current)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Current</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-700">{{ $position->office->name }}</p>
                                <p class="mt-0.5 text-xs app-muted">
                                    {{ $position->office->office_type ?: 'Office type unknown' }}
                                    @if($position->office->office_code) · {{ $position->office->office_code }} @endif
                                </p>
                            </div>
                            <p class="shrink-0 text-right text-sm app-muted">
                                {{ $position->first_reported_start?->format('M Y') ?? '?' }}–{{ $position->last_reported_end?->format('M Y') ?? '?' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm app-muted">No position history is available.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="app-surface p-5">
                <h2 class="font-semibold text-gray-900">Identity safety</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="app-muted">Review status</dt>
                        <dd class="font-medium text-gray-900">{{ Str::headline($profile->review_status) }}</dd>
                    </div>
                    <div>
                        <dt class="app-muted">Source identity hint</dt>
                        <dd class="break-all font-mono text-xs text-gray-700">{{ $profile->identity_hint }}</dd>
                    </div>
                    <div>
                        <dt class="app-muted">Contact link</dt>
                        <dd class="font-medium text-gray-900">{{ $profile->person ? 'Linked by team' : 'Not linked' }}</dd>
                    </div>
                </dl>
                <p class="mt-4 border-t border-gray-200 pt-4 text-xs app-muted">
                    Matching names are not automatically treated as the same person across offices. Email and human evidence will drive later identity resolution.
                </p>
            </section>
        </aside>
    </div>

    <section class="app-surface overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Source observations</h2>
            <p class="text-sm app-muted">Most recent 50 evidence records. Links open the public source file.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Period</th>
                        <th class="px-5 py-3">Office</th>
                        <th class="px-5 py-3">Reported title</th>
                        <th class="px-5 py-3">Evidence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($profile->observations as $observation)
                        @php
                            $sourceUrl = data_get($observation->source_data, 'url');
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-700">{{ $observation->period_label ?: $observation->period_end?->format('M Y') }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $observation->office_raw }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $observation->title_raw }}</td>
                            <td class="whitespace-nowrap px-5 py-3">
                                @if($sourceUrl)
                                    <a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline">
                                        View source ↗
                                    </a>
                                @else
                                    <span class="app-muted">Source recorded</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
