@php
    $isToday = $meeting->meeting_date->isToday();
    $isTomorrow = $meeting->meeting_date->isTomorrow();
    $isThisWeek = $meeting->meeting_date->isCurrentWeek();

    $dateLabel = match (true) {
        $isToday => 'Today',
        $isTomorrow => 'Tomorrow',
        $isThisWeek => $meeting->meeting_date->format('l'),
        default => $meeting->meeting_date->format('M j'),
    };
@endphp

<div
    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
    {{-- Date Banner --}}
    <div
        class="px-4 py-2 bg-gradient-to-r from-indigo-50 dark:from-indigo-900/30 to-white dark:to-gray-800 border-b border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <span
                class="text-sm font-medium {{ $isToday ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-700 dark:text-gray-300' }}">
                {{ $dateLabel }}
            </span>
            @if($isToday)
                <span
                    class="px-2 py-0.5 text-xs font-medium bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-300 rounded-full">Today</span>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="p-4">
        {{-- Title --}}
        <a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="block group">
            <h3
                class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                {{ $meeting->title ?: 'Untitled Meeting' }}
            </h3>
        </a>

        {{-- Attendees --}}
        @if($meeting->people->count() > 0)
            <div class="flex items-center gap-2 mt-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                @if($meeting->people->count() <= 2)
                    {{ $meeting->people->pluck('name')->join(', ') }}
                @else
                    {{ $meeting->people->first()->name }} + {{ $meeting->people->count() - 1 }} others
                @endif
            </div>
        @endif

        {{-- Organizations --}}
        @if($meeting->organizations->count() > 0)
            <div class="flex items-center gap-2 mt-1 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                {{ $meeting->organizations->pluck('name')->join(', ') }}
            </div>
        @endif

        {{-- Issues/Topics --}}
        @if($meeting->issues->count() > 0)
            <div class="flex flex-wrap items-center gap-2 mt-3">
                @foreach($meeting->issues->take(3) as $issue)
                    <span
                        class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full">
                        {{ $issue->name }}
                    </span>
                @endforeach
                @if($meeting->issues->count() > 3)
                    <span class="text-xs text-gray-400">+{{ $meeting->issues->count() - 3 }} more</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div
        class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('meetings.show', $meeting) }}" wire:navigate
                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                View details
            </a>

            {{-- Edit/Delete dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute left-0 bottom-full mb-2 w-36 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-20">
                    <a href="{{ route('meetings.edit', $meeting) }}" wire:navigate
                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </a>
                    <button wire:click="deleteMeeting({{ $meeting->id }})"
                        wire:confirm="Are you sure you want to delete this meeting? This action cannot be undone."
                        class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 w-full text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
        <a href="{{ route('meetings.show', $meeting) }}" wire:navigate
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Prep
        </a>
    </div>
</div>