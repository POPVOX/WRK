<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-indigo-600">Internal directory</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">Congress Explorer</h1>
            <p class="mt-2 max-w-3xl text-gray-600">
                Search reported House and Senate staff roles without adding them to general Contacts.
                Profiles remain provisional until evidence or team review confirms an identity.
            </p>
        </div>
        <div class="space-y-3">
            <a href="{{ route('congress.changes') }}" wire:navigate class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 hover:bg-amber-100">
                <span>Gmail staff-change review</span>
                <span class="rounded-full bg-white px-2 py-0.5">{{ number_format($pendingChangeSignals) }} pending</span>
            </a>
            <div class="grid grid-cols-3 gap-3 text-center">
            <div class="app-surface px-4 py-3">
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalProfiles) }}</p>
                <p class="text-xs app-muted">Profiles</p>
            </div>
            <div class="app-surface px-4 py-3">
                <p class="text-2xl font-semibold text-emerald-700">{{ number_format($currentProfiles) }}</p>
                <p class="text-xs app-muted">Current</p>
            </div>
            <div class="app-surface px-4 py-3">
                <p class="text-2xl font-semibold text-indigo-700">{{ number_format($linkedProfiles) }}</p>
                <p class="text-xs app-muted">Linked</p>
            </div>
            </div>
        </div>
    </div>

    <section class="app-surface p-5 space-y-4" aria-label="Congressional staff filters">
        <div>
            <label for="congress-search" class="text-sm font-medium text-gray-700">Search staff, office, title, or office code</label>
            <input
                id="congress-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Try a name, committee, legislative director, or office code…"
                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="congress-chamber" class="text-sm font-medium text-gray-700">Chamber</label>
                <select id="congress-chamber" wire:model.live="chamber" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">House &amp; Senate</option>
                    <option value="House">House</option>
                    <option value="Senate">Senate</option>
                </select>
            </div>
            <div>
                <label for="congress-status" class="text-sm font-medium text-gray-700">Role status</label>
                <select id="congress-status" wire:model.live="status" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    <option value="current">Current</option>
                    <option value="former">Former</option>
                    <option value="">All history</option>
                </select>
            </div>
            <div>
                <label for="congress-office-type" class="text-sm font-medium text-gray-700">Office type</label>
                <select id="congress-office-type" wire:model.live="officeType" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">All office types</option>
                    @foreach($officeTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label for="congress-title" class="text-sm font-medium text-gray-700">Title contains</label>
                <div class="mt-1 flex gap-2">
                    <input id="congress-title" type="text" wire:model.live.debounce.300ms="title" placeholder="e.g. Communications" class="block min-w-0 flex-1 rounded-lg border-gray-300 text-sm">
                    <button type="button" wire:click="clearFilters" class="rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Clear
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="app-surface overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Staff profiles</h2>
                <p class="text-sm app-muted">{{ number_format($staff->total()) }} matching {{ Str::plural('profile', $staff->total()) }}</p>
            </div>
            <div wire:loading class="text-sm font-medium text-indigo-600">Updating…</div>
        </div>

        @if($staff->isEmpty())
            <div class="px-6 py-14 text-center">
                <h3 class="font-semibold text-gray-900">No staff profiles match these filters</h3>
                <p class="mt-1 text-sm app-muted">Try broadening the office, title, or status filters.</p>
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($staff as $profile)
                    @php($position = $profile->currentPosition)
                    <a href="{{ route('congress.staff.show', $profile) }}" wire:navigate class="block px-5 py-4 hover:bg-gray-50">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $profile->display_name }}</h3>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $profile->chamber === 'Senate' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800' }}">
                                        {{ $profile->chamber }}
                                    </span>
                                    @if($profile->person_id)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Linked contact</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-700">{{ $position?->title ?? 'No current role reported' }}</p>
                                <p class="mt-0.5 truncate text-sm app-muted">
                                    {{ $position?->office?->name ?? 'Historical profile' }}
                                    @if($position?->office?->office_code)
                                        · {{ $position->office->office_code }}
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 text-left sm:text-right">
                                <p class="text-sm font-medium {{ $position ? 'text-emerald-700' : 'text-gray-600' }}">{{ $position ? 'Current' : 'Former / unconfirmed' }}</p>
                                <p class="mt-0.5 text-xs app-muted">
                                    {{ number_format($profile->observations_count) }} source {{ Str::plural('observation', $profile->observations_count) }}
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-5 py-4">
                {{ $staff->links() }}
            </div>
        @endif
    </section>
</div>
