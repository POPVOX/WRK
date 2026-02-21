<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBoxWebhookEvent;
use App\Models\BoxWebhookEvent;
use App\Services\Box\BoxClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BoxWebhookController extends Controller
{
    public function __construct(
        protected BoxClient $boxClient
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $timestamp = $request->header('BOX-DELIVERY-TIMESTAMP');
        $signaturePrimary = $request->header('BOX-SIGNATURE-PRIMARY');
        $signatureSecondary = $request->header('BOX-SIGNATURE-SECONDARY');

        $signatureValid = $this->boxClient->verifyWebhookSignature(
            $rawBody,
            is_string($timestamp) ? $timestamp : null,
            is_string($signaturePrimary) ? $signaturePrimary : null,
            is_string($signatureSecondary) ? $signatureSecondary : null
        );

        if (! $signatureValid) {
            Log::warning('Rejected Box webhook with invalid signature', [
                'delivery_id' => $request->header('BOX-DELIVERY-ID'),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Invalid Box webhook signature.',
            ], 401);
        }

        $payload = $request->json()->all();
        $deliveryId = $this->resolveDeliveryId($request, $payload);

        $event = BoxWebhookEvent::firstOrCreate(
            ['delivery_id' => $deliveryId],
            [
                'trigger' => data_get($payload, 'trigger'),
                'source_type' => data_get($payload, 'source.type'),
                'source_id' => data_get($payload, 'source.id'),
                'headers' => [
                    'delivery_timestamp' => $request->header('BOX-DELIVERY-TIMESTAMP'),
                    'signature_version' => $request->header('BOX-SIGNATURE-VERSION'),
                    'signature_primary' => $request->header('BOX-SIGNATURE-PRIMARY'),
                    'signature_secondary' => $request->header('BOX-SIGNATURE-SECONDARY'),
                ],
                'payload' => $payload,
                'status' => 'received',
            ]
        );

        if (! $event->wasRecentlyCreated) {
            return response()->json([
                'ok' => true,
                'message' => 'Duplicate delivery ignored.',
            ]);
        }

        ProcessBoxWebhookEvent::dispatch($event->id);

        return response()->json([
            'ok' => true,
            'message' => 'Webhook accepted.',
            'delivery_id' => $deliveryId,
        ], 202);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function resolveDeliveryId(Request $request, array $payload): string
    {
        $headerDeliveryId = trim((string) $request->header('BOX-DELIVERY-ID', ''));
        if ($headerDeliveryId !== '') {
            return $headerDeliveryId;
        }

        $payloadId = trim((string) data_get($payload, 'id', ''));
        if ($payloadId !== '') {
            return $payloadId;
        }

        return (string) Str::uuid();
    }
}
