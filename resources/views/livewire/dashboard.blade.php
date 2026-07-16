<div class="app-page-frame min-h-[calc(100vh-5rem)]">
    @php
        $calendarStatus = $isCalendarConnected
            ? ($lastSyncAt ? 'Calendar last synced '.$lastSyncAt : 'Calendar connected')
            : 'Calendar is not connected';
        $meetingCount = (int) ($workspaceStats['meetings_today'] ?? 0);
        $overdueCount = (int) ($workspaceStats['tasks_overdue'] ?? 0);
        $timezone = auth()->user()->timezone ?: config('app.timezone');
        $location = auth()->user()->location ?: 'Workspace';
        $latestAssistantMessage = collect($conversationMessages)
            ->reverse()
            ->first(fn (array $message): bool => ($message['role'] ?? '') === 'assistant');
    @endphp

    @if($aiWarning || $calendarWarning || $gmailWarning || $passportWarning)
        <section class="desk-alert flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm text-[#5c574d]">
            <p>
                {{ $calendarWarning ?: $gmailWarning ?: $passportWarning ?: $aiWarning }}
            </p>
            @if($isCalendarConnected && ($calendarWarning || $gmailWarning))
                <button type="button" wire:click="syncCalendar" wire:loading.attr="disabled" class="desk-link">
                    Re-sync now →
                </button>
            @endif
        </section>
    @endif

    <header>
        <p class="desk-kicker">
            {{ now($timezone)->format('l, F j') }} · {{ $location }} ({{ now($timezone)->format('T') }})
        </p>
        <h1 class="desk-page-title mt-2">{{ $greeting }}, {{ $firstName }}.</h1>
        <p class="mt-2 text-sm text-[#5c574d]">
            {{ $meetingCount }} {{ Str::plural('meeting', $meetingCount) }} today.
            {{ $overdueCount }} overdue {{ Str::plural('task', $overdueCount) }}.
            {{ $calendarStatus }}@if($isCalendarConnected) —
                <button type="button" wire:click="syncCalendar" wire:loading.attr="disabled" class="desk-link align-baseline">
                    <span wire:loading.remove wire:target="syncCalendar">re-sync</span>
                    <span wire:loading wire:target="syncCalendar">syncing…</span>
                </button>@endif.
        </p>
    </header>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,3fr)_minmax(17rem,2fr)]">
        <div class="space-y-8">
            <section>
                <div class="flex items-center justify-between gap-4 pb-2">
                    <p class="desk-section-label">
                        Up next
                        @if($nextMeeting)
                            · {{ $nextMeeting['relative_label'] }}
                        @endif
                    </p>
                </div>
                <div class="desk-rule">
                    @if($nextMeeting)
                        <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <a href="{{ $nextMeeting['url'] }}" wire:navigate class="desk-display text-[1.35rem] font-semibold leading-tight text-[#26221c] hover:text-[#8a4b2d]">
                                    {{ $nextMeeting['title'] }}
                                </a>
                                <p class="mt-1 text-xs text-[#5c574d]">
                                    {{ $nextMeeting['time_label'] }} · {{ $nextMeeting['location'] }}
                                    @if(!empty($nextMeeting['organization']))
                                        · {{ $nextMeeting['organization'] }}
                                    @endif
                                </p>
                                <p class="desk-display mt-2 text-sm italic text-[#8a4b2d]">
                                    {{ $nextMeeting['key_ask'] ?: 'WRK can draft a talk track from the context already in your workspace.' }}
                                </p>
                            </div>
                            <button type="button" wire:click="useSmartAction('prep_next_meeting')" wire:loading.attr="disabled" class="desk-button-dark shrink-0">
                                ✦ Prep brief
                            </button>
                        </div>
                    @else
                        <div class="py-4 text-sm text-[#8a8578]">
                            Your calendar is clear. Use this block for focused work.
                        </div>
                    @endif
                </div>
            </section>

            <section>
                <p class="desk-section-label pb-2">Needs you</p>
                <div class="desk-hairline">
                    @forelse($urgentTasks->take(3) as $task)
                        @php
                            $isOverdue = $task->due_date && $task->due_date->isPast() && !$task->due_date->isToday();
                        @endphp
                        <div class="desk-row flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[#26221c]">
                                    {{ $task->title ?: $task->description }}
                                    @if($isOverdue)
                                        <span class="ml-2 text-[11px] font-semibold text-[#b33a2b]">overdue</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-[11px] text-[#8a8578]">
                                    {{ $task->project?->name ?: 'Standalone task' }}
                                    @if($task->due_date)
                                        · due {{ $task->due_date->format('M j') }}
                                    @endif
                                    · {{ $task->priority ?: 'medium' }}
                                </p>
                            </div>
                            <button type="button" wire:click="completeAction({{ $task->id }})" class="desk-link shrink-0">Mark done</button>
                        </div>
                    @empty
                        <div class="desk-row py-3 text-sm text-[#8a8578]">No urgent tasks right now.</div>
                    @endforelse

                    @if(($notesDebt['count'] ?? 0) > 0)
                        <div class="desk-row flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#26221c]">
                                    {{ number_format($notesDebt['count']) }} {{ Str::plural('meeting', $notesDebt['count']) }} need notes
                                </p>
                                @if(!empty($notesDebt['oldest']))
                                    <p class="mt-0.5 truncate text-[11px] text-[#8a8578]">
                                        Oldest: {{ $notesDebt['oldest']['title'] }} · {{ $notesDebt['oldest']['date_label'] }}
                                    </p>
                                @endif
                            </div>
                            <a href="{{ route('meetings.index', ['filter' => 'needs-notes']) }}" wire:navigate class="desk-link shrink-0">Review →</a>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="space-y-7">
            <section class="rounded-[0.625rem] bg-[#26221c] px-5 py-5 text-[#f7f3ec]">
                <p class="desk-section-label !text-[#c9bfa9]">Latest coverage</p>
                @if($latestCoverage)
                    <a href="{{ $latestCoverage['url'] }}" class="desk-display mt-3 block text-lg font-semibold leading-snug text-[#f7f3ec] hover:text-white" @if(Str::startsWith($latestCoverage['url'], 'http')) target="_blank" rel="noopener" @else wire:navigate @endif>
                        “{{ $latestCoverage['title'] }}” — {{ $latestCoverage['outlet'] }}
                    </a>
                    <p class="mt-3 text-[11px] text-[#c9bfa9]">{{ $latestCoverage['date_label'] }} · Media log →</p>
                @else
                    <p class="desk-display mt-3 text-lg font-medium">No coverage logged yet.</p>
                    <a href="{{ route('media.index') }}" wire:navigate class="mt-3 inline-block text-xs text-[#c9bfa9] hover:text-white">Open media log →</a>
                @endif
            </section>

            <section>
                <p class="desk-section-label pb-2">This week</p>
                <dl class="desk-hairline">
                    <div class="desk-row flex items-center justify-between py-3 text-xs">
                        <dt>Meetings logged</dt>
                        <dd class="desk-data">{{ number_format($thisWeekStats['meetings']) }}</dd>
                    </div>
                    <div class="desk-row flex items-center justify-between py-3 text-xs">
                        <dt>Campaign sends</dt>
                        <dd class="desk-data">{{ number_format($thisWeekStats['campaign_sends']) }}</dd>
                    </div>
                    <div class="desk-row flex items-center justify-between py-3 text-xs">
                        <dt>New contacts</dt>
                        <dd class="desk-data">{{ number_format($thisWeekStats['new_contacts']) }}</dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>

    @if(!empty($companionSuggestions))
        <section>
            <div class="flex items-center justify-between pb-2">
                <p class="desk-section-label">WRK suggestions</p>
                <button type="button" wire:click="clearCompanionSuggestions" class="text-xs text-[#8a8578] hover:text-[#8a4b2d]">Clear</button>
            </div>
            <div class="desk-hairline">
                @foreach(array_slice($companionSuggestions, 0, 4) as $index => $suggestion)
                    <div wire:key="morning-suggestion-{{ $suggestion['id'] }}" class="desk-row flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-[#26221c]">{{ $suggestion['title'] }}</p>
                            @if(!empty($suggestion['reason']))
                                <p class="desk-display mt-0.5 text-sm italic text-[#8a4b2d]">{{ $suggestion['reason'] }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <button type="button" wire:click="dismissCompanionSuggestion('{{ $suggestion['id'] }}')" class="text-xs text-[#8a8578]">Dismiss</button>
                            <button type="button" wire:click="applyCompanionSuggestion('{{ $suggestion['id'] }}')" class="desk-link">Approve →</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-auto pt-4">
        @if($latestAssistantMessage)
            <div class="desk-inset mb-2 px-4 py-3">
                <p class="desk-display text-sm italic text-[#8a4b2d]">{{ Str::limit($latestAssistantMessage['content'] ?? '', 260) }}</p>
            </div>
        @endif
        <form wire:submit.prevent="submitOmni" class="desk-command-bar flex items-center gap-3 px-4 py-2.5">
            <label for="morning-desk-command" class="desk-display shrink-0 text-sm italic text-[#8a4b2d]">Ask WRK</label>
            <input id="morning-desk-command" type="text" wire:model="omniInput" placeholder="Log a meeting, prep a brief, capture a task…" class="!min-h-0 flex-1 !border-0 !bg-transparent !p-0 text-sm !shadow-none focus:!ring-0">
            <button type="submit" wire:loading.attr="disabled" class="desk-button-primary !min-h-8 !px-3 !py-1.5">
                <span wire:loading.remove wire:target="submitOmni">Enter</span>
                <span wire:loading wire:target="submitOmni">Working…</span>
            </button>
            <kbd class="hidden rounded border border-[#d8d0bf] px-1.5 py-0.5 font-mono text-[10px] text-[#8a8578] sm:inline">⌘K</kbd>
        </form>
    </section>

    @if($showTimezonePrompt)
        <livewire:components.timezone-location :isPrompt="true" />
    @endif
</div>
