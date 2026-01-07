<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeetingAIService
{
    protected ?string $apiKey;

    protected string $baseUrl = 'https://api.anthropic.com/v1';

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
        if (empty($this->apiKey)) {
            Log::error('Anthropic API key not configured');

            return $this->getEmptyExtraction();
        }

        try {
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

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/messages", [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Anthropic API error: '.$response->body());

                return $this->getEmptyExtraction();
            }

            $content = $response->json('content.0.text', '');

            // Parse JSON response
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to extract JSON from the response if it's wrapped
                if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                    $data = json_decode($matches[0], true);
                }
            }

            return $data ?? $this->getEmptyExtraction();
        } catch (\Exception $e) {
            Log::error('AI extraction error: '.$e->getMessage());

            return $this->getEmptyExtraction();
        }
    }

    /**
     * Get empty extraction result.
     */
    protected function getEmptyExtraction(): array
    {
        return [
            'suggested_title' => '',
            'organizations' => [],
            'people' => [],
            'issues' => [],
            'key_ask' => '',
            'commitments_made' => '',
            'suggested_date' => null,
            'ai_summary' => '',
        ];
    }
}
