<?php

namespace App\Jobs;

use App\Services\Box\BoxProjectDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncLinkedBoxDocumentsForItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $boxExternalItemId;

    public $timeout = 60;

    public function __construct(string $boxExternalItemId)
    {
        $this->boxExternalItemId = $boxExternalItemId;
    }

    public function handle(BoxProjectDocumentService $service): void
    {
        $service->queueSyncForBoxExternalItemId($this->boxExternalItemId);
    }
}
