<?php

use App\Jobs\ProcessBoxWebhookEvent;
use App\Jobs\SyncBoxProjectDocumentLink;
use App\Jobs\SyncLinkedBoxDocumentsForItem;
use App\Models\BoxItem;
use App\Models\BoxProjectDocumentLink;
use App\Models\BoxWebhookEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\Box\BoxClient;
use App\Services\Box\BoxMetadataService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('box link command creates a synced project document', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();
    $boxItem = BoxItem::create([
        'box_item_id' => 'box-file-1001',
        'box_item_type' => 'file',
        'name' => 'WRK Brief.pdf',
        'path_display' => '/WRK/Projects/WRK Brief.pdf',
        'sha1' => 'abc123',
        'size' => 1200,
    ]);

    $this->artisan('box:link-to-project', [
        'boxItemId' => $boxItem->box_item_id,
        'projectId' => $project->id,
        '--visibility' => 'management',
        '--created-by' => $user->id,
    ])->assertSuccessful();

    $link = BoxProjectDocumentLink::where('box_item_id', $boxItem->id)
        ->where('project_id', $project->id)
        ->first();

    expect($link)->not()->toBeNull();
    expect($link->sync_status)->toBe('synced');
    expect($link->visibility)->toBe('management');
    expect($link->project_document_id)->not()->toBeNull();

    $document = $link->projectDocument()->first();
    expect($document)->not()->toBeNull();
    expect($document->project_id)->toBe($project->id);
    expect($document->type)->toBe('link');
    expect($document->url)->toBe('https://app.box.com/file/box-file-1001');
    expect($document->visibility)->toBe('management');
    expect($document->is_archived)->toBeFalse();
});

test('box link command queues sync when queue option is used', function () {
    Queue::fake();

    $project = Project::factory()->create();
    $boxItem = BoxItem::create([
        'box_item_id' => 'box-file-1002',
        'box_item_type' => 'file',
        'name' => 'Contract.pdf',
    ]);

    $this->artisan('box:link-to-project', [
        'boxItemId' => $boxItem->box_item_id,
        'projectId' => $project->id,
        '--queue' => true,
    ])->assertSuccessful();

    $link = BoxProjectDocumentLink::where('box_item_id', $boxItem->id)
        ->where('project_id', $project->id)
        ->first();

    expect($link)->not()->toBeNull();
    expect($link->sync_status)->toBe('pending');
    expect($link->project_document_id)->toBeNull();

    Queue::assertPushed(SyncBoxProjectDocumentLink::class, function (SyncBoxProjectDocumentLink $job) use ($link) {
        return $job->linkId === $link->id;
    });
});

test('box sync documents command queues only matching links', function () {
    Queue::fake();

    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $boxItemA = BoxItem::create([
        'box_item_id' => 'box-file-2001',
        'box_item_type' => 'file',
        'name' => 'Project A Plan.docx',
    ]);
    $boxItemB = BoxItem::create([
        'box_item_id' => 'box-file-2002',
        'box_item_type' => 'file',
        'name' => 'Project B Plan.docx',
    ]);

    $linkA = BoxProjectDocumentLink::create([
        'box_item_id' => $boxItemA->id,
        'project_id' => $projectA->id,
        'sync_status' => 'pending',
    ]);
    BoxProjectDocumentLink::create([
        'box_item_id' => $boxItemB->id,
        'project_id' => $projectB->id,
        'sync_status' => 'pending',
    ]);

    $this->artisan('box:sync-documents', [
        '--project-id' => $projectA->id,
        '--queue' => true,
    ])->assertSuccessful();

    Queue::assertPushed(SyncBoxProjectDocumentLink::class, 1);
    Queue::assertPushed(SyncBoxProjectDocumentLink::class, function (SyncBoxProjectDocumentLink $job) use ($linkA) {
        return $job->linkId === $linkA->id;
    });
});

test('box sync documents command syncs a targeted link immediately', function () {
    $project = Project::factory()->create();
    $boxItem = BoxItem::create([
        'box_item_id' => 'box-file-3001',
        'box_item_type' => 'file',
        'name' => 'Meeting Notes.md',
        'path_display' => '/WRK/Team/Meeting Notes.md',
        'size' => 2048,
    ]);

    $link = BoxProjectDocumentLink::create([
        'box_item_id' => $boxItem->id,
        'project_id' => $project->id,
        'visibility' => 'all',
        'sync_status' => 'pending',
    ]);

    $this->artisan('box:sync-documents', [
        '--link-id' => $link->id,
    ])->assertSuccessful();

    $link->refresh();

    expect($link->sync_status)->toBe('synced');
    expect($link->project_document_id)->not()->toBeNull();
    expect($link->last_synced_at)->not()->toBeNull();
});

test('processing file webhook event refreshes metadata and queues linked document sync', function () {
    Queue::fake();

    config()->set('services.box.access_token', 'test-box-token');
    config()->set('services.box.base_uri', 'https://api.box.com/2.0');

    Http::fake([
        'https://api.box.com/2.0/files/box-file-4001*' => Http::response([
            'id' => 'box-file-4001',
            'type' => 'file',
            'name' => 'Updated Plan.pdf',
            'etag' => '1',
            'sha1' => 'sha1hash',
            'size' => 4096,
            'path_collection' => [
                'entries' => [
                    ['name' => 'All Files'],
                    ['name' => 'WRK'],
                ],
            ],
        ], 200),
    ]);

    $event = BoxWebhookEvent::create([
        'delivery_id' => 'delivery-file-update-1',
        'trigger' => 'FILE.UPLOADED',
        'source_type' => 'file',
        'source_id' => 'box-file-4001',
        'payload' => ['id' => 'delivery-file-update-1'],
        'status' => 'received',
    ]);

    $job = new ProcessBoxWebhookEvent($event->id);
    $job->handle(app(BoxMetadataService::class), app(BoxClient::class));

    $event->refresh();
    expect($event->status)->toBe('processed');
    expect($event->processed_at)->not()->toBeNull();

    $boxItem = BoxItem::where('box_item_id', 'box-file-4001')->first();
    expect($boxItem)->not()->toBeNull();
    expect($boxItem->name)->toBe('Updated Plan.pdf');

    Queue::assertPushed(SyncLinkedBoxDocumentsForItem::class, function (SyncLinkedBoxDocumentsForItem $queuedJob) {
        return $queuedJob->boxExternalItemId === 'box-file-4001';
    });
});

test('processing file trashed webhook event marks item trashed and queues linked document sync', function () {
    Queue::fake();

    $boxItem = BoxItem::create([
        'box_item_id' => 'box-file-5001',
        'box_item_type' => 'file',
        'name' => 'Old Report.pdf',
        'trashed_at' => null,
    ]);

    $event = BoxWebhookEvent::create([
        'delivery_id' => 'delivery-file-trash-1',
        'trigger' => 'FILE.TRASHED',
        'source_type' => 'file',
        'source_id' => 'box-file-5001',
        'payload' => ['id' => 'delivery-file-trash-1'],
        'status' => 'received',
    ]);

    $job = new ProcessBoxWebhookEvent($event->id);
    $job->handle(app(BoxMetadataService::class), app(BoxClient::class));

    $event->refresh();
    $boxItem->refresh();

    expect($event->status)->toBe('processed');
    expect($boxItem->trashed_at)->not()->toBeNull();

    Queue::assertPushed(SyncLinkedBoxDocumentsForItem::class, function (SyncLinkedBoxDocumentsForItem $queuedJob) {
        return $queuedJob->boxExternalItemId === 'box-file-5001';
    });
});
