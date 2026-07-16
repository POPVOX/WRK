<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalOffice;
use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Create Congressional Staff List')]
class StaffListCreate extends Component
{
    use WithPagination;

    public string $name = '';

    public string $description = '';

    public string $search = '';

    public string $chamber = '';

    public string $status = 'current';

    public string $officeType = '';

    public string $title = '';

    public bool $hasRunSearch = false;

    public bool $selectedAllMatches = false;

    /** @var array<int,int|string> */
    public array $selectedProfileIds = [];

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'chamber', 'status', 'officeType', 'title'], true)) {
            $this->hasRunSearch = false;
            $this->selectedAllMatches = false;
            $this->resetPage('resultsPage');
        }
    }

    public function runSearch(): void
    {
        $this->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'chamber' => ['nullable', 'in:,House,Senate'],
            'status' => ['nullable', 'in:,current,former'],
            'officeType' => ['nullable', 'string', 'max:255'],
        ]);

        $this->hasRunSearch = true;
        $this->resetPage('resultsPage');
    }

    /** @param array<int,int|string> $profileIds */
    public function selectVisible(array $profileIds): void
    {
        $allowed = $this->staffQuery()
            ->whereKey(array_map('intval', $profileIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->selectedProfileIds = array_values(array_unique(array_merge($this->selectedProfileIds, $allowed)));
    }

    public function selectAllMatches(): void
    {
        $this->selectedProfileIds = $this->staffQuery()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->selectedAllMatches = true;
    }

    public function clearSelection(): void
    {
        $this->selectedProfileIds = [];
        $this->selectedAllMatches = false;
    }

    public function saveList(): mixed
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'selectedProfileIds' => ['required', 'array', 'min:1'],
            'selectedProfileIds.*' => ['integer'],
        ]);

        $name = trim($validated['name']);
        if (CongressionalStaffList::query()->where('user_id', Auth::id())->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            $this->addError('name', 'You already have a congressional staff list with this name.');

            return null;
        }

        $profileIds = CongressionalStaffProfile::query()
            ->whereKey(array_map('intval', $validated['selectedProfileIds']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $list = DB::transaction(function () use ($name, $profileIds): CongressionalStaffList {
            $list = CongressionalStaffList::query()->create([
                'user_id' => Auth::id(),
                'name' => $name,
                'description' => trim($this->description) ?: null,
                'criteria' => $this->criteria(),
                'selection_mode' => $this->selectedAllMatches ? 'filtered_snapshot' : 'selected',
            ]);
            $now = now();
            foreach (array_chunk($profileIds, 1000) as $chunk) {
                DB::table('congressional_staff_list_members')->insert(array_map(fn (int $profileId) => [
                    'congressional_staff_list_id' => $list->id,
                    'congressional_staff_profile_id' => $profileId,
                    'added_by' => Auth::id(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }

            return $list;
        });

        $this->dispatch('notify', type: 'success', message: "Saved {$list->name} with ".count($profileIds).' staff members.');

        return $this->redirectRoute('congress.lists', ['list' => $list->id], navigate: true);
    }

    /** @return array<string,string> */
    protected function criteria(): array
    {
        return array_filter([
            'search' => trim($this->search),
            'chamber' => $this->chamber,
            'status' => $this->status,
            'office_type' => $this->officeType,
            'title' => trim($this->title),
        ], fn ($value) => $value !== '');
    }

    protected function staffQuery(): Builder
    {
        return CongressionalStaffProfile::query()
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn (Builder $query) => $query
                    ->whereLike('display_name', $term)
                    ->orWhereHas('positions', fn (Builder $query) => $query
                        ->whereLike('title', $term)
                        ->orWhereHas('office', fn (Builder $query) => $query
                            ->whereLike('name', $term)
                            ->orWhereLike('office_code', $term))));
            })
            ->when($this->chamber !== '', fn (Builder $query) => $query->where('chamber', $this->chamber))
            ->when($this->status === 'current', fn (Builder $query) => $query
                ->whereHas('positions', fn (Builder $query) => $query->where('is_current', true)))
            ->when($this->status === 'former', fn (Builder $query) => $query
                ->whereDoesntHave('positions', fn (Builder $query) => $query->where('is_current', true)))
            ->when($this->officeType !== '', fn (Builder $query) => $query
                ->whereHas('positions.office', fn (Builder $query) => $query->where('office_type', $this->officeType)))
            ->when(trim($this->title) !== '', fn (Builder $query) => $query
                ->whereHas('positions', fn (Builder $query) => $query->whereLike('title', '%'.trim($this->title).'%')));
    }

    public function render()
    {
        $results = $this->hasRunSearch
            ? $this->staffQuery()->with('currentPosition.office')->orderBy('display_name')->paginate(50, ['*'], 'resultsPage')
            : null;

        return view('livewire.congressional-directory.staff-list-create', [
            'results' => $results,
            'officeTypes' => CongressionalOffice::query()->whereNotNull('office_type')->distinct()->orderBy('office_type')->pluck('office_type'),
        ]);
    }
}
