{{-- Inquiry Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        {{-- Backdrop --}}
        <div wire:click="closeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

        {{-- Modal --}}
        <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $editingId ? 'Edit Inquiry' : 'Log Media Inquiry' }}
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
                        <input wire:model="inquiryForm.subject" type="text" placeholder="What are they asking about?"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        @error('inquiryForm.subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Urgency & Status --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Urgency</label>
                            <select wire:model="inquiryForm.urgency"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="standard">Standard</option>
                                <option value="urgent">Urgent</option>
                                <option value="breaking">Breaking</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select wire:model="inquiryForm.status"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="new">New</option>
                                <option value="responding">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="declined">Declined</option>
                                <option value="no_response">No Response</option>
                            </select>
                        </div>
                    </div>

                    {{-- Received & Deadline --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Received At
                                *</label>
                            <input wire:model="inquiryForm.received_at" type="datetime-local"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            @error('inquiryForm.received_at') <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deadline</label>
                            <input wire:model="inquiryForm.deadline" type="datetime-local"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                    </div>

                    {{-- Journalist Selection --}}
                    <div x-data="{ showNewForm: false }">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Journalist</label>

                        {{-- Dropdown for existing --}}
                        <div x-show="!showNewForm" class="space-y-2">
                            <select wire:model="inquiryForm.journalist_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="">— Select journalist —</option>
                                @foreach($journalists as $journalist)
                                    <option value="{{ $journalist->id }}">
                                        {{ $journalist->name }}@if($journalist->organization)
                                        ({{ $journalist->organization->name }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" @click="showNewForm = true"
                                class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Add New Journalist
                            </button>
                        </div>

                        {{-- New journalist form --}}
                        <div x-show="showNewForm" x-cloak class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">New
                                    Journalist</span>
                                <button type="button"
                                    @click="showNewForm = false; $wire.set('inquiryForm.journalist_name', ''); $wire.set('inquiryForm.journalist_email', ''); $wire.set('inquiryForm.outlet_name', '');"
                                    class="text-xs text-gray-500 hover:text-gray-700">
                                    ← Back to select
                                </button>
                            </div>
                            <div
                                class="grid grid-cols-2 gap-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Name
                                        *</label>
                                    <input wire:model="inquiryForm.journalist_name" type="text"
                                        placeholder="Reporter name"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                    <input wire:model="inquiryForm.journalist_email" type="email"
                                        placeholder="reporter@outlet.com"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Outlet /
                                        Organization</label>
                                    <input wire:model="inquiryForm.outlet_name" type="text"
                                        placeholder="e.g., ProPublica"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                </div>
                                <p class="col-span-2 text-xs text-indigo-600 dark:text-indigo-400">
                                    Will be added to People as a Press Contact
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">What are they
                            asking? *</label>
                        <textarea wire:model="inquiryForm.description" rows="4" placeholder="Details of the inquiry..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                        @error('inquiryForm.description') <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Handled By --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assigned
                            To</label>
                        <select wire:model="inquiryForm.handled_by"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">— Unassigned —</option>
                            @foreach($teamMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Related Project --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Related
                            Project</label>
                        <select wire:model="inquiryForm.project_id"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">— Select Project —</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Response Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Response
                            Notes</label>
                        <textarea wire:model="inquiryForm.response_notes" rows="3"
                            placeholder="How did we respond? What did we provide?"
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
                <button wire:click="saveInquiry"
                    class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    {{ $editingId ? 'Update Inquiry' : 'Save Inquiry' }}
                </button>
            </div>
        </div>
    </div>
</div>