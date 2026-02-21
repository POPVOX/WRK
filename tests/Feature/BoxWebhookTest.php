<?php

use App\Jobs\ProcessBoxWebhookEvent;
use Illuminate\Support\Facades\Queue;

test('box webhook stores event and queues processor when signature is valid', function () {
    Queue::fake();

    config()->set('services.box.webhook.enforce_signature', true);
    config()->set('services.box.webhook.primary_signature_key', 'test-primary-key');
    config()->set('services.box.webhook.secondary_signature_key', null);

    $payload = [
        'id' => 'webhook-event-1',
        'trigger' => 'FILE.UPLOADED',
        'source' => [
            'type' => 'file',
            'id' => '123456',
            'name' => 'brief.md',
        ],
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = '1700000000';
    $signature = base64_encode(hash_hmac('sha256', $timestamp.$body, 'test-primary-key', true));

    $response = $this->call(
        'POST',
        '/webhooks/box',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_BOX_DELIVERY_ID' => 'delivery-123',
            'HTTP_BOX_DELIVERY_TIMESTAMP' => $timestamp,
            'HTTP_BOX_SIGNATURE_PRIMARY' => $signature,
        ],
        $body
    );

    $response->assertStatus(202);

    $this->assertDatabaseHas('box_webhook_events', [
        'delivery_id' => 'delivery-123',
        'trigger' => 'FILE.UPLOADED',
        'source_type' => 'file',
        'source_id' => '123456',
        'status' => 'received',
    ]);

    Queue::assertPushed(ProcessBoxWebhookEvent::class);
});

test('box webhook rejects invalid signature when enforcement is enabled', function () {
    config()->set('services.box.webhook.enforce_signature', true);
    config()->set('services.box.webhook.primary_signature_key', 'test-primary-key');
    config()->set('services.box.webhook.secondary_signature_key', null);

    $payload = [
        'trigger' => 'FILE.UPLOADED',
        'source' => ['type' => 'file', 'id' => 'abc123'],
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $response = $this->call(
        'POST',
        '/webhooks/box',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_BOX_DELIVERY_ID' => 'delivery-invalid',
            'HTTP_BOX_DELIVERY_TIMESTAMP' => '1700000000',
            'HTTP_BOX_SIGNATURE_PRIMARY' => 'bad-signature',
        ],
        $body
    );

    $response->assertStatus(401);

    $this->assertDatabaseMissing('box_webhook_events', [
        'delivery_id' => 'delivery-invalid',
    ]);
});

test('box webhook accepts unsigned payload when signature enforcement is disabled', function () {
    Queue::fake();

    config()->set('services.box.webhook.enforce_signature', false);
    config()->set('services.box.webhook.primary_signature_key', null);
    config()->set('services.box.webhook.secondary_signature_key', null);

    $payload = [
        'trigger' => 'FILE.PREVIEWED',
        'source' => ['type' => 'file', 'id' => 'unsigned-file-id'],
    ];

    $response = $this->postJson('/webhooks/box', $payload);

    $response->assertStatus(202);

    $this->assertDatabaseHas('box_webhook_events', [
        'trigger' => 'FILE.PREVIEWED',
        'source_type' => 'file',
        'source_id' => 'unsigned-file-id',
        'status' => 'received',
    ]);

    Queue::assertPushed(ProcessBoxWebhookEvent::class);
});
