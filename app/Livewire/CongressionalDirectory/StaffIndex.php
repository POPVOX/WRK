<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalOffice;
use App\Models\CongressionalStaffProfile;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Congress Explorer')]
class StaffIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $chamber = '';

    #[Url(except: 'current')]
    public string $status = 'current';

    #[Url(as: 'office_type', except: '')]
    public string $officeType = '';

    #[Url(except: '')]
    public string $title = '';

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'chamber', 'status', 'officeType', 'title'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'chamber', 'officeType', 'title']);
        $this->status = 'current';
        $this->resetPage();
    }

    public function render()
    {
        $staff = CongressionalStaffProfile::query()
            ->with(['currentPosition.office', 'person'])
            ->withCount('observations')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('display_name', 'like', $term)
                        ->orWhereHas('positions', function (Builder $query) use ($term): void {
                            $query->where('title', 'like', $term)
                                ->orWhereHas('office', fn (Builder $query) => $query
                                    ->where('name', 'like', $term)
                                    ->orWhere('office_code', 'like', $term));
                        });
                });
            })
            ->when($this->chamber !== '', fn (Builder $query) => $query->where('chamber', $this->chamber))
            ->when($this->status === 'current', fn (Builder $query) => $query
                ->whereHas('positions', fn (Builder $query) => $query->where('is_current', true)))
            ->when($this->status === 'former', fn (Builder $query) => $query
                ->whereDoesntHave('positions', fn (Builder $query) => $query->where('is_current', true)))
            ->when($this->officeType !== '', fn (Builder $query) => $query
                ->whereHas('positions.office', fn (Builder $query) => $query->where('office_type', $this->officeType)))
            ->when($this->title !== '', fn (Builder $query) => $query
                ->whereHas('positions', fn (Builder $query) => $query->where('title', 'like', '%'.trim($this->title).'%')))
            ->orderBy('display_name')
            ->paginate(25);

        return view('livewire.congressional-directory.staff-index', [
            'staff' => $staff,
            'officeTypes' => CongressionalOffice::query()
                ->whereNotNull('office_type')
                ->distinct()
                ->orderBy('office_type')
                ->pluck('office_type'),
            'totalProfiles' => CongressionalStaffProfile::query()->count(),
            'currentProfiles' => CongressionalStaffProfile::query()
                ->whereHas('positions', fn (Builder $query) => $query->where('is_current', true))
                ->count(),
            'linkedProfiles' => CongressionalStaffProfile::query()->whereNotNull('person_id')->count(),
        ]);
    }
}
