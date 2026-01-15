<?php

namespace App\Services;

use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EventParserService
{
    /**
     * Parse unstructured text (event descriptions, invites, etc.) using AI.
     *
     * @return array{event: array, error?: string, parsing_notes?: string}
     */
    public function parseText(string $text): array
    {
        if (empty(trim($text))) {
            return ['event' => null, 'error' => 'No text provided'];
        }

        if (!config('ai.enabled', true)) {
            return ['event' => null, 'error' => 'AI features are disabled'];
        }

        try {
            $prompt = $this->buildPrompt($text);

            $response = AnthropicClient::send([
                'system' => $this->getSystemPrompt(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 2000,
            ]);

            if (isset($response['error']) && $response['error']) {
                Log::error('EventParser AI error', ['response' => $response]);

                return ['event' => null, 'error' => 'Failed to connect to AI service'];
            }

            $content = $response['content'][0]['text'] ?? '';

            return $this->parseAIResponse($content);
        } catch (\Exception $e) {
            Log::error('EventParser exception: ' . $e->getMessage());

            return ['event' => null, 'error' => 'Error processing event info: ' . $e->getMessage()];
        }
    }

    /**
     * Extract event metadata from a URL (Eventbrite, Meetup, etc.)
     *
     * @return array{event: array, error?: string}
     */
    public function parseUrl(string $url): array
    {
        if (empty(trim($url))) {
            return ['event' => null, 'error' => 'No URL provided'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['event' => null, 'error' => 'Invalid URL format'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; TravelBot/1.0)',
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])
                ->get($url);

            if (!$response->successful()) {
                return ['event' => null, 'error' => 'Could not fetch URL (HTTP ' . $response->status() . ')'];
            }

            $html = $response->body();
            $textContent = $this->extractTextFromHtml($html);
            $metadata = $this->extractMetadataFromHtml($html);

            $aiResult = $this->parseText($textContent);

            if (isset($aiResult['event']) && $aiResult['event']) {
                $aiResult['event'] = array_merge($aiResult['event'], array_filter($metadata));
                $aiResult['event']['source_url'] = $url;
            }

            return $aiResult;
        } catch (\Exception $e) {
            Log::error('EventParser URL fetch error: ' . $e->getMessage());

            return ['event' => null, 'error' => 'Error fetching URL: ' . $e->getMessage()];
        }
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert at parsing event information from various sources including calendar invites, event pages, conference agendas, and meeting notifications.

You should identify and extract:
- Event title
- Event type (conference_session, meeting, presentation, workshop, reception, site_visit, other)
- Date and time (start and end)
- Location/venue name
- Address
- Description
- Any notes or additional details

Be precise about:
- Dates (convert to YYYY-MM-DD format)
- Times (convert to HH:MM 24-hour format)
- Distinguish between venue name and full address
PROMPT;
    }

    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
Please analyze the following event information and extract all relevant details.

Return a JSON object with this structure:
{
  "title": "Event title",
  "type": "conference_session|meeting|presentation|workshop|reception|site_visit|other",
  "start_date": "YYYY-MM-DD",
  "start_time": "HH:MM (24-hour format)",
  "end_date": "YYYY-MM-DD (if different from start)",
  "end_time": "HH:MM (24-hour format)",
  "location": "Venue or location name",
  "address": "Full street address if available",
  "description": "Event description or summary",
  "notes": "Any other relevant details",
  "confidence": 0.0 to 1.0 (how confident you are in this extraction)
}

Important:
- Extract as much information as possible, even if some fields are missing
- If only a date is mentioned without year, assume the next occurrence
- If times are in 12-hour format, convert to 24-hour
- Include confidence score - lower for ambiguous extractions

EVENT TEXT:
---
{$text}
---

Respond with ONLY valid JSON, no markdown code blocks or explanation.
PROMPT;
    }

    protected function parseAIResponse(string $content): array
    {
        $content = preg_replace('/^```json?\s*/m', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        if (!is_array($data)) {
            return ['event' => null, 'error' => 'Failed to parse AI response'];
        }

        $normalized = $this->normalizeEvent($data);

        if (!$normalized) {
            return ['event' => null, 'error' => 'Could not extract valid event information'];
        }

        return [
            'event' => $normalized,
            'parsing_notes' => $data['parsing_notes'] ?? null,
        ];
    }

    protected function normalizeEvent(array $data): ?array
    {
        if (empty($data['title'])) {
            return null;
        }

        $startDate = $this->parseDate($data['start_date'] ?? null);
        $startTime = $this->parseTime($data['start_time'] ?? null);
        $endDate = $this->parseDate($data['end_date'] ?? null) ?: $startDate;
        $endTime = $this->parseTime($data['end_time'] ?? null);

        // Build datetime strings
        $startDatetime = $startDate ? ($startDate . ($startTime ? ' ' . $startTime : ' 00:00')) : null;
        $endDatetime = $endDate ? ($endDate . ($endTime ? ' ' . $endTime : ' 23:59')) : null;

        // Validate type
        $validTypes = ['conference_session', 'meeting', 'presentation', 'workshop', 'reception', 'site_visit', 'other'];
        $type = strtolower($data['type'] ?? 'other');
        if (!in_array($type, $validTypes)) {
            $type = 'other';
        }

        return [
            'title' => trim($data['title']),
            'type' => $type,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => $data['location'] ?? null,
            'address' => $data['address'] ?? null,
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'confidence' => min(1, max(0, (float) ($data['confidence'] ?? 0.8))),
        ];
    }

    protected function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($time)->format('H:i');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function extractTextFromHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > 10000) {
            $text = substr($text, 0, 10000) . '...';
        }

        return $text;
    }

    protected function extractMetadataFromHtml(string $html): array
    {
        $metadata = [];

        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/', $html, $matches)) {
            $metadata['title'] = html_entity_decode($matches[1]);
        }

        if (preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            $jsonLd = json_decode($matches[1], true);
            if (is_array($jsonLd)) {
                if (isset($jsonLd['name'])) {
                    $metadata['title'] = $jsonLd['name'];
                }
                if (isset($jsonLd['startDate'])) {
                    try {
                        $dt = \Carbon\Carbon::parse($jsonLd['startDate']);
                        $metadata['start_datetime'] = $dt->format('Y-m-d H:i');
                    } catch (\Exception $e) {
                    }
                }
                if (isset($jsonLd['endDate'])) {
                    try {
                        $dt = \Carbon\Carbon::parse($jsonLd['endDate']);
                        $metadata['end_datetime'] = $dt->format('Y-m-d H:i');
                    } catch (\Exception $e) {
                    }
                }
                if (isset($jsonLd['location'])) {
                    if (is_array($jsonLd['location'])) {
                        $metadata['location'] = $jsonLd['location']['name'] ?? null;
                        $metadata['address'] = $jsonLd['location']['address'] ?? null;
                    } else {
                        $metadata['location'] = $jsonLd['location'];
                    }
                }
            }
        }

        if (empty($metadata['title']) && preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
            $metadata['title'] = html_entity_decode(trim($matches[1]));
        }

        return $metadata;
    }
}
