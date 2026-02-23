<?php

namespace App\Services\Outreach;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SlackInsightService
{
    protected string $apiBase = 'https://slack.com/api';

    public function isConfigured(): bool
    {
        return $this->token() !== '';
    }

    /**
     * @return array{
     *   channel_id:string,
     *   days_back:int,
     *   message_count:int,
     *   messages:array<int,array{
     *     ts:string,
     *     author:string,
     *     user_id:string,
     *     text:string,
     *     links:array<int,string>
     *   }>
     * }
     */
    public function fetchChannelInsights(string $channelId, int $daysBack = 7, int $maxMessages = 80): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Set SLACK_BOT_USER_OAUTH_TOKEN before using Slack ingest.');
        }

        $channelId = trim($channelId);
        if ($channelId === '') {
            throw new RuntimeException('Slack channel ID is required for Substack drafting.');
        }

        $daysBack = max(1, min($daysBack, 30));
        $maxMessages = max(10, min($maxMessages, 400));
        $oldestTimestamp = now()->subDays($daysBack)->getTimestamp();

        $messages = [];
        $cursor = null;

        do {
            $remaining = $maxMessages - count($messages);
            if ($remaining <= 0) {
                break;
            }

            $payload = array_filter([
                'channel' => $channelId,
                'limit' => min(200, $remaining),
                'oldest' => (string) $oldestTimestamp,
                'inclusive' => true,
                'cursor' => $cursor,
            ], static fn ($value): bool => $value !== null && $value !== '');

            $response = Http::withToken($this->token())
                ->asForm()
                ->timeout(20)
                ->post($this->apiUrl('conversations.history'), $payload)
                ->throw()
                ->json();

            if (! ($response['ok'] ?? false)) {
                $error = (string) ($response['error'] ?? 'unknown_error');
                throw new RuntimeException("Slack conversations.history failed: {$error}");
            }

            foreach ((array) ($response['messages'] ?? []) as $message) {
                if (! $this->isSignalMessage($message)) {
                    continue;
                }

                $text = (string) ($message['text'] ?? '');
                $messages[] = [
                    'ts' => (string) ($message['ts'] ?? ''),
                    'author' => (string) ($message['user'] ?? ''),
                    'user_id' => (string) ($message['user'] ?? ''),
                    'text' => $this->normalizeText($text),
                    'links' => $this->extractLinks($text),
                ];

                if (count($messages) >= $maxMessages) {
                    break;
                }
            }

            $nextCursor = (string) Arr::get($response, 'response_metadata.next_cursor', '');
            $cursor = $nextCursor !== '' ? $nextCursor : null;
        } while ($cursor !== null && count($messages) < $maxMessages);

        $messages = $this->hydrateAuthorNames($messages);
        usort($messages, static fn (array $a, array $b): int => ((float) $a['ts']) <=> ((float) $b['ts']));

        return [
            'channel_id' => $channelId,
            'days_back' => $daysBack,
            'message_count' => count($messages),
            'messages' => $messages,
        ];
    }

    /**
     * @param  array<int,array{author:string,user_id:string,ts:string,text:string,links:array<int,string>}>  $messages
     * @return array<int,array{author:string,user_id:string,ts:string,text:string,links:array<int,string>}>
     */
    protected function hydrateAuthorNames(array $messages): array
    {
        $userIds = collect($messages)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return $messages;
        }

        $nameMap = [];
        foreach ($userIds as $userId) {
            try {
                $response = Http::withToken($this->token())
                    ->asForm()
                    ->timeout(15)
                    ->post($this->apiUrl('users.info'), ['user' => $userId])
                    ->throw()
                    ->json();

                if (! ($response['ok'] ?? false)) {
                    continue;
                }

                $profile = (array) Arr::get($response, 'user.profile', []);
                $displayName = trim((string) ($profile['display_name'] ?? ''));
                $realName = trim((string) ($profile['real_name'] ?? ''));
                $nameMap[$userId] = $displayName !== '' ? $displayName : ($realName !== '' ? $realName : $userId);
            } catch (\Throwable) {
                $nameMap[$userId] = $userId;
            }
        }

        return array_map(
            static function (array $message) use ($nameMap): array {
                $userId = (string) ($message['user_id'] ?? '');
                if ($userId !== '' && isset($nameMap[$userId])) {
                    $message['author'] = $nameMap[$userId];
                }

                return $message;
            },
            $messages
        );
    }

    /**
     * @param  array<string,mixed>  $message
     */
    protected function isSignalMessage(array $message): bool
    {
        $text = trim((string) ($message['text'] ?? ''));
        if ($text === '') {
            return false;
        }

        $subtype = (string) ($message['subtype'] ?? '');
        if ($subtype !== '' && ! in_array($subtype, ['thread_broadcast'], true)) {
            return false;
        }

        if (str_starts_with($text, '/')) {
            return false;
        }

        return true;
    }

    /**
     * Convert Slack formatting to plain text markdown-ish content.
     */
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
        $normalized = str_replace(["\r\n", "\r"], "\n", $normalized);

        return trim($normalized);
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

        $cleaned = array_values(array_filter(array_unique(array_map(
            static fn (string $url): string => rtrim($url, '.,;:'),
            $links
        ))));

        return array_values(array_filter($cleaned, static fn (string $url): bool => str_starts_with($url, 'http')));
    }

    protected function token(): string
    {
        return trim((string) (
            config('services.slack.bot_token')
            ?: config('services.slack.notifications.bot_user_oauth_token')
        ));
    }

    protected function apiUrl(string $path): string
    {
        $base = rtrim((string) config('services.slack.api_base', $this->apiBase), '/');

        return $base.'/'.ltrim($path, '/');
    }
}
