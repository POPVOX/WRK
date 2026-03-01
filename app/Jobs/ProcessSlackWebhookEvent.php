<?php

namespace App\Jobs;

use App\Models\SlackWebhookEvent;
use App\Services\Slack\SlackCommandRouterService;
use App\Services\Slack\SlackWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSlackWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $slackWebhookEventId;

    public $timeout = 60;

    public function __construct(int $slackWebhookEventId)
    {
        $this->slackWebhookEventId = $slackWebhookEventId;
    }

    public function handle(SlackCommandRouterService $commandRouter, SlackWebhookService $webhookService): void
    {
        $event = SlackWebhookEvent::find($this->slackWebhookEventId);
        if (! $event) {
            return;
        }

        try {
            $payload = is_array($event->payload) ? $event->payload : [];
            $route = $commandRouter->route($payload);

            if (is_array($route)) {
                $transport = trim((string) ($route['transport'] ?? ''));

                if ($transport === 'response_url') {
                    $webhookService->postResponse(
                        (string) ($route['response_url'] ?? ''),
                        (string) ($route['text'] ?? '')
                    );
                } elseif ($transport === 'channel') {
                    $webhookService->postChannelMessage(
                        (string) ($route['channel'] ?? ''),
                        (string) ($route['text'] ?? ''),
                        isset($route['thread_ts']) ? (string) $route['thread_ts'] : null
                    );
                }
            }

            $this->markProcessed($event);
        } catch (\Throwable $exception) {
            $this->markFailed($event, $exception->getMessage());
            throw $exception;
        }
    }

    protected function markProcessed(SlackWebhookEvent $event): void
    {
        $event->update([
            'status' => 'processed',
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    protected function markFailed(SlackWebhookEvent $event, string $errorMessage): void
    {
        $event->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        Log::warning('Slack webhook processing failed', [
            'slack_webhook_event_id' => $event->id,
            'delivery_id' => $event->delivery_id,
            'event_type' => $event->event_type,
            'error' => $errorMessage,
        ]);
    }
}
