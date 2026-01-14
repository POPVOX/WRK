<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Organizations</h1>
            <p class="mt-1 text-gray-500 dark:text-gray-400">View and manage stakeholder organizations and partners</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button wire:click="openAddModal"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Organization
            </button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center">
        <div class="flex-1">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search organizations..."
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>
        <div>
            <select wire:model.live="filterType"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">All Types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <!-- View Toggle -->
        <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-1">
            <button wire:click="setViewMode('card')"
                class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'card' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            </button>
            <button wire:click="setViewMode('table')"
                class="px-3 py-1.5 text-sm font-medium rounded {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

            @if(session('message'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-300">
                    {{ session('message') }}
                </div>
            @endif

            @if($viewMode === 'card')
                <!-- Organizations Card Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($organizations as $org)
                        <a href="{{ route('organizations.show', $org) }}" wire:navigate
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    @if($org->logo_url)
                                        <img src="{{ $org->logo_url }}" alt="{{ $org->name }}"
                                            class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                            {{ strtoupper(substr($org->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $org->name }}
                                        </h3>
                                        @if($org->type)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 mt-1">
                                                {{ $org->type }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>

                            <div class="mt-4 flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $org->meetings_count }} {{ Str::plural('meeting', $org->meetings_count) }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ $org->people_count }} {{ Str::plural('person', $org->people_count) }}
                                </div>
                            </div>

                            @if($org->website)
                                <div class="mt-2 text-sm text-indigo-600 dark:text-indigo-400 truncate">
                                    {{ $org->display_website }}
                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <p>No organizations found.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <!-- Organizations Table View -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Website</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Meetings</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">People</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($organizations as $org)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" onclick="window.location='{{ route('organizations.show', $org) }}'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $org->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($org->type)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                                {{ $org->type }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 dark:text-indigo-400 truncate max-w-xs">
                                        {{ $org->display_website ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ $org->meetings_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ $org->people_count }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        No organizations found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Pagination -->
            @if($organizations->hasPages())
                <div class="mt-6">
                    {{ $organizations->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Add Organization Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Organization</h3>
                    <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Mode Tabs --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button wire:click="setAddMode('single')"
                        class="flex-1 px-4 py-3 text-sm font-medium {{ $addMode === 'single' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        Single
                    </button>
                    <button wire:click="setAddMode('bulk')"
                        class="flex-1 px-4 py-3 text-sm font-medium {{ $addMode === 'bulk' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        Bulk Text
                    </button>
                    <button wire:click="setAddMode('csv')"
                        class="flex-1 px-4 py-3 text-sm font-medium {{ $addMode === 'csv' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        CSV Upload
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6">
                    {{-- Single Organization Form --}}
                    @if($addMode === 'single')
                        <form wire:submit="saveSingleOrg" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                <input type="text" wire:model="orgName"
                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Organization name">
                                @error('orgName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Abbreviation</label>
                                    <input type="text" wire:model="orgAbbreviation"
                                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                        placeholder="e.g. ACME">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                    <select wire:model="orgType"
                                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="">Select type...</option>
                                        @foreach($types as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                                <input type="url" wire:model="orgWebsite"
                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="https://example.com">
                                @error('orgWebsite') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn URL</label>
                                <input type="url" wire:model="orgLinkedIn"
                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="https://linkedin.com/company/...">
                                <p class="text-xs text-gray-500 mt-1">Logo will be automatically synced from LinkedIn</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model="orgDescription" rows="3"
                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Brief description..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" wire:click="closeAddModal"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                                    Create Organization
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- Bulk Text Input --}}
                    @if($addMode === 'bulk')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Text to Process</label>
                                <textarea wire:model="bulkText" rows="8"
                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                    placeholder="Paste any text here... meeting notes, emails, lists, etc. AI will extract organization names, or enter one org per line for direct import."></textarea>
                                @error('bulkText') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Apply Type to All (optional)</label>
                                <select wire:model="bulkType"
                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">No type</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- AI Extraction Section --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Import Method:</span>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="extractOrgsWithAI"
                                            class="inline-flex items-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                                            {{ $isExtracting ? 'disabled' : '' }}>
                                            @if($isExtracting)
                                                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                                Extracting...
                                            @else
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                                </svg>
                                                Extract with AI
                                            @endif
                                        </button>
                                        <button type="button" wire:click="importBulkText"
                                            class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                                            Line-by-Line
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                    <strong>Extract with AI:</strong> Paste any text and AI will find organization names.
                                    <strong>Line-by-Line:</strong> Enter one organization name per line for direct import.
                                </p>
                            </div>

                            {{-- Extracted Organizations Preview --}}
                            @if(count($extractedOrgs) > 0)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        Found {{ count($extractedOrgs) }} organization(s) - uncheck any you don't want to import:
                                    </p>
                                    <div class="space-y-2 max-h-48 overflow-y-auto">
                                        @foreach($extractedOrgs as $index => $org)
                                            <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 {{ $org['exists'] ? 'opacity-50' : '' }}">
                                                <input type="checkbox"
                                                    wire:click="toggleExtractedOrg({{ $index }})"
                                                    {{ $org['selected'] ? 'checked' : '' }}
                                                    {{ $org['exists'] ? 'disabled' : '' }}
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-sm text-gray-900 dark:text-white">{{ $org['name'] }}</span>
                                                @if($org['exists'])
                                                    <span class="text-xs text-amber-600 dark:text-amber-400">(already exists)</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="flex justify-end mt-4">
                                        <button type="button" wire:click="importExtractedOrgs"
                                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                                            Import Selected
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if($importedCount > 0)
                                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-700 dark:text-green-300 text-sm">
                                    ✓ Imported {{ $importedCount }} organization(s)
                                </div>
                            @endif

                            @if(count($importErrors) > 0)
                                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg text-yellow-700 dark:text-yellow-300 text-sm max-h-32 overflow-y-auto">
                                    <strong>Skipped:</strong>
                                    <ul class="mt-1 list-disc list-inside">
                                        @foreach($importErrors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="flex justify-end pt-4">
                                <button type="button" wire:click="closeAddModal"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    Close
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- CSV Upload --}}
                    @if($addMode === 'csv')
                        <form wire:submit="importCsv" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSV File</label>
                                <input type="file" wire:model="csvFile" accept=".csv,.txt"
                                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                                @error('csvFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Expected columns: Name, Abbreviation, Type, Website, Description
                                </p>
                            </div>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="csvHasHeader" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">First row is header</span>
                            </label>

                            @if(count($csvPreview) > 0)
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview (first {{ count($csvPreview) }} rows):</p>
                                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
                                        <table class="min-w-full text-xs">
                                            @foreach($csvPreview as $row)
                                                <tr class="{{ $loop->first && $csvHasHeader ? 'bg-gray-100 dark:bg-gray-700 font-medium' : '' }}">
                                                    @foreach($row as $cell)
                                                        <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-600">{{ str($cell)->limit(30) }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                            @endif

                            @if($importedCount > 0)
                                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-700 dark:text-green-300 text-sm">
                                    ✓ Imported {{ $importedCount }} organization(s)
                                </div>
                            @endif

                            @if(count($importErrors) > 0)
                                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg text-yellow-700 dark:text-yellow-300 text-sm max-h-32 overflow-y-auto">
                                    <strong>Skipped:</strong>
                                    <ul class="mt-1 list-disc list-inside">
                                        @foreach($importErrors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" wire:click="closeAddModal"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                                    Import CSV
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>