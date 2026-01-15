<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="text-3xl">🌍</span> Travel
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    Manage team travel, itineraries, and expenses
                </p>
            </div>
            <a href="{{ route('travel.create') }}" 
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Trip
            </a>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-8">
        <nav class="flex gap-8 overflow-x-auto" aria-label="Tabs">
            @foreach([
                'all' => ['label' => 'All Trips', 'count' => $stats['all']],
                'upcoming' => ['label' => 'Upcoming', 'count' => $stats['upcoming']],
                'my' => ['label' => 'My Trips', 'count' => $stats['my']],
                'completed' => ['label' => 'Completed', 'count' => $stats['completed']],
                'templates' => ['label' => 'Templates', 'count' => $stats['templates']],
            ] as $key => $tabInfo)
                <button wire:click="setTab('{{ $key }}')"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition {{ $tab === $key 
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                    {{ $tabInfo['label'] }}
                    <span class="ml-2 py-0.5 px-2 rounded-full text-xs {{ $tab === $key 
                        ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' 
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                        {{ $tabInfo['count'] }}
                    </span>
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-8">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search trips..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Status Filter -->
            <select wire:model.live="status" 
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <!-- Type Filter -->
            <select wire:model.live="type" 
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                <option value="">All Types</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <!-- Traveler Filter -->
            <select wire:model.live="traveler" 
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                <option value="">All Travelers</option>
                @foreach($travelers as $trav)
                    <option value="{{ $trav->id }}">{{ $trav->name }}</option>
                @endforeach
            </select>

            <!-- View Toggle -->
            <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                <button wire:click="setView('cards')" 
                        class="p-2 rounded {{ $view === 'cards' ? 'bg-white dark:bg-gray-600 shadow' : '' }}">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button wire:click="setView('list')" 
                        class="p-2 rounded {{ $view === 'list' ? 'bg-white dark:bg-gray-600 shadow' : '' }}">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- Clear Filters -->
            @if($search || $status || $type || $traveler)
                <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    Clear filters
                </button>
            @endif
        </div>
    </div>

    <!-- Trips Grid/List -->
    @if($trips->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="text-6xl mb-4">🌍</div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No trips found</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                @if($search || $status || $type || $traveler)
                    Try adjusting your filters
                @else
                    Get started by planning your first trip
                @endif
            </p>
            <a href="{{ route('travel.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Plan a Trip
            </a>
        </div>
    @elseif($view === 'cards')
        <!-- Cards View -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($trips as $trip)
                <a href="{{ route('travel.show', $trip) }}" 
                   class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-200 group">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">{{ $trip->country_flag }}</span>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 line-clamp-1 text-lg">
                                        {{ $trip->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $trip->primary_destination_city }}, {{ $trip->primary_destination_country }}
                                    </p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ \App\Models\Trip::getStatusColors()[$trip->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Trip::getStatusOptions()[$trip->status] ?? $trip->status }}
                            </span>
                        </div>

                        <!-- Dates -->
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $trip->start_date->format('M j') }} - {{ $trip->end_date->format('M j, Y') }}</span>
                            <span class="text-gray-400 dark:text-gray-500">({{ $trip->duration }} days)</span>
                        </div>

                        <!-- Type Badge -->
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-lg">{{ \App\Models\Trip::getTypeIcons()[$trip->type] ?? '✈️' }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ \App\Models\Trip::getTypeOptions()[$trip->type] ?? $trip->type }}
                            </span>
                        </div>

                        <!-- Travelers -->
                        @if($trip->travelers->isNotEmpty())
                            <div class="flex items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex -space-x-2">
                                    @foreach($trip->travelers->take(4) as $traveler)
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-medium border-2 border-white dark:border-gray-800"
                                             title="{{ $traveler->name }}">
                                            {{ substr($traveler->name, 0, 1) }}
                                        </div>
                                    @endforeach
                                    @if($trip->travelers->count() > 4)
                                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 text-xs font-medium border-2 border-white dark:border-gray-800">
                                            +{{ $trip->travelers->count() - 4 }}
                                        </div>
                                    @endif
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $trip->travelers->count() }} traveler{{ $trip->travelers->count() !== 1 ? 's' : '' }}
                                </span>
                            </div>
                        @endif

                        <!-- Compliance Warning -->
                        @if($trip->hasComplianceIssues())
                            <div class="mt-4 flex items-center gap-2 text-amber-600 dark:text-amber-400 text-sm bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Action required
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <!-- List View -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trip</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Travelers</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($trips as $trip)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">{{ $trip->country_flag }}</span>
                                    <div>
                                        <a href="{{ route('travel.show', $trip) }}" class="font-medium text-gray-900 dark:text-white hover:text-indigo-600">
                                            {{ $trip->name }}
                                        </a>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $trip->primary_destination_city }}, {{ $trip->primary_destination_country }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $trip->start_date->format('M j') }} - {{ $trip->end_date->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ \App\Models\Trip::getTypeIcons()[$trip->type] ?? '✈️' }}
                                {{ \App\Models\Trip::getTypeOptions()[$trip->type] ?? $trip->type }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex -space-x-2">
                                    @foreach($trip->travelers->take(3) as $traveler)
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-medium border-2 border-white dark:border-gray-800"
                                             title="{{ $traveler->name }}">
                                            {{ substr($traveler->name, 0, 1) }}
                                        </div>
                                    @endforeach
                                    @if($trip->travelers->count() > 3)
                                        <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 text-xs border-2 border-white dark:border-gray-800">
                                            +{{ $trip->travelers->count() - 3 }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ \App\Models\Trip::getStatusColors()[$trip->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ \App\Models\Trip::getStatusOptions()[$trip->status] ?? $trip->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('travel.show', $trip) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    View →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Pagination -->
    @if($trips->hasPages())
        <div class="mt-6">
            {{ $trips->links() }}
        </div>
    @endif
</div>
