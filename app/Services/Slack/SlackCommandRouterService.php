<?php

namespace App\Services\Slack;

use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SlackCommandRouterService
{
    public function __construct(
        protected ChatService $chatService,
        protected SlackWebhookService $slackWebhookService
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>|null
     */
    public function route(array $payload): ?array
    {
        if ($this->slackWebhookService->isSlashCommandPayload($payload)) {
            return $this->routeSlashCommand($payload);
        }

        $type = trim((string) ($payload['type'] ?? ''));
        $eventType = trim((string) Arr::get($payload, 'event.type', ''));
        if ($type === 'event_callback' && $eventType === 'app_mention') {
            return $this->routeAppMention($payload);
        }

        return null;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>|null
     */
    protected function routeSlashCommand(array $payload): ?array
    {
        $responseUrl = trim((string) ($payload['response_url'] ?? ''));
        if ($responseUrl === '') {
            return null;
        }

        $query = trim((string) ($payload['text'] ?? ''));
        if ($query === '') {
            return [
                'transport' => 'response_url',
                'response_url' => $responseUrl,
                'text' => 'Send `/wrk <question>` and I will reply from your WRK workspace context.',
            ];
        }

        $actor = $this->resolveActor($payload);
        $answer = $this->buildAnswer($query, $actor);

        return [
            'transport' => 'response_url',
            'response_url' => $responseUrl,
            'text' => $answer,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>|null
     */
    protected function routeAppMention(array $payload): ?array
    {
        $event = Arr::get($payload, 'event');
        if (! is_array($event)) {
            return null;
        }

        $channel = trim((string) ($event['channel'] ?? ''));
        if ($channel === '') {
            return null;
        }

        $query = $this->stripMentions(trim((string) ($event['text'] ?? '')));
        if ($query === '') {
            $query = 'Give me a quick summary of my highest-priority open work items.';
        }

        $actor = $this->resolveActor([
            'user_id' => (string) ($event['user'] ?? ''),
        ]);

        $answer = $this->buildAnswer($query, $actor);
        $threadTs = trim((string) ($event['thread_ts'] ?? $event['ts'] ?? ''));

        return [
            'transport' => 'channel',
            'channel' => $channel,
            'thread_ts' => $threadTs !== '' ? $threadTs : null,
            'text' => $answer,
        ];
    }

    protected function buildAnswer(string $query, ?User $actor): string
    {
        try {
            $answer = trim((string) $this->chatService->query($query, [], $actor));
        } catch (\Throwable) {
            $answer = '';
        }

        if ($answer === '') {
            $answer = 'I could not process that right now. Please try rephrasing the request.';
        }

        return Str::limit($answer, 3500, '...');
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected function resolveActor(array $payload): ?User
    {
        return $this->slackWebhookService->resolveActor($payload);
    }

    protected function stripMentions(string $text): string
    {
        $stripped = preg_replace('/<@[A-Z0-9]+>/', '', $text) ?? $text;
        $stripped = preg_replace('/\s+/', ' ', $stripped) ?? $stripped;

        return trim($stripped);
    }
}
