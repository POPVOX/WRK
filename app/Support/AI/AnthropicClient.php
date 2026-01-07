<?php

namespace App\Support\AI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicClient
{
    public const DEFAULT_TIMEOUT = 120;

    public const API_URL = 'https://api.anthropic.com/v1/messages';

    public const API_VERSION = '2023-06-01';

    public const DEFAULT_MODEL = 'claude-sonnet-4-20250514';

    public static function request(): PendingRequest
    {
        $apiKey = config('services.anthropic.api_key') ?: env('ANTHROPIC_API_KEY');

        return Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
            'Content-Type' => 'application/json',
        ])->timeout(self::DEFAULT_TIMEOUT)->retry(2, 500);
    }

    /**
     * Send a message to Anthropic with standard logging.
     *
     * @param  array{messages: array<int, array{role:string,content:string}>, system?:string, max_tokens?:int, model?:string}  $payload
     */
    public static function send(array $payload): array
    {
        $model = $payload['model'] ?? self::DEFAULT_MODEL;
        $baseKey = 'metrics:ai';

        $start = microtime(true);
        $res = self::request()->post(self::API_URL, array_merge([
            'model' => $model,
            'max_tokens' => $payload['max_tokens'] ?? 2000,
        ], $payload));

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if ($res->successful()) {
            $log = [
                'model' => $model,
                'status' => $res->status(),
                'duration_ms' => $durationMs,
            ];
            Log::info('Anthropic request success', $log);
            if (config('ai.metrics.enabled', true)) {
                Cache::increment("{$baseKey}:success");
                Cache::increment("{$baseKey}:count");
                Cache::increment("{$baseKey}:latency_ms_total", $durationMs);
                Cache::put("{$baseKey}:last_success_at", now()->toIso8601String(), 3600);
                Log::info('metric.ai_request', $log + ['outcome' => 'success']);
            }

            return $res->json();
        }

        $log = [
            'model' => $model,
            'status' => $res->status(),
            'duration_ms' => $durationMs,
            'body' => $res->body(),
        ];

        Log::error('Anthropic request failed', $log);
        if (config('ai.metrics.enabled', true)) {
            Cache::increment("{$baseKey}:error");
            Cache::increment("{$baseKey}:count");
            Cache::increment("{$baseKey}:latency_ms_total", $durationMs);
            Cache::put("{$baseKey}:last_error_at", now()->toIso8601String(), 3600);
            Cache::put("{$baseKey}:last_error_status", $res->status(), 3600);
            Cache::put("{$baseKey}:last_error_body", substr($res->body(), 0, 500), 3600);
            Log::warning('metric.ai_request', $log + ['outcome' => 'error']);
        }

        // Best-effort shape to not break callers
        return [
            'error' => true,
            'status' => $res->status(),
            'body' => $res->body(),
        ];
    }
}
