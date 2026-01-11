<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ask about your work</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Search meetings, projects, contacts, and more</p>
        </div>

        {{-- Chat Container --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

            {{-- Conversation Area --}}
            <div class="min-h-[400px] max-h-[60vh] overflow-y-auto p-6" id="chat-container">
                @if($aiAnswer || ($searchResults && count($searchResults) > 0))
                    {{-- User's Question --}}
                    <div class="flex justify-end mb-6">
                        <div class="bg-indigo-600 text-white px-4 py-3 rounded-2xl rounded-br-md max-w-[85%]">
                            <p>{{ $query }}</p>
                        </div>
                    </div>

                    {{-- AI Answer --}}
                    @if($aiAnswer)
                        <div class="flex gap-3 mb-6">
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-2xl rounded-tl-md prose prose-sm dark:prose-invert max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($aiAnswer) !!}
                                </div>

                                {{-- Sources --}}
                                @if($searchResults && count($searchResults) > 0)
                                    <div class="mt-4">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Related sources:</p>
                                        <div class="space-y-2">
                                            @foreach(collect($searchResults)->take(3) as $result)
                                                <a href="{{ $result['url'] }}" wire:navigate
                                                    class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-sm">
                                                    @php
                                                        $icons = [
                                                            'meeting' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
                                                            'project' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />',
                                                            'person' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
                                                            'organization' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
                                                            'commitment' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                                                            'decision' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
                                                        ];
                                                    @endphp
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        {!! $icons[$result['source_type']] ?? $icons['meeting'] !!}
                                                    </svg>
                                                    <span class="text-gray-700 dark:text-gray-300 truncate">{{ $result['title'] }}</span>
                                                    <span class="text-gray-400 dark:text-gray-500 text-xs flex-shrink-0">{{ ucfirst($result['source_type']) }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @elseif($searchResults && count($searchResults) > 0)
                        {{-- Search Results Only (no AI) --}}
                        <div class="space-y-3">
                            @foreach($searchResults as $result)
                                <a href="{{ $result['url'] }}" wire:navigate
                                    class="block p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <div class="flex items-start gap-3">
                                        @php
                                            $icons = [
                                                'meeting' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
                                                'project' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />',
                                                'person' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
                                                'organization' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
                                                'commitment' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                                                'decision' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
                                            ];
                                            $colors = [
                                                'meeting' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400',
                                                'project' => 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400',
                                                'person' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400',
                                                'organization' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400',
                                                'commitment' => 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400',
                                                'decision' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400',
                                            ];
                                        @endphp
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 {{ $colors[$result['source_type']] ?? 'bg-gray-100 text-gray-600' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                {!! $icons[$result['source_type']] ?? $icons['meeting'] !!}
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $result['title'] }}</p>
                                            @if($result['snippet'])
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $result['snippet'] }}</p>
                                            @endif
                                            <div class="flex items-center gap-2 mt-2 text-xs text-gray-400">
                                                <span class="capitalize">{{ $result['source_type'] }}</span>
                                                @if(!empty($result['date']))
                                                    <span>•</span>
                                                    <span>{{ $result['date'] instanceof \Carbon\Carbon ? $result['date']->format('M j, Y') : $result['date'] }}</span>
                                                @endif
                                                @if(!empty($result['organization']))
                                                    <span>•</span>
                                                    <span>{{ $result['organization'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Clear conversation --}}
                    <div class="text-center mt-6">
                        <button wire:click="clearSearch"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                            Clear conversation
                        </button>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="flex flex-col items-center justify-center h-full py-12 text-center">
                        <p class="text-gray-400 dark:text-gray-500 mb-6">Try asking something like:</p>
                        <div class="flex flex-wrap gap-2 justify-center max-w-lg">
                            @foreach($quickQueries as $suggestion)
                                <button wire:click="runQuickQuery('{{ addslashes($suggestion) }}')"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Input Area --}}
            <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
                <form wire:submit="search" class="flex gap-3">
                    <div class="flex-1 relative">
                        <input type="text" wire:model="query"
                            placeholder="Ask anything about meetings, projects, people..."
                            class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" wire:click="$set('useAI', false)"
                            class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                            title="Search">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                        <button type="submit" wire:click="$set('useAI', true)"
                            class="px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium hover:from-indigo-700 hover:to-purple-700 transition flex items-center gap-2"
                            title="Ask AI">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            <span class="hidden sm:inline">Ask AI</span>
                        </button>
                    </div>
                </form>

                {{-- Loading indicator --}}
                <div wire:loading wire:target="search, runQuickQuery" class="flex items-center justify-center gap-2 mt-4 text-sm text-gray-500">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Searching...</span>
                </div>
            </div>
        </div>

        {{-- Quick navigation hint --}}
        <div class="text-center mt-6">
            <p class="text-sm text-gray-400 dark:text-gray-500">
                Or browse directly:
                <a href="{{ route('meetings.index') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">Meetings</a> •
                <a href="{{ route('projects.index') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">Projects</a> •
                <a href="{{ route('contacts.index') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">People</a> •
                <a href="{{ route('organizations.index') }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">Organizations</a>
            </p>
        </div>
    </div>
</div>

@script
<script>
    // Auto-scroll chat to bottom when new messages arrive
    $wire.on('searchComplete', () => {
        const container = document.getElementById('chat-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
@endscript
