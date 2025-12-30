<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reliability & Observability</h1>
            <p class="text-gray-500 dark:text-gray-400">AI health, queues, calendar sync, and document ingestion.</p>
        </div>
        <a href="{{ route('dashboard') }}" wire:navigate
           class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">← Back to Dashboard</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">AI Requests</h2>
                @if($ai['last_error_at'])
                    <span class="text-xs text-amber-600 dark:text-amber-400">Last error {{ \Carbon\Carbon::parse($ai['last_error_at'])->diffForHumans() }}</span>
                @endif
            </div>
            <div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                <div class="flex justify-between"><span>Success</span><span>{{ $ai['success'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Error</span><span class="text-red-600 dark:text-red-400">{{ $ai['error'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Avg latency (ms)</span><span>{{ $ai['avg_latency_ms'] ?? '—' }}</span></div>
                @if($ai['last_error_status'])
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Last error status: {{ $ai['last_error_status'] }}</div>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Queues</h2>
            </div>
            <div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                <div class="flex justify-between"><span>Queued jobs</span><span>{{ $queues['depth'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Failed jobs</span><span class="text-red-600 dark:text-red-400">{{ $queues['failed'] ?? '—' }}</span></div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Calendar Sync</h2>
            </div>
            <div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                <div class="flex justify-between"><span>Connected users</span><span>{{ $calendar['connected'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Stale (>7d) syncs</span><span class="{{ ($calendar['stale'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">{{ $calendar['stale'] ?? '—' }}</span></div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Document Ingestion</h2>
            </div>
            <div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                <div class="flex justify-between">
                    <span>Failed ingestion jobs</span>
                    <span class="{{ ($documents['failed_ingestion'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">
                        {{ $documents['failed_ingestion'] ?? '—' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

