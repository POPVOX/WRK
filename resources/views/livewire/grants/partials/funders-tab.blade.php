{{-- Search --}}
<div class="mb-6">
    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search funders or grants..."
        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
</div>

{{-- Current Funders --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
        Current Funders
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $currentFunders->count() }})</span>
    </h2>

    @if($currentFunders->isNotEmpty())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($currentFunders as $funder)
                @include('livewire.grants.partials.funder-card', ['funder' => $funder, 'variant' => 'current'])
            @endforeach
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400">No current funders</p>
        </div>
    @endif
</div>

{{-- Prospective Funders --}}
<div>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
        Prospective Funders
        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $prospectiveFunders->count() }})</span>
    </h2>

    @if($prospectiveFunders->isNotEmpty())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($prospectiveFunders as $funder)
                @include('livewire.grants.partials.funder-card', ['funder' => $funder, 'variant' => 'prospective'])
            @endforeach
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-8 text-center">
            <p class="text-gray-500 dark:text-gray-400 mb-2">No prospective funders yet</p>
            <button wire:click="openCreateFunderModal"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                + Add a prospective funder
            </button>
        </div>
    @endif
</div>