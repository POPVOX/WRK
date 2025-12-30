# Project Nesting Implementation

## Overview

Implement parent/child project relationships across all three view modes (Grid, List, Tree). Projects like "REBOOT CONGRESS 2026" should display as parents containing nested sub-projects like events, publications, chapters, newsletters, etc.

## Current State

- `projects` table already has `parent_project_id`, `project_type`, and `sort_order` columns
- `Project` model already has `parent()`, `children()`, `ancestors()` relationships
- Projects page has Grid, List, and Calendar view toggles
- Need to add Tree view and update Grid/List to show hierarchy

---

## Database: Already Complete

The migrations should already exist with:

```php
// In projects table
$table->foreignId('parent_project_id')->nullable()->constrained('projects')->nullOnDelete();
$table->string('project_type')->default('initiative');
$table->integer('sort_order')->default(0);
```

Project types: `initiative`, `publication`, `event`, `chapter`, `newsletter`, `tool`, `component`, `research`, `outreach`

---

## Model Updates

### app/Models/Project.php

Ensure these relationships and methods exist:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        // ... existing fillable fields
        'parent_project_id',
        'project_type',
        'sort_order',
    ];

    // Parent relationship
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'parent_project_id');
    }

    // Children relationship
    public function children(): HasMany
    {
        return $this->hasMany(Project::class, 'parent_project_id')->orderBy('sort_order');
    }

    // Recursive children (for tree building)
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    // Get all ancestors
    public function ancestors(): array
    {
        $ancestors = [];
        $project = $this->parent;
        while ($project) {
            array_unshift($ancestors, $project);
            $project = $project->parent;
        }
        return $ancestors;
    }

    // Get root ancestor
    public function rootAncestor(): ?Project
    {
        $ancestors = $this->ancestors();
        return $ancestors[0] ?? null;
    }

    // Check if this is a root project
    public function isRoot(): bool
    {
        return is_null($this->parent_project_id);
    }

    // Check if has children
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    // Get depth in hierarchy
    public function getDepthAttribute(): int
    {
        return count($this->ancestors());
    }

    // Get breadcrumb path
    public function getBreadcrumbAttribute(): array
    {
        $path = $this->ancestors();
        $path[] = $this;
        return $path;
    }

    // Scope: only root projects
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_project_id');
    }

    // Scope: only orphan projects (no parent, for filtering)
    public function scopeOrphans($query)
    {
        return $query->whereNull('parent_project_id');
    }

    // Scope: with children count
    public function scopeWithChildrenCount($query)
    {
        return $query->withCount('children');
    }

    // Get icon based on project type
    public function getTypeIconAttribute(): string
    {
        return match($this->project_type) {
            'publication' => 'document-text',
            'event' => 'calendar',
            'chapter' => 'bookmark',
            'newsletter' => 'newspaper',
            'tool' => 'wrench-screwdriver',
            'research' => 'academic-cap',
            'outreach' => 'megaphone',
            'component' => 'puzzle-piece',
            default => 'folder',
        };
    }

    // Get color based on project type
    public function getTypeColorAttribute(): string
    {
        return match($this->project_type) {
            'publication' => 'text-blue-500',
            'event' => 'text-purple-500',
            'chapter' => 'text-indigo-500',
            'newsletter' => 'text-green-500',
            'tool' => 'text-orange-500',
            'research' => 'text-cyan-500',
            'outreach' => 'text-pink-500',
            'component' => 'text-gray-500',
            default => 'text-gray-400',
        };
    }
}
```

---

## Livewire Component Updates

### app/Livewire/Projects/Index.php

```php
<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // Existing properties
    public string $search = '';
    public string $status = '';
    public string $scope = '';
    public string $lead = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    
    // View mode: grid, list, tree, calendar
    public string $view = 'grid';
    
    // Hierarchy filter: all, roots, orphans
    public string $hierarchyFilter = 'roots';
    
    // Track expanded projects in grid/list view
    public array $expanded = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'scope' => ['except' => ''],
        'lead' => ['except' => ''],
        'view' => ['except' => 'grid'],
        'hierarchyFilter' => ['except' => 'roots'],
    ];

    public function toggleExpand(int $projectId): void
    {
        if (in_array($projectId, $this->expanded)) {
            $this->expanded = array_diff($this->expanded, [$projectId]);
        } else {
            $this->expanded[] = $projectId;
        }
    }

    public function expandAll(): void
    {
        $this->expanded = Project::roots()->pluck('id')->toArray();
    }

    public function collapseAll(): void
    {
        $this->expanded = [];
    }

    public function getProjectsProperty()
    {
        $query = Project::query()
            ->with(['lead', 'parent', 'children'])
            ->withCount('children');

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Status filter
        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Scope filter
        if ($this->scope) {
            $query->where('scope', $this->scope);
        }

        // Lead filter
        if ($this->lead) {
            $query->where('lead_id', $this->lead);
        }

        // Hierarchy filter (except in tree view which always shows full hierarchy)
        if ($this->view !== 'tree') {
            if ($this->hierarchyFilter === 'roots') {
                $query->whereNull('parent_project_id');
            } elseif ($this->hierarchyFilter === 'orphans') {
                $query->whereNull('parent_project_id')
                      ->whereDoesntHave('children');
            }
            // 'all' shows everything
        } else {
            // Tree view: only get roots, children loaded via relationship
            $query->whereNull('parent_project_id');
        }

        // Sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        // Tree view doesn't paginate
        if ($this->view === 'tree') {
            return $query->with('childrenRecursive')->get();
        }

        return $query->paginate(12);
    }

    // Get all root projects for tree view
    public function getTreeProjectsProperty()
    {
        return Project::roots()
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.projects.index', [
            'projects' => $this->projects,
        ]);
    }
}
```

---

## View Templates

### resources/views/livewire/projects/index.blade.php

```blade
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <p class="text-gray-600">Manage initiatives, track progress, and organize work streams.</p>
        </div>
        <a href="{{ route('projects.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <x-heroicon-o-plus class="w-5 h-5" />
            New Project
        </a>
    </div>

    {{-- Filters Row --}}
    <div class="flex flex-wrap items-center gap-4 mb-6">
        {{-- Search --}}
        <div class="flex-1 min-w-[200px] max-w-md">
            <input type="text" 
                   wire:model.live.debounce.300ms="search" 
                   placeholder="Search projects..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        {{-- Status Filter --}}
        <select wire:model.live="status" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Statuses</option>
            <option value="planning">Planning</option>
            <option value="active">Active</option>
            <option value="on_hold">On Hold</option>
            <option value="completed">Completed</option>
            <option value="archived">Archived</option>
        </select>

        {{-- Scope Filter --}}
        <select wire:model.live="scope" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Scopes</option>
            <option value="internal">Internal</option>
            <option value="external">External</option>
        </select>

        {{-- Lead Filter --}}
        <select wire:model.live="lead" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Leads</option>
            @foreach(\App\Models\User::orderBy('name')->get() as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>

        {{-- Sort --}}
        <select wire:model.live="sortBy" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="created_at">Sort: Date</option>
            <option value="name">Sort: Name</option>
            <option value="status">Sort: Status</option>
            <option value="updated_at">Sort: Updated</option>
        </select>

        {{-- View Toggles --}}
        <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg">
            <button wire:click="$set('view', 'grid')" 
                    class="p-2 rounded {{ $view === 'grid' ? 'bg-white shadow' : 'hover:bg-gray-200' }}"
                    title="Grid View">
                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-gray-600" />
            </button>
            <button wire:click="$set('view', 'list')" 
                    class="p-2 rounded {{ $view === 'list' ? 'bg-white shadow' : 'hover:bg-gray-200' }}"
                    title="List View">
                <x-heroicon-o-bars-3 class="w-5 h-5 text-gray-600" />
            </button>
            <button wire:click="$set('view', 'tree')" 
                    class="p-2 rounded {{ $view === 'tree' ? 'bg-white shadow' : 'hover:bg-gray-200' }}"
                    title="Tree View">
                <x-heroicon-o-queue-list class="w-5 h-5 text-gray-600" />
            </button>
            <button wire:click="$set('view', 'calendar')" 
                    class="p-2 rounded {{ $view === 'calendar' ? 'bg-white shadow' : 'hover:bg-gray-200' }}"
                    title="Calendar View">
                <x-heroicon-o-calendar class="w-5 h-5 text-gray-600" />
            </button>
        </div>
    </div>

    {{-- Hierarchy Filter (not shown in tree view) --}}
    @if($view !== 'tree')
    <div class="flex items-center gap-2 mb-4">
        <span class="text-sm text-gray-600">Show:</span>
        <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg">
            <button wire:click="$set('hierarchyFilter', 'roots')" 
                    class="px-3 py-1 text-sm rounded {{ $hierarchyFilter === 'roots' ? 'bg-white shadow' : 'hover:bg-gray-200' }}">
                Parent Projects
            </button>
            <button wire:click="$set('hierarchyFilter', 'all')" 
                    class="px-3 py-1 text-sm rounded {{ $hierarchyFilter === 'all' ? 'bg-white shadow' : 'hover:bg-gray-200' }}">
                All Projects
            </button>
            <button wire:click="$set('hierarchyFilter', 'orphans')" 
                    class="px-3 py-1 text-sm rounded {{ $hierarchyFilter === 'orphans' ? 'bg-white shadow' : 'hover:bg-gray-200' }}">
                Standalone Only
            </button>
        </div>
    </div>
    @endif

    {{-- Tree View Controls --}}
    @if($view === 'tree')
    <div class="flex items-center gap-2 mb-4">
        <button wire:click="expandAll" class="text-sm text-indigo-600 hover:text-indigo-800">
            Expand All
        </button>
        <span class="text-gray-300">|</span>
        <button wire:click="collapseAll" class="text-sm text-indigo-600 hover:text-indigo-800">
            Collapse All
        </button>
    </div>
    @endif

    {{-- Content based on view mode --}}
    @if($view === 'grid')
        @include('livewire.projects.partials.grid-view')
    @elseif($view === 'list')
        @include('livewire.projects.partials.list-view')
    @elseif($view === 'tree')
        @include('livewire.projects.partials.tree-view')
    @elseif($view === 'calendar')
        @include('livewire.projects.partials.calendar-view')
    @endif

    {{-- Pagination (not for tree view) --}}
    @if($view !== 'tree' && $projects->hasPages())
    <div class="mt-6">
        {{ $projects->links() }}
    </div>
    @endif
</div>
```

---

### resources/views/livewire/projects/partials/grid-view.blade.php

```blade
{{-- Grid View with Expandable Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($projects as $project)
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
            {{-- Card Header --}}
            <div class="p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono text-gray-400">P-{{ str_pad($project->id, 3, '0', STR_PAD_LEFT) }}</span>
                        
                        @if($project->children_count > 0)
                            <button wire:click="toggleExpand({{ $project->id }})" 
                                    class="p-0.5 text-gray-400 hover:text-gray-600 rounded">
                                @if(in_array($project->id, $expanded))
                                    <x-heroicon-s-chevron-down class="w-4 h-4" />
                                @else
                                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                                @endif
                            </button>
                        @endif
                    </div>
                    
                    <x-status-badge :status="$project->status" />
                </div>

                {{-- Title --}}
                <a href="{{ route('projects.show', $project) }}" class="block group">
                    <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                        {{ $project->name }}
                    </h3>
                </a>

                {{-- Parent Link (if child) --}}
                @if($project->parent)
                    <div class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                        <x-heroicon-o-arrow-turn-down-right class="w-3 h-3" />
                        <a href="{{ route('projects.show', $project->parent) }}" class="hover:text-indigo-600">
                            {{ $project->parent->name }}
                        </a>
                    </div>
                @endif

                {{-- Type Badge (for non-initiative types) --}}
                @if($project->project_type !== 'initiative')
                    <div class="flex items-center gap-1 mt-2">
                        <x-dynamic-component :component="'heroicon-o-' . $project->type_icon" 
                                             class="w-4 h-4 {{ $project->type_color }}" />
                        <span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $project->project_type) }}</span>
                    </div>
                @endif

                {{-- Lead --}}
                @if($project->lead)
                    <div class="flex items-center gap-1 mt-2 text-sm text-gray-600">
                        <x-heroicon-o-user class="w-4 h-4" />
                        {{ $project->lead->name }}
                    </div>
                @endif

                {{-- Description --}}
                @if($project->description)
                    <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $project->description }}</p>
                @endif

                {{-- Dates --}}
                @if($project->start_date || $project->end_date)
                    <div class="flex items-center gap-1 mt-2 text-xs text-gray-500">
                        <x-heroicon-o-calendar class="w-3 h-3" />
                        @if($project->start_date)
                            {{ $project->start_date->format('M j, Y') }}
                        @endif
                        @if($project->start_date && $project->end_date)
                            -
                        @endif
                        @if($project->end_date)
                            {{ $project->end_date->format('M j, Y') }}
                        @endif
                    </div>
                @endif

                {{-- Stats Row --}}
                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                        <x-heroicon-o-calendar-days class="w-4 h-4" />
                        {{ $project->meetings_count ?? 0 }} meetings
                    </div>
                    <div class="flex items-center gap-1 text-xs text-gray-500">
                        <x-heroicon-o-check-circle class="w-4 h-4" />
                        {{ $project->decisions_count ?? 0 }} decisions
                    </div>
                    @if($project->children_count > 0)
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <x-heroicon-o-folder class="w-4 h-4" />
                            {{ $project->children_count }} sub-projects
                        </div>
                    @endif
                </div>
            </div>

            {{-- Expanded Children --}}
            @if(in_array($project->id, $expanded) && $project->children_count > 0)
                <div class="border-t border-gray-200 bg-gray-50 p-3">
                    <div class="space-y-2">
                        @foreach($project->children->take(5) as $child)
                            <a href="{{ route('projects.show', $child) }}" 
                               class="flex items-center justify-between p-2 bg-white rounded border border-gray-200 hover:border-indigo-300 transition-colors">
                                <div class="flex items-center gap-2">
                                    <x-dynamic-component :component="'heroicon-o-' . $child->type_icon" 
                                                         class="w-4 h-4 {{ $child->type_color }}" />
                                    <span class="text-sm font-medium text-gray-900">{{ $child->name }}</span>
                                </div>
                                <x-status-badge :status="$child->status" size="sm" />
                            </a>
                        @endforeach
                        
                        @if($project->children_count > 5)
                            <a href="{{ route('projects.show', $project) }}#subprojects" 
                               class="block text-center text-xs text-indigo-600 hover:text-indigo-800 py-1">
                                Show {{ $project->children_count - 5 }} more...
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="col-span-full text-center py-12 text-gray-500">
            No projects found.
        </div>
    @endforelse
