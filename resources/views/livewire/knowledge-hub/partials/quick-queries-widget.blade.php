<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            Quick Queries
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Click to ask AI</p>
    </div>

    <div class="p-5">
        <div class="space-y-2">
            @foreach($quickQueries as $queryText)
                <button wire:click="runQuickQuery('{{ addslashes($queryText) }}')" wire:loading.attr="disabled"
                    class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-700 transition group disabled:opacity-50">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition flex-shrink-0" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        <span
                            class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                            "{{ $queryText }}"
                        </span>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Custom Query Prompt --}}
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Or type your own question in the search bar above
            </p>
        </div>
    </div>
</div>