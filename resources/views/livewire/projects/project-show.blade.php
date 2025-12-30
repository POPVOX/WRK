<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('projects.index') }}" wire:navigate
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-300">P-{{ str_pad($project->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ $project->name }}
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            ];
                            $scopeColors = [
                                'US' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                'Global' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                                'Comms' => 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
                            ];
                        @endphp
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$project->status] ?? $statusColors['active'] }}">
                            {{ $statuses[$project->status] ?? ucfirst($project->status) }}
                        </span>
                        @if($project->scope)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $scopeColors[$project->scope] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $project->scope }}
                            </span>
                        @endif
                        @if($project->lead)
                            <span class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ $project->lead }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="startEditing"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                    Edit
                </button>
                <a href="{{ route('projects.duplicate', $project) }}" wire:navigate
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Duplicate
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Edit Modal -->
            @if($editing)
                <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                    aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelEditing">
                        </div>
                        <div
                            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <form wire:submit="save" class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Project</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                    <input type="text" wire:model="name"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                    <textarea wire:model="description" rows="3"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Goals</label>
                                    <textarea wire:model="goals" rows="3"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start
                                            Date</label>
                                        <input type="date" wire:model="start_date"
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target
                                            End</label>
                                        <input type="date" wire:model="target_end_date"
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                    <select wire:model="status"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex justify-end gap-3 pt-4">
                                    <button type="button" wire:click="cancelEditing"
                                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md">Cancel</button>
                                    <button type="submit"
                                        class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Description & Goals -->
            @if($project->description || $project->goals)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                    @if($project->description)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Description</h4>
                            <p class="text-gray-900 dark:text-white">{{ $project->description }}</p>
                        </div>
                    @endif
                    @if($project->goals)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Goals</h4>
                            <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $project->goals }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Sub-Projects Section (for parent projects) -->
            @if($project->children->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6" id="subprojects">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Sub-Projects
                            <span class="text-sm font-normal text-gray-500">({{ $project->children->count() }})</span>
                        </h3>
                        <a href="{{ route('projects.create') }}?parent={{ $project->id }}" wire:navigate
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Sub-Project
                        </a>
                    </div>

                    @php
                        $statusColors = [
                            'planning' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        ];
                        $typeIcons = [
                            'publication' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            'event' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                            'chapter' => 'M16 4v12l-4-2-4 2V4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                            'newsletter' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
                            'tool' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                            'research' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                            'outreach' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                            'component' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
                            'initiative' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
                        ];
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($project->children->sortBy('sort_order') as $child)
                            <a href="{{ route('projects.show', $child) }}" wire:navigate
                                class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $child->type_color ?? 'text-gray-400' }}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="{{ $typeIcons[$child->project_type] ?? $typeIcons['initiative'] }}" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="font-medium text-gray-900 dark:text-white truncate">{{ $child->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full font-medium {{ $statusColors[$child->status] ?? $statusColors['active'] }}">
                                            {{ ucfirst(str_replace('_', ' ', $child->status)) }}
                                        </span>
                                        @if($child->project_type && $child->project_type !== 'initiative')
                                            <span
                                                class="text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $child->project_type) }}</span>
                                        @endif
                                        @if($child->children_count > 0)
                                            <span class="text-gray-400">+{{ $child->children_count }}</span>
                                        @endif
                                    </div>
                                    @if($child->lead)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $child->lead }}</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                @php
                    $tabBadges = [
                        'meetings' => $project->meetings->count(),
                        'documents' => $project->documents->count(),
                        'notes' => $project->notes->count(),
                        'decisions' => $project->decisions->count(),
                        'milestones' => $project->milestones->count(),
                        'questions' => $project->questions->where('status', 'open')->count(),
                    ];
                @endphp
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px overflow-x-auto">
                        @foreach(['overview' => 'Overview', 'chat' => '✨ AI Chat', 'team' => 'Team', 'meetings' => 'Meetings', 'documents' => 'Documents', 'notes' => 'Notes', 'decisions' => 'Decisions', 'milestones' => 'Milestones', 'questions' => 'Questions'] as $tab => $label)
                            @php $count = $tabBadges[$tab] ?? null; @endphp
                            <button
                                wire:click="setTab('{{ $tab }}')"
                                class="px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap flex items-center gap-2 {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
                                @if($tab === 'chat' && !config('ai.enabled')) disabled title="AI is disabled by the administrator" @endif>
                                <span>{{ $label }}</span>
                                @if($count)
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $count }}</span>
                                @endif
                            </button>
                        @endforeach
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Overview Tab -->
                    @if($activeTab === 'overview')
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">Recent Activity</h4>
                            @if($project->meetings->count() || $project->decisions->count())
                                <div class="space-y-3">
                                    @foreach($project->decisions->take(5) as $decision)
                                        <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                            <div
                                                class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">Decision:
                                                    {{ $decision->title }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $decision->decision_date?->format('M j, Y') ?? 'No date' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach($project->meetings->take(5) as $meeting)
                                        <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                            <div
                                                class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $meeting->title ?: 'Meeting' }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $meeting->meeting_date->format('M j, Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm">No activity yet. Link meetings and add
                                    decisions to get started.</p>
                            @endif
                        </div>
                    @endif

                    <!-- AI Chat Tab -->
                    @if($activeTab === 'chat')
                        <div class="space-y-4" wire:poll.3s="refreshChatHistory">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">✨</span>
                                    <h4 class="font-medium text-gray-900 dark:text-white">Project AI Assistant</h4>
                                    @if($aiEnabled)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Disabled</span>
                                    @endif
                                </div>
                                @if(count($projectChatHistory) > 0)
                                    <button wire:click="clearProjectChat" class="text-sm text-gray-500 hover:text-red-500">
                                        Clear Chat
                                    </button>
                                @endif
                            </div>

                            @if($aiNotice)
                                <div
                                    class="p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg text-sm text-yellow-800 dark:text-yellow-300">
                                    {{ $aiNotice }}
                                </div>
                            @endif

                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Ask questions about this project, get help drafting content, or brainstorm ideas. The AI has
                                access to project context and documents.
                            </p>

                            <!-- Chat History -->
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 min-h-[300px] max-h-[500px] overflow-y-auto space-y-4"
                                id="chat-container">
                                @forelse($projectChatHistory as $message)
                                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                                        <div
                                            class="max-w-[80%] {{ $message['role'] === 'user' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700' }} rounded-lg px-4 py-2 shadow-sm">
                                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                                {!! nl2br(e($message['content'])) !!}
                                            </div>
                                            <div
                                                class="text-xs {{ $message['role'] === 'user' ? 'text-indigo-200' : 'text-gray-400' }} mt-1">
                                                {{ $message['timestamp'] }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                                        <div class="text-center">
                                            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                            <p>Start a conversation with your project AI assistant</p>
                                        </div>
                                    </div>
                                @endforelse

                                @if($isChatProcessing)
                                    <div class="flex justify-start">
                                        <div
                                            class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-2 shadow-sm">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 animate-spin text-indigo-500" fill="none"
                                                    viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                                <span class="text-sm text-gray-500">AI is thinking...</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Chat Input -->
                            <form wire:submit="sendEnhancedChat" class="flex gap-2">
                                <input type="text" wire:model="projectChatQuery" placeholder="Ask about this project..."
                                    class="flex-1 rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                    @disabled(!$aiEnabled)>
                                <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                    @disabled(!$aiEnabled || $isChatProcessing)>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endif

                    <!-- Team Tab -->
                    @if($activeTab === 'team')
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium text-gray-900 dark:text-white">POPVOX Fdn Team</h4>
                                <button wire:click="toggleAddStaffModal"
                                    class="px-3 py-1.5 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm">
                                    {{ $showAddStaffModal ? 'Cancel' : '+ Add Staff' }}
                                </button>
                            </div>

                            @if($project->staff->count())
                                <div class="space-y-3">
                                    @foreach($project->staff as $staff)
                                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                                    <span
                                                        class="text-indigo-600 dark:text-indigo-400 font-medium">{{ substr($staff->name, 0, 1) }}</span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $staff->name }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $staff->email }}</div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <select wire:change="updateStaffRole({{ $staff->id }}, $event.target.value)"
                                                    class="text-sm border-gray-300 dark:bg-gray-600 dark:border-gray-500 rounded-md">
                                                    @foreach($staffRoles as $value => $label)
                                                        <option value="{{ $value }}" {{ $staff->pivot->role === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button wire:click="removeStaff({{ $staff->id }})"
                                                    wire:confirm="Remove this team member?"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-8">No team members assigned
                                    yet.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Documents Tab -->
                    @if($activeTab === 'documents')
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium text-gray-900 dark:text-white">Documents</h4>
                                <button wire:click="toggleDocumentForm"
                                    class="px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 shadow-sm">
                                    {{ $showDocumentForm ? 'Cancel' : '+ Add Document' }}
                                </button>
                            </div>

                            @if($showDocumentForm)
                                <form wire:submit="addDocument" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg space-y-4">
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" wire:model.live="documentType" value="link"
                                                class="text-indigo-600">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Link</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" wire:model.live="documentType" value="file"
                                                class="text-indigo-600">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">File</span>
                                        </label>
                                    </div>
                                    <div>
                                        <input type="text" wire:model="documentTitle" placeholder="Title *" required
                                            class="w-full rounded-md border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                    </div>
                                    @if($documentType === 'link')
                                        <div>
                                            <input type="url" wire:model="documentUrl" placeholder="https://..." required
                                                class="w-full rounded-md border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                        </div>
                                    @else
                                        <div>
                                            <input type="file" wire:model="documentFile"
                                                class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700">
                                        </div>
                                    @endif
                                    <div>
                                        <textarea wire:model="documentDescription" placeholder="Description (optional)" rows="2"
                                            class="w-full rounded-md border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white"></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Add Document
                                        </button>
                                    </div>
                                </form>
                            @endif

                            @if($project->documents->count())
                                <div class="space-y-3">
                                    @foreach($project->documents as $doc)
                                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex items-center gap-3">
                                                @if($doc->type === 'link')
                                                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                @else
                                                    <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                @endif
                                                <div>
                                                    <a href="{{ $doc->getAccessUrl() }}" target="_blank"
                                                        class="font-medium text-gray-900 dark:text-white hover:text-indigo-600">
                                                        {{ $doc->title }}
                                                    </a>
                                                    @if($doc->description)
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $doc->description }}
                                                        </div>
                                                    @endif
                                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                                        Added {{ $doc->created_at->diffForHumans() }}
                                                        @if($doc->uploadedBy) by {{ $doc->uploadedBy->name }} @endif
                                                        @if($doc->formatted_size) · {{ $doc->formatted_size }} @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Delete this document?"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-8">No documents yet. Add links
                                    or upload files.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Notes Tab -->
                    @if($activeTab === 'notes')
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium text-gray-900 dark:text-white">Project Notes</h4>
                                <button wire:click="toggleNoteForm"
                                    class="px-3 py-1.5 text-sm font-semibold text-white bg-purple-600 rounded-md hover:bg-purple-700 shadow-sm">
                                    {{ $showNoteForm ? 'Cancel' : '+ Add Note' }}
                                </button>
                            </div>

                            @if($showNoteForm)
                                <form wire:submit="addNote" class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg space-y-4">
                                    <div>
                                        <select wire:model="noteType"
                                            class="w-full rounded-md border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                            @foreach($noteTypes as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <textarea wire:model="noteContent" placeholder="Write your note..." rows="4" required
                                            class="w-full rounded-md border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white"></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                            Add Note
                                        </button>
                                    </div>
                                </form>
                            @endif

                            <!-- Pinned Notes -->
                            @if($project->pinnedNotes->count())
                                <div class="mb-4">
                                    <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">📌 Pinned</h5>
                                    @foreach($project->pinnedNotes as $note)
                                        @include('livewire.projects.partials.note-item', ['note' => $note])
                                    @endforeach
                                </div>
                            @endif

                            <!-- All Notes -->
                            @if($project->notes->where('is_pinned', false)->count())
                                <div class="space-y-3">
                                    @foreach($project->notes->where('is_pinned', false) as $note)
                                        @include('livewire.projects.partials.note-item', ['note' => $note])
                                    @endforeach
                                </div>
                            @elseif(!$project->pinnedNotes->count())
                                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-8">No notes yet. Add project
                                    updates, decisions, or blockers.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Meetings Tab -->
                    @if($activeTab === 'meetings')
                        <div class="space-y-4">
                            <div class="flex justify-end">
                                <button wire:click="toggleAddMeetingModal"
                                    class="px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 shadow-sm">
                                    {{ $showAddMeetingModal ? 'Cancel' : '+ Link Meeting' }}
                                </button>
                            </div>

                            @if($project->meetings->count())
                                @foreach($project->meetings as $meeting)
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg group">
                                        <div class="flex justify-between items-start">
                                            <a href="{{ route('meetings.show', $meeting) }}" wire:navigate
                                                class="flex-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $meeting->title ?: 'Meeting' }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $meeting->meeting_date->format('M j, Y') }}
                                                </div>
                                                @if($meeting->pivot->relevance_note)
                                                    <div class="text-sm text-indigo-600 dark:text-indigo-400 mt-1">
                                                        {{ $meeting->pivot->relevance_note }}
                                                    </div>
                                                @endif
                                            </a>
                                            <div class="flex items-center gap-3">
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $meeting->organizations->pluck('name')->join(', ') }}
                                                </div>
                                                <button wire:click="unlinkMeeting({{ $meeting->id }})"
                                                    wire:confirm="Unlink this meeting from project?"
                                                    class="opacity-0 group-hover:opacity-100 text-red-600 dark:text-red-400 text-xs hover:underline">
                                                    Unlink
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-8">No meetings linked yet.
                                    Click "+ Link Meeting" to add existing meetings.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Decisions Tab -->
                    @if($activeTab === 'decisions')
                        <div class="space-y-4">
                            <div class="flex justify-end">
                                <button wire:click="toggleDecisionForm"
                                    class="px-3 py-1.5 text-sm font-semibold text-white bg-purple-600 rounded-md hover:bg-purple-700 shadow-sm">
                                    {{ $showDecisionForm ? 'Cancel' : '+ Add Decision' }}
                                </button>
                            </div>

                            @if($showDecisionForm)
                                <form wire:submit="addDecision"
                                    class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title
                                            *</label>
                                        <input type="text" wire:model="decisionTitle" required
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('decisionTitle') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description
                                            *</label>
                                        <textarea wire:model="decisionDescription" rows="2" required
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                        @error('decisionDescription') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rationale
                                            (Why?)</label>
                                        <textarea wire:model="decisionRationale" rows="2"
                                            placeholder="Why was this decision made?"
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Context</label>
                                        <textarea wire:model="decisionContext" rows="2"
                                            placeholder="What inputs/information informed this?"
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Decision
                                                Date</label>
                                            <input type="date" wire:model="decisionDate"
                                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Decided
                                                By</label>
                                            <input type="text" wire:model="decisionDecidedBy"
                                                placeholder="Who made the decision?"
                                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="px-4 py-2 text-sm font-semibold text-white bg-purple-600 rounded-md hover:bg-purple-700 shadow-sm">Add
                                            Decision</button>
                                    </div>
                                </form>
                            @endif

                            @forelse($project->decisions as $decision)
                                <div
                                    class="p-4 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h5 class="font-medium text-gray-900 dark:text-white">{{ $decision->title }}</h5>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                {{ $decision->description }}
                                            </p>
                                            @if($decision->rationale)
                                                <div class="mt-3 p-3 bg-purple-50 dark:bg-purple-900/30 rounded">
                                                    <div class="text-xs font-medium text-purple-700 dark:text-purple-300 mb-1">WHY
                                                    </div>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $decision->rationale }}
                                                    </p>
                                                </div>
                                            @endif
                                            @if($decision->context)
                                                <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-800 rounded">
                                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">CONTEXT
                                                    </div>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $decision->context }}</p>
                                                </div>
                                            @endif
                                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $decision->decision_date?->format('M j, Y') }}
                                                @if($decision->decided_by) • {{ $decision->decided_by }} @endif
                                            </div>
                                        </div>
                                        <button wire:click="deleteDecision({{ $decision->id }})"
                                            wire:confirm="Delete this decision?"
                                            class="text-red-600 dark:text-red-400 hover:text-red-800 text-sm ml-4">Delete</button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-8">No decisions recorded yet.
                                </p>
                            @endforelse
                        </div>
                    @endif

                    <!-- Milestones Tab -->
                    @if($activeTab === 'milestones')
                        <div class="space-y-4">
                            <div class="flex justify-end">
                                <button wire:click="toggleMilestoneForm"
                                    class="px-3 py-1.5 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm">
                                    {{ $showMilestoneForm ? 'Cancel' : '+ Add Milestone' }}
                                </button>
                            </div>

                            @if($showMilestoneForm)
                                <form wire:submit="addMilestone"
                                    class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title
                                            *</label>
                                        <input type="text" wire:model="milestoneTitle" required
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                        <textarea wire:model="milestoneDescription" rows="2"
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Target
                                            Date</label>
                                        <input type="date" wire:model="milestoneTargetDate"
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm">Add
                                            Milestone</button>
                                    </div>
                                </form>
                            @endif

                            @if($pendingMilestones->count())
                                <h5 class="font-medium text-gray-900 dark:text-white">Pending</h5>
                                @foreach($pendingMilestones as $milestone)
                                    <div
                                        class="p-4 bg-white dark:bg-gray-700 border {{ $milestone->is_overdue ? 'border-red-300 dark:border-red-600' : 'border-gray-200 dark:border-gray-600' }} rounded-lg flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $milestone->title }}</div>
                                            @if($milestone->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $milestone->description }}</p>
                                            @endif
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Target: {{ $milestone->target_date?->format('M j, Y') ?? 'No date' }}
                                                @if($milestone->is_overdue)
                                                    <span class="text-red-600 dark:text-red-400 font-medium">OVERDUE</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button wire:click="completeMilestone({{ $milestone->id }})"
                                                class="px-3 py-1 text-sm text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900 rounded hover:bg-green-200">Complete</button>
                                            <button wire:click="deleteMilestone({{ $milestone->id }})" wire:confirm="Delete?"
                                                class="text-red-600 dark:text-red-400 text-sm">Delete</button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            @if($completedMilestones->count())
                                <h5 class="font-medium text-gray-900 dark:text-white mt-6">Completed</h5>
                                @foreach($completedMilestones as $milestone)
                                    <div
                                        class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-center justify-between opacity-75">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white line-through">
                                                {{ $milestone->title }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Completed:
                                                {{ $milestone->completed_date?->format('M j, Y') }}
                                            </div>
                                        </div>
                                        <button wire:click="deleteMilestone({{ $milestone->id }})" wire:confirm="Delete?"
                                            class="text-red-600 dark:text-red-400 text-sm">Delete</button>
                                    </div>
                                @endforeach
                            @endif

                            @if(!$pendingMilestones->count() && !$completedMilestones->count())
                                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-8">No milestones yet.</p>
                            @endif
                        </div>
                    @endif

                    <!-- Questions Tab -->
                    @if($activeTab === 'questions')
                        <div class="space-y-4">
                            <div class="flex justify-end">
                                <button wire:click="toggleQuestionForm"
                                    class="px-3 py-1.5 text-sm font-semibold text-white bg-yellow-600 rounded-md hover:bg-yellow-700 shadow-sm">
                                    {{ $showQuestionForm ? 'Cancel' : '+ Add Question' }}
                                </button>
                            </div>

                            @if($showQuestionForm)
                                <form wire:submit="addQuestion"
                                    class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Question
                                            *</label>
                                        <textarea wire:model="questionText" rows="2" required
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Why does this
                                            matter?</label>
                                        <textarea wire:model="questionContext" rows="2"
                                            class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="px-4 py-2 text-sm font-semibold text-white bg-yellow-600 rounded-md hover:bg-yellow-700 shadow-sm">Add
                                            Question</button>
                                    </div>
                                </form>
                            @endif

                            @if($openQuestions->count())
                                <h5 class="font-medium text-gray-900 dark:text-white">Open Questions</h5>
                                @foreach($openQuestions as $question)
                                    <div
                                        class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <p class="text-gray-900 dark:text-white">{{ $question->question }}</p>
                                                @if($question->context)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $question->context }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="flex gap-2 ml-4">
                                                @if($answeringQuestionId !== $question->id)
                                                    <button wire:click="startAnswering({{ $question->id }})"
                                                        class="px-3 py-1 text-sm text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900 rounded hover:bg-green-200">Answer</button>
                                                @endif
                                                <button wire:click="deleteQuestion({{ $question->id }})" wire:confirm="Delete?"
                                                    class="text-red-600 dark:text-red-400 text-sm">Delete</button>
                                            </div>
                                        </div>
                                        @if($answeringQuestionId === $question->id)
                                            <form wire:submit="submitAnswer"
                                                class="mt-4 p-3 bg-white dark:bg-gray-700 rounded space-y-3">
                                                <textarea wire:model="answerText" rows="3" placeholder="Your answer..." required
                                                    class="w-full rounded-md border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white"></textarea>
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" wire:click="cancelAnswering"
                                                        class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                                                    <button type="submit"
                                                        class="px-4 py-1 text-sm text-white bg-green-600 rounded hover:bg-green-700">Submit
                                                        Answer</button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            @if($answeredQuestions->count())
                                <h5 class="font-medium text-gray-900 dark:text-white mt-6">Answered</h5>
                                @foreach($answeredQuestions as $question)
                                    <div
                                        class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                        <p class="text-gray-900 dark:text-white">{{ $question->question }}</p>
                                        <div class="mt-2 p-3 bg-white dark:bg-gray-700 rounded">
                                            <div class="text-xs font-medium text-green-700 dark:text-green-300 mb-1">ANSWER</div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $question->answer }}</p>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $question->answered_date?->format('M j, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            @if(!$openQuestions->count() && !$answeredQuestions->count())
                                <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-8">No questions yet.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar: Organizations, People, Issues -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Organizations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Organizations</h4>
                        <button wire:click="toggleAddOrgModal"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
                    </div>
                    @forelse($project->organizations as $org)
                        <div class="flex items-center justify-between py-2 group">
                            <div class="flex-1">
                                <a href="{{ route('organizations.show', $org) }}" wire:navigate
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">{{ $org->name }}</a>
                                @if($org->pivot->role)
                                    <span
                                        class="ml-2 text-xs px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded">{{ $org->pivot->role }}</span>
                                @endif
                            </div>
                            <button wire:click="unlinkOrganization({{ $org->id }})" wire:confirm="Remove this organization?"
                                class="opacity-0 group-hover:opacity-100 text-red-600 dark:text-red-400 text-xs">×</button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">None linked yet</p>
                    @endforelse
                </div>

                <!-- People -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">People</h4>
                        <button wire:click="toggleAddPersonModal"
                            class="text-xs text-green-600 dark:text-green-400 hover:underline">+ Add</button>
                    </div>
                    @forelse($project->people as $person)
                        <div class="flex items-center justify-between py-2 group">
                            <div class="flex-1">
                                <a href="{{ route('people.show', $person) }}" wire:navigate
                                    class="text-sm text-green-600 dark:text-green-400 hover:underline">{{ $person->name }}</a>
                                @if($person->pivot->role)
                                    <span
                                        class="ml-2 text-xs px-1.5 py-0.5 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">{{ $person->pivot->role }}</span>
                                @endif
                            </div>
                            <button wire:click="unlinkPerson({{ $person->id }})" wire:confirm="Remove this person?"
                                class="opacity-0 group-hover:opacity-100 text-red-600 dark:text-red-400 text-xs">×</button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">None linked yet</p>
                    @endforelse
                </div>

                <!-- Issues -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Issues</h4>
                        <button wire:click="toggleAddIssueModal"
                            class="text-xs text-purple-600 dark:text-purple-400 hover:underline">+ Add</button>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        @forelse($project->issues as $issue)
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded text-xs group">
                                {{ $issue->name }}
                                <button wire:click="unlinkIssue({{ $issue->id }})" wire:confirm="Remove this issue?"
                                    class="opacity-0 group-hover:opacity-100 text-red-600 dark:text-red-400 hover:text-red-800">×</button>
                            </span>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">None linked yet</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Organization Link Modal -->
    @if($showAddOrgModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="toggleAddOrgModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Link Organization</h3>
                    <div class="space-y-4">
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="orgSearch"
                                placeholder="Search organizations..."
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @if($orgResults->count() && !$selectedOrgId)
                                <div
                                    class="mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md max-h-40 overflow-y-auto">
                                    @foreach($orgResults as $org)
                                        <button type="button" wire:click="selectOrg({{ $org->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-white">
                                            {{ $org->name }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <input type="text" wire:model="orgRole" placeholder="e.g., Partner, Funder, Stakeholder"
                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button wire:click="toggleAddOrgModal"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                            <button wire:click="linkOrganization"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                                @if(!$selectedOrgId) disabled @endif>Link</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Person Link Modal -->
    @if($showAddPersonModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="toggleAddPersonModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Link Person</h3>
                    <div class="space-y-4">
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="personSearch" placeholder="Search people..."
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @if($personResults->count() && !$selectedPersonId)
                                <div
                                    class="mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md max-h-40 overflow-y-auto">
                                    @foreach($personResults as $person)
                                        <button type="button" wire:click="selectPerson({{ $person->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-white">
                                            {{ $person->name }}
                                            @if($person->organization)
                                                <span class="text-gray-500 dark:text-gray-400">-
                                                    {{ $person->organization->name }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <input type="text" wire:model="personRole" placeholder="e.g., Primary Contact, Champion"
                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button wire:click="toggleAddPersonModal"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                            <button wire:click="linkPerson"
                                class="px-4 py-2 text-sm text-white bg-green-600 rounded-md hover:bg-green-700"
                                @if(!$selectedPersonId) disabled @endif>Link</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Issue Link Modal -->
    @if($showAddIssueModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="toggleAddIssueModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Link Issue</h3>
                    <div class="space-y-4">
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="issueSearch" placeholder="Search issues..."
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @if($issueResults->count() && !$selectedIssueId)
                                <div
                                    class="mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md max-h-40 overflow-y-auto">
                                    @foreach($issueResults as $issue)
                                        <button type="button" wire:click="selectIssue({{ $issue->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-white">
                                            {{ $issue->name }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="flex justify-end gap-3">
                            <button wire:click="toggleAddIssueModal"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                            <button wire:click="linkIssue"
                                class="px-4 py-2 text-sm text-white bg-purple-600 rounded-md hover:bg-purple-700"
                                @if(!$selectedIssueId) disabled @endif>Link</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Meeting Link Modal -->
    @if($showAddMeetingModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="toggleAddMeetingModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Link Meeting</h3>
                    <div class="space-y-4">
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="meetingSearch"
                                placeholder="Search meetings..."
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @if($meetingResults->count() && !$selectedMeetingId)
                                <div
                                    class="mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md max-h-40 overflow-y-auto">
                                    @foreach($meetingResults as $meeting)
                                        <button type="button" wire:click="selectMeeting({{ $meeting->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-white">
                                            <div>{{ $meeting->title ?: $meeting->meeting_date->format('M j, Y') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $meeting->organizations->pluck('name')->join(', ') }}
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Relevance Note
                                (optional)</label>
                            <input type="text" wire:model="meetingRelevance" placeholder="Why is this meeting relevant?"
                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button wire:click="toggleAddMeetingModal"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                            <button wire:click="linkMeeting"
                                class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700"
                                @if(!$selectedMeetingId) disabled @endif>Link</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Staff Modal -->
    @if($showAddStaffModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="toggleAddStaffModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add Team Member</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search
                                Staff</label>
                            <input type="text" wire:model.live.debounce.300ms="staffSearch" placeholder="Type to search..."
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @if($staffResults->count())
                                <div
                                    class="mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md max-h-40 overflow-y-auto">
                                    @foreach($staffResults as $user)
                                        <button type="button" wire:click="selectStaff({{ $user->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm {{ $selectedStaffId === $user->id ? 'bg-indigo-50 dark:bg-indigo-900' : '' }}">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                            <select wire:model="staffRole"
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($staffRoles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button wire:click="toggleAddStaffModal"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                            <button wire:click="addStaff"
                                class="px-4 py-2 text-sm text-white bg-green-600 rounded-md hover:bg-green-700"
                                @if(!$selectedStaffId) disabled @endif>Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Project Chat Sidebar -->
    <div class="fixed bottom-4 right-4 z-40" x-data="{ chatOpen: false }">
        <!-- Chat Window -->
        <div x-show="chatOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            class="w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 mb-4">
            <div class="flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-700">
                <h5 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    Ask about this project
                </h5>
                <div class="flex items-center gap-2">
                    <button wire:click="clearProjectChat"
                        class="text-gray-400 hover:text-gray-600 text-xs">Clear</button>
                    <button @click="chatOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex flex-col h-80">
                <div class="flex-1 p-3 overflow-y-auto space-y-3">
                    @if(count($projectChatHistory) === 0)
                        <div class="text-center text-gray-400 dark:text-gray-500 py-8">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                            <p class="text-sm">Ask anything about this project</p>
                            <p class="text-xs mt-1">Try: "What are the open questions?"</p>
                        </div>
                    @endif
                    @foreach($projectChatHistory as $msg)
                        <div class="{{ $msg['role'] === 'user' ? 'text-right' : 'text-left' }}">
                            <div
                                class="inline-block max-w-[85%] px-3 py-2 rounded-lg text-sm {{ $msg['role'] === 'user' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' }}">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                            <div class="text-xs text-gray-400 mt-1">{{ $msg['timestamp'] }}</div>
                        </div>
                    @endforeach
                    @if($isChatProcessing)
                        <div class="text-left">
                            <div class="inline-block px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700">
                                <div class="flex items-center gap-1">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                                        style="animation-delay: 0.1s"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                                        style="animation-delay: 0.2s"></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <form wire:submit="sendProjectChat" class="p-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex gap-2">
                        <input type="text" wire:model="projectChatQuery" placeholder="Ask a question..."
                            class="flex-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Chat Toggle Button -->
        <button @click="chatOpen = !chatOpen"
            class="w-14 h-14 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 flex items-center justify-center transition-transform"
            :class="chatOpen ? 'rotate-0' : ''">
            <svg x-show="!chatOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <svg x-show="chatOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>
</div>