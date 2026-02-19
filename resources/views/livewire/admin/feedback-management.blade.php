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

            {{-- Clickable Stats Cards - Click to filter --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                {{-- Total (clears filter) --}}
                <button 
                    wire:click="setQuickFilter('')"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-left transition-all hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 {{ $quickFilter === '' ? 'ring-2 ring-indigo-500 dark:ring-indigo-400' : '' }}"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </button>
                {{-- New --}}
                <button 
                    wire:click="setQuickFilter('new')"
                    class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800 p-4 text-left transition-all hover:shadow-md hover:border-yellow-400 dark:hover:border-yellow-600 {{ $quickFilter === 'new' ? 'ring-2 ring-yellow-500 dark:ring-yellow-400' : '' }}"
                >
                    <p class="text-sm text-yellow-600 dark:text-yellow-400">New</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['new'] }}</p>
                </button>
                {{-- Open Bugs --}}
                <button 
                    wire:click="setQuickFilter('bugs')"
                    class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4 text-left transition-all hover:shadow-md hover:border-red-400 dark:hover:border-red-600 {{ $quickFilter === 'bugs' ? 'ring-2 ring-red-500 dark:ring-red-400' : '' }}"
                >
                    <p class="text-sm text-red-600 dark:text-red-400">Open Bugs</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stats['bugs'] }}</p>
                </button>
                {{-- Suggestions --}}
                <button 
                    wire:click="setQuickFilter('suggestions')"
                    class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4 text-left transition-all hover:shadow-md hover:border-blue-400 dark:hover:border-blue-600 {{ $quickFilter === 'suggestions' ? 'ring-2 ring-blue-500 dark:ring-blue-400' : '' }}"
                >
                    <p class="text-sm text-blue-600 dark:text-blue-400">Suggestions</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['suggestions'] }}</p>
                </button>
                {{-- Resolved --}}
                <button 
                    wire:click="setQuickFilter('resolved')"
                    class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-4 text-left transition-all hover:shadow-md hover:border-green-400 dark:hover:border-green-600 {{ $quickFilter === 'resolved' ? 'ring-2 ring-green-500 dark:ring-green-400' : '' }}"
                >
                    <p class="text-sm text-green-600 dark:text-green-400">Resolved</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $resolutionStats['total_resolved'] }}</p>
                </button>
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
                                            $typeStyles = [
                                                'bug' => [
                                                    'bg' => 'bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-900/20',
                                                    'text' => 'text-red-700 dark:text-red-300',
                                                    'border' => 'border border-red-200 dark:border-red-800/50',
                                                    'icon' => '🐛',
                                                    'dot' => 'bg-red-500',
                                                ],
                                                'suggestion' => [
                                                    'bg' => 'bg-gradient-to-r from-blue-50 to-sky-100 dark:from-blue-900/30 dark:to-sky-900/20',
                                                    'text' => 'text-blue-700 dark:text-blue-300',
                                                    'border' => 'border border-blue-200 dark:border-blue-800/50',
                                                    'icon' => '💡',
                                                    'dot' => 'bg-blue-500',
                                                ],
                                                'compliment' => [
                                                    'bg' => 'bg-gradient-to-r from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20',
                                                    'text' => 'text-green-700 dark:text-green-300',
                                                    'border' => 'border border-green-200 dark:border-green-800/50',
                                                    'icon' => '🎉',
                                                    'dot' => 'bg-green-500',
                                                ],
                                                'question' => [
                                                    'bg' => 'bg-gradient-to-r from-purple-50 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/20',
                                                    'text' => 'text-purple-700 dark:text-purple-300',
                                                    'border' => 'border border-purple-200 dark:border-purple-800/50',
                                                    'icon' => '❓',
                                                    'dot' => 'bg-purple-500',
                                                ],
                                                'general' => [
                                                    'bg' => 'bg-gradient-to-r from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800',
                                                    'text' => 'text-gray-600 dark:text-gray-300',
                                                    'border' => 'border border-gray-200 dark:border-gray-700',
                                                    'icon' => '💬',
                                                    'dot' => 'bg-gray-400',
                                                ],
                                            ];
                                            $style = $typeStyles[$item->feedback_type] ?? $typeStyles['general'];
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium shadow-sm {{ $style['bg'] }} {{ $style['text'] }} {{ $style['border'] }}">
                                            <span class="text-sm leading-none">{{ $style['icon'] }}</span>
                                            <span>{{ $types[$item->feedback_type] ?? 'General Feedback' }}</span>
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
                                            $statusStyles = [
                                                'new' => [
                                                    'bg' => 'bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/40 dark:to-amber-900/30',
                                                    'text' => 'text-yellow-800 dark:text-yellow-200',
                                                    'border' => 'border border-yellow-300 dark:border-yellow-700/50',
                                                    'dot' => 'bg-yellow-500 animate-pulse',
                                                ],
                                                'reviewed' => [
                                                    'bg' => 'bg-gradient-to-r from-blue-50 to-sky-100 dark:from-blue-900/40 dark:to-sky-900/30',
                                                    'text' => 'text-blue-700 dark:text-blue-200',
                                                    'border' => 'border border-blue-200 dark:border-blue-700/50',
                                                    'dot' => 'bg-blue-500',
                                                ],
                                                'in_progress' => [
                                                    'bg' => 'bg-gradient-to-r from-indigo-50 to-violet-100 dark:from-indigo-900/40 dark:to-violet-900/30',
                                                    'text' => 'text-indigo-700 dark:text-indigo-200',
                                                    'border' => 'border border-indigo-200 dark:border-indigo-700/50',
                                                    'dot' => 'bg-indigo-500 animate-pulse',
                                                ],
                                                'addressed' => [
                                                    'bg' => 'bg-gradient-to-r from-green-50 to-emerald-100 dark:from-green-900/40 dark:to-emerald-900/30',
                                                    'text' => 'text-green-700 dark:text-green-200',
                                                    'border' => 'border border-green-200 dark:border-green-700/50',
                                                    'dot' => 'bg-green-500',
                                                ],
                                                'dismissed' => [
                                                    'bg' => 'bg-gradient-to-r from-gray-100 to-slate-100 dark:from-gray-700 dark:to-slate-700',
                                                    'text' => 'text-gray-600 dark:text-gray-300',
                                                    'border' => 'border border-gray-200 dark:border-gray-600',
                                                    'dot' => 'bg-gray-400',
                                                ],
                                            ];
                                            $statusStyle = $statusStyles[$item->status] ?? $statusStyles['new'];
                                        @endphp
                                        <div class="flex flex-col gap-1.5">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium shadow-sm {{ $statusStyle['bg'] }} {{ $statusStyle['text'] }} {{ $statusStyle['border'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $statusStyle['dot'] }}"></span>
                                                {{ $statuses[$item->status] ?? $item->status }}
                                            </span>
                                            @if($item->resolved_at)
                                                <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1 font-medium">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $item->time_to_resolution }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->priority)
                                            @php
                                                $priorityStyles = [
                                                    'critical' => [
                                                        'bg' => 'bg-gradient-to-r from-red-500 to-rose-600',
                                                        'text' => 'text-white',
                                                        'icon' => '🔥',
                                                        'shadow' => 'shadow-sm shadow-red-200 dark:shadow-red-900/50',
                                                    ],
                                                    'high' => [
                                                        'bg' => 'bg-gradient-to-r from-orange-100 to-amber-100 dark:from-orange-900/40 dark:to-amber-900/30',
                                                        'text' => 'text-orange-700 dark:text-orange-200',
                                                        'icon' => '⚡',
                                                        'border' => 'border border-orange-200 dark:border-orange-700/50',
                                                    ],
                                                    'medium' => [
                                                        'bg' => 'bg-gradient-to-r from-sky-50 to-blue-100 dark:from-sky-900/30 dark:to-blue-900/30',
                                                        'text' => 'text-sky-700 dark:text-sky-200',
                                                        'icon' => '➡️',
                                                        'border' => 'border border-sky-200 dark:border-sky-700/50',
                                                    ],
                                                    'low' => [
                                                        'bg' => 'bg-gradient-to-r from-gray-50 to-slate-100 dark:from-gray-700 dark:to-slate-700',
                                                        'text' => 'text-gray-500 dark:text-gray-300',
                                                        'icon' => '↓',
                                                        'border' => 'border border-gray-200 dark:border-gray-600',
                                                    ],
                                                ];
                                                $priorityStyle = $priorityStyles[$item->priority] ?? $priorityStyles['medium'];
                                            @endphp
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $priorityStyle['bg'] }} {{ $priorityStyle['text'] }} {{ $priorityStyle['border'] ?? '' }} {{ $priorityStyle['shadow'] ?? 'shadow-sm' }}">
                                                <span class="text-xs leading-none">{{ $priorityStyle['icon'] }}</span>
                                                {{ $priorities[$item->priority] ?? $item->priority }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-300 dark:text-gray-600">—</span>
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

            {{-- Collapsible Resolution Stats Panel --}}
            @if($quickFilter === 'resolved' || $showResolutionStats)
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <button 
                    wire:click="toggleResolutionStats"
                    class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">Resolution Analytics</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 {{ $showResolutionStats ? 'rotate-180' : '' }} transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                @if($showResolutionStats)
                <div class="px-5 pb-5 border-t border-gray-200 dark:border-gray-700">
                    {{-- Resolution Stats Grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 text-white">
                            <p class="text-sm font-medium text-green-100">Total Resolved</p>
                            <p class="text-2xl font-bold mt-1">{{ $resolutionStats['total_resolved'] }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Effort</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $resolutionStats['total_effort_hours'] }}h</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Avg Time to Fix</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $resolutionStats['avg_resolution_time_hours'] }}h</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Resolution Rate</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                {{ $stats['total'] > 0 ? round(($resolutionStats['total_resolved'] / $stats['total']) * 100) : 0 }}%
                            </p>
                        </div>
                    </div>

                    {{-- Resolution by Type --}}
                    @if(count($resolutionStats['by_type']) > 0)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">By Resolution Type</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($resolutionStats['by_type'] as $type => $count)
                                @php
                                    $colors = [
                                        'fix' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                        'enhancement' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        'wontfix' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        'duplicate' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'workaround' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                    ];
                                    $icons = ['fix' => '✅', 'enhancement' => '✨', 'wontfix' => '🚫', 'duplicate' => '🔄', 'workaround' => '🔧'];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium {{ $colors[$type] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $icons[$type] ?? '📋' }} {{ $resolutionTypes[$type] ?? ucfirst($type) }}
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-white/30 dark:bg-black/20 font-semibold">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @else
            {{-- Toggle to show resolution stats --}}
            <div class="mt-4 text-center">
                <button 
                    wire:click="toggleResolutionStats"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                >
                    📊 Show resolution analytics
                </button>
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
                        <div class="flex flex-wrap gap-2 mb-4">
                            @php
                                $modalTypeStyles = [
                                    'bug' => 'bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/50',
                                    'suggestion' => 'bg-gradient-to-r from-blue-50 to-sky-100 dark:from-blue-900/30 dark:to-sky-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50',
                                    'compliment' => 'bg-gradient-to-r from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800/50',
                                    'question' => 'bg-gradient-to-r from-purple-50 to-violet-100 dark:from-purple-900/30 dark:to-violet-900/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50',
                                    'general' => 'bg-gradient-to-r from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700',
                                ];
                                $modalTypeEmojis = ['bug' => '🐛', 'suggestion' => '💡', 'compliment' => '🎉', 'question' => '❓', 'general' => '💬'];
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium shadow-sm {{ $modalTypeStyles[$viewingFeedback->feedback_type] ?? $modalTypeStyles['general'] }}">
                                <span class="text-sm leading-none">{{ $modalTypeEmojis[$viewingFeedback->feedback_type] ?? '💬' }}</span>
                                {{ $types[$viewingFeedback->feedback_type] ?? $viewingFeedback->feedback_type }}
                            </span>
                            @if($viewingFeedback->category)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium shadow-sm bg-gradient-to-r from-gray-50 to-slate-100 dark:from-gray-700 dark:to-slate-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
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

                        {{-- AI Code Fix Assistant --}}
                        @if($viewingFeedback->canGenerateAiFix())
                            <div class="mb-4 p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                    <span class="text-sm font-medium text-purple-900 dark:text-purple-300">AI Code Fix Assistant</span>
                                </div>

                                @if($viewingFeedback->latestFixProposal)
                                    @php $proposal = $viewingFeedback->latestFixProposal; @endphp
                                    <div class="flex items-center gap-3">
                                        @switch($proposal->status)
                                            @case('pending')
                                            @case('generating')
                                                <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span class="text-sm">Generating fix proposal...</span>
                                                </div>
                                                @break
                                            @case('ready')
                                                <button 
                                                    wire:click="viewFixProposal({{ $proposal->id }})"
                                                    class="px-3 py-1.5 text-sm font-medium bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View Proposed Fix
                                                </button>
                                                @break
                                            @case('approved')
                                            @case('deployed')
                                                <span class="flex items-center gap-2 text-green-700 dark:text-green-400 text-sm">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    Fix {{ $proposal->status === 'deployed' ? 'deployed' : 'approved' }}
                                                    @if($proposal->commit_sha)
                                                        <code class="text-xs bg-gray-200 dark:bg-gray-700 px-1 rounded">{{ Str::limit($proposal->commit_sha, 7, '') }}</code>
                                                    @endif
                                                </span>
                                                @break
                                            @case('rejected')
                                                <span class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                    Proposal rejected
                                                </span>
                                                <button 
                                                    wire:click="requestAiFix({{ $viewingFeedback->id }})"
                                                    class="text-xs text-purple-600 dark:text-purple-400 hover:underline"
                                                >
                                                    Request New Fix
                                                </button>
                                                @break
                                            @case('failed')
                                                <span class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    Generation failed
                                                </span>
                                                <button 
                                                    wire:click="requestAiFix({{ $viewingFeedback->id }})"
                                                    class="text-xs text-purple-600 dark:text-purple-400 hover:underline"
                                                >
                                                    Retry
                                                </button>
                                                @break
                                        @endswitch
                                    </div>
                                @else
                                    <p class="text-sm text-purple-700 dark:text-purple-400 mb-3">
                                        Request an AI-generated code fix for this {{ $viewingFeedback->feedback_type }}.
                                    </p>
                                    <button 
                                        wire:click="requestAiFix({{ $viewingFeedback->id }})"
                                        class="px-3 py-1.5 text-sm font-medium bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg transition-colors flex items-center gap-2"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Generate AI Fix
                                    </button>
                                @endif
                            </div>
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

    {{-- AI Fix Review Modal --}}
    @if($showFixModal && $viewingProposal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeFixModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-purple-600 to-pink-600">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            AI Fix Proposal
                        </h3>
                        <button wire:click="closeFixModal" class="text-white/80 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        {{-- Status Badge --}}
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium
                                @switch($viewingProposal->status)
                                    @case('pending')
                                    @case('generating')
                                        bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300
                                        @break
                                    @case('ready')
                                        bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300
                                        @break
                                    @case('approved')
                                    @case('deployed')
                                        bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300
                                        @break
                                    @default
                                        bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                @endswitch
                            ">
                                {{ $viewingProposal->status_label }}
                            </span>
                            @if($viewingProposal->estimated_complexity)
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    Complexity: {{ $viewingProposal->complexity_label }} ({{ $viewingProposal->estimated_complexity }}/10)
                                </span>
                            @endif
                        </div>

                        {{-- Problem Analysis --}}
                        @if($viewingProposal->problem_analysis)
                            <div class="mb-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                                <h4 class="font-semibold text-purple-900 dark:text-purple-300 mb-2">Problem Analysis</h4>
                                <p class="text-purple-800 dark:text-purple-300">{{ $viewingProposal->problem_analysis }}</p>
                            </div>
                        @endif

                        {{-- Affected Files --}}
                        @if($viewingProposal->affected_files && count($viewingProposal->affected_files) > 0)
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Affected Files ({{ count($viewingProposal->affected_files) }})</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($viewingProposal->affected_files as $file)
                                        <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded font-mono">
                                            {{ $file }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Implementation Notes --}}
                        @if($viewingProposal->implementation_notes)
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Suggested Approach</h4>
                                <p class="text-gray-700 dark:text-gray-300">{{ $viewingProposal->implementation_notes }}</p>
                            </div>
                        @endif

                        {{-- Diff Preview --}}
                        @if($viewingProposal->diff_preview)
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Code Changes</h4>
                                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto">
                                    <pre class="whitespace-pre-wrap">{!! $viewingProposal->formatted_diff !!}</pre>
                                </div>
                            </div>
                        @else
                            <div class="mb-4 p-8 bg-gray-100 dark:bg-gray-700 rounded-lg text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">No code changes generated yet</p>
                            </div>
                        @endif

                        {{-- Error Message --}}
                        @if($viewingProposal->error_message)
                            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <h4 class="font-semibold text-red-900 dark:text-red-300 mb-1">Error</h4>
                                <p class="text-red-700 dark:text-red-400 text-sm">{{ $viewingProposal->error_message }}</p>
                            </div>
                        @endif

                        {{-- Rejection Reason Input --}}
                        @if($viewingProposal->canDeploy())
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Rejection Reason (optional, only needed if rejecting)
                                </label>
                                <textarea
                                    wire:model="rejectionReason"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                    placeholder="Why are you rejecting this fix..."
                                ></textarea>
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/50">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Requested by {{ $viewingProposal->requester?->name ?? 'Unknown' }}
                            · {{ $viewingProposal->created_at->diffForHumans() }}
                        </div>
                        <div class="flex gap-3">
                            <button 
                                wire:click="closeFixModal" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors"
                            >
                                Close
                            </button>
                            @if($viewingProposal->canDeploy())
                                <button 
                                    wire:click="rejectFix({{ $viewingProposal->id }})"
                                    class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                >
                                    Reject
                                </button>
                                <button 
                                    wire:click="deployFix({{ $viewingProposal->id }})"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg transition-colors flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Approve & Deploy
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


