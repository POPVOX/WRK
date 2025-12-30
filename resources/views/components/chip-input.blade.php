@props([
    'placeholder' => 'Search...',
    'searchUrl' => '',
    'selectedItems' => [],
    'itemKey' => 'id',
    'itemLabel' => 'name',
    'wireModel' => '',
    'colorClass' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
])

@php
    // Convert selected items to JSON for Alpine
    $initialItems = collect($selectedItems)->map(fn($item) => [
        'id' => $item->{$itemKey},
        'name' => $item->{$itemLabel},
    ])->toArray();
@endphp

<div x-data="{
    search: '',
    suggestions: [],
    showSuggestions: false,
    selectedIndex: 0,
    searchTimeout: null,
    selectedIds: @entangle($wireModel),
    selectedItems: {{ json_encode($initialItems) }},
    colorClass: '{{ $colorClass }}',
    
    async fetchSuggestions() {
        if (this.search.length < 1) {
            this.suggestions = [];
            this.showSuggestions = false;
            return;
        }
        
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`{{ $searchUrl }}?q=${encodeURIComponent(this.search)}`);
                const data = await response.json();
                // Filter out already selected items
                this.suggestions = data.filter(item => !this.selectedIds.includes(item.id));
                this.selectedIndex = 0;
                this.showSuggestions = this.suggestions.length > 0;
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 150);
    },
    
    selectItem(item) {
        if (!this.selectedIds.includes(item.id)) {
            this.selectedIds.push(item.id);
            this.selectedItems.push({ id: item.id, name: item.name });
        }
        this.search = '';
        this.suggestions = [];
        this.showSuggestions = false;
        this.$refs.searchInput.focus();
    },
    
    removeItem(id) {
        this.selectedIds = this.selectedIds.filter(i => i !== id);
        this.selectedItems = this.selectedItems.filter(i => i.id !== id);
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
                    this.selectItem(this.suggestions[this.selectedIndex]);
                }
                break;
            case 'Escape':
                this.showSuggestions = false;
                break;
        }
    }
}" class="relative">
    <!-- Selected Chips (Alpine-rendered) -->
    <div class="flex flex-wrap gap-2 mb-2">
        <template x-for="item in selectedItems" :key="item.id">
            <span :class="colorClass" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium">
                <span x-text="item.name"></span>
                <button type="button" @click="removeItem(item.id)" class="hover:opacity-70">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </span>
        </template>
    </div>
    
    <!-- Search Input -->
    <input
        type="text"
        x-ref="searchInput"
        x-model="search"
        @input="fetchSuggestions()"
        @keydown="onKeydown($event)"
        @blur="setTimeout(() => showSuggestions = false, 200)"
        @focus="if (search.length > 0) fetchSuggestions()"
        placeholder="{{ $placeholder }}"
        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
    >
    
    <!-- Suggestions Dropdown -->
    <div
        x-show="showSuggestions"
        x-transition
        class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto"
    >
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
            <button
                type="button"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 text-sm transition"
                :class="{ 'bg-indigo-50 dark:bg-indigo-900/30': index === selectedIndex }"
                @click="selectItem(suggestion)"
                @mouseenter="selectedIndex = index"
            >
                <span class="text-gray-900 dark:text-white" x-text="suggestion.name"></span>
                <template x-if="suggestion.subtitle">
                    <span class="text-gray-500 dark:text-gray-400 text-xs ml-2" x-text="suggestion.subtitle"></span>
                </template>
            </button>
        </template>
    </div>
</div>
