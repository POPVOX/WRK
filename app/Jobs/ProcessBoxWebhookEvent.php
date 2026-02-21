<?php

namespace App\Jobs;

use App\Models\BoxWebhookEvent;
use App\Services\Box\BoxClient;
use App\Services\Box\BoxMetadataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBoxWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $boxWebhookEventId;

    public $timeout = 60;

    public function __construct(int $boxWebhookEventId)
    {
        $this->boxWebhookEventId = $boxWebhookEventId;
    }

    public function handle(BoxMetadataService $metadataService, BoxClient $client): void
    {
        $event = BoxWebhookEvent::find($this->boxWebhookEventId);
        if (! $event) {
            return;
        }

        $trigger = strtoupper((string) ($event->trigger ?? ''));
        $sourceType = strtolower((string) ($event->source_type ?? ''));
        $sourceId = trim((string) ($event->source_id ?? ''));

        try {
            if ($this->isDeleteLikeTrigger($trigger) && $sourceId !== '') {
                $metadataService->markItemTrashed($sourceId);
                $this->dispatchLinkedDocumentSync($sourceType, $sourceId, $trigger);
                $this->markProcessed($event);

                return;
            }

            if ($sourceId !== '' && in_array($sourceType, ['file', 'folder'], true)) {
                $metadataService->refreshItem($sourceType, $sourceId);
                $this->dispatchLinkedDocumentSync($sourceType, $sourceId, $trigger);
            }

            $this->markProcessed($event);
        } catch (RequestException $exception) {
            if ($sourceId !== '' && $client->itemNotFound($exception)) {
                $metadataService->markItemTrashed($sourceId);
                $this->dispatchLinkedDocumentSync($sourceType, $sourceId, $trigger);
                $this->markProcessed($event);

                return;
            }

            $this->markFailed($event, $exception->getMessage());
            throw $exception;
        } catch (\Throwable $exception) {
            $this->markFailed($event, $exception->getMessage());
            throw $exception;
        }
    }

    private function isDeleteLikeTrigger(string $trigger): bool
    {
        return str_contains($trigger, 'TRASHED')
            || str_contains($trigger, 'DELETED')
            || str_contains($trigger, 'MOVED_TO_TRASH');
    }

    private function dispatchLinkedDocumentSync(string $sourceType, string $sourceId, string $trigger): void
    {
        if ($sourceId === '') {
            return;
        }

        $isFileSource = $sourceType === 'file' || str_starts_with($trigger, 'FILE.');
        if (! $isFileSource) {
            return;
        }

        SyncLinkedBoxDocumentsForItem::dispatch($sourceId);
    }

    private function markProcessed(BoxWebhookEvent $event): void
    {
        $event->update([
            'status' => 'processed',
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    private function markFailed(BoxWebhookEvent $event, string $errorMessage): void
    {
        $event->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        Log::warning('Box webhook processing failed', [
            'box_webhook_event_id' => $event->id,
            'delivery_id' => $event->delivery_id,
            'error' => $errorMessage,
        ]);
    }
}
