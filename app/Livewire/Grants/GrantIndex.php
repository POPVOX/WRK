<?php

namespace App\Livewire\Grants;

use App\Models\Grant;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class GrantIndex extends Component
{
    use WithPagination;

    public string $activeTab = 'dashboard';

    public string $search = '';

    public string $filterStatus = '';

    public string $filterFunder = '';

    public string $grantSearch = '';

    public string $grantStatusFilter = '';

    public ?int $grantFunderFilter = null;

    public string $reportStatusFilter = '';

    public ?int $reportFunderFilter = null;

    // Create/Edit Grant Modal
    public bool $showGrantModal = false;

    public ?int $editingGrantId = null;

    public string $grantName = '';

    public ?int $grantFunderId = null;

    public string $grantStatus = 'pending';

    public ?string $grantAmount = '';

    public ?string $grantStartDate = null;

    public ?string $grantEndDate = null;

    public string $grantDescription = '';

    public string $grantDeliverables = '';

    public string $grantVisibility = 'management';

    public string $grantNotes = '';

    public string $grantScope = 'all';

    public ?int $grantPrimaryProjectId = null;

    // Create/Edit Funder Modal
    public bool $showFunderModal = false;

    public ?int $editingFunderId = null;

    public string $funderName = '';

    public string $funderType = 'Funder';

    public string $funderWebsite = '';

    public string $funderLinkedIn = '';

    public string $funderDescription = '';

    public string $funderPriorities = '';

    public string $funderStatus = 'prospective'; // prospective, current

    // Organization autocomplete
    public string $orgSearch = '';

    public bool $showOrgSuggestions = false;

    public ?int $selectedOrgId = null;

    public function mount(): void
    {
        // Admin-only access
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Access denied. Admin only.');
        }
    }

    protected $queryString = [
        'activeTab' => ['except' => 'dashboard'],
        'search' => ['except' => ''],
    ];

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // --- Grant Modal Methods ---
    public function openCreateGrantModal(?int $funderId = null): void
    {
        $this->resetGrantForm();
        if ($funderId) {
            $this->grantFunderId = $funderId;
        }
        $this->showGrantModal = true;
    }

    public function openEditModal(int $grantId): void
    {
        $grant = Grant::findOrFail($grantId);
        $this->editingGrantId = $grant->id;
        $this->grantName = $grant->name;
        $this->grantFunderId = $grant->organization_id;
        $this->grantStatus = $grant->status ?? 'pending';
        $this->grantAmount = $grant->amount ? (string) $grant->amount : '';
        $this->grantStartDate = $grant->start_date?->format('Y-m-d');
        $this->grantEndDate = $grant->end_date?->format('Y-m-d');
        $this->grantDescription = $grant->description ?? '';
        $this->grantDeliverables = $grant->deliverables ?? '';
        $this->grantVisibility = $grant->visibility ?? 'management';
        $this->grantNotes = $grant->notes ?? '';
        $this->grantScope = $grant->scope ?? 'all';
        $this->grantPrimaryProjectId = $grant->primary_project_id;
        $this->showGrantModal = true;
    }

    public function closeModal(): void
    {
        $this->showGrantModal = false;
        $this->resetGrantForm();
    }

    public function resetGrantForm(): void
    {
        $this->editingGrantId = null;
        $this->grantName = '';
        $this->grantFunderId = null;
        $this->grantStatus = 'pending';
        $this->grantAmount = '';
        $this->grantStartDate = null;
        $this->grantEndDate = null;
        $this->grantDescription = '';
        $this->grantDeliverables = '';
        $this->grantVisibility = 'management';
        $this->grantNotes = '';
        $this->grantScope = 'all';
        $this->grantPrimaryProjectId = null;
    }

    public function saveGrant(): void
    {
        $validated = $this->validate([
            'grantName' => 'required|string|max:255',
            'grantFunderId' => 'required|exists:organizations,id',
            'grantStatus' => 'required|in:prospective,pending,active,completed,declined',
            'grantAmount' => 'nullable|numeric|min:0',
            'grantStartDate' => 'nullable|date',
            'grantEndDate' => 'nullable|date|after_or_equal:grantStartDate',
            'grantDescription' => 'nullable|string',
            'grantDeliverables' => 'nullable|string',
            'grantVisibility' => 'required|in:all,management,admin',
            'grantNotes' => 'nullable|string',
            'grantScope' => 'required|in:all,us,global,project',
            'grantPrimaryProjectId' => 'nullable|exists:projects,id',
        ]);

        $data = [
            'name' => $this->grantName,
            'organization_id' => $this->grantFunderId,
            'status' => $this->grantStatus,
            'amount' => $this->grantAmount !== '' ? (float) $this->grantAmount : null,
            'start_date' => $this->grantStartDate,
            'end_date' => $this->grantEndDate,
            'description' => $this->grantDescription,
            'deliverables' => $this->grantDeliverables,
            'visibility' => $this->grantVisibility,
            'notes' => $this->grantNotes,
            'scope' => $this->grantScope,
            'primary_project_id' => $this->grantScope === 'project' ? $this->grantPrimaryProjectId : null,
        ];

        if ($this->editingGrantId) {
            Grant::where('id', $this->editingGrantId)->update($data);
        } else {
            Grant::create($data);
        }

        $this->closeModal();
    }

    // --- Funder Modal Methods ---
    public function openCreateFunderModal(): void
    {
        $this->resetFunderForm();
        $this->showFunderModal = true;
    }

    public function openEditFunderModal(int $funderId): void
    {
        $funder = Organization::findOrFail($funderId);
        $this->editingFunderId = $funder->id;
        $this->funderName = $funder->name;
        $this->funderType = $funder->type ?? 'Funder';
        $this->funderWebsite = $funder->website ?? '';
        $this->funderLinkedIn = $funder->linkedin_url ?? '';
        $this->funderDescription = $funder->description ?? '';
        $this->funderPriorities = $funder->funder_priorities ?? '';
        // Determine status based on grants
        $this->funderStatus = $funder->grants()->where('status', 'active')->exists() ? 'current' : 'prospective';
        $this->showFunderModal = true;
    }

    public function closeFunderModal(): void
    {
        $this->showFunderModal = false;
        $this->resetFunderForm();
    }

    public function resetFunderForm(): void
    {
        $this->editingFunderId = null;
        $this->funderName = '';
        $this->funderType = 'Funder';
        $this->funderWebsite = '';
        $this->funderLinkedIn = '';
        $this->funderDescription = '';
        $this->funderPriorities = '';
        $this->funderStatus = 'prospective';
        $this->orgSearch = '';
        $this->showOrgSuggestions = false;
        $this->selectedOrgId = null;
    }

    public function updatedOrgSearch(): void
    {
        $this->showOrgSuggestions = strlen($this->orgSearch) >= 2;

        // Clear selection if user is typing a new search
        if ($this->selectedOrgId && $this->funderName !== $this->orgSearch) {
            $this->selectedOrgId = null;
        }

        // Sync funderName with orgSearch when not using a selected org
        if (! $this->selectedOrgId) {
            $this->funderName = $this->orgSearch;
        }
    }

    public function getOrgSuggestionsProperty()
    {
        if (strlen($this->orgSearch) < 2) {
            return collect();
        }

        return Organization::where('name', 'like', '%'.$this->orgSearch.'%')
            ->whereNotIn('id', function ($query) {
                // Exclude organizations that are already funders (have is_funder or have grants)
                $query->select('organization_id')->from('grants');
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    public function selectOrganization(int $orgId): void
    {
        $org = Organization::find($orgId);
        if ($org) {
            $this->selectedOrgId = $org->id;
            $this->funderName = $org->name;
            $this->orgSearch = $org->name;
            $this->funderType = $org->type ?? 'Funder';
            $this->funderWebsite = $org->website ?? '';
            $this->funderLinkedIn = $org->linkedin_url ?? '';
            $this->funderDescription = $org->description ?? '';
            $this->funderPriorities = $org->funder_priorities ?? '';
            $this->showOrgSuggestions = false;
        }
    }

    public function hideOrgSuggestions(): void
    {
        // Small delay before hiding to allow click on suggestion
        $this->showOrgSuggestions = false;
    }

    public function saveFunder(): void
    {
        $this->validate([
            'funderName' => 'required|string|max:255',
            'funderType' => 'nullable|string|max:100',
            'funderWebsite' => 'nullable|url|max:500',
            'funderLinkedIn' => 'nullable|url|max:500',
            'funderDescription' => 'nullable|string',
            'funderPriorities' => 'nullable|string',
        ]);

        $data = [
            'name' => $this->funderName,
            'type' => $this->funderType,
            'website' => $this->funderWebsite ?: null,
            'linkedin_url' => $this->funderLinkedIn ?: null,
            'description' => $this->funderDescription ?: null,
            'funder_priorities' => $this->funderPriorities ?: null,
            'is_funder' => true,
        ];

        if ($this->editingFunderId) {
            // Editing an existing funder
            Organization::where('id', $this->editingFunderId)->update($data);
        } elseif ($this->selectedOrgId) {
            // Selected an existing organization - update it to be a funder
            Organization::where('id', $this->selectedOrgId)->update($data);
        } else {
            // Creating a brand new funder
            Organization::create($data);
        }

        $this->closeFunderModal();
    }

    /**
     * Get enhanced stats for the dashboard
     */
    public function getStatsProperty(): array
    {
        $activeGrants = Grant::where('status', 'active')->get();

        return [
            'total_funders' => Organization::where('is_funder', true)->count(),
            'current_funders' => Organization::where('is_funder', true)
                ->whereHas('grants', fn ($q) => $q->where('status', 'active'))
                ->count(),
            'prospective_count' => Organization::where('is_funder', true)
                ->whereDoesntHave('grants', fn ($q) => $q->where('status', 'active'))
                ->count(),
            'total_grants' => Grant::count(),
            'active_grants' => $activeGrants->count(),
            'completed_grants' => Grant::where('status', 'completed')->count(),
            'active_funding' => $activeGrants->sum('amount'),
            'pipeline_value' => Grant::where('status', 'prospective')->sum('amount') ?? 0,
        ];
    }

    /**
     * Get items that need attention for the dashboard
     */
    public function getNeedsAttentionProperty(): array
    {
        $grantsEndingSoon = Grant::with('funder')
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addMonths(2))
            ->orderBy('end_date')
            ->get();

        // Generate suggestion
        $prospectiveCount = Organization::where('is_funder', true)
            ->whereDoesntHave('grants', fn ($q) => $q->where('status', 'active'))
            ->count();
        
        $suggestion = null;
        if ($prospectiveCount > 0) {
            $suggestion = "You have {$prospectiveCount} prospective funder".($prospectiveCount > 1 ? 's' : '').' in your pipeline. Consider scheduling outreach.';
        } elseif ($grantsEndingSoon->isEmpty()) {
            $suggestion = 'All grants are on track. Consider researching new funding opportunities.';
        }

        return [
            'grants_ending_soon' => $grantsEndingSoon,
            'suggestion' => $suggestion,
        ];
    }

    /**
     * Get upcoming deadlines for the calendar
     */
    public function getUpcomingDeadlinesProperty()
    {
        $deadlines = collect();

        // Grant end dates
        Grant::with('funder')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addMonths(3))
            ->orderBy('end_date')
            ->get()
            ->each(function ($grant) use ($deadlines) {
                $deadlines->push([
                    'date' => $grant->end_date,
                    'title' => $grant->name.' ends',
                    'funder' => $grant->funder->name ?? 'Unknown',
                    'type' => 'Grant End',
                    'url' => route('grants.show', $grant),
                ]);
            });

        return $deadlines->sortBy('date')->values();
    }

    /**
     * Get funder breakdown for the health chart
     */
    public function getFunderBreakdownProperty()
    {
        return Organization::where('is_funder', true)
            ->withSum(['grants' => fn ($q) => $q->where('status', 'active')], 'amount')
            ->orderByDesc('grants_sum_amount')
            ->get()
            ->filter(fn ($funder) => ($funder->grants_sum_amount ?? 0) > 0)
            ->map(function ($funder) {
                $funder->total_funding = $funder->grants_sum_amount ?? 0;

                return $funder;
            });
    }


    public function render()
    {
        // Get funders with their grants
        $fundersQuery = Organization::where('is_funder', true)
            ->withCount(['grants', 'grants as active_grants_count' => fn ($q) => $q->where('status', 'active')])
            ->withSum(['grants' => fn ($q) => $q->where('status', 'active')], 'amount')
            ->with(['grants' => fn ($q) => $q->orderByDesc('created_at')->take(5)]);

        if ($this->search) {
            $fundersQuery->where('name', 'like', '%'.$this->search.'%');
        }

        $funders = $fundersQuery->orderBy('name')->get()->map(function ($funder) {
            $funder->total_funding = $funder->grants_sum_amount ?? 0;
            return $funder;
        });

        // Separate into current (has active grants) and prospective
        $currentFunders = $funders->filter(fn ($f) => $f->active_grants_count > 0);
        $prospectiveFunders = $funders->filter(fn ($f) => $f->active_grants_count === 0);

        // Grants for table view (with filters)
        $grants = Grant::with(['funder', 'projects'])
            ->when($this->grantSearch, fn ($q) => $q->where('name', 'like', '%'.$this->grantSearch.'%'))
            ->when($this->grantStatusFilter, fn ($q) => $q->where('status', $this->grantStatusFilter))
            ->when($this->grantFunderFilter, fn ($q) => $q->where('organization_id', $this->grantFunderFilter))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.grants.grant-index', [
            'grants' => $grants,
            'funders' => $funders,
            'currentFunders' => $currentFunders,
            'prospectiveFunders' => $prospectiveFunders,
            'statuses' => Grant::STATUSES,
            'scopes' => Grant::SCOPES,
            'projects' => Project::orderBy('name')->get(),
            'stats' => $this->stats,
            'needsAttention' => $this->needsAttention,
            'upcomingDeadlines' => $this->upcomingDeadlines,
            'funderBreakdown' => $this->funderBreakdown,
        ])->title('Funders');
    }
}
