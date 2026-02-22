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
                <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    Gmail read-only mode: send/compose disabled
                </span>
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
                        class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm cursor-not-allowed opacity-70"
                        title="Compose will be enabled once Gmail send scope is added"
                        disabled
                    >
                        Compose (Soon)
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
                            <li>Draft response text (you edit/send externally)</li>
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
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                Read-only
                            </span>
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
                                            class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-white dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            Generate Draft
                                        </button>
                                        @if($replyDraft !== '')
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
                                    placeholder="Generate a draft, edit it here, then send from Gmail while WRK is in read-only mode."
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
        </div>
    </div>
</div>
