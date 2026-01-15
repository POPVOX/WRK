{{-- Stats Row - 4 cards, horizontal like Media dashboard --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- Active Funding --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Funding</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                    ${{ number_format($stats['active_funding'] / 1000) }}K
                </p>
            </div>
            <div class="w-12 h-12 bg-green-50 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
            across {{ $stats['active_grants'] }} active grant{{ $stats['active_grants'] !== 1 ? 's' : '' }}
        </p>
    </div>

    {{-- Total Grants --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Grants</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total_grants'] }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>
        <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
            {{ $stats['active_grants'] }} active • {{ $stats['completed_grants'] }} completed
        </p>
    </div>

    {{-- Current Funders --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Funders</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['current_funders'] }}</p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
            with active grants
        </p>
    </div>

    {{-- Pipeline Value --}}
    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Pipeline</p>
                <p class="text-3xl font-bold text-purple-700 dark:text-purple-300 mt-1">
                    ${{ number_format(($stats['pipeline_value'] ?? 0) / 1000) }}K
                </p>
            </div>
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
        </div>
        <p class="text-xs mt-2 text-purple-600 dark:text-purple-400">
            {{ $stats['prospective_count'] }} prospective funder{{ $stats['prospective_count'] !== 1 ? 's' : '' }}
        </p>
    </div>
</div>

{{-- Middle Row - 3 Columns --}}
<div class="grid lg:grid-cols-3 gap-6 mb-6">
    
    {{-- Column 1: Needs Attention --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Needs Attention
        </h3>

        @if($needsAttention['grants_ending_soon']->isNotEmpty())
            <div class="space-y-3">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg">
                    <div class="flex items-center gap-2 text-sm font-medium text-blue-700 dark:text-blue-400">
                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                        {{ $needsAttention['grants_ending_soon']->count() }} grant{{ $needsAttention['grants_ending_soon']->count() !== 1 ? 's' : '' }} ending soon
                    </div>
                    <div class="mt-2 space-y-1">
                        @foreach($needsAttention['grants_ending_soon']->take(3) as $grant)
                            <a href="{{ route('grants.show', $grant) }}" wire:navigate
                               class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 truncate">
                                → {{ $grant->name }} ({{ $grant->end_date->format('M j') }})
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            {{-- All Clear --}}
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>All grants in good standing</span>
                </div>
            </div>
        @endif

        {{-- Suggestion --}}
        @if($needsAttention['suggestion'])
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex gap-2">
                    <span class="text-lg flex-shrink-0">💡</span>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $needsAttention['suggestion'] }}
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- Column 2: Upcoming Deadlines --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Upcoming Deadlines
        </h3>

        @if($upcomingDeadlines->isNotEmpty())
            <div class="space-y-3">
                @foreach($upcomingDeadlines->take(5) as $deadline)
                    @php
                        $isOverdue = $deadline['date']->isPast();
                        $isToday = $deadline['date']->isToday();
                    @endphp
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-12 text-center">
                            <div class="text-xs font-medium {{ $isOverdue ? 'text-red-600 dark:text-red-400' : ($isToday ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400') }} uppercase">
                                {{ $deadline['date']->format('M') }}
                            </div>
                            <div class="text-lg font-bold {{ $isOverdue ? 'text-red-600 dark:text-red-400' : ($isToday ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white') }}">
                                {{ $deadline['date']->format('j') }}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ $deadline['url'] }}" wire:navigate
                               class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 truncate block">
                                {{ $deadline['title'] }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $deadline['funder'] }} • {{ $deadline['type'] }}
                            </p>
                        </div>
                        @if($isOverdue)
                            <span class="text-xs text-red-600 dark:text-red-400 font-medium">Overdue</span>
                        @elseif($isToday)
                            <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">Today</span>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <button wire:click="setTab('reports')"
               class="block mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                View all reports →
            </button>
        @else
            <div class="text-center py-6">
                <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming deadlines</p>
            </div>
        @endif
    </div>

    {{-- Column 3: Funder Health --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            Funder Relationships
        </h3>

        <div class="space-y-4">
            {{-- Funding by Funder --}}
            @if($funderBreakdown->isNotEmpty())
                @php $maxFunding = $funderBreakdown->max('total_funding'); @endphp
                @foreach($funderBreakdown->take(4) as $funder)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 dark:text-gray-300 truncate">{{ $funder->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400 ml-2">${{ number_format($funder->total_funding / 1000) }}K</span>
                        </div>
                        <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full transition-all" 
                                 style="width: {{ $maxFunding > 0 ? ($funder->total_funding / $maxFunding) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">No active funding</p>
            @endif
        </div>

        <button wire:click="setTab('funders')"
           class="block mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 w-full text-left">
            View all funders →
        </button>
    </div>
</div>

{{-- Funders Grid (Compact on Dashboard) --}}
<div>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Current Funders</h2>
        <button wire:click="setTab('funders')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
            View all →
        </button>
    </div>

    @if($currentFunders->isNotEmpty())
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($currentFunders->take(4) as $funder)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:border-indigo-200 dark:hover:border-indigo-700 transition">
                    <div class="flex items-center gap-3 mb-3">
                        @if($funder->logo_url)
                            <img src="{{ $funder->logo_url }}" alt="{{ $funder->name }}" class="w-10 h-10 rounded-lg object-cover">
                        @else
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white text-xs font-bold">
                                {{ strtoupper(substr($funder->name, 0, 2)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <a href="#" wire:click="setTab('funders')" class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 truncate block">
                                {{ $funder->name }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $funder->grants_count }} grant{{ $funder->grants_count !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">${{ number_format($funder->total_funding / 1000) }}K active</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400 mb-2">No current funders yet</p>
            <button wire:click="openCreateFunderModal" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                + Add your first funder
            </button>
        </div>
    @endif
</div>
