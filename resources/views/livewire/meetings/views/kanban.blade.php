{{-- Kanban View - Columns by Month --}}
<div class="overflow-x-auto pb-4">
    <div class="flex gap-4 min-w-max">
        @php
            $months = collect();
            $currentDate = now()->startOfMonth();
            for ($i = 0; $i < 7; $i++) {
                $months->push($currentDate->copy()->addMonths($i));
            }
        @endphp

        @foreach($months as $month)
            @php
                $monthKey = $month->format('Y-m');
                $monthMeetings = $kanbanMeetings->get($monthKey, collect());
                $isCurrentMonth = $month->isCurrentMonth();
            @endphp

            <div class="flex-shrink-0 w-72">
                {{-- Column Header --}}
                <div
                    class="flex items-center justify-between px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-t-lg border border-b-0 border-gray-200 dark:border-gray-700">
                    <h3
                        class="font-semibold {{ $isCurrentMonth ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $month->format('F Y') }}
                    </h3>
                    <span
                        class="text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-700 px-2 py-0.5 rounded-full">
                        {{ $monthMeetings->count() }}
                    </span>
                </div>

                {{-- Column Content --}}
                <div
                    class="bg-gray-50 dark:bg-gray-900 rounded-b-lg border border-gray-200 dark:border-gray-700 p-2 min-h-[300px] max-h-[600px] overflow-y-auto space-y-2">
                    @forelse($monthMeetings as $meeting)
                        @php
                            $colors = ['bg-indigo-500', 'bg-purple-500', 'bg-blue-500', 'bg-teal-500', 'bg-pink-500', 'bg-amber-500'];
                            $colorIndex = abs(crc32($meeting->organizations->first()?->name ?? 'default')) % count($colors);
                        @endphp
                        <a href="{{ route('meetings.show', $meeting) }}"
                            class="block p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md border border-gray-100 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 transition group">

                            {{-- Date --}}
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <span
                                        class="font-medium {{ $meeting->meeting_date->isPast() ? 'text-gray-500 dark:text-gray-400' : 'text-indigo-600 dark:text-indigo-400' }}">
                                        {{ $meeting->meeting_date->format('D j') }}
                                    </span>
                                </div>
                                @if($meeting->isPast() && $meeting->hasNotes())
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                @elseif($meeting->isPast())
                                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>

                            {{-- Title --}}
                            <h4
                                class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition line-clamp-2">
                                {{ Str::limit($meeting->title ?: 'Untitled Meeting', 50) }}
                            </h4>

                            {{-- Organization --}}
                            @if($meeting->organizations->first())
                                <div class="flex items-center gap-1.5 mt-2">
                                    <div
                                        class="w-4 h-4 rounded {{ $colors[$colorIndex] }} flex items-center justify-center text-white text-[10px] font-bold">
                                        {{ strtoupper(substr($meeting->organizations->first()->name, 0, 1)) }}
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ Str::limit($meeting->organizations->first()->name, 20) }}
                                    </span>
                                </div>
                            @endif

                            {{-- Attendee count --}}
                            @if($meeting->people->count() > 0)
                                <div class="flex items-center gap-1 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ $meeting->people->count() }}
                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-xs text-gray-400 dark:text-gray-500">No meetings</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

<p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
    Showing 7 months from {{ now()->format('F Y') }}
</p>