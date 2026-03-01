<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSlackWebhookEvent;
use App\Models\SlackWebhookEvent;
use App\Services\Slack\SlackWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SlackWebhookController extends Controller
{
    public function __construct(
        protected SlackWebhookService $slackWebhookService
    ) {}

    public function handle(Request $request): JsonResponse|Response
    {
        $rawBody = $request->getContent();
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        $signatureValid = $this->slackWebhookService->verifySignature(
            $rawBody,
            is_string($timestamp) ? $timestamp : null,
            is_string($signature) ? $signature : null
        );

        if (! $signatureValid) {
            Log::warning('Rejected Slack webhook with invalid signature', [
                'request_id' => $request->header('X-Slack-Request-Id'),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Invalid Slack webhook signature.',
            ], 401);
        }

        $payload = $this->slackWebhookService->extractPayload($request);
        if (($payload['type'] ?? null) === 'url_verification') {
            return response((string) ($payload['challenge'] ?? ''), 200)
                ->header('Content-Type', 'text/plain');
        }

        $deliveryId = $this->slackWebhookService->resolveDeliveryId($payload, $request, $rawBody);
        $event = SlackWebhookEvent::firstOrCreate(
            ['delivery_id' => $deliveryId],
            [
                'event_type' => $this->slackWebhookService->eventType($payload),
                'slack_user_id' => $this->slackWebhookService->slackUserId($payload),
                'slack_channel_id' => $this->slackWebhookService->slackChannelId($payload),
                'headers' => [
                    'request_id' => $request->header('X-Slack-Request-Id'),
                    'request_timestamp' => $request->header('X-Slack-Request-Timestamp'),
                    'signature' => $request->header('X-Slack-Signature'),
                    'user_agent' => $request->userAgent(),
                ],
                'payload' => $payload,
                'status' => 'received',
            ]
        );

        if ($event->wasRecentlyCreated) {
            ProcessSlackWebhookEvent::dispatch($event->id);
        }

        if ($this->slackWebhookService->isSlashCommandPayload($payload)) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => $event->wasRecentlyCreated
                    ? 'Working on it... I will post your response shortly.'
                    : 'This request is already being processed.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => $event->wasRecentlyCreated ? 'Webhook accepted.' : 'Duplicate delivery ignored.',
        ]);
    }
}
