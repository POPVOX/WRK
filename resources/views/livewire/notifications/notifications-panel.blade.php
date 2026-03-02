<div class="fixed right-3 top-14 lg:top-3 z-50" wire:poll.30s="$refresh">
    @if($open)
        <button
            type="button"
            wire:click="closePanel"
            class="fixed inset-0 z-40 bg-gray-900/20"
            aria-label="Close notifications"
        ></button>
    @endif

    <div class="relative z-50">
        <button
            type="button"
            wire:click="togglePanel"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
            aria-label="Open notifications"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9" />
            </svg>
            <span class="hidden sm:inline">Notifications</span>
            @if($unreadCount > 0)
                <span class="inline-flex min-w-[1.3rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        @if($open)
            <section class="absolute right-0 mt-2 w-[22rem] sm:w-[25rem] max-h-[70vh] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
                <header class="border-b border-gray-100 px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                            <p class="mt-0.5 text-xs text-gray-500">Important updates, tuned to stay calm and useful.</p>
                        </div>
                        <button
                            type="button"
                            wire:click="closePanel"
                            class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50"
                        >
                            Close
                        </button>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-2">
                        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-xs">
                            <button
                                type="button"
                                wire:click="setScope('unread')"
                                class="rounded-md px-2 py-1 font-medium {{ $scope === 'unread' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600' }}"
                            >
                                Unread
                            </button>
                            <button
                                type="button"
                                wire:click="setScope('all')"
                                class="rounded-md px-2 py-1 font-medium {{ $scope === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600' }}"
                            >
                                All
                            </button>
                        </div>

                        @if($unreadCount > 0)
                            <button
                                type="button"
                                wire:click="markAllAsRead"
                                class="text-xs font-medium text-indigo-700 hover:text-indigo-800"
                            >
                                Mark all read
                            </button>
                        @endif
                    </div>
                </header>

                <div class="max-h-[52vh] overflow-y-auto p-3 space-y-2">
                    @forelse($items as $item)
                        @php
                            $levelStyles = match($item['level']) {
                                'urgent' => 'border-red-200 bg-red-50',
                                'warning' => 'border-amber-200 bg-amber-50',
                                'success' => 'border-emerald-200 bg-emerald-50',
                                default => 'border-gray-200 bg-gray-50',
                            };
                        @endphp
                        <article class="rounded-xl border px-3 py-2 {{ $item['is_read'] ? 'border-gray-200 bg-white' : $levelStyles }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p>
                                    @if($item['body'] !== '')
                                        <p class="mt-0.5 text-xs leading-relaxed text-gray-700">{{ $item['body'] }}</p>
                                    @endif
                                    <p class="mt-1 text-[11px] text-gray-500">{{ $item['time_label'] }}</p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="{{ $item['is_read'] ? 'markAsUnread' : 'markAsRead' }}('{{ $item['id'] }}')"
                                    class="shrink-0 rounded-md border border-gray-300 px-1.5 py-0.5 text-[11px] font-medium text-gray-600 hover:bg-gray-100"
                                >
                                    {{ $item['is_read'] ? 'Unread' : 'Read' }}
                                </button>
                            </div>
                            @if($item['action_url'])
                                <div class="mt-2">
                                    <a
                                        href="{{ $item['action_url'] }}"
                                        wire:navigate
                                        wire:click="markAsRead('{{ $item['id'] }}')"
                                        class="inline-flex items-center rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100"
                                    >
                                        {{ $item['action_label'] ?: 'Open' }}
                                    </a>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-5 text-center">
                            <p class="text-sm font-medium text-gray-700">No {{ $scope }} notifications right now.</p>
                            <p class="mt-1 text-xs text-gray-500">You are all caught up.</p>
                        </div>
                    @endforelse
                </div>

                @if(auth()->user()?->isManagement() && Route::has('notifications.admin'))
                    <footer class="border-t border-gray-100 px-4 py-3">
                        <a href="{{ route('notifications.admin') }}" wire:navigate class="text-xs font-medium text-indigo-700 hover:text-indigo-800">
                            Manage notifications
                        </a>
                    </footer>
                @endif
            </section>
        @endif
    </div>
</div>

