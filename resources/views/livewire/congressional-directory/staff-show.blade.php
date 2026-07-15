<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <a href="{{ route('congress.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
        ← Back to Congress Explorer
    </a>

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
                        @php($sourceUrl = data_get($observation->source_data, 'url'))
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
