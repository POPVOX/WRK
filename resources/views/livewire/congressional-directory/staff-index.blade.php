<div class="desk-page">
    <x-congress-nav />

    <x-desk-page-header eyebrow="People · Congress" title="Congress Explorer" description="Search current and historical congressional staff without overwhelming the general contacts database.">
        <x-slot:actions>
            <a href="{{ route('congress.lists.create') }}" wire:navigate class="desk-button-primary">＋ New list</a>
        </x-slot:actions>
    </x-desk-page-header>

    <section class="desk-inset space-y-4 p-5" aria-label="Congressional staff search">
        <label class="desk-search">
            <span class="text-[#8a8578]">⌕</span>
            <input id="congress-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Name, committee, title, office, or code…" aria-label="Search congressional staff">
        </label>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <select wire:model.live="chamber" aria-label="Chamber">
                <option value="">House &amp; Senate</option><option value="House">House</option><option value="Senate">Senate</option>
            </select>
            <select wire:model.live="status" aria-label="Role status">
                <option value="current">Current roles</option><option value="former">Former roles</option><option value="">All history</option>
            </select>
            <select wire:model.live="officeType" aria-label="Office type">
                <option value="">All office types</option>@foreach($officeTypes as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach
            </select>
            <input type="text" wire:model.live.debounce.300ms="title" placeholder="Title contains…" aria-label="Title contains">
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#e4ddd0] pt-4">
            <p class="text-sm text-[#5c574d]">
                @if($title)<span class="desk-data">title:</span> “{{ $title }}” · @endif
                <span class="desk-data">chamber:</span> {{ $chamber ?: 'House + Senate' }} ·
                <span class="desk-data">status:</span> {{ $status ?: 'all history' }} ·
                <strong class="text-[#26221c]">{{ number_format($staff->total()) }} matches</strong>
            </p>
            <div class="desk-toolbar">
                @if($search || $chamber || $title || $officeType || $status !== 'current')<button type="button" wire:click="clearFilters" class="text-xs font-semibold text-[#8a8578]">Clear filters</button>@endif
                <a href="{{ route('congress.lists.create') }}" wire:navigate class="desk-button-primary">Build list from {{ number_format($staff->total()) }} →</a>
            </div>
        </div>
    </section>

    <section>
        <div class="flex items-end justify-between gap-4 pb-2">
            <p class="desk-section-label">Staff profiles</p>
            <p class="desk-data text-[10px] text-[#8a8578]">{{ number_format($currentProfiles) }} current · {{ number_format($linkedProfiles) }} linked</p>
        </div>
        <div class="desk-table-wrap">
            <table class="desk-table">
                <thead><tr><th>Name</th><th>Role</th><th>Office</th><th>Confidence</th></tr></thead>
                <tbody>
                    @forelse($staff as $profile)
                        @php
                            $position = $profile->currentPosition;
                        @endphp
                        <tr wire:key="staff-profile-{{ $profile->id }}">
                            <td class="min-w-[15rem]">
                                <a href="{{ route('congress.staff.show', $profile) }}" wire:navigate class="desk-table-title hover:text-[#8a4b2d]">{{ $profile->display_name }}</a>
                                <p class="desk-data mt-1 text-[10px] text-[#8a8578]">{{ strtoupper($profile->chamber ?: 'Congress') }}@if($profile->person_id) · linked contact @endif</p>
                            </td>
                            <td>{{ $position?->title ?? 'No current role reported' }}</td>
                            <td>{{ $position?->office?->name ?? 'Historical profile' }}</td>
                            <td>
                                <span class="font-semibold {{ $profile->observations_count >= 3 ? 'desk-status-positive' : 'desk-status-warning' }}">
                                    {{ number_format($profile->observations_count) }} {{ Str::plural('source', $profile->observations_count) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="!py-12 text-center text-[#8a8578]">No staff profiles match these filters. Try a broader title or office.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($staff->hasPages())<div class="mt-4">{{ $staff->links() }}</div>@endif
    </section>

    <footer class="desk-inset grid gap-3 px-5 py-4 text-center sm:grid-cols-3">
        <a href="{{ route('congress.index') }}" wire:navigate><span class="desk-section-label">Search</span><strong class="desk-data mt-1 block">{{ number_format($staff->total()) }} matches</strong></a>
        <a href="{{ route('congress.lists') }}" wire:navigate><span class="desk-section-label">→ Lists</span><strong class="mt-1 block text-sm text-[#8a4b2d]">Build an audience</strong></a>
        <a href="{{ route('congress.campaigns') }}" wire:navigate><span class="desk-section-label">→ Campaigns</span><strong class="mt-1 block text-sm text-[#8a4b2d]">Reach out carefully</strong></a>
    </footer>
</div>
