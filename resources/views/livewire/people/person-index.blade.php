<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Contacts</h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">Manage relationships like a CRM: assign owners, track
                status, and focus your outreach</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button wire:click="toggleAddPersonForm"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                @if($showAddPersonForm) Cancel @else Add Contact @endif
            </button>
        </div>
    </div>

    <!-- Search, Filters, and Add Button -->
    <div class="mb-6 grid grid-cols-1 lg:grid-cols-12 gap-3 items-center">
        <div class="lg:col-span-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, title, or email..."
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div class="lg:col-span-2">
            <select wire:model.live="filterOrg"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Organizations</option>
                @foreach($organizations as $org)
                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <select wire:model.live="filterStatus"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Any Status</option>
                @foreach($statuses as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <select wire:model.live="filterOwner"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Any Owner</option>
                @foreach($owners as $o)
                    <option value="{{ $o->id }}">{{ $o->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <input type="text" wire:model.live.debounce.300ms="filterTag" placeholder="Tag filter"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <div class="lg:col-span-12 flex items-center gap-3 justify-between mt-2">
            <div class="flex items-center gap-2">
                <!-- View Toggle -->
                <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-1">
                    <button wire:click="setViewMode('card')"
                        class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'card' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button wire:click="setViewMode('table')"
                        class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <!-- Trash Toggle -->
                <button wire:click="toggleTrashed"
                    class="relative px-3 py-1.5 text-sm font-medium rounded-lg transition-colors {{ $showTrashed ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    @if($trashedCount > 0)
                        <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none {{ $showTrashed ? 'bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-200' : 'bg-red-500 text-white' }} rounded-full">{{ $trashedCount }}</span>
                    @endif
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button wire:click="openImportModal"
                    class="px-4 py-2 text-sm font-semibold text-indigo-700 bg-white border border-indigo-200 rounded-md hover:bg-indigo-50 shadow-sm">
                    Import CSV
                </button>

                <div class="flex items-center gap-2">
                    <input type="text" wire:model.defer="newViewName" placeholder="Save current filters as…"
                        class="px-3 py-2 text-sm rounded-md border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <button wire:click="saveView"
                        class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm">
                        Save View
                    </button>
                </div>

                <button wire:click="toggleAddPersonForm"
                    class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm">
                    @if($showAddPersonForm)
                        Cancel
                    @else
                        + Add Contact
                    @endif
                </button>
            </div>
        </div>
    </div>

    {{-- Saved Views --}}
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Saved Views</h3>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ count($views) }} saved</div>
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
            @forelse($views as $v)
                <div
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-gray-200 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">
                    <button wire:click="loadView({{ $v['id'] }})"
                        class="text-indigo-700 dark:text-indigo-400 hover:underline">
                        {{ $v['name'] }}
                    </button>
                    <button wire:click="deleteView({{ $v['id'] }})"
                        class="text-gray-500 hover:text-red-600 text-xs">×</button>
                </div>
            @empty
                <div class="text-xs text-gray-500 dark:text-gray-400">No saved views yet.</div>
            @endforelse
        </div>
    </div>


    <!-- Add Contact Modal with Tabs -->
    @if($showAddPersonForm)
        <div
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Contacts</h3>
                <button wire:click="toggleAddPersonForm" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex px-6 -mb-px">
                    <button wire:click="$set('addModalTab', 'single')"
                        class="px-4 py-3 text-sm font-medium border-b-2 {{ $addModalTab === 'single' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        Single Contact
                    </button>
                    <button wire:click="$set('addModalTab', 'bulk')"
                        class="px-4 py-3 text-sm font-medium border-b-2 {{ $addModalTab === 'bulk' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        ✨ AI Bulk Extract
                    </button>
                    <button wire:click="$set('addModalTab', 'csv')"
                        class="px-4 py-3 text-sm font-medium border-b-2 {{ $addModalTab === 'csv' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        CSV Upload
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- Single Contact Tab -->
                @if($addModalTab === 'single')
                    <form wire:submit="addPerson" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                <input type="text" wire:model="newPersonName" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('newPersonName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organization</label>
                                <div x-data="{ showNewOrg: false }">
                                    <div x-show="!showNewOrg">
                                        <select wire:model="newPersonOrgId"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Select organization...</option>
                                            @foreach($organizations as $org)
                                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" @click="showNewOrg = true"
                                            class="mt-1 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add New Org
                                        </button>
                                    </div>
                                    <div x-show="showNewOrg" x-cloak class="space-y-2">
                                        <input type="text" wire:model="newOrgName" placeholder="New organization name"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <button type="button" @click="showNewOrg = false; $wire.set('newOrgName', '')"
                                            class="text-xs text-gray-500 hover:text-gray-700">
                                            ← Back to select
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                <input type="text" wire:model="newPersonTitle" placeholder="e.g., Director"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input type="email" wire:model="newPersonEmail" placeholder="email@example.com"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('newPersonEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn
                                    URL</label>
                                <input type="url" wire:model="newPersonLinkedIn" placeholder="https://linkedin.com/in/..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('newPersonLinkedIn') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="toggleAddPersonForm"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm">
                                Add Contact
                            </button>
                        </div>
                    </form>
                @endif

                <!-- Bulk AI Extract Tab -->
                @if($addModalTab === 'bulk')
                    @if(!$showExtractedPreview)
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span class="font-medium">AI-Powered Contact Extraction</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Paste email signatures, meeting notes, contact lists, or any text. AI will extract names, titles,
                                organizations, emails, and phone numbers.
                            </p>
                            <textarea wire:model="bulkText" rows="8"
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Paste text here...

                                                            Example:
                                                            John Smith
                                                            Senior Policy Analyst
                                                            Congressional Research Service
                                                            john.smith@crs.gov
                                                            (202) 555-1234

                                                            Jane Doe - Director of Communications
                                                            House Modernization Committee
                                                            jane.doe@house.gov"></textarea>
                            <div class="flex justify-end gap-3">
                                <button wire:click="toggleAddPersonForm" type="button"
                                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">
                                    Cancel
                                </button>
                                <button wire:click="extractPeopleFromText" type="button"
                                    class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm flex items-center gap-2"
                                    wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                    wire:target="extractPeopleFromText">
                                    <span wire:loading.remove wire:target="extractPeopleFromText">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </span>
                                    <span wire:loading wire:target="extractPeopleFromText">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </span>
                                    <span wire:loading.remove wire:target="extractPeopleFromText">Extract Contacts</span>
                                    <span wire:loading wire:target="extractPeopleFromText">Extracting...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Extracted Preview -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    Extracted {{ count($extractedPeople) }} contacts
                                </h4>
                                <button wire:click="$set('showExtractedPreview', false)"
                                    class="text-sm text-gray-500 hover:text-gray-700">
                                    ← Back to text
                                </button>
                            </div>
                            <div class="max-h-64 overflow-y-auto space-y-2">
                                @foreach($extractedPeople as $index => $person)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $person['name'] ?? 'Unknown' }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                @if(!empty($person['title'])){{ $person['title'] }}@endif
                                                @if(!empty($person['organization'])) @ {{ $person['organization'] }}@endif
                                            </div>
                                            @if(!empty($person['email']))
                                                <div class="text-xs text-gray-500">{{ $person['email'] }}</div>
                                            @endif
                                        </div>
                                        <button wire:click="removeExtractedPerson({{ $index }})"
                                            class="text-gray-400 hover:text-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-end gap-3">
                                <button wire:click="toggleAddPersonForm" type="button"
                                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">
                                    Cancel
                                </button>
                                <button wire:click="saveExtractedPeople" type="button"
                                    class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm">
                                    Save {{ count($extractedPeople) }} Contacts
                                </button>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- CSV Upload Tab -->
                @if($addModalTab === 'csv')
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Upload a CSV with columns: name, email, organization, title, phone, source, status, tags (comma or
                            |), owner_email
                        </p>
                        <form wire:submit.prevent="importContacts" class="space-y-4">
                            <input type="file" wire:model="importFile" accept=".csv,.txt"
                                class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            @if(!empty($importReport))
                                <div class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg text-sm">
                                    <div class="font-medium text-green-800 dark:text-green-300 mb-2">Import Complete!</div>
                                    <div>Created: <span class="font-semibold">{{ $importReport['created'] ?? 0 }}</span></div>
                                    <div>Updated: <span class="font-semibold">{{ $importReport['updated'] ?? 0 }}</span></div>
                                    <div>Skipped: <span class="font-semibold">{{ $importReport['skipped'] ?? 0 }}</span></div>
                                </div>
                            @endif
                            <div class="flex justify-end gap-3">
                                <button type="button" wire:click="toggleAddPersonForm"
                                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-md hover:bg-green-700 shadow-sm"
                                    wire:loading.attr="disabled" wire:target="importFile,importContacts">
                                    Import CSV
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($viewMode === 'card')
        {{-- Bulk Actions Toolbar --}}
        @if(count($selected) > 0)
            <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-3 text-sm">
                <div class="text-gray-700 dark:text-gray-300 font-medium">{{ count($selected) }} selected</div>

                @if($showTrashed)
                    {{-- Trash mode actions --}}
                    <button wire:click="bulkRestore"
                        class="px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Restore
                    </button>
                    <button wire:click="confirmBulkDelete"
                        class="px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded hover:bg-red-700 transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Forever
                    </button>
                @else
                    {{-- Active mode: Actions dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                            class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded hover:bg-indigo-700 transition-colors flex items-center gap-1">
                            Actions
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                            class="absolute left-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 py-1">

                            {{-- Assign Owner --}}
                            <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Assign Owner</div>
                                <div class="flex items-center gap-1">
                                    <select wire:model="bulkOwnerId"
                                        class="flex-1 text-sm rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white py-1">
                                        <option value="">Select…</option>
                                        @foreach($owners as $o)
                                            <option value="{{ $o->id }}">{{ $o->name }}</option>
                                        @endforeach
                                    </select>
                                    <button wire:click="applyBulkOwner" @click="open = false"
                                        class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">Go</button>
                                </div>
                            </div>

                            {{-- Change Status --}}
                            <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Change Status</div>
                                <div class="flex items-center gap-1">
                                    <select wire:model="bulkStatus"
                                        class="flex-1 text-sm rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white py-1">
                                        <option value="">Select…</option>
                                        @foreach($statuses as $k => $label)
                                            <option value="{{ $k }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button wire:click="applyBulkStatus" @click="open = false"
                                        class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">Go</button>
                                </div>
                            </div>

                            {{-- Add Tag --}}
                            <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Add Tag</div>
                                <div class="flex items-center gap-1">
                                    <input type="text" wire:model.defer="bulkTag" placeholder="Tag name"
                                        class="flex-1 text-sm rounded border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white py-1 px-2">
                                    <button wire:click="applyBulkAddTag" @click="open = false"
                                        class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">Go</button>
                                </div>
                            </div>

                            {{-- Delete --}}
                            <button wire:click="confirmBulkDelete" @click="open = false"
                                class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Move to Trash
                            </button>
                        </div>
                    </div>
                @endif

                <button wire:click="clearSelection" class="ml-auto text-xs text-gray-500 hover:text-gray-700">Clear</button>
            </div>
        @endif

        <!-- People Card Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($people as $person)
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Card Header with Avatar --}}
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" wire:model="selected" value="{{ $person->id }}"
                                class="mt-2 w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                onclick="event.stopPropagation()">
                            @if($person->photo_url)
                                <x-avatar :name="$person->name" :photo="$person->photo_url" size="lg" />
                            @else
                                <x-avatar :name="$person->name" size="lg" />
                            @endif
                            <a href="{{ route('people.show', $person) }}" wire:navigate class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 dark:text-white truncate hover:text-indigo-600">
                                    {{ $person->name }}
                                </h3>
                                @if($person->title)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $person->title }}</p>
                                @endif
                                @if($person->organization)
                                    <p class="text-sm text-indigo-600 dark:text-indigo-400 truncate">
                                        {{ $person->organization->name }}
                                    </p>
                                @endif
                            </a>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="p-4 space-y-3">
                        {{-- Contact Info --}}
                        <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $person->meetings_count }} {{ Str::plural('meeting', $person->meetings_count) }}
                            </span>
                            @if($person->email)
                                <span class="inline-flex items-center gap-1 truncate">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="truncate max-w-[120px]">{{ $person->email }}</span>
                                </span>
                            @endif
                        </div>

                        {{-- Status & Owner Pills --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
                                    'not_engaged' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    'retired' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                ];
                            @endphp
                            @if($person->status)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$person->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statuses[$person->status] ?? ucfirst($person->status) }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                    No status
                                </span>
                            @endif
                            @if($person->owner)
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ $person->owner->name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Card actions --}}
                    <div class="px-4 pb-3 flex justify-end gap-1">
                        @if($showTrashed)
                            <button wire:click="restorePerson({{ $person->id }})"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs text-green-600 hover:text-green-800 hover:bg-green-50 dark:hover:bg-green-900/30 rounded transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                Restore
                            </button>
                            <button wire:click="permanentlyDeletePerson({{ $person->id }})"
                                wire:confirm="This will PERMANENTLY delete this contact. This cannot be undone."
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Forever
                            </button>
                        @else
                            <button wire:click="deletePerson({{ $person->id }})"
                                wire:confirm="Move this contact to trash?"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p>No people found.</p>
                </div>
            @endforelse
        </div>
    @else
        <!-- People Table View with Inline Editing -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Organization</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Phone</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($people as $person)
                        @if($editingPersonId === $person->id)
                            {{-- Editing Row --}}
                            <tr class="bg-indigo-50 dark:bg-indigo-900/20">
                                <td class="px-4 py-2">
                                    <input type="text" wire:model="editName"
                                        class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Name">
                                    @error('editName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" wire:model="editTitle"
                                        class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Title">
                                </td>
                                <td class="px-4 py-2">
                                    <select wire:model="editOrgId"
                                        class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">No Organization</option>
                                        @foreach($organizations as $org)
                                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="email" wire:model="editEmail"
                                        class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="email@example.com">
                                    @error('editEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" wire:model="editPhone"
                                        class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Phone">
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-xs text-gray-500">—</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-1">
                                        <button wire:click="saveInlineEdit"
                                            class="p-1.5 rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/60"
                                            title="Save">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                        <button wire:click="cancelEditing"
                                            class="p-1.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600"
                                            title="Cancel">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            {{-- Display Row --}}
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 group"
                                x-data="{ hover: false }"
                                @mouseenter="hover = true"
                                @mouseleave="hover = false">
                                <td class="px-4 py-3">
                                    <a href="{{ route('people.show', $person) }}" wire:navigate class="flex items-center gap-3 hover:text-indigo-600">
                                        @if($person->photo_url)
                                            <x-avatar :name="$person->name" :photo="$person->photo_url" size="sm" :showRing="false" />
                                        @else
                                            <x-avatar :name="$person->name" size="sm" :showRing="false" />
                                        @endif
                                        <span class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $person->name }}</span>
                                        @if($person->is_journalist)
                                            <span class="text-xs" title="Journalist">📰</span>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $person->title ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($person->organization)
                                        <a href="{{ route('organizations.show', $person->organization) }}" wire:navigate
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $person->organization->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    @if($person->email)
                                        <a href="mailto:{{ $person->email }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $person->email }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    @if($person->phone)
                                        <a href="tel:{{ $person->phone }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $person->phone }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <select wire:change="updateField({{ $person->id }}, 'status', $event.target.value)"
                                        class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach($statuses as $k => $label)
                                            <option value="{{ $k }}" {{ $person->status === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        @if($showTrashed)
                                            <button wire:click="restorePerson({{ $person->id }})"
                                                class="p-1.5 rounded text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/40"
                                                title="Restore">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </button>
                                            <button wire:click="permanentlyDeletePerson({{ $person->id }})"
                                                wire:confirm="This will PERMANENTLY delete this contact. This cannot be undone."
                                                class="p-1.5 rounded text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/40"
                                                title="Delete Forever">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            <button wire:click="startEditing({{ $person->id }})"
                                                class="p-1.5 rounded text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/40"
                                                title="Edit inline">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <a href="{{ route('people.show', $person) }}" wire:navigate
                                                class="p-1.5 rounded text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/40"
                                                title="View details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <button wire:click="deletePerson({{ $person->id }})"
                                                wire:confirm="Move this contact to trash?"
                                                class="p-1.5 rounded text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/40"
                                                title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No contacts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Pagination -->
    @if($people->hasPages() || $people->count() > 0)
        <div class="mt-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Show</span>
                <select wire:model.live="perPage"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-500 dark:text-gray-400">per page</span>
            </div>
            <div>
                {{ $people->links() }}
            </div>
        </div>
    @endif
</div>
</div>

{{-- Import CSV Modal --}}
@if($showImportModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/70" wire:click="closeImportModal"></div>
        <div
            class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Import Contacts (CSV)</h3>
                <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" wire:click="closeImportModal">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Accepted columns (header names): name, email,
                    organization, title, phone, source, status, tags (comma or |), owner_email</p>
                <form wire:submit.prevent="importContacts" class="space-y-3">
                    <input type="file" wire:model="importFile"
                        class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Import</button>
                    </div>
                </form>

                @if(!empty($importReport))
                    <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                        <div>Created: <span class="font-semibold">{{ $importReport['created'] ?? 0 }}</span></div>
                        <div>Updated: <span class="font-semibold">{{ $importReport['updated'] ?? 0 }}</span></div>
                        <div>Skipped: <span class="font-semibold">{{ $importReport['skipped'] ?? 0 }}</span></div>
                    </div>
                @endif
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-right">
                <button
                    class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
                    wire:click="closeImportModal">Close</button>
            </div>
        </div>
    </div>
@endif
</div>

{{-- Bulk Delete Confirmation Modal --}}
@if($confirmingBulkDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/70" wire:click="cancelBulkDelete"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/40 mb-4">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                @if($showTrashed)
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Permanently Delete {{ count($selected) }} Contact(s)?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        This action is <strong>permanent</strong> and cannot be undone. All meeting and project associations will be removed.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button wire:click="cancelBulkDelete"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="bulkPermanentlyDelete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Delete Forever
                        </button>
                    </div>
                @else
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Move {{ count($selected) }} Contact(s) to Trash?</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        These contacts will be moved to the trash. You can restore them later from the trash view.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button wire:click="cancelBulkDelete"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button wire:click="bulkDelete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Move to Trash
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif