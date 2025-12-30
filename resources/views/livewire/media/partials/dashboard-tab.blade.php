{{-- Dashboard Tab - Redesigned Layout --}}

{{-- ============================================== --}}
{{-- STATS ROW - 4 horizontal cards                --}}
{{-- ============================================== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- This Month --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">This Month</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['clips_this_month'] }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
            </div>
        </div>
        @if(($stats['clips_month_change'] ?? 0) !== 0)
            <p class="text-xs mt-2 {{ $stats['clips_month_change'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $stats['clips_month_change'] > 0 ? '↑' : '↓' }} 
                {{ abs($stats['clips_month_change']) }}% vs last month
            </p>
        @endif
    </div>

    {{-- This Quarter --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">This Quarter</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['clips_this_quarter'] }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        @if(($stats['clips_quarter_change'] ?? 0) !== 0)
            <p class="text-xs mt-2 {{ $stats['clips_quarter_change'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $stats['clips_quarter_change'] > 0 ? '↑' : '↓' }} 
                {{ abs($stats['clips_quarter_change']) }}% vs last quarter
            </p>
        @endif
    </div>

    {{-- Staff Quoted --}}
    <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-200 dark:border-indigo-800 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Staff Quoted</p>
                <p class="text-3xl font-bold text-indigo-700 dark:text-indigo-300 mt-1">{{ $stats['staff_quoted'] }}</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-800/50 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
        </div>
        @if(($stats['unique_staff_quoted'] ?? 0) > 0)
            <p class="text-xs mt-2 text-indigo-600 dark:text-indigo-400">
                {{ $stats['unique_staff_quoted'] }} team member{{ $stats['unique_staff_quoted'] > 1 ? 's' : '' }}
            </p>
        @endif
    </div>

    {{-- Positive Rate --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Positive</p>
                <p class="text-3xl font-bold {{ ($stats['positive_rate'] ?? 0) >= 50 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }} mt-1">
                    {{ $stats['positive_rate'] ?? 0 }}%
                </p>
            </div>
            <div class="w-12 h-12 {{ ($stats['positive_rate'] ?? 0) >= 50 ? 'bg-green-50 dark:bg-green-900/30' : 'bg-gray-50 dark:bg-gray-700' }} rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 {{ ($stats['positive_rate'] ?? 0) >= 50 ? 'text-green-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
            of {{ $stats['clips_this_quarter'] }} clips this quarter
        </p>
    </div>
</div>

{{-- ============================================== --}}
{{-- MIDDLE ROW - 3 column layout                  --}}
{{-- ============================================== --}}
<div class="grid lg:grid-cols-3 gap-6 mb-6">
    
    {{-- Column 1: Needs Attention --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Needs Attention
        </h3>

        @php
            $hasUrgentInquiries = $needsAttention['inquiries_urgent']->isNotEmpty();
            $hasAwaitingPitches = $needsAttention['pitches_awaiting']->isNotEmpty();
            $hasPendingClips = ($needsAttention['clips_pending_count'] ?? 0) > 0;
            $hasAnything = $hasUrgentInquiries || $hasAwaitingPitches || $hasPendingClips;
        @endphp

        @if($hasAnything)
            <div class="space-y-3">
                @if($hasUrgentInquiries)
                    <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 rounded-lg">
                        <div class="flex items-center gap-2 text-sm font-medium text-red-700 dark:text-red-400">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            {{ $needsAttention['inquiries_urgent']->count() }} 
                            {{ Str::plural('inquiry', $needsAttention['inquiries_urgent']->count()) }} need response
                        </div>
                        <div class="mt-2 space-y-1">
                            @foreach($needsAttention['inquiries_urgent']->take(2) as $inquiry)
                                <button wire:click="setTab('inquiries')"
                                   class="block text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 truncate text-left">
                                    → {{ $inquiry->outlet?->name ?? $inquiry->outlet_name ?? 'Unknown outlet' }}
                                    @if($inquiry->deadline)
                                        <span class="text-red-500 dark:text-red-400">
                                            ({{ $inquiry->deadline->isToday() ? 'Today' : $inquiry->deadline->format('M j') }})
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($hasAwaitingPitches)
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-lg">
                        <div class="flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400">
                            <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                            {{ $needsAttention['pitches_awaiting']->count() }} 
                            {{ Str::plural('pitch', $needsAttention['pitches_awaiting']->count()) }} awaiting (7+ days)
                        </div>
                        <div class="mt-2 space-y-1">
                            @foreach($needsAttention['pitches_awaiting']->take(2) as $pitch)
                                <button wire:click="setTab('pitches')"
                                   class="block text-sm text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 truncate text-left">
                                    → {{ $pitch->outlet?->name ?? $pitch->outlet_name ?? 'Unknown outlet' }}
                                    <span class="text-amber-500">({{ $pitch->pitched_at?->diffInDays(now()) ?? '?' }}d)</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($hasPendingClips)
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm font-medium text-blue-700 dark:text-blue-400">
                                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                {{ $needsAttention['clips_pending_count'] }} clips to review
                            </div>
                            <button wire:click="$set('clipStatus', 'pending_review'); setTab('coverage')"
                               class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium">
                                Review →
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @else
            {{-- All Clear State --}}
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>All inquiries handled</span>
                </div>
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>No stale pitches</span>
                </div>
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>No clips to review</span>
                </div>
            </div>
        @endif

        {{-- Suggestion --}}
        @if(isset($needsAttention['suggestion']) && $needsAttention['suggestion'])
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                @if($needsAttention['suggestion']['type'] === 'pitch_reminder')
                    <div class="flex gap-2">
                        <span class="text-lg flex-shrink-0">💡</span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if($needsAttention['suggestion']['days'])
                                No pitches in {{ $needsAttention['suggestion']['days'] }}+ days.
                            @else
                                You haven't sent any pitches yet.
                            @endif
                            @if($needsAttention['suggestion']['journalist'])
                                Consider reaching out to
                                <a href="{{ route('people.show', $needsAttention['suggestion']['journalist']) }}" wire:navigate
                                   class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">
                                    {{ $needsAttention['suggestion']['journalist']->name }}
                                </a>
                            @endif
                        </p>
                    </div>
                @elseif($needsAttention['suggestion']['type'] === 'good_quarter')
                    <div class="flex gap-2">
                        <span class="text-lg flex-shrink-0">🎉</span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Great quarter! {{ $needsAttention['suggestion']['count'] }} clips
                            @if($needsAttention['suggestion']['vs_last'] > 0)
                                (up from {{ $needsAttention['suggestion']['vs_last'] }})
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Column 2: Coverage Breakdown --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
            </svg>
            Coverage Breakdown
        </h3>

        {{-- Sentiment Bar --}}
        <div class="mb-5">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">Sentiment</span>
            </div>
            
            @php
                $total = array_sum($stats['sentiment'] ?? []);
                $positive = $stats['sentiment']['positive'] ?? 0;
                $neutral = $stats['sentiment']['neutral'] ?? 0;
                $negative = $stats['sentiment']['negative'] ?? 0;
                $mixed = $stats['sentiment']['mixed'] ?? 0;
            @endphp

            @if($total > 0)
                <div class="h-4 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden flex">
                    @if($positive > 0)
                        <div class="bg-green-500 h-full transition-all" 
                             style="width: {{ ($positive / $total) * 100 }}%"
                             title="{{ $positive }} positive"></div>
                    @endif
                    @if($neutral > 0)
                        <div class="bg-gray-400 h-full transition-all" 
                             style="width: {{ ($neutral / $total) * 100 }}%"
                             title="{{ $neutral }} neutral"></div>
                    @endif
                    @if($mixed > 0)
                        <div class="bg-amber-400 h-full transition-all" 
                             style="width: {{ ($mixed / $total) * 100 }}%"
                             title="{{ $mixed }} mixed"></div>
                    @endif
                    @if($negative > 0)
                        <div class="bg-red-500 h-full transition-all" 
                             style="width: {{ ($negative / $total) * 100 }}%"
                             title="{{ $negative }} negative"></div>
                    @endif
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-gray-600 dark:text-gray-400">
                    @if($positive > 0)
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            {{ $positive }} positive
                        </span>
                    @endif
                    @if($neutral > 0)
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                            {{ $neutral }} neutral
                        </span>
                    @endif
                    @if($mixed > 0)
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-amber-400 rounded-full"></span>
                            {{ $mixed }} mixed
                        </span>
                    @endif
                    @if($negative > 0)
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                            {{ $negative }} negative
                        </span>
                    @endif
                </div>
            @else
                <div class="h-4 bg-gray-100 dark:bg-gray-700 rounded-full"></div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">No coverage this quarter</p>
            @endif
        </div>

        {{-- By Type --}}
        <div>
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="font-medium text-gray-700 dark:text-gray-300">By Type</span>
            </div>
            
            @if(count($stats['types'] ?? []) > 0)
                <div class="space-y-2">
                    @php
                        $maxType = max($stats['types']);
                        $typeIcons = [
                            'article' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            'podcast' => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
                            'broadcast' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                            'opinion' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                        ];
                    @endphp
                    @foreach($stats['types'] as $type => $count)
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIcons[$type] ?? $typeIcons['article'] }}"/>
                            </svg>
                            <div class="flex-1">
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400 capitalize">{{ $type }}</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $count }}</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full transition-all" 
                                         style="width: {{ ($count / $maxType) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">No coverage this quarter</p>
            @endif
        </div>
    </div>

    {{-- Column 3: Top Outlets & Recent Contacts --}}
    <div class="space-y-6">
        {{-- Top Outlets --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Top Outlets
            </h3>

            @if(count($stats['top_outlets'] ?? []) > 0)
                <div class="space-y-3">
                    @foreach($stats['top_outlets'] as $outlet => $count)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $outlet }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white ml-2">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">No coverage this quarter</p>
            @endif

            <button wire:click="setTab('outlets')"
               class="block mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                View all outlets →
            </button>
        </div>

        {{-- Recent Contacts --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Recent Contacts
            </h3>

            @if($recentContacts->isNotEmpty())
                <div class="space-y-3">
                    @foreach($recentContacts->take(3) as $contact)
                        <a href="{{ route('people.show', $contact) }}" wire:navigate
                           class="flex items-center gap-3 group">
                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ substr($contact->name, 0, 1) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-white group-hover:text-indigo-600 truncate">
                                    {{ $contact->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $contact->organization?->name ?? 'Unknown outlet' }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">No press contacts yet</p>
            @endif

            <button wire:click="setTab('contacts')"
               class="block mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                View all contacts →
            </button>
        </div>
    </div>
</div>

{{-- ============================================== --}}
{{-- RECENT COVERAGE - 2 column grid               --}}
{{-- ============================================== --}}
<div>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Coverage</h2>
        <button wire:click="setTab('coverage')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
            View all →
        </button>
    </div>

    @if($recentClips->isNotEmpty())
        <div class="grid md:grid-cols-2 gap-4">
            @foreach($recentClips as $clip)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:border-indigo-200 dark:hover:border-indigo-700 hover:shadow-sm transition">
                    <div class="flex gap-4">
                        {{-- Thumbnail --}}
                        @if($clip->image_url)
                            <div class="w-24 h-20 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100 dark:bg-gray-700">
                                <img src="{{ $clip->image_url }}" 
                                     alt=""
                                     class="w-full h-full object-cover">
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            {{-- Date & Outlet --}}
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>{{ $clip->published_at?->format('M j, Y') }}</span>
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $clip->outlet_display_name }}</span>
                            </div>

                            {{-- Title --}}
                            <a href="{{ $clip->url }}" 
                               target="_blank"
                               rel="noopener"
                               class="block font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition line-clamp-2 mb-2">
                                {{ $clip->title }}
                                <svg class="inline w-3 h-3 ml-0.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>

                            {{-- Meta Row --}}
                            <div class="flex items-center flex-wrap gap-2 text-xs">
                                @if($clip->journalist_display_name !== 'Unknown')
                                    <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $clip->journalist_display_name }}
                                    </span>
                                @endif

                                @if($clip->staffMentioned->isNotEmpty())
                                    <span class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                        </svg>
                                        {{ $clip->staffMentioned->pluck('name')->join(', ') }} quoted
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Sentiment Badge --}}
                        <div class="flex-shrink-0">
                            @php
                                $sentimentColors = [
                                    'positive' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'neutral' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    'negative' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'mixed' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $sentimentColors[$clip->sentiment] ?? $sentimentColors['neutral'] }}">
                                {{ ucfirst($clip->sentiment) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 mb-2">No press coverage logged yet</p>
            <button wire:click="openClipModal"
               class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                Log your first clip →
            </button>
        </div>
    @endif
</div>
