<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Knowledge Hub</h1>
        <p class="text-gray-600 dark:text-gray-400">Your organizational intelligence at a glance</p>
    </div>

    {{-- Search Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-6">
        <form wire:submit="search" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model="query" placeholder="Ask anything... or search across all content"
                    class="w-full pl-12 pr-4 py-3 text-lg border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit" wire:click="$set('useAI', false)"
                class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                Search
            </button>
            <button type="submit" wire:click="$set('useAI', true)"
                class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 transition inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                Ask AI
            </button>
        </form>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ route('meetings.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Meetings
            </a>
            <a href="{{ route('projects.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Projects
            </a>
            <a href="{{ route('organizations.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Organizations
            </a>
            <a href="{{ route('contacts.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                People
            </a>
            <a href="{{ route('grants.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 transition">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Funders
            </a>
        </div>
    </div>

    {{-- Search Results (when query is submitted) --}}
    @if($searchResults !== null || $aiAnswer !== null)
        <div class="mb-6">
            @include('livewire.knowledge-hub.partials.search-results')
        </div>
    @endif

    {{-- Dashboard Widgets --}}
    @if($searchResults === null && $aiAnswer === null)
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Column 1: Needs Attention --}}
            <div class="space-y-6">
                @include('livewire.knowledge-hub.partials.needs-attention-widget')
            </div>

            {{-- Column 2: This Week --}}
            <div class="space-y-6">
                @include('livewire.knowledge-hub.partials.this-week-widget')
            </div>

            {{-- Column 3: Active Relationships --}}
            <div class="space-y-6">
                @include('livewire.knowledge-hub.partials.active-relationships-widget')
            </div>
        </div>

        {{-- Bottom Row --}}
        <div class="grid gap-6 lg:grid-cols-2 mt-6">
            {{-- Recent Insights --}}
            @include('livewire.knowledge-hub.partials.recent-insights-widget')

            {{-- Quick Queries --}}
            @include('livewire.knowledge-hub.partials.quick-queries-widget')
        </div>
    @endif
</div>