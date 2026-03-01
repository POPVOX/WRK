<div class="space-y-6">
    <div class="app-page-frame-wide">
        <div class="app-page-head">
            <div>
                <h1 class="app-page-title">Contact Lists</h1>
                <p class="app-page-lead">Saved groups of contacts for outreach, follow-up, and bulk messaging.</p>
            </div>
            <a href="{{ route('contacts.index') }}" wire:navigate
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Back to Contacts
            </a>
        </div>

        <div class="app-card p-4">
            <form wire:submit="createList" class="grid gap-3 lg:grid-cols-[16rem_minmax(0,1fr)_auto]">
                <input
                    type="text"
                    wire:model.defer="newListName"
                    placeholder="New list name"
                    class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                <input
                    type="text"
                    wire:model.defer="newListDescription"
                    placeholder="Description (optional)"
                    class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    Create List
                </button>
            </form>
            @error('newListName')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
            @error('newListDescription')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 xl:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="app-card p-3">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Your Lists</h2>
                <div class="mt-3 space-y-2">
                    @forelse($lists as $list)
                        <button
                            type="button"
                            wire:click="selectList({{ $list->id }})"
                            class="w-full rounded-lg border px-3 py-2 text-left transition {{ $selectedList && $selectedList->id === $list->id ? 'border-indigo-300 bg-indigo-50 text-indigo-800 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300' : 'border-gray-200 bg-white text-gray-800 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-medium">{{ $list->name }}</p>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $list->people_count }}
                                </span>
                            </div>
                            @if($list->description)
                                <p class="mt-1 line-clamp-2 text-[11px] text-gray-500 dark:text-gray-400">{{ $list->description }}</p>
                            @endif
                        </button>
                    @empty
                        <p class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No lists yet.
                        </p>
                    @endforelse
                </div>
            </aside>

            <section class="app-card p-4">
                @if($selectedList)
                    <header class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedList->name }}</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $members->count() }} member{{ $members->count() === 1 ? '' : 's' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="emailList"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                Email List
                            </button>
                            <button
                                type="button"
                                wire:click="deleteList({{ $selectedList->id }})"
                                wire:confirm="Delete this list? Contacts will not be deleted."
                                class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40"
                            >
                                Delete List
                            </button>
                        </div>
                    </header>

                    @if($selectedList->description)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $selectedList->description }}</p>
                    @endif

                    <div class="mt-4">
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="memberSearch"
                            placeholder="Search members by name, title, email"
                            class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Organization</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Email</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($members as $member)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('contacts.show', $member) }}" wire:navigate class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $member->name }}
                                            </a>
                                            @if($member->title)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->title }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            @if($member->organization)
                                                <a href="{{ route('organizations.show', $member->organization) }}" wire:navigate class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                    {{ $member->organization->name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                            @if($member->email)
                                                <a href="mailto:{{ $member->email }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $member->email }}</a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-sm">
                                            <button
                                                type="button"
                                                wire:click="removeFromList({{ $member->id }})"
                                                class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No contacts in this list yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        Create or select a list to manage members.
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
