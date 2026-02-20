<div class="fixed bottom-20 right-4 z-50">
    {{-- Floating Button (when closed) --}}
    @if(!$isOpen)
        <button wire:click="open"
            class="group flex items-center justify-center w-10 h-10 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200"
            title="Quick Task">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    @endif

    {{-- Task Panel --}}
    @if($isOpen)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300"
            style="width: 380px;">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span class="text-white font-semibold text-sm">Quick Add Task</span>
                </div>
                <button wire:click="close" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-4">
                @if($submitted)
                    {{-- Success State --}}
                    <div class="text-center py-6">
                        <div
                            class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Task Created!</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Your task has been added.</p>
                        <div class="flex justify-center gap-3">
                            <button wire:click="addAnother"
                                class="px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors">
                                Add Another
                            </button>
                            <button wire:click="close"
                                class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Task Form --}}
                    <form wire:submit="submit" class="space-y-4">
                        {{-- Title --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Task Title *</label>
                            <input type="text" wire:model="title" autofocus placeholder="What needs to be done?"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">
                            @error('title')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Project --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Project
                                (optional)</label>
                            <select wire:model="projectId"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">
                                <option value="">No project</option>
                                @foreach($this->projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assignee & Due Date --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Assign to</label>
                                <select wire:model="assignedTo"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">
                                    <option value="">Unassigned</option>
                                    @foreach($this->teamMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Due date</label>
                                <input type="date" wire:model="dueDate"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>

                        {{-- Priority --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Priority</label>
                            <div class="flex gap-2">
                                @foreach(['low' => '🟢 Low', 'medium' => '🟡 Medium', 'high' => '🔴 High'] as $value => $label)
                                    <button type="button" wire:click="$set('priority', '{{ $value }}')"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-all {{ $priority === $value ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Description (collapsible) --}}
                        <div x-data="{ showDesc: false }">
                            <button type="button" @click="showDesc = !showDesc"
                                class="text-xs text-green-600 dark:text-green-400 hover:underline flex items-center gap-1">
                                <svg class="w-3 h-3 transition-transform" :class="showDesc ? 'rotate-90' : ''" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Add description
                            </button>
                            <div x-show="showDesc" x-collapse class="mt-2">
                                <textarea wire:model="description" rows="2" placeholder="Additional details..."
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500 resize-none"></textarea>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-medium text-sm rounded-lg hover:from-green-700 hover:to-emerald-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove>Create Task</span>
                            <span wire:loading class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Creating...
                            </span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>