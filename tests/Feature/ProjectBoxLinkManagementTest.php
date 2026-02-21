<?php

use App\Livewire\Projects\ProjectShow;
use App\Models\BoxItem;
use App\Models\BoxProjectDocumentLink;
use App\Models\Project;
use App\Models\User;
use App\Services\Box\BoxProjectDocumentService;
use Livewire\Livewire;

test('project show can link an existing box file to the project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $boxItem = BoxItem::create([
        'box_item_id' => 'box-ui-1001',
        'box_item_type' => 'file',
        'name' => 'Project Plan.pdf',
        'path_display' => '/WRK/Projects/Project Plan.pdf',
        'size' => 1024,
    ]);

    Livewire::actingAs($user)
        ->test(ProjectShow::class, ['project' => $project])
        ->set('boxLinkVisibility', 'management')
        ->call('linkExistingBoxItem', $boxItem->id);

    $link = BoxProjectDocumentLink::where('box_item_id', $boxItem->id)
        ->where('project_id', $project->id)
        ->first();

    expect($link)->not()->toBeNull();
    expect($link->sync_status)->toBe('synced');
    expect($link->project_document_id)->not()->toBeNull();

    $this->assertDatabaseHas('project_documents', [
        'id' => $link->project_document_id,
        'project_id' => $project->id,
        'type' => 'link',
        'url' => 'https://app.box.com/file/box-ui-1001',
        'visibility' => 'management',
    ]);
});

test('project show can sync an existing box link', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $boxItem = BoxItem::create([
        'box_item_id' => 'box-ui-1002',
        'box_item_type' => 'file',
        'name' => 'Contract Addendum.docx',
        'path_display' => '/WRK/Contracts/Contract Addendum.docx',
    ]);

    $link = BoxProjectDocumentLink::create([
        'box_item_id' => $boxItem->id,
        'project_id' => $project->id,
        'visibility' => 'admin',
        'sync_status' => 'pending',
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('syncBoxLink', $link->id);

    $link->refresh();
    expect($link->sync_status)->toBe('synced');
    expect($link->project_document_id)->not()->toBeNull();

    $this->assertDatabaseHas('project_documents', [
        'id' => $link->project_document_id,
        'project_id' => $project->id,
        'visibility' => 'admin',
    ]);
});

test('project show can unlink a box link and remove its linked project document', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $boxItem = BoxItem::create([
        'box_item_id' => 'box-ui-1003',
        'box_item_type' => 'file',
        'name' => 'Weekly Report.md',
        'path_display' => '/WRK/Projects/Weekly Report.md',
    ]);

    $service = app(BoxProjectDocumentService::class);
    $link = $service->linkItemToProject($boxItem, $project, $user->id, 'all');
    $doc = $service->syncLink($link);

    Livewire::actingAs($user)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('unlinkBoxLink', $link->id);

    $this->assertDatabaseMissing('box_project_document_links', [
        'id' => $link->id,
    ]);
    $this->assertDatabaseMissing('project_documents', [
        'id' => $doc->id,
    ]);
});
