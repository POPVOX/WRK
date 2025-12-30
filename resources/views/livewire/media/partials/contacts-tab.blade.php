{{-- Press Contacts Tab --}}
<div>
    {{-- Filters --}}
    <div class="flex items-center gap-3 mb-6">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search journalists..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </div>
    </div>

    {{-- Journalists Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($journalists as $journalist)
            <a href="{{ route('people.show', $journalist) }}" wire:navigate
                class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-sm transition">
                <div class="flex items-start gap-3">
                    {{-- Avatar --}}
                    <div
                        class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ strtoupper(substr($journalist->name, 0, 1)) }}
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $journalist->name }}</h4>
                        <p class="text-sm text-purple-600 dark:text-purple-400 truncate">
                            {{ $journalist->organization?->name ?? 'Independent' }}
                        </p>
                        @if($journalist->email)
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $journalist->email }}</p>
                        @endif

                        {{-- Labeled Stats --}}
                        <div class="flex items-center gap-3 mt-2 text-xs">
                            <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400" title="Press clips">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                                {{ $journalist->press_clips_count }}
                                {{ Str::plural('clip', $journalist->press_clips_count) }}
                            </span>
                            <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400" title="Pitches sent">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                {{ $journalist->pitches_received_count ?? 0 }}
                                {{ Str::plural('pitch', $journalist->pitches_received_count ?? 0) }}
                            </span>
                            <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400" title="Inquiries">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                {{ $journalist->inquiries_made_count ?? 0 }}
                                {{ Str::plural('inquiry', $journalist->inquiries_made_count ?? 0) }}
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-lg font-medium">No press contacts</p>
                <p class="text-sm mt-1">Mark people as journalists in the People directory to see them here.</p>
            </div>
        @endforelse
    </div>
</div>