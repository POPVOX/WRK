{{-- Pitch Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        {{-- Backdrop --}}
        <div wire:click="closeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

        {{-- Modal --}}
        <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $editingId ? 'Edit Pitch' : 'New Pitch' }}
                </h3>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                <div class="space-y-4">
                    {{-- Subject --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject *</label>
                        <input wire:model="pitchForm.subject" type="text" placeholder="Pitch subject line"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        @error('pitchForm.subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select wire:model="pitchForm.status"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="following_up">Following Up</option>
                            <option value="accepted">Accepted</option>
                            <option value="published">Published</option>
                            <option value="declined">Declined</option>
                            <option value="no_response">No Response</option>
                        </select>
                    </div>

                    {{-- Outlet & Journalist --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target
                                Outlet</label>
                            <input wire:model="pitchForm.outlet_name" type="text" placeholder="e.g., Politico"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Journalist
                                Name</label>
                            <input wire:model="pitchForm.journalist_name" type="text" placeholder="Reporter name"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                    </div>

                    {{-- Journalist Email --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Journalist
                            Email</label>
                        <input wire:model="pitchForm.journalist_email" type="email" placeholder="reporter@outlet.com"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>

                    {{-- Pitch Content --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pitch Content
                            *</label>
                        <textarea wire:model="pitchForm.description" rows="6" placeholder="Your pitch..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                        @error('pitchForm.description') <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Related Project --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Related
                            Project</label>
                        <select wire:model="pitchForm.project_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">— Select Project —</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Issues --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Related
                            Issues</label>
                        <select wire:model="pitchForm.issue_ids" multiple
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm h-24">
                            @foreach($issues as $issue)
                                <option value="{{ $issue->id }}">{{ $issue->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hold Cmd/Ctrl to select multiple</p>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal
                            Notes</label>
                        <textarea wire:model="pitchForm.notes" rows="2" placeholder="Any internal notes..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800">
                    Cancel
                </button>
                @if(!$editingId && $pitchForm['status'] === 'draft')
                    <button wire:click="$set('pitchForm.status', 'sent'); savePitch()"
                        class="px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50">
                        Save & Mark Sent
                    </button>
                @endif
                <button wire:click="savePitch"
                    class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    {{ $editingId ? 'Update Pitch' : 'Save Pitch' }}
                </button>
            </div>
        </div>
    </div>
</div>