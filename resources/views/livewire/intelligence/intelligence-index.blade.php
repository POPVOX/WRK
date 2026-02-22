<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Intelligence</h1>
            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Monitor agent activity across WRK and review cross-domain signals before they become blockers.
                Insights are tied to live operational data and suggest where to act next.
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

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Agent Council</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Specialized agents monitor key operating domains and surface intelligence to the organization.
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
            @foreach($agents as $agent)
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
</div>
