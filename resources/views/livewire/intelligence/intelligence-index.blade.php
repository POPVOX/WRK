<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Intelligence</h1>
                <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
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

        <nav class="flex flex-wrap items-center gap-2">
            <a href="{{ route('intelligence.index') }}" wire:navigate
                class="rounded-lg px-3 py-2 text-sm font-medium {{ $panel === 'home' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                Home
            </a>
            <a href="{{ route('intelligence.agents') }}" wire:navigate
                class="rounded-lg px-3 py-2 text-sm font-medium {{ $panel === 'agents' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                Agents
            </a>
            <a href="{{ route('intelligence.create') }}" wire:navigate
                class="rounded-lg px-3 py-2 text-sm font-medium {{ $panel === 'create' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                Create Agent
            </a>
            <a href="{{ route('intelligence.audit') }}" wire:navigate
                class="rounded-lg px-3 py-2 text-sm font-medium {{ $panel === 'audit' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                Audit
            </a>
        </nav>
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
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Direct Agent</h2>
                            @if($selectedAgent)
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $selectedAgent->name }}</span>
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
                                                    {{ $isUser ? 'You' : 'WRK Agent' }} • {{ $message['timestamp'] ?? '' }}
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
