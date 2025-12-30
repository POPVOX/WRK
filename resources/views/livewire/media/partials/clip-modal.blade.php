{{-- Clip Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4">
        {{-- Backdrop --}}
        <div wire:click="closeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

        {{-- Modal --}}
        <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $editingId ? 'Edit Press Clip' : 'Log Press Clip' }}
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
                    {{-- URL --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Article URL
                            *</label>
                        <div class="flex gap-2">
                            <input wire:model="clipForm.url" type="url" placeholder="https://..."
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <button type="button" wire:click="fetchClipFromUrl" wire:loading.attr="disabled"
                                wire:target="fetchClipFromUrl"
                                class="px-3 py-2 text-sm font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800 disabled:opacity-50 whitespace-nowrap">
                                <span wire:loading.remove wire:target="fetchClipFromUrl">✨ Auto-fill</span>
                                <span wire:loading wire:target="fetchClipFromUrl">Fetching...</span>
                            </button>
                        </div>
                        @error('clipForm.url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Paste URL and click Auto-fill to
                            extract article details</p>
                    </div>

                    {{-- Paste Full Text Option --}}
                    <div x-data="{ showPasteText: false }">
                        <button type="button" @click="showPasteText = !showPasteText"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                            <svg class="w-4 h-4 transition-transform" :class="showPasteText ? 'rotate-90' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                            Or paste full article text
                        </button>
                        <div x-show="showPasteText" x-collapse class="mt-3">
                            <div
                                class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                    Copy/paste the full article text below. AI will extract quotes, staff mentions, and
                                    summary.
                                </p>
                                <textarea wire:model="clipForm.raw_text" rows="5"
                                    placeholder="Paste the full article text here..."
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm mb-2"></textarea>
                                <button type="button" wire:click="extractFromPastedText" wire:loading.attr="disabled"
                                    wire:target="extractFromPastedText"
                                    class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="extractFromPastedText">✨ Extract from
                                        Text</span>
                                    <span wire:loading wire:target="extractFromPastedText">Analyzing...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                        <input wire:model="clipForm.title" type="text" placeholder="Article headline"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        @error('clipForm.title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Outlet & Date --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Outlet
                                *</label>
                            <input wire:model="clipForm.outlet_name" type="text" placeholder="e.g., The Hill"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            @error('clipForm.outlet_name') <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Published
                                Date *</label>
                            <input wire:model="clipForm.published_at" type="date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                    </div>

                    {{-- Type & Sentiment --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select wire:model="clipForm.clip_type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="article">Article</option>
                                <option value="broadcast">Broadcast</option>
                                <option value="podcast">Podcast</option>
                                <option value="opinion">Opinion/Editorial</option>
                                <option value="interview">Interview</option>
                                <option value="mention">Mention</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sentiment</label>
                            <select wire:model="clipForm.sentiment"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="positive">Positive</option>
                                <option value="neutral">Neutral</option>
                                <option value="negative">Negative</option>
                                <option value="mixed">Mixed</option>
                            </select>
                        </div>
                    </div>

                    {{-- Journalist Names (supports multiple, comma or "and" separated) --}}
                    <div x-data="{ 
                        reporters: $wire.clipForm.journalist_name ? $wire.clipForm.journalist_name.split(/\s*(?:,|&amp;|\band\b)\s*/i).filter(n => n.trim()) : [],
                        newReporter: '',
                        addReporter() {
                            if (this.newReporter.trim()) {
                                // Split by comma or 'and' for multiple entry
                                const names = this.newReporter.split(/\s*(?:,|&amp;|\band\b)\s*/i).filter(n => n.trim());
                                this.reporters = [...this.reporters, ...names];
                                this.newReporter = '';
                                this.updateWire();
                            }
                        },
                        removeReporter(index) {
                            this.reporters.splice(index, 1);
                            this.updateWire();
                        },
                        updateWire() {
                            $wire.set('clipForm.journalist_name', this.reporters.join(', '));
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Reporter(s)
                        </label>

                        {{-- Chips Display --}}
                        <div class="flex flex-wrap gap-2 mb-2" x-show="reporters.length > 0">
                            <template x-for="(reporter, index) in reporters" :key="index">
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-sm bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-300">
                                    <span x-text="reporter"></span>
                                    <button type="button" @click="removeReporter(index)"
                                        class="w-4 h-4 rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-800 flex items-center justify-center">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>

                        {{-- Input --}}
                        <div class="flex gap-2">
                            <input type="text" x-model="newReporter" @keydown.enter.prevent="addReporter()"
                                placeholder="Add reporter name (press Enter)"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <button type="button" @click="addReporter()"
                                class="px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 rounded-lg transition">
                                Add
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Separate multiple reporters with comma or "and"
                        </p>
                    </div>

                    {{-- Summary --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Summary</label>
                        <textarea wire:model="clipForm.summary" rows="2" placeholder="Brief summary of the article..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                    </div>

                    {{-- Quotes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pull
                            Quotes</label>
                        <textarea wire:model="clipForm.quotes" rows="2"
                            placeholder="Notable quotes mentioning POPVOX..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                    </div>

                    {{-- Staff Mentioned --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Staff
                            Quoted/Mentioned</label>
                        <select wire:model="clipForm.staff_ids" multiple
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm h-24">
                            @foreach($teamMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hold Cmd/Ctrl to select multiple</p>
                    </div>



                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal
                            Notes</label>
                        <textarea wire:model="clipForm.notes" rows="2" placeholder="Any internal notes..."
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
                <button wire:click="saveClip"
                    class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    {{ $editingId ? 'Update Clip' : 'Save Clip' }}
                </button>
            </div>
        </div>
    </div>
</div>