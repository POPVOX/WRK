<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalOffice;
use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public string $newListName = '';

    public ?int $selectedListId = null;

    /** @var array<int,int|string> */
    public array $checkedProfileIds = [];

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);

        $firstListId = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->value('id');

        $this->selectedListId = $firstListId ? (int) $firstListId : null;
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

    public function createList(): void
    {
        $this->validate([
            'newListName' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $name = trim($this->newListName);
        $existing = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            $this->selectedListId = $existing->id;
            $this->newListName = '';
            $this->dispatch('notify', type: 'info', message: 'That list already exists and is now selected.');

            return;
        }

        $list = CongressionalStaffList::query()->create([
            'user_id' => Auth::id(),
            'name' => $name,
        ]);

        $this->selectedListId = $list->id;
        $this->newListName = '';
        $this->dispatch('notify', type: 'success', message: 'Congressional staff list created.');
    }

    /** @param array<int,int|string> $profileIds */
    public function checkProfiles(array $profileIds): void
    {
        $this->checkedProfileIds = collect($this->checkedProfileIds)
            ->merge($profileIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function clearCheckedProfiles(): void
    {
        $this->checkedProfileIds = [];
    }

    public function addCheckedToList(): void
    {
        $list = $this->selectedList();
        if (! $list) {
            $this->dispatch('notify', type: 'error', message: 'Create or choose a staff list first.');

            return;
        }

        $profileIds = $this->filteredStaffQuery()
            ->whereKey(collect($this->checkedProfileIds)->map(fn ($id) => (int) $id)->filter()->unique())
            ->pluck('id');
        $added = $this->insertProfiles($list, $profileIds->all());
        $this->checkedProfileIds = [];

        $this->dispatch(
            'notify',
            type: 'success',
            message: $added > 0 ? "Added {$added} staff to {$list->name}." : 'Those staff are already on the list.'
        );
    }

    public function addAllMatchesToList(): void
    {
        $list = $this->selectedList();
        if (! $list) {
            $this->dispatch('notify', type: 'error', message: 'Create or choose a staff list first.');

            return;
        }

        $added = 0;
        $this->filteredStaffQuery()
            ->select('congressional_staff_profiles.id')
            ->orderBy('congressional_staff_profiles.id')
            ->chunkById(1000, function ($profiles) use ($list, &$added): void {
                $added += $this->insertProfiles($list, $profiles->pluck('id')->all());
            }, 'congressional_staff_profiles.id', 'id');

        $this->dispatch(
            'notify',
            type: 'success',
            message: $added > 0 ? "Added {$added} matching staff to {$list->name}." : 'All matching staff are already on the list.'
        );
    }

    protected function selectedList(): ?CongressionalStaffList
    {
        if (! $this->selectedListId) {
            return null;
        }

        return CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->whereKey($this->selectedListId)
            ->first();
    }

    /** @param array<int,int> $profileIds */
    protected function insertProfiles(CongressionalStaffList $list, array $profileIds): int
    {
        $added = 0;
        $now = now();

        foreach (array_chunk(array_values(array_unique($profileIds)), 1000) as $chunk) {
            $rows = array_map(fn (int $profileId) => [
                'congressional_staff_list_id' => $list->id,
                'congressional_staff_profile_id' => $profileId,
                'added_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk);

            $added += DB::table('congressional_staff_list_members')->insertOrIgnore($rows);
        }

        return $added;
    }

    protected function filteredStaffQuery(): Builder
    {
        return CongressionalStaffProfile::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->whereLike('display_name', $term)
                        ->orWhereHas('positions', function (Builder $query) use ($term): void {
                            $query->whereLike('title', $term)
                                ->orWhereHas('office', fn (Builder $query) => $query
                                    ->whereLike('name', $term)
                                    ->orWhereLike('office_code', $term));
                        });
                });
            })
            ->when($this->chamber !== '', fn (Builder $query) => $query->where('chamber', $this->chamber))
            ->when($this->status === 'current', fn (Builder $query) => $query
                ->directoryActive()
                ->whereHas('positions', fn (Builder $query) => $query->where('is_current', true)))
            ->when($this->status === 'former', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->directoryInactive()
                    ->orWhereDoesntHave('positions', fn (Builder $query) => $query->where('is_current', true))))
            ->when($this->officeType !== '', fn (Builder $query) => $query
                ->whereHas('positions.office', fn (Builder $query) => $query->where('office_type', $this->officeType)))
            ->when($this->title !== '', fn (Builder $query) => $query
                ->whereHas('positions', fn (Builder $query) => $query->whereLike('title', '%'.trim($this->title).'%')));
    }

    public function render()
    {
        $staff = $this->filteredStaffQuery()
            ->with(['currentPosition.office', 'person'])
            ->withCount('observations')
            ->orderBy('display_name')
            ->paginate(25);

        $lists = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->withCount('profiles')
            ->orderBy('name')
            ->get();

        return view('livewire.congressional-directory.staff-index', [
            'staff' => $staff,
            'lists' => $lists,
            'selectedList' => $lists->firstWhere('id', $this->selectedListId),
            'officeTypes' => CongressionalOffice::query()
                ->whereNotNull('office_type')
                ->distinct()
                ->orderBy('office_type')
                ->pluck('office_type'),
            'totalProfiles' => CongressionalStaffProfile::query()->count(),
            'currentProfiles' => CongressionalStaffProfile::query()
                ->directoryActive()
                ->whereHas('positions', fn (Builder $query) => $query->where('is_current', true))
                ->count(),
            'linkedProfiles' => CongressionalStaffProfile::query()->whereNotNull('person_id')->count(),
            'pendingChangeSignals' => CongressionalStaffChangeSignal::query()->where('status', 'pending')->count(),
        ]);
    }
}
