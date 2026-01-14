<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Notifications\TeamInvitation;
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

    // Activation link modal
    public bool $showActivationModal = false;

    public ?string $activationLink = null;

    public ?string $activationUserName = null;

    public ?string $activationUserEmail = null;

    // Bulk activation links
    public bool $showBulkLinksModal = false;
    public array $bulkActivationLinks = [];

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

        $user->update(['is_admin' => ! $user->is_admin]);
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

    public function generateActivationLink(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');

            return;
        }

        // Generate a 64-character token
        $token = Str::random(64);
        $expiresAt = now()->addDays(7);

        $user->update([
            'activation_token' => $token,
            'activation_token_expires_at' => $expiresAt,
            'activated_at' => null, // Reset if previously activated
        ]);

        $this->activationLink = url("/activate/{$token}");
        $this->activationUserName = $user->name;
        $this->activationUserEmail = $user->email;
        $this->showActivationModal = true;
    }

    public function closeActivationModal(): void
    {
        $this->showActivationModal = false;
        $this->activationLink = null;
        $this->activationUserName = null;
        $this->activationUserEmail = null;
    }

    // === Bulk Activation Links ===

    public function generateAllActivationLinks(): void
    {
        $users = User::whereNull('activated_at')->get();
        $this->bulkActivationLinks = [];

        foreach ($users as $user) {
            $token = Str::random(64);
            $expiresAt = now()->addDays(7);

            $user->update([
                'activation_token' => $token,
                'activation_token_expires_at' => $expiresAt,
            ]);

            $this->bulkActivationLinks[] = [
                'name' => $user->name,
                'email' => $user->email,
                'link' => url("/activate/{$token}"),
                'expires' => $expiresAt->format('M j, Y'),
            ];
        }

        if (empty($this->bulkActivationLinks)) {
            $this->dispatch('notify', type: 'info', message: 'All staff members have already activated their accounts.');
            return;
        }

        $this->showBulkLinksModal = true;
    }

    public function closeBulkLinksModal(): void
    {
        $this->showBulkLinksModal = false;
        $this->bulkActivationLinks = [];
    }

    public function sendInviteEmail(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');
            return;
        }

        // Generate activation token if needed
        if (!$user->activation_token || ($user->activation_token_expires_at && $user->activation_token_expires_at->isPast())) {
            $token = Str::random(64);
            $user->update([
                'activation_token' => $token,
                'activation_token_expires_at' => now()->addDays(7),
            ]);
        }

        try {
            $user->notify(new TeamInvitation($user->activation_token));
            $this->dispatch('notify', type: 'success', message: "Invite sent to {$user->email}!");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to send email. Check mail configuration.');
        }
    }

    public function sendAllInviteEmails(): void
    {
        $users = User::whereNull('activated_at')->get();
        $sent = 0;

        foreach ($users as $user) {
            // Generate activation token if needed
            if (!$user->activation_token || ($user->activation_token_expires_at && $user->activation_token_expires_at->isPast())) {
                $token = Str::random(64);
                $user->update([
                    'activation_token' => $token,
                    'activation_token_expires_at' => now()->addDays(7),
                ]);
            }

            try {
                $user->notify(new TeamInvitation($user->activation_token));
                $sent++;
            } catch (\Exception $e) {
                // Continue to next user
            }
        }

        if ($sent > 0) {
            $this->dispatch('notify', type: 'success', message: "Sent {$sent} invitation email(s)!");
        } else {
            $this->dispatch('notify', type: 'info', message: 'No invitations to send. All accounts are activated.');
        }
    }

    public function render()
    {
        $staff = User::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.staff-management', [
            'staff' => $staff,
        ]);
    }
}
