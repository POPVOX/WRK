{{-- Timeline Tab - Gantt Chart Style --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="p-5 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">2026 Roadmap</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gantt-style view of publications and events</p>
    </div>

    {{-- Gantt Chart --}}
    <div class="overflow-x-auto">
        <div class="min-w-[1200px] p-4">
            {{-- Month Headers --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">
                <div class="w-48 flex-shrink-0 font-medium text-sm text-gray-700 dark:text-gray-300 pr-4">Item</div>
                <div class="flex-1 grid grid-cols-12 gap-1">
                    @foreach(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $idx => $month)
                        @php
                            $isCurrent = now()->month === ($idx + 1) && now()->year === 2026;
                            $isPast = now()->year > 2026 || (now()->year === 2026 && now()->month > ($idx + 1));
                        @endphp
                        <div class="text-center text-xs font-medium {{ $isCurrent ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $month }}
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Current Month Indicator Line --}}
            @php
                $currentMonth = now()->month;
                $currentYear = now()->year;
                $dayOfMonth = now()->day;
                $daysInMonth = now()->daysInMonth;
                // Position: (month - 1) * (100/12)% + (day/daysInMonth) * (100/12)%
                $leftPercent = (($currentMonth - 1) / 12 * 100) + (($dayOfMonth / $daysInMonth) / 12 * 100);
            @endphp

            {{-- Publications Section --}}
            <div class="mb-6">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <span>📝</span> Publications
                </div>
                
                @forelse($project->publications->sortBy('target_date') as $pub)
                    @php
                        $targetMonth = $pub->target_date ? $pub->target_date->month : 1;
                        $startOffset = (($targetMonth - 1) / 12) * 100;
                        $statusColors = [
                            'idea' => 'bg-gray-300 dark:bg-gray-600',
                            'outlined' => 'bg-purple-400 dark:bg-purple-600',
                            'drafting' => 'bg-blue-400 dark:bg-blue-600',
                            'editing' => 'bg-yellow-400 dark:bg-yellow-600',
                            'review' => 'bg-orange-400 dark:bg-orange-600',
                            'ready' => 'bg-emerald-400 dark:bg-emerald-600',
                            'published' => 'bg-green-500 dark:bg-green-600',
                        ];
                        $barColor = $statusColors[$pub->status] ?? 'bg-gray-400';
                    @endphp
                    <div class="flex items-center mb-2 group">
                        <div class="w-48 flex-shrink-0 pr-4">
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" title="{{ $pub->title }}">
                                {{ Str::limit($pub->title, 28) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $pub->target_date?->format('M j') ?? 'No date' }}
                            </div>
                        </div>
                        <div class="flex-1 relative h-8 bg-gray-100 dark:bg-gray-700 rounded">
                            {{-- Bar --}}
                            <div class="absolute top-1 bottom-1 rounded {{ $barColor }} shadow-sm flex items-center justify-center text-xs text-white font-medium transition-all group-hover:shadow-md"
                                style="left: {{ $startOffset }}%; width: calc(100% / 12 - 4px);">
                                <span class="truncate px-1">{{ \App\Models\ProjectPublication::STATUSES[$pub->status] ?? $pub->status }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400 italic pl-48">No publications scheduled</div>
                @endforelse
            </div>

            {{-- Events Section --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <span>🎪</span> Events
                </div>
                
                @forelse($project->events->sortBy('event_date') as $event)
                    @php
                        $eventMonth = $event->event_date ? $event->event_date->month : 1;
                        $startOffset = (($eventMonth - 1) / 12) * 100;
                        $typeColors = [
                            'staff_event' => 'bg-purple-500 dark:bg-purple-600',
                            'demo' => 'bg-blue-500 dark:bg-blue-600',
                            'launch' => 'bg-pink-500 dark:bg-pink-600',
                            'briefing' => 'bg-indigo-500 dark:bg-indigo-600',
                            'workshop' => 'bg-teal-500 dark:bg-teal-600',
                            'other' => 'bg-gray-500 dark:bg-gray-600',
                        ];
                        $barColor = $typeColors[$event->type] ?? 'bg-purple-400';
                    @endphp
                    <div class="flex items-center mb-2 group">
                        <div class="w-48 flex-shrink-0 pr-4">
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" title="{{ $event->title }}">
                                {{ Str::limit($event->title, 28) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $event->event_date?->format('M j') ?? 'TBD' }}
                            </div>
                        </div>
                        <div class="flex-1 relative h-8 bg-gray-100 dark:bg-gray-700 rounded">
                            {{-- Event marker (diamond shape) --}}
                            <div class="absolute top-1/2 -translate-y-1/2 w-6 h-6 {{ $barColor }} rotate-45 rounded-sm shadow-sm group-hover:shadow-md transition-all"
                                style="left: calc({{ $startOffset }}% + (100% / 24) - 12px);">
                            </div>
                            {{-- Label --}}
                            <div class="absolute top-1/2 -translate-y-1/2 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                style="left: calc({{ $startOffset }}% + (100% / 24) + 16px);">
                                {{ \App\Models\ProjectEvent::TYPES[$event->type] ?? $event->type }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400 italic pl-48">No events scheduled</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-x-6 gap-y-2 text-xs">
            <div class="font-semibold text-gray-600 dark:text-gray-400">Publication Status:</div>
            @foreach([
                'idea' => ['bg-gray-300', 'Idea'],
                'outlined' => ['bg-purple-400', 'Outlined'],
                'drafting' => ['bg-blue-400', 'Drafting'],
                'editing' => ['bg-yellow-400', 'Editing'],
                'review' => ['bg-orange-400', 'Review'],
                'published' => ['bg-green-500', 'Published'],
            ] as $status => $info)
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded {{ $info[0] }}"></div>
                    <span class="text-gray-600 dark:text-gray-400">{{ $info[1] }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-2 text-xs mt-3">
            <div class="font-semibold text-gray-600 dark:text-gray-400">Event Types:</div>
            @foreach([
                'staff_event' => ['bg-purple-500', 'Staff Event'],
                'demo' => ['bg-blue-500', 'Demo'],
                'launch' => ['bg-pink-500', 'Launch'],
                'workshop' => ['bg-teal-500', 'Workshop'],
            ] as $type => $info)
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rotate-45 rounded-sm {{ $info[0] }}"></div>
                    <span class="text-gray-600 dark:text-gray-400">{{ $info[1] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>