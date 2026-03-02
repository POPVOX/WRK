<?php

use App\Models\User;
use App\Notifications\WorkspaceAlert;
use App\Services\Notifications\WorkspaceNotificationService;

test('workspace notification service sends normalized payload to unique recipients', function () {
    $actor = User::factory()->create([
        'name' => 'Marci Harris',
        'is_visible' => true,
        'access_level' => 'management',
    ]);

    $recipientA = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'staff',
    ]);
    $recipientB = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'staff',
    ]);

    $sent = app(WorkspaceNotificationService::class)->sendToUsers(
        [$recipientA, $recipientB, $recipientA],
        'project_added',
        'Marci Harris added you to a new project',
        'Legislative Tech Initiative',
        [
            'category' => 'project',
            'level' => 'not-a-real-level',
            'action_url' => '/projects/42',
            'action_label' => 'Open Project',
            'actor' => $actor,
            'manual' => true,
            'meta' => [
                'project_id' => 42,
            ],
        ],
    );

    expect($sent)->toBe(2);

    $this->assertDatabaseCount('notifications', 2);

    $notification = $recipientA->notifications()->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe(WorkspaceAlert::class);
    expect((string) ($notification->data['kind'] ?? ''))->toBe('project_added');
    expect((string) ($notification->data['category'] ?? ''))->toBe('project');
    expect((string) ($notification->data['level'] ?? ''))->toBe('info');
    expect((string) ($notification->data['title'] ?? ''))->toContain('added you to a new project');
    expect((string) ($notification->data['body'] ?? ''))->toBe('Legislative Tech Initiative');
    expect((string) ($notification->data['action_label'] ?? ''))->toBe('Open Project');
    expect((string) ($notification->data['action_url'] ?? ''))->toBe('/projects/42');
    expect((int) ($notification->data['actor_id'] ?? 0))->toBe($actor->id);
    expect((string) ($notification->data['actor_name'] ?? ''))->toBe('Marci Harris');
    expect((bool) ($notification->data['manual'] ?? false))->toBeTrue();
    expect((int) ($notification->data['meta']['project_id'] ?? 0))->toBe(42);
});

test('workspace notification service resolves audiences with visibility boundaries', function () {
    $staff = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'staff',
        'is_admin' => false,
    ]);
    $manager = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'management',
        'is_admin' => false,
    ]);
    $admin = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'admin',
        'is_admin' => false,
    ]);
    $legacyAdminFlag = User::factory()->create([
        'is_visible' => true,
        'access_level' => 'staff',
        'is_admin' => true,
    ]);
    $hiddenManager = User::factory()->create([
        'is_visible' => false,
        'access_level' => 'management',
        'is_admin' => false,
    ]);

    $service = app(WorkspaceNotificationService::class);

    $allStaffIds = $service->resolveAudience('all_staff')->pluck('id')->sort()->values()->all();
    expect($allStaffIds)->toContain($staff->id, $manager->id, $admin->id, $legacyAdminFlag->id);
    expect($allStaffIds)->not->toContain($hiddenManager->id);

    $managementIds = $service->resolveAudience('management')->pluck('id')->sort()->values()->all();
    expect($managementIds)->toContain($manager->id, $admin->id, $legacyAdminFlag->id);
    expect($managementIds)->not->toContain($staff->id, $hiddenManager->id);

    $adminIds = $service->resolveAudience('admins')->pluck('id')->sort()->values()->all();
    expect($adminIds)->toContain($admin->id, $legacyAdminFlag->id);
    expect($adminIds)->not->toContain($manager->id, $staff->id, $hiddenManager->id);

    $specificIds = $service->resolveAudience('specific_users', [$staff->id, $hiddenManager->id])->pluck('id')->sort()->values()->all();
    expect($specificIds)->toBe([$staff->id]);
});
