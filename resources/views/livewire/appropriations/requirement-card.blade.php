<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden">
    {{-- Header with Status Badge --}}
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white leading-tight flex-1">
                {{ $requirement->report_title }}
            </h3>
            <span class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap
                @if($requirement->effective_status === 'submitted') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                @elseif($requirement->effective_status === 'overdue') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                @elseif($requirement->effective_status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300
                @endif">
                {{ ucfirst(str_replace('_', ' ', $requirement->effective_status)) }}
            </span>
        </div>
    </div>

    {{-- Body --}}
    <div class="p-4 space-y-3">
        {{-- Agency --}}
        <div class="flex items-center text-sm">
            <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span class="font-medium text-gray-900 dark:text-white">{{ $requirement->responsible_agency }}</span>
        </div>

        {{-- Due Date --}}
        @if($requirement->due_date)
            <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-gray-700 dark:text-gray-300">
                    Due: {{ $requirement->due_date->format('M d, Y') }}
                    @if($requirement->days_until_due !== null && $requirement->status !== 'submitted')
                        <span class="ml-1
                            @if($requirement->days_until_due < 0) text-red-600 font-semibold
                            @elseif($requirement->days_until_due < 14) text-orange-600 font-medium
                            @else text-gray-500 dark:text-gray-400
                            @endif">
                            ({{ abs($requirement->days_until_due) }} days {{ $requirement->days_until_due < 0 ? 'overdue' : 'left' }})
                        </span>
                    @endif
                </span>
            </div>
        @endif

        {{-- Timeline & Category --}}
        <div class="flex items-center gap-2 text-xs">
            <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                {{ ucfirst(str_replace('_', ' ', $requirement->category)) }}
            </span>
            @if($requirement->timeline_value)
                <span class="px-2 py-1 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                    {{ $requirement->timeline_value }} days
                </span>
            @endif
            @if($requirement->source_page_reference)
                <span class="text-gray-500 dark:text-gray-400">{{ $requirement->source_page_reference }}</span>
            @endif
        </div>

        {{-- Assigned To --}}
        @if($requirement->assignedTo)
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ $requirement->assignedTo->name }}
            </div>
        @endif

        {{-- Expandable Description --}}
        @if($showDetails)
            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-3">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <div class="font-medium text-gray-900 dark:text-white mb-1">Description:</div>
                    <p>{{ $requirement->description }}</p>
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <div class="font-medium text-gray-900 dark:text-white mb-1">Recipients:</div>
                    <p>{{ $requirement->reporting_recipients }}</p>
                </div>
                @if($requirement->notes)
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <div class="font-medium text-gray-900 dark:text-white mb-1">Notes:</div>
                        <p>{{ $requirement->notes }}</p>
                    </div>
                @endif
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Source: {{ $requirement->legislativeReport?->display_name }}
                </div>
            </div>
        @endif
    </div>

    {{-- Footer Actions --}}
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <button wire:click="toggleDetails" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
            {{ $showDetails ? 'Hide Details' : 'Show Details' }}
        </button>

        <div class="flex items-center gap-2">
            <button wire:click="openEditModal" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </button>

            @if($requirement->status !== 'submitted')
                @if($requirement->status === 'pending')
                    <button wire:click="updateStatus('in_progress')"
                        class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 dark:bg-blue-900/30 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded">
                        Start
                    </button>
                @endif

                <button wire:click="updateStatus('submitted')"
                    class="px-2 py-1 text-xs font-medium text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/50 rounded">
                    Mark Submitted
                </button>
            @else
                <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                    ✓ {{ $requirement->completed_at?->format('M d') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEditModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="saveEdit">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                Edit Requirement
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select wire:model="editStatus" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="submitted">Submitted</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assigned To</label>
                                    <select wire:model="editAssignedTo" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Unassigned</option>
                                        @foreach($teamMembers as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                    <textarea wire:model="editNotes" rows="3"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Add notes..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Changes
                            </button>
                            <button type="button" wire:click="closeEditModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-600 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

