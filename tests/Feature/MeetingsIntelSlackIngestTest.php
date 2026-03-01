<?php

use App\Jobs\ProcessSlackWebhookEvent;
use App\Models\MeetingIntelNote;
use App\Models\SlackWebhookEvent;
use App\Services\Slack\SlackCommandRouterService;
use App\Services\Slack\SlackWebhookService;

test('processing configured meetings intel slack message stores note for intelligence tagging', function () {
    config()->set('services.slack.meetings_intel_channel_ids', ['CMEETINGSINTEL']);
    config()->set('services.slack.bot_token', '');
    config()->set('services.slack.notifications.bot_user_oauth_token', '');

    $event = SlackWebhookEvent::query()->create([
        'delivery_id' => 'delivery-meetings-intel-1',
        'event_type' => 'event:message',
        'slack_user_id' => 'U12345',
        'slack_channel_id' => 'CMEETINGSINTEL',
        'payload' => [
            'type' => 'event_callback',
            'event_id' => 'Ev12345',
            'event' => [
                'type' => 'message',
                'channel' => 'CMEETINGSINTEL',
                'user' => 'U12345',
                'text' => "Strong donor signals from today's meeting with <@U99999> <https://example.org/report|readout>",
                'ts' => '1712345678.000200',
            ],
        ],
        'status' => 'received',
    ]);

    (new ProcessSlackWebhookEvent($event->id))->handle(
        app(SlackCommandRouterService::class),
        app(SlackWebhookService::class)
    );

    $this->assertDatabaseHas('meeting_intel_notes', [
        'source' => 'slack_meetings_intel',
        'slack_channel_id' => 'CMEETINGSINTEL',
        'slack_message_ts' => '1712345678.000200',
    ]);

    $note = MeetingIntelNote::query()
        ->where('slack_channel_id', 'CMEETINGSINTEL')
        ->where('slack_message_ts', '1712345678.000200')
        ->firstOrFail();

    expect($note->content)->toContain('Strong donor signals')
        ->toContain('@teammate')
        ->toContain('readout (https://example.org/report)');

    expect($event->fresh()->status)->toBe('processed');
});

test('processing message outside configured meetings intel channel does not store note', function () {
    config()->set('services.slack.meetings_intel_channel_ids', ['CMEETINGSINTEL']);

    $event = SlackWebhookEvent::query()->create([
        'delivery_id' => 'delivery-meetings-intel-2',
        'event_type' => 'event:message',
        'slack_user_id' => 'U12345',
        'slack_channel_id' => 'COTHER',
        'payload' => [
            'type' => 'event_callback',
            'event_id' => 'Ev22222',
            'event' => [
                'type' => 'message',
                'channel' => 'COTHER',
                'user' => 'U12345',
                'text' => 'This should not be ingested into meetings intel.',
                'ts' => '1712345679.000200',
            ],
        ],
        'status' => 'received',
    ]);

    (new ProcessSlackWebhookEvent($event->id))->handle(
        app(SlackCommandRouterService::class),
        app(SlackWebhookService::class)
    );

    $this->assertDatabaseMissing('meeting_intel_notes', [
        'slack_channel_id' => 'COTHER',
        'slack_message_ts' => '1712345679.000200',
    ]);

    expect($event->fresh()->status)->toBe('processed');
});

