<?php

namespace App\Services;

use App\Exceptions\MeetingExtractionException;
use App\Support\AI\AnthropicClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MeetingAIService
{
    protected const MAX_EXTRACTION_CHARACTERS = 60_000;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key') ?? env('ANTHROPIC_API_KEY') ?? '';
    }

    /**
     * Transcribe audio is not supported by Anthropic.
     * We'll use browser's Web Speech API instead (handled in frontend).
     * This method is kept for compatibility but returns null.
     */
    public function transcribeAudio(string $filePath): ?string
    {
        // Anthropic doesn't have audio transcription
        // Audio transcription is handled by browser's Web Speech API
        Log::info('Audio transcription requested - use browser Web Speech API');

        return null;
    }

    /**
     * Extract structured meeting data from text using Claude.
     */
    public function extractMeetingData(string $text): array
    {
        if (! config('ai.enabled')) {
            throw new MeetingExtractionException(
                'disabled',
                'AI extraction is disabled in production. Ask an administrator to enable AI features.'
            );
        }

        if (empty($this->apiKey)) {
            Log::error('Anthropic API key not configured');

            throw new MeetingExtractionException(
                'not_configured',
                'Anthropic is not configured in production. Ask an administrator to add the API key.'
            );
        }

        $text = self::prepareNotes($text);

        $prompt = <<<PROMPT
Analyze the following meeting notes or transcript and extract structured information.
Return a JSON object with these fields:
- suggested_title: a short, descriptive title for this meeting (max 60 chars, e.g. "Housing Policy Discussion with City Council")
- organizations: array of organization/company names mentioned
- people: array of person names mentioned (attendees, participants)
- issues: array of topics, issues, or subjects discussed
- key_ask: the main request or ask from the meeting (string, can be empty)
- commitments_made: any promises, agreements, or next steps committed to (string, can be empty)
- suggested_date: if a meeting date is mentioned, extract it in YYYY-MM-DD format (can be null)
- ai_summary: a brief 2-3 sentence summary of the meeting

Only include items that are clearly mentioned. Don't invent or assume information.

Meeting notes:
---
{$text}
---

Respond with ONLY valid JSON, no markdown code blocks or explanation.
PROMPT;

        $response = AnthropicClient::send([
            'max_tokens' => 1600,
            'timeout' => 90,
            'attempts' => 1,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if ($response['error'] ?? false) {
            Log::error('Anthropic API error during meeting extraction', [
                'status' => $response['status'] ?? null,
                'body' => Str::limit((string) ($response['body'] ?? ''), 500),
            ]);

            throw $this->providerException($response);
        }

        if (($response['stop_reason'] ?? null) === 'max_tokens') {
            throw new MeetingExtractionException(
                'truncated',
                'The AI response was incomplete. Try extracting a shorter set of notes.'
            );
        }

        $contentBlocks = collect($response['content'] ?? [])
            ->filter(fn (mixed $block): bool => is_array($block)
                && isset($block['text'])
                && (! isset($block['type']) || $block['type'] === 'text'))
            ->map(fn (array $block): string => trim((string) $block['text']))
            ->filter()
            ->values();

        if ($contentBlocks->isEmpty()) {
            throw new MeetingExtractionException(
                'empty_response',
                'The AI returned no usable text. Please retry the extraction.'
            );
        }

        $data = null;
        foreach ($contentBlocks as $content) {
            $data = $this->decodeJsonObject($content);
            if (is_array($data)) {
                break;
            }
        }

        if (! is_array($data)) {
            $data = $this->decodeJsonObject($contentBlocks->implode("\n"));
        }

        if (! is_array($data)) {
            throw new MeetingExtractionException(
                'invalid_response',
                'The AI returned an unreadable result. Please retry the extraction.'
            );
        }

        $normalized = [
            'suggested_title' => Str::limit(Str::squish((string) ($data['suggested_title'] ?? '')), 255, ''),
            'organizations' => $this->normalizeNameList($data['organizations'] ?? []),
            'people' => $this->normalizeNameList($data['people'] ?? []),
            'issues' => $this->normalizeNameList($data['issues'] ?? []),
            'key_ask' => trim((string) ($data['key_ask'] ?? '')),
            'commitments_made' => trim((string) ($data['commitments_made'] ?? '')),
            'suggested_date' => $this->normalizeDate($data['suggested_date'] ?? null),
            'ai_summary' => trim((string) ($data['ai_summary'] ?? '')),
        ];

        if ($normalized['suggested_title'] === ''
            && $normalized['ai_summary'] === ''
            && $normalized['organizations'] === []
            && $normalized['people'] === []
            && $normalized['issues'] === []
            && $normalized['key_ask'] === ''
            && $normalized['commitments_made'] === '') {
            throw new MeetingExtractionException(
                'no_information',
                'The AI could not identify meeting details in these notes. Add a little more context and retry.'
            );
        }

        return $normalized;
    }

    public static function prepareNotes(string $text): string
    {
        $text = trim($text);
        if (Str::length($text) <= self::MAX_EXTRACTION_CHARACTERS) {
            return $text;
        }

        $beginning = Str::substr($text, 0, 45_000);
        $ending = Str::substr($text, -15_000);

        return $beginning."\n\n[Middle of exceptionally long notes omitted for extraction]\n\n".$ending;
    }

    protected function providerException(array $response): MeetingExtractionException
    {
        $status = (int) ($response['status'] ?? 0);
        $body = json_decode((string) ($response['body'] ?? ''), true);
        $providerMessage = Str::lower((string) data_get($body, 'error.message', ''));

        if (str_contains($providerMessage, 'credit balance')
            || str_contains($providerMessage, 'billing')
            || str_contains($providerMessage, 'payment')) {
            return new MeetingExtractionException(
                'billing',
                'The Anthropic account needs billing attention. Ask an administrator to check its credit balance.'
            );
        }

        return match (true) {
            in_array($status, [401, 403], true) => new MeetingExtractionException(
                'credentials',
                'Anthropic rejected the production API credentials. Ask an administrator to update the API key.'
            ),
            $status === 404 => new MeetingExtractionException(
                'model_unavailable',
                'The configured Anthropic model is unavailable in production. Ask an administrator to review the AI model setting.'
            ),
            $status === 429 => new MeetingExtractionException(
                'rate_limited',
                'Anthropic is rate-limiting requests right now. Wait a minute and retry.'
            ),
            $status >= 500 => new MeetingExtractionException(
                'provider_unavailable',
                'Anthropic is temporarily unavailable. Please retry in a few minutes.'
            ),
            default => new MeetingExtractionException(
                'provider_rejected',
                $status > 0
                    ? "Anthropic rejected the extraction request (HTTP {$status}). Ask an administrator to check Admin → Metrics."
                    : 'The AI provider could not complete the extraction. Please retry.'
            ),
        };
    }

    protected function decodeJsonObject(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (! preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return is_array($decoded) && json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /** @return array<int, string> */
    protected function normalizeNameList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn ($value): bool => is_string($value))
            ->map(fn (string $value): string => Str::limit(Str::squish($value), 255, ''))
            ->filter()
            ->unique(fn (string $value): string => Str::lower($value))
            ->values()
            ->all();
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);

            return $date && $date->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
