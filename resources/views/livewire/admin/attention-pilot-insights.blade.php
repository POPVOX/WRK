<div class="space-y-6">
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Attention Pilot Insights</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">How well “Needs You” is identifying work that deserves staff attention.</p>
        </div>
    </x-slot>

    <div class="app-page-frame space-y-6">
        <div class="flex flex-wrap gap-2" aria-label="Insight period">
            @foreach(['7' => 'Last 7 days', '30' => 'Last 30 days', 'all' => 'All time'] as $value => $label)
                <button type="button" wire:click="setPeriod('{{ $value }}')"
                    class="rounded-full border px-3 py-1.5 text-sm font-medium transition {{ $period === $value ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Usefulness</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $usefulRate }}%</p>
            </div>
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ratings</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $totalRatings }}</p>
            </div>
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Useful</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $usefulCount }}</p>
            </div>
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Not relevant</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $notRelevantCount }}</p>
            </div>
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Participants</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $participantCount }}</p>
            </div>
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Missing signals</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $missingCount }}</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="app-surface p-5 sm:p-6">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Signal quality by category</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Usefulness is calculated from explicit staff ratings.</p>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse($categoryStats as $stat)
                        <div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-medium capitalize text-gray-800 dark:text-gray-200">{{ str_replace('_', ' ', $stat['category']) }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $stat['useful_rate'] }}% useful · {{ $stat['total'] }} ratings</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $stat['useful_rate'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No item ratings in this period yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="app-surface p-5 sm:p-6">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Missing signals</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">What staff expected the queue to surface but did not find.</p>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse($missingSignals as $signal)
                        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $signal->note }}</p>
                            <p class="mt-2 text-xs text-gray-400">{{ $signal->user?->name ?: $signal->user?->email ?: 'Former team member' }} · {{ $signal->created_at->diffForHumans() }}</p>
                        </article>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">No missing signals reported in this period.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="app-surface p-5 sm:p-6">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Signal quality by rule</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use this view to tune the exact conditions that generate attention items.</p>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse($ruleStats as $stat)
                    <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $stat['label'] }}</h4>
                                <p class="mt-1 font-mono text-[11px] text-gray-400">{{ $stat['rule_key'] }}</p>
                            </div>
                            <span class="text-lg font-semibold {{ $stat['useful_rate'] >= 70 ? 'text-emerald-600' : ($stat['useful_rate'] >= 40 ? 'text-amber-600' : 'text-red-600') }}">{{ $stat['useful_rate'] }}%</span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $stat['useful'] }} useful · {{ $stat['not_relevant'] }} not relevant</p>
                    </article>
                @empty
                    <p class="py-8 text-sm text-gray-500 dark:text-gray-400">No rule-level ratings in this period yet.</p>
                @endforelse
            </div>
        </section>

        <section class="app-surface overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent ratings</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-800">
                        <tr>
                            <th class="px-5 py-3">Team member</th>
                            <th class="px-5 py-3">Signal</th>
                            <th class="px-5 py-3">Rule</th>
                            <th class="px-5 py-3">Category</th>
                            <th class="px-5 py-3">Rating</th>
                            <th class="px-5 py-3">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($recentRatings as $rating)
                            <tr>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-200">{{ $rating->user?->name ?: $rating->user?->email ?: 'Former team member' }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $rating->item_key }}</td>
                                <td class="px-5 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $ruleLabels[$rating->rule_key] ?? ($rating->rule_key ? str($rating->rule_key)->replace('_', ' ')->title() : 'Legacy signal') }}</td>
                                <td class="px-5 py-3 capitalize text-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', $rating->category ?: 'uncategorized') }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $rating->response === 'useful' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                        {{ $rating->response === 'useful' ? 'Useful' : 'Not relevant' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-400">{{ $rating->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-gray-500 dark:text-gray-400">No ratings in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
