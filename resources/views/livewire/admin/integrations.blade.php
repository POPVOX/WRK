<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Integrations</h1>
            <p class="text-gray-500 dark:text-gray-400">Connection health for Box and sync pipelines.</p>
        </div>
        <div class="inline-flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <span>Last refreshed {{ $generatedAt }}</span>
            <button wire:click="refreshHealth"
                class="rounded-lg bg-gray-900 px-3 py-1.5 font-medium text-white transition hover:bg-black dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                Refresh
            </button>
        </div>
    </div>

    <section class="rounded-2xl border p-5 shadow-sm {{ ($box['auth_healthy'] ?? false) ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex h-2.5 w-2.5 rounded-full {{ ($box['auth_healthy'] ?? false) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
            <h2 class="text-sm font-semibold {{ ($box['auth_healthy'] ?? false) ? 'text-emerald-900 dark:text-emerald-100' : 'text-amber-900 dark:text-amber-100' }}">
                {{ ($box['auth_healthy'] ?? false) ? 'Box connectivity healthy' : 'Box needs attention' }}
            </h2>
        </div>
        @if(!empty($box['auth_error']))
            <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">{{ $box['auth_error'] }}</p>
        @endif
        @if(!($box['auth_configured'] ?? false))
            <p class="mt-2 text-xs text-amber-800 dark:text-amber-200">
                Set Box auth in env: <span class="font-mono">BOX_CLIENT_ID</span>, <span class="font-mono">BOX_CLIENT_SECRET</span>, <span class="font-mono">BOX_SUBJECT_TYPE</span>, and subject ID.
            </p>
        @endif
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-1">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Box Auth & Folder Access</h2>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-3">
                    <span class="text-gray-600 dark:text-gray-300">Auth configured</span>
                    <span class="font-medium {{ ($box['auth_configured'] ?? false) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                        {{ ($box['auth_configured'] ?? false) ? 'Yes' : 'No' }}
                    </span>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Root Folder</p>
                        <span class="text-xs font-medium {{ ($box['root_folder']['reachable'] ?? false) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                            {{ ($box['root_folder']['reachable'] ?? false) ? 'Reachable' : 'Not reachable' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ID: {{ $box['root_folder']['id'] ?: '—' }}</p>
                    @if(!empty($box['root_folder']['name']))
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $box['root_folder']['name'] }}</p>
                    @endif
                    @if(!empty($box['root_folder']['error']))
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ $box['root_folder']['error'] }}</p>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Projects Folder</p>
                        <span class="text-xs font-medium {{ ($box['projects_folder']['reachable'] ?? false) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                            {{ ($box['projects_folder']['reachable'] ?? false) ? 'Reachable' : 'Not reachable' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ID: {{ $box['projects_folder']['id'] ?: '—' }}</p>
                    @if(!empty($box['projects_folder']['name']))
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $box['projects_folder']['name'] }}</p>
                    @endif
                    @if(!empty($box['projects_folder']['error']))
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ $box['projects_folder']['error'] }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-1">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Metadata Sync</h2>
            @if(!($box['sync']['tables_ready'] ?? false))
                <p class="mt-3 text-sm text-amber-700 dark:text-amber-300">Box tables are unavailable. Run migrations.</p>
            @else
                <div class="mt-4 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <div class="flex justify-between"><span>Total items</span><span>{{ $box['sync']['total_items'] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span>Files</span><span>{{ $box['sync']['file_items'] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span>Folders</span><span>{{ $box['sync']['folder_items'] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span>Trashed</span><span>{{ $box['sync']['trashed_items'] ?? '—' }}</span></div>
                    <div class="pt-2 text-xs text-gray-500 dark:text-gray-400">
                        Last sync:
                        @if(!empty($box['sync']['last_synced_at']))
                            {{ $box['sync']['last_synced_at'] }} ({{ $box['sync']['last_synced_human'] }})
                        @else
                            —
                        @endif
                    </div>
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-1">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Webhook Pipeline</h2>
            @if(!($box['webhook']['table_ready'] ?? false))
                <p class="mt-3 text-sm text-amber-700 dark:text-amber-300">Webhook table is unavailable. Run migrations.</p>
            @else
                <div class="mt-4 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <div class="flex justify-between"><span>Events stored</span><span>{{ $box['webhook']['event_count'] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span>Last status</span><span>{{ $box['webhook']['last_status'] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span>Last trigger</span><span>{{ $box['webhook']['last_trigger'] ?? '—' }}</span></div>
                    <div class="flex justify-between"><span>Delivery ID</span><span class="truncate max-w-[160px] text-right">{{ $box['webhook']['last_delivery_id'] ?? '—' }}</span></div>
                    <div class="pt-2 text-xs text-gray-500 dark:text-gray-400">
                        Last event:
                        @if(!empty($box['webhook']['last_event_at']))
                            {{ $box['webhook']['last_event_at'] }} ({{ $box['webhook']['last_event_human'] }})
                        @else
                            —
                        @endif
                    </div>
                    @if(!empty($box['webhook']['last_processed_at']))
                        <div class="text-xs text-gray-500 dark:text-gray-400">Processed at: {{ $box['webhook']['last_processed_at'] }}</div>
                    @endif
                    @if(!empty($box['webhook']['last_error_message']))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-2 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                            {{ $box['webhook']['last_error_message'] }}
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>

