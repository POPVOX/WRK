{{-- Sections View (Default - Upcoming, Needs Notes, Completed) --}}
<div class="space-y-8">

    {{-- UPCOMING SECTION --}}
    @if($view === '' || $view === 'upcoming')
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Upcoming
                    <span
                        class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $upcomingMeetings->count() }})</span>
                </h2>
            </div>

            @if($upcomingMeetings->count() > 0)
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($upcomingMeetings as $meeting)
                        @include('livewire.meetings.partials.upcoming-card', ['meeting' => $meeting])
                    @endforeach
                </div>
            @else
                <div
                    class="p-8 text-center bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No upcoming meetings</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Schedule a new meeting or import from your calendar.
                    </p>
                    <a href="{{ route('meetings.create') }}"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                        Log a meeting →
                    </a>
                </div>
            @endif
        </section>
    @endif

    {{-- NEEDS NOTES SECTION --}}
    @if(($view === '' || $view === 'needs_notes') && $needsNotesMeetings->count() > 0)
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Needs Notes
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $stats['needs_notes'] }})</span>
                </h2>
                @if($stats['needs_notes'] > 10)
                    <a href="?view=needs_notes" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                        View all →
                    </a>
                @endif
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($needsNotesMeetings as $meeting)
                    @include('livewire.meetings.partials.needs-notes-row', ['meeting' => $meeting])
                @endforeach
            </div>
        </section>
    @endif

    {{-- COMPLETED SECTION --}}
    @if($view === '' || $view === 'completed')
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Completed
                </h2>
                <select wire:model.live="completedPeriod"
                    class="text-sm border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="year">This Year</option>
                    <option value="all">All Time</option>
                </select>
            </div>

            @if($completedMeetings->count() > 0)
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($completedMeetings as $meeting)
                        @include('livewire.meetings.partials.completed-row', ['meeting' => $meeting])
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($completedMeetings->hasPages())
                    <div class="mt-4">
                        {{ $completedMeetings->links() }}
                    </div>
                @endif
            @else
                <div
                    class="p-8 text-center bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No completed meetings with notes in this period.</p>
                </div>
            @endif
        </section>
    @endif

</div>