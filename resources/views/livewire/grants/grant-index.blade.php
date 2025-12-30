<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Funders & Reporting</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage funders, grants, and reporting requirements</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openCreateGrantModal"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Add Grant
            </button>
            <button wire:click="openCreateFunderModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Funder
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex gap-6 overflow-x-auto">
            <button wire:click="setTab('dashboard')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'dashboard' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Dashboard
            </button>
            <button wire:click="setTab('funders')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'funders' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Funders
                <span class="ml-1.5 px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">{{ $stats['total_funders'] }}</span>
            </button>
            <button wire:click="setTab('grants')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'grants' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Grants
                <span class="ml-1.5 px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">{{ $stats['total_grants'] }}</span>
            </button>
            <button wire:click="setTab('reports')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'reports' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Reports
                @if($stats['reports_overdue'] > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 text-xs bg-red-100 text-red-700 rounded-full">{{ $stats['reports_overdue'] }} overdue</span>
                @elseif($stats['reports_due_soon'] > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $stats['reports_due_soon'] }} due</span>
                @endif
            </button>
        </nav>
    </div>

    {{-- Dashboard Tab --}}
    @if($activeTab === 'dashboard')
        @include('livewire.grants.partials.dashboard-tab')
    @endif

    {{-- Funders Tab --}}
    @if($activeTab === 'funders')
        @include('livewire.grants.partials.funders-tab')
    @endif

    {{-- Grants Tab --}}
    @if($activeTab === 'grants')
        @include('livewire.grants.partials.grants-tab')
    @endif

    {{-- Reports Tab --}}
    @if($activeTab === 'reports')
        @include('livewire.grants.partials.reports-tab')
    @endif

    {{-- Create/Edit Funder Modal --}}
    @if($showFunderModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeFunderModal"></div>
                <div
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="saveFunder">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $editingFunderId ? 'Edit Funder' : 'Add Funder' }}
                            </h3>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            {{-- Organization Search/Name Input --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Organization Name *
                                </label>
                                @if(!$editingFunderId)
                                    <input type="text" 
                                        wire:model.live.debounce.300ms="orgSearch"
                                        placeholder="Search existing organizations or enter new name..."
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        autocomplete="off">
                                    
                                    {{-- Organization Suggestions Dropdown --}}
                                    @if($showOrgSuggestions && $this->orgSuggestions->count() > 0)
                                        <div class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-48 overflow-y-auto">
                                            @foreach($this->orgSuggestions as $org)
                                                <button type="button"
                                                    wire:click="selectOrganization({{ $org->id }})"
                                                    class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                                                        {{ strtoupper(substr($org->name, 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $org->name }}</div>
                                                        @if($org->type)
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $org->type }}</div>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($selectedOrgId)
                                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">
                                            ✓ Using existing organization - fields auto-populated
                                        </p>
                                    @elseif(strlen($orgSearch) >= 2 && $this->orgSuggestions->count() === 0)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            No matches found. A new organization will be created.
                                        </p>
                                    @endif

                                    {{-- Hidden input to sync funderName when creating new --}}
                                    <input type="hidden" wire:model="funderName" x-init="$watch('$wire.orgSearch', value => { if (!$wire.selectedOrgId) $wire.funderName = value })">
                                @else
                                    <input type="text" wire:model="funderName"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @endif
                                @error('funderName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                <select wire:model="funderType"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="Funder">Funder/Foundation</option>
                                    <option value="Government Agency">Government Agency</option>
                                    <option value="Business">Corporate/Business</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                                <input type="url" wire:model="funderWebsite" placeholder="https://..."
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn URL</label>
                                <input type="url" wire:model="funderLinkedIn" placeholder="https://linkedin.com/company/..."
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <p class="text-xs text-gray-500 mt-1">Logo will be automatically synced from LinkedIn</p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model="funderDescription" rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Funding
                                    Priorities / Notes</label>
                                <textarea wire:model="funderPriorities" rows="3"
                                    placeholder="What do they typically fund? Key contacts? Notes on alignment..."
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                            <button type="button" wire:click="closeFunderModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                {{ $editingFunderId ? 'Save Changes' : 'Add Funder' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Create/Edit Grant Modal --}}
    @if($showGrantModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <div
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="saveGrant">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $editingGrantId ? 'Edit Grant' : 'Add Grant' }}
                            </h3>
                        </div>
                        <div class="px-6 py-4 space-y-4 max-h-96 overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grant Name
                                    *</label>
                                <input type="text" wire:model="grantName"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('grantName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Funder
                                    *</label>
                                <select wire:model="grantFunderId"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select funder...</option>
                                    @foreach($funders as $funder)
                                        <option value="{{ $funder->id }}">{{ $funder->name }}</option>
                                    @endforeach
                                </select>
                                @error('grantFunderId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                                    <input type="number" wire:model="grantAmount" step="0.01" min="0"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select wire:model="grantStatus"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @foreach($statuses as $k => $label)
                                            <option value="{{ $k }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start
                                        Date</label>
                                    <input type="date" wire:model="grantStartDate"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End
                                        Date</label>
                                    <input type="date" wire:model="grantEndDate"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model="grantDescription" rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Visibility</label>
                                <select wire:model="grantVisibility"
                                    class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="admin">Admin Only</option>
                                    <option value="management">Management</option>
                                    <option value="all">All Staff</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Scope</label>
                                    <select wire:model.live="grantScope"
                                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @foreach($scopes as $k => $label)
                                            <option value="{{ $k }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if($grantScope === 'project')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Primary
                                            Project</label>
                                        <select wire:model="grantPrimaryProjectId"
                                            class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Select project...</option>
                                            @foreach($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                            <button type="button" wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                {{ $editingGrantId ? 'Save Changes' : 'Create Grant' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>