<?php

namespace App\Livewire\Appropriations;

use App\Models\LegislativeReport;
use App\Models\ReportingRequirement;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Appropriations Tracker')]
class Dashboard extends Component
{
    #[Url]
    public string $selectedFiscalYear = '';

    #[Url]
    public string $filterStatus = 'all';

    #[Url]
    public string $filterAgency = '';

    #[Url]
    public string $filterCategory = '';

    #[Url]
    public string $searchQuery = '';

    public string $viewMode = 'grid'; // grid or list

    public function mount(): void
    {
        if (empty($this->selectedFiscalYear)) {
            $this->selectedFiscalYear = LegislativeReport::latest('fiscal_year')->value('fiscal_year') ?? 'FY2026';
        }
    }

    public function getStatsProperty(): array
    {
        $baseQuery = ReportingRequirement::whereHas('legislativeReport', function ($q) {
            $q->where('fiscal_year', $this->selectedFiscalYear);
        });

        return [
            'total_requirements' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'overdue' => (clone $baseQuery)->where('status', '!=', 'submitted')
                ->where('due_date', '<', now())->count(),
            'submitted' => (clone $baseQuery)->where('status', 'submitted')->count(),
            'upcoming_7_days' => (clone $baseQuery)->where('status', '!=', 'submitted')
                ->whereBetween('due_date', [now(), now()->addDays(7)])->count(),
        ];
    }

    public function getRequirementsProperty()
    {
        $query = ReportingRequirement::with(['legislativeReport', 'assignedTo'])
            ->whereHas('legislativeReport', function ($q) {
                $q->where('fiscal_year', $this->selectedFiscalYear);
            });

        if ($this->filterStatus !== 'all') {
            if ($this->filterStatus === 'overdue') {
                $query->where('status', '!=', 'submitted')
                    ->where('due_date', '<', now());
            } elseif ($this->filterStatus === 'upcoming') {
                $query->where('status', '!=', 'submitted')
                    ->whereBetween('due_date', [now(), now()->addDays(14)]);
            } else {
                $query->where('status', $this->filterStatus);
            }
        }

        if ($this->filterAgency) {
            $query->where('responsible_agency', 'like', '%' . $this->filterAgency . '%');
        }

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('report_title', 'like', "%{$this->searchQuery}%")
                    ->orWhere('description', 'like', "%{$this->searchQuery}%")
                    ->orWhere('responsible_agency', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->orderByRaw('CASE 
            WHEN status = "submitted" THEN 2 
            WHEN due_date < NOW() THEN 0 
            ELSE 1 
        END')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getAgenciesProperty(): array
    {
        return ReportingRequirement::distinct()
            ->pluck('responsible_agency')
            ->sort()
            ->values()
            ->toArray();
    }

    public function getFiscalYearsProperty(): array
    {
        $years = LegislativeReport::distinct()
            ->pluck('fiscal_year')
            ->sort()
            ->reverse()
            ->values()
            ->toArray();

        // Always include current and next FY
        $currentYear = date('Y');
        $currentFY = 'FY' . ($currentYear + (date('n') >= 10 ? 1 : 0));
        $nextFY = 'FY' . ($currentYear + (date('n') >= 10 ? 2 : 1));

        $years = array_unique(array_merge([$nextFY, $currentFY], $years));
        rsort($years);

        return $years;
    }

    public function getReportsProperty()
    {
        return LegislativeReport::where('fiscal_year', $this->selectedFiscalYear)
            ->withCount([
                'requirements',
                'requirements as pending_count' => fn($q) => $q->where('status', 'pending'),
                'requirements as overdue_count' => fn($q) => $q->where('status', '!=', 'submitted')->where('due_date', '<', now()),
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function exportToCSV(): StreamedResponse
    {
        $requirements = $this->requirements;
        $fiscalYear = $this->selectedFiscalYear;

        return response()->streamDownload(function () use ($requirements) {
            echo "Report Title,Agency,Due Date,Status,Timeline,Category,Description,Recipients,Page Ref,Source Report\n";

            foreach ($requirements as $req) {
                echo sprintf(
                    "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    str_replace('"', '""', $req->report_title),
                    $req->responsible_agency,
                    $req->due_date?->format('Y-m-d') ?? 'TBD',
                    $req->effective_status,
                    $req->timeline_value ? "{$req->timeline_value} days" : ($req->timeline_type ?? 'N/A'),
                    $req->category,
                    str_replace('"', '""', $req->description),
                    str_replace('"', '""', $req->reporting_recipients),
                    $req->source_page_reference ?? '',
                    $req->legislativeReport?->display_name ?? ''
                );
            }
        }, "appropriations-tracker-{$fiscalYear}.csv");
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function clearFilters(): void
    {
        $this->filterStatus = 'all';
        $this->filterAgency = '';
        $this->filterCategory = '';
        $this->searchQuery = '';
    }

    public function render()
    {
        return view('livewire.appropriations.dashboard', [
            'stats' => $this->stats,
            'requirements' => $this->requirements,
            'agencies' => $this->agencies,
            'fiscalYears' => $this->fiscalYears,
            'reports' => $this->reports,
        ]);
    }
}

