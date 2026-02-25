<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        @if($aiWarning || $calendarWarning || $gmailWarning || $passportWarning)
            <div class="space-y-2">
                @if($aiWarning)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300">
                        {{ $aiWarning }}
                    </div>
                @endif
                @if($calendarWarning)
                    <div class="rounded-lg border border-blue-200 bg-blue-50 text-blue-800 px-4 py-3 text-sm dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300">
                        {{ $calendarWarning }}
                    </div>
                @endif
                @if($gmailWarning)
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-800 px-4 py-3 text-sm dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-300">
                        {{ $gmailWarning }}
                    </div>
                @endif
                @if($passportWarning)
                    <a href="{{ route('profile.travel') }}" wire:navigate
                        class="block rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm dark:bg-red-900/30 dark:border-red-800 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                        ✈️ {{ $passportWarning }}
                    </a>
                @endif
            </div>
        @endif

        <section class="space-y-4">
            <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                <span>{{ $workspaceDateLabel }}</span>
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <livewire:components.timezone-location />
            </div>

            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="space-y-2 max-w-3xl">
                    <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-gray-900 dark:text-white">
                        {{ $greeting }}, {{ $firstName }}.
                    </h1>
                    <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ $focusNarrative }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if($isCalendarConnected)
                        <button wire:click="syncCalendar" wire:loading.attr="disabled"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4 {{ $isSyncing ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="ml-2" wire:loading.remove wire:target="syncCalendar">
                                {{ $lastSyncAt ? 'Synced '.$lastSyncAt : 'Sync calendar' }}
                            </span>
                            <span class="ml-2" wire:loading wire:target="syncCalendar">Syncing...</span>
                        </button>

                        <button wire:click="syncGmail" wire:loading.attr="disabled"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4 {{ $isSyncingGmail ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            <span class="ml-2" wire:loading.remove wire:target="syncGmail">
                                {{ $lastGmailSyncAt ? 'Gmail '.$lastGmailSyncAt : 'Sync Gmail' }}
                            </span>
                            <span class="ml-2" wire:loading wire:target="syncGmail">Syncing...</span>
                        </button>

                        <form action="{{ route('google.disconnect') }}" method="POST" class="inline-flex">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-3 py-2 border border-rose-300 dark:border-rose-700 text-sm font-medium rounded-lg text-rose-700 dark:text-rose-300 bg-white dark:bg-gray-800 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors">
                                Disconnect Google
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 sm:p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Workspace Conversation</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Your companion proposes, you approve</span>
            </div>
            @php
                $hasConversation = !empty($conversationMessages);
            @endphp

            <div
                x-data
                x-init="$nextTick(() => { if ($refs.thread) { $refs.thread.scrollTop = $refs.thread.scrollHeight; } })"
                x-on:workspace-thread-updated.window="$nextTick(() => { if ($refs.thread) { $refs.thread.scrollTop = $refs.thread.scrollHeight; } })"
                class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div x-ref="thread" class="{{ $hasConversation ? 'h-[360px]' : 'h-[150px]' }} overflow-y-auto p-4 space-y-3 bg-gray-50 dark:bg-gray-900/40">
                    @forelse($conversationMessages as $message)
                        @php
                            $isUser = ($message['role'] ?? 'assistant') === 'user';
                        @endphp
                        <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[94%] rounded-xl px-3 py-2 {{ $isUser ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600' }}">
                                <div class="text-[11px] mb-1 {{ $isUser ? 'text-indigo-100' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $isUser ? 'You' : 'WRK Assistant' }} • {{ $message['timestamp'] ?? '' }}
                                </div>
                                <div class="whitespace-pre-wrap text-sm">{{ $message['content'] ?? '' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                Start with a quick brain dump and I will propose tasks you can approve.
                            </p>
                            <div class="mt-3 flex flex-wrap justify-center gap-2">
                                <button
                                    type="button"
                                    wire:click="useSmartAction('brain_dump')"
                                    class="px-3 py-1.5 text-xs font-medium rounded-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Start morning brain dump
                                </button>
                                <button
                                    type="button"
                                    wire:click="useSmartAction('remind')"
                                    class="px-3 py-1.5 text-xs font-medium rounded-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Add Monday reminder
                                </button>
                            </div>
                        </div>
                    @endforelse
                </div>

                <form wire:submit.prevent="submitOmni" class="space-y-3 p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>

                        <textarea
                            wire:model="omniInput"
                            rows="{{ $hasConversation ? 3 : 2 }}"
                            placeholder="Dump your notes, ask a question, or capture a reminder..."
                            class="flex-1 text-base sm:text-lg rounded-xl border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-indigo-500 focus:border-indigo-500 resize-y min-h-[76px]"
                        ></textarea>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="submitOmni"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove wire:target="submitOmni">Enter</span>
                            <span wire:loading wire:target="submitOmni">Working...</span>
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach($smartActions as $action)
                            <button
                                type="button"
                                wire:click="useSmartAction('{{ $action['key'] }}')"
                                class="px-3 py-1.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                {{ $action['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Shortcuts: <span class="font-mono">/task</span>, <span class="font-mono">/remind</span>,
                        <span class="font-mono">/sync gmail</span>, <span class="font-mono">/help</span>. Free-form notes create suggested actions only until you approve.
                    </p>
                </form>
            </div>
        </section>

        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Suggested Actions</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Your companion infers tasks from notes. Nothing is created until you click an action button.
                    </p>
                </div>
                @if(!empty($companionSuggestions))
                    <button
                        type="button"
                        wire:click="clearCompanionSuggestions"
                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Clear all
                    </button>
                @endif
            </div>

            @if(!empty($companionSuggestions))
                <div class="space-y-3">
                    @foreach($companionSuggestions as $suggestion)
                        @php
                            $type = $suggestion['type'] ?? 'task';
                            $actionLabel = match ($type) {
                                'task' => 'Create task',
                                'reminder' => 'Create reminder',
                                'draft_email' => 'Draft email',
                                'create_project' => 'Create project',
                                'create_subproject' => 'Create subproject',
                                default => 'Apply',
                            };
                            $loadingLabel = match ($type) {
                                'task' => 'Creating...',
                                'reminder' => 'Creating...',
                                'draft_email' => 'Drafting...',
                                'create_project' => 'Creating...',
                                'create_subproject' => 'Creating...',
                                default => 'Working...',
                            };
                            $typeLabel = match ($type) {
                                'draft_email' => 'Email',
                                'create_project' => 'Project',
                                'create_subproject' => 'Subproject',
                                default => ucfirst($type),
                            };
                            $confidenceClass = match ($suggestion['confidence'] ?? 'medium') {
                                'high' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                'low' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                            };
                        @endphp
                        <div wire:key="suggestion-{{ $loop->index }}-{{ $suggestion['id'] ?? $loop->index }}" class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                            {{ $typeLabel }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $confidenceClass }}">
                                            {{ ucfirst($suggestion['confidence'] ?? 'medium') }} confidence
                                        </span>
                                        @if(!empty($suggestion['due_label']))
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                Due {{ $suggestion['due_label'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-2 font-medium text-gray-900 dark:text-white break-words">
                                        {{ $suggestion['title'] ?? 'Untitled suggestion' }}
                                    </p>
                                    @if(!empty($suggestion['reason']))
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $suggestion['reason'] }}</p>
                                    @endif
                                    @if(!empty($suggestion['links']) && is_array($suggestion['links']))
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($suggestion['links'] as $link)
                                                @if(is_array($link) && !empty($link['type']) && !empty($link['name']))
                                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                        Linked {{ ucfirst($link['type']) }}: {{ $link['name'] }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    @if(!empty($suggestion['unresolved_project_name']))
                                        <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">
                                            No existing project match for "{{ $suggestion['unresolved_project_name'] }}". A "Create project" suggestion was added.
                                        </p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="applyCompanionSuggestionAt({{ $loop->index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="applyCompanionSuggestionAt({{ $loop->index }})"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        <span wire:loading.remove wire:target="applyCompanionSuggestionAt({{ $loop->index }})">
                                            {{ $actionLabel }}
                                        </span>
                                        <span wire:loading wire:target="applyCompanionSuggestionAt({{ $loop->index }})">
                                            {{ $loadingLabel }}
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="dismissCompanionSuggestionAt({{ $loop->index }})"
                                        wire:loading.attr="disabled"
                                        wire:target="applyCompanionSuggestionAt({{ $loop->index }}),dismissCompanionSuggestionAt({{ $loop->index }})"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-60 disabled:cursor-not-allowed"
                                    >
                                        <span wire:loading.remove wire:target="dismissCompanionSuggestionAt({{ $loop->index }})">Dismiss</span>
                                        <span wire:loading wire:target="dismissCompanionSuggestionAt({{ $loop->index }})">Dismissing...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-4 text-sm text-gray-500 dark:text-gray-400">
                    Drop your stream-of-consciousness notes and I will propose tasks, reminders, email drafts, and project ideas for approval.
                </div>
            @endif
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wide">Up Next</h2>

                    @if($nextMeeting)
                        <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/40 bg-indigo-50/60 dark:bg-indigo-900/20 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">
                                        {{ $nextMeeting['date_label'] }} · {{ $nextMeeting['relative_label'] }}
                                    </p>
                                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $nextMeeting['title'] }}</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $nextMeeting['time_label'] }} · {{ $nextMeeting['location'] }}
                                        @if(!empty($nextMeeting['organization']))
                                            · {{ $nextMeeting['organization'] }}
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ $nextMeeting['url'] }}" wire:navigate
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100/60 dark:hover:bg-indigo-900/40">
                                    Open meeting
                                </a>
                            </div>

                            <div class="mt-3 rounded-lg bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Prep cue</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200">
                                    {{ $nextMeeting['key_ask'] ?: 'Use “Prep next meeting” in the workspace bar to generate a brief and talk track.' }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-4 text-sm text-gray-500 dark:text-gray-400">
                            No upcoming meeting found. You can use this block for deep work.
                        </div>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wide">Now / Next / Later</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-lg border border-red-100 dark:border-red-900/40 bg-red-50/50 dark:bg-red-900/10 p-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300 mb-2">Now</div>
                            @forelse($nowTasks as $task)
                                <div class="text-sm text-gray-800 dark:text-gray-100 mb-2 last:mb-0">• {{ $task->title ?: $task->description }}</div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">No immediate tasks.</div>
                            @endforelse
                        </div>

                        <div class="rounded-lg border border-amber-100 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-900/10 p-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300 mb-2">Next</div>
                            @forelse($nextTasks as $task)
                                <div class="text-sm text-gray-800 dark:text-gray-100 mb-2 last:mb-0">
                                    • {{ $task->title ?: $task->description }}
                                    @if($task->due_date)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $task->due_date->format('M j') }})</span>
                                    @endif
                                </div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">No near-term queue.</div>
                            @endforelse
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300 mb-2">Later</div>
                            @forelse($laterTasks as $task)
                                <div class="text-sm text-gray-800 dark:text-gray-100 mb-2 last:mb-0">• {{ $task->title ?: $task->description }}</div>
                            @empty
                                <div class="text-sm text-gray-500 dark:text-gray-400">Nothing parked.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide">Priority Queue</h2>
                        <a href="{{ route('meetings.index') }}" wire:navigate class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            View full workflow
                        </a>
                    </div>

                    @if($urgentTasks->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($urgentTasks as $task)
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ $task->title ?: $task->description }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $task->project?->name ?: 'Standalone task' }}
                                            @if($task->due_date)
                                                · due {{ $task->due_date->format('M j') }}
                                            @endif
                                            · {{ ucfirst($task->priority ?: 'medium') }}
                                        </p>
                                    </div>
                                    <button wire:click="completeAction({{ $task->id }})"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-lg border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/30">
                                        Complete
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-4 text-sm text-gray-500 dark:text-gray-400">
                            No urgent tasks. Capture a new one with <span class="font-mono">/task</span>.
                        </div>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-4 space-y-6">
                <a href="{{ $dailyPulse['url'] }}" wire:navigate
                    class="block bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-5 text-white hover:from-gray-800 hover:to-gray-700 transition-colors">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-300 mb-2">{{ $dailyPulse['title'] }}</p>
                    <p class="text-base font-medium leading-relaxed mb-3">{{ $dailyPulse['body'] }}</p>
                    <p class="text-xs text-gray-300">{{ $dailyPulse['meta'] }}</p>
                </a>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 uppercase tracking-wide">Quick Access</h2>
                    <div class="space-y-2">
                        <a href="{{ route('meetings.create') }}" wire:navigate class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200">
                            <span>Log meeting</span>
                            <span>→</span>
                        </a>
                        <a href="{{ route('projects.index') }}" wire:navigate class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200">
                            <span>Open projects</span>
                            <span>→</span>
                        </a>
                        <a href="{{ route('travel.index') }}" wire:navigate class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200">
                            <span>Travel workspace</span>
                            <span>→</span>
                        </a>
                        <a href="{{ route('knowledge.base') }}" wire:navigate class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200">
                            <span>Knowledge search</span>
                            <span>→</span>
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('grants.index') }}" wire:navigate class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200">
                                <span>Funders & reporting</span>
                                <span>→</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if($showTimezonePrompt)
            <livewire:components.timezone-location :isPrompt="true" />
        @endif
    </div>
</div>
