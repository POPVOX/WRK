<div
    class="p-4 border rounded-lg transition
    {{ ($highlight ?? false) ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }}">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            {{-- Header --}}
            <div class="flex items-center gap-2 mb-1">
                @if($inquiry->urgency === 'breaking')
                    <span class="px-1.5 py-0.5 text-xs font-bold bg-red-600 text-white rounded">BREAKING</span>
                @elseif($inquiry->urgency === 'urgent')
                    <span class="px-1.5 py-0.5 text-xs font-bold bg-amber-500 text-white rounded">URGENT</span>
                @endif
                @if($inquiry->is_overdue)
                    <span
                        class="px-1.5 py-0.5 text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded">OVERDUE</span>
                @elseif($inquiry->deadline && $inquiry->deadline->isToday())
                    <span
                        class="px-1.5 py-0.5 text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded">DUE
                        TODAY</span>
                @endif
                @if($inquiry->ai_insights)
                    <span
                        class="px-1.5 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" />
                        </svg>
                        AI Analyzed
                    </span>
                @endif
            </div>

            {{-- Subject --}}
            <h4 wire:click="openInquiryModal({{ $inquiry->id }})"
                class="font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer">
                {{ $inquiry->subject }}
            </h4>

            {{-- Description --}}
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                {{ Str::limit($inquiry->description, 150) }}
            </p>

            {{-- AI Insights Summary --}}
            @if($inquiry->ai_insights && !empty($inquiry->ai_insights['summary']))
                <div
                    class="mt-2 p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                    <p class="text-xs text-purple-800 dark:text-purple-300">
                        <span class="font-semibold">AI Summary:</span> {{ $inquiry->ai_insights['summary'] }}
                    </p>
                    @if(!empty($inquiry->ai_insights['suggested_angle']))
                        <p class="text-xs text-purple-700 dark:text-purple-400 mt-1">
                            <span class="font-medium">Suggested angle:</span>
                            {{ Str::limit($inquiry->ai_insights['suggested_angle'], 100) }}
                        </p>
                    @endif
                </div>
            @endif

            {{-- Meta --}}
            <div class="flex items-center flex-wrap gap-3 mt-3 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    {{ $inquiry->outlet_display_name }}
                </span>
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ $inquiry->journalist_display_name }}
                    @if($inquiry->journalist_id)
                        <span
                            class="px-1 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded">linked</span>
                    @endif
                </span>
                <span>{{ $inquiry->received_at->diffForHumans() }}</span>
                @if($inquiry->deadline)
                    <span
                        class="flex items-center gap-1 {{ $inquiry->is_overdue ? 'text-red-600 dark:text-red-400' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Deadline: {{ $inquiry->deadline->format('M j, g:ia') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Status & Actions --}}
        <div class="flex flex-col items-end gap-2">
            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                @if($inquiry->status === 'new') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                @elseif($inquiry->status === 'responding') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                @elseif($inquiry->status === 'completed') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 @endif">
                {{ $inquiry->status_label }}
            </span>

            @if($inquiry->handledBy)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $inquiry->handledBy->name }}
                </span>
            @elseif($inquiry->status === 'new')
                <button wire:click="assignInquiryToMe({{ $inquiry->id }})"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                    Take this →
                </button>
            @endif

            {{-- AI Analyze Button - Always show --}}
            <button wire:click="analyzeInquiryWithAI({{ $inquiry->id }})" wire:loading.attr="disabled"
                wire:loading.class="opacity-50" wire:target="analyzeInquiryWithAI({{ $inquiry->id }})"
                class="mt-1 px-2 py-1 text-xs font-medium text-purple-700 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:hover:bg-purple-900/50 rounded-lg flex items-center gap-1 transition">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" />
                </svg>
                <span wire:loading.remove wire:target="analyzeInquiryWithAI({{ $inquiry->id }})">
                    {{ $inquiry->ai_insights ? 'Re-analyze' : 'AI Analyze' }}
                </span>
                <span wire:loading wire:target="analyzeInquiryWithAI({{ $inquiry->id }})">Analyzing...</span>
            </button>
        </div>
    </div>
</div>