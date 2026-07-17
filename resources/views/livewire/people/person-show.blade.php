<div class="desk-page">
    @php
        $touchDates = collect([
            $person->contactActivities->where('occurred_at', '<=', now())->max('occurred_at'),
            $person->interactions->where('occurred_at', '<=', now())->max('occurred_at'),
            $person->meetings->where('meeting_date', '<=', today())->max('meeting_date'),
        ])->filter()->map(fn ($date) => \Carbon\Carbon::parse($date));
        $latestTouch = $touchDates->sortDesc()->first();
        $touchTone = match (true) {
            ! $latestTouch => 'text-[#8a8578]',
            $latestTouch->greaterThanOrEqualTo(now()->subMonth()) => 'desk-status-positive',
            $latestTouch->lessThan(now()->subMonths(3)) => 'desk-status-danger',
            default => 'text-[#5c574d]',
        };

        $timeline = collect()
            ->concat($automaticActivities->map(fn ($activity) => [
                'date' => $activity->occurred_at,
                'kind' => $activity->activity_type,
                'title' => $activity->subject ?: Str::headline($activity->activity_type),
                'summary' => $activity->summary,
                'meta' => collect([
                    $activity->direction ? Str::headline($activity->direction) : null,
                    $activity->source_type ? 'via '.Str::headline($activity->source_type) : null,
                    $activity->campaignRecipient?->campaign?->name ? 'Campaign: '.$activity->campaignRecipient->campaign->name : null,
                ])->filter()->join(' · '),
                'href' => null,
                'action' => null,
            ]))
            ->concat($interactions->map(fn ($interaction) => [
                'date' => $interaction->occurred_at,
                'kind' => $interaction->type,
                'title' => Str::headline($interaction->type).' logged',
                'summary' => $interaction->summary,
                'meta' => $interaction->user ? 'by '.$interaction->user->name : null,
                'href' => null,
                'action' => null,
            ]))
            ->concat($meetings->map(fn ($meeting) => [
                'date' => $meeting->meeting_date,
                'kind' => 'meeting',
                'title' => $meeting->title ?: 'Untitled meeting',
                'summary' => $meeting->ai_summary,
                'meta' => $meeting->organizations->pluck('name')->take(2)->join(', '),
                'href' => route('meetings.show', $meeting),
                'action' => 'Notes →',
            ]))
            ->filter(fn ($item) => filled($item['date']))
            ->sortByDesc(fn ($item) => \Carbon\Carbon::parse($item['date'])->timestamp)
            ->values();
    @endphp

    <header class="flex flex-col gap-5 border-b-2 border-[#26221c] pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex min-w-0 items-center gap-4">
            <x-avatar :name="$person->name" :photo="$person->photo_url" size="2xl" />
            <div class="min-w-0">
                <a href="{{ route('contacts.index') }}" wire:navigate class="desk-kicker">People · Contacts</a>
                <h1 class="desk-page-title mt-1 truncate">{{ $person->name }}</h1>
                <p class="mt-1 text-sm text-[#5c574d]">
                    {{ $person->title ?: 'Title not set' }}
                    @if($person->organization)
                        · <a href="{{ route('organizations.show', $person->organization) }}" wire:navigate class="font-semibold text-[#8a4b2d]">{{ $person->organization->name }} →</a>
                    @endif
                </p>
                <p class="desk-meta mt-2">
                    Last touch: <span class="font-semibold {{ $touchTone }}">{{ $latestTouch?->diffForHumans() ?: 'none recorded' }}</span>
                    · {{ number_format($meetings->count()) }} {{ Str::plural('meeting', $meetings->count()) }}
                    · Owner: {{ $person->owner?->name ?: 'unassigned' }}
                </p>
            </div>
        </div>
        <div class="desk-toolbar">
            @if($person->email)<a href="mailto:{{ $person->email }}" class="desk-button-secondary">✉ Email</a>@endif
            <a href="{{ route('meetings.create', ['person' => $person->id]) }}" wire:navigate class="desk-button-secondary">＋ Log meeting</a>
            @if($editing)
                <button type="button" wire:click="cancelEditing" class="desk-button-secondary">Cancel</button>
            @else
                <button type="button" wire:click="startEditing" class="desk-button-secondary">Edit</button>
            @endif
            <button type="button" disabled class="desk-button-dark cursor-not-allowed opacity-60" title="Relationship briefs are planned for a later phase">✦ Relationship brief</button>
        </div>
    </header>

    @if($editing)
        <section class="desk-inset p-5">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-[#ddd4c5] pb-4">
                <div><p class="desk-section-label !text-[#8a4b2d]">Edit contact</p><p class="desk-meta mt-1">Identity, relationship ownership, and the next action live on this record.</p></div>
                <button type="button" wire:click="delete" wire:confirm="Are you sure you want to delete this person?" class="text-xs font-semibold text-[#b33a2b]">Delete contact</button>
            </div>

            <form wire:submit="save" class="mt-5 grid gap-4 md:grid-cols-2">
                <label><span class="desk-section-label">Name</span><input type="text" wire:model="name" class="mt-2 w-full">@error('name')<span class="mt-1 block text-xs text-[#b33a2b]">{{ $message }}</span>@enderror</label>
                <label><span class="desk-section-label">Title</span><input type="text" wire:model="title" class="mt-2 w-full"></label>
                <label><span class="desk-section-label">Organization</span><select wire:model="organization_id" class="mt-2 w-full"><option value="">No organization</option>@foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></label>
                <label><span class="desk-section-label">Email</span><input type="email" wire:model="email" class="mt-2 w-full">@error('email')<span class="mt-1 block text-xs text-[#b33a2b]">{{ $message }}</span>@enderror</label>
                <label><span class="desk-section-label">Phone</span><input type="text" wire:model="phone" class="mt-2 w-full"></label>
                <label><span class="desk-section-label">LinkedIn</span><input type="url" wire:model="linkedin_url" class="mt-2 w-full">@error('linkedin_url')<span class="mt-1 block text-xs text-[#b33a2b]">{{ $message }}</span>@enderror</label>
                <label class="md:col-span-2"><span class="desk-section-label">Notes</span><textarea wire:model="notes" rows="4" class="mt-2 w-full"></textarea></label>
                <div class="md:col-span-2 flex justify-end gap-2"><button type="button" wire:click="cancelEditing" class="desk-button-secondary">Cancel</button><button type="submit" class="desk-button-primary">Save contact</button></div>
            </form>

            <form wire:submit="saveCrm" class="mt-6 grid gap-4 border-t border-[#ddd4c5] pt-5 md:grid-cols-2">
                <label><span class="desk-section-label">Relationship owner</span><select wire:model="owner_id" class="mt-2 w-full"><option value="">Unassigned</option>@foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach</select></label>
                <label><span class="desk-section-label">Source</span><input type="text" wire:model="source" class="mt-2 w-full" placeholder="Meeting, referral, import…"></label>
                <label><span class="desk-section-label">Tags</span><input type="text" wire:model="tagsInput" class="mt-2 w-full" placeholder="partner, funder, parliament"></label>
                <label><span class="desk-section-label">Next action date</span><input type="datetime-local" wire:model="next_action_at" class="mt-2 w-full"></label>
                <label class="md:col-span-2"><span class="desk-section-label">Next action</span><input type="text" wire:model="next_action_note" class="mt-2 w-full" placeholder="What should happen next?"></label>
                <div class="md:col-span-2 flex justify-end"><button type="submit" class="desk-button-primary">Save relationship details</button></div>
            </form>
        </section>
    @else
        <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_19rem]">
            <main class="min-w-0 space-y-8">
                @if($person->next_action_at || $person->next_action_note)
                    <section class="desk-alert px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div><p class="desk-section-label !text-[#b33a2b]">Open follow-up</p><p class="desk-display mt-2 text-lg font-semibold text-[#26221c]">{{ $person->next_action_note ?: 'Follow up with '.$person->name }}</p></div>
                            @if($person->next_action_at)<time class="desk-data text-xs {{ $person->next_action_at->isPast() ? 'desk-status-danger' : 'text-[#5c574d]' }}">{{ $person->next_action_at->format('M j, Y') }}</time>@endif
                        </div>
                    </section>
                @endif

                <section>
                    <div class="flex flex-wrap items-end justify-between gap-3 pb-2">
                        <div><p class="desk-section-label">Relationship timeline</p><p class="desk-meta mt-1">Meetings, direct notes, and matched correspondence in one history.</p></div>
                        <details class="relative">
                            <summary class="desk-button-secondary cursor-pointer list-none">＋ Log interaction</summary>
                            <form wire:submit="addInteraction" class="desk-modal-panel absolute right-0 z-20 mt-2 w-[min(34rem,calc(100vw-2rem))] space-y-4 p-5">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label><span class="desk-section-label">Type</span><select wire:model="interaction_type" class="mt-2 w-full">@foreach(['call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'note' => 'Note'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                                    <label><span class="desk-section-label">Date</span><input type="datetime-local" wire:model="interaction_date" class="mt-2 w-full"></label>
                                </div>
                                <label class="block"><span class="desk-section-label">Summary</span><textarea wire:model="interaction_summary" rows="4" class="mt-2 w-full" placeholder="What happened?"></textarea></label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label><span class="desk-section-label">Follow-up date</span><input type="datetime-local" wire:model="interaction_next_at" class="mt-2 w-full"></label>
                                    <label><span class="desk-section-label">Follow-up</span><input type="text" wire:model="interaction_next_note" class="mt-2 w-full"></label>
                                </div>
                                <div class="flex justify-end"><button type="submit" class="desk-button-primary">Add to timeline</button></div>
                            </form>
                        </details>
                    </div>
                    <div class="desk-rule">
                        @forelse($timeline->take(20) as $item)
                            @php $occurredAt = \Carbon\Carbon::parse($item['date']); @endphp
                            <article class="desk-row grid gap-2 py-4 sm:grid-cols-[5.5rem_1.5rem_minmax(0,1fr)_auto] sm:items-start">
                                <time class="desk-data text-[11px] text-[#8a8578]">{{ $occurredAt->format('M j') }}</time>
                                <span class="text-center text-sm text-[#8a8578]" aria-hidden="true">{{ $item['kind'] === 'meeting' ? '◷' : (str_contains((string) $item['kind'], 'email') ? '✉' : '·') }}</span>
                                <div class="min-w-0">
                                    @if($item['href'])<a href="{{ $item['href'] }}" wire:navigate class="font-semibold text-[#26221c] hover:text-[#8a4b2d]">{{ $item['title'] }}</a>@else<p class="font-semibold text-[#26221c]">{{ $item['title'] }}</p>@endif
                                    @if($item['summary'])<p class="desk-meta mt-1">{{ Str::limit(strip_tags((string) $item['summary']), 180) }}</p>@endif
                                    @if($item['meta'])<p class="desk-meta mt-1">{{ $item['meta'] }}</p>@endif
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($item['href'])<a href="{{ $item['href'] }}" wire:navigate class="desk-link whitespace-nowrap">{{ $item['action'] }}</a>@endif
                                </div>
                            </article>
                        @empty
                            <div class="desk-empty">No relationship history has been recorded yet.</div>
                        @endforelse
                    </div>
                    @if($timeline->count() > 20)<p class="mt-3 text-xs text-[#8a8578]">Showing the 20 most recent touchpoints.</p>@endif
                </section>

                <section>
                    <div class="flex items-end justify-between pb-2"><p class="desk-section-label">Linked projects</p><button type="button" wire:click="toggleAddProjectModal" class="desk-link">＋ Add project</button></div>
                    <div class="desk-hairline">
                        @forelse($projects as $project)
                            <div class="desk-row flex items-center justify-between gap-4 py-3">
                                <div><a href="{{ route('projects.show', $project) }}" wire:navigate class="desk-display font-semibold hover:text-[#8a4b2d]">{{ $project->name }}</a><p class="desk-meta mt-1">{{ $project->pivot->role ?: Str::headline($project->status) }}</p></div>
                                <button type="button" wire:click="unlinkProject({{ $project->id }})" wire:confirm="Remove from this project?" class="text-xs text-[#b33a2b]">Remove</button>
                            </div>
                        @empty
                            <div class="desk-empty">No projects are linked to this contact.</div>
                        @endforelse
                    </div>
                </section>

                <section>
                    <div class="flex items-end justify-between pb-2"><p class="desk-section-label">Documents and notes</p><p class="desk-meta">{{ number_format($attachments->count()) }} files</p></div>
                    <form wire:submit="uploadAttachment" class="desk-inset grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                        <label><span class="desk-section-label">File</span><input type="file" wire:model="newAttachment" class="mt-2 block w-full text-xs">@error('newAttachment')<span class="mt-1 block text-xs text-[#b33a2b]">{{ $message }}</span>@enderror</label>
                        <label><span class="desk-section-label">Note</span><input type="text" wire:model="attachmentNotes" class="mt-2 w-full" placeholder="Optional context"></label>
                        <button type="submit" wire:loading.attr="disabled" class="desk-button-primary"><span wire:loading.remove wire:target="uploadAttachment">Upload</span><span wire:loading wire:target="uploadAttachment">Uploading…</span></button>
                    </form>
                    <div class="desk-hairline">
                        @forelse($attachments as $attachment)
                            <div class="desk-row flex items-center justify-between gap-4 py-3"><div><a href="{{ $attachment->url }}" target="_blank" class="font-semibold text-[#26221c] hover:text-[#8a4b2d]">{{ $attachment->original_filename }}</a><p class="desk-meta mt-1">{{ $attachment->human_size }} · {{ $attachment->created_at->format('M j, Y') }}@if($attachment->notes) · {{ $attachment->notes }}@endif</p></div><button type="button" wire:click="deleteAttachment({{ $attachment->id }})" wire:confirm="Delete this document?" class="text-xs text-[#b33a2b]">Delete</button></div>
                        @empty
                            <div class="desk-empty">No documents have been added.</div>
                        @endforelse
                    </div>
                </section>
            </main>

            <aside class="space-y-7 xl:border-l xl:border-[#e4ddd0] xl:pl-7">
                <section><p class="desk-section-label pb-3">Details</p><dl class="space-y-4 text-sm">
                    <div><dt class="desk-meta">Email</dt><dd>@if($person->email)<a href="mailto:{{ $person->email }}" class="font-semibold text-[#26221c] hover:text-[#8a4b2d]">{{ $person->email }}</a>@else<span class="text-[#8a8578]">Not set</span>@endif</dd></div>
                    <div><dt class="desk-meta">Phone</dt><dd>@if($person->phone)<a href="tel:{{ $person->phone }}" class="font-semibold text-[#26221c]">{{ $person->phone }}</a>@else<span class="text-[#8a8578]">Not set</span>@endif</dd></div>
                    <div><dt class="desk-meta">LinkedIn</dt><dd>@if($person->linkedin_url)<a href="{{ $person->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="desk-link">Open profile ↗</a>@else<span class="text-[#8a8578]">Not linked</span>@endif</dd></div>
                    <div><dt class="desk-meta">Source</dt><dd>{{ $person->source ?: 'Not recorded' }}</dd></div>
                </dl></section>

                @if(!empty($person->tags))
                    <section><p class="desk-section-label pb-3">Tags</p><div class="flex flex-wrap gap-2">@foreach($person->tags as $tag)<span class="rounded-full bg-[#f0eadd] px-3 py-1 text-xs font-semibold text-[#4a453b]">{{ $tag }}</span>@endforeach</div></section>
                @endif

                @if($person->organization)
                    <section><p class="desk-section-label pb-3">Organization</p><a href="{{ route('organizations.show', $person->organization) }}" wire:navigate class="desk-display font-semibold text-[#8a4b2d]">{{ $person->organization->name }} →</a><p class="desk-meta mt-2">{{ number_format($person->organization->people()->count()) }} contacts · {{ number_format($person->organization->meetings()->count()) }} meetings</p></section>
                @endif

                @if($topIssues->isNotEmpty())
                    <section><p class="desk-section-label pb-3">Topics discussed</p><div class="space-y-2">@foreach($topIssues as $issue)<div class="flex items-center justify-between gap-3 text-sm"><span>{{ $issue->name }}</span><span class="desk-data text-xs text-[#8a8578]">{{ $issue->meetings_count }}</span></div>@endforeach</div></section>
                @endif

                @if($person->notes)
                    <section><p class="desk-section-label pb-3">Relationship note</p><p class="text-sm leading-relaxed text-[#5c574d]">{{ $person->notes }}</p></section>
                @endif
            </aside>
        </div>
    @endif

    @if($showAddProjectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-[#26221c]/55 p-4" role="dialog" aria-modal="true" aria-labelledby="link-project-title">
            <div class="desk-modal-panel w-full max-w-md p-6">
                <div class="flex items-center justify-between gap-4"><h2 id="link-project-title" class="desk-display text-xl font-semibold">Link a project</h2><button type="button" wire:click="toggleAddProjectModal" class="text-xl text-[#8a8578]">×</button></div>
                <div class="mt-5 space-y-4">
                    <label class="block"><span class="desk-section-label">Project</span><input type="text" wire:model.live.debounce.300ms="projectSearch" placeholder="Search projects…" class="mt-2 w-full"></label>
                    @if($projectResults->count() && !$selectedProjectId)<div class="max-h-48 overflow-y-auto border-y border-[#e4ddd0]">@foreach($projectResults as $project)<button type="button" wire:click="selectProject({{ $project->id }})" class="desk-row block w-full px-2 py-3 text-left"><span class="font-semibold">{{ $project->name }}</span><span class="desk-meta ml-2">{{ Str::headline($project->status) }}</span></button>@endforeach</div>@endif
                    <label class="block"><span class="desk-section-label">Role or context</span><input type="text" wire:model="projectRole" placeholder="Primary contact, partner, advisor…" class="mt-2 w-full"></label>
                    <div class="flex justify-end gap-2"><button type="button" wire:click="toggleAddProjectModal" class="desk-button-secondary">Cancel</button><button type="button" wire:click="linkProject" class="desk-button-primary" @disabled(!$selectedProjectId)>Link project</button></div>
                </div>
            </div>
        </div>
    @endif
</div>
