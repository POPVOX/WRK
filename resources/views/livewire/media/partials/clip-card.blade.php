<div
    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition bg-white dark:bg-gray-800">
    <div class="flex items-start gap-4">
        {{-- Article image --}}
        @if($clip->image_url)
            <a href="{{ $clip->url }}" target="_blank" class="flex-shrink-0 hidden sm:block">
                <img src="{{ $clip->image_url }}" alt="" class="w-28 h-20 object-cover rounded-md">
            </a>
        @endif

        <div class="flex-1 min-w-0">
            {{-- Date and Outlet --}}
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                <span>{{ $clip->published_at->format('M j, Y') }}</span>
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $clip->outlet_display_name }}</span>
                @if($clip->clip_type !== 'article')
                    <span
                        class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">{{ $clip->clip_type_label }}</span>
                @endif
            </div>

            {{-- Title with both read and edit options --}}
            <div class="flex items-start gap-2">
                <a href="{{ $clip->url }}" target="_blank" rel="noopener"
                    class="flex-1 font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition line-clamp-2">
                    {{ $clip->title }}
                    <svg class="inline w-4 h-4 ml-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>

            {{-- Summary/Quote --}}
            @if($clip->summary || $clip->quotes)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2 italic">
                    "{{ Str::limit($clip->quotes ?? $clip->summary, 150) }}"
                </p>
            @endif

            {{-- Meta --}}
            <div class="flex items-center flex-wrap gap-3 mt-3 text-sm">
                @if($clip->journalist_id || $clip->journalist_name)
                    <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        @if($clip->journalist_id)
                            <a href="{{ route('people.show', $clip->journalist_id) }}" wire:navigate
                                class="hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline">
                                {{ $clip->journalist_display_name }}
                            </a>
                        @else
                            @php
                                $reporters = preg_split('/\s*(?:,|&|\band\b)\s*/i', $clip->journalist_name ?? '');
                                $reporters = array_filter(array_map('trim', $reporters));
                            @endphp
                            @if(count($reporters) > 1)
                                <span class="flex flex-wrap gap-1">
                                    @foreach($reporters as $reporter)
                                        <span class="px-1.5 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700">{{ $reporter }}</span>
                                    @endforeach
                                </span>
                            @else
                                {{ $clip->journalist_display_name }}
                            @endif
                        @endif
                    </span>
                @endif

                @if($clip->staffMentioned->isNotEmpty())
                    <span class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        {{ $clip->staffMentioned->pluck('name')->join(', ') }} quoted
                    </span>
                @endif

                @if($clip->issues->isNotEmpty())
                    <div class="flex items-center gap-1">
                        @foreach($clip->issues->take(2) as $issue)
                            <span
                                class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">
                                {{ $issue->name }}
                            </span>
                        @endforeach
                        @if($clip->issues->count() > 2)
                            <span class="text-xs text-gray-400">+{{ $clip->issues->count() - 2 }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Sentiment Badge + Edit --}}
        <div class="flex flex-col items-end gap-2 flex-shrink-0">
            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full 
                @if($clip->sentiment === 'positive') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                @elseif($clip->sentiment === 'negative') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                @elseif($clip->sentiment === 'mixed') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 @endif">
                <span class="w-1.5 h-1.5 rounded-full 
                    @if($clip->sentiment === 'positive') bg-green-500
                    @elseif($clip->sentiment === 'negative') bg-red-500
                    @elseif($clip->sentiment === 'mixed') bg-amber-500
                    @else bg-gray-500 @endif"></span>
                {{ ucfirst($clip->sentiment) }}
            </span>

            {{-- Edit button - now more prominent --}}
            <button wire:click="editClip({{ $clip->id }})"
                class="flex items-center gap-1 px-2 py-1 text-xs text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 bg-gray-50 hover:bg-indigo-50 dark:bg-gray-700 dark:hover:bg-indigo-900/30 rounded transition" title="Edit clip in WRK">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </button>
        </div>
    </div>

    {{-- Actions for pending clips --}}
    @if($clip->status === 'pending_review')
        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
            <button wire:click="approveClip({{ $clip->id }})"
                class="text-sm text-green-600 hover:text-green-800 font-medium">
                ✓ Approve
            </button>
            <button wire:click="rejectClip({{ $clip->id }})" class="text-sm text-red-600 hover:text-red-800 font-medium">
                ✗ Reject
            </button>
            <button wire:click="openClipModal({{ $clip->id }})" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">
                Edit
            </button>
        </div>
    @endif
</div>