{{-- Recursive Tree Node --}}
@php
    $isExpanded = in_array($project->id, $expanded);
    $hasChildren = $project->children->count() > 0;
    $indentClass = match ($depth) {
        0 => 'ml-0',
        1 => 'ml-6',
        2 => 'ml-12',
        3 => 'ml-16',
        default => 'ml-20',
    };
@endphp

<div class="{{ $indentClass }}">
    <div class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 group">
        {{-- Expand/Collapse Toggle --}}
        <div class="w-5 flex-shrink-0">
            @if($hasChildren)
                <button wire:click="toggleExpand({{ $project->id }})"
                    class="p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded">
                    @if($isExpanded)
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    @endif
                </button>
            @else
                <span class="block w-4 h-4"></span>
            @endif
        </div>

        {{-- Project Type Icon --}}
        @php
            $typeIcon = match ($project->project_type ?? 'initiative') {
                'publication' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'event' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'chapter' => 'M16 4v12l-4-2-4 2V4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                'newsletter' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
                'tool' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'research' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                'outreach' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                'component' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
                default => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
            };
        @endphp
        <svg class="w-5 h-5 flex-shrink-0 {{ $project->type_color ?? 'text-gray-400' }}" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIcon }}" />
        </svg>

        {{-- Project Name --}}
        <a href="{{ route('projects.show', $project) }}" wire:navigate
            class="flex-1 font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 truncate {{ $depth === 0 ? 'text-base' : 'text-sm' }}">
            {{ $project->name }}
        </a>

        {{-- Type Label (for nested items) --}}
        @if($depth > 0 && $project->project_type && $project->project_type !== 'initiative')
            <span class="text-xs text-gray-400 capitalize hidden group-hover:inline">
                {{ str_replace('_', ' ', $project->project_type) }}
            </span>
        @endif

        {{-- Status Badge --}}
        <span
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-700' }}">
            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
        </span>

        {{-- Children Count --}}
        @if($hasChildren)
            <span class="text-xs text-gray-400">
                ({{ $project->children->count() }})
            </span>
        @endif

        {{-- Lead (on hover) --}}
        @if($project->lead)
            <span class="text-xs text-gray-400 hidden group-hover:inline">
                {{ $project->lead }}
            </span>
        @endif
    </div>

    {{-- Recursive Children --}}
    @if($isExpanded && $hasChildren)
        <div class="border-l-2 border-gray-200 dark:border-gray-700 ml-2.5">
            @foreach($project->children as $child)
                @include('livewire.projects.partials.tree-node', ['project' => $child, 'depth' => $depth + 1, 'statusColors' => $statusColors])
            @endforeach
        </div>
    @endif
</div>