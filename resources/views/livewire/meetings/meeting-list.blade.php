<div class="desk-page">
    <x-desk-page-header eyebrow="Relationship work" title="Meetings" description="A chronological agenda, plus the conversations that still need notes.">
        <x-slot:actions>
            <div class="desk-segmented" aria-label="Meeting ownership">
                <button type="button" wire:click="$set('scope', 'mine')" class="{{ $scope === 'mine' ? 'is-active' : '' }}">Mine</button>
                <button type="button" wire:click="$set('scope', 'all')" class="{{ $scope === 'all' ? 'is-active' : '' }}">Team</button>
            </div>
            <button type="button" wire:click="openBulkImport" class="desk-button-secondary">Bulk import</button>
            <a href="{{ route('meetings.create') }}" wire:navigate class="desk-button-primary">＋ Log meeting</a>
        </x-slot:actions>
    </x-desk-page-header>

    <section class="flex flex-wrap items-center justify-between gap-3">
        <div class="desk-filter-pills" aria-label="Meeting status">
            <button type="button" wire:click="$set('view', 'upcoming')" class="desk-filter-pill {{ in_array($view, ['', 'upcoming']) ? 'is-active' : '' }}">Upcoming · {{ number_format($stats['upcoming']) }}</button>
            <button type="button" wire:click="$set('view', 'needs_notes')" class="desk-filter-pill {{ $view === 'needs_notes' ? 'is-active' : '' }}">Needs notes · <span class="{{ $view === 'needs_notes' ? '' : 'text-[#b33a2b]' }}">{{ number_format($stats['needs_notes']) }}</span></button>
            <button type="button" wire:click="$set('view', 'completed')" class="desk-filter-pill {{ $view === 'completed' ? 'is-active' : '' }}">Past</button>
        </div>
        <div class="desk-toolbar">
            <label class="desk-search min-w-[15rem]">
                <span class="text-[#8a8578]">⌕</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search meetings…" aria-label="Search meetings">
            </label>
            <select wire:model.live="organization" aria-label="Filter by organization" class="!min-h-10 text-sm">
                <option value="">All organizations</option>
                @foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach
            </select>
        </div>
    </section>

    @if($stats['needs_notes'] > 0 && in_array($view, ['', 'upcoming', 'needs_notes']))
        <section class="desk-alert px-4 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="desk-section-label !text-[#8a4b2d]">Notes debt · {{ number_format($stats['needs_notes']) }}</p>
                    <p class="mt-1 text-sm text-[#5c574d]">Close the loop on the oldest conversations while the context is still fresh.</p>
                </div>
                <button type="button" wire:click="$set('view', 'needs_notes')" class="desk-link">Review all →</button>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach($needsNotesMeetings->sortBy('meeting_date')->take(3) as $meeting)
                    <article class="border-t border-[#e0c9b8] pt-3" wire:key="notes-debt-{{ $meeting->id }}">
                        <p class="desk-data text-[10px] text-[#8a8578]">{{ $meeting->meeting_date->format('M j, Y') }}</p>
                        <a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="desk-display mt-1 block text-base font-semibold hover:text-[#8a4b2d]">{{ $meeting->title }}</a>
                        <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold">
                            <a href="{{ route('meetings.edit', $meeting) }}" wire:navigate class="text-[#8a4b2d]">Add notes</a>
                            <a href="{{ route('meetings.edit', ['meeting' => $meeting, 'draft' => 'calendar']) }}" wire:navigate class="text-[#26221c]">✦ Draft from calendar</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if(in_array($view, ['', 'upcoming']))
        @php
            $agendaGroups = $upcomingMeetings->groupBy(function ($meeting) {
                return $meeting->meeting_date->isToday() ? 'Today' : ($meeting->meeting_date->isTomorrow() ? 'Tomorrow' : $meeting->meeting_date->format('l, F j'));
            });
        @endphp
        @forelse($agendaGroups as $dayLabel => $meetings)
            <section>
                <p class="desk-section-label pb-2 {{ $dayLabel === 'Today' ? '!text-[#8a4b2d]' : '' }}">{{ $dayLabel }}</p>
                <div class="desk-rule">
                    @foreach($meetings as $meeting)
                        <article class="desk-row grid gap-2 py-4 sm:grid-cols-[5.5rem_minmax(0,1fr)_auto] sm:items-start" wire:key="agenda-{{ $meeting->id }}">
                            <time class="desk-data text-xs text-[#5c574d]">{{ $meeting->start_time ? \Carbon\Carbon::parse($meeting->start_time)->format('g:i A') : '—' }}</time>
                            <div class="min-w-0">
                                <a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="desk-display text-lg font-semibold leading-tight hover:text-[#8a4b2d]">{{ $meeting->title }}</a>
                                <p class="desk-meta mt-1">
                                    {{ $meeting->location ?: 'Location not set' }}
                                    @if($meeting->people->isNotEmpty()) · {{ $meeting->people->take(3)->pluck('name')->join(', ') }}@endif
                                    @if($meeting->organizations->isNotEmpty()) · {{ $meeting->organizations->first()->name }}@endif
                                </p>
                            </div>
                            <a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="desk-link">Open →</a>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="desk-empty">No upcoming meetings match these filters. <a href="{{ route('meetings.create') }}" wire:navigate class="desk-link">＋ Log a meeting</a></div>
        @endforelse
    @elseif($view === 'needs_notes')
        <section>
            <p class="desk-section-label pb-2">Meetings awaiting notes</p>
            <div class="desk-rule">
                @forelse($needsNotesMeetings as $meeting)
                    <article class="desk-row grid gap-2 py-4 sm:grid-cols-[7rem_minmax(0,1fr)_auto] sm:items-center">
                        <time class="desk-data text-xs">{{ $meeting->meeting_date->format('M j, Y') }}</time>
                        <div><a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="desk-display text-lg font-semibold hover:text-[#8a4b2d]">{{ $meeting->title }}</a><p class="desk-meta mt-1">{{ $meeting->organizations->pluck('name')->join(', ') ?: 'No organization linked' }}</p></div>
                        <a href="{{ route('meetings.edit', $meeting) }}" wire:navigate class="desk-button-secondary">Add notes</a>
                    </article>
                @empty
                    <div class="desk-empty">No meetings need notes.</div>
                @endforelse
            </div>
        </section>
    @else
        <section>
            <p class="desk-section-label pb-2">Past meetings</p>
            <div class="desk-rule">
                @forelse($completedMeetings as $meeting)
                    <article class="desk-row grid gap-2 py-4 sm:grid-cols-[7rem_minmax(0,1fr)_auto] sm:items-center">
                        <time class="desk-data text-xs">{{ $meeting->meeting_date->format('M j, Y') }}</time>
                        <div><a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="desk-display text-lg font-semibold hover:text-[#8a4b2d]">{{ $meeting->title }}</a><p class="desk-meta mt-1">{{ $meeting->organizations->pluck('name')->join(', ') ?: 'No organization linked' }}</p></div>
                        <a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="desk-link">Notes →</a>
                    </article>
                @empty
                    <div class="desk-empty">No past meetings match these filters.</div>
                @endforelse
            </div>
            @if($completedMeetings->hasPages())<div class="mt-4">{{ $completedMeetings->links() }}</div>@endif
        </section>
    @endif

    @if($showBulkImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-[#26221c]/55 p-4" role="dialog" aria-modal="true" aria-labelledby="bulk-meeting-title">
            <div class="desk-modal-panel w-full max-w-2xl overflow-hidden">
                <div class="flex items-start justify-between border-b border-[#e4ddd0] px-6 py-4">
                    <div><h2 id="bulk-meeting-title" class="desk-display text-xl font-semibold">Bulk import meetings</h2><p class="mt-1 text-sm text-[#8a8578]">Paste calendar or email text and review what WRK extracts.</p></div>
                    <button type="button" wire:click="closeBulkImport" class="text-xl text-[#8a8578]">×</button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    @if($importError)<div class="desk-alert px-3 py-2 text-sm text-[#b33a2b]">{{ $importError }}</div>@endif
                    @if($importSuccess)<div class="desk-inset px-3 py-2 text-sm text-[#3b7a45]">{{ $importSuccess }}</div>@endif
                    @if(empty($extractedMeetings))
                        <label class="block"><span class="desk-section-label">Source text</span><textarea wire:model="bulkImportText" rows="9" class="mt-2 w-full" placeholder="Paste meeting details here…"></textarea></label>
                    @else
                        <div class="max-h-80 divide-y divide-[#e4ddd0] overflow-y-auto">
                            @foreach($extractedMeetings as $index => $meeting)
                                <div class="flex items-center justify-between gap-4 py-3"><div><p class="font-semibold">{{ $meeting['title'] ?? 'Untitled' }}</p><p class="desk-meta">{{ $meeting['date'] ?? 'No date' }}</p></div><button type="button" wire:click="removeExtractedMeeting({{ $index }})" class="text-[#b33a2b]">Remove</button></div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="flex justify-end gap-2 border-t border-[#e4ddd0] px-6 py-4">
                    <button type="button" wire:click="closeBulkImport" class="desk-button-secondary">Cancel</button>
                    @if(empty($extractedMeetings))
                        <button type="button" wire:click="extractMeetings" wire:loading.attr="disabled" class="desk-button-primary"><span wire:loading.remove wire:target="extractMeetings">✦ Extract meetings</span><span wire:loading wire:target="extractMeetings">Extracting…</span></button>
                    @else
                        <button type="button" wire:click="importMeetings" wire:loading.attr="disabled" class="desk-button-primary">Import {{ count($extractedMeetings) }}</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
