<?php

namespace App\Livewire\People;

use App\Models\ContactList;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Contact Lists')]
class ContactListsIndex extends Component
{
    public string $newListName = '';

    public string $newListDescription = '';

    public string $memberSearch = '';

    public ?int $selectedListId = null;

    public function mount(): void
    {
        $firstListId = ContactList::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->value('id');

        if ($firstListId) {
            $this->selectedListId = (int) $firstListId;
        }
    }

    public function createList(): void
    {
        $this->validate([
            'newListName' => 'required|string|min:2|max:120',
            'newListDescription' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        if (! $user) {
            return;
        }

        $name = trim($this->newListName);

        $existing = ContactList::query()
            ->where('user_id', $user->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            $this->selectedListId = $existing->id;
            $this->dispatch('notify', type: 'info', message: 'List already exists. Opened existing list.');
            $this->newListName = '';
            $this->newListDescription = '';

            return;
        }

        $list = ContactList::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => trim($this->newListDescription) !== '' ? trim($this->newListDescription) : null,
        ]);

        $this->selectedListId = $list->id;
        $this->newListName = '';
        $this->newListDescription = '';
        $this->dispatch('notify', type: 'success', message: 'Contact list created.');
    }

    public function selectList(int $listId): void
    {
        $exists = ContactList::query()
            ->where('user_id', Auth::id())
            ->whereKey($listId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->selectedListId = $listId;
        $this->memberSearch = '';
    }

    public function deleteList(int $listId): void
    {
        $list = ContactList::query()
            ->where('user_id', Auth::id())
            ->whereKey($listId)
            ->first();

        if (! $list) {
            return;
        }

        $list->delete();

        if ($this->selectedListId === $listId) {
            $next = ContactList::query()
                ->where('user_id', Auth::id())
                ->orderBy('name')
                ->value('id');
            $this->selectedListId = $next ? (int) $next : null;
        }

        $this->dispatch('notify', type: 'success', message: 'List deleted.');
    }

    public function removeFromList(int $personId): void
    {
        if (! $this->selectedListId) {
            return;
        }

        $list = ContactList::query()
            ->where('user_id', Auth::id())
            ->whereKey($this->selectedListId)
            ->first();

        if (! $list) {
            return;
        }

        $list->people()->detach($personId);
        $this->dispatch('notify', type: 'success', message: 'Contact removed from list.');
    }

    public function emailList(): ?RedirectResponse
    {
        if (! $this->selectedListId) {
            $this->dispatch('notify', type: 'error', message: 'Choose a list first.');

            return null;
        }

        $list = ContactList::query()
            ->where('user_id', Auth::id())
            ->whereKey($this->selectedListId)
            ->first();

        if (! $list) {
            return null;
        }

        $emails = $list->people()
            ->whereNotNull('people.email')
            ->pluck('people.email')
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            $this->dispatch('notify', type: 'error', message: 'No email addresses found in this list.');

            return null;
        }

        $to = array_shift($emails);
        $query = [];
        if ($emails !== []) {
            $query['bcc'] = implode(',', $emails);
        }

        $mailto = 'mailto:'.$to;
        if ($query !== []) {
            $mailto .= '?'.http_build_query($query);
        }

        return redirect()->away($mailto);
    }

    public function render()
    {
        $userId = Auth::id();

        $lists = ContactList::query()
            ->where('user_id', $userId)
            ->withCount('people')
            ->orderBy('name')
            ->get();

        $selectedList = null;
        $members = collect();

        if ($this->selectedListId) {
            $selectedList = ContactList::query()
                ->where('user_id', $userId)
                ->whereKey($this->selectedListId)
                ->first();

            if ($selectedList) {
                $members = Person::query()
                    ->whereHas('contactLists', fn ($query) => $query->where('contact_lists.id', $selectedList->id))
                    ->with('organization')
                    ->when(trim($this->memberSearch) !== '', function ($query) {
                        $needle = trim($this->memberSearch);
                        $query->where(function ($search) use ($needle) {
                            $search->where('name', 'like', '%'.$needle.'%')
                                ->orWhere('title', 'like', '%'.$needle.'%')
                                ->orWhere('email', 'like', '%'.$needle.'%');
                        });
                    })
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('livewire.people.contact-lists-index', [
            'lists' => $lists,
            'selectedList' => $selectedList,
            'members' => $members,
        ]);
    }
}
