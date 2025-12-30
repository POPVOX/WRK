<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Staff Management')]
class StaffManagement extends Component
{
    public string $newName = '';
    public string $newEmail = '';
    public bool $newIsAdmin = false;
    public string $search = '';

    public bool $showAddModal = false;
    public ?string $tempPassword = null;

    protected $rules = [
        'newName' => 'required|string|max:255',
        'newEmail' => 'required|email|unique:users,email',
    ];

    public function openAddModal()
    {
        $this->reset(['newName', 'newEmail', 'newIsAdmin', 'tempPassword']);
        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->tempPassword = null;
    }

    public function addStaff()
    {
        $this->validate();

        // Generate a temporary password
        $this->tempPassword = Str::random(12);

        User::create([
            'name' => $this->newName,
            'email' => $this->newEmail,
            'password' => Hash::make($this->tempPassword),
            'is_admin' => $this->newIsAdmin,
        ]);

        $this->reset(['newName', 'newEmail', 'newIsAdmin']);
        $this->dispatch('notify', type: 'success', message: 'Staff member added! Share the temporary password with them.');
    }

    public function toggleAdmin(int $userId)
    {
        $user = User::find($userId);

        // Prevent removing admin from yourself
        if ($user->id === auth()->id() && $user->is_admin) {
            $this->dispatch('notify', type: 'error', message: 'You cannot remove admin status from yourself.');
            return;
        }

        $user->update(['is_admin' => !$user->is_admin]);
        $this->dispatch('notify', type: 'success', message: 'Admin status updated.');
    }

    public function deleteStaff(int $userId)
    {
        $user = User::find($userId);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'You cannot delete your own account.');
            return;
        }

        $user->delete();
        $this->dispatch('notify', type: 'success', message: 'Staff member removed.');
    }

    public function render()
    {
        $staff = User::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.staff-management', [
            'staff' => $staff,
        ]);
    }
}
