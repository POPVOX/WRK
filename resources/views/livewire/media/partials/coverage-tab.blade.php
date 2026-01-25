{{-- Coverage Tab --}}
<div>
    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search clips..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <select wire:model.live="dateRange"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="all">All Time</option>
                <option value="year">Last Year</option>
                <option value="quarter">Last Quarter</option>
                <option value="month">Last Month</option>
                <option value="week">Last Week</option>
            </select>
            <select wire:model.live="clipType"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Types</option>
                <option value="article">Articles</option>
                <option value="broadcast">Broadcast</option>
                <option value="podcast">Podcasts</option>
                <option value="opinion">Opinion</option>
                <option value="interview">Interviews</option>
            </select>
            <input wire:model.live.debounce.300ms="outletFilter" type="text" placeholder="Filter by outlet..."
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm w-40">
            <select wire:model.live="sentiment"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Sentiment</option>
                <option value="positive">Positive</option>
                <option value="neutral">Neutral</option>
                <option value="negative">Negative</option>
                <option value="mixed">Mixed</option>
            </select>
            <select wire:model.live="clipStatus"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="approved">Approved</option>
                <option value="pending_review">Pending Review</option>
            </select>
        </div>
    </div>

    {{-- Clips Grid --}}
    <div class="space-y-4">
        @forelse($clips as $clip)
            @include('livewire.media.partials.clip-card', ['clip' => $clip])
        @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                <p class="text-lg font-medium">No clips found</p>
                <p class="text-sm mt-1">Try adjusting your filters or log a new clip</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($clips->hasPages())
        <div class="mt-6">
            {{ $clips->links() }}
        </div>
    @endif
</div>