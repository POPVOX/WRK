<div class="desk-page">
    <x-congress-nav />

    <x-desk-page-header eyebrow="Congress · Lists" title="Staff lists" description="Reusable audiences built from directory characteristics and refined person by person.">
        <x-slot:actions><a href="{{ route('congress.lists.create') }}" wire:navigate class="desk-button-primary">＋ New list</a></x-slot:actions>
    </x-desk-page-header>

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
                            @if($selectedList->description)<p class="mt-1 text-sm text-gray-600">{{ $selectedList->description }}</p>@endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('congress.campaigns.create', ['list' => $selectedList->id]) }}" wire:navigate class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Start campaign</a>
                            <button type="button" wire:click="deleteList({{ $selectedList->id }})" wire:confirm="Delete this list? Staff profiles will not be deleted." class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">Delete list</button>
                        </div>
                    </div>
                    @if($selectedList->criteria)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($selectedList->criteria as $criterion => $value)<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ Str::headline($criterion) }}: {{ $value }}</span>@endforeach
                        </div>
                    @endif
                    <input type="search" wire:model.live.debounce.250ms="memberSearch" placeholder="Search this list by name, title, or office" class="mt-4 block w-full rounded-lg border-gray-300 text-sm">
                </header>

                <div class="divide-y divide-gray-200">
                    @forelse($members as $profile)
                        @php
                            $position = $profile->currentPosition;
                        @endphp
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
                <div class="px-6 py-16 text-center"><p class="text-sm text-gray-500">You do not have any staff lists yet.</p><a href="{{ route('congress.lists.create') }}" wire:navigate class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Create your first list</a></div>
            @endif
        </section>
    </div>
</div>
