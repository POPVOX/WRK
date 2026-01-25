<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Projects</h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Manage initiatives, track progress, and organize work
                streams</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('projects.create') }}" wire:navigate
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-sm font-medium rounded-lg text-white hover:bg-indigo-700 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Project
            </a>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col lg:flex-row gap-4 items-start lg:items-center">
        <div class="flex-1 min-w-0">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search projects..."
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <select wire:model.live="filterStatus"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">All Statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterScope"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">All Scopes</option>
                @foreach($scopes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterLead"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">All Leads</option>
                @foreach($leads as $lead)
                    <option value="{{ $lead }}">{{ $lead }}</option>
                @endforeach
            </select>
            <select wire:model.live="sortBy"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="date">Sort: Date</option>
                <option value="alpha">Sort: A-Z</option>
                <option value="lead">Sort: Lead</option>
                <option value="status">Sort: Status</option>
            </select>
        </div>
        <!-- View Toggle -->
        <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-1">
            <button wire:click="setViewMode('grid')"
                class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}"
                title="Card View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            </button>
            <button wire:click="setViewMode('list')"
                class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}"
                title="List View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
            </button>
            <button wire:click="setViewMode('tree')"
                class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'tree' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}"
                title="Tree View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </button>
            <button wire:click="setViewMode('timeline')"
                class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'timeline' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}"
                title="Timeline View">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Hierarchy Filter (for grid/list view) --}}
    @if(in_array($viewMode, ['grid', 'list']))
        <div class="flex items-center gap-2 mb-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">Show:</span>
            <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-1">
                <button wire:click="$set('hierarchyFilter', 'roots')"
                    class="px-3 py-1 text-sm rounded {{ $hierarchyFilter === 'roots' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                    Parent Projects
                </button>
                <button wire:click="$set('hierarchyFilter', 'all')"
                    class="px-3 py-1 text-sm rounded {{ $hierarchyFilter === 'all' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                    All Projects
                </button>
            </div>
        </div>
    @endif

    {{-- Tree View Controls --}}
    @if($viewMode === 'tree')
        <div class="flex items-center gap-4 mb-4">
            <button wire:click="expandAll"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                Expand All
            </button>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <button wire:click="collapseAll"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                Collapse All
            </button>
        </div>
    @endif

    @if($viewMode === 'grid')
        <!-- Projects Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($projects as $project)
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                @if($project->children_count > 0)
                                    <button wire:click="toggleExpand({{ $project->id }})"
                                        class="p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        @if(in_array($project->id, $expanded))
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </button>
                                @endif
                                <a href="{{ route('projects.show', $project) }}" wire:navigate
                                    class="text-xs font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-500 dark:text-gray-400 hover:text-indigo-600">
                                    P-{{ str_pad($project->id, 3, '0', STR_PAD_LEFT) }}
                                </a>
                            </div>
                            @php
                                $statusColors = [
                                    'planning' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                    'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                    'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                    'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                ];
                                $scopeColors = [
                                    'US' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                    'Global' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                                    'Comms' => 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
                                ];
                            @endphp
                            <select wire:change="updateProjectStatus({{ $project->id }}, $event.target.value)"
                                class="text-xs rounded-full px-2 py-1 border-0 {{ $statusColors[$project->status] ?? $statusColors['active'] }} cursor-pointer focus:ring-2 focus:ring-indigo-500">
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ $project->status === $value ? 'selected' : '' }}>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <a href="{{ route('projects.show', $project) }}" wire:navigate>
                            <h3
                                class="text-lg font-semibold text-gray-900 dark:text-white mb-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                {{ $project->name }}
                            </h3>
                        </a>

                        {{-- Parent link (if child project) --}}
                        @if($project->parent)
                            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                <a href="{{ route('projects.show', $project->parent) }}" class="hover:text-indigo-600">
                                    {{ $project->parent->name }}
                                </a>
                            </div>
                        @endif

                        {{-- Project type badge (if not initiative) --}}
                        @if($project->project_type && $project->project_type !== 'initiative')
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $project->type_color }} bg-gray-100 dark:bg-gray-700 mb-2">
                                {{ ucfirst($project->project_type) }}
                            </span>
                        @endif

                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            @if($project->scope)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $scopeColors[$project->scope] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $project->scope }}
                                </span>
                            @endif
                            @if($project->lead_display)
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ $project->lead_display }}
                                </span>
                            @endif
                        </div>

                        @if($project->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
                                {{ Str::limit($project->description, 100) }}
                            </p>
                        @endif

                        @if($project->start_date || $project->target_end_date)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                📅 {{ $project->start_date?->format('M j') ?? '?' }} -
                                {{ $project->target_end_date?->format('M j, Y') ?? '?' }}
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $project->meetings_count }} meetings
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $project->decisions_count }} decisions
                            </div>
                            @if($project->children_count > 0)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                    </svg>
                                    {{ $project->children_count }} sub-projects
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Expanded Children --}}
                    @if(in_array($project->id, $expanded) && $project->children_count > 0)
                        <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-3">
                            <div class="space-y-2">
                                @foreach($project->children->take(5) as $child)
                                    <a href="{{ route('projects.show', $child) }}" wire:navigate
                                        class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $child->name }}</span>
                                            @if($child->project_type && $child->project_type !== 'initiative')
                                                <span class="text-xs text-gray-400">• {{ ucfirst($child->project_type) }}</span>
                                            @endif
                                        </div>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$child->status] ?? $statusColors['active'] }}">
                                            {{ ucfirst($child->status) }}
                                        </span>
                                    </a>
                                @endforeach
                                @if($project->children_count > 5)
                                    <a href="{{ route('projects.show', $project) }}#subprojects" wire:navigate
                                        class="block text-center text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 py-1">
                                        Show {{ $project->children_count - 5 }} more...
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p>No projects found.</p>
                    <a href="{{ route('projects.create') }}" wire:navigate
                        class="text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block">
                        Create your first project →
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($projects->hasPages())
            <div class="mt-6">
                {{ $projects->links() }}
            </div>
        @endif
    @elseif($viewMode === 'list')
        {{-- Expand/Collapse controls for list view --}}
        @if($hierarchyFilter === 'roots')
            <div class="flex items-center gap-4 mb-4">
                <button wire:click="expandAll"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                    Expand All
                </button>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <button wire:click="collapseAll"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                    Collapse All
                </button>
            </div>
        @endif

        <!-- Projects List/Table View -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ID</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Name</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Scope</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Lead</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Status</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Timeline</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Meetings</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $statusColors = [
                            'planning' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        ];
                        $scopeColors = [
                            'US' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                            'Global' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                            'Comms' => 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
                        ];
                    @endphp
                    @forelse($projects as $project)
                        {{-- Parent row --}}
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    @if($project->children_count > 0 && $hierarchyFilter === 'roots')
                                        <button wire:click="toggleExpand({{ $project->id }})"
                                            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            @if(in_array($project->id, $expanded))
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                        </button>
                                    @endif
                                    <a href="{{ route('projects.show', $project) }}" wire:navigate
                                        class="text-xs font-mono bg-gray-100 dark:bg-gray-600 px-2 py-0.5 rounded text-gray-500 dark:text-gray-400 hover:text-indigo-600">
                                        P-{{ str_pad($project->id, 3, '0', STR_PAD_LEFT) }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('projects.show', $project) }}" wire:navigate
                                    class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                    {{ $project->name }}
                                </a>
                                @if($project->children_count > 0 && $hierarchyFilter === 'roots')
                                    <span class="ml-2 text-xs text-gray-400">({{ $project->children_count }} sub-projects)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($project->scope)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $scopeColors[$project->scope] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $project->scope }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $project->lead_display ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <select wire:click.stop
                                    wire:change="updateProjectStatus({{ $project->id }}, $event.target.value)"
                                    class="text-xs rounded-full px-2 py-1 border-0 {{ $statusColors[$project->status] ?? $statusColors['active'] }} cursor-pointer focus:ring-2 focus:ring-indigo-500">
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" {{ $project->status === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                @if($project->start_date || $project->target_end_date)
                                    {{ $project->start_date?->format('M j') ?? '?' }} -
                                    {{ $project->target_end_date?->format('M j, Y') ?? '?' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $project->meetings_count }}
                            </td>
                        </tr>

                        {{-- Child rows (expanded) --}}
                        @if(in_array($project->id, $expanded) && $project->children_count > 0 && $hierarchyFilter === 'roots')
                            @foreach($project->children as $child)
                                <tr class="bg-gray-50/50 dark:bg-gray-900/30 hover:bg-gray-100 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <div class="pl-6">
                                            <a href="{{ route('projects.show', $child) }}" wire:navigate
                                                class="text-xs font-mono bg-gray-100 dark:bg-gray-600 px-2 py-0.5 rounded text-gray-500 dark:text-gray-400 hover:text-indigo-600">
                                                P-{{ str_pad($child->id, 3, '0', STR_PAD_LEFT) }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="pl-4 flex items-center gap-2">
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            <a href="{{ route('projects.show', $child) }}" wire:navigate
                                                class="text-sm text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $child->name }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        @if($child->scope)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $scopeColors[$child->scope] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $child->scope }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ $child->lead_display ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$child->status] ?? $statusColors['active'] }}">
                                            {{ ucfirst($child->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if($child->start_date || $child->target_end_date)
                                            {{ $child->start_date?->format('M j') ?? '?' }} -
                                            {{ $child->target_end_date?->format('M j, Y') ?? '?' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $child->meetings_count ?? 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                No projects found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($projects->hasPages())
            <div class="mt-6">
                {{ $projects->links() }}
            </div>
        @endif
    @elseif($viewMode === 'tree')
        <!-- Tree View -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            @php
                $statusColors = [
                    'planning' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                    'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                ];
            @endphp
            <div class="space-y-1">
                @forelse($treeProjects as $project)
                    @include('livewire.projects.partials.tree-node', ['project' => $project, 'depth' => 0, 'statusColors' => $statusColors])
                @empty
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <p>No projects found.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Timeline / Kanban View -->
        <div class="overflow-x-auto pb-4">
            <div class="flex gap-4 min-w-max">
                @php
                    $statusColors = [
                        'planning' => 'border-l-purple-500',
                        'active' => 'border-l-green-500',
                        'on_hold' => 'border-l-yellow-500',
                        'completed' => 'border-l-blue-500',
                        'archived' => 'border-l-gray-500',
                    ];
                    $scopeColors = [
                        'US' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                        'Global' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                        'Comms' => 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
                    ];
                @endphp
                @foreach($timelineData as $monthKey => $monthData)
                    <div class="w-72 flex-shrink-0">
                        <!-- Month Header -->
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-t-lg px-4 py-3 sticky top-0">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $monthData['name'] }}</h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $monthData['projects']->count() }}
                                projects</span>
                        </div>
                        <!-- Month Projects -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-b-lg p-3 min-h-[400px] space-y-3">
                            @forelse($monthData['projects'] as $project)
                                <a href="{{ route('projects.show', $project) }}" wire:navigate
                                    class="block bg-white dark:bg-gray-700 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow border-l-4 {{ $statusColors[$project->status] ?? 'border-l-gray-400' }}">
                                    <div class="flex items-center justify-between mb-1">
                                        <span
                                            class="text-xs font-mono text-gray-400">P-{{ str_pad($project->id, 3, '0', STR_PAD_LEFT) }}</span>
                                        @if($project->scope)
                                            <span
                                                class="text-xs px-1.5 py-0.5 rounded {{ $scopeColors[$project->scope] ?? 'bg-gray-100 text-gray-600' }}">{{ $project->scope }}</span>
                                        @endif
                                    </div>
                                    <h4 class="font-medium text-sm text-gray-900 dark:text-white mb-1 line-clamp-2">
                                        {{ $project->name }}
                                    </h4>
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        @if($project->lead_display)
                                            <span>{{ $project->lead_display }}</span>
                                        @endif
                                        @if($project->start_date && $project->target_end_date)
                                            <span>{{ $project->start_date->format('M j') }} -
                                                {{ $project->target_end_date->format('M j') }}</span>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="text-center py-8 text-gray-400 dark:text-gray-500 text-sm">
                                    No projects
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
</div>
</div>