<?php

use App\Jobs\ProcessSlackWebhookEvent;
use Illuminate\Support\Facades\Queue;

test('slack slash webhook stores event and queues processor when signature is valid', function () {
    Queue::fake();

    config()->set('services.slack.webhook.enforce_signature', true);
    config()->set('services.slack.webhook.signing_secret', 'test-signing-secret');

    $payload = [
        'token' => 'token',
        'team_id' => 'T123',
        'channel_id' => 'C123',
        'user_id' => 'U123',
        'command' => '/wrk',
        'text' => 'What are my priorities this week?',
        'response_url' => 'https://example.test/slack/response',
        'trigger_id' => 'trigger-1',
    ];

    $body = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
    $timestamp = (string) now()->timestamp;
    $signature = 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", 'test-signing-secret');

    $response = $this->call(
        'POST',
        '/webhooks/slack',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_X_SLACK_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_X_SLACK_SIGNATURE' => $signature,
        ],
        $body
    );

    $response->assertStatus(200)
        ->assertJson([
            'response_type' => 'ephemeral',
        ]);

    $this->assertDatabaseHas('slack_webhook_events', [
        'event_type' => 'slash_command:/wrk',
        'slack_user_id' => 'U123',
        'slack_channel_id' => 'C123',
        'status' => 'received',
    ]);

    Queue::assertPushed(ProcessSlackWebhookEvent::class);
});

test('slack webhook rejects invalid signature when enforcement is enabled', function () {
    config()->set('services.slack.webhook.enforce_signature', true);
    config()->set('services.slack.webhook.signing_secret', 'test-signing-secret');

    $payload = [
        'channel_id' => 'C999',
        'user_id' => 'U999',
        'command' => '/wrk',
        'text' => 'hello',
        'response_url' => 'https://example.test/slack/response',
    ];

    $body = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);

    $response = $this->call(
        'POST',
        '/webhooks/slack',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_X_SLACK_REQUEST_TIMESTAMP' => (string) now()->timestamp,
            'HTTP_X_SLACK_SIGNATURE' => 'v0=invalid',
        ],
        $body
    );

    $response->assertStatus(401);

    $this->assertDatabaseMissing('slack_webhook_events', [
        'slack_user_id' => 'U999',
        'slack_channel_id' => 'C999',
    ]);
});

test('slack url verification challenge returns challenge and does not queue', function () {
    Queue::fake();

    config()->set('services.slack.webhook.enforce_signature', true);
    config()->set('services.slack.webhook.signing_secret', 'test-signing-secret');

    $payload = [
        'type' => 'url_verification',
        'challenge' => 'challenge-token',
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = (string) now()->timestamp;
    $signature = 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", 'test-signing-secret');

    $response = $this->call(
        'POST',
        '/webhooks/slack',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SLACK_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_X_SLACK_SIGNATURE' => $signature,
        ],
        $body
    );

    $response->assertStatus(200);
    expect($response->getContent())->toBe('challenge-token');

    Queue::assertNothingPushed();

    $this->assertDatabaseCount('slack_webhook_events', 0);
});
