<div class="app-page-frame space-y-8">
    <div class="flex flex-col gap-4">
        <div class="app-page-head">
            <div>
                <h1 class="app-page-title">Intelligence</h1>
                <p class="app-page-lead">
                    Agent command center for approvals, specialist operations, and auditable runs.
                </p>
            </div>
            <div class="inline-flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <span>Last refreshed {{ $generatedAt }}</span>
                <button wire:click="refresh"
                    class="rounded-lg bg-gray-900 px-3 py-1.5 font-medium text-white transition hover:bg-black dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                    Refresh
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <nav class="app-tabset">
                <a href="{{ route('intelligence.index') }}" wire:navigate
                    class="app-tab {{ $panel === 'home' ? 'app-tab-active' : '' }}">
                    Command Center
                </a>
                <a href="{{ route('intelligence.files') }}" wire:navigate
                    class="app-tab {{ $panel === 'files' ? 'app-tab-active' : '' }}">
                    Files
                </a>
                <a href="{{ route('intelligence.agents') }}" wire:navigate
                    class="app-tab {{ $panel === 'agents' ? 'app-tab-active' : '' }}">
                    Agents
                </a>
            </nav>
            <div class="app-link-group">
                <a href="{{ route('intelligence.create') }}" wire:navigate class="hover:text-gray-900 dark:hover:text-gray-100">Create</a>
                <span>•</span>
                <a href="{{ route('intelligence.audit') }}" wire:navigate class="hover:text-gray-900 dark:hover:text-gray-100">Audit</a>
            </div>
        </div>
    </div>

    @if(!$migrationReady)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
            <h2 class="text-lg font-semibold">Agent stack not initialized</h2>
            <p class="mt-2 text-sm">{{ $migrationMessage }}</p>
            <p class="mt-3 text-sm">Run: <span class="font-mono">php artisan migrate --force</span></p>
        </section>
    @endif

    @if($migrationReady)
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full bg-indigo-100 px-2.5 py-1 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    Create specialist: {{ ($permissionSnapshot['can_create_specialist'] ?? false) ? 'Yes' : 'No' }}
                </span>
                <span class="rounded-full bg-indigo-100 px-2.5 py-1 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    Create project agents: {{ ($permissionSnapshot['can_create_project'] ?? false) ? 'Yes' : 'No' }}
                </span>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    Project scope: {{ str_replace('_', ' ', $permissionSnapshot['project_scope'] ?? 'all') }}
                </span>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    Medium approvals: {{ ($permissionSnapshot['can_approve_medium_risk'] ?? false) ? 'Yes' : 'No' }}
                </span>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    High approvals: {{ ($permissionSnapshot['can_approve_high_risk'] ?? false) ? 'Yes' : 'No' }}
                </span>
                @if(auth()->user()?->isAdmin())
                    <a href="{{ route('admin.permissions') }}" wire:navigate
                        class="ml-auto rounded-lg border border-gray-300 px-2.5 py-1 font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Manage permissions
                    </a>
                @endif
            </div>
        </section>
    @endif

    @if($panel === 'home')
        @if($migrationReady)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Approval Queue</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Pending actions from all agents you can access.</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ count($homePendingSuggestions) }} pending
                    </span>
                </div>

                @if(empty($homePendingSuggestions))
                    <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        No pending suggestions right now.
                    </div>
                @else
                    <div class="mt-5 space-y-3">
                        @foreach($homePendingSuggestions as $suggestion)
                            @php
                                $riskClass = match($suggestion['risk_level']) {
                                    'high' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    'low' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                };
                            @endphp
                            <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700" wire:key="home-suggestion-{{ $suggestion['id'] }}">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ str_replace('_', ' ', $suggestion['suggestion_type']) }}
                                    </span>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $riskClass }}">
                                        {{ strtoupper($suggestion['risk_level']) }}
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ str_replace('_', ' ', $suggestion['governance_mode']) }}
                                    </span>
                                    @if(!empty($suggestion['agent_name']))
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $suggestion['agent_name'] }}
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $suggestion['title'] }}</h3>
                                @if(!empty($suggestion['reasoning']))
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $suggestion['reasoning'] }}</p>
                                @endif

                                @if(!empty($suggestion['sources']))
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($suggestion['sources'] as $source)
                                            @if(!empty($source['source_url']))
                                                <a href="{{ $source['source_url'] }}" wire:navigate
                                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                                    {{ ucfirst($source['source_type']) }}: {{ $source['source_title'] }}
                                                </a>
                                            @else
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ ucfirst($source['source_type']) }}: {{ $source['source_title'] }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <input type="text" wire:model.defer="suggestionOverrides.{{ $suggestion['id'] }}"
                                    class="mt-3 w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Optional edit before approving">

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button wire:click="approveSuggestion({{ $suggestion['id'] }})"
                                        @if(empty($suggestion['can_review'])) disabled @endif
                                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                                        Approve
                                    </button>
                                    <button wire:click="dismissSuggestion({{ $suggestion['id'] }})"
                                        @if(empty($suggestion['can_review'])) disabled @endif
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Dismiss
                                    </button>
                                    @if(!empty($suggestion['agent_id']))
                                        <a href="{{ route('intelligence.agents', ['agent' => $suggestion['agent_id']]) }}" wire:navigate
                                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Open agent
                                        </a>
                                    @endif
                                    @if(empty($suggestion['can_review']))
                                        <span class="text-[11px] text-red-600 dark:text-red-300">No permission for this risk tier</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @if(auth()->user()?->isManagement())
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Management Support Digest</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Escalated support signals only. Raw journaling stays hidden unless the staff member consented.
                        </p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ count($supportSignalDigest) }} escalated
                    </span>
                </div>

                @if(empty($supportSignalDigest))
                    <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        No escalated support signals right now.
                    </div>
                @else
                    <div class="mt-5 space-y-3">
                        @foreach($supportSignalDigest as $signal)
                            <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                        Escalated
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $signal['staff_name'] }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $signal['escalated_at'] }}</span>
                                </div>

                                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">{{ $signal['summary'] }}</p>

                                <div class="mt-2 flex flex-wrap gap-1.5 text-[11px]">
                                    @if(!empty($signal['escalation_reason']))
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                            {{ str_replace('_', ' ', $signal['escalation_reason']) }}
                                        </span>
                                    @endif
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $signal['window_signal_count'] }} in recent window
                                    </span>
                                    @if(!empty($signal['raw_context_shared']))
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            Raw context shared
                                        </span>
                                    @endif
                                </div>

                                @if(!empty($signal['raw_context']) && !empty($signal['raw_context_shared']))
                                    <div class="mt-2 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-300">
                                        {{ $signal['raw_context'] }}
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Meetings Intel (Slack)</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Ingest notes from the Meetings Intel Slack channel, then tag people, organizations, funders, grants, and meetings.
                    </p>
                </div>
                <div class="text-right">
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ count($meetingsIntelNotes) }} notes
                    </span>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        @if($meetingsIntelSlackConfigured)
                            Slack ingest configured
                            @if($meetingsIntelChannelHint !== '')
                                · {{ $meetingsIntelChannelHint }}
                            @endif
                        @else
                            Configure `SLACK_BOT_USER_OAUTH_TOKEN` + `SLACK_MEETINGS_INTEL_CHANNEL_IDS`
                        @endif
                    </div>
                </div>
            </div>

            @if(empty($meetingsIntelNotes))
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                    No Meetings Intel notes captured yet.
                </div>
            @else
                <div class="mt-5 space-y-4">
                    @foreach($meetingsIntelNotes as $note)
                        <article class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/30" wire:key="meetings-intel-note-{{ $note['id'] }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    {{ str_replace('_', ' ', $note['source']) }}
                                </span>
                                @if(!empty($note['slack_channel_id']))
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $note['slack_channel_id'] }}
                                    </span>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $note['author'] }} · {{ $note['captured_at'] }}
                                </span>
                            </div>

                            <p class="mt-2 whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">{{ $note['content'] }}</p>

                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                @if(!empty($note['meeting_label']))
                                    <a href="{{ $note['meeting_url'] }}" wire:navigate class="rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                        Meeting: {{ $note['meeting_label'] }}
                                    </a>
                                @endif
                                @if(!empty($note['project_label']))
                                    <a href="{{ $note['project_url'] }}" wire:navigate class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        Project: {{ $note['project_label'] }}
                                    </a>
                                @endif
                                @if(!empty($note['grant_label']))
                                    <a href="{{ $note['grant_url'] }}" wire:navigate class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                        Grant: {{ $note['grant_label'] }}
                                    </a>
                                @endif
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1.5 text-[11px]">
                                @foreach($note['person_tags'] as $tag)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Person: {{ $tag }}</span>
                                @endforeach
                                @foreach($note['organization_tags'] as $tag)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Org: {{ $tag }}</span>
                                @endforeach
                                @foreach($note['funder_tags'] as $tag)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Funder: {{ $tag }}</span>
                                @endforeach
                                @foreach($note['project_tags'] as $tag)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Project tag: {{ $tag }}</span>
                                @endforeach
                                @foreach($note['grant_tags'] as $tag)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Grant tag: {{ $tag }}</span>
                                @endforeach
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Link to meeting</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.meeting_id"
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">No meeting link</option>
                                        @foreach(($meetingsIntelOptions['meetings'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Primary project</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.project_id"
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">No primary project</option>
                                        @foreach(($meetingsIntelOptions['projects'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Primary grant</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.grant_id"
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">No primary grant</option>
                                        @foreach(($meetingsIntelOptions['grants'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">People tags</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.person_ids"
                                        multiple
                                        class="mt-1 h-28 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @foreach(($meetingsIntelOptions['people'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Organization tags</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.organization_ids"
                                        multiple
                                        class="mt-1 h-28 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @foreach(($meetingsIntelOptions['organizations'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Funder tags</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.funder_organization_ids"
                                        multiple
                                        class="mt-1 h-28 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @foreach(($meetingsIntelOptions['funders'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project tags</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.project_ids"
                                        multiple
                                        class="mt-1 h-28 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @foreach(($meetingsIntelOptions['projects'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Grant tags</label>
                                    <select wire:model.defer="meetingIntelTagDrafts.{{ $note['id'] }}.grant_ids"
                                        multiple
                                        class="mt-1 h-28 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @foreach(($meetingsIntelOptions['grants'] ?? []) as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button wire:click="saveMeetingIntelTags({{ $note['id'] }})"
                                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700">
                                    Save context
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Agent Council</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Cross-domain monitoring agents surfacing organization-level signals.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                        {{ $activeAgentCount }} active
                    </span>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        {{ $watchAgentCount }} watch
                    </span>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($agentCouncil as $agent)
                    @php
                        $isWatch = $agent['status'] === 'watch';
                    @endphp
                    <article class="rounded-xl border p-4 {{ $isWatch ? 'border-amber-200 bg-amber-50/40 dark:border-amber-800 dark:bg-amber-900/20' : 'border-gray-200 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-900/40' }}">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $agent['name'] }}</h3>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $isWatch ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                {{ $isWatch ? 'Watch' : 'Active' }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">{{ $agent['monitoring'] }}</p>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Open signals</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $agent['signal_count'] }}</p>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $agent['last_run_human'] }}</p>
                        </div>
                        <p class="mt-3 text-xs font-medium text-gray-700 dark:text-gray-200">{{ $agent['next_focus'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Insight Stream</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Substantive cross-domain intelligence with direct links to where action can happen.
                    </p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    {{ count($insights) }} signals
                </span>
            </div>

            <div class="mt-5 space-y-4">
                @foreach($insights as $insight)
                    @php
                        $severityClasses = match ($insight['severity']) {
                            'high' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            default => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                        };
                    @endphp
                    <article class="rounded-xl border border-gray-200 bg-gray-50/60 p-5 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $severityClasses }}">
                                {{ $insight['severity'] }}
                            </span>
                            <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                {{ $insight['agent'] }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Confidence: {{ $insight['confidence'] }}</span>
                        </div>

                        <h3 class="mt-3 text-lg font-semibold text-gray-900 dark:text-white">{{ $insight['title'] }}</h3>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $insight['summary'] }}</p>

                        @if(!empty($insight['evidence']))
                            <ul class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                @foreach($insight['evidence'] as $line)
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        <span>{{ $line }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($insight['action_url']))
                            <div class="mt-4">
                                <a href="{{ $insight['action_url'] }}" wire:navigate
                                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                    {{ $insight['action_label'] }}
                                </a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($panel === 'files')
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Box Files</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Browse synced Box files directly from Intelligence so staff and agents can reference shared context quickly.
                    </p>
                </div>
                @if(auth()->user()?->isAdmin())
                    <a href="{{ route('admin.integrations') }}" wire:navigate
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Box integration health
                    </a>
                @endif
            </div>

            @if(!$boxFilesReady)
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                    Box file metadata is not available yet. Run migrations and Box metadata sync first.
                </div>
            @else
                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,2fr)_220px_auto_auto] lg:items-end">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Search</span>
                        <input type="text" wire:model.live.debounce.300ms="boxFilesSearch"
                            placeholder="Name, path, Box ID, owner"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</span>
                        <select wire:model.live="boxFilesType"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="all">All items</option>
                            <option value="file">Files</option>
                            <option value="folder">Folders</option>
                        </select>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" wire:model.live="boxFilesMarkdownOnly"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                        Markdown only
                    </label>
                    <button wire:click="resetBoxFilesFilters"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Reset filters
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px]">
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ $boxFilesStats['items_total'] ?? 0 }} total items
                    </span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ $boxFilesStats['files_total'] ?? 0 }} files
                    </span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ $boxFilesStats['folders_total'] ?? 0 }} folders
                    </span>
                    <span class="rounded-full bg-indigo-100 px-2.5 py-1 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        {{ $boxFilesStats['markdown_total'] ?? 0 }} markdown files
                    </span>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        Last synced {{ $boxFilesLastSyncedLabel }}
                    </span>
                </div>

                @if($boxFilesAccessMessage !== '')
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        {{ $boxFilesAccessMessage }}
                    </div>
                @endif

                @if(empty($boxFiles))
                    <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        No Box items match these filters yet.
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach($boxFiles as $item)
                            <article class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $item['type_label'] }}
                                    </span>
                                    @if($item['type'] === 'file')
                                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            .{{ $item['extension'] }}
                                        </span>
                                    @endif
                                    @if(($item['project_links_count'] ?? 0) > 0)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            Linked to {{ $item['project_links_count'] }} project docs
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-2 flex flex-wrap items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <a href="{{ $item['open_url'] }}" target="_blank" rel="noopener noreferrer"
                                            class="text-sm font-semibold text-indigo-700 transition hover:underline dark:text-indigo-300">
                                            {{ $item['name'] }}
                                        </a>
                                        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $item['path'] }}</p>
                                    </div>
                                    <a href="{{ $item['open_url'] }}" target="_blank" rel="noopener noreferrer"
                                        class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Open in Box
                                    </a>
                                </div>
                                <div class="mt-3 grid gap-2 text-xs text-gray-600 dark:text-gray-300 sm:grid-cols-2 lg:grid-cols-4">
                                    <p><span class="font-medium text-gray-700 dark:text-gray-200">Owner:</span> {{ $item['owner'] }}</p>
                                    <p><span class="font-medium text-gray-700 dark:text-gray-200">Size:</span> {{ $item['size_label'] }}</p>
                                    <p><span class="font-medium text-gray-700 dark:text-gray-200">Modified:</span> {{ $item['modified_at'] }}</p>
                                    <p><span class="font-medium text-gray-700 dark:text-gray-200">Synced:</span> {{ $item['last_synced'] }}</p>
                                </div>

                                <div class="mt-4 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Link Context</p>
                                    <div class="mt-2 grid gap-2 lg:grid-cols-3">
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Project</label>
                                            <select wire:model.defer="boxFilesLinkDrafts.{{ $item['id'] }}.project_id"
                                                class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <option value="">Select project</option>
                                                @foreach($boxFilesProjectOptions as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <button wire:click="linkBoxFileToProject({{ $item['id'] }})"
                                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Link Project
                                            </button>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Meeting</label>
                                            <select wire:model.defer="boxFilesLinkDrafts.{{ $item['id'] }}.meeting_id"
                                                class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <option value="">Select meeting</option>
                                                @foreach($boxFilesMeetingOptions as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <button wire:click="linkBoxFileToMeeting({{ $item['id'] }})"
                                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Link Meeting
                                            </button>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300">Funder</label>
                                            <select wire:model.defer="boxFilesLinkDrafts.{{ $item['id'] }}.funder_id"
                                                class="w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <option value="">Select funder</option>
                                                @foreach($boxFilesFunderOptions as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <button wire:click="linkBoxFileToFunder({{ $item['id'] }})"
                                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Link Funder
                                            </button>
                                        </div>
                                    </div>

                                    @php
                                        $linkedProjects = $item['context_links']['project'] ?? [];
                                        $linkedMeetings = $item['context_links']['meeting'] ?? [];
                                        $linkedFunders = $item['context_links']['funder'] ?? [];
                                    @endphp

                                    @if(!empty($linkedProjects) || !empty($linkedMeetings) || !empty($linkedFunders))
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @foreach($linkedProjects as $link)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                    <a href="{{ $link['url'] }}" wire:navigate class="hover:underline">Project: {{ $link['label'] }}</a>
                                                    <button wire:click="unlinkBoxFileContext({{ $item['id'] }}, 'project', {{ $link['id'] }})" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-100">×</button>
                                                </span>
                                            @endforeach
                                            @foreach($linkedMeetings as $link)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    <a href="{{ $link['url'] }}" wire:navigate class="hover:underline">Meeting: {{ $link['label'] }}</a>
                                                    <button wire:click="unlinkBoxFileContext({{ $item['id'] }}, 'meeting', {{ $link['id'] }})" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-300 dark:hover:text-emerald-100">×</button>
                                                </span>
                                            @endforeach
                                            @foreach($linkedFunders as $link)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                    <a href="{{ $link['url'] }}" wire:navigate class="hover:underline">Funder: {{ $link['label'] }}</a>
                                                    <button wire:click="unlinkBoxFileContext({{ $item['id'] }}, 'funder', {{ $link['id'] }})" class="text-amber-600 hover:text-amber-800 dark:text-amber-300 dark:hover:text-amber-100">×</button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">File Organization Playbook (Human + Agent Ready)</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Since Box is mostly greenfield, this is the right moment to keep structure consistent so staff can find things quickly and agents can reason reliably.
            </p>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recommended Top-Level Structure</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Keep one canonical taxonomy used by all teams.</p>
                    <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-900 p-3 text-xs text-gray-100"><code>/00_Admin
/10_Programs
/20_Funding
/30_Partners_and_Stakeholders
/40_Meetings
/50_Communications
/60_Travel
/90_Archive</code></pre>
                    <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                        For recurring meetings, use one long-lived markdown log per series (for example: <span class="font-mono">/40_Meetings/DL_GovRel_Biweekly/notes.md</span>).
                    </p>
                </article>

                <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Operating Rules</h3>
                    <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                        <li>Prefer <span class="font-mono">.md</span> for notes, briefs, decisions, meeting logs, and status updates.</li>
                        <li>Use stable filenames: <span class="font-mono">YYYY-MM-DD_topic_owner_v01.md</span>.</li>
                        <li>Store raw source files separately from derived summaries to avoid confusion.</li>
                        <li>Keep one canonical file per subject, then link instead of duplicating content.</li>
                        <li>Add explicit owners and next review dates so stale files can be archived.</li>
                        <li>Archive completed work to <span class="font-mono">/90_Archive</span> with a short final summary.</li>
                    </ul>
                </article>
            </div>

            <article class="mt-5 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Suggested Markdown Front Matter</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This gives agents reliable metadata for search, filtering, and summaries.</p>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-900 p-3 text-xs text-gray-100"><code>---
title: Democracy's Library Gov Rel Biweekly
owner: marci@popvox.org
project: POPVOX Foundation State Capacity
organizations: [Democracy's Library]
tags: [meeting-notes, gov-rel, follow-up]
visibility: internal
last_updated: 2026-03-03
next_review: 2026-03-17
---
</code></pre>
            </article>
        </section>
    @endif

    @if($panel === 'agents')
        @if(!$migrationReady)
            <section class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                Run migrations first to access agent workspaces.
            </section>
        @else
            <section class="grid gap-6 xl:grid-cols-12">
                <div class="xl:col-span-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Agent Directory</h2>
                        <a href="{{ route('intelligence.create') }}" wire:navigate
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                            Create
                        </a>
                    </div>

                    @if(empty($agentDirectory))
                        <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            No agents found.
                        </div>
                    @else
                        <div class="mt-4 space-y-3 max-h-[720px] overflow-y-auto pr-1">
                            @foreach($agentDirectory as $agent)
                                @php
                                    $selected = (int) ($agent['id'] ?? 0) === (int) $selectedAgentId;
                                    $isPaused = ($agent['status'] ?? '') === 'paused';
                                @endphp
                                <article wire:key="agent-card-{{ $agent['id'] }}"
                                    class="rounded-xl border p-4 transition {{ $selected ? 'border-indigo-400 bg-indigo-50/70 dark:border-indigo-700 dark:bg-indigo-900/20' : 'border-gray-200 bg-gray-50/50 dark:border-gray-700 dark:bg-gray-900/30' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <button wire:click="selectAgent({{ $agent['id'] }})"
                                            class="text-left text-sm font-semibold {{ $selected ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-900 dark:text-white' }}">
                                            {{ $agent['name'] }}
                                        </button>
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isPaused ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                            {{ $isPaused ? 'Paused' : 'Active' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                        {{ ucfirst($agent['scope']) }}
                                        @if(!empty($agent['specialty']))
                                            · {{ $agent['specialty'] }}
                                        @endif
                                        @if(!empty($agent['project_name']))
                                            · {{ $agent['project_name'] }}
                                        @endif
                                    </p>

                                    <div class="mt-3 flex items-center justify-between text-[11px] text-gray-600 dark:text-gray-300">
                                        <span>Pending: {{ $agent['pending_suggestions_count'] ?? 0 }}</span>
                                        <button wire:click="toggleAgentStatus({{ $agent['id'] }})"
                                            class="rounded-lg border px-2 py-0.5 {{ $isPaused ? 'border-emerald-300 text-emerald-700 dark:border-emerald-700 dark:text-emerald-300' : 'border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300' }}">
                                            {{ $isPaused ? 'Resume' : 'Pause' }}
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="xl:col-span-8 space-y-6">
                    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        x-data
                        x-init="$nextTick(() => { if ($refs.thread) $refs.thread.scrollTop = $refs.thread.scrollHeight; })"
                        x-on:workspace-thread-updated.window="$nextTick(() => { if ($refs.thread) $refs.thread.scrollTop = $refs.thread.scrollHeight; })">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Direct Agent</h2>
                            @if($selectedAgent)
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $selectedAgent->name }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $selectedThreadVisibility === 'public' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                        {{ strtoupper($selectedThreadVisibility) }}
                                    </span>
                                    <button wire:click="toggleSelectedThreadVisibility"
                                        class="rounded-lg border border-gray-300 px-2.5 py-1 text-[11px] font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                        Toggle
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if(!$selectedAgent)
                            <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                Select an agent first.
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40 overflow-hidden">
                                <div x-ref="thread" class="h-[320px] overflow-y-auto p-4 space-y-3">
                                    @forelse($agentMessages as $message)
                                        @php
                                            $isUser = ($message['role'] ?? 'assistant') === 'user';
                                        @endphp
                                        <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                                            <div class="max-w-[95%] rounded-xl px-3 py-2 {{ $isUser ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600' }}">
                                                <div class="text-[11px] mb-1 {{ $isUser ? 'text-indigo-100' : 'text-gray-500 dark:text-gray-400' }}">
                                                    {{ $isUser ? 'You' : 'WRK Agent' }} • {{ strtoupper($message['visibility'] ?? $selectedThreadVisibility) }} • {{ $message['timestamp'] ?? '' }}
                                                </div>
                                                <div class="whitespace-pre-wrap text-sm">{{ $message['content'] ?? '' }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="h-full flex items-center justify-center text-center text-sm text-gray-500 dark:text-gray-400">
                                            No thread yet. Direct this agent with goals, notes, and requested follow-ups.
                                        </div>
                                    @endforelse
                                </div>

                                <form wire:submit.prevent="directSelectedAgent" class="border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-start gap-3">
                                        <textarea wire:model="directiveInput" rows="3"
                                            class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="Tell the agent what changed, what to draft, and what to propose."></textarea>
                                        <button type="submit"
                                            class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
                                            Enter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Agent Queue</h2>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($pendingSuggestions) }} pending</span>
                        </div>

                        @if(empty($pendingSuggestions))
                            <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                No pending suggestions for selected agent.
                            </div>
                        @else
                            <div class="mt-4 space-y-3">
                                @foreach($pendingSuggestions as $suggestion)
                                    @php
                                        $riskClass = match($suggestion['risk_level']) {
                                            'high' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                            'low' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                            default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        };
                                    @endphp
                                    <article wire:key="agent-pending-{{ $suggestion['id'] }}" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                {{ str_replace('_', ' ', $suggestion['suggestion_type']) }}
                                            </span>
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $riskClass }}">
                                                {{ strtoupper($suggestion['risk_level']) }}
                                            </span>
                                        </div>

                                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $suggestion['title'] }}</h3>
                                        @if(!empty($suggestion['reasoning']))
                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $suggestion['reasoning'] }}</p>
                                        @endif

                                        <input type="text" wire:model.defer="suggestionOverrides.{{ $suggestion['id'] }}"
                                            class="mt-3 w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="Optional edit before approving">

                                        <div class="mt-3 flex items-center gap-2">
                                            <button wire:click="approveSuggestion({{ $suggestion['id'] }})"
                                                @if(empty($suggestion['can_review'])) disabled @endif
                                                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                                                Approve
                                            </button>
                                            <button wire:click="dismissSuggestion({{ $suggestion['id'] }})"
                                                @if(empty($suggestion['can_review'])) disabled @endif
                                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Dismiss
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Memory Audit</h2>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($memoryAudit) }} entries</span>
                        </div>

                        @if(empty($memoryAudit))
                            <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                No memory entries available for this scope.
                            </div>
                        @else
                            <div class="mt-4 space-y-2.5">
                                @foreach($memoryAudit as $memory)
                                    <article class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                {{ $memory['memory_type'] }}
                                            </span>
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $memory['visibility'] === 'public' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                                {{ strtoupper($memory['visibility']) }}
                                            </span>
                                            @if($memory['is_cross_agent'])
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                    {{ $memory['agent_name'] }}
                                                </span>
                                            @endif
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                @if($memory['confidence'] !== null)
                                                    {{ number_format((float) $memory['confidence'], 2) }} confidence •
                                                @endif
                                                {{ $memory['created_at'] }}
                                            </span>
                                        </div>
                                        <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $memory['text'] }}</p>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            </section>
        @endif
    @endif

    @if($panel === 'create')
        @if(!$migrationReady)
            <section class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                Run migrations first to create agents.
            </section>
        @else
            <section class="max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Create Agent</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Use this guided form, then direct the agent from the Agents page.</p>

                <form wire:submit.prevent="createAgent" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Agent name</label>
                        <input type="text" wire:model.defer="createForm.name"
                            class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="Ex: Digital Parliaments Copilot">
                        @error('createForm.name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Scope</label>
                            <select wire:model="createForm.scope"
                                class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="specialist">Specialist</option>
                                <option value="project">Project</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Specialty</label>
                            <input type="text" wire:model.defer="createForm.specialty"
                                class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="policy, grants, communications">
                        </div>
                    </div>

                    @if(($createForm['scope'] ?? 'specialist') === 'project')
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Project</label>
                            <select wire:model.defer="createForm.project_id"
                                class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Select project</option>
                                @foreach($projectOptions as $project)
                                    <option value="{{ $project['id'] }}">{{ $project['name'] }} ({{ $project['status'] }})</option>
                                @endforeach
                            </select>
                            @error('createForm.project_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Template</label>
                        <select wire:model.defer="createForm.template_id"
                            class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">None</option>
                            @foreach($templateOptions as $template)
                                @if($template['agent_type'] === ($createForm['scope'] ?? 'specialist') || $template['agent_type'] === 'specialist')
                                    <option value="{{ $template['id'] }}">{{ $template['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Mission</label>
                        <textarea wire:model.defer="createForm.mission" rows="2"
                            class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="What outcomes this agent owns"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Instructions</label>
                        <textarea wire:model.defer="createForm.instructions" rows="4"
                            class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            placeholder="How the agent should behave"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Autonomy mode</label>
                            <select wire:model.defer="createForm.autonomy_mode"
                                class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="tiered">Tiered</option>
                                <option value="propose_only">Propose only</option>
                            </select>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 sm:pt-6">
                            Tiered mode can execute low-risk actions automatically when allowed by governance settings.
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Low</label>
                            <select wire:model.defer="createForm.governance_low"
                                class="mt-1 w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="autonomous">Autonomous</option>
                                <option value="team_approval">Team approval</option>
                                <option value="management_approval">Mgmt approval</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Medium</label>
                            <select wire:model.defer="createForm.governance_medium"
                                class="mt-1 w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="team_approval">Team approval</option>
                                <option value="autonomous">Autonomous</option>
                                <option value="management_approval">Mgmt approval</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">High</label>
                            <select wire:model.defer="createForm.governance_high"
                                class="mt-1 w-full rounded-lg border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="management_approval">Mgmt approval</option>
                                <option value="team_approval">Team approval</option>
                                <option value="autonomous">Autonomous</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <a href="{{ route('intelligence.agents') }}" wire:navigate
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Cancel
                        </a>
                        <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Create Agent
                        </button>
                    </div>
                </form>
            </section>
        @endif
    @endif

    @if($panel === 'audit')
        @if(!$migrationReady)
            <section class="rounded-2xl border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                Run migrations first to view audit trails.
            </section>
        @else
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Audit & Traceability</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Reasoning chains, alternatives, and sources for each run.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="selectedAgentId"
                            class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="0">All accessible agents</option>
                            @foreach($agentDirectory as $agent)
                                <option value="{{ $agent['id'] }}">{{ $agent['name'] }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('intelligence.create') }}" wire:navigate
                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700">
                            Create Agent
                        </a>
                    </div>
                </div>

                @if(empty($recentRuns))
                    <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        No runs found for this filter.
                    </div>
                @else
                    <div class="mt-5 space-y-4">
                        @foreach($recentRuns as $run)
                            <article class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $run['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : ($run['status'] === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300') }}">
                                        {{ strtoupper($run['status']) }}
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $run['agent_name'] }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Run #{{ $run['id'] }} • {{ $run['created_at'] }}</span>
                                </div>

                                @if(!empty($run['directive']))
                                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $run['directive'] }}</p>
                                @endif
                                @if(!empty($run['result_summary']))
                                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $run['result_summary'] }}</p>
                                @endif
                                @if(!empty($run['error_message']))
                                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $run['error_message'] }}</p>
                                @endif

                                @if(!empty($run['reasoning_chain']))
                                    <div class="mt-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reasoning chain</p>
                                        <ul class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                            @foreach($run['reasoning_chain'] as $item)
                                                <li>• {{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(!empty($run['alternatives_considered']))
                                    <div class="mt-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Alternatives considered</p>
                                        <ul class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                            @foreach($run['alternatives_considered'] as $item)
                                                <li>• {{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(!empty($run['suggestions']))
                                    <div class="mt-3 space-y-2">
                                        @foreach($run['suggestions'] as $suggestion)
                                            <div class="rounded-lg border border-gray-200 bg-white p-3 text-sm dark:border-gray-700 dark:bg-gray-800">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                        {{ str_replace('_', ' ', $suggestion['type']) }}
                                                    </span>
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ $suggestion['status'] }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $suggestion['title'] }}</p>

                                                @if(!empty($suggestion['sources']))
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        @foreach($suggestion['sources'] as $source)
                                                            @if(!empty($source['url']))
                                                                <a href="{{ $source['url'] }}" wire:navigate
                                                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                                                    {{ ucfirst($source['type']) }}: {{ $source['title'] }}
                                                                </a>
                                                            @else
                                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                                    {{ ucfirst($source['type']) }}: {{ $source['title'] }}
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    @endif
</div>
