<div class="app-page-frame space-y-4" wire:poll.60s="$refresh">
    <div class="app-page-head">
        <div>
            <h1 class="app-page-title">Notifications</h1>
            <p class="app-page-lead">Team updates and reminders, organized to stay informative without noise.</p>
        </div>
        @if($unreadCount > 0)
            <button
                type="button"
                wire:click="markAllAsRead"
                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Mark all read
            </button>
        @endif
    </div>

    <div class="app-card p-3">
        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-sm">
            <button
                type="button"
                wire:click="setScope('all')"
                class="rounded-md px-3 py-1.5 font-medium {{ $scope === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600' }}"
            >
                All
            </button>
            <button
                type="button"
                wire:click="setScope('unread')"
                class="rounded-md px-3 py-1.5 font-medium {{ $scope === 'unread' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600' }}"
            >
                Unread
                @if($unreadCount > 0)
                    <span class="ml-1 rounded-full bg-indigo-600 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    <div class="space-y-2">
        @forelse($items as $item)
            @php
                $levelStyles = match($item['level']) {
                    'urgent' => 'border-red-200 bg-red-50',
                    'warning' => 'border-amber-200 bg-amber-50',
                    'success' => 'border-emerald-200 bg-emerald-50',
                    default => 'border-gray-200 bg-white',
                };
            @endphp
            <article class="app-card px-4 py-3 {{ $levelStyles }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p>
                        @if($item['body'] !== '')
                            <p class="mt-1 text-sm text-gray-700">{{ $item['body'] }}</p>
                        @endif
                        <p class="mt-1 text-xs text-gray-500">{{ $item['time_label'] }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($item['action_url'])
                            <a
                                href="{{ $item['action_url'] }}"
                                wire:navigate
                                wire:click="markAsRead('{{ $item['id'] }}')"
                                class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                            >
                                {{ $item['action_label'] ?: 'Open' }}
                            </a>
                        @endif
                        <button
                            type="button"
                            wire:click="{{ $item['is_read'] ? 'markAsUnread' : 'markAsRead' }}('{{ $item['id'] }}')"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                        >
                            {{ $item['is_read'] ? 'Mark unread' : 'Mark read' }}
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="app-card px-4 py-6 text-center">
                <p class="text-sm font-semibold text-gray-800">No {{ $scope }} notifications.</p>
                <p class="mt-1 text-xs text-gray-500">You are caught up.</p>
            </div>
        @endforelse
    </div>

    @if($scope === 'all' && $items->count() >= $limit)
        <div class="flex justify-center">
            <button
                type="button"
                wire:click="loadMore"
                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Load more
            </button>
        </div>
    @endif
</div>
