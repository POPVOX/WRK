<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Notifications\TeamInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    public bool $showInactive = false;

    public ?string $tempPassword = null;

    // Activation link modal
    public bool $showActivationModal = false;

    public ?string $activationLink = null;

    public ?string $activationUserName = null;

    public ?string $activationUserEmail = null;

    // Bulk activation links
    public bool $showBulkLinksModal = false;

    public array $bulkActivationLinks = [];

    // Edit staff member
    public bool $showEditModal = false;

    public ?int $editingUserId = null;

    public string $editName = '';

    public string $editEmail = '';

    public string $editAccessLevel = 'staff';

    protected $rules = [
        'newName' => 'required|string|max:255',
        'newEmail' => 'required|email|unique:users,email',
    ];

    protected function editRules(): array
    {
        return [
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|unique:users,email,'.$this->editingUserId,
            'editAccessLevel' => 'required|in:staff,management,admin',
        ];
    }

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
            'access_level' => $this->newIsAdmin ? 'admin' : 'staff',
            'is_active' => true,
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

        $makeAdmin = ! $user->isAdmin();
        $user->update([
            'is_admin' => $makeAdmin,
            'access_level' => $makeAdmin ? 'admin' : 'staff',
        ]);
        $this->dispatch('notify', type: 'success', message: 'Admin status updated.');
    }

    public function deactivateStaff(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');

            return;
        }

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'You cannot deactivate your own account.');

            return;
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'is_active' => false,
                'is_visible' => false,
                'deactivated_at' => now(),
                'deactivated_by' => auth()->id(),
                'remember_token' => null,
                'activation_token' => null,
                'activation_token_expires_at' => null,
                'google_access_token' => null,
                'google_refresh_token' => null,
                'google_token_expires_at' => null,
                'calendar_sync_status' => 'disconnected',
            ])->save();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        $this->dispatch('notify', type: 'success', message: "{$user->name} was deactivated. Historical records were preserved.");
    }

    public function reactivateStaff(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');

            return;
        }

        $user->forceFill([
            'is_active' => true,
            'is_visible' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ])->save();

        $this->dispatch('notify', type: 'success', message: "{$user->name} was reactivated and can sign in again.");
    }

    public function openEditModal(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');

            return;
        }

        $this->editingUserId = $userId;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editAccessLevel = $user->isAdmin()
            ? 'admin'
            : (in_array($user->access_level, ['staff', 'management'], true) ? $user->access_level : 'staff');
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingUserId = null;
        $this->editName = '';
        $this->editEmail = '';
        $this->editAccessLevel = 'staff';
    }

    public function saveEdit(): void
    {
        $this->validate($this->editRules());

        $user = User::find($this->editingUserId);
        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');

            return;
        }

        if ($user->id === auth()->id() && $user->isAdmin() && $this->editAccessLevel !== 'admin') {
            $this->addError('editAccessLevel', 'You cannot remove your own administrator access.');

            return;
        }

        $user->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
            'access_level' => $this->editAccessLevel,
            'is_admin' => $this->editAccessLevel === 'admin',
        ]);

        $this->closeEditModal();
        $this->dispatch('notify', type: 'success', message: 'Staff member updated.');
    }

    public function generateActivationLink(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');

            return;
        }

        if (! $user->is_active) {
            $this->dispatch('notify', type: 'error', message: 'Reactivate this staff member before generating an activation link.');

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
        $users = User::active()->whereNull('activated_at')->get();
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
        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'User not found.');

            return;
        }

        if (! $user->is_active) {
            $this->dispatch('notify', type: 'error', message: 'Reactivate this staff member before sending an invite.');

            return;
        }

        // Generate activation token if needed
        if (! $user->activation_token || ($user->activation_token_expires_at && $user->activation_token_expires_at->isPast())) {
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
        $users = User::active()->whereNull('activated_at')->get();
        $sent = 0;

        foreach ($users as $user) {
            // Generate activation token if needed
            if (! $user->activation_token || ($user->activation_token_expires_at && $user->activation_token_expires_at->isPast())) {
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
            ->when(! $this->showInactive, fn ($query) => $query->active())
            ->when($this->search, function ($query) {
                $query->where(function ($searchQuery): void {
                    $searchQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.staff-management', [
            'staff' => $staff,
        ]);
    }
}
