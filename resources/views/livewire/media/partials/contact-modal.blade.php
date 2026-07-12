{{-- Press Contact Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex min-h-screen items-center justify-center p-4">
        <button type="button" wire:click="closeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm" aria-label="Close modal"></button>

        <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">New Press Contact</h3>
                <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="saveContact">
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
                        <input wire:model="contactForm.name" type="text" autocomplete="name" autofocus
                            class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('contactForm.name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input wire:model="contactForm.email" type="email" autocomplete="email"
                                class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('contactForm.email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input wire:model="contactForm.phone" type="tel" autocomplete="tel"
                                class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Title / Role</label>
                        <input wire:model="contactForm.title" type="text" placeholder="Reporter, Editor, Producer…"
                            class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Existing outlet</label>
                        <select wire:model="contactForm.outlet_id"
                            class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select an outlet —</option>
                            @foreach($mediaOutlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-3 text-xs uppercase tracking-wide text-gray-400">
                        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                        or add a new outlet
                        <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">New outlet name</label>
                        <input wire:model="contactForm.outlet_name" type="text" placeholder="e.g., ProPublica"
                            class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button" wire:click="closeModal"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveContact"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveContact">Save Contact</span>
                        <span wire:loading wire:target="saveContact">Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
