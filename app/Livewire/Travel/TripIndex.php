<?php

namespace App\Livewire\Travel;

use App\Models\Project;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Travel')]
class TripIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $traveler = '';

    #[Url]
    public string $tab = 'all';

    #[Url]
    public string $view = 'cards';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingTraveler(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->type = '';
        $this->traveler = '';
        $this->resetPage();
    }

    public function getTripsProperty()
    {
        $query = Trip::with(['travelers', 'project', 'creator', 'destinations'])
            ->notTemplates();

        // Tab filters
        switch ($this->tab) {
            case 'upcoming':
                $query->upcoming();
                break;
            case 'my':
                $query->forUser(Auth::id());
                break;
            case 'completed':
                $query->completed();
                break;
            case 'templates':
                $query = Trip::templates();
                break;
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('primary_destination_city', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        // Status filter
        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Type filter
        if ($this->type) {
            $query->where('type', $this->type);
        }

        // Traveler filter
        if ($this->traveler) {
            $query->forUser($this->traveler);
        }

        return $query->orderBy('start_date', 'desc')->paginate(12);
    }

    public function getStatsProperty(): array
    {
        $userId = Auth::id();

        return [
            'all' => Trip::notTemplates()->count(),
            'upcoming' => Trip::notTemplates()->upcoming()->count(),
            'my' => Trip::notTemplates()->forUser($userId)->count(),
            'completed' => Trip::notTemplates()->completed()->count(),
            'templates' => Trip::templates()->count(),
        ];
    }

    public function getTravelersProperty()
    {
        return User::whereHas('trips')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getProjectsProperty()
    {
        return Project::orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.travel.trip-index', [
            'trips' => $this->trips,
            'stats' => $this->stats,
            'travelers' => $this->travelers,
            'projects' => $this->projects,
            'typeOptions' => Trip::getTypeOptions(),
            'statusOptions' => Trip::getStatusOptions(),
        ]);
    }
}
