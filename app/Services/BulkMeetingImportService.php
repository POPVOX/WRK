<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class BulkMeetingImportService
{
    /**
     * Extract meetings from unstructured text using AI
     */
    public function extractMeetings(string $rawText): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => 'claude-sonnet-4-20250514',
                        'max_tokens' => 4000,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $this->buildExtractionPrompt($rawText),
                            ]
                        ]
                    ]);

            $content = $response->json('content.0.text');

            if (!$content) {
                return ['error' => 'No response from AI', 'meetings' => []];
            }

            // Parse the JSON from the response
            $meetings = $this->parseAIResponse($content);

            return ['meetings' => $meetings, 'error' => null];

        } catch (\Exception $e) {
            \Log::error('BulkMeetingImportService error: ' . $e->getMessage());
            return ['error' => 'Failed to extract meetings: ' . $e->getMessage(), 'meetings' => []];
        }
    }

    protected function buildExtractionPrompt(string $rawText): string
    {
        return <<<PROMPT
You are extracting meeting information from unstructured text. Parse the following text and identify individual meetings.

For each meeting, extract:
- date: Meeting date in YYYY-MM-DD format (estimate if only partial info given)
- title: A short title for the meeting
- organizations: Array of organization names mentioned
- people: Array of person names mentioned (with their titles if available)
- summary: A brief summary of what was discussed
- key_ask: The main request or ask from this meeting (if any)
- notes: The raw notes/text relevant to this meeting

TEXT TO PARSE:
---
{$rawText}
---

Respond with ONLY a JSON array of meeting objects. No explanation, no markdown, just valid JSON.

Example format:
[
  {
    "date": "2024-12-15",
    "title": "Meeting with XYZ Corp",
    "organizations": ["XYZ Corporation"],
    "people": [{"name": "John Smith", "title": "Director"}],
    "summary": "Discussed partnership opportunities",
    "key_ask": "Follow up with proposal by January",
    "notes": "Met with John to discuss..."
  }
]

If you cannot identify any meetings, return an empty array: []
PROMPT;
    }

    protected function parseAIResponse(string $content): array
    {
        // Try to extract JSON from the response
        $content = trim($content);

        // Handle markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::warning('Failed to parse AI response as JSON: ' . json_last_error_msg());
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Create meetings from extracted data
     */
    public function createMeetings(array $extractedMeetings): array
    {
        $created = [];
        $errors = [];

        foreach ($extractedMeetings as $index => $data) {
            try {
                // Create the meeting
                $meeting = Meeting::create([
                    'user_id' => Auth::id(),
                    'meeting_date' => $data['date'] ?? now()->format('Y-m-d'),
                    'title' => $data['title'] ?? 'Imported Meeting',
                    'raw_notes' => $data['notes'] ?? '',
                    'ai_summary' => $data['summary'] ?? null,
                    'key_ask' => $data['key_ask'] ?? null,
                    'status' => 'new',
                ]);

                // Link organizations
                if (!empty($data['organizations'])) {
                    foreach ($data['organizations'] as $orgName) {
                        $org = Organization::firstOrCreate(
                            ['name' => trim($orgName)],
                            ['name' => trim($orgName)]
                        );
                        $meeting->organizations()->attach($org->id);
                    }
                }

                // Link people
                if (!empty($data['people'])) {
                    foreach ($data['people'] as $personData) {
                        $personName = is_string($personData) ? $personData : ($personData['name'] ?? null);
                        $personTitle = is_array($personData) ? ($personData['title'] ?? null) : null;

                        if ($personName) {
                            $person = Person::firstOrCreate(
                                ['name' => trim($personName)],
                                [
                                    'name' => trim($personName),
                                    'title' => $personTitle,
                                ]
                            );
                            $meeting->people()->attach($person->id);
                        }
                    }
                }

                $created[] = $meeting;

            } catch (\Exception $e) {
                $errors[] = "Meeting " . ($index + 1) . ": " . $e->getMessage();
                \Log::error('Error creating meeting: ' . $e->getMessage());
            }
        }

        return [
            'created' => $created,
            'errors' => $errors,
        ];
    }
}
