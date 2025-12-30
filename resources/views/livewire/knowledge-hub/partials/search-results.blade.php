<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                @if($aiAnswer)
                    AI Answer
                @else
                    Search Results
                @endif
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Query: "{{ $query }}"
            </p>
        </div>
        <button wire:click="clearSearch"
            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
            ✕ Clear
        </button>
    </div>

    <div class="p-5">
        {{-- Loading State --}}
        @if($searching)
            <div class="flex items-center justify-center py-12">
                <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            fill="none"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    {{ $useAI ? 'Thinking...' : 'Searching...' }}
                </div>
            </div>
        @else
            {{-- AI Answer --}}
            @if($aiAnswer)
                <div class="prose prose-sm dark:prose-invert max-w-none mb-6">
                    {!! \Str::markdown($aiAnswer) !!}
                </div>

                @if($searchResults && count($searchResults) > 0)
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Sources</h3>
                        <div class="space-y-2">
                            @foreach(array_slice($searchResults, 0, 5) as $result)
                                @include('livewire.knowledge-hub.partials.search-result-item', ['result' => $result, 'compact' => true])
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                {{-- Standard Search Results --}}
                @if($searchResults && count($searchResults) > 0)
                    <div class="space-y-3">
                        @foreach($searchResults as $result)
                            @include('livewire.knowledge-hub.partials.search-result-item', ['result' => $result, 'compact' => false])
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No results found for "{{ $query }}"</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try different keywords or ask AI for help</p>
                    </div>
                @endif
            @endif
        @endif
    </div>
</div>