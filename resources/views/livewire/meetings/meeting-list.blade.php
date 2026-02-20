<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Meetings</h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Track and manage all your stakeholder meetings and
                conversations</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-2">
            <button wire:click="openBulkImport"
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Bulk Import
            </button>
            <a href="{{ route('meetings.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-sm font-medium rounded-lg text-white hover:bg-indigo-700 transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Log Meeting
            </a>
        </div>
    </div>

    {{-- Toolbar Row 1: Scope Toggle + Filter Tabs + View Mode --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
            {{-- Scope Toggle --}}
            <div
                class="flex items-center gap-1 p-1 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <button wire:click="$set('scope', 'mine')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition whitespace-nowrap {{ $scope === 'mine' ? 'bg-indigo-600 text-white shadow' : 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-800' }}">
                    My Meetings
                </button>
                <button wire:click="$set('scope', 'all')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition whitespace-nowrap {{ $scope === 'all' ? 'bg-indigo-600 text-white shadow' : 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-800' }}">
                    All Team
                </button>
            </div>

            {{-- Filter Tabs --}}
            <div class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-x-auto">
                <button wire:click="$set('view', '')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition whitespace-nowrap {{ $view === '' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    All
                </button>
                <button wire:click="$set('view', 'upcoming')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition whitespace-nowrap {{ $view === 'upcoming' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    Upcoming
                    @if($stats['upcoming'] > 0)
                        <span
                            class="ml-1 px-1.5 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-full">{{ $stats['upcoming'] }}</span>
                    @endif
                </button>
                <button wire:click="$set('view', 'needs_notes')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition whitespace-nowrap {{ $view === 'needs_notes' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    Needs Notes
                    @if($stats['needs_notes'] > 0)
                        <span
                            class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 rounded-full">{{ $stats['needs_notes'] }}</span>
                    @endif
                </button>
                <button wire:click="$set('view', 'completed')"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition whitespace-nowrap {{ $view === 'completed' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                    Completed
                </button>
            </div>
        </div>

        {{-- View Mode Switcher --}}
        <div class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <button wire:click="$set('viewMode', 'sections')" title="Sections View"
                class="p-2 rounded-md transition {{ $viewMode === 'sections' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span class="sr-only">Sections</span>
            </button>
            <button wire:click="$set('viewMode', 'list')" title="List View"
                class="p-2 rounded-md transition {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="sr-only">List</span>
            </button>
            <button wire:click="$set('viewMode', 'cards')" title="Cards View"
                class="p-2 rounded-md transition {{ $viewMode === 'cards' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="sr-only">Cards</span>
            </button>
            <button wire:click="$set('viewMode', 'kanban')" title="Kanban by Month"
                class="p-2 rounded-md transition {{ $viewMode === 'kanban' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                <span class="sr-only">Kanban</span>
            </button>
        </div>
    </div>

    {{-- Filters Row --}}
    <div class="flex flex-wrap items-center gap-4 mb-6">
        {{-- Search --}}
        <div class="flex-1 min-w-[200px] max-w-md">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search meetings..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        {{-- Organization Filter --}}
        <select wire:model.live="organization"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">All Organizations</option>
            @foreach($organizations as $org)
                <option value="{{ $org->id }}">{{ $org->name }}</option>
            @endforeach
        </select>

        {{-- Issue Filter --}}
        <select wire:model.live="issue"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">All Issues</option>
            @foreach($issues as $i)
                <option value="{{ $i->id }}">{{ $i->name }}</option>
            @endforeach
        </select>

        {{-- Team Member Filter --}}
        <select wire:model.live="teamMember"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">All Staff</option>
            @foreach($teamMembers as $member)
                <option value="{{ $member->id }}">{{ $member->name }}</option>
            @endforeach
        </select>

        @if($search || $organization || $issue || $teamMember || $scope !== 'mine')
            <button wire:click="clearFilters" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                Clear filters
            </button>
        @endif
    </div>

    {{-- Content Views --}}
    @if($viewMode === 'sections')
        @include('livewire.meetings.views.sections')
    @elseif($viewMode === 'list')
        @include('livewire.meetings.views.list')
    @elseif($viewMode === 'cards')
        @include('livewire.meetings.views.cards')
    @elseif($viewMode === 'kanban')
        @include('livewire.meetings.views.kanban')
    @endif

    {{-- Bulk Import Modal --}}
    @if($showBulkImportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity"
                    wire:click="closeBulkImport"></div>
                <div
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bulk Import Meetings</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Paste meeting information from emails,
                            calendars, or any text source.</p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        @if($importError)
                            <div
                                class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
                                {{ $importError }}
                            </div>
                        @endif
                        @if($importSuccess)
                            <div
                                class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-300">
                                {{ $importSuccess }}
                            </div>
                        @endif

                        @if(empty($extractedMeetings))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Paste
                                    Text</label>
                                <textarea wire:model="bulkImportText" rows="8"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Paste meeting details here..."></textarea>
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Extracted Meetings ({{ count($extractedMeetings) }})
                                </label>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    @foreach($extractedMeetings as $index => $m)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $m['title'] ?? 'Untitled' }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $m['date'] ?? 'No date' }}
                                                </div>
                                            </div>
                                            <button wire:click="removeExtractedMeeting({{ $index }})"
                                                class="text-red-500 hover:text-red-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeBulkImport"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        @if(empty($extractedMeetings))
                            <button wire:click="extractMeetings" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="extractMeetings">Extract Meetings</span>
                                <span wire:loading wire:target="extractMeetings">Extracting...</span>
                            </button>
                        @else
                            <button wire:click="importMeetings" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="importMeetings">Import {{ count($extractedMeetings) }}
                                    Meeting(s)</span>
                                <span wire:loading wire:target="importMeetings">Importing...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>