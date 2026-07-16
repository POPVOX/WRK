<div class="desk-page">
    <x-desk-page-header eyebrow="Admin" title="Reliability" description="AI health, queues, calendar sync, document ingestion, and the signals needed to catch stuck work." />

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
                <div class="flex justify-between"><span>Active syncs</span><span>{{ $calendar['active'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Stale (&gt;30m) or never</span><span class="{{ ($calendar['stale'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : '' }}">{{ $calendar['stale'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Failed users</span><span class="{{ ($calendar['failed'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ $calendar['failed'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Never completed</span><span>{{ $calendar['never_synced'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Queued calendar jobs</span><span>{{ $calendar['queued_jobs'] ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Failed calendar jobs</span><span class="{{ ($calendar['failed_jobs'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ $calendar['failed_jobs'] ?? '—' }}</span></div>
                @if($calendar['latest_success'])
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Latest success {{ \Carbon\Carbon::parse($calendar['latest_success'])->diffForHumans() }}</div>
                @endif
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

    @if(!empty($calendar['problem_users']))
        <div class="mt-6 overflow-hidden rounded-lg border border-amber-200 bg-white shadow-sm dark:border-amber-800 dark:bg-gray-800">
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-200">Calendar syncs needing attention</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($calendar['problem_users'] as $syncUser)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $syncUser['name'] ?: 'User #'.$syncUser['id'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Last success: {{ $syncUser['calendar_import_date'] ? \Carbon\Carbon::parse($syncUser['calendar_import_date'])->diffForHumans() : 'never' }}
                            </p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold uppercase text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                            {{ $syncUser['calendar_sync_status'] ?: 'unknown' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
