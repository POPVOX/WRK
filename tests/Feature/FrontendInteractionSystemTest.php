<?php

use App\Livewire\Meetings\MeetingCapture;
use App\Livewire\Organizations\OrganizationShow;
use App\Livewire\Projects\ProjectCreate;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('meeting capture renders the document form and guarded save action', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(MeetingCapture::class)
        ->assertSee('meeting-capture-form', false)
        ->assertSee('meeting-capture-actions', false)
        ->assertSee('wire:loading.attr="disabled"', false)
        ->assertSee('Saving…');
});

test('project creation renders a chat and review workspace', function () {
    $user = User::factory()->profileCompleted()->create();

    Livewire::actingAs($user)
        ->test(ProjectCreate::class)
        ->assertSee('project-create-workspace', false)
        ->assertSee('project-create-chat', false)
        ->assertSee('project-create-preview', false)
        ->assertSee('Create With Chat')
        ->assertSee('Project Profile Preview');
});

test('organization project linking uses the shared modal treatment', function () {
    $user = User::factory()->create();
    $organization = Organization::query()->create([
        'name' => 'Open Institutions Lab',
        'status' => 'active',
    ]);

    Livewire::actingAs($user)
        ->test(OrganizationShow::class, ['organization' => $organization])
        ->call('toggleAddProjectModal')
        ->assertSee('organization-project-title', false)
        ->assertSee('desk-modal-backdrop', false)
        ->assertSee('desk-modal-panel', false)
        ->assertSee('Link a project');
});
