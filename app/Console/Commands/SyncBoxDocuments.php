<?php

namespace App\Console\Commands;

use App\Jobs\SyncBoxProjectDocumentLink;
use App\Models\BoxItem;
use App\Models\BoxProjectDocumentLink;
use App\Services\Box\BoxProjectDocumentService;
use Illuminate\Console\Command;

class SyncBoxDocuments extends Command
{
    protected $signature = 'box:sync-documents
        {--link-id= : Sync a single link ID}
        {--box-item-id= : Sync links for this external Box item ID}
        {--project-id= : Sync links for this project ID}
        {--queue : Queue sync jobs instead of running now}';

    protected $description = 'Sync linked Box file metadata into WRK project_documents records.';

    public function handle(BoxProjectDocumentService $service): int
    {
        $query = BoxProjectDocumentLink::query();

        if ($linkId = $this->option('link-id')) {
            $query->whereKey((int) $linkId);
        }

        if ($projectId = $this->option('project-id')) {
            $query->where('project_id', (int) $projectId);
        }

        if ($boxExternalItemId = $this->option('box-item-id')) {
            $boxItem = BoxItem::where('box_item_id', (string) $boxExternalItemId)->first();
            if (! $boxItem) {
                $this->warn("No Box item found for external id {$boxExternalItemId}.");

                return self::SUCCESS;
            }

            $query->where('box_item_id', $boxItem->id);
        }

        $links = $query->pluck('id');
        if ($links->isEmpty()) {
            $this->info('No links matched filters.');

            return self::SUCCESS;
        }

        if ((bool) $this->option('queue')) {
            foreach ($links as $id) {
                SyncBoxProjectDocumentLink::dispatch((int) $id);
            }

            $this->info("Queued {$links->count()} Box document sync job(s).");

            return self::SUCCESS;
        }

        $result = $service->syncLinks(
            BoxProjectDocumentLink::whereIn('id', $links->all())
        );

        $this->info("Synced: {$result['synced']} | Failed: {$result['failed']}");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
