<div class="space-y-4">
    {{-- Selected Tags Display --}}
    @if($totalSelected > 0)
        <div class="flex flex-wrap gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            @foreach($selectedTags as $tag)
                <span class="inline-flex items-center gap-1 px-2 py-1 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-full">
                    <span>{{ $tag['emoji'] }}</span>
                    <span>{{ $tag['name'] }}</span>
                    <button type="button" wire:click="removeTag('{{ $tag['type'] }}', {{ $tag['id'] }})"
                        class="ml-1 text-gray-400 hover:text-red-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            @endforeach
            <button type="button" wire:click="clearAll" class="text-xs text-gray-500 hover:text-red-500 ml-2">
                Clear all
            </button>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-4">
            <button type="button" wire:click="$set('activeTab', 'regions')"
                class="py-2 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'regions' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                🌍 Regions
                @if(count($selectedRegions) > 0)
                    <span class="ml-1 px-1.5 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-full">{{ count($selectedRegions) }}</span>
                @endif
            </button>
            <button type="button" wire:click="$set('activeTab', 'countries')"
                class="py-2 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'countries' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                🏳️ Countries
                @if(count($selectedCountries) > 0)
                    <span class="ml-1 px-1.5 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-full">{{ count($selectedCountries) }}</span>
                @endif
            </button>
            @if($showUsStates)
                <button type="button" wire:click="$set('activeTab', 'us_states')"
                    class="py-2 px-1 text-sm font-medium border-b-2 transition {{ $activeTab === 'us_states' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    🇺🇸 US States
                    @if(count($selectedUsStates) > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-full">{{ count($selectedUsStates) }}</span>
                    @endif
                </button>
            @endif
        </nav>
    </div>

    {{-- Tab Content --}}
    <div class="min-h-[200px]">
        {{-- Regions Tab --}}
        @if($activeTab === 'regions')
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach($regions as $region)
                    <button type="button" wire:click="toggleRegion({{ $region->id }})"
                        class="flex items-center gap-2 p-3 text-left text-sm rounded-lg border transition {{ in_array($region->id, $selectedRegions) ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <span class="text-lg">🌍</span>
                        <span class="font-medium">{{ $region->name }}</span>
                        @if(in_array($region->id, $selectedRegions))
                            <svg class="w-4 h-4 ml-auto text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Countries Tab --}}
        @if($activeTab === 'countries')
            <div class="space-y-3">
                {{-- Search and Filter --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <input type="text" wire:model.live.debounce.300ms="countrySearch"
                            class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500"
                            placeholder="Search countries...">
                    </div>
                    <select wire:model.live="filterByRegion"
                        class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500">
                        <option value="">All Regions</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Countries List --}}
                <div class="max-h-[300px] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 p-2">
                        @forelse($countries as $country)
                            <button type="button" wire:click="toggleCountry({{ $country->id }})"
                                class="flex items-center gap-2 p-2 text-left text-sm rounded transition {{ in_array($country->id, $selectedCountries) ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                <span class="font-medium truncate">{{ $country->name }}</span>
                                @if(in_array($country->id, $selectedCountries))
                                    <svg class="w-4 h-4 ml-auto flex-shrink-0 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @empty
                            <div class="col-span-full text-center py-4 text-gray-500">No countries found</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        {{-- US States Tab --}}
        @if($activeTab === 'us_states' && $showUsStates)
            <div class="space-y-3">
                {{-- Search --}}
                <input type="text" wire:model.live.debounce.300ms="stateSearch"
                    class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500"
                    placeholder="Search states or territories...">

                {{-- States List --}}
                <div class="max-h-[300px] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    @php
                        $states = $usStates->where('type', 'state');
                        $territories = $usStates->where('type', '!=', 'state');
                    @endphp

                    {{-- States --}}
                    <div class="p-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2 px-2">States</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1">
                            @foreach($states as $state)
                                <button type="button" wire:click="toggleUsState({{ $state->id }})"
                                    class="flex items-center gap-1 p-2 text-left text-sm rounded transition {{ in_array($state->id, $selectedUsStates) ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    <span class="font-medium text-gray-400 text-xs w-6">{{ $state->abbreviation }}</span>
                                    <span class="truncate">{{ $state->name }}</span>
                                    @if(in_array($state->id, $selectedUsStates))
                                        <svg class="w-3 h-3 ml-auto flex-shrink-0 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Territories --}}
                    @if($territories->isNotEmpty())
                        <div class="p-2 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-2 px-2">Territories & Districts</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1">
                                @foreach($territories as $territory)
                                    <button type="button" wire:click="toggleUsState({{ $territory->id }})"
                                        class="flex items-center gap-1 p-2 text-left text-sm rounded transition {{ in_array($territory->id, $selectedUsStates) ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                        <span class="font-medium text-gray-400 text-xs w-6">{{ $territory->abbreviation }}</span>
                                        <span class="truncate">{{ $territory->name }}</span>
                                        @if(in_array($territory->id, $selectedUsStates))
                                            <svg class="w-3 h-3 ml-auto flex-shrink-0 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>


