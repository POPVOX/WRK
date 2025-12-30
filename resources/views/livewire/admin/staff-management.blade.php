<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Staff Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Action Bar -->
            <div class="mb-6 flex items-center justify-between">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search staff..."
                    class="w-full md:w-96 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                
                <button wire:click="openAddModal"
                    class="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Staff Member
                </button>
            </div>

            <!-- Staff Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joined</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($staff as $member)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-medium">
                                                {{ substr($member->name, 0, 1) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $member->name }}
                                                    @if($member->id === auth()->id())
                                                        <span class="text-xs text-gray-500">(you)</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ $member->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($member->is_admin)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                                Admin
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300">
                                                Staff
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ $member->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button wire:click="toggleAdmin({{ $member->id }})"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            {{ $member->is_admin ? 'Remove Admin' : 'Make Admin' }}
                                        </button>
                                        @if($member->id !== auth()->id())
                                            <button wire:click="deleteStaff({{ $member->id }})" 
                                                wire:confirm="Are you sure you want to remove this staff member?"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                Delete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        No staff members found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    @if($showAddModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeAddModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            Add New Staff Member
                        </h3>

                        @if($tempPassword)
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-green-800 dark:text-green-300 mb-2">Staff Member Created!</h4>
                                <p class="text-sm text-green-700 dark:text-green-400 mb-2">
                                    Share this temporary password with them:
                                </p>
                                <code class="block bg-green-100 dark:bg-green-800 px-3 py-2 rounded text-green-900 dark:text-green-100 font-mono">
                                    {{ $tempPassword }}
                                </code>
                                <p class="text-xs text-green-600 dark:text-green-500 mt-2">
                                    They should change this password after logging in.
                                </p>
                            </div>
                        @else
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                    <input type="text" wire:model="newName" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="Full name">
                                    @error('newName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                    <input type="email" wire:model="newEmail" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="email@example.com">
                                    @error('newEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="newIsAdmin" id="newIsAdmin"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <label for="newIsAdmin" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Make this user an administrator
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        @if($tempPassword)
                            <button type="button" wire:click="closeAddModal"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Done
                            </button>
                        @else
                            <button type="button" wire:click="addStaff"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Add Staff Member
                            </button>
                            <button type="button" wire:click="closeAddModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-500">
                                Cancel
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
