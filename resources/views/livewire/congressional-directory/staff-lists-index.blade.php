<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-indigo-600">Congress Explorer</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">Congressional staff lists</h1>
            <p class="mt-2 text-gray-600">Review saved search results and remove individual staff before outreach.</p>
        </div>
        <a href="{{ route('congress.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Back to Explorer
        </a>
    </div>

    <section class="app-surface p-5">
        <form wire:submit="createList" class="grid gap-3 lg:grid-cols-[16rem_minmax(0,1fr)_auto]">
            <input type="text" wire:model.defer="newListName" placeholder="New list name" class="rounded-lg border-gray-300 text-sm">
            <input type="text" wire:model.defer="newListDescription" placeholder="Description (optional)" class="rounded-lg border-gray-300 text-sm">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Create list</button>
        </form>
        @error('newListName') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('newListDescription') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </section>

    <div class="grid gap-5 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="app-surface p-4">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your staff lists</h2>
            <div class="mt-3 space-y-2">
                @forelse($lists as $list)
                    <button type="button" wire:click="selectList({{ $list->id }})" class="w-full rounded-lg border px-3 py-3 text-left {{ $selectedList?->id === $list->id ? 'border-indigo-300 bg-indigo-50 text-indigo-900' : 'border-gray-200 hover:bg-gray-50' }}">
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold">{{ $list->name }}</span>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-gray-600">{{ number_format($list->profiles_count) }}</span>
                        </span>
                        @if($list->description)<span class="mt-1 block text-xs text-gray-500">{{ $list->description }}</span>@endif
                    </button>
                @empty
                    <p class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">No staff lists yet. Build one from an Explorer search.</p>
                @endforelse
            </div>
        </aside>

        <section class="app-surface overflow-hidden">
            @if($selectedList)
                <header class="border-b border-gray-200 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">{{ $selectedList->name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ number_format($selectedList->profiles_count) }} staff members</p>
                        </div>
                        <button type="button" wire:click="deleteList({{ $selectedList->id }})" wire:confirm="Delete this list? Staff profiles will not be deleted." class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">Delete list</button>
                    </div>
                    <input type="search" wire:model.live.debounce.250ms="memberSearch" placeholder="Search this list by name, title, or office" class="mt-4 block w-full rounded-lg border-gray-300 text-sm">

                    <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-indigo-950">Create an outreach dry run</h3>
                                <p class="mt-1 text-xs text-indigo-800">Resolve addresses, remove duplicates and blocked records, review every recipient, and preview a message. Sending remains disabled.</p>
                            </div>
                            <form wire:submit="createDryRun" class="flex w-full max-w-xl flex-col gap-2 sm:flex-row">
                                <div class="min-w-0 flex-1">
                                    <input type="text" wire:model.defer="draftName" placeholder="Dry-run name" class="block w-full rounded-lg border-indigo-200 text-sm">
                                    @error('draftName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Build workbench</button>
                            </form>
                        </div>

                        @if($drafts->isNotEmpty())
                            <div class="mt-4 border-t border-indigo-200 pt-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800">Existing dry runs</p>
                                <div class="mt-2 grid gap-2 md:grid-cols-2">
                                    @foreach($drafts as $draft)
                                        <a href="{{ route('congress.outreach.show', $draft) }}" wire:navigate class="rounded-lg border border-indigo-200 bg-white px-3 py-2 hover:border-indigo-400">
                                            <span class="flex items-center justify-between gap-2">
                                                <span class="truncate text-sm font-semibold text-gray-900">{{ $draft->name }}</span>
                                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $draft->status === 'ready' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">{{ $draft->status === 'ready' ? 'Review ready' : 'Draft' }}</span>
                                            </span>
                                            <span class="mt-1 block text-xs text-gray-500">{{ number_format($draft->approved_recipients_count) }} approved of {{ number_format($draft->recipients_count) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </header>

                <div class="divide-y divide-gray-200">
                    @forelse($members as $profile)
                        @php($position = $profile->currentPosition)
                        <div class="flex items-center gap-4 px-5 py-4">
                            <a href="{{ route('congress.staff.show', $profile) }}" wire:navigate class="min-w-0 flex-1 hover:text-indigo-700">
                                <p class="font-semibold text-gray-900">{{ $profile->display_name }}</p>
                                <p class="mt-0.5 text-sm text-gray-700">{{ $position?->title ?? 'No current role reported' }}</p>
                                <p class="mt-0.5 truncate text-xs text-gray-500">{{ $position?->office?->name ?? 'Historical profile' }}</p>
                            </a>
                            <button type="button" wire:click="removeFromList({{ $profile->id }})" class="shrink-0 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Remove</button>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-gray-500">No staff in this list match your search.</div>
                    @endforelse
                </div>

                @if($members->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">{{ $members->links() }}</div>
                @endif
            @else
                <div class="px-6 py-16 text-center text-sm text-gray-500">Create or select a list, then add staff from the Explorer.</div>
            @endif
        </section>
    </div>
</div>
