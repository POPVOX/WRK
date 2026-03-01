<?php

use App\Livewire\Projects\ProjectShow;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('project show delete action removes project, unparents children, and redirects', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $child = Project::factory()->create(['parent_project_id' => $project->id]);

    Storage::disk('public')->put('project-documents/delete-me.txt', 'delete me');

    ProjectDocument::factory()->create([
        'project_id' => $project->id,
        'title' => 'Delete Me',
        'type' => 'file',
        'file_path' => 'project-documents/delete-me.txt',
    ]);

    Livewire::actingAs($user)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('deleteProject')
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseHas('projects', [
        'id' => $child->id,
        'parent_project_id' => null,
    ]);

    Storage::disk('public')->assertMissing('project-documents/delete-me.txt');
});

test('project show header delete button dispatches a global event instead of wire click', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('request-project-delete', false)
        ->assertDontSee('wire:click="deleteProject"', false);
});
