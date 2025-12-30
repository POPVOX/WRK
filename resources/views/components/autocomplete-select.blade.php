@props([
    'placeholder' => 'Search...',
    'searchUrl' => '',
    'selectedItem' => null,
    'itemKey' => 'id',
    'itemLabel' => 'name',
    'wireModel' => '',
])

<div x-data="autocompleteSelect()" class="relative">
    <input
        type="text"
        x-ref="input"
        x-model="search"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown.down.prevent="moveDown"
        @keydown.up.prevent="moveUp"
        @keydown.enter.prevent="selectHighlighted"
        @keydown.escape="close"
        placeholder="{{ $placeholder }}"
        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        autocomplete="off"
    />
    <input type="hidden" name="{{ $wireModel }}" x-model="selectedValue" wire:model="{{ $wireModel }}" />

    <div
        x-show="showDropdown && suggestions.length > 0"
        x-transition
        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        @mousedown.prevent
    >
        <template x-for="(item, index) in suggestions" :key="item.{{ $itemKey }}">
            <button
                type="button"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 text-sm"
                :class="{ 'bg-indigo-50 dark:bg-indigo-900/30': index === highlightedIndex }"
                @click="selectItem(item)"
                @mouseenter="highlightedIndex = index"
                x-text="item.{{ $itemLabel }}"
            ></button>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
function autocompleteSelect() {
    return {
        search: '{{ $selectedItem?->{$itemLabel} ?? '' }}',
        selectedValue: '{{ $selectedItem?->{$itemKey} ?? '' }}',
        suggestions: [],
        showDropdown: false,
        highlightedIndex: 0,
        searchTimeout: null,

        onInput() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.fetchSuggestions(), 200);
        },

        async fetchSuggestions() {
            if (this.search.length < 1) {
                this.suggestions = [];
                this.showDropdown = false;
                return;
            }

            try {
                const response = await fetch(`{{ $searchUrl }}?q=${encodeURIComponent(this.search)}`);
                this.suggestions = await response.json();
                this.highlightedIndex = 0;
                this.showDropdown = this.suggestions.length > 0;
            } catch (error) {
                console.error('Autocomplete error:', error);
            }
        },

        onFocus() {
            if (this.suggestions.length > 0) {
                this.showDropdown = true;
            }
        },

        onBlur() {
            setTimeout(() => this.close(), 150);
        },

        close() {
            this.showDropdown = false;
        },

        moveDown() {
            if (this.highlightedIndex < this.suggestions.length - 1) {
                this.highlightedIndex++;
            }
        },

        moveUp() {
            if (this.highlightedIndex > 0) {
                this.highlightedIndex--;
            }
        },

        selectHighlighted() {
            if (this.suggestions.length > 0) {
                this.selectItem(this.suggestions[this.highlightedIndex]);
            }
        },

        selectItem(item) {
            this.search = item.{{ $itemLabel }};
            this.selectedValue = item.{{ $itemKey }};
            this.showDropdown = false;
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}
</script>
@endpush
@endonce
