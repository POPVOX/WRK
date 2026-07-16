<div class="desk-page">
    <x-desk-page-header
        eyebrow="Work portfolio"
        title="Projects"
        description="The work underway across POPVOX Foundation, organized by operating status."
    >
        <x-slot:actions>
            <div class="desk-segmented" aria-label="Project ownership">
                <button type="button" wire:click="setOwnershipScope('mine')" class="{{ $ownershipScope === 'mine' ? 'is-active' : '' }}">Mine</button>
                <button type="button" wire:click="setOwnershipScope('team')" class="{{ $ownershipScope === 'team' ? 'is-active' : '' }}">Team</button>
            </div>
            <a href="{{ route('projects.create') }}" wire:navigate class="desk-button-primary">＋ New project</a>
        </x-slot:actions>
    </x-desk-page-header>

    <section class="grid gap-3 lg:grid-cols-[minmax(18rem,1fr)_11rem_11rem_11rem]">
        <label class="desk-search">
            <span class="text-[#8a8578]" aria-hidden="true">⌕</span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search projects…" aria-label="Search projects">
        </label>
        <select wire:model.live="filterStatus" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterScope" aria-label="Filter by scope">
            <option value="">All scopes</option>
            @foreach($scopes as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterLead" aria-label="Filter by lead">
            <option value="">All leads</option>
            @foreach($leads as $lead)
                <option value="{{ $lead }}">{{ $lead }}</option>
            @endforeach
        </select>
    </section>

    @php
        $groupedProjects = $projects->getCollection()->groupBy('status');
        $statusOrder = ['active', 'on_hold', 'planning', 'completed', 'archived'];
    @endphp

    @forelse($statusOrder as $status)
        @php
            $statusProjects = $groupedProjects->get($status, collect());
        @endphp
        @if($statusProjects->isNotEmpty())
            <section>
                <div class="flex items-end justify-between gap-4 pb-2">
                    <p class="desk-section-label {{ $status === 'active' ? '!text-[#8a4b2d]' : '' }}">
                        {{ $statuses[$status] ?? Str::headline($status) }} · {{ $statusProjects->count() }}
                    </p>
                </div>

                <div class="desk-table-wrap">
                    <table class="desk-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Scope</th>
                                <th>Lead</th>
                                <th>Timeline</th>
                                <th>Health</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statusProjects as $project)
                                @php
                                    $pastDue = $project->target_end_date && $project->target_end_date->isPast() && ! in_array($project->status, ['completed', 'archived']);
                                    $health = match (true) {
                                        $project->status === 'on_hold' => ['On hold', 'desk-status-warning'],
                                        $pastDue => ['Past due', 'desk-status-danger'],
                                        $project->status === 'completed' => ['Complete', 'text-[#8a8578]'],
                                        $project->status === 'planning' => ['Planning', 'desk-status-warning'],
                                        default => ['On track', 'desk-status-positive'],
                                    };
                                    $lead = $project->lead ?: $project->staff->first()?->name;
                                @endphp
                                <tr wire:key="project-row-{{ $project->id }}">
                                    <td class="min-w-[18rem]">
                                        <a href="{{ route('projects.show', $project) }}" wire:navigate class="desk-table-title hover:text-[#8a4b2d]">
                                            {{ $project->name }}
                                        </a>
                                        @if($project->children_count > 0)
                                            <button type="button" wire:click="toggleExpand({{ $project->id }})" class="mt-1 block text-[11px] font-semibold text-[#8a4b2d]">
                                                {{ $project->children_count }} {{ Str::plural('sub-project', $project->children_count) }} {{ in_array($project->id, $expanded) ? '▴' : '▾' }}
                                            </button>
                                        @elseif($project->description)
                                            <p class="desk-meta mt-1">{{ Str::limit($project->description, 90) }}</p>
                                        @endif
                                    </td>
                                    <td>{{ $project->scope ?: '—' }}</td>
                                    <td>{{ $lead ?: 'Unassigned' }}</td>
                                    <td class="desk-data text-xs">
                                        @if($project->start_date || $project->target_end_date)
                                            {{ $project->start_date?->format('M Y') ?: '—' }} → {{ $project->target_end_date?->format('M Y') ?: 'ongoing' }}
                                        @else
                                            Ongoing
                                        @endif
                                    </td>
                                    <td><span class="font-semibold {{ $health[1] }}">{{ $health[0] }}</span></td>
                                </tr>

                                @if(in_array($project->id, $expanded) && $project->children->isNotEmpty())
                                    @foreach($project->children as $child)
                                        <tr wire:key="project-child-{{ $child->id }}" class="bg-[#f5f1e8]">
                                            <td class="border-l-2 !border-l-[#8a4b2d] pl-6">
                                                <a href="{{ route('projects.show', $child) }}" wire:navigate class="desk-table-title text-[0.95rem] hover:text-[#8a4b2d]">{{ $child->name }}</a>
                                            </td>
                                            <td>{{ $child->scope ?: '—' }}</td>
                                            <td>{{ $child->lead ?: 'Unassigned' }}</td>
                                            <td class="desk-data text-xs">{{ $child->target_end_date?->format('M Y') ?: 'Ongoing' }}</td>
                                            <td class="text-[#8a8578]">Sub-project</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @empty
    @endforelse

    @if($projects->isEmpty())
        <div class="desk-empty">
            No projects match these filters. <a href="{{ route('projects.create') }}" wire:navigate class="desk-link">＋ Create a project</a>
        </div>
    @endif

    @if(! $filterStatus && $quietStatusCount > 0)
        <button type="button" wire:click="toggleQuietStatuses" class="w-fit text-xs font-semibold text-[#8a4b2d]">
            {{ $showQuietStatuses ? 'Hide planning and completed work' : 'Show planning and completed work · '.number_format($quietStatusCount) }} →
        </button>
    @endif

    @if($projects->hasPages())
        <div>{{ $projects->links() }}</div>
    @endif
</div>
