<?php

namespace App\Console\Commands;

use App\Jobs\SyncBoxProjectDocumentLink;
use App\Models\BoxItem;
use App\Models\Project;
use App\Models\User;
use App\Services\Box\BoxProjectDocumentService;
use Illuminate\Console\Command;

class LinkBoxItemToProject extends Command
{
    protected $signature = 'box:link-to-project
        {boxItemId : External Box item ID (example: 1234567890)}
        {projectId : WRK project ID}
        {--visibility=all : all|management|admin}
        {--created-by= : User ID to attribute the synced document}
        {--queue : Queue document sync instead of running immediately}';

    protected $description = 'Link a synced Box file to a WRK project and create/update a project document.';

    public function handle(BoxProjectDocumentService $service): int
    {
        $boxExternalItemId = trim((string) $this->argument('boxItemId'));
        $projectId = (int) $this->argument('projectId');
        $visibility = (string) $this->option('visibility');
        $createdBy = $this->option('created-by') !== null ? (int) $this->option('created-by') : null;

        $boxItem = BoxItem::where('box_item_id', $boxExternalItemId)->first();
        if (! $boxItem) {
            $this->error("Box item {$boxExternalItemId} was not found. Run box:sync-metadata first.");

            return self::FAILURE;
        }

        if ($boxItem->box_item_type !== 'file') {
            $this->error('Only Box file items can be linked to project documents.');

            return self::FAILURE;
        }

        $project = Project::find($projectId);
        if (! $project) {
            $this->error("Project {$projectId} not found.");

            return self::FAILURE;
        }

        if ($createdBy !== null && ! User::whereKey($createdBy)->exists()) {
            $this->error("User {$createdBy} not found.");

            return self::FAILURE;
        }

        $link = $service->linkItemToProject($boxItem, $project, $createdBy, $visibility);

        if ((bool) $this->option('queue')) {
            SyncBoxProjectDocumentLink::dispatch($link->id);
            $this->info("Linked Box item {$boxExternalItemId} to project {$project->id} and queued sync.");

            return self::SUCCESS;
        }

        try {
            $doc = $service->syncLink($link);
            $this->info("Linked and synced to project_document #{$doc->id}.");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Link created, but sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
