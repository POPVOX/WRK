<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ============================================== --}}
            {{-- HEADER - Personal Greeting --}}
            {{-- ============================================== --}}
            <div class="mb-8">
                @if($aiWarning || $calendarWarning)
                    <div class="space-y-2 mb-4">
                        @if($aiWarning)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
                                {{ $aiWarning }}
                            </div>
                        @endif
                        @if($calendarWarning)
                            <div class="rounded-lg border border-blue-200 bg-blue-50 text-blue-800 px-4 py-3 text-sm">
                                {{ $calendarWarning }}
                            </div>
                        @endif
                    </div>
                @endif
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $greeting }}, {{ $firstName }}
                        </h1>
                        <p class="mt-1 text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span>Here's what's on your plate today</span>
                            <span class="text-gray-400 dark:text-gray-500">{{ now()->format('l, F j, Y') }}</span>
                            <span class="text-gray-300 dark:text-gray-600">•</span>
                            <livewire:components.timezone-location />
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($isCalendarConnected)
                            <button wire:click="syncCalendar" wire:loading.attr="disabled"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm"
                                title="Sync calendar">
                                <svg class="w-4 h-4 {{ $isSyncing ? 'animate-spin' : '' }}" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span class="ml-2 hidden sm:inline" wire:loading.remove wire:target="syncCalendar">
                                    {{ $lastSyncAt ? 'Synced ' . $lastSyncAt : 'Sync' }}
                                </span>
                                <span class="ml-2 hidden sm:inline" wire:loading
                                    wire:target="syncCalendar">Syncing...</span>
                            </button>
                        @endif
                        <a href="{{ route('meetings.create') }}" wire:navigate
                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Log Meeting
                        </a>
                    </div>
                </div>
            </div>

            {{-- ============================================== --}}
            {{-- MY DAY - Stats Row --}}
            {{-- ============================================== --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- Meetings Today --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Meetings Today</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                                {{ $stats['meetings_today'] }}</p>
                        </div>
                        <div
                            class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    @if($stats['meetings_tomorrow'] > 0)
                        <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
                            {{ $stats['meetings_tomorrow'] }} tomorrow
                        </p>
                    @endif
                </div>

                {{-- Tasks Due --}}
                <div
                    class="{{ $stats['actions_overdue'] > 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700' }} rounded-xl border p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium {{ $stats['actions_overdue'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                Tasks Due
                            </p>
                            <p
                                class="text-3xl font-bold {{ $stats['actions_overdue'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-900 dark:text-white' }} mt-1">
                                {{ $stats['actions_due_today'] }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 {{ $stats['actions_overdue'] > 0 ? 'bg-red-100 dark:bg-red-900/40' : 'bg-amber-50 dark:bg-amber-900/30' }} rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 {{ $stats['actions_overdue'] > 0 ? 'text-red-500' : 'text-amber-500' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    @if($stats['actions_overdue'] > 0)
                        <p class="text-xs mt-2 text-red-600 dark:text-red-400">
                            {{ $stats['actions_overdue'] }} overdue!
                        </p>
                    @elseif($stats['actions_this_week'] > 0)
                        <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
                            {{ $stats['actions_this_week'] }} this week
                        </p>
                    @endif
                </div>

                {{-- Active Projects --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">My Projects</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                                {{ $stats['active_projects'] }}</p>
                        </div>
                        <div
                            class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">
                        {{ $stats['project_actions_pending'] }} tasks pending
                    </p>
                </div>

                {{-- Reports Due (Admin) OR Tasks This Week --}}
                @if(auth()->user()->isAdmin())
                    <div
                        class="{{ $stats['reports_overdue'] > 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : ($stats['reports_due_soon'] > 0 ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700') }} rounded-xl border p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-sm font-medium {{ $stats['reports_overdue'] > 0 ? 'text-red-600 dark:text-red-400' : ($stats['reports_due_soon'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400') }}">
                                    Reports Due
                                </p>
                                <p
                                    class="text-3xl font-bold {{ $stats['reports_overdue'] > 0 ? 'text-red-700 dark:text-red-300' : ($stats['reports_due_soon'] > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-900 dark:text-white') }} mt-1">
                                    {{ $stats['reports_due_soon'] }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 {{ $stats['reports_overdue'] > 0 ? 'bg-red-100 dark:bg-red-900/40' : ($stats['reports_due_soon'] > 0 ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-gray-50 dark:bg-gray-700') }} rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 {{ $stats['reports_overdue'] > 0 ? 'text-red-500' : ($stats['reports_due_soon'] > 0 ? 'text-amber-500' : 'text-gray-400') }}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        @if($stats['reports_overdue'] > 0)
                            <p class="text-xs mt-2 text-red-600 dark:text-red-400">{{ $stats['reports_overdue'] }} overdue!</p>
                        @else
                            <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">upcoming</p>
                        @endif
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">This Week</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                                    {{ $stats['actions_this_week'] }}</p>
                            </div>
                            <div
                                class="w-12 h-12 bg-green-50 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs mt-2 text-gray-500 dark:text-gray-400">tasks due</p>
                    </div>
                @endif
            </div>

            {{-- ============================================== --}}
            {{-- MAIN CONTENT - Two Column Layout --}}
            {{-- ============================================== --}}
            <div class="grid lg:grid-cols-2 gap-6 mb-6">

                {{-- Column 1: Today's Schedule --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Today's Schedule
                        </h2>
                        <a href="{{ route('meetings.index') }}" wire:navigate
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                            View calendar →
                        </a>
                    </div>

                    @if($todaysMeetings->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($todaysMeetings as $meeting)
                                <a href="{{ route('meetings.show', $meeting) }}" wire:navigate
                                    class="flex items-start gap-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                                    {{-- Time --}}
                                    <div class="flex-shrink-0 w-16 text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $meeting->meeting_date?->format('g:i A') ?: 'TBD' }}
                                        </p>
                                    </div>

                                    {{-- Details --}}
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 truncate">
                                            {{ $meeting->title ?: ($meeting->organizations->pluck('name')->first() ?: 'Meeting') }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            @if($meeting->location)
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ $meeting->location }}
                                                </span>
                                            @endif
                                            @if($meeting->organizations->count() > 0)
                                                <span class="text-gray-400">•</span>
                                                <span>{{ $meeting->organizations->pluck('name')->first() }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Status Indicators --}}
                                    @if(!$meeting->hasNotes() && $meeting->isPast())
                                        <span
                                            class="flex-shrink-0 px-2 py-1 text-xs bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded-full">
                                            Needs notes
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        {{-- Tomorrow Preview --}}
                        @if($tomorrowMeetingsCount > 0)
                            <div
                                class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                                Tomorrow: {{ $tomorrowMeetingsCount }} meeting{{ $tomorrowMeetingsCount !== 1 ? 's' : '' }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">No meetings today</p>
                            @if($tomorrowMeetingsCount > 0)
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                                    {{ $tomorrowMeetingsCount }} meeting{{ $tomorrowMeetingsCount !== 1 ? 's' : '' }} tomorrow
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Column 2: My Tasks --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            My Tasks
                        </h2>
                        <div class="flex items-center gap-2">
                            <button wire:click="toggleAiSuggestions" 
                                class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800 flex items-center gap-1"
                                title="AI Task Suggestions">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                AI
                            </button>
                            <button wire:click="toggleAddTask" 
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add
                            </button>
                        </div>
                    </div>

                    {{-- Add Task Form --}}
                    @if($showAddTask)
                        <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                            <form wire:submit="createTask" class="space-y-3">
                                <div>
                                    <input type="text" wire:model="newTaskTitle" placeholder="What needs to be done?" 
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        autofocus>
                                    @error('newTaskTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="date" wire:model="newTaskDueDate" 
                                        class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                    <select wire:model="newTaskPriority" 
                                        class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                        <option value="low">Low Priority</option>
                                        <option value="medium">Medium Priority</option>
                                        <option value="high">High Priority</option>
                                    </select>
                                </div>
                                @if($availableProjects->isNotEmpty())
                                    <select wire:model="newTaskProjectId" 
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                        <option value="">No project (standalone task)</option>
                                        @foreach($availableProjects as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <textarea wire:model="newTaskDescription" placeholder="Add notes (optional)..." rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"></textarea>
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="toggleAddTask" 
                                        class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                        Add Task
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- AI Suggestions --}}
                    @if($showAiSuggestions)
                        <div class="mb-4 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-medium text-purple-900 dark:text-purple-300 flex items-center gap-2">
                                    <span>✨</span> AI Suggested Tasks
                                </h3>
                                <button wire:click="loadAiSuggestions" 
                                    class="text-xs text-purple-600 dark:text-purple-400 hover:text-purple-800 flex items-center gap-1"
                                    wire:loading.attr="disabled">
                                    <svg class="w-3 h-3 {{ $isLoadingSuggestions ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Refresh
                                </button>
                            </div>

                            @if($isLoadingSuggestions)
                                <div class="flex items-center justify-center py-4">
                                    <svg class="animate-spin h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="ml-2 text-sm text-purple-600 dark:text-purple-400">Analyzing your work context...</span>
                                </div>
                            @elseif(empty($aiSuggestedTasks))
                                <p class="text-sm text-purple-600 dark:text-purple-400 text-center py-2">No suggestions available. Try refreshing!</p>
                            @else
                                <div class="space-y-2">
                                    @foreach($aiSuggestedTasks as $index => $suggestion)
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-purple-100 dark:border-purple-800">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $suggestion['title'] }}</p>
                                                    @if(!empty($suggestion['reason']))
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $suggestion['reason'] }}</p>
                                                    @endif
                                                    <div class="flex items-center gap-2 mt-1">
                                                        @if(!empty($suggestion['priority']))
                                                            <span class="px-1.5 py-0.5 text-xs rounded {{ $suggestion['priority'] === 'high' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : ($suggestion['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                                                                {{ ucfirst($suggestion['priority']) }}
                                                            </span>
                                                        @endif
                                                        @if(!empty($suggestion['suggested_due_date']))
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                Due: {{ \Carbon\Carbon::parse($suggestion['suggested_due_date'])->format('M j') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <button wire:click="addSuggestedTask({{ $index }})" 
                                                        class="p-1 text-green-600 hover:text-green-800 dark:text-green-400" title="Add task">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                    </button>
                                                    <button wire:click="dismissSuggestion({{ $index }})" 
                                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Dismiss">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <button wire:click="toggleAiSuggestions" 
                                class="mt-3 w-full text-center text-xs text-purple-600 dark:text-purple-400 hover:text-purple-800">
                                Hide suggestions
                            </button>
                        </div>
                    @endif

                    @if($myActions->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($myActions->take(5) as $action)
                                <div
                                    class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    {{-- Checkbox --}}
                                    <button wire:click="completeAction({{ $action->id }})"
                                        class="flex-shrink-0 mt-0.5 w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 hover:border-indigo-500 dark:hover:border-indigo-400 transition">
                                    </button>

                                    {{-- Task Details --}}
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm text-gray-900 dark:text-white {{ $action->is_overdue ? 'font-medium' : '' }}">
                                            {{ $action->title ?? $action->description }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-0.5 text-xs">
                                            @if($action->due_date)
                                                <span
                                                    class="{{ $action->is_overdue ? 'text-red-600 dark:text-red-400' : ($action->due_date->isToday() ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400') }}">
                                                    @if($action->is_overdue)
                                                        Overdue
                                                    @elseif($action->due_date->isToday())
                                                        Due today
                                                    @elseif($action->due_date->isTomorrow())
                                                        Due tomorrow
                                                    @else
                                                        Due {{ $action->due_date->format('M j') }}
                                                    @endif
                                                </span>
                                            @endif
                                            @if($action->source === 'ai_suggested')
                                                <span class="text-purple-500 dark:text-purple-400">✨ AI</span>
                                            @endif
                                            @if($action->meeting && $action->meeting->organizations->isNotEmpty())
                                                <span class="text-gray-400">•</span>
                                                <span
                                                    class="text-gray-500 dark:text-gray-400 truncate">{{ $action->meeting->organizations->first()->name }}</span>
                                            @elseif($action->project)
                                                <span class="text-gray-400">•</span>
                                                <span class="text-gray-500 dark:text-gray-400 truncate">{{ $action->project->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($myActions->count() > 5)
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 text-center">
                                + {{ $myActions->count() - 5 }} more tasks
                            </p>
                        @endif
                    @else
                        <div class="text-center py-8">
                            <svg class="w-10 h-10 mx-auto text-green-300 dark:text-green-600 mb-2" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">All caught up!</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No pending tasks</p>
                            <button wire:click="toggleAddTask" 
                                class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                                Add a task →
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============================================== --}}
            {{-- SECOND ROW - Projects & Needs Attention --}}
            {{-- ============================================== --}}
            <div class="grid lg:grid-cols-2 gap-6 mb-6">

                {{-- My Projects --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            My Projects
                        </h2>
                        <a href="{{ route('projects.index') }}" wire:navigate
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                            View all →
                        </a>
                    </div>

                    @if($myProjects->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($myProjects->take(4) as $project)
                                <a href="{{ route('projects.show', $project) }}" wire:navigate
                                    class="block p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                                    <div class="flex items-center justify-between mb-2">
                                        <p
                                            class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 truncate">
                                            {{ $project->name }}
                                        </p>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $project->pending_milestones_count }} milestones
                                        </span>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full transition-all"
                                                style="width: {{ $project->progress_percent }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 w-10 text-right">
                                            {{ $project->progress_percent }}%
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">No projects assigned</p>
                        </div>
                    @endif
                </div>

                {{-- Needs Attention --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        Needs Attention
                    </h2>

                    @if($needsAttention->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($needsAttention as $item)
                                @php
                                    $colors = [
                                        'overdue' => 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800 text-red-700 dark:text-red-400',
                                        'urgent' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-800 text-amber-700 dark:text-amber-400',
                                        'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800 text-blue-700 dark:text-blue-400',
                                    ];
                                    $dotColors = [
                                        'overdue' => 'bg-red-500',
                                        'urgent' => 'bg-amber-500',
                                        'info' => 'bg-blue-500',
                                    ];
                                    $color = $colors[$item['severity']] ?? $colors['info'];
                                    $dotColor = $dotColors[$item['severity']] ?? $dotColors['info'];
                                @endphp
                                <div class="p-3 rounded-lg border {{ $color }}">
                                    <div class="flex items-center gap-2 text-sm font-medium">
                                        <span
                                            class="w-2 h-2 {{ $dotColor }} rounded-full {{ $item['severity'] === 'overdue' ? 'animate-pulse' : '' }}"></span>
                                        {{ $item['title'] }}
                                    </div>
                                    @if($item['items']->isNotEmpty())
                                        <div class="mt-2 space-y-1">
                                            @foreach($item['items']->take(2) as $subitem)
                                                <a href="{{ $subitem['url'] }}" wire:navigate
                                                    class="block text-sm hover:underline truncate">
                                                    → {{ $subitem['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>No overdue tasks</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>All meetings have notes</span>
                            </div>
                            @if(auth()->user()->isAdmin())
                                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Reports on track</span>
                                </div>
                            @endif
                        </div>

                        {{-- Encouraging message --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex gap-2">
                                <span class="text-lg">✨</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">You're all caught up. Nice work!</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============================================== --}}
            {{-- FUNDING ALERTS (Admin Only) --}}
            {{-- ============================================== --}}
            @if(auth()->user()->isAdmin() && $fundingAlerts->isNotEmpty())
                <div
                    class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-5 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Funding Alerts
                        </h2>
                        <a href="{{ route('grants.index') }}" wire:navigate
                            class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-800">
                            View all →
                        </a>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        @foreach($fundingAlerts as $alert)
                            <a href="{{ $alert['url'] }}" wire:navigate
                                class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-purple-100 dark:border-purple-800 hover:border-purple-300 dark:hover:border-purple-600 transition">
                                <div class="flex items-center gap-2 mb-1">
                                    @if($alert['type'] === 'overdue')
                                        <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                                        <span class="text-xs font-medium text-red-600 dark:text-red-400">Overdue</span>
                                    @elseif($alert['type'] === 'due_soon')
                                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Due Soon</span>
                                    @else
                                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                        <span
                                            class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ $alert['type'] }}</span>
                                    @endif
                                </div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $alert['title'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $alert['funder'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ============================================== --}}
            {{-- RECENT PRESS COVERAGE (Everyone) --}}
            {{-- ============================================== --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        Recent Press Coverage
                    </h2>
                    <a href="{{ route('media.index') }}" wire:navigate
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                        View all →
                    </a>
                </div>

                @if($recentCoverage->isNotEmpty())
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach($recentCoverage->take(4) as $clip)
                                            <a href="{{ $clip->url }}" target="_blank" rel="noopener"
                                                class="flex gap-4 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                                                {{-- Thumbnail --}}
                                                @if($clip->image_url)
                                                    <div class="w-20 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-gray-100 dark:bg-gray-700">
                                                        <img src="{{ $clip->image_url }}" alt="" class="w-full h-full object-cover">
                                                    </div>
                                                @endif

                                                <div class="flex-1 min-w-0">
                                                    {{-- Outlet & Date --}}
                                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                        <span
                                                            class="font-medium text-gray-700 dark:text-gray-300">{{ $clip->outlet_display_name }}</span>
                                                        <span class="text-gray-300 dark:text-gray-600">•</span>
                                                        <span>{{ $clip->published_at?->format('M j') }}</span>
                                                    </div>

                                                    {{-- Title --}}
                                                    <p
                                                        class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 line-clamp-2">
                                                        {{ $clip->title }}
                                                    </p>

                                                    {{-- Meta --}}
                                                    <div class="flex items-center gap-2 mt-1">
                                                        @if($clip->staffMentioned->isNotEmpty())
                                                            <span class="text-xs text-indigo-600 dark:text-indigo-400 flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                                                </svg>
                                                                {{ $clip->staffMentioned->first()->name }} quoted
                                                            </span>
                                                        @endif

                                                        @php
                                                            $sentimentColors = [
                                                                'positive' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
                                                                'neutral' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                                                                'negative' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
                                                            ];
                                                        @endphp
                             <span
                                                            class="px-1.5 py-0.5 text-xs rounded {{ $sentimentColors[$clip->sentiment] ?? 'bg-gray-100 text-gray-600' }}">
                                                            {{ ucfirst($clip->sentiment ?? 'neutral') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No recent coverage</p>
                        <a href="{{ route('media.index') }}" wire:navigate
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 mt-1 inline-block">
                            Log a clip →
                        </a>
                    </div>
                @endif
            </div>

            {{-- ============================================== --}}
            {{-- MY WINS WIDGET --}}
            {{-- ============================================== --}}
            <div class="grid lg:grid-cols-3 gap-6 mb-6">
                <div class="lg:col-span-1">
                    <livewire:accomplishments.my-wins-widget />
                </div>
                
                {{-- Quick Links for Management (Admin only) --}}
                @if(auth()->user()->isAdmin())
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            📊 Team Management
                        </h2>
                        <div class="grid sm:grid-cols-3 gap-4">
                            <a href="{{ route('accomplishments.team') }}" wire:navigate
                                class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center text-lg">
                                    📊
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Team Dashboard</div>
                                    <div class="text-xs text-gray-500">View team accomplishments</div>
                                </div>
                            </a>
                            <a href="{{ route('admin.staff') }}" wire:navigate
                                class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center text-lg">
                                    👥
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Staff Management</div>
                                    <div class="text-xs text-gray-500">Manage team members</div>
                                </div>
                            </a>
                            <a href="{{ route('admin.feedback') }}" wire:navigate
                                class="flex items-center gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center text-lg">
                                    💬
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Feedback</div>
                                    <div class="text-xs text-gray-500">View user feedback</div>
                                </div>
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Timezone Prompt on Login --}}
    @if($showTimezonePrompt)
        <livewire:components.timezone-location :isPrompt="true" />
    @endif
</div>

@script
<script>
    // Auto-scroll chat to bottom when new messages arrive
    $wire.on('chatUpdated', () => {
        const container = document.getElementById('chat-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
@endscript