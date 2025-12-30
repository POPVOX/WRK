<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            This Week
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Upcoming meetings</p>
    </div>

    <div class="p-5">
        @if($thisWeekMeetings->isEmpty())
            <div class="text-center py-6">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">No meetings scheduled this week</p>
                <a href="{{ route('meetings.create') }}"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 mt-2 inline-block">
                    + Log a meeting
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($thisWeekMeetings as $date => $meetings)
                    @php
                        $dateObj = \Carbon\Carbon::parse($date);
                        $isToday = $dateObj->isToday();
                        $isTomorrow = $dateObj->isTomorrow();
                        $dayLabel = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $dateObj->format('l'));
                    @endphp

                    <div>
                        <h3
                            class="text-xs font-semibold uppercase tracking-wide {{ $isToday ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }} mb-2">
                            {{ $dayLabel }}
                            @unless($isToday || $isTomorrow)
                                <span class="font-normal normal-case">{{ $dateObj->format('M j') }}</span>
                            @endunless
                        </h3>

                        <div class="space-y-2">
                            @foreach($meetings as $meeting)
                                <a href="{{ route('meetings.show', $meeting) }}"
                                    class="block p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition truncate">
                                                {{ $meeting->title ?: 'Untitled Meeting' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                @if($meeting->people->count() > 0)
                                                    {{ $meeting->people->count() }}
                                                    attendee{{ $meeting->people->count() > 1 ? 's' : '' }}
                                                @endif
                                            </div>
                                            @if($meeting->organizations->first())
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $meeting->organizations->first()->name }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700">
        <a href="{{ route('meetings.index') }}?view=upcoming"
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
            View all upcoming →
        </a>
    </div>
</div>