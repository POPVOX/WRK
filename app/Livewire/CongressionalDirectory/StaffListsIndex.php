<?php

namespace App\Livewire\CongressionalDirectory;

use App\Models\CongressionalStaffList;
use App\Models\CongressionalStaffProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Congressional Staff Lists')]
class StaffListsIndex extends Component
{
    use WithPagination;

    public string $newListName = '';

    public string $newListDescription = '';

    public string $memberSearch = '';

    public ?int $selectedListId = null;

    public function mount(): void
    {
        abort_unless(config('features.congressional_directory_ui'), 404);

        $firstListId = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->value('id');

        $this->selectedListId = $firstListId ? (int) $firstListId : null;
    }

    public function updatedMemberSearch(): void
    {
        $this->resetPage('listPage');
    }

    public function createList(): void
    {
        $this->validate([
            'newListName' => ['required', 'string', 'min:2', 'max:120'],
            'newListDescription' => ['nullable', 'string', 'max:1000'],
        ]);

        $name = trim($this->newListName);
        $existing = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            $this->selectedListId = $existing->id;
            $this->reset(['newListName', 'newListDescription']);
            $this->dispatch('notify', type: 'info', message: 'That list already exists and is now open.');

            return;
        }

        $list = CongressionalStaffList::query()->create([
            'user_id' => Auth::id(),
            'name' => $name,
            'description' => trim($this->newListDescription) ?: null,
        ]);

        $this->selectedListId = $list->id;
        $this->reset(['newListName', 'newListDescription']);
        $this->dispatch('notify', type: 'success', message: 'Congressional staff list created.');
    }

    public function selectList(int $listId): void
    {
        if (! CongressionalStaffList::query()->where('user_id', Auth::id())->whereKey($listId)->exists()) {
            return;
        }

        $this->selectedListId = $listId;
        $this->memberSearch = '';
        $this->resetPage('listPage');
    }

    public function removeFromList(int $profileId): void
    {
        $list = $this->selectedList();
        if (! $list) {
            return;
        }

        $list->profiles()->detach($profileId);
        $this->dispatch('notify', type: 'success', message: 'Staff member removed from the list.');
    }

    public function deleteList(int $listId): void
    {
        $list = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->whereKey($listId)
            ->first();

        if (! $list) {
            return;
        }

        $list->delete();
        if ($this->selectedListId === $listId) {
            $nextId = CongressionalStaffList::query()
                ->where('user_id', Auth::id())
                ->orderBy('name')
                ->value('id');
            $this->selectedListId = $nextId ? (int) $nextId : null;
        }

        $this->dispatch('notify', type: 'success', message: 'Congressional staff list deleted.');
    }

    protected function selectedList(): ?CongressionalStaffList
    {
        return $this->selectedListId
            ? CongressionalStaffList::query()->where('user_id', Auth::id())->whereKey($this->selectedListId)->first()
            : null;
    }

    public function render()
    {
        $lists = CongressionalStaffList::query()
            ->where('user_id', Auth::id())
            ->withCount('profiles')
            ->orderBy('name')
            ->get();
        $selectedList = $lists->firstWhere('id', $this->selectedListId);
        $members = null;

        if ($selectedList) {
            $members = CongressionalStaffProfile::query()
                ->whereHas('staffLists', fn (Builder $query) => $query->where('congressional_staff_lists.id', $selectedList->id))
                ->with('currentPosition.office')
                ->when(trim($this->memberSearch) !== '', function (Builder $query): void {
                    $term = '%'.trim($this->memberSearch).'%';
                    $query->where(function (Builder $query) use ($term): void {
                        $query->whereLike('display_name', $term)
                            ->orWhereHas('positions', fn (Builder $query) => $query
                                ->whereLike('title', $term)
                                ->orWhereHas('office', fn (Builder $query) => $query->whereLike('name', $term)));
                    });
                })
                ->orderBy('display_name')
                ->paginate(25, ['*'], 'listPage');
        }

        return view('livewire.congressional-directory.staff-lists-index', [
            'lists' => $lists,
            'selectedList' => $selectedList,
            'members' => $members,
        ]);
    }
}
