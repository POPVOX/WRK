<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-4 sm:p-6">
    <div class="max-w-[1800px] mx-auto space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Inbox</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Communications workspace with agent suggestions, context linking, and tracked follow-through.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($readOnlyMode)
                    <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        Gmail connected in read-only scope. Reconnect Google to enable send/compose.
                    </span>
                @else
                    <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
                        Gmail compose + send enabled
                    </span>
                @endif
                <button
                    type="button"
                    wire:click="syncGmail"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <svg class="w-4 h-4 {{ $isSyncingGmail ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span class="ml-2" wire:loading.remove wire:target="syncGmail">
                        {{ $lastGmailSyncAt ? 'Gmail '.$lastGmailSyncAt : 'Sync Gmail' }}
                    </span>
                    <span class="ml-2" wire:loading wire:target="syncGmail">Syncing...</span>
                </button>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-700 dark:bg-gray-800">
            <div class="grid h-[calc(100vh-12rem)] grid-cols-1 lg:grid-cols-[15rem_22rem_minmax(0,1fr)] 2xl:grid-cols-[15rem_22rem_minmax(0,1fr)_20rem]">
                <aside class="border-r border-gray-200 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-900/30 p-3 overflow-y-auto">
                    <button
                        type="button"
                        wire:click="openComposer"
                        class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @disabled($readOnlyMode)
                        title="{{ $readOnlyMode ? 'Reconnect Google with Gmail compose/send scope first.' : 'Compose a new email' }}"
                    >
                        Compose
                    </button>

                    <nav class="mt-4 space-y-1">
                        @foreach($this->folders as $key => $label)
                            <button
                                type="button"
                                wire:click="$set('folder', '{{ $key }}')"
                                class="w-full flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition {{ $folder === $key ? 'bg-white text-indigo-700 border border-indigo-100 shadow-sm dark:bg-gray-800 dark:border-indigo-800 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                            >
                                <span>{{ $label }}</span>
                                <span class="text-xs rounded-full px-2 py-0.5 {{ $folder === $key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                                    {{ $folderCounts[$key] ?? 0 }}
                                </span>
                            </button>
                        @endforeach
                    </nav>

                    <div class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">What Agent Can Do</h3>
                        <ul class="mt-2 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                            <li>Create follow-up tasks from threads</li>
                            <li>Suggest project linkage</li>
                            <li>Draft response text and send from WRK</li>
                            <li>Link contacts to projects</li>
                        </ul>
                    </div>
                </aside>

                <section class="border-r border-gray-200 dark:border-gray-700 flex flex-col min-h-0">
                    <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search mail, contact, organization..."
                                class="w-full rounded-lg border-gray-200 pl-9 pr-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        @forelse($threadSummaries as $thread)
                            @php
                                $isSelected = ($selectedThread['thread_key'] ?? null) === $thread['thread_key'];
                                $dotClass = match($thread['sentiment']) {
                                    'critical' => 'bg-red-500',
                                    'positive' => 'bg-emerald-500',
                                    default => 'bg-gray-300',
                                };
                            @endphp
                            <button
                                type="button"
                                wire:click="selectThread('{{ $thread['thread_key'] }}')"
                                class="w-full text-left px-4 py-3 border-b border-gray-100 transition {{ $isSelected ? 'bg-indigo-50/70 dark:bg-indigo-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40' }} dark:border-gray-700"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="h-2 w-2 rounded-full {{ $dotClass }}"></span>
                                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $thread['counterpart_name'] }}</p>
                                        </div>
                                        <p class="mt-1 truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $thread['subject'] }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $thread['preview'] }}</p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $thread['date_label'] }}</p>
                                        @if(($thread['message_count'] ?? 0) > 1)
                                            <span class="mt-1 inline-flex rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $thread['message_count'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if(!empty($thread['labels']))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($thread['labels'] as $label)
                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $label }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </button>
                        @empty
                            <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
                                No messages found for this filter yet.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="flex flex-col min-h-0">
                    @if($selectedThread)
                        <header class="h-16 border-b border-gray-100 px-4 flex items-center justify-between dark:border-gray-700">
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedThread['subject'] }}</h2>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                    {{ $selectedThread['counterpart_name'] }}
                                    @if(!empty($selectedThread['counterpart_email']))
                                        &lt;{{ $selectedThread['counterpart_email'] }}&gt;
                                    @endif
                                </p>
                            </div>
                            @if($readOnlyMode)
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                    Reconnect Google for send
                                </span>
                            @else
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    Send enabled
                                </span>
                            @endif
                        </header>

                        <div class="flex-1 overflow-y-auto p-4 space-y-6 bg-gray-50/60 dark:bg-gray-900/30">
                            <div class="space-y-3">
                                @foreach($selectedThread['messages'] as $message)
                                    <div class="flex {{ $message['is_inbound'] ? 'justify-start' : 'justify-end' }}">
                                        <article class="max-w-[90%] rounded-xl px-3 py-2 text-sm {{ $message['is_inbound'] ? 'bg-white border border-gray-200 text-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100' : 'bg-indigo-600 text-white' }}">
                                            <p class="text-[11px] {{ $message['is_inbound'] ? 'text-gray-500 dark:text-gray-400' : 'text-indigo-100' }}">
                                                {{ $message['sender_name'] }} • {{ $message['sent_label'] }}
                                            </p>
                                            <p class="mt-1 whitespace-pre-wrap leading-relaxed">{{ $message['snippet'] }}</p>
                                            @if(!empty($message['labels']))
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($message['labels'] as $label)
                                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] {{ $message['is_inbound'] ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200' : 'bg-indigo-500 text-indigo-50' }}">
                                                            {{ $label }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </article>
                                    </div>
                                @endforeach
                            </div>

                            <article class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
                                <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">Agent Analysis</h3>
                                <p class="mt-1 text-sm text-indigo-800 dark:text-indigo-300 leading-relaxed">{{ $selectedThread['agent_analysis'] }}</p>

                                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                    @foreach($selectedThread['suggestions'] as $suggestion)
                                        <div class="rounded-lg border border-indigo-100 bg-white p-3 dark:border-indigo-800 dark:bg-gray-800">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $suggestion['title'] }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $suggestion['body'] }}</p>
                                            <button
                                                type="button"
                                                wire:click="{{ $suggestion['action'] }}"
                                                class="mt-3 inline-flex items-center rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                            >
                                                {{ $suggestion['button'] }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        </div>

                        <footer class="border-t border-gray-100 p-4 space-y-3 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-wrap items-center gap-2">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Link to project</label>
                                <select
                                    wire:model.live="selectedProjectId"
                                    class="min-w-[250px] rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">No project selected</option>
                                    @foreach($selectedThread['project_candidates'] as $candidate)
                                        <option value="{{ $candidate['id'] }}">
                                            {{ $candidate['name'] }} ({{ ucfirst($candidate['status']) }})
                                        </option>
                                    @endforeach
                                </select>
                                @if(!empty($selectedThread['project_candidates']))
                                    <button
                                        type="button"
                                        wire:click="createProjectFromThread"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        Create New Project
                                    </button>
                                @endif
                            </div>

                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Reply Draft</h4>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="generateReplyDraft"
                                            wire:loading.attr="disabled"
                                            wire:target="generateReplyDraft,sendReplyDraft,openComposerForReply"
                                            class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-white dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            <span wire:loading.remove wire:target="generateReplyDraft">Generate Draft</span>
                                            <span wire:loading wire:target="generateReplyDraft">Generating...</span>
                                        </button>
                                        @if($replyDraft !== '')
                                            <button
                                                type="button"
                                                wire:click="sendReplyDraft"
                                                wire:loading.attr="disabled"
                                                wire:target="sendReplyDraft"
                                                @disabled($readOnlyMode)
                                                class="inline-flex items-center rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                                                title="{{ $readOnlyMode ? 'Reconnect Google with Gmail send scope first.' : 'Send this reply now' }}"
                                            >
                                                <span wire:loading.remove wire:target="sendReplyDraft">Send Reply</span>
                                                <span wire:loading wire:target="sendReplyDraft">Sending...</span>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openComposerForReply"
                                                wire:loading.attr="disabled"
                                                wire:target="openComposerForReply"
                                                @disabled($readOnlyMode)
                                                class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-white disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                                title="{{ $readOnlyMode ? 'Reconnect Google with Gmail compose scope first.' : 'Open reply in composer' }}"
                                            >
                                                Open in Composer
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="clearReplyDraft"
                                                class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-white dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                            >
                                                Clear
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <textarea
                                    wire:model="replyDraft"
                                    rows="4"
                                    class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Generate a draft, edit it here, and send it from WRK."
                                ></textarea>
                            </div>
                        </footer>
                    @else
                        <div class="flex-1 flex items-center justify-center text-center p-8 text-sm text-gray-500 dark:text-gray-400">
                            Select an email thread to review context and actions.
                        </div>
                    @endif
                </section>

                <aside class="hidden 2xl:block border-l border-gray-200 bg-gray-50/70 p-4 overflow-y-auto dark:border-gray-700 dark:bg-gray-900/30">
                    @if($selectedThread)
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Contact Context</h3>

                        <div class="mt-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            @if($selectedThread['person'])
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedThread['person']->name }}</p>
                                @if($selectedThread['person']->title)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $selectedThread['person']->title }}</p>
                                @endif
                                @if($selectedThread['person']->email)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $selectedThread['person']->email }}</p>
                                @endif
                                <a href="{{ route('contacts.show', $selectedThread['person']) }}" wire:navigate
                                    class="mt-3 inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    Open contact
                                </a>
                            @else
                                <p class="text-sm text-gray-600 dark:text-gray-300">This sender is not yet linked to a contact.</p>
                            @endif
                        </div>

                        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Organization</h4>
                            @if($selectedThread['organization'])
                                <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $selectedThread['organization']->name }}</p>
                                <a href="{{ route('organizations.show', $selectedThread['organization']) }}" wire:navigate
                                    class="mt-2 inline-flex items-center rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    Open organization
                                </a>
                            @else
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">No organization linked yet.</p>
                            @endif
                        </div>

                        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project Candidates</h4>
                            @if(!empty($selectedThread['project_candidates']))
                                <div class="mt-2 space-y-2">
                                    @foreach($selectedThread['project_candidates'] as $project)
                                        <div class="rounded-lg border border-gray-200 px-2.5 py-2 dark:border-gray-700">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $project['name'] }}</p>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ ucfirst($project['status']) }} · {{ str_replace('_', ' ', $project['source']) }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">No project matches found yet.</p>
                            @endif
                        </div>
                    @else
                        <div class="h-full flex items-center justify-center text-center text-sm text-gray-500 dark:text-gray-400">
                            Context details appear when you select a thread.
                        </div>
                    @endif
                </aside>
            </div>

            @if($showComposer)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" wire:keydown.escape="closeComposer">
                    <section class="w-full max-w-4xl rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
                        <header class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Compose Email</h2>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Send or save to Gmail drafts directly from WRK.</p>
                            </div>
                            <button
                                type="button"
                                wire:click="closeComposer"
                                class="rounded-lg border border-gray-300 px-2.5 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                Close
                            </button>
                        </header>

                        <div class="space-y-3 p-5">
                            @if($composeStatus !== '')
                                @php
                                    $composeStatusClass = match($composeStatusType) {
                                        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200',
                                        'error' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200',
                                        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200',
                                        default => 'border-gray-200 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-200',
                                    };
                                @endphp
                                <div class="rounded-lg border px-3 py-2 text-sm {{ $composeStatusClass }}">
                                    {{ $composeStatus }}
                                </div>
                            @endif

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="space-y-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">To</span>
                                    <input
                                        type="text"
                                        wire:model="composeTo"
                                        placeholder="name@example.org"
                                        class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                </label>
                                <label class="space-y-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cc</span>
                                    <input
                                        type="text"
                                        wire:model="composeCc"
                                        placeholder="optional"
                                        class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                </label>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="space-y-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bcc</span>
                                    <input
                                        type="text"
                                        wire:model="composeBcc"
                                        placeholder="optional"
                                        class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                </label>
                                <label class="space-y-1 sm:col-span-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subject</span>
                                    <input
                                        type="text"
                                        wire:model="composeSubject"
                                        placeholder="Subject"
                                        class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                </label>
                            </div>

                            <label class="space-y-1 block">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Message</span>
                                <textarea
                                    wire:model="composeBody"
                                    rows="10"
                                    placeholder="Write your message..."
                                    class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                ></textarea>
                            </label>
                        </div>

                        <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                            <button
                                type="button"
                                wire:click="saveComposeDraft"
                                wire:loading.attr="disabled"
                                wire:target="saveComposeDraft,sendCompose"
                                @disabled($readOnlyMode)
                                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                <span wire:loading.remove wire:target="saveComposeDraft">Save Draft</span>
                                <span wire:loading wire:target="saveComposeDraft">Saving...</span>
                            </button>
                            <button
                                type="button"
                                wire:click="sendCompose"
                                wire:loading.attr="disabled"
                                wire:target="sendCompose,saveComposeDraft"
                                @disabled($readOnlyMode)
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="sendCompose">Send Email</span>
                                <span wire:loading wire:target="sendCompose">Sending...</span>
                            </button>
                        </footer>
                    </section>
                </div>
            @endif

            <div class="border-t border-gray-200 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Inbox Action Log</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Latest {{ count($inboxActionLogs) }} actions</span>
                </div>

                @if($inboxActionLogs->isNotEmpty())
                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($inboxActionLogs as $log)
                            @php
                                $statusClass = match($log->action_status) {
                                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                    'queued' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                    default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                };
                            @endphp
                            <article class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800" wire:key="inbox-log-{{ $log->id }}">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $log->action_label }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">
                                        {{ strtoupper($log->action_status) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at?->format('M j, g:i A') }} · {{ str_replace('_', ' ', $log->suggestion_key) }}
                                </p>
                                @if($log->subject)
                                    <p class="mt-1 text-xs text-gray-700 dark:text-gray-300 line-clamp-2">Thread: {{ $log->subject }}</p>
                                @endif
                                @if($log->counterpart_name)
                                    <p class="mt-1 text-xs text-gray-700 dark:text-gray-300">
                                        Contact: {{ $log->counterpart_name }}
                                        @if($log->counterpart_email)
                                            ({{ $log->counterpart_email }})
                                        @endif
                                    </p>
                                @endif
                                @if($log->project)
                                    <p class="mt-1 text-xs text-gray-700 dark:text-gray-300">Project: {{ $log->project->name }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        No inbox actions logged yet. Approve an agent suggestion to begin the audit trail.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
