{{-- Cards View - Grid of meeting cards --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    @forelse($allMeetings as $meeting)
        @php
            $colors = ['bg-indigo-500', 'bg-purple-500', 'bg-blue-500', 'bg-teal-500', 'bg-pink-500', 'bg-amber-500'];
            $colorIndex = abs(crc32($meeting->organizations->first()?->name ?? 'default')) % count($colors);
        @endphp
        <a href="{{ route('meetings.show', $meeting) }}"
            class="block bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-lg transition group">

            {{-- Color Bar --}}
            <div class="h-1.5 {{ $colors[$colorIndex] }}"></div>

            <div class="p-4">
                {{-- Date Badge --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="text-center bg-gray-100 dark:bg-gray-700 rounded-lg px-2.5 py-1">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $meeting->meeting_date?->format('M') }}</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white -mt-0.5">
                                {{ $meeting->meeting_date?->format('j') }}</div>
                        </div>
                        <div>
                            @if($meeting->isPast() && $meeting->hasNotes())
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">
                                    Complete
                                </span>
                            @elseif($meeting->isPast())
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400">
                                    Needs Notes
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400">
                                    Upcoming
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Title --}}
                <h3
                    class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition line-clamp-2 mb-2">
                    {{ $meeting->title ?: 'Untitled Meeting' }}
                </h3>

                {{-- Organization --}}
                @if($meeting->organizations->first())
                    <div class="flex items-center gap-2 mb-3">
                        <div
                            class="w-5 h-5 rounded {{ $colors[$colorIndex] }} flex items-center justify-center text-white text-xs font-bold">
                            {{ strtoupper(substr($meeting->organizations->first()->name, 0, 1)) }}
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400 truncate">
                            {{ $meeting->organizations->first()->name }}
                        </span>
                    </div>
                @endif

                {{-- Attendees --}}
                @if($meeting->people->count() > 0)
                    <div class="flex items-center gap-2">
                        <div class="flex -space-x-1.5 overflow-hidden">
                            @foreach($meeting->people->take(4) as $person)
                                <div
                                    class="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-300">
                                    {{ strtoupper(substr($person->name, 0, 1)) }}
                                </div>
                            @endforeach
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $meeting->people->count() }} attendee{{ $meeting->people->count() !== 1 ? 's' : '' }}
                        </span>
                    </div>
                @endif

                {{-- Issues Tags --}}
                @if($meeting->issues->count() > 0)
                    <div class="flex flex-wrap gap-1 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        @foreach($meeting->issues->take(3) as $issue)
                            <span class="px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                {{ $issue->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </a>
    @empty
        <div
            class="col-span-full p-12 text-center bg-gray-50 dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No meetings found matching your filters.</p>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($allMeetings->hasPages())
    <div class="mt-6">
        {{ $allMeetings->links() }}
    </div>
@endif