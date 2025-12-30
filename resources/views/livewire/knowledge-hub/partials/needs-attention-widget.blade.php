<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            Needs Attention
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Things that need your action</p>
    </div>

    <div class="p-5 space-y-4">
        {{-- Overdue Commitments --}}
        @if($needsAttention['overdue_count'] > 0)
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        Overdue Commitments
                    </span>
                    <span class="text-sm text-red-600 dark:text-red-400 font-medium">{{ $needsAttention['overdue_count'] }}</span>
                </div>

                @foreach($needsAttention['overdue_commitments']->take(3) as $commitment)
                    <div class="block pl-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition">
                        <div class="font-medium truncate">{{ Str::limit($commitment->description, 50) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $commitment->organization?->name ?? $commitment->person?->name ?? 'Unknown' }}
                            • Due {{ $commitment->due_date->diffForHumans() }}
                        </div>
                    </div>
                @endforeach

                @if($needsAttention['overdue_count'] > 3)
                    <a href="#" class="block pl-4 text-sm text-rose-600 dark:text-rose-400 hover:text-rose-800">
                        View all {{ $needsAttention['overdue_count'] }} →
                    </a>
                @endif
            </div>
        @endif

        {{-- Meetings Need Notes --}}
        @if($needsAttention['meetings_need_notes_count'] > 0)
            <div class="space-y-2 {{ $needsAttention['overdue_count'] > 0 ? 'pt-4 border-t border-gray-100 dark:border-gray-700' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                        Meetings Need Notes
                    </span>
                    <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">{{ $needsAttention['meetings_need_notes_count'] }}</span>
                </div>

                @foreach($needsAttention['meetings_need_notes'] as $meeting)
                    <a href="{{ route('meetings.show', $meeting) }}"
                        class="block pl-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition">
                        <div class="font-medium truncate">{{ $meeting->title ?: 'Untitled Meeting' }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $meeting->meeting_date?->format('M j, Y') }}
                            @if($meeting->organizations->first())
                                • {{ $meeting->organizations->first()->name }}
                            @endif
                        </div>
                    </a>
                @endforeach

                @if($needsAttention['meetings_need_notes_count'] > 3)
                    <a href="{{ route('meetings.index') }}?view=needs_notes"
                        class="block pl-4 text-sm text-amber-600 dark:text-amber-400 hover:text-amber-800">
                        View all {{ $needsAttention['meetings_need_notes_count'] }} →
                    </a>
                @endif
            </div>
        @endif

        {{-- Reports Due (Management only) --}}
        @if($needsAttention['reports_due_soon']->count() > 0)
            <div class="space-y-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                        Reports Due Soon
                    </span>
                    <span class="text-sm text-blue-600 dark:text-blue-400 font-medium">{{ $needsAttention['reports_due_soon']->count() }}</span>
                </div>

                @foreach($needsAttention['reports_due_soon'] as $report)
                    <a href="{{ route('grants.show', $report->grant) }}"
                        class="block pl-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition">
                        <div class="font-medium truncate">{{ $report->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $report->grant->funder?->name }}
                            • Due {{ $report->due_date->format('M j') }}
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- All Clear State --}}
        @if($needsAttention['overdue_count'] === 0 && $needsAttention['meetings_need_notes_count'] === 0 && $needsAttention['reports_due_soon']->isEmpty())
            <div class="text-center py-6">
                <svg class="w-12 h-12 mx-auto text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400">All caught up! Nothing needs attention right now.</p>
            </div>
        @endif
    </div>
</div>