</div>
```

---

### resources/views/livewire/projects/partials/list-view.blade.php

```blade
{{-- List View with Expandable Rows --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8"></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sub-projects</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($projects as $project)
                {{-- Parent Row --}}
                <tr class="hover:bg-gray-50 {{ $project->children_count > 0 ? 'font-medium' : '' }}">
                    <td class="px-4 py-3">
                        @if($project->children_count > 0)
                            <button wire:click="toggleExpand({{ $project->id }})" class="text-gray-400 hover:text-gray-600">
                                @if(in_array($project->id, $expanded))
                                    <x-heroicon-s-chevron-down class="w-4 h-4" />
                                @else
                                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                                @endif
                            </button>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono text-gray-400">P-{{ str_pad($project->id, 3, '0', STR_PAD_LEFT) }}</span>
                            <a href="{{ route('projects.show', $project) }}" class="text-gray-900 hover:text-indigo-600">
                                {{ $project->name }}
                            </a>
                        </div>
                        @if($project->parent)
                            <div class="flex items-center gap-1 mt-0.5 text-xs text-gray-500">
                                <x-heroicon-o-arrow-turn-down-right class="w-3 h-3" />
                                {{ $project->parent->name }}
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <x-dynamic-component :component="'heroicon-o-' . $project->type_icon" 
                                                 class="w-4 h-4 {{ $project->type_color }}" />
                            <span class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $project->project_type) }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $project->lead?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <x-status-badge :status="$project->status" />
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        @if($project->start_date)
                            {{ $project->start_date->format('M j') }}
                            @if($project->end_date)
                                - {{ $project->end_date->format('M j, Y') }}
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        @if($project->children_count > 0)
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-folder class="w-4 h-4" />
                                {{ $project->children_count }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                </tr>

                {{-- Expanded Children Rows --}}
                @if(in_array($project->id, $expanded) && $project->children_count > 0)
                    @foreach($project->children as $child)
                        <tr class="bg-gray-50 hover:bg-gray-100">
                            <td class="px-4 py-2"></td>
                            <td class="px-4 py-2 pl-10">
                                <div class="flex items-center gap-2">
                                    <x-dynamic-component :component="'heroicon-o-' . $child->type_icon" 
                                                         class="w-4 h-4 {{ $child->type_color }}" />
                                    <a href="{{ route('projects.show', $child) }}" class="text-sm text-gray-700 hover:text-indigo-600">
                                        {{ $child->name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', $child->project_type) }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600">
                                {{ $child->lead?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-2">
                                <x-status-badge :status="$child->status" size="sm" />
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500">
                                @if($child->start_date)
                                    {{ $child->start_date->format('M j') }}
                                    @if($child->end_date)
                                        - {{ $child->end_date->format('M j') }}
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($child->children_count > 0)
                                    <span class="text-xs text-gray-500">{{ $child->children_count }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                        No projects found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
```

---

### resources/views/livewire/projects/partials/tree-view.blade.php

```blade
{{-- Tree View --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
    <div class="space-y-1">
        @forelse($projects as $project)
            @include('livewire.projects.partials.tree-node', ['project' => $project, 'depth' => 0])
        @empty
            <div class="text-center py-12 text-gray-500">
                No projects found.
            </div>
        @endforelse
    </div>
</div>
```

---

### resources/views/livewire/projects/partials/tree-node.blade.php

```blade
{{-- Recursive Tree Node --}}
@php
    $isExpanded = in_array($project->id, $expanded);
    $hasChildren = $project->children->count() > 0;
    $indentClass = match($depth) {
        0 => 'ml-0',
        1 => 'ml-6',
        2 => 'ml-12',
        3 => 'ml-18',
        default => 'ml-24',
    };
@endphp

<div class="{{ $indentClass }}">
    <div class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-gray-50 group">
        {{-- Expand/Collapse Toggle --}}
        <div class="w-5 flex-shrink-0">
            @if($hasChildren)
                <button wire:click="toggleExpand({{ $project->id }})" 
                        class="p-0.5 text-gray-400 hover:text-gray-600 rounded">
                    @if($isExpanded)
                        <x-heroicon-s-chevron-down class="w-4 h-4" />
                    @else
                        <x-heroicon-s-chevron-right class="w-4 h-4" />
                    @endif
                </button>
            @else
                <span class="block w-4 h-4"></span>
            @endif
        </div>

        {{-- Type Icon --}}
        <x-dynamic-component :component="'heroicon-o-' . $project->type_icon" 
                             class="w-5 h-5 flex-shrink-0 {{ $project->type_color }}" />

        {{-- Project Name --}}
        <a href="{{ route('projects.show', $project) }}" 
           class="flex-1 font-medium text-gray-900 hover:text-indigo-600 truncate {{ $depth === 0 ? 'text-base' : 'text-sm' }}">
            {{ $project->name }}
        </a>

        {{-- Type Label (for nested items) --}}
        @if($depth > 0 && $project->project_type !== 'initiative')
            <span class="text-xs text-gray-400 capitalize hidden group-hover:inline">
                {{ str_replace('_', ' ', $project->project_type) }}
            </span>
        @endif

        {{-- Status Badge --}}
        <x-status-badge :status="$project->status" size="sm" />

        {{-- Dates (compact) --}}
        @if($project->start_date || $project->end_date)
            <span class="text-xs text-gray-400 hidden sm:inline">
                @if($project->start_date)
                    {{ $project->start_date->format('M j') }}
                @endif
                @if($project->end_date)
                    - {{ $project->end_date->format('M j') }}
                @endif
            </span>
        @endif

        {{-- Children Count --}}
        @if($hasChildren)
            <span class="text-xs text-gray-400">
                ({{ $project->children->count() }})
            </span>
        @endif

        {{-- Lead (on hover) --}}
        @if($project->lead)
            <span class="text-xs text-gray-400 hidden group-hover:inline">
                {{ $project->lead->name }}
            </span>
        @endif
    </div>

    {{-- Recursive Children --}}
    @if($isExpanded && $hasChildren)
        <div class="border-l border-gray-200 ml-2.5">
            @foreach($project->children as $child)
                @include('livewire.projects.partials.tree-node', ['project' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
```

---

## Status Badge Component

### resources/views/components/status-badge.blade.php

```blade
@props([
    'status',
    'size' => 'md'
])

@php
    $colors = match($status) {
        'planning' => 'bg-purple-100 text-purple-700',
        'active' => 'bg-green-100 text-green-700',
        'on_hold' => 'bg-yellow-100 text-yellow-700',
        'completed' => 'bg-blue-100 text-blue-700',
        'archived' => 'bg-gray-100 text-gray-700',
        default => 'bg-gray-100 text-gray-600',
    };
    
    $sizeClasses = match($size) {
        'sm' => 'px-1.5 py-0.5 text-xs',
        'md' => 'px-2 py-1 text-xs',
        'lg' => 'px-3 py-1 text-sm',
        default => 'px-2 py-1 text-xs',
    };
@endphp

<span class="inline-flex items-center font-medium rounded-full {{ $colors }} {{ $sizeClasses }}">
    {{ ucfirst(str_replace('_', ' ', $status)) }}
    <x-heroicon-s-chevron-down class="w-3 h-3 ml-0.5" />
</span>
```

---

## Project Create/Edit Form Updates

Add parent project and type selection to the project form:

### In project create/edit form

```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Project Name --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
        <input type="text" wire:model="name" 
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Parent Project --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Parent Project</label>
        <select wire:model="parent_project_id" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">None (standalone project)</option>
            @foreach(\App\Models\Project::roots()->where('id', '!=', $projectId ?? 0)->orderBy('name')->get() as $parent)
                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Nest this project under a parent initiative</p>
    </div>

    {{-- Project Type --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Project Type</label>
        <select wire:model="project_type" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="initiative">Initiative</option>
            <option value="publication">Publication</option>
            <option value="event">Event</option>
            <option value="chapter">Chapter</option>
            <option value="newsletter">Newsletter</option>
            <option value="tool">Tool</option>
            <option value="research">Research</option>
            <option value="outreach">Outreach</option>
            <option value="component">Component</option>
        </select>
    </div>

    {{-- ... rest of form fields --}}
</div>
```

---

## Project Show Page Updates

Show sub-projects on the project detail page:

```blade
{{-- On project show page, add a sub-projects section --}}

@if($project->children->count() > 0)
<div class="mt-8" id="subprojects">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        Sub-projects ({{ $project->children->count() }})
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($project->children->sortBy('sort_order') as $child)
            <a href="{{ route('projects.show', $child) }}" 
               class="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-lg hover:border-indigo-300 hover:shadow-sm transition">
                <x-dynamic-component :component="'heroicon-o-' . $child->type_icon" 
                                     class="w-6 h-6 flex-shrink-0 {{ $child->type_color }}" />
                <div class="flex-1 min-w-0">
                    <h4 class="font-medium text-gray-900 truncate">{{ $child->name }}</h4>
                    <p class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $child->project_type) }}</p>
                </div>
                <x-status-badge :status="$child->status" size="sm" />
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- Show breadcrumb if this is a child project --}}
@if($project->parent)
<nav class="mb-4">
    <ol class="flex items-center gap-2 text-sm">
        <li>
            <a href="{{ route('projects.index') }}" class="text-gray-500 hover:text-gray-700">Projects</a>
        </li>
        @foreach($project->breadcrumb as $ancestor)
            <li class="flex items-center gap-2">
                <x-heroicon-s-chevron-right class="w-4 h-4 text-gray-400" />
                @if($loop->last)
                    <span class="text-gray-900 font-medium">{{ $ancestor->name }}</span>
                @else
                    <a href="{{ route('projects.show', $ancestor) }}" class="text-gray-500 hover:text-gray-700">
                        {{ $ancestor->name }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
```

---

## Summary of Changes

1. **Model**: Add helper methods for hierarchy (already done via migrations)

2. **Livewire Component**: 
   - Add `hierarchyFilter` property
   - Add `expanded` array to track expanded projects
   - Add `toggleExpand()`, `expandAll()`, `collapseAll()` methods
   - Modify query to filter by hierarchy

3. **Views**:
   - Update main index to include hierarchy filter and view toggles
   - Create `grid-view.blade.php` with expandable cards
   - Create `list-view.blade.php` with expandable rows
   - Create `tree-view.blade.php` with recursive tree
   - Create `tree-node.blade.php` for recursive rendering
   - Update project form with parent/type fields
   - Update project show page with breadcrumbs and sub-projects

4. **Components**:
   - Create/update `status-badge.blade.php`
   - Ensure Heroicons are available

---

## Testing Checklist

- [ ] Grid view shows parent projects with expand/collapse
- [ ] Grid view expanded state shows child projects inline
- [ ] List view shows parent projects with expand/collapse
- [ ] List view expanded state shows indented children
- [ ] Tree view shows full hierarchy
- [ ] Tree view expand/collapse works recursively
- [ ] Hierarchy filter switches between roots/all/orphans
- [ ] Creating a project allows selecting parent
- [ ] Project show page displays breadcrumbs for children
- [ ] Project show page lists sub-projects for parents
- [ ] Type icons display correctly for each project type
