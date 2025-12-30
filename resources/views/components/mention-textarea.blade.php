@props([
    'name' => '',
    'id' => null,
    'placeholder' => '',
    'rows' => 4,
])

<div x-data="mentionTextarea()" class="relative">
    <textarea {{ $attributes->merge(['class' => 'w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white', 'rows' => $rows, 'placeholder' => $placeholder]) }} @if($name) name="{{ $name }}" @endif @if($id) id="{{ $id }}" @endif x-ref="textarea" @input="onInput($event)" @keydown="onKeydown($event)" @blur="hideSuggestions()">{{ $slot }}</textarea>

    <!-- Mention Suggestions Dropdown -->
    <div
        x-show="showSuggestions && suggestions.length > 0"
        x-transition
        class="absolute z-50 mt-1 w-full max-w-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        @mousedown.prevent
    >
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.id + '-' + suggestion.type">
            <button
                type="button"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 transition"
                :class="{ 'bg-indigo-50 dark:bg-indigo-900/30': index === selectedIndex }"
                @click="selectSuggestion(suggestion)"
                @mouseenter="selectedIndex = index"
            >
                <!-- Type Icon -->
                <div class="flex-shrink-0">
                    <template x-if="suggestion.type === 'person'">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white text-sm font-medium">
                            <span x-text="suggestion.name.charAt(0)"></span>
                        </div>
                    </template>
                    <template x-if="suggestion.type === 'organization'">
                        <div class="w-8 h-8 rounded bg-indigo-500 flex items-center justify-center text-white text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </template>
                    <template x-if="suggestion.type === 'staff'">
                        <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white text-sm font-medium">
                            <span x-text="suggestion.name.charAt(0)"></span>
                        </div>
                    </template>
                </div>
                <!-- Name & Subtitle -->
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="suggestion.name"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="suggestion.subtitle || suggestion.type"></div>
                </div>
                <!-- Type Badge -->
                <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded-full"
                    :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': suggestion.type === 'person',
                        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300': suggestion.type === 'organization',
                        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': suggestion.type === 'staff'
                    }"
                    x-text="suggestion.type">
                </span>
            </button>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
function mentionTextarea() {
    return {
        showSuggestions: false,
        suggestions: [],
        selectedIndex: 0,
        mentionStart: null,
        searchTimeout: null,

        onInput(event) {
            const textarea = this.$refs.textarea;
            const cursorPos = textarea.selectionStart;
            const text = textarea.value.substring(0, cursorPos);
            
            // Find the last @ symbol
            const lastAtIndex = text.lastIndexOf('@');
            
            if (lastAtIndex !== -1) {
                const afterAt = text.substring(lastAtIndex + 1);
                // Check if there's a space after @, which means the mention is complete
                if (!afterAt.includes(' ') && !afterAt.includes('\n')) {
                    this.mentionStart = lastAtIndex;
                    this.searchMentions(afterAt);
                    return;
                }
            }
            
            this.hideSuggestions();
        },

        searchMentions(query) {
            clearTimeout(this.searchTimeout);
            
            if (query.length < 1) {
                this.hideSuggestions();
                return;
            }

            this.searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/api/mentions/search?q=${encodeURIComponent(query)}`);
                    this.suggestions = await response.json();
                    this.selectedIndex = 0;
                    this.showSuggestions = this.suggestions.length > 0;
                } catch (error) {
                    console.error('Mention search error:', error);
                    this.hideSuggestions();
                }
            }, 150);
        },

        onKeydown(event) {
            if (!this.showSuggestions) return;

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, this.suggestions.length - 1);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                    break;
                case 'Enter':
                case 'Tab':
                    if (this.suggestions.length > 0) {
                        event.preventDefault();
                        this.selectSuggestion(this.suggestions[this.selectedIndex]);
                    }
                    break;
                case 'Escape':
                    this.hideSuggestions();
                    break;
            }
        },

        selectSuggestion(suggestion) {
            const textarea = this.$refs.textarea;
            const beforeMention = textarea.value.substring(0, this.mentionStart);
            const afterCursor = textarea.value.substring(textarea.selectionStart);
            
            // Insert the mention with a display name
            const mentionText = `@${suggestion.name} `;
            textarea.value = beforeMention + mentionText + afterCursor;
            
            // Update cursor position
            const newCursorPos = beforeMention.length + mentionText.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            
            // Trigger input event for Livewire
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            
            this.hideSuggestions();
            textarea.focus();
        },

        hideSuggestions() {
            this.showSuggestions = false;
            this.suggestions = [];
            this.mentionStart = null;
        }
    }
}
</script>
@endpush
@endonce
