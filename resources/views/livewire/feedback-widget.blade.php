<div
    x-data="{
        init() {
            // Capture browser metadata
            $wire.userAgent = navigator.userAgent;
            $wire.screenResolution = screen.width + 'x' + screen.height;
            $wire.viewportSize = window.innerWidth + 'x' + window.innerHeight;
            $wire.pageTitle = document.title;
        },
        handlePaste(event) {
            const items = event.clipboardData?.items;
            if (!items) return;
            
            for (const item of items) {
                if (item.type.indexOf('image') !== -1) {
                    const file = item.getAsFile();
                    if (file) {
                        // Create a file input event
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        const input = $refs.screenshotInput;
                        input.files = dataTransfer.files;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
        }
    }"
    class="fixed bottom-4 right-4 z-50"
>
    {{-- Floating Button (when closed) --}}
    @if(!$isOpen)
        <button
            wire:click="open"
            class="group flex items-center gap-2 px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="text-sm font-medium">Feedback</span>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
        </button>
    @endif

    {{-- Feedback Panel --}}
    @if($isOpen)
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300"
            style="width: {{ $isMinimized ? '280px' : '380px' }};"
            @paste="handlePaste($event)"
        >
            {{-- Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="text-white font-semibold text-sm">Beta Feedback</span>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        wire:click="{{ $isMinimized ? 'expand' : 'minimize' }}"
                        class="p-1.5 hover:bg-white/20 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($isMinimized)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            @endif
                        </svg>
                    </button>
                    <button
                        wire:click="close"
                        class="p-1.5 hover:bg-white/20 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Content --}}
            @if(!$isMinimized)
                <div class="p-4">
                    @if($submitted)
                        {{-- Success State --}}
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Thank you!</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Your feedback helps us improve.</p>
                            <button
                                wire:click="close"
                                class="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                            >
                                Close
                            </button>
                        </div>
                    @else
                        {{-- Feedback Form --}}
                        <form wire:submit="submit" class="space-y-4">
                            {{-- Feedback Type --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">What type of feedback?</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['bug' => '🐛 Bug', 'suggestion' => '💡 Suggestion', 'compliment' => '🎉 Compliment', 'question' => '❓ Question', 'general' => '💬 General'] as $type => $label)
                                        <button
                                            type="button"
                                            wire:click="$set('feedbackType', '{{ $type }}')"
                                            class="px-3 py-1.5 text-xs font-medium rounded-full transition-all {{ $feedbackType === $type ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                                        >
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Category (optional) --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Category (optional)</label>
                                <select
                                    wire:model="category"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="">Select a category...</option>
                                    @foreach(\App\Models\Feedback::CATEGORIES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Message --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Your feedback</label>
                                <textarea
                                    wire:model="message"
                                    rows="4"
                                    placeholder="Tell us what's on your mind... (You can paste images directly!)"
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white placeholder-gray-400 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                ></textarea>
                                @error('message')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Screenshot --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Screenshot (optional)
                                    <span class="text-gray-500 font-normal">- paste or upload</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="file"
                                        wire:model="screenshot"
                                        accept="image/*"
                                        x-ref="screenshotInput"
                                        class="hidden"
                                        id="screenshot-input"
                                    >
                                    
                                    @if($screenshot)
                                        <div class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                                            <img src="{{ $screenshot->temporaryUrl() }}" alt="Screenshot preview" class="w-full h-32 object-cover">
                                            <button
                                                type="button"
                                                wire:click="$set('screenshot', null)"
                                                class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <label
                                            for="screenshot-input"
                                            class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors"
                                        >
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Click or paste image</span>
                                        </label>
                                    @endif
                                </div>
                                @error('screenshot')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Page Info (small footer) --}}
                            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate" title="{{ $pageUrl }}">
                                    📍 {{ $pageRoute ?: $pageUrl }}
                                </p>
                            </div>

                            {{-- Submit Button --}}
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="w-full px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium text-sm rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove>Send Feedback</span>
                                <span wire:loading class="flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sending...
                                </span>
                            </button>
                        </form>
                    @endif
                </div>
            @else
                {{-- Minimized State --}}
                <div class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    Click to expand and share your feedback
                </div>
            @endif
        </div>
    @endif
</div>

