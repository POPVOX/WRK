<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectObserverResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_creation_survives_box_queue_dispatch_failures(): void
    {
        Config::set('services.box.access_token', 'test-box-token');
        Config::set('services.box.projects_folder_id', 'projects-root-1');
        Config::set('services.box.auto_provision_project_folders', true);

        Schema::drop('jobs');

        $project = Project::create([
            'name' => 'Resilient Project',
            'status' => 'planning',
            'project_type' => 'initiative',
        ]);

        $this->assertNotNull($project->id);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Resilient Project',
        ]);
    }
}
