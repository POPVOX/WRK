<?php

use App\Livewire\Admin\Permissions;
use App\Livewire\Admin\StaffManagement;
use App\Models\Meeting;
use App\Models\User;
use Livewire\Livewire;

test('admin can add deactivate view and reactivate staff without deleting history', function () {
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)
        ->test(StaffManagement::class)
        ->call('openAddModal')
        ->set('newName', 'New Team Member')
        ->set('newEmail', 'new.team@example.org')
        ->call('addStaff')
        ->assertDispatched('notify');

    $staff = User::query()->where('email', 'new.team@example.org')->firstOrFail();
    expect($staff->is_active)->toBeTrue();

    $meeting = Meeting::create([
        'user_id' => $staff->id,
        'meeting_date' => now()->toDateString(),
        'title' => 'Historical staff meeting',
        'status' => Meeting::STATUS_NEW,
    ]);

    $component
        ->call('deactivateStaff', $staff->id)
        ->assertDispatched('notify')
        ->assertDontSee('New Team Member')
        ->set('showInactive', true)
        ->assertSee('New Team Member')
        ->assertSee('Former staff')
        ->assertSee('Reactivate');

    $staff->refresh();
    expect($staff->is_active)->toBeFalse()
        ->and($staff->is_visible)->toBeFalse()
        ->and($staff->deactivated_at)->not->toBeNull()
        ->and($staff->deactivated_by)->toBe($admin->id)
        ->and(User::query()->whereKey($staff->id)->exists())->toBeTrue()
        ->and(Meeting::query()->whereKey($meeting->id)->where('user_id', $staff->id)->exists())->toBeTrue();

    $component->call('reactivateStaff', $staff->id)->assertDispatched('notify');

    $staff->refresh();
    expect($staff->is_active)->toBeTrue()
        ->and($staff->is_visible)->toBeTrue()
        ->and($staff->deactivated_at)->toBeNull()
        ->and($staff->deactivated_by)->toBeNull();
});

test('admin cannot deactivate their own account', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(StaffManagement::class)
        ->call('deactivateStaff', $admin->id)
        ->assertDispatched('notify', fn (string $name, array $params): bool => $params['type'] === 'error');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('deactivated staff are signed out and blocked from authenticated pages', function () {
    $staff = User::factory()->profileCompleted()->create(['is_active' => false]);

    $this->actingAs($staff)
        ->get('/dashboard')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('inactive staff are omitted from new meeting team member choices', function () {
    $admin = User::factory()->admin()->create();
    $inactive = User::factory()->create([
        'name' => 'Former Team Member',
        'is_active' => false,
        'is_visible' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(\App\Livewire\Meetings\MeetingCapture::class)
        ->assertDontSee($inactive->name);
});

test('inactive staff cannot receive activation links or invitations', function () {
    $admin = User::factory()->admin()->create();
    $inactive = User::factory()->create([
        'is_active' => false,
        'activation_token' => null,
    ]);

    Livewire::actingAs($admin)
        ->test(StaffManagement::class)
        ->call('generateActivationLink', $inactive->id)
        ->assertSet('showActivationModal', false)
        ->assertDispatched('notify', fn (string $name, array $params): bool => $params['type'] === 'error')
        ->call('sendInviteEmail', $inactive->id)
        ->assertDispatched('notify', fn (string $name, array $params): bool => $params['type'] === 'error');

    expect($inactive->fresh()->activation_token)->toBeNull();
});

test('admin can edit staff platform access while preserving their own admin access', function () {
    $admin = User::factory()->admin()->create();
    $staff = User::factory()->create([
        'access_level' => 'staff',
        'is_admin' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(StaffManagement::class)
        ->call('openEditModal', $staff->id)
        ->assertSet('editAccessLevel', 'staff')
        ->set('editAccessLevel', 'management')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    expect($staff->fresh()->access_level)->toBe('management')
        ->and($staff->fresh()->is_admin)->toBeFalse();

    Livewire::actingAs($admin)
        ->test(StaffManagement::class)
        ->call('openEditModal', $staff->id)
        ->set('editAccessLevel', 'admin')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($staff->fresh()->access_level)->toBe('admin')
        ->and($staff->fresh()->is_admin)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(StaffManagement::class)
        ->call('openEditModal', $admin->id)
        ->set('editAccessLevel', 'staff')
        ->call('saveEdit')
        ->assertHasErrors('editAccessLevel');

    expect($admin->fresh()->isAdmin())->toBeTrue();
});

test('advanced permissions accepts the canonical staff access level', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin User']);
    $staff = User::factory()->create([
        'name' => 'Staff User',
        'access_level' => 'staff',
        'is_admin' => false,
    ]);

    $component = Livewire::actingAs($admin)->test(Permissions::class);
    $rows = $component->get('rows');
    $staffIndex = collect($rows)->search(fn (array $row): bool => $row['id'] === $staff->id);

    expect($staffIndex)->not->toBeFalse();

    $component
        ->set("rows.{$staffIndex}.access_level", 'staff')
        ->call('save')
        ->assertHasNoErrors();

    expect($staff->fresh()->access_level)->toBe('staff');
});

test('advanced permissions cannot remove the current administrators own access', function () {
    $admin = User::factory()->admin()->create();
    $component = Livewire::actingAs($admin)->test(Permissions::class);
    $rows = $component->get('rows');
    $adminIndex = collect($rows)->search(fn (array $row): bool => $row['id'] === $admin->id);

    $component
        ->set("rows.{$adminIndex}.access_level", 'staff')
        ->call('save')
        ->assertHasErrors("rows.{$adminIndex}.access_level");

    expect($admin->fresh()->isAdmin())->toBeTrue();
});
