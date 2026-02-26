<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Projects')]
class ProjectList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterScope = '';

    public string $filterLead = '';

    public string $sortBy = 'date'; // 'date', 'alpha', 'lead', 'status'

    public string $viewMode = 'grid'; // 'grid', 'list', 'tree', 'timeline'

    // Hierarchy filter: 'roots' (parent projects only), 'all' (flat list)
    public string $hierarchyFilter = 'roots';

    // Track expanded projects for grid/list views
    public array $expanded = [];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode)
    {
        $this->viewMode = $mode;
    }

    public function setSortBy(string $sort)
    {
        $this->sortBy = $sort;
    }

    public function toggleExpand(int $projectId): void
    {
        if (in_array($projectId, $this->expanded)) {
            $this->expanded = array_values(array_diff($this->expanded, [$projectId]));
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

    public function updateProjectStatus(int $projectId, string $status)
    {
        $project = Project::find($projectId);
        if ($project && array_key_exists($status, Project::STATUSES)) {
            $project->update(['status' => $status]);
            $this->dispatch('notify', type: 'success', message: 'Status updated!');
        }
    }

    public function render()
    {
        $query = Project::query()
            ->with(['parent', 'children'])
            ->withCount(['meetings', 'milestones', 'questions', 'children'])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterScope, function ($q) {
                $q->where('scope', $this->filterScope);
            })
            ->when($this->filterLead, function ($q) {
                $q->where('lead', $this->filterLead);
            });

        // Apply hierarchy filter (except for tree view which always shows roots)
        if ($this->viewMode === 'tree' || $this->hierarchyFilter === 'roots') {
            $query->whereNull('parent_project_id');
        }

        // Apply sorting
        switch ($this->sortBy) {
            case 'alpha':
                $query->orderBy('name', 'asc');
                break;
            case 'lead':
                $query->orderBy('lead', 'asc')->orderBy('start_date', 'asc');
                break;
            case 'status':
                $query->orderByRaw("CASE status 
                    WHEN 'planning' THEN 1 
                    WHEN 'active' THEN 2 
                    WHEN 'on_hold' THEN 3 
                    WHEN 'completed' THEN 4 
                    WHEN 'archived' THEN 5 
                    ELSE 6 END")->orderBy('start_date', 'asc');
                break;
            case 'date':
            default:
                $query->orderBy('sort_order')->orderBy('start_date', 'asc');
                break;
        }

        // Get unique leads for filter
        $leads = Project::whereNotNull('lead')->distinct()->pluck('lead')->sort()->values();

        // For timeline view, group projects by month
        $timelineData = [];
        if ($this->viewMode === 'timeline') {
            $allProjects = $query->get();

            // Generate months from Jan 2026 to Dec 2026
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = Carbon::create(2026, $month, 1);
                $monthKey = $monthDate->format('Y-m');
                $monthName = $monthDate->format('M Y');

                $monthProjects = $allProjects->filter(function ($project) use ($monthDate) {
                    if (! $project->start_date && ! $project->target_end_date) {
                        return false;
                    }

                    $start = $project->start_date ?? $project->target_end_date;
                    $end = $project->target_end_date ?? $project->start_date;

                    $monthStart = $monthDate->copy()->startOfMonth();
                    $monthEnd = $monthDate->copy()->endOfMonth();

                    // Project overlaps with this month
                    return $start <= $monthEnd && $end >= $monthStart;
                });

                $timelineData[$monthKey] = [
                    'name' => $monthName,
                    'projects' => $monthProjects,
                ];
            }
        }

        // For tree view, load recursive children
        $treeProjects = collect();
        if ($this->viewMode === 'tree') {
            $treeProjects = $query->with('childrenRecursive')->get();
        }

        return view('livewire.projects.project-list', [
            'projects' => in_array($this->viewMode, ['grid', 'list']) ? $query->paginate(12) : collect(),
            'treeProjects' => $treeProjects,
            'timelineData' => $timelineData,
            'statuses' => Project::STATUSES,
            'scopes' => ['US' => 'US', 'Global' => 'Global', 'Comms' => 'Comms'],
            'leads' => $leads,
        ]);
    }
}
