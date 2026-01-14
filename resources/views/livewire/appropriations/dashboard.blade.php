<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Appropriations Tracker
            </h2>
            <div class="flex gap-2">
                <button wire:click="exportToCSV"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white dark:bg-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
                <a href="{{ route('appropriations.upload') }}" wire:navigate
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Report
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_requirements'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-pointer hover:ring-2 hover:ring-blue-500" wire:click="$set('filterStatus', 'pending')">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pending</div>
                    <div class="mt-1 text-2xl font-bold text-blue-600">{{ $stats['pending'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-pointer hover:ring-2 hover:ring-yellow-500" wire:click="$set('filterStatus', 'in_progress')">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">In Progress</div>
                    <div class="mt-1 text-2xl font-bold text-yellow-600">{{ $stats['in_progress'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-pointer hover:ring-2 hover:ring-red-500" wire:click="$set('filterStatus', 'overdue')">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Overdue</div>
                    <div class="mt-1 text-2xl font-bold text-red-600">{{ $stats['overdue'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-pointer hover:ring-2 hover:ring-green-500" wire:click="$set('filterStatus', 'submitted')">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Submitted</div>
                    <div class="mt-1 text-2xl font-bold text-green-600">{{ $stats['submitted'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 cursor-pointer hover:ring-2 hover:ring-orange-500" wire:click="$set('filterStatus', 'upcoming')">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due in 7 Days</div>
                    <div class="mt-1 text-2xl font-bold text-orange-600">{{ $stats['upcoming_7_days'] }}</div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fiscal Year</label>
                        <select wire:model.live="selectedFiscalYear" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($fiscalYears as $fy)
                                <option value="{{ $fy }}">{{ $fy }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select wire:model.live="filterStatus" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="overdue">Overdue</option>
                            <option value="upcoming">Due Soon</option>
                            <option value="submitted">Submitted</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Agency</label>
                        <select wire:model.live="filterAgency" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Agencies</option>
                            @foreach($agencies as $agency)
                                <option value="{{ $agency }}">{{ $agency }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select wire:model.live="filterCategory" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Categories</option>
                            <option value="new">New</option>
                            <option value="prior_year">Prior Year</option>
                            <option value="ongoing">Ongoing</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="searchQuery"
                                type="text"
                                placeholder="Search requirements..."
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                            @if($searchQuery || $filterStatus !== 'all' || $filterAgency || $filterCategory)
                                <button wire:click="clearFilters" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Source Reports (Collapsible) --}}
            @if($reports->count() > 0)
                <details class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                    <summary class="px-4 py-3 cursor-pointer font-medium text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        📜 Source Reports ({{ $reports->count() }})
                    </summary>
                    <div class="px-4 pb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                            @foreach($reports as $report)
                                <div class="border dark:border-gray-700 rounded-lg p-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $report->display_name }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $report->title }}</div>
                                    <div class="flex gap-3 mt-2 text-xs">
                                        <span class="text-blue-600">{{ $report->requirements_count }} total</span>
                                        @if($report->pending_count > 0)
                                            <span class="text-yellow-600">{{ $report->pending_count }} pending</span>
                                        @endif
                                        @if($report->overdue_count > 0)
                                            <span class="text-red-600">{{ $report->overdue_count }} overdue</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endif

            {{-- View Mode Toggle --}}
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Showing {{ $requirements->count() }} requirements
                </div>
                <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button wire:click="setViewMode('grid')"
                        class="px-3 py-1 text-sm rounded {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button wire:click="setViewMode('list')"
                        class="px-3 py-1 text-sm rounded {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Requirements Display --}}
            @if($viewMode === 'grid')
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($requirements as $requirement)
                        <livewire:appropriations.requirement-card
                            :requirement="$requirement"
                            :key="'req-'.$requirement->id" />
                    @empty
                        <div class="col-span-full text-center py-12">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">No requirements found matching your filters.</p>
                            <a href="{{ route('appropriations.upload') }}" wire:navigate class="mt-4 inline-flex items-center text-indigo-600 hover:text-indigo-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add a report to get started
                            </a>
                        </div>
                    @endforelse
                </div>
            @else
                {{-- List View --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Agency</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Due Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Category</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($requirements as $requirement)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700" wire:key="list-{{ $requirement->id }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ Str::limit($requirement->report_title, 50) }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $requirement->legislativeReport?->display_name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $requirement->responsible_agency }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($requirement->due_date)
                                            <span class="{{ $requirement->isOverdue() ? 'text-red-600 font-semibold' : ($requirement->days_until_due !== null && $requirement->days_until_due < 14 ? 'text-orange-600' : 'text-gray-600 dark:text-gray-400') }}">
                                                {{ $requirement->due_date->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">TBD</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            @if($requirement->effective_status === 'submitted') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                            @elseif($requirement->effective_status === 'overdue') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                            @elseif($requirement->effective_status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $requirement->effective_status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $requirement->category)) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                        No requirements found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

