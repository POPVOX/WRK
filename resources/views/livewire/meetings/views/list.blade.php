{{-- List View - Compact table-style list --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Meeting</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Organization</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Attendees</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($allMeetings as $meeting)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition" 
                    onclick="window.location='{{ route('meetings.show', $meeting) }}'">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        <div class="font-medium">{{ $meeting->meeting_date?->format('M j') }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $meeting->meeting_date?->format('Y') }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                            {{ $meeting->title ?: 'Untitled Meeting' }}
                        </div>
                        @if($meeting->issues->count() > 0)
                            <div class="flex gap-1 mt-1">
                                @foreach($meeting->issues->take(2) as $issue)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $issue->name }}
                                    </span>
                                @endforeach
                                @if($meeting->issues->count() > 2)
                                    <span class="text-xs text-gray-500">+{{ $meeting->issues->count() - 2 }}</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden md:table-cell">
                        @if($meeting->organizations->first())
                            {{ Str::limit($meeting->organizations->first()->name, 25) }}
                        @else
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                        @if($meeting->people->count() > 0)
                            <div class="flex -space-x-2 overflow-hidden">
                                @foreach($meeting->people->take(3) as $person)
                                    <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                        {{ strtoupper(substr($person->name, 0, 1)) }}
                                    </div>
                                @endforeach
                                @if($meeting->people->count() > 3)
                                    <span class="flex items-center justify-center h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-500">
                                        +{{ $meeting->people->count() - 3 }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($meeting->isPast() && $meeting->hasNotes())
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">
                                Complete
                            </span>
                        @elseif($meeting->isPast())
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400">
                                Needs Notes
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400">
                                Upcoming
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        No meetings found matching your filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($allMeetings->hasPages())
    <div class="mt-4">
        {{ $allMeetings->links() }}
    </div>
@endif
