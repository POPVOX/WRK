<?php

use App\Models\Grant;
use App\Models\GrantDocument;
use App\Models\Organization;
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

test('project document visibility is enforced by the download route', function () {
    Storage::fake(PrivateFiles::DISK);
    Storage::fake('public');

    Storage::disk(PrivateFiles::DISK)->put('project-documents/management.txt', 'restricted content');

    $document = ProjectDocument::factory()->create([
        'project_id' => Project::factory()->create()->id,
        'title' => 'Management',
        'type' => 'file',
        'visibility' => 'management',
        'file_path' => 'project-documents/management.txt',
    ]);

    $url = route('files.download', ['type' => 'project-document', 'id' => $document->id]);

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['access_level' => 'management']))
        ->get($url)
        ->assertOk()
        ->assertDownload('Management.txt');
});

test('grant visibility is enforced by the download route', function () {
    Storage::fake(PrivateFiles::DISK);
    Storage::fake('public');

    Storage::disk(PrivateFiles::DISK)->put('grant_documents/restricted.pdf', 'restricted grant content');

    $organization = Organization::create([
        'name' => 'Restricted Funder',
        'type' => 'funder',
        'status' => 'active',
    ]);
    $grant = Grant::create([
        'organization_id' => $organization->id,
        'name' => 'Restricted Grant',
        'status' => 'active',
        'visibility' => 'management',
    ]);
    $document = GrantDocument::create([
        'grant_id' => $grant->id,
        'title' => 'Agreement',
        'type' => 'agreement',
        'file_path' => 'grant_documents/restricted.pdf',
        'file_type' => 'pdf',
    ]);

    $url = route('files.download', ['type' => 'grant-document', 'id' => $document->id]);

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['access_level' => 'management']))
        ->get($url)
        ->assertOk()
        ->assertDownload('Agreement.pdf');
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
