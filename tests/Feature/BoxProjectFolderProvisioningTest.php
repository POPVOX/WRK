<?php

use App\Jobs\EnsureBoxProjectFolder;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('creating a project queues box folder provisioning when box is configured', function () {
    Queue::fake();

    config()->set('services.box.access_token', 'test-box-token');
    config()->set('services.box.projects_folder_id', 'projects-root-1');
    config()->set('services.box.auto_provision_project_folders', true);

    $project = Project::factory()->create([
        'name' => 'Digital Parliaments Project',
    ]);

    Queue::assertPushed(EnsureBoxProjectFolder::class, function (EnsureBoxProjectFolder $job) use ($project) {
        return $job->projectId === $project->id;
    });
});

test('ensure box project folder creates parent folder before child folder', function () {
    config()->set('services.box.access_token', 'test-box-token');
    config()->set('services.box.base_uri', 'https://api.box.com/2.0');
    config()->set('services.box.projects_folder_id', 'projects-root-2');
    config()->set('services.box.auto_provision_project_folders', true);

    $rootProject = Project::withoutEvents(fn () => Project::factory()->create([
        'name' => 'Contract Work',
    ]));
    $childProject = Project::withoutEvents(fn () => Project::factory()->create([
        'name' => 'Contract Work - Q1 Reporting',
        'parent_project_id' => $rootProject->id,
    ]));

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'https://api.box.com/2.0/folders') {
            $payload = $request->data();
            $name = (string) data_get($payload, 'name');
            $parentId = (string) data_get($payload, 'parent.id');

            if ($name === 'Contract Work' && $parentId === 'projects-root-2') {
                return Http::response([
                    'id' => 'box-parent-1001',
                    'type' => 'folder',
                    'name' => 'Contract Work',
                    'parent' => ['id' => 'projects-root-2'],
                    'path_collection' => [
                        'entries' => [
                            ['name' => 'All Files'],
                            ['name' => 'WRK'],
                            ['name' => 'Projects'],
                        ],
                    ],
                ], 201);
            }

            if ($name === 'Contract Work - Q1 Reporting' && $parentId === 'box-parent-1001') {
                return Http::response([
                    'id' => 'box-child-1001',
                    'type' => 'folder',
                    'name' => 'Contract Work - Q1 Reporting',
                    'parent' => ['id' => 'box-parent-1001'],
                    'path_collection' => [
                        'entries' => [
                            ['name' => 'All Files'],
                            ['name' => 'WRK'],
                            ['name' => 'Projects'],
                            ['name' => 'Contract Work'],
                        ],
                    ],
                ], 201);
            }
        }

        return Http::response([], 404);
    });

    EnsureBoxProjectFolder::dispatchSync($childProject->id);

    $rootProject->refresh();
    $childProject->refresh();

    expect($rootProject->box_folder_id)->toBe('box-parent-1001');
    expect($rootProject->box_folder_status)->toBe('ready');
    expect($childProject->box_folder_id)->toBe('box-child-1001');
    expect($childProject->box_folder_status)->toBe('ready');
});

test('ensure box project folder reuses an existing folder when box returns name conflict', function () {
    config()->set('services.box.access_token', 'test-box-token');
    config()->set('services.box.base_uri', 'https://api.box.com/2.0');
    config()->set('services.box.projects_folder_id', 'projects-root-3');
    config()->set('services.box.auto_provision_project_folders', true);

    $project = Project::withoutEvents(fn () => Project::factory()->create([
        'name' => 'Digital Parliaments Project',
    ]));

    Http::fake(function ($request) {
        if ($request->method() === 'POST' && $request->url() === 'https://api.box.com/2.0/folders') {
            return Http::response([
                'type' => 'error',
                'status' => 409,
                'code' => 'item_name_in_use',
                'context_info' => [
                    'conflicts' => [
                        'type' => 'folder',
                        'id' => 'box-existing-777',
                        'name' => 'Digital Parliaments Project',
                    ],
                ],
            ], 409);
        }

        if ($request->method() === 'GET' && str_starts_with($request->url(), 'https://api.box.com/2.0/folders/box-existing-777')) {
            return Http::response([
                'id' => 'box-existing-777',
                'type' => 'folder',
                'name' => 'Digital Parliaments Project',
                'parent' => ['id' => 'projects-root-3'],
            ], 200);
        }

        return Http::response([], 404);
    });

    EnsureBoxProjectFolder::dispatchSync($project->id);

    $project->refresh();

    expect($project->box_folder_id)->toBe('box-existing-777');
    expect($project->box_folder_status)->toBe('ready');
    expect($project->box_folder_error)->toBeNull();
});

test('box provision project folders command queues missing projects by default', function () {
    Queue::fake();

    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();
    $projectWithFolder = Project::factory()->create([
        'box_folder_id' => 'box-existing-888',
        'box_folder_status' => 'ready',
    ]);

    config()->set('services.box.access_token', 'test-box-token');
    config()->set('services.box.projects_folder_id', 'projects-root-4');
    config()->set('services.box.auto_provision_project_folders', true);

    $this->artisan('box:provision-project-folders')
        ->assertSuccessful();

    Queue::assertPushed(EnsureBoxProjectFolder::class, function (EnsureBoxProjectFolder $job) use ($projectA, $projectB) {
        return in_array($job->projectId, [$projectA->id, $projectB->id], true);
    });
    Queue::assertPushed(EnsureBoxProjectFolder::class, 2);

    expect($projectWithFolder->box_folder_id)->toBe('box-existing-888');
});
