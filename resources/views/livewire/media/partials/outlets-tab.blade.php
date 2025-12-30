{{-- Outlets Tab --}}
<div>
    {{-- Search --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex-1 max-w-md">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search outlets..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </div>
    </div>

    {{-- Outlets Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($mediaOutlets as $outlet)
            <a href="{{ route('organizations.show', $outlet) }}" wire:navigate
                class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-sm transition">
                <div class="flex items-start gap-3">
                    {{-- Logo/Icon --}}
                    @if($outlet->logo_url)
                        <img src="{{ $outlet->logo_url }}" alt="{{ $outlet->name }}"
                            class="w-12 h-12 rounded-lg object-contain bg-gray-100 dark:bg-gray-700 p-1 flex-shrink-0">
                    @else
                        <div
                            class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                        </div>
                    @endif

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $outlet->name }}</h4>
                            <span
                                class="px-1.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 rounded flex-shrink-0">
                                media
                            </span>
                        </div>

                        {{-- Labeled Stats --}}
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                                {{ $outlet->press_clips_count ?? 0 }} clips
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                {{ $outlet->pitches_count ?? 0 }} pitches
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                {{ $outlet->inquiries_count ?? 0 }} inquiries
                            </span>
                        </div>

                        {{-- Journalists at outlet --}}
                        @if(isset($outlet->journalists) && $outlet->journalists->isNotEmpty())
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400 truncate">
                                Contacts: {{ $outlet->journalists->pluck('name')->take(2)->join(', ') }}
                                @if($outlet->journalists->count() > 2)
                                    +{{ $outlet->journalists->count() - 2 }} more
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Chevron --}}
                    <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 flex-shrink-0 self-center" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                <p class="text-lg font-medium">No media outlets</p>
                <p class="text-sm mt-1">Organizations tagged as 'media' will appear here.</p>
            </div>
        @endforelse
    </div>
</div>