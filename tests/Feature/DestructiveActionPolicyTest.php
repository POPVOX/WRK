<?php

use App\Livewire\Meetings\MeetingDetail;
use App\Livewire\Organizations\OrganizationShow;
use App\Livewire\People\PersonShow;
use App\Livewire\Projects\ProjectShow;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('staff cannot delete a project they did not create', function () {
    $staff = User::factory()->create();
    $project = Project::factory()->create(['created_by' => User::factory()->create()->id]);

    Livewire::actingAs($staff)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('deleteProject');

    expect(Project::find($project->id))->not->toBeNull();
});

test('creator can delete their own project', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->create(['created_by' => $creator->id]);

    Livewire::actingAs($creator)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('deleteProject')
        ->assertRedirect(route('projects.index'));

    expect(Project::find($project->id))->toBeNull();
});

test('management can delete any project', function () {
    $management = User::factory()->create(['access_level' => 'management']);
    $project = Project::factory()->create(['created_by' => User::factory()->create()->id]);

    Livewire::actingAs($management)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('deleteProject')
        ->assertRedirect(route('projects.index'));

    expect(Project::find($project->id))->toBeNull();
});

test('staff cannot delete an organization but management can', function () {
    $staff = User::factory()->create();
    $management = User::factory()->create(['access_level' => 'management']);
    $organization = Organization::create(['name' => 'Test Org', 'type' => 'other', 'status' => 'active']);

    Livewire::actingAs($staff)
        ->test(OrganizationShow::class, ['organization' => $organization])
        ->call('delete');

    expect(Organization::find($organization->id))->not->toBeNull();

    Livewire::actingAs($management)
        ->test(OrganizationShow::class, ['organization' => $organization])
        ->call('delete');

    expect(Organization::find($organization->id))->toBeNull();
});

test('staff cannot delete a contact but management can', function () {
    $staff = User::factory()->create();
    $management = User::factory()->create(['access_level' => 'management']);
    $person = Person::create(['name' => 'Test Person']);

    Livewire::actingAs($staff)
        ->test(PersonShow::class, ['person' => $person])
        ->call('delete');

    expect(Person::find($person->id))->not->toBeNull();

    Livewire::actingAs($management)
        ->test(PersonShow::class, ['person' => $person])
        ->call('delete');

    expect(Person::find($person->id))->toBeNull();
});

test('staff cannot delete a meeting they do not own', function () {
    $staff = User::factory()->create();
    $owner = User::factory()->create();
    $meeting = Meeting::create([
        'user_id' => $owner->id,
        'title' => 'Test Meeting',
        'meeting_date' => now()->toDateString(),
    ]);

    Livewire::actingAs($staff)
        ->test(MeetingDetail::class, ['meeting' => $meeting])
        ->call('deleteMeeting');

    expect(Meeting::find($meeting->id))->not->toBeNull();

    $component = Livewire::actingAs($owner)
        ->test(MeetingDetail::class, ['meeting' => $meeting])
        ->call('deleteMeeting')
        ->assertRedirect(route('meetings.index'));

    expect($component->effects['redirectUsingNavigate'] ?? false)->toBeTrue();

    expect(Meeting::find($meeting->id))->toBeNull();
});

test('meeting action deletion is scoped to the open meeting', function () {
    $user = User::factory()->create();
    $openMeeting = Meeting::create([
        'user_id' => $user->id,
        'title' => 'Open Meeting',
        'meeting_date' => now()->toDateString(),
    ]);
    $otherMeeting = Meeting::create([
        'user_id' => $user->id,
        'title' => 'Other Meeting',
        'meeting_date' => now()->toDateString(),
    ]);
    $otherAction = $otherMeeting->actions()->create([
        'description' => 'Must remain attached to the other meeting',
        'assigned_to' => $user->id,
    ]);

    expect(fn () => Livewire::actingAs($user)
        ->test(MeetingDetail::class, ['meeting' => $openMeeting])
        ->call('deleteAction', $otherAction->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('actions', ['id' => $otherAction->id, 'deleted_at' => null]);
});
