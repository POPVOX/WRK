<?php

namespace App\Services\Intelligence;

use App\Models\MeetingIntelNote;
use App\Services\Slack\SlackWebhookService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class MeetingsIntelIngestService
{
    public function __construct(
        protected SlackWebhookService $slackWebhookService
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function shouldIngest(array $payload): bool
    {
        $type = trim((string) ($payload['type'] ?? ''));
        $eventType = trim((string) Arr::get($payload, 'event.type', ''));
        $channelId = trim((string) Arr::get($payload, 'event.channel', ''));

        if ($type !== 'event_callback' || $eventType !== 'message' || $channelId === '') {
            return false;
        }

        $configured = $this->configuredChannelIds();
        if (empty($configured)) {
            return false;
        }

        return in_array($channelId, $configured, true);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function ingest(array $payload): ?MeetingIntelNote
    {
        if (! Schema::hasTable('meeting_intel_notes')) {
            return null;
        }

        if (! $this->shouldIngest($payload)) {
            return null;
        }

        $event = Arr::get($payload, 'event');
        if (! is_array($event)) {
            return null;
        }

        $subtype = trim((string) ($event['subtype'] ?? ''));
        if ($subtype !== '' && ! in_array($subtype, ['thread_broadcast'], true)) {
            return null;
        }

        if (trim((string) ($event['bot_id'] ?? '')) !== '') {
            return null;
        }

        $channelId = trim((string) ($event['channel'] ?? ''));
        $messageTs = trim((string) ($event['ts'] ?? ''));
        if ($channelId === '' || $messageTs === '') {
            return null;
        }

        $text = $this->normalizeText((string) ($event['text'] ?? ''));
        if ($text === '') {
            return null;
        }

        $threadTs = trim((string) ($event['thread_ts'] ?? $messageTs));
        $slackUserId = trim((string) ($event['user'] ?? ''));
        $actor = $this->slackWebhookService->resolveActor($payload);
        $capturedAt = $this->parseSlackTimestamp($messageTs);

        return MeetingIntelNote::query()->updateOrCreate(
            [
                'slack_channel_id' => $channelId,
                'slack_message_ts' => $messageTs,
            ],
            [
                'source' => 'slack_meetings_intel',
                'source_ref' => trim((string) ($event['client_msg_id'] ?? $messageTs)) ?: $messageTs,
                'slack_thread_ts' => $threadTs !== '' ? $threadTs : null,
                'author_user_id' => $actor?->id,
                'author_label' => $actor?->name ?: ($slackUserId !== '' ? 'Slack '.$slackUserId : null),
                'content' => $text,
                'captured_at' => $capturedAt,
                'metadata' => [
                    'event_id' => (string) ($payload['event_id'] ?? ''),
                    'slack_user_id' => $slackUserId !== '' ? $slackUserId : null,
                    'subtype' => $subtype !== '' ? $subtype : null,
                    'mentions' => $this->extractMentions((string) ($event['text'] ?? '')),
                    'links' => $this->extractLinks((string) ($event['text'] ?? '')),
                ],
            ]
        );
    }

    /**
     * @return array<int,string>
     */
    protected function configuredChannelIds(): array
    {
        $value = config('services.slack.meetings_intel_channel_ids', []);
        if (is_string($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($channelId): string => trim((string) $channelId),
            $value
        )));
    }

    protected function normalizeText(string $text): string
    {
        $normalized = preg_replace_callback(
            '/<((?:https?:\/\/|mailto:)[^>|]+)(?:\|([^>]+))?>/i',
            static function (array $matches): string {
                $url = trim((string) ($matches[1] ?? ''));
                $label = trim((string) ($matches[2] ?? ''));
                if ($label === '' || $label === $url) {
                    return $url;
                }

                return "{$label} ({$url})";
            },
            $text
        ) ?? $text;

        $normalized = preg_replace('/<@[A-Z0-9]+>/', '@teammate', $normalized) ?? $normalized;
        $normalized = preg_replace('/<#[A-Z0-9]+\|([^>]+)>/', '#$1', $normalized) ?? $normalized;
        $normalized = str_replace(["\r\n", "\r"], "\n", $normalized);

        return trim($normalized);
    }

    /**
     * @return array<int,string>
     */
    protected function extractMentions(string $text): array
    {
        if (! preg_match_all('/<@([A-Z0-9]+)>/', $text, $matches) || empty($matches[1])) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn ($value): string => trim((string) $value),
            $matches[1]
        )));
    }

    /**
     * @return array<int,string>
     */
    protected function extractLinks(string $text): array
    {
        $links = [];

        if (preg_match_all('/<(https?:\/\/[^>|]+)(?:\|[^>]+)?>/i', $text, $matches) && isset($matches[1])) {
            foreach ($matches[1] as $url) {
                $links[] = trim((string) $url);
            }
        }

        if (preg_match_all('/https?:\/\/[^\s<>()"]+/i', $text, $matches) && isset($matches[0])) {
            foreach ($matches[0] as $url) {
                $links[] = trim((string) $url);
            }
        }

        return array_values(array_filter(array_unique(array_map(
            static fn (string $url): string => rtrim($url, '.,;:'),
            $links
        ))));
    }

    protected function parseSlackTimestamp(string $value): ?Carbon
    {
        $ts = trim($value);
        if ($ts === '' || ! is_numeric($ts)) {
            return null;
        }

        try {
            return Carbon::createFromTimestamp((int) floor((float) $ts));
        } catch (\Throwable) {
            return null;
        }
    }
}
