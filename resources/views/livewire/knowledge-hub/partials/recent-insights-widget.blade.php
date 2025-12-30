<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            Recent Insights
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Activity patterns from the last 30 days</p>
    </div>

    <div class="p-5">
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Topics This Month --}}
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Topics Discussed</h3>
                @if($recentInsights['topics']->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No topics tagged yet</p>
                @else
                    <div class="space-y-2">
                        @php $maxCount = $recentInsights['topics']->max('meetings_count') ?: 1; @endphp
                        @foreach($recentInsights['topics'] as $topic)
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="text-gray-700 dark:text-gray-300">{{ $topic->name }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">{{ $topic->meetings_count }}</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full"
                                            style="width: {{ ($topic->meetings_count / $maxCount) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Top Organizations --}}
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Organizations</h3>
                @if($recentInsights['organizations']->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No meeting activity yet</p>
                @else
                    <div class="space-y-2">
                        @foreach($recentInsights['organizations'] as $org)
                            <a href="{{ route('organizations.show', $org) }}"
                                class="flex items-center justify-between py-1 text-sm hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                <span class="text-gray-700 dark:text-gray-300">{{ Str::limit($org->name, 25) }}</span>
                                <span class="text-gray-400 dark:text-gray-500">{{ $org->meetings_count }} meetings</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Decisions --}}
        @if($recentInsights['decisions']->isNotEmpty())
            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recent Decisions</h3>
                <div class="space-y-3">
                    @foreach($recentInsights['decisions'] as $decision)
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 text-indigo-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" />
                            </svg>
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($decision->decision, 80) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $decision->decided_at->format('M j') }}
                                    @if($decision->project)
                                        • {{ $decision->project->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Summary Stat --}}
        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 text-center">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $recentInsights['total_meetings_this_month'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">meetings in the last 30 days</div>
        </div>
    </div>
</div>