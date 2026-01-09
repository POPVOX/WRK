<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('grants.index') }}" wire:navigate
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        {{ $grant->name }}
                    </h2>
                    @if($grant->funder)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $grant->funder->name }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                        'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                        'declined' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                    ];
                @endphp
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$grant->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statuses[$grant->status] ?? ucfirst($grant->status) }}
                </span>
                @if($grant->amount)
                    <span class="text-lg font-bold text-indigo-600">${{ number_format($grant->amount, 0) }}</span>
                @endif
                <button wire:click="openEditModal"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Edit
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('message'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Tabs --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px overflow-x-auto">
                        @foreach(['overview' => 'Overview', 'contacts' => 'Contacts', 'documents' => 'Documents', 'requirements' => 'Requirements', 'reports' => '✨ AI Reports', 'automated' => '📊 Automated Reports'] as $tab => $label)
                            <button wire:click="setTab('{{ $tab }}')"
                                class="px-6 py-3 text-sm font-medium border-b-2 whitespace-nowrap {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                <div class="p-6">
                    {{-- Overview Tab --}}
                    @if($activeTab === 'overview')
                        <div class="space-y-6">
                            {{-- Grant Intel Section (Consolidated Insights) --}}
                            @if($consolidatedInsights['hasInsights'] ?? false)
                                <div
                                    class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-purple-100 dark:border-purple-800">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="text-2xl">🎯</span>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Grant Intel</h3>
                                        <span
                                            class="text-xs bg-purple-100 text-purple-700 dark:bg-purple-800 dark:text-purple-300 px-2 py-0.5 rounded-full">AI
                                            Synthesized</span>
                                    </div>

                                    {{-- Summary --}}
                                    @if(!empty($consolidatedInsights['summaries']))
                                        <div class="mb-5 p-4 bg-white/60 dark:bg-gray-800/60 rounded-lg">
                                            <p class="text-gray-700 dark:text-gray-300">
                                                {{ $consolidatedInsights['summaries'][0] ?? '' }}
                                            </p>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        {{-- Funder Profile --}}
                                        <div class="space-y-4">
                                            <h4
                                                class="font-semibold text-purple-700 dark:text-purple-400 flex items-center gap-2">
                                                <span>🏢</span> Funder Profile
                                            </h4>

                                            @if(!empty($consolidatedInsights['priorities']))
                                                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                                                    <h5
                                                        class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">
                                                        What They Care About</h5>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($consolidatedInsights['priorities'] as $priority)
                                                            <span
                                                                class="px-2 py-1 bg-purple-100 dark:bg-purple-800/50 text-purple-700 dark:text-purple-300 text-sm rounded">{{ $priority }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if(!empty($consolidatedInsights['values']))
                                                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                                                    <h5
                                                        class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">
                                                        Values & Themes</h5>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                                        {{ implode(' • ', $consolidatedInsights['values']) }}
                                                    </p>
                                                </div>
                                            @endif

                                            @if(!empty($consolidatedInsights['approach']))
                                                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                                                    <h5
                                                        class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">
                                                        Partnership Style</h5>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                                        {{ $consolidatedInsights['approach'] }}</p>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- What We Need To Do --}}
                                        <div class="space-y-4">
                                            <h4
                                                class="font-semibold text-indigo-700 dark:text-indigo-400 flex items-center gap-2">
                                                <span>📋</span> What We Need To Do
                                            </h4>

                                            @if(!empty($consolidatedInsights['goals']))
                                                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                                                    <h5
                                                        class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">
                                                        Grant Goals</h5>
                                                    <ul class="space-y-1">
                                                        @foreach(array_slice($consolidatedInsights['goals'], 0, 5) as $goal)
                                                            <li class="text-sm text-gray-700 dark:text-gray-300 flex items-start gap-2">
                                                                <span class="text-green-500 mt-0.5">✓</span>
                                                                {{ $goal }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            @if(!empty($consolidatedInsights['keyDates']))
                                                <div class="bg-white/60 dark:bg-gray-800/60 rounded-lg p-3">
                                                    <h5
                                                        class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">
                                                        Key Dates</h5>
                                                    <ul class="space-y-1">
                                                        @foreach(array_slice($consolidatedInsights['keyDates'], 0, 5) as $date)
                                                            <li class="text-sm text-gray-700 dark:text-gray-300">
                                                                @if(is_array($date))
                                                                    <strong>{{ $date['event'] ?? '' }}:</strong>
                                                                    {{ $date['date_description'] ?? '' }}
                                                                @else
                                                                    {{ $date }}
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            @if(!empty($consolidatedInsights['restrictions']) || !empty($consolidatedInsights['compliance']))
                                                <div
                                                    class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 border border-yellow-200 dark:border-yellow-800">
                                                    <h5
                                                        class="text-xs font-semibold text-yellow-700 dark:text-yellow-400 uppercase mb-2">
                                                        ⚠️ Watch Out For</h5>
                                                    <ul class="space-y-1 text-sm text-yellow-800 dark:text-yellow-300">
                                                        @foreach(array_slice($consolidatedInsights['restrictions'], 0, 3) as $r)
                                                            <li>• {{ $r }}</li>
                                                        @endforeach
                                                        @foreach(array_slice($consolidatedInsights['compliance'], 0, 2) as $c)
                                                            <li>• {{ $c }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- What To Highlight in Reports --}}
                                    @if(!empty($consolidatedInsights['relevantActivities']))
                                        <div class="mt-6 pt-5 border-t border-purple-200 dark:border-purple-700">
                                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                                                <span>✨</span> What To Highlight (Recent Activities)
                                            </h4>
                                            <p class="text-sm text-gray-500 mb-3">These project activities may be relevant for your
                                                next report:</p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                @foreach($consolidatedInsights['relevantActivities'] as $activity)
                                                    <div
                                                        class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                                                        <div class="flex items-start gap-2">
                                                            <span class="text-lg">
                                                                @if($activity['type'] === 'decision') 🎯
                                                                @elseif($activity['type'] === 'milestone') 🏆
                                                                @elseif($activity['type'] === 'meeting') 👥
                                                                @else 📌 @endif
                                                            </span>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="font-medium text-gray-900 dark:text-white text-sm">
                                                                    {{ $activity['title'] }}</div>
                                                                <div class="text-xs text-gray-500">
                                                                    {{ ucfirst($activity['type']) }} • {{ $activity['project'] }} •
                                                                    {{ $activity['date'] ?? 'No date' }}
                                                                </div>
                                                                @if(!empty($activity['description']))
                                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                                                        {{ Str::limit($activity['description'], 100) }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div
                                    class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 text-center">
                                    <span class="text-4xl mb-3 block">📄</span>
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-2">No Grant Intel Yet</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                        Upload grant documents and extract insights to get a consolidated view of funder
                                        priorities, requirements, and what to highlight in your reports.
                                    </p>
                                    <button wire:click="setTab('documents')"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                                        Upload Documents
                                    </button>
                                </div>
                            @endif

                            {{-- Basic Grant Info --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Grant Period</h4>
                                    <p class="text-gray-900 dark:text-white">
                                        @if($grant->start_date && $grant->end_date)
                                            {{ $grant->start_date->format('M j, Y') }} -
                                            {{ $grant->end_date->format('M j, Y') }}
                                        @else
                                            Not specified
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Visibility</h4>
                                    <p class="text-gray-900 dark:text-white">
                                        {{ ucfirst($grant->visibility ?? 'Management') }}</p>
                                </div>
                            </div>

                            @if($grant->description)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</h4>
                                    <p class="text-gray-900 dark:text-white">{{ $grant->description }}</p>
                                </div>
                            @endif

                            @if($grant->deliverables)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Deliverables</h4>
                                    <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $grant->deliverables }}</p>
                                </div>
                            @endif

                            {{-- Linked Projects --}}
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Linked Projects
                                    ({{ $linkedProjects->count() }})</h4>
                                @if($linkedProjects->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($linkedProjects as $project)
                                            <a href="{{ route('projects.show', $project) }}" wire:navigate
                                                class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $project->name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ ucfirst($project->status) }}</div>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No projects linked to this grant yet.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Contacts Tab --}}
                    @if($activeTab === 'contacts')
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    Funder Contacts ({{ $funderContacts->count() }})
                                </h4>
                                @if($grant->funder)
                                    <a href="{{ route('organizations.show', $grant->funder) }}" wire:navigate
                                        class="text-sm text-indigo-600 hover:text-indigo-800">
                                        View Organization →
                                    </a>
                                @endif
                            </div>

                            @if($funderContacts->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($funderContacts as $contact)
                                        <a href="{{ route('people.show', $contact) }}" wire:navigate
                                            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                            <div
                                                class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-medium">
                                                {{ substr($contact->name, 0, 1) }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium text-gray-900 dark:text-white truncate">
                                                    {{ $contact->name }}
                                                </div>
                                                @if($contact->title)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                        {{ $contact->title }}
                                                    </div>
                                                @endif
                                                @if($contact->email)
                                                    <div class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                                        {{ $contact->email }}
                                                    </div>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 opacity-50" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">
                                        No contacts found for this funder.
                                        @if($grant->funder)
                                            <a href="{{ route('organizations.show', $grant->funder) }}"
                                                class="text-indigo-600 hover:text-indigo-800">
                                                Add contacts to {{ $grant->funder->name }}
                                            </a>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Documents Tab --}}
                    @if($activeTab === 'documents')
                        <div class="space-y-6">
                            {{-- Upload Mode Tabs --}}
                            <div class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg w-fit">
                                <button wire:click="$set('uploadMode', 'file')"
                                    class="px-4 py-2 text-sm font-medium rounded-md transition {{ $uploadMode === 'file' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                    <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Upload File
                                </button>
                                <button wire:click="$set('uploadMode', 'paste')"
                                    class="px-4 py-2 text-sm font-medium rounded-md transition {{ $uploadMode === 'paste' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                    <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Paste Text
                                </button>
                            </div>

                            @if($uploadMode === 'paste')
                                                        {{-- Paste Text Form --}}
                                                        <div
                                                            class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6 border border-gray-200 dark:border-gray-600">
                                                            <form wire:submit="savePastedText" class="space-y-4">
                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                    <div>
                                                                        <label
                                                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document
                                                                            Title *</label>
                                                                        <input type="text" wire:model="uploadTitle"
                                                                            placeholder="e.g., Grant Agreement Summary"
                                                                            class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                                        @error('uploadTitle') <span class="text-red-500 text-xs">{{ $message }}</span>
                                                                        @enderror
                                                                    </div>
                                                                    <div>
                                                                        <label
                                                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document
                                                                            Type</label>
                                                                        <select wire:model="uploadType"
                                                                            class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                                            @foreach($documentTypes as $k => $label)
                                                                                <option value="{{ $k }}">{{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div>
                                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                                        Paste Content *
                                                                        <span class="text-xs text-gray-500 font-normal ml-2">Paste grant text, email
                                                                            content, or any relevant document text</span>
                                                                    </label>
                                                                    <textarea wire:model="pasteContent" rows="12" placeholder="Paste your grant document text here...

                                You can paste:
                                • Grant agreement terms
                                • Email correspondence
                                • Application text
                                • Any document content for AI analysis"
                                                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono text-sm"></textarea>
                                                                    @error('pasteContent') <span class="text-red-500 text-xs">{{ $message }}</span>
                                                                    @enderror
                                                                    @if($pasteContent)
                                                                        <p class="text-xs text-gray-500 mt-1">{{ number_format(strlen($pasteContent)) }}
                                                                            characters</p>
                                                                    @endif
                                                                </div>

                                                                <div class="flex items-center gap-3">
                                                                    <button type="submit"
                                                                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                                                        Save Document
                                                                    </button>
                                                                    @if($pasteContent)
                                                                        <button type="button" wire:click="$set('pasteContent', '')"
                                                                            class="text-sm text-gray-500 hover:text-gray-700">
                                                                            Clear
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </form>
                                                        </div>
                            @else
                                {{-- Drag & Drop Upload Zone --}}
                                <div x-data="{ 
                                            isDragging: false,
                                            isUploading: false
                                         }" x-on:livewire-upload-start="isUploading = true"
                                    x-on:livewire-upload-finish="isUploading = false"
                                    x-on:livewire-upload-error="isUploading = false" class="relative">

                                    <div x-on:dragover.prevent="isDragging = true" x-on:dragleave.prevent="isDragging = false"
                                        x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                                        :class="isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600'"
                                        class="border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200 bg-gray-50 dark:bg-gray-700/50">

                                        {{-- Upload Icon --}}
                                        <div class="mb-4">
                                            <div :class="isDragging ? 'scale-110 text-indigo-500' : 'text-gray-400'"
                                                class="w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center transition-transform">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                            </div>
                                        </div>

                                        <p class="text-gray-600 dark:text-gray-300 mb-2">
                                            <span x-show="!isDragging">Drag and drop files here, or</span>
                                            <span x-show="isDragging" class="text-indigo-600 font-medium">Drop files to
                                                upload</span>
                                        </p>

                                        <label x-show="!isDragging" class="inline-block cursor-pointer">
                                            <span
                                                class="px-4 py-2 text-sm font-semibold text-indigo-600 bg-white dark:bg-gray-800 border border-indigo-300 dark:border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition">
                                                Browse Files
                                            </span>
                                            <input type="file" x-ref="fileInput" wire:model="uploadFile" class="hidden"
                                                accept=".pdf,.doc,.docx,.txt,.md">
                                        </label>

                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                                            Supported: PDF, DOC, DOCX, TXT, MD (max 20MB)
                                        </p>
                                    </div>

                                    {{-- Upload Progress --}}
                                    <div x-show="isUploading"
                                        class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-xl flex items-center justify-center">
                                        <div class="text-center">
                                            <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto mb-2" fill="none"
                                                viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <p class="text-sm text-gray-600 dark:text-gray-300">Uploading...</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- File Details (shown after file selected) --}}
                                @if($uploadFile)
                                    <div
                                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">
                                                        {{ $uploadFile->getClientOriginalName() }}</p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ number_format($uploadFile->getSize() / 1024, 1) }} KB</p>
                                                </div>
                                            </div>
                                            <button type="button" wire:click="$set('uploadFile', null)"
                                                class="text-gray-400 hover:text-red-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <form wire:submit="uploadDocument" class="space-y-3">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document
                                                        Title *</label>
                                                    <input type="text" wire:model="uploadTitle"
                                                        placeholder="Enter a title for this document..."
                                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                    @error('uploadTitle') <span class="text-red-500 text-xs">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document
                                                        Type</label>
                                                    <select wire:model="uploadType"
                                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                        @foreach($documentTypes as $k => $label)
                                                            <option value="{{ $k }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <button type="submit"
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                                    Upload & Save
                                                </button>
                                                <label class="text-sm text-gray-500 dark:text-gray-400">
                                                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 mr-1.5">
                                                    Auto-extract requirements after upload
                                                </label>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            @endif {{-- End @else for uploadMode --}}

                            {{-- Documents List --}}
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-medium text-gray-900 dark:text-white">Uploaded Documents
                                        ({{ $grant->documents->count() }})</h4>
                                    @php
                                        $unprocessedCount = $grant->documents->where('ai_processed', false)->count();
                                    @endphp
                                    @if($unprocessedCount > 0)
                                        <button wire:click="extractAllRequirements"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 shadow-sm">
                                            <span>✨</span>
                                            Extract Insights ({{ $unprocessedCount }})
                                        </button>
                                    @endif
                                </div>
                                @if($grant->documents->count() > 0)
                                    <div class="space-y-3">
                                        @foreach($grant->documents as $doc)
                                            <div
                                                class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                                                <div class="flex items-center gap-4">
                                                    <div
                                                        class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-white">{{ $doc->title }}
                                                        </div>
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ $documentTypes[$doc->type] ?? ucfirst($doc->type) }} •
                                                            {{ $doc->getFileSizeFormatted() }} •
                                                            {{ $doc->created_at->format('M j, Y') }}
                                                        </div>
                                                        @if($doc->ai_processed && $doc->ai_summary)
                                                            <div class="mt-1 text-sm text-green-600 dark:text-green-400">
                                                                ✓ AI Processed
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ $doc->getFileUrl() }}" target="_blank"
                                                        class="px-3 py-1.5 text-sm text-indigo-600 hover:text-indigo-800">
                                                        View
                                                    </a>
                                                    @if(!$doc->ai_processed)
                                                        <button wire:click="extractRequirements({{ $doc->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                                            class="px-3 py-1.5 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 inline-flex items-center gap-1">
                                                            <span wire:loading.remove wire:target="extractRequirements({{ $doc->id }})">✨ Extract Insights</span>
                                                            <span wire:loading wire:target="extractRequirements({{ $doc->id }})">
                                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                </svg>
                                                                Processing...
                                                            </span>
                                                        </button>
                                                    @endif
                                                    <button wire:click="deleteDocument({{ $doc->id }})"
                                                        wire:confirm="Are you sure you want to delete this document?"
                                                        class="px-3 py-1.5 text-sm text-red-600 hover:text-red-800">
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Processed Indicator --}}
                                            @if($doc->ai_processed)
                                                <div class="ml-14 mt-2">
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 bg-green-100 dark:bg-green-800/50 dark:text-green-300 rounded-full">
                                                        ✓ Insights extracted
                                                    </span>
                                                    <a href="#" wire:click.prevent="setTab('overview')" class="ml-2 text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                                        View in Grant Intel →
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No documents uploaded yet.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Requirements Tab --}}
                    @if($activeTab === 'requirements')
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-900 dark:text-white">Reporting Requirements</h4>
                                <button wire:click="toggleAddRequirement"
                                    class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    {{ $showAddRequirement ? 'Cancel' : '+ Add Requirement' }}
                                </button>
                            </div>

                            @if($showAddRequirement)
                                <form wire:submit="addRequirement" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <input type="text" wire:model="reqName" placeholder="Requirement name..."
                                            class="rounded-lg border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                        <select wire:model="reqType"
                                            class="rounded-lg border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                            @foreach($requirementTypes as $k => $label)
                                                <option value="{{ $k }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <input type="date" wire:model="reqDueDate"
                                            class="rounded-lg border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                        <input type="text" wire:model="reqNotes" placeholder="Notes (optional)..."
                                            class="rounded-lg border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                    </div>
                                    <button type="submit"
                                        class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700">
                                        Add Requirement
                                    </button>
                                </form>
                            @endif

                            {{-- Requirements List --}}
                            @if($upcomingRequirements->count() > 0)
                                <div class="space-y-3">
                                    @foreach($upcomingRequirements as $req)
                                        @php
                                            $isOverdue = $req->isOverdue();
                                            $isDueSoon = $req->isDueSoon();
                                        @endphp
                                        <div
                                            class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border {{ $isOverdue ? 'border-red-300 dark:border-red-700' : ($isDueSoon ? 'border-yellow-300 dark:border-yellow-700' : 'border-gray-200 dark:border-gray-700') }} rounded-lg">
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $req->name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $requirementTypes[$req->type] ?? ucfirst($req->type) }} • Due:
                                                    {{ $req->due_date->format('M j, Y') }}
                                                </div>
                                                @if($req->sourceDocument)
                                                    <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">
                                                        📄 Source: {{ $req->sourceDocument->title }}
                                                    </div>
                                                @endif
                                                @if($req->source_quote)
                                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 italic">
                                                        "{{ Str::limit($req->source_quote, 100) }}"
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if($isOverdue)
                                                    <span
                                                        class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 rounded">Overdue</span>
                                                @elseif($isDueSoon)
                                                    <span
                                                        class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 rounded">Due
                                                        Soon</span>
                                                @endif
                                                <select wire:change="updateRequirementStatus({{ $req->id }}, $event.target.value)"
                                                    class="text-sm rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                    <option value="pending" @selected($req->status === 'pending')>Pending</option>
                                                    <option value="in_progress" @selected($req->status === 'in_progress')>In Progress
                                                    </option>
                                                    <option value="submitted" @selected($req->status === 'submitted')>Submitted
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm">No reporting requirements yet. Add
                                    requirements manually or extract from uploaded documents.</p>
                            @endif
                        </div>
                    @endif

                    {{-- Reports Tab --}}
                    @if($activeTab === 'reports')
                        <div class="space-y-6">
                            <div
                                class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 rounded-lg p-6">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">✨ AI Report Generator</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Generate draft reports based on linked projects, meetings, and documents from the
                                    Knowledge Hub.
                                </p>
                                <div class="flex items-center gap-3">
                                    <select wire:model="reportType"
                                        class="rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="progress">Progress Report</option>
                                        <option value="narrative">Narrative Report</option>
                                        <option value="financial">Financial Summary</option>
                                        <option value="impact">Impact Assessment</option>
                                    </select>
                                    <button wire:click="generateReport"
                                        class="px-4 py-2 text-sm font-semibold text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="generateReport">Generate Report Draft</span>
                                        <span wire:loading wire:target="generateReport">Generating...</span>
                                    </button>
                                    @if($generatedReport)
                                        <button wire:click="clearReport" class="text-sm text-gray-500 hover:text-gray-700">
                                            Clear
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($generatedReport)
                                <div
                                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-medium text-gray-900 dark:text-white">Generated Report Draft</h4>
                                        <button
                                            onclick="navigator.clipboard.writeText(document.getElementById('report-content').innerText)"
                                            class="px-3 py-1.5 text-sm text-indigo-600 hover:text-indigo-800">
                                            Copy to Clipboard
                                        </button>
                                    </div>
                                    <div id="report-content" class="prose prose-sm dark:prose-invert max-w-none">
                                        {!! \Illuminate\Support\Str::markdown($generatedReport) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Automated Reports Tab --}}
                    @if($activeTab === 'automated')
                        <livewire:grants.automated-reports :grant="$grant" :key="'automated-reports-'.$grant->id" />
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Grant Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEditModal"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit="saveGrant">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Grant</h3>
                        </div>
                        <div class="px-6 py-4 max-h-[70vh] overflow-y-auto space-y-4">
                            {{-- Grant Name --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grant Name *</label>
                                <input type="text" wire:model="editName"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('editName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            {{-- Status & Amount --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                                    <select wire:model="editStatus"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="pending">Pending</option>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="declined">Declined</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount ($)</label>
                                    <input type="number" wire:model="editAmount" step="0.01" min="0"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>

                            {{-- Date Range --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                                    <input type="date" wire:model="editStartDate"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                                    <input type="date" wire:model="editEndDate"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('editEndDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model="editDescription" rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Brief description of the grant purpose and scope"></textarea>
                            </div>

                            {{-- Deliverables --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deliverables</label>
                                <textarea wire:model="editDeliverables" rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Key deliverables and milestones"></textarea>
                            </div>

                            {{-- Visibility & Scope --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Visibility</label>
                                    <select wire:model="editVisibility"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="management">Management Only</option>
                                        <option value="all">All Team Members</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Scope</label>
                                    <select wire:model="editScope"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="all">All Programs</option>
                                        <option value="project">Specific Project</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal Notes</label>
                                <textarea wire:model="editNotes" rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Internal notes (not shared with funder)"></textarea>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                            <button type="button" wire:click="closeEditModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>