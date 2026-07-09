<?php

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Storage;

test('document downloads require authentication', function () {
    Storage::fake(PrivateFiles::DISK);

    $document = ProjectDocument::factory()->create([
        'project_id' => Project::factory()->create()->id,
        'type' => 'file',
        'file_path' => 'project-documents/secret.txt',
    ]);

    $this->get(route('files.download', ['type' => 'project-document', 'id' => $document->id]))
        ->assertRedirect(route('login'));
});

test('authenticated staff can download a project document from the private disk', function () {
    Storage::fake(PrivateFiles::DISK);
    Storage::fake('public');

    Storage::disk(PrivateFiles::DISK)->put('project-documents/secret.txt', 'private content');

    $document = ProjectDocument::factory()->create([
        'project_id' => Project::factory()->create()->id,
        'title' => 'Secret',
        'type' => 'file',
        'file_path' => 'project-documents/secret.txt',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('files.download', ['type' => 'project-document', 'id' => $document->id]))
        ->assertOk()
        ->assertDownload('Secret.txt');
});

test('legacy files still on the public disk are served through the download route', function () {
    Storage::fake(PrivateFiles::DISK);
    Storage::fake('public');

    Storage::disk('public')->put('project-documents/legacy.txt', 'legacy content');

    $document = ProjectDocument::factory()->create([
        'project_id' => Project::factory()->create()->id,
        'title' => 'Legacy',
        'type' => 'file',
        'file_path' => 'project-documents/legacy.txt',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('files.download', ['type' => 'project-document', 'id' => $document->id]))
        ->assertOk()
        ->assertDownload('Legacy.txt');
});

test('unknown download types 404', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('files.download', ['type' => 'nope', 'id' => 1]))
        ->assertNotFound();
});
