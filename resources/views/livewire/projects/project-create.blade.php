<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('projects.index') }}" wire:navigate
                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $isDuplicate ? 'Duplicate Project' : 'New Project' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <!-- AI Extract Option -->
            <div class="mb-6">
                <button wire:click="toggleAiExtract" type="button"
                    class="w-full flex items-center justify-center gap-3 px-4 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 shadow-lg transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <div class="text-left">
                        <div class="font-semibold">✨ AI Extract from Text</div>
                        <div class="text-sm opacity-90">Paste free-form text and let AI fill in all fields</div>
                    </div>
                </button>
            </div>

            <!-- AI Extraction Panel -->
            @if($showAiExtract)
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6 border-2 border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h3 class="font-semibold text-gray-900 dark:text-white">AI Text Extraction</h3>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Paste project details, notes, emails, or any text. The AI will extract title, description, goals,
                        timeline, scope, lead, and tags.
                    </p>

                    <textarea wire:model="freeText" rows="8"
                        class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white mb-4"
                        placeholder="Paste your text here...

                            Example:
                            Anne is leading a new US project called 'Community Engagement Initiative' to improve how we interact with local stakeholders. The main goals are to increase transparency, gather feedback from 500+ community members, and establish monthly town halls. We plan to start in January 2026 and complete by June 2026. Tags: Bridge-building, Public engagement..."></textarea>

                    <div class="flex justify-end gap-3">
                        <button wire:click="toggleAiExtract" type="button"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">
                            Cancel
                        </button>
                        <button wire:click="extractFromText" type="button"
                            class="px-4 py-2 text-sm font-semibold text-white bg-purple-600 rounded-md hover:bg-purple-700 shadow-sm flex items-center gap-2"
                            wire:loading.attr="disabled" wire:loading.class="opacity-50" wire:target="extractFromText">
                            <span wire:loading.remove wire:target="extractFromText">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </span>
                            <span wire:loading wire:target="extractFromText">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                            <span wire:loading.remove wire:target="extractFromText">Extract Details</span>
                            <span wire:loading wire:target="extractFromText">Extracting...</span>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Extracted Badge -->
            @if($hasExtracted)
                <div
                    class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3 mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-green-700 dark:text-green-300 text-sm">
                        <strong>AI extracted project details.</strong> Review and adjust the fields below before creating.
                    </span>
                </div>
            @endif

            <!-- Duplicate Badge -->
            @if($isDuplicate)
                <div
                    class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-3 mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="text-blue-700 dark:text-blue-300 text-sm">
                        <strong>Duplicating:</strong> {{ $sourceProjectName }}. Adjust the details below and save.
                    </span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <form wire:submit="save" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Project Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="e.g., Digital Parliaments Project">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Scope and Lead -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Scope
                            </label>
                            <select wire:model="scope"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select scope...</option>
                                @foreach($scopeOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Lead (POPVOX Staffer)
                            </label>
                            <select wire:model="lead"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select lead...</option>
                                @foreach($leadOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Parent Project and Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Parent Project
                            </label>
                            <select wire:model="parent_project_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">None (standalone project)</option>
                                @foreach($parentProjects as $parentProject)
                                    <option value="{{ $parentProject->id }}">{{ $parentProject->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Nest this project under a parent
                                initiative</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Project Type <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="project_type" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($projectTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('project_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description
                        </label>
                        <textarea wire:model="description" rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="What is this project about?"></textarea>
                    </div>

                    <!-- Goals -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Goals
                        </label>
                        <textarea wire:model="goals" rows="4"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="What are the key objectives?"></textarea>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Start Date
                            </label>
                            <input type="date" wire:model="start_date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Target End Date
                            </label>
                            <input type="date" wire:model="target_end_date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('target_end_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Status and URL -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Status
                            </label>
                            <select wire:model="status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                External URL
                            </label>
                            <input type="url" wire:model="url"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="https://...">
                            @error('url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Tags / Themes
                        </label>
                        <input type="text" wire:model="tagsInput"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Comma-separated, e.g.: Bridge-building, Interbranch feedback, Modernization">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Separate multiple tags with commas</p>
                    </div>

                    <!-- Geographic Tags -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Geographic Focus
                        </label>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <livewire:components.geographic-tag-selector
                                :selectedRegions="$selectedRegions"
                                :selectedCountries="$selectedCountries"
                                :selectedUsStates="$selectedUsStates"
                            />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tag regions, countries, or US states/territories relevant to this project</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('projects.index') }}" wire:navigate
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm">
                            Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>