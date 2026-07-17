<div class="desk-page">
    <x-congress-nav />

    <x-desk-page-header eyebrow="Congress · Lists" title="Create a staff list" description="Describe the audience, run the search, choose the right people, and save a reusable list.">
        <x-slot:actions><a href="{{ route('congress.lists') }}" wire:navigate class="desk-button-secondary">Cancel</a></x-slot:actions>
    </x-desk-page-header>

    <section class="app-surface p-5">
        <h2 class="text-lg font-semibold text-gray-900">1. Name the list</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div>
                <label for="list-name" class="text-sm font-semibold text-gray-700">List name</label>
                <input id="list-name" type="text" wire:model.defer="name" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="House legislative assistants">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="list-description" class="text-sm font-semibold text-gray-700">Description</label>
                <input id="list-description" type="text" wire:model.defer="description" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="Who belongs here and what this list is for">
                @error('description')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="app-surface p-5">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">2. Define the audience</h2>
            <p class="mt-1 text-sm text-gray-500">These characteristics are saved with the list so you can understand how it was assembled.</p>
        </div>
        <form wire:submit="runSearch" class="mt-4 space-y-4">
            <input type="search" wire:model.defer="search" class="block w-full rounded-xl border-gray-300" placeholder="Name, office, committee, or office code">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-medium text-gray-700">Chamber
                    <select wire:model.defer="chamber" class="mt-1 block w-full rounded-lg border-gray-300 text-sm"><option value="">House &amp; Senate</option><option value="House">House</option><option value="Senate">Senate</option></select>
                </label>
                <label class="text-sm font-medium text-gray-700">Role status
                    <select wire:model.defer="status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm"><option value="current">Current staff</option><option value="former">Former staff</option><option value="">All history</option></select>
                </label>
                <label class="text-sm font-medium text-gray-700">Office type
                    <select wire:model.defer="officeType" class="mt-1 block w-full rounded-lg border-gray-300 text-sm"><option value="">All office types</option>@foreach($officeTypes as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select>
                </label>
                <label class="text-sm font-medium text-gray-700">Title contains
                    <input type="text" wire:model.defer="title" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="Caseworker">
                </label>
            </div>
            <div class="flex justify-end"><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Run search</button></div>
        </form>
    </section>

    @if($results)
        <section class="app-surface overflow-hidden">
            <header class="flex flex-col gap-4 border-b border-gray-200 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="text-lg font-semibold text-gray-900">3. Select staff</h2><p class="mt-1 text-sm text-gray-500">{{ number_format($results->total()) }} matching profiles · {{ number_format(count($selectedProfileIds)) }} selected</p></div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="selectVisible({{ Illuminate\Support\Js::from($results->pluck('id')->all()) }})" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Select this page</button>
                    <button type="button" wire:click="selectAllMatches" class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Select all {{ number_format($results->total()) }}</button>
                    @if(count($selectedProfileIds) > 0)<button type="button" wire:click="clearSelection" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Clear</button>@endif
                </div>
            </header>
            <div class="divide-y divide-gray-200">
                @forelse($results as $profile)
                    @php
                        $position = $profile->currentPosition;
                    @endphp
                    <label class="flex cursor-pointer items-start gap-3 px-5 py-4 hover:bg-gray-50">
                        <input type="checkbox" wire:model.live="selectedProfileIds" value="{{ $profile->id }}" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="min-w-0 flex-1"><span class="font-semibold text-gray-900">{{ $profile->display_name }}</span><span class="mt-0.5 block text-sm text-gray-700">{{ $position?->title ?? 'No current title' }}</span><span class="mt-0.5 block truncate text-xs text-gray-500">{{ $position?->office?->name ?? 'No current office' }}</span></span>
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $profile->chamber === 'Senate' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800' }}">{{ $profile->chamber }}</span>
                    </label>
                @empty
                    <div class="px-6 py-14 text-center text-sm text-gray-500">No staff match these characteristics.</div>
                @endforelse
            </div>
            @if($results->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $results->links() }}</div>@endif
        </section>
    @endif

    <section class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-xl border border-indigo-200 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <div><p class="font-semibold text-gray-900">{{ number_format(count($selectedProfileIds)) }} staff selected</p><p class="text-xs text-gray-500">You can remove individual staff later without deleting their profiles.</p></div>
        <button type="button" wire:click="saveList" @disabled(count($selectedProfileIds) === 0) class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">Save list</button>
    </section>
</div>
