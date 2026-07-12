<?php

use App\Livewire\Projects\ProjectCreate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('project creation repairs a postgres project id sequence collision and retries', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('PostgreSQL sequence behavior only.');
    }

    Queue::fake();
    $user = User::factory()->profileCompleted()->create();
    $collidingId = ((int) Project::max('id')) + 100;

    DB::table('projects')->insert([
        'id' => $collidingId,
        'name' => 'Existing imported project',
        'status' => 'active',
        'project_type' => 'initiative',
        'created_by' => $user->id,
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    DB::statement(
        "SELECT setval(pg_get_serial_sequence('projects', 'id'), ?, false)",
        [$collidingId]
    );

    Livewire::actingAs($user)
        ->test(ProjectCreate::class)
        ->set('name', 'Sequence-resilient project')
        ->set('description', 'Created after an imported ID left the sequence behind.')
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::where('name', 'Sequence-resilient project')->firstOrFail();

    expect($project->id)->toBeGreaterThan($collidingId);
});
