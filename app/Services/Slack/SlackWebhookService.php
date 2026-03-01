<?php

namespace App\Services\Slack;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SlackWebhookService
{
    public function __construct(
        protected SlackIdentityService $slackIdentityService
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function extractPayload(Request $request): array
    {
        $payloadEnvelope = $request->input('payload');
        if (is_string($payloadEnvelope) && trim($payloadEnvelope) !== '') {
            $decoded = json_decode($payloadEnvelope, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $json = $request->json()->all();
        if (is_array($json) && $json !== []) {
            return $json;
        }

        $all = $request->all();
        if (is_array($all) && $all !== []) {
            return $all;
        }

        $contentType = Str::lower((string) $request->header('Content-Type', ''));
        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $rawBody = $request->getContent();
            if (trim($rawBody) !== '') {
                $parsed = [];
                parse_str($rawBody, $parsed);
                if (is_array($parsed) && $parsed !== []) {
                    if (isset($parsed['payload']) && is_string($parsed['payload'])) {
                        $decoded = json_decode($parsed['payload'], true);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    }

                    return $parsed;
                }
            }
        }

        return [];
    }

    public function verifySignature(string $rawBody, ?string $timestamp, ?string $signature): bool
    {
        if (! (bool) config('services.slack.webhook.enforce_signature', true)) {
            return true;
        }

        $signingSecret = trim((string) config('services.slack.webhook.signing_secret', ''));
        if ($signingSecret === '') {
            return false;
        }

        $timestamp = trim((string) $timestamp);
        $signature = trim((string) $signature);
        if ($timestamp === '' || $signature === '' || ! is_numeric($timestamp)) {
            return false;
        }

        $timestampValue = (int) $timestamp;
        if (abs(now()->timestamp - $timestampValue) > 300) {
            return false;
        }

        $baseString = "v0:{$timestamp}:{$rawBody}";
        $computed = 'v0='.hash_hmac('sha256', $baseString, $signingSecret);

        return hash_equals($computed, $signature);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function resolveDeliveryId(array $payload, Request $request, string $rawBody): string
    {
        $candidates = [
            trim((string) Arr::get($payload, 'event_id', '')),
            trim((string) Arr::get($payload, 'trigger_id', '')),
            trim((string) Arr::get($payload, 'event.client_msg_id', '')),
            trim((string) $request->header('X-Slack-Request-Id', '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $timestamp = trim((string) $request->header('X-Slack-Request-Timestamp', ''));
        if ($timestamp === '') {
            $timestamp = (string) now()->timestamp;
        }

        return hash('sha256', $timestamp.'|'.$rawBody);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function eventType(array $payload): string
    {
        if ($this->isSlashCommandPayload($payload)) {
            $command = trim((string) ($payload['command'] ?? ''));

            return $command !== '' ? 'slash_command:'.$command : 'slash_command';
        }

        $type = trim((string) ($payload['type'] ?? ''));
        if ($type === 'event_callback') {
            $eventType = trim((string) Arr::get($payload, 'event.type', ''));

            return $eventType !== '' ? 'event:'.$eventType : 'event';
        }

        if ($type !== '') {
            return 'type:'.$type;
        }

        return 'unknown';
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function slackUserId(array $payload): ?string
    {
        $userId = trim((string) (
            Arr::get($payload, 'user_id')
            ?: Arr::get($payload, 'user.id')
            ?: Arr::get($payload, 'event.user')
        ));

        return $userId !== '' ? $userId : null;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function slackChannelId(array $payload): ?string
    {
        $channelId = trim((string) (
            Arr::get($payload, 'channel_id')
            ?: Arr::get($payload, 'channel.id')
            ?: Arr::get($payload, 'event.channel')
        ));

        return $channelId !== '' ? $channelId : null;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function isSlashCommandPayload(array $payload): bool
    {
        return trim((string) ($payload['command'] ?? '')) !== ''
            && trim((string) ($payload['response_url'] ?? '')) !== '';
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function resolveActor(array $payload): ?User
    {
        $slackUserId = $this->slackUserId($payload);
        $workspaceId = $this->workspaceId($payload);

        if ($slackUserId !== null) {
            $identityMatch = $this->slackIdentityService->resolveUser($slackUserId, $workspaceId);
            if ($identityMatch) {
                return $identityMatch;
            }
        }

        $email = trim((string) Arr::get($payload, 'user_email', ''));
        if ($email === '') {
            if ($slackUserId) {
                $email = trim((string) ($this->fetchUserEmail($slackUserId) ?? ''));
            }
        }

        if ($email === '') {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->first();

        if ($user && $slackUserId !== null) {
            $this->slackIdentityService->rememberIdentity($user, $slackUserId, $workspaceId);
        }

        return $user;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public function workspaceId(array $payload): ?string
    {
        $workspaceId = trim((string) (
            Arr::get($payload, 'team_id')
            ?: Arr::get($payload, 'team.id')
            ?: Arr::get($payload, 'authorizations.0.team_id')
        ));

        return $workspaceId !== '' ? $workspaceId : null;
    }

    public function postResponse(string $responseUrl, string $text): void
    {
        $responseUrl = trim($responseUrl);
        if ($responseUrl === '') {
            throw new RuntimeException('Slack response URL is required.');
        }

        Http::asJson()
            ->timeout(20)
            ->post($responseUrl, [
                'response_type' => 'ephemeral',
                'replace_original' => false,
                'text' => Str::limit(trim($text), 3500, '...'),
            ])
            ->throw();
    }

    public function postChannelMessage(string $channelId, string $text, ?string $threadTs = null): void
    {
        $token = $this->token();
        if ($token === '') {
            throw new RuntimeException('Slack bot token is not configured.');
        }

        $payload = array_filter([
            'channel' => trim($channelId),
            'text' => Str::limit(trim($text), 3500, '...'),
            'thread_ts' => $threadTs ? trim($threadTs) : null,
        ], static fn ($value): bool => $value !== null && $value !== '');

        $response = Http::withToken($token)
            ->asForm()
            ->timeout(20)
            ->post($this->apiUrl('chat.postMessage'), $payload)
            ->throw()
            ->json();

        if (! ($response['ok'] ?? false)) {
            $error = (string) ($response['error'] ?? 'unknown_error');
            throw new RuntimeException("Slack chat.postMessage failed: {$error}");
        }
    }

    protected function fetchUserEmail(string $slackUserId): ?string
    {
        $token = $this->token();
        if ($token === '') {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->asForm()
                ->timeout(15)
                ->post($this->apiUrl('users.info'), [
                    'user' => $slackUserId,
                ])
                ->throw()
                ->json();
        } catch (\Throwable) {
            return null;
        }

        if (! ($response['ok'] ?? false)) {
            return null;
        }

        $email = trim((string) Arr::get($response, 'user.profile.email', ''));

        return $email !== '' ? $email : null;
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
        $base = rtrim((string) config('services.slack.api_base', 'https://slack.com/api'), '/');

        return $base.'/'.ltrim($path, '/');
    }
}
