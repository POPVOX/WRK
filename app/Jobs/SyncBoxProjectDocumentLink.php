<?php

namespace App\Jobs;

use App\Models\BoxProjectDocumentLink;
use App\Services\Box\BoxProjectDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBoxProjectDocumentLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $linkId;

    public $timeout = 60;

    public function __construct(int $linkId)
    {
        $this->linkId = $linkId;
    }

    public function handle(BoxProjectDocumentService $service): void
    {
        $link = BoxProjectDocumentLink::find($this->linkId);
        if (! $link) {
            return;
        }

        try {
            $service->syncLink($link);
        } catch (\Throwable $exception) {
            Log::warning('Box document link sync failed', [
                'link_id' => $this->linkId,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }
}
