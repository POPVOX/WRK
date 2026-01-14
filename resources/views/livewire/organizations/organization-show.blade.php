<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $organization->name }}
            </h2>
            <a href="{{ route('organizations.index') }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                ← Back to Organizations
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Organization Profile Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                @if($editing)
                    <!-- Edit Mode -->
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                <input type="text" wire:model="name"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Abbreviation</label>
                                <input type="text" wire:model="abbreviation" placeholder="e.g., BPC" maxlength="20"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('abbreviation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                <select wire:model="type"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select type...</option>
                                    @foreach($types as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                                <input type="url" wire:model="website" placeholder="https://..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('website') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input type="email" wire:model="email" placeholder="contact@organization.org"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                                <input type="tel" wire:model="phone" placeholder="(555) 123-4567"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn
                                URL</label>
                            <input type="url" wire:model="linkedin_url" placeholder="https://linkedin.com/company/..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('linkedin_url') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                            <textarea wire:model="notes" rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Geographic Focus</label>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <livewire:components.geographic-tag-selector
                                    :selectedRegions="$selectedRegions"
                                    :selectedCountries="$selectedCountries"
                                    :selectedUsStates="$selectedUsStates"
                                />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                Save Changes
                            </button>
                            <button type="button" wire:click="cancelEditing"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                                Cancel
                            </button>
                        </div>
                    </form>
                @else
                    <!-- View Mode -->
                    <div class="flex justify-between items-start">
                        <div class="flex gap-4">
                            @if($organization->logo_url)
                                <img src="{{ $organization->logo_url }}" alt="{{ $organization->name }} logo"
                                    class="w-16 h-16 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                            @else
                                <div
                                    class="w-16 h-16 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-300">
                                        {{ $organization->abbreviation ?: substr($organization->name, 0, 2) }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <div class="flex items-center gap-3">
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ $organization->name }}
                                        @if($organization->abbreviation)
                                            <span
                                                class="text-lg font-normal text-gray-500 dark:text-gray-400">({{ $organization->abbreviation }})</span>
                                        @endif
                                    </h3>
                                    @if($organization->type)
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                            {{ $organization->type }}
                                        </span>
                                    @endif
                                    {{-- Geographic Tags --}}
                                    @foreach($organization->regions as $region)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                            🌍 {{ $region->name }}
                                        </span>
                                    @endforeach
                                    @foreach($organization->countries as $country)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300">
                                            🏳️ {{ $country->name }}
                                        </span>
                                    @endforeach
                                    @foreach($organization->usStates as $state)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300">
                                            🇺🇸 {{ $state->abbreviation }}
                                        </span>
                                    @endforeach
                                </div>
                                @if($organization->description)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 max-w-2xl">
                                        {{ Str::limit($organization->description, 200) }}
                                    </p>
                                @endif
                                <div class="flex flex-wrap gap-4 mt-2">
                                    @if($organization->website)
                                        <a href="{{ $organization->website }}" target="_blank"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                            Website
                                        </a>
                                    @endif
                                    @if($organization->email)
                                        <a href="mailto:{{ $organization->email }}"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            {{ $organization->email }}
                                        </a>
                                    @endif
                                    @if($organization->phone)
                                        <a href="tel:{{ $organization->phone }}"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            {{ $organization->phone }}
                                        </a>
                                    @endif
                                    @if($organization->linkedin_url)
                                        <a href="{{ $organization->linkedin_url }}" target="_blank"
                                            class="text-blue-600 dark:text-blue-400 hover:underline text-sm inline-flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                            </svg>
                                            LinkedIn
                                        </a>
                                    @endif
                                </div>
                                @if($organization->notes)
                                    <p class="mt-3 text-gray-600 dark:text-gray-400">{{ $organization->notes }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2">
                            @if($organization->linkedin_url && !$organization->logo_url)
                                <button wire:click="fetchFromLinkedIn" wire:loading.attr="disabled"
                                    class="px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="fetchFromLinkedIn">Fetch LinkedIn</span>
                                    <span wire:loading wire:target="fetchFromLinkedIn">Fetching...</span>
                                </button>
                            @endif
                            <button wire:click="startEditing"
                                class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </button>
                            <button wire:click="delete"
                                wire:confirm="Are you sure you want to delete this organization? This will remove it from all meetings."
                                class="px-3 py-2 text-sm font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 rounded-md hover:bg-red-200 dark:hover:bg-red-800">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $meetings->count() }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Meetings</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $people->count() }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">People</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $topIssues->count() }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Topics Discussed</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">0</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Projects (coming soon)</div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Top Issues -->
            @if($topIssues->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Areas Discussed</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($topIssues as $issue)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                {{ $issue->name }}
                                <span class="ml-2 text-xs bg-purple-200 dark:bg-purple-800 px-1.5 py-0.5 rounded-full">
                                    {{ $issue->meetings_count }}
                                </span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- People -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">People</h4>
                    <button wire:click="toggleAddPersonForm"
                        class="px-3 py-1.5 text-sm font-medium text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900 rounded-md hover:bg-green-200 dark:hover:bg-green-800">
                        @if($showAddPersonForm)
                            Cancel
                        @else
                            + Add Person
                        @endif
                    </button>
                </div>

                <!-- Add Person Form -->
                @if($showAddPersonForm)
                    <form wire:submit="addPerson" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <input type="text" wire:model="newPersonName" placeholder="Name *" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                                @error('newPersonName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <input type="text" wire:model="newPersonTitle" placeholder="Title (e.g., Director)"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                            </div>
                            <div>
                                <input type="email" wire:model="newPersonEmail" placeholder="Email"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                                @error('newPersonEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <input type="url" wire:model="newPersonLinkedIn" placeholder="LinkedIn URL"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                                @error('newPersonLinkedIn') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                Add Person
                            </button>
                        </div>
                    </form>
                @endif

                <!-- People List -->
                @if($people->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($people as $person)
                            <div class="flex items-center justify-between gap-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg group">
                                <a href="{{ route('people.show', $person) }}" wire:navigate class="flex items-center gap-3 flex-1 min-w-0">
                                    @if($person->photo_url)
                                        <img src="{{ $person->photo_url }}" alt="{{ $person->name }}" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                            {{ substr($person->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate hover:text-green-600 dark:hover:text-green-400">
                                            {{ $person->name }}
                                        </div>
                                        @if($person->title)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $person->title }}</div>
                                        @endif
                                    </div>
                                </a>
                                <button wire:click="removePerson({{ $person->id }})" wire:confirm="Remove this person from the organization?"
                                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-opacity"
                                    title="Remove from organization">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No people added yet. Click "Add Person" to add someone.</p>
                @endif
            </div>

            {{-- Media Coverage Section (only for media outlets) --}}
            @if(strtolower($organization->type ?? '') === 'media')
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg shadow-sm border border-indigo-100 dark:border-indigo-800 p-6">
                    {{-- Header with inline stats --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Media Coverage</h4>
                        </div>
                        {{-- Compact inline stats --}}
                        <div class="flex items-center gap-4 text-sm">
                            <span class="flex items-center gap-1.5">
                                <span class="font-semibold text-green-600 dark:text-green-400">{{ $pressClips->count() }}</span>
                                <span class="text-gray-500 dark:text-gray-400">clips</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $pitches->count() }}</span>
                                <span class="text-gray-500 dark:text-gray-400">pitches</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="font-semibold text-purple-600 dark:text-purple-400">{{ $mediaInquiries->count() }}</span>
                                <span class="text-gray-500 dark:text-gray-400">inquiries</span>
                            </span>
                        </div>
                    </div>

                    {{-- Press Clips --}}
                    @if($pressClips->count() > 0)
                        <div class="mb-6">
                            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Recent Coverage
                            </h5>
                            <div class="space-y-3">
                                @foreach($pressClips->take(5) as $clip)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-100 dark:border-gray-700">
                                        <div class="flex items-start gap-4">
                                            {{-- Article image or outlet logo --}}
                                            @if($clip->image_url)
                                                <a href="{{ $clip->url }}" target="_blank" class="flex-shrink-0">
                                                    <img src="{{ $clip->image_url }}" alt="" class="w-24 h-16 object-cover rounded-md">
                                                </a>
                                            @elseif($clip->outlet && $clip->outlet->logo_url)
                                                <a href="{{ $clip->url }}" target="_blank" class="flex-shrink-0">
                                                    <img src="{{ $clip->outlet->logo_url }}" alt="{{ $clip->outlet->name }}" class="w-16 h-16 object-contain rounded-md bg-gray-50 dark:bg-gray-700 p-1">
                                                </a>
                                            @else
                                                <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-md flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            
                                            <div class="flex-1 min-w-0">
                                                {{-- Title --}}
                                                @if($clip->url)
                                                    <a href="{{ $clip->url }}" target="_blank" class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 block">
                                                        {{ $clip->title ?: 'Untitled Clip' }}
                                                        <svg class="inline w-3 h-3 ml-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                        </svg>
                                                    </a>
                                                @else
                                                    <span class="font-medium text-gray-900 dark:text-white block">
                                                        {{ $clip->title ?: 'Untitled Clip' }}
                                                    </span>
                                                @endif

                                                {{-- Meta row --}}
                                                <div class="flex items-center flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    <span>{{ $clip->published_at?->format('M j, Y') }}</span>
                                                    @if($clip->clip_type !== 'article')
                                                        <span class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ ucfirst($clip->clip_type) }}</span>
                                                    @endif
                                                    @if($clip->journalist)
                                                        <span>•</span>
                                                        <a href="{{ route('people.show', $clip->journalist) }}" wire:navigate class="hover:text-indigo-600">
                                                            {{ $clip->journalist->name }}
                                                        </a>
                                                    @elseif($clip->journalist_name)
                                                        <span>• {{ $clip->journalist_name }}</span>
                                                    @endif
                                                </div>

                                                {{-- Summary/Quotes --}}
                                                @if($clip->summary || $clip->quotes)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2 italic">
                                                        "{{ Str::limit($clip->quotes ?? $clip->summary, 150) }}"
                                                    </p>
                                                @endif

                                                {{-- Staff Mentioned --}}
                                                @if($clip->staffMentioned && $clip->staffMentioned->count() > 0)
                                                    <div class="flex items-center gap-1 mt-2 text-xs text-indigo-600 dark:text-indigo-400">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                                        </svg>
                                                        {{ $clip->staffMentioned->pluck('name')->join(', ') }} quoted
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Sentiment badge --}}
                                            @php
                                                $sentimentColors = [
                                                    'positive' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                                    'neutral' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    'negative' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                                    'mixed' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                                ];
                                            @endphp
                                            <span class="text-xs px-2 py-0.5 rounded flex-shrink-0 {{ $sentimentColors[$clip->sentiment] ?? $sentimentColors['neutral'] }}">
                                                {{ ucfirst($clip->sentiment ?? 'neutral') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Pitches --}}
                    @if($pitches->count() > 0)
                        <div class="mb-6">
                            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                Recent Pitches
                            </h5>
                            <div class="space-y-2">
                                @foreach($pitches->take(5) as $pitch)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white block truncate">
                                                    {{ $pitch->subject ?: 'Untitled Pitch' }}
                                                </span>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ $pitch->created_at?->format('M j, Y') }}
                                                    @if($pitch->assignee)
                                                        • {{ $pitch->assignee->name }}
                                                    @endif
                                                </div>
                                            </div>
                                            @php
                                                $pitchStatusColors = [
                                                    'draft' => 'bg-gray-100 text-gray-700',
                                                    'sent' => 'bg-blue-100 text-blue-700',
                                                    'follow_up' => 'bg-yellow-100 text-yellow-700',
                                                    'accepted' => 'bg-green-100 text-green-700',
                                                    'declined' => 'bg-red-100 text-red-700',
                                                    'no_response' => 'bg-gray-100 text-gray-500',
                                                ];
                                            @endphp
                                            <span class="text-xs px-2 py-0.5 rounded {{ $pitchStatusColors[$pitch->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ ucfirst(str_replace('_', ' ', $pitch->status ?? 'draft')) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Inquiries --}}
                    @if($mediaInquiries->count() > 0)
                        <div>
                            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Recent Inquiries
                            </h5>
                            <div class="space-y-2">
                                @foreach($mediaInquiries->take(5) as $inquiry)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white block truncate">
                                                    {{ $inquiry->subject ?: 'Untitled Inquiry' }}
                                                </span>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    {{ $inquiry->received_at?->format('M j, Y') }}
                                                    @if($inquiry->journalist)
                                                        • {{ $inquiry->journalist->name }}
                                                    @endif
                                                </div>
                                            </div>
                                            @php
                                                $inquiryStatusColors = [
                                                    'new' => 'bg-blue-100 text-blue-700',
                                                    'in_progress' => 'bg-yellow-100 text-yellow-700',
                                                    'responded' => 'bg-green-100 text-green-700',
                                                    'declined' => 'bg-red-100 text-red-700',
                                                ];
                                            @endphp
                                            <span class="text-xs px-2 py-0.5 rounded {{ $inquiryStatusColors[$inquiry->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ ucfirst(str_replace('_', ' ', $inquiry->status ?? 'new')) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($pressClips->count() == 0 && $pitches->count() == 0 && $mediaInquiries->count() == 0)
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                            </svg>
                            <p>No media coverage recorded yet for this outlet.</p>
                            <a href="{{ route('media.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                                Go to Media & Press →
                            </a>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Projects</h4>
                    <button wire:click="toggleAddProjectModal" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
                </div>
                @if($projects->count())
                    <div class="space-y-2">
                        @foreach($projects as $project)
                            <div class="flex items-center justify-between py-2 group">
                                <div class="flex-1">
                                    <a href="{{ route('projects.show', $project) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">{{ $project->name }}</a>
                                    @if($project->pivot->role)
                                        <span class="ml-2 text-xs px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded">{{ $project->pivot->role }}</span>
                                    @endif
                                    @php
                                        $statusColors = [
                                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'on_hold' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            'archived' => 'bg-gray-100 text-gray-600',
                                        ];
                                    @endphp
                                    <span class="ml-2 text-xs px-1.5 py-0.5 rounded {{ $statusColors[$project->status] ?? 'bg-gray-100' }}">{{ ucfirst($project->status) }}</span>
                                </div>
                                <button wire:click="unlinkProject({{ $project->id }})" wire:confirm="Remove from this project?"
                                    class="opacity-0 group-hover:opacity-100 text-red-600 dark:text-red-400 text-xs">×</button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Not linked to any projects yet.</p>
                @endif
            </div>

            <!-- Documents & Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Documents & Notes</h4>
                </div>

                <!-- Upload Form -->
                <form wire:submit="uploadAttachment" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex-1">
                            <input type="file" wire:model="newAttachment"
                                class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700 dark:file:bg-indigo-900 dark:file:text-indigo-300 hover:file:bg-indigo-200 dark:hover:file:bg-indigo-800">
                            @error('newAttachment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <input type="text" wire:model="attachmentNotes" placeholder="Add notes (optional)"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="uploadAttachment">Upload</span>
                            <span wire:loading wire:target="uploadAttachment">Uploading...</span>
                        </button>
                    </div>
                    <div wire:loading wire:target="newAttachment" class="text-sm text-gray-500 mt-2">Processing file...
                    </div>
                </form>

                <!-- Attachments List -->
                @if($attachments->count() > 0)
                    <div class="space-y-2">
                        @foreach($attachments as $attachment)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <a href="{{ $attachment->url }}" target="_blank"
                                            class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $attachment->original_filename }}
                                        </a>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $attachment->human_size }} • {{ $attachment->created_at->format('M j, Y') }}
                                            @if($attachment->notes)
                                                • {{ $attachment->notes }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <button wire:click="deleteAttachment({{ $attachment->id }})"
                                    wire:confirm="Delete this document?"
                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm">
                                    Delete
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No documents uploaded yet.</p>
                @endif
            </div>

            <!-- Meetings Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Meetings</h4>
                </div>
                @if($meetings->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Title</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Date</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Attendees</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">View</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($meetings as $meeting)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                                {{ Str::limit($meeting->title ?: 'Untitled Meeting', 40) }}
                                            </div>
                                            @if($meeting->ai_summary)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                    {{ Str::limit($meeting->ai_summary, 50) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {{ $meeting->meeting_date->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-xs">
                                                @if($meeting->people->count() > 0)
                                                    {{ $meeting->people->take(2)->pluck('name')->join(', ') }}
                                                    @if($meeting->people->count() > 2)
                                                        <span class="text-gray-400">+{{ $meeting->people->count() - 2 }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400 italic">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                ];
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$meeting->status] ?? $statusColors['pending'] }}">
                                                {{ ucfirst($meeting->status ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('meetings.show', $meeting) }}" wire:navigate
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                                View →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        No meetings recorded yet with this organization.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Project Link Modal -->
    @if($showAddProjectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="toggleAddProjectModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Link to Project</h3>
                    <div class="space-y-4">
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="projectSearch" placeholder="Search projects..."
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @if($projectResults->count() && !$selectedProjectId)
                                <div class="mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md max-h-40 overflow-y-auto">
                                    @foreach($projectResults as $project)
                                        <button type="button" wire:click="selectProject({{ $project->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-white">
                                            {{ $project->name }}
                                            <span class="text-xs text-gray-500">{{ ucfirst($project->status) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role (optional)</label>
                            <input type="text" wire:model="projectRole" placeholder="e.g., Partner, Funder, Stakeholder"
                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button wire:click="toggleAddProjectModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                            <button wire:click="linkProject" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700" @if(!$selectedProjectId) disabled @endif>Link</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>