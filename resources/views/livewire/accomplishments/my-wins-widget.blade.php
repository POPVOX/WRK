<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="text-lg">🎉</span>
            My Recent Wins
        </h2>
        <a href="{{ route('accomplishments.index') }}" wire:navigate
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
            View All →
        </a>
    </div>

    @if($hasError)
        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
            <p class="text-sm">Unable to load stats</p>
        </div>
    @else

    {{-- Quick Stats --}}
    @if($weekStats)
        <div class="grid grid-cols-4 gap-3 mb-4">
            <div class="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $weekStats->meetings_attended }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Meetings</p>
            </div>
            <div class="text-center p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $weekStats->documents_authored }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Documents</p>
            </div>
            <div class="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $weekStats->projects_owned + $weekStats->projects_contributed }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Projects</p>
            </div>
            <div class="text-center p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $recognitionCount }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Recognition</p>
            </div>
        </div>

        {{-- Impact Score --}}
        <div class="mb-4 p-3 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">This Week's Impact</span>
                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($weekStats->total_impact_score, 1) }}</span>
            </div>
            <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                @php
                    $progressPercent = min(100, ($weekStats->total_impact_score / 50) * 100);
                @endphp
                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all"
                    style="width: {{ $progressPercent }}%"></div>
            </div>
        </div>
    @endif

    {{-- Recent Highlights --}}
    @if(count($recentWins) > 0)
        <div class="space-y-2">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Recent Highlights</p>
            @foreach($recentWins as $win)
                <a href="{{ route('accomplishments.index') }}" wire:navigate
                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <span class="text-lg">{{ $win['type_emoji'] }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-white truncate">{{ $win['title'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $win['date'] }}</p>
                    </div>
                    @if($win['is_recognition'])
                        <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 rounded-full">
                            Recognition
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-4">
            <p class="text-gray-500 dark:text-gray-400 text-sm">No recent wins recorded</p>
            <a href="{{ route('accomplishments.index') }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 mt-1 inline-block">
                Add an accomplishment →
            </a>
        </div>
    @endif

    {{-- Add Button --}}
    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
        <a href="{{ route('accomplishments.index') }}?add=true" wire:navigate
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Accomplishment
        </a>
    </div>
    @endif
</div>

