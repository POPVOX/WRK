<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Product Feedback</h1>
                        <p class="mt-1 text-gray-500 dark:text-gray-400">Review and manage user feedback from beta testing</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Export Dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click="open = !open"
                                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium text-sm rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-all"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                @click.away="open = false"
                                x-transition
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                            >
                                <div class="py-1">
                                    <button
                                        wire:click="exportJson"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Export All (JSON)
                                        <span class="text-xs text-gray-500 ml-auto">Full data</span>
                                    </button>
                                    <button
                                        wire:click="exportCsv"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Export All (CSV)
                                        <span class="text-xs text-gray-500 ml-auto">Spreadsheet</span>
                                    </button>
                                    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                    <button
                                        wire:click="exportFilteredJson"
                                        @click="open = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                        Export Filtered (JSON)
                                        <span class="text-xs text-gray-500 ml-auto">Current view</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button
                            wire:click="generateProductInsights"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium text-sm rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-sm"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <span wire:loading.remove wire:target="generateProductInsights">AI Product Insights</span>
                            <span wire:loading wire:target="generateProductInsights">Analyzing...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                <nav class="-mb-px flex gap-6">
                    <button
                        wire:click="setTab('feedback')"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'feedback' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                    >
                        📋 All Feedback
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'feedback' ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">{{ $stats['total'] }}</span>
                    </button>
                    <button
                        wire:click="setTab('resolutions')"
                        class="py-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'resolutions' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}"
                    >
                        ✅ Resolution Tracker
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'resolutions' ? 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">{{ $resolutionStats['total_resolved'] }}</span>
                    </button>
                </nav>
            </div>

            @if($activeTab === 'feedback')
            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4">
                    <p class="text-sm text-yellow-600 dark:text-yellow-400">New</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['new'] }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4">
                    <p class="text-sm text-red-600 dark:text-red-400">Open Bugs</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stats['bugs'] }}</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
                    <p class="text-sm text-blue-600 dark:text-blue-400">Suggestions</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['suggestions'] }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-4">
                    <p class="text-sm text-green-600 dark:text-green-400">Resolved</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $resolutionStats['total_resolved'] }}</p>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search feedback..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                        >
                    </div>
                    <select
                        wire:model.live="filterStatus"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                    >
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select
                        wire:model.live="filterType"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                    >
                        <option value="">All Types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select
                        wire:model.live="filterPriority"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                    >
                        <option value="">All Priorities</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Feedback List --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Feedback</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Page</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($feedbackItems as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer" wire:click="viewFeedback({{ $item->id }})">
                                    <td class="px-4 py-3">
                                        @php
                                            $typeColors = [
                                                'bug' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                'suggestion' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                'compliment' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                'question' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                'general' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            ];
                                            $typeEmojis = ['bug' => '🐛', 'suggestion' => '💡', 'compliment' => '🎉', 'question' => '❓', 'general' => '💬'];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $typeColors[$item->feedback_type] ?? $typeColors['general'] }}">
                                            {{ $typeEmojis[$item->feedback_type] ?? '💬' }} {{ $types[$item->feedback_type] ?? 'General' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-900 dark:text-white line-clamp-2 max-w-md">{{ $item->message }}</p>
                                        @if($item->ai_tags)
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach(array_slice($item->ai_tags, 0, 3) as $tag)
                                                    <span class="px-1.5 py-0.5 text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-900 dark:text-white">{{ $item->user?->name ?? 'Anonymous' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px]" title="{{ $item->page_url }}">
                                            {{ $item->page_route ?: parse_url($item->page_url, PHP_URL_PATH) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = [
                                                'new' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                'reviewed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                'in_progress' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                                'addressed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                'dismissed' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                            ];
                                        @endphp
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$item->status] ?? '' }}">
                                                {{ $statuses[$item->status] ?? $item->status }}
                                            </span>
                                            @if($item->resolved_at)
                                                <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                                                    ✓ {{ $item->time_to_resolution }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->priority)
                                            @php
                                                $priorityColors = [
                                                    'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                    'high' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                                    'medium' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                    'low' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                                ];
                                            @endphp
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $priorityColors[$item->priority] ?? '' }}">
                                                {{ $priorities[$item->priority] ?? $item->priority }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->created_at->format('M j, g:i A') }}
                                    </td>
                                    <td class="px-4 py-3" wire:click.stop>
                                        <div class="flex items-center gap-2">
                                            @if($item->screenshot_path)
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            @endif
                                            @if(!$item->resolved_at)
                                                <button
                                                    wire:click="openResolveModal({{ $item->id }})"
                                                    class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 rounded hover:bg-green-200 dark:hover:bg-green-900/60 transition-colors"
                                                    title="Mark as resolved"
                                                >
                                                    ✓ Resolve
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">No feedback yet</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($feedbackItems->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                        {{ $feedbackItems->links() }}
                    </div>
                @endif
            </div>
            @else
            {{-- Resolution Tracker Tab --}}
            <div class="space-y-6">
                {{-- Resolution Stats --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-5 text-white">
                        <p class="text-sm font-medium text-green-100">Total Resolved</p>
                        <p class="text-3xl font-bold mt-1">{{ $resolutionStats['total_resolved'] }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Effort</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $resolutionStats['total_effort_hours'] }}h</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Avg Time to Fix</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $resolutionStats['avg_resolution_time_hours'] }}h</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Resolution Rate</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $stats['total'] > 0 ? round(($resolutionStats['total_resolved'] / $stats['total']) * 100) : 0 }}%
                        </p>
                    </div>
                </div>

                {{-- Resolution by Type --}}
                @if(count($resolutionStats['by_type']) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Resolutions by Type</h3>
                    <div class="flex flex-wrap gap-3">
                        @foreach($resolutionStats['by_type'] as $type => $count)
                            @php
                                $typeColors = [
                                    'fix' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    'enhancement' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                    'wontfix' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'duplicate' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    'workaround' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $typeColors[$type] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $resolutionTypes[$type] ?? ucfirst($type) }}
                                <span class="ml-2 px-2 py-0.5 rounded-full bg-white/50 dark:bg-black/20 text-xs">{{ $count }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Resolution Timeline --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Resolution History</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">All fixes and updates with time elapsed and effort</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Issue</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Resolution</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reported</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Resolved</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time to Fix</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Effort</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Resolved By</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($resolvedItems as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3">
                                            @php
                                                $typeColors = [
                                                    'fix' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                    'enhancement' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                    'wontfix' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                                    'duplicate' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                    'workaround' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                ];
                                            @endphp
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $typeColors[$item->resolution_type] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $resolutionTypes[$item->resolution_type] ?? 'Fixed' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="max-w-xs">
                                                <p class="text-sm text-gray-900 dark:text-white font-medium line-clamp-2">{{ Str::limit($item->message, 80) }}</p>
                                                @if($item->ai_summary)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1">{{ $item->ai_summary }}</p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($item->resolution_notes)
                                                <p class="text-sm text-gray-700 dark:text-gray-300 max-w-xs line-clamp-2">{{ $item->resolution_notes }}</p>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $item->created_at->format('M j, Y') }}
                                            <span class="text-xs block text-gray-400">{{ $item->created_at->format('g:i A') }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $item->resolved_at->format('M j, Y') }}
                                            <span class="text-xs block text-gray-400">{{ $item->resolved_at->format('g:i A') }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            {{ $item->time_to_resolution }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            {{ $item->effort_display ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $item->resolver?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <button
                                                wire:click="markUnresolved({{ $item->id }})"
                                                class="text-xs text-gray-400 hover:text-red-500 transition-colors"
                                                title="Revert resolution"
                                            >
                                                ↩
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-12 text-center">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="text-gray-500 dark:text-gray-400">No resolutions yet</p>
                                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Mark feedback items as resolved to track them here</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Resolve Modal --}}
    @if($showResolveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeResolveModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-6 pt-5 pb-4">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-green-100 dark:bg-green-900/40">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mark as Resolved</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resolution Type</label>
                                <select wire:model="resolutionType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    @foreach($resolutionTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resolution Notes</label>
                                <textarea
                                    wire:model="resolutionNotes"
                                    rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                    placeholder="Describe what was fixed or changed..."
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Effort (minutes)</label>
                                    <input
                                        type="number"
                                        wire:model="resolutionEffortMinutes"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                        placeholder="30"
                                        min="0"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Commit Hash (optional)</label>
                                    <input
                                        type="text"
                                        wire:model="resolutionCommit"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                        placeholder="abc123"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-3 flex justify-end gap-3">
                        <button
                            wire:click="closeResolveModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="markResolved"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                        >
                            Mark Resolved
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail Modal --}}
    @if($showDetailModal && $viewingFeedback)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetailModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Feedback Details</h3>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        {{-- User & Meta --}}
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $viewingFeedback->user?->name ?? 'Anonymous' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $viewingFeedback->created_at->format('M j, Y g:i A') }}</p>
                            </div>
                            <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                <p>{{ $viewingFeedback->browser }} {{ $viewingFeedback->browser_version }}</p>
                                <p>{{ $viewingFeedback->os }} • {{ $viewingFeedback->device_type }}</p>
                                <p>{{ $viewingFeedback->viewport_size }}</p>
                            </div>
                        </div>

                        {{-- Type & Category --}}
                        <div class="flex gap-2 mb-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $typeColors[$viewingFeedback->feedback_type] ?? '' }}">
                                {{ $types[$viewingFeedback->feedback_type] ?? $viewingFeedback->feedback_type }}
                            </span>
                            @if($viewingFeedback->category)
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ \App\Models\Feedback::CATEGORIES[$viewingFeedback->category] ?? $viewingFeedback->category }}
                                </span>
                            @endif
                        </div>

                        {{-- Message --}}
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</p>
                            <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ $viewingFeedback->message }}</p>
                        </div>

                        {{-- Page --}}
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Page</p>
                            <a href="{{ $viewingFeedback->page_url }}" target="_blank" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                                {{ $viewingFeedback->page_url }}
                            </a>
                        </div>

                        {{-- Screenshot --}}
                        @if($viewingFeedback->screenshot_path)
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Screenshot</p>
                                <a href="{{ Storage::url($viewingFeedback->screenshot_path) }}" target="_blank">
                                    <img src="{{ Storage::url($viewingFeedback->screenshot_path) }}" alt="Feedback screenshot" class="max-w-full rounded-lg border border-gray-200 dark:border-gray-700">
                                </a>
                            </div>
                        @endif

                        {{-- AI Analysis --}}
                        @if($viewingFeedback->ai_analyzed_at)
                            <div class="mb-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <span class="text-sm font-medium text-indigo-900 dark:text-indigo-300">AI Analysis</span>
                                </div>
                                @if($viewingFeedback->ai_summary)
                                    <p class="text-sm text-indigo-800 dark:text-indigo-300 mb-2"><strong>Summary:</strong> {{ $viewingFeedback->ai_summary }}</p>
                                @endif
                                @if($viewingFeedback->ai_recommendations)
                                    <p class="text-sm text-indigo-800 dark:text-indigo-300 mb-2"><strong>Recommendations:</strong> {{ $viewingFeedback->ai_recommendations }}</p>
                                @endif
                                @if($viewingFeedback->ai_tags)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($viewingFeedback->ai_tags as $tag)
                                            <span class="px-2 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-800 text-indigo-700 dark:text-indigo-300 rounded">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <button wire:click="reanalyze({{ $viewingFeedback->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-4">
                                🤖 Run AI Analysis
                            </button>
                        @endif

                        {{-- Admin Controls --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Admin Controls</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                                    <select wire:model="editStatus" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @foreach($statuses as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Priority</label>
                                    <select wire:model="editPriority" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">Not set</option>
                                        @foreach($priorities as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Assign To</label>
                                    <select wire:model="editAssignedTo" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">Unassigned</option>
                                        @foreach($staffMembers as $staff)
                                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Admin Notes</label>
                                    <textarea wire:model="editAdminNotes" rows="2" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Internal notes..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <button wire:click="deleteFeedback({{ $viewingFeedback->id }})" wire:confirm="Are you sure you want to delete this feedback?" class="text-sm text-red-600 dark:text-red-400 hover:underline">
                            Delete
                        </button>
                        <div class="flex gap-2">
                            <button wire:click="closeDetailModal" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                Cancel
                            </button>
                            <button wire:click="saveFeedbackChanges" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- AI Insights Modal --}}
    @if($showInsightsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeInsightsModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-indigo-600 to-purple-600">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            AI Product Insights
                        </h3>
                        <button wire:click="closeInsightsModal" class="text-white/80 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        @if($isGeneratingInsights)
                            <div class="text-center py-12">
                                <svg class="w-12 h-12 mx-auto text-indigo-500 animate-spin mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-600 dark:text-gray-400">Analyzing feedback patterns...</p>
                            </div>
                        @elseif(isset($aiInsights['error']))
                            <div class="text-center py-12">
                                <p class="text-red-600 dark:text-red-400">{{ $aiInsights['error'] }}</p>
                            </div>
                        @elseif(!empty($aiInsights))
                            <div class="space-y-6">
                                {{-- Executive Summary --}}
                                @if(isset($aiInsights['executive_summary']))
                                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                        <h4 class="font-semibold text-indigo-900 dark:text-indigo-300 mb-2">Executive Summary</h4>
                                        <p class="text-indigo-800 dark:text-indigo-300">{{ $aiInsights['executive_summary'] }}</p>
                                    </div>
                                @endif

                                {{-- Recommended Actions --}}
                                @if(isset($aiInsights['recommended_actions']) && count($aiInsights['recommended_actions']) > 0)
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <span class="w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm">✓</span>
                                            Recommended Actions
                                        </h4>
                                        <div class="space-y-3">
                                            @foreach($aiInsights['recommended_actions'] as $action)
                                                <div class="p-3 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                                    <div class="flex items-start gap-3">
                                                        <span class="flex-shrink-0 w-6 h-6 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center text-sm font-medium">{{ $action['priority'] ?? '?' }}</span>
                                                        <div class="flex-1">
                                                            <p class="font-medium text-gray-900 dark:text-white">{{ $action['action'] ?? '' }}</p>
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $action['rationale'] ?? '' }}</p>
                                                            <div class="flex gap-2 mt-2">
                                                                <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded">Effort: {{ $action['effort'] ?? 'unknown' }}</span>
                                                                <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded">Impact: {{ $action['impact'] ?? 'unknown' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Critical Issues --}}
                                @if(isset($aiInsights['critical_issues']) && count($aiInsights['critical_issues']) > 0)
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <span class="w-6 h-6 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center text-sm">!</span>
                                            Critical Issues
                                        </h4>
                                        <div class="space-y-2">
                                            @foreach($aiInsights['critical_issues'] as $issue)
                                                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                                    <p class="font-medium text-red-900 dark:text-red-300">{{ $issue['issue'] ?? '' }}</p>
                                                    <p class="text-sm text-red-700 dark:text-red-400 mt-1">{{ $issue['suggested_fix'] ?? '' }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Feature Requests --}}
                                @if(isset($aiInsights['feature_requests']) && count($aiInsights['feature_requests']) > 0)
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <span class="w-6 h-6 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-sm">💡</span>
                                            Top Feature Requests
                                        </h4>
                                        <div class="grid gap-2">
                                            @foreach($aiInsights['feature_requests'] as $feature)
                                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                                    <p class="font-medium text-blue-900 dark:text-blue-300">{{ $feature['feature'] ?? '' }}</p>
                                                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">{{ $feature['business_impact'] ?? '' }}</p>
                                                    <div class="flex gap-2 mt-2">
                                                        <span class="px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded">Demand: {{ $feature['demand_level'] ?? 'unknown' }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Positive Highlights --}}
                                @if(isset($aiInsights['positive_highlights']) && count($aiInsights['positive_highlights']) > 0)
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                            <span class="w-6 h-6 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm">🎉</span>
                                            What Users Love
                                        </h4>
                                        <ul class="space-y-1">
                                            @foreach($aiInsights['positive_highlights'] as $highlight)
                                                <li class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $highlight }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button wire:click="closeInsightsModal" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


