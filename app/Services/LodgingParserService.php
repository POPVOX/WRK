<?php

namespace App\Services;

use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LodgingParserService
{
    /**
     * Parse unstructured text (confirmation emails, booking details) using AI.
     *
     * @return array{lodging: array, error?: string, parsing_notes?: string}
     */
    public function parseText(string $text): array
    {
        if (empty(trim($text))) {
            return ['lodging' => null, 'error' => 'No text provided'];
        }

        if (!config('ai.enabled', true)) {
            return ['lodging' => null, 'error' => 'AI features are disabled'];
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
                Log::error('LodgingParser AI error', ['response' => $response]);

                return ['lodging' => null, 'error' => 'Failed to connect to AI service'];
            }

            $content = $response['content'][0]['text'] ?? '';

            return $this->parseAIResponse($content);
        } catch (\Exception $e) {
            Log::error('LodgingParser exception: ' . $e->getMessage());

            return ['lodging' => null, 'error' => 'Error processing lodging info: ' . $e->getMessage()];
        }
    }

    /**
     * Extract lodging metadata from a URL (booking sites, hotel pages, etc.)
     *
     * @return array{lodging: array, error?: string}
     */
    public function parseUrl(string $url): array
    {
        if (empty(trim($url))) {
            return ['lodging' => null, 'error' => 'No URL provided'];
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['lodging' => null, 'error' => 'Invalid URL format'];
        }

        try {
            // Fetch the page content
            $response = Http::timeout(15)
                ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; TravelBot/1.0)',
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])
                ->get($url);

            if (!$response->successful()) {
                return ['lodging' => null, 'error' => 'Could not fetch URL (HTTP ' . $response->status() . ')'];
            }

            $html = $response->body();

            // Extract text and metadata from HTML
            $textContent = $this->extractTextFromHtml($html);
            $metadata = $this->extractMetadataFromHtml($html);

            // Combine metadata with AI parsing of content
            $aiResult = $this->parseText($textContent);

            if (isset($aiResult['lodging']) && $aiResult['lodging']) {
                // Merge with metadata we extracted directly
                $aiResult['lodging'] = array_merge($aiResult['lodging'], array_filter($metadata));
                $aiResult['lodging']['source_url'] = $url;
            }

            return $aiResult;
        } catch (\Exception $e) {
            Log::error('LodgingParser URL fetch error: ' . $e->getMessage());

            return ['lodging' => null, 'error' => 'Error fetching URL: ' . $e->getMessage()];
        }
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert at parsing hotel and accommodation booking information.
Your job is to extract structured lodging data from unstructured text such as confirmation emails, booking receipts, or property descriptions.

You should identify and extract:
- Property/hotel name
- Hotel chain (if applicable: Marriott, Hilton, Airbnb, etc.)
- Full address
- City and country
- Check-in and check-out dates
- Check-in and check-out times
- Room type
- Number of nights
- Nightly rate and total cost
- Confirmation/booking reference number
- Contact phone and email
- Any special notes or amenities mentioned

Be precise about:
- Dates (convert to YYYY-MM-DD format)
- Times (convert to HH:MM 24-hour format)
- Countries (use 2-letter ISO codes like US, GB, DE, FR)
- Currencies (USD, EUR, GBP, etc.)
PROMPT;
    }

    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
Please analyze the following hotel/accommodation booking information and extract all relevant details.

Return a JSON object with this structure:
{
  "property_name": "Hotel or property name",
  "chain": "Hotel chain if applicable (Marriott, Hilton, Airbnb, etc.)",
  "address": "Street address",
  "city": "City name",
  "country": "2-letter country code (US, GB, DE, etc.)",
  "check_in_date": "YYYY-MM-DD",
  "check_in_time": "HH:MM (24-hour format)",
  "check_out_date": "YYYY-MM-DD",
  "check_out_time": "HH:MM (24-hour format)",
  "room_type": "Room type description",
  "nights": number,
  "nightly_rate": decimal number,
  "total_cost": decimal number,
  "currency": "USD, EUR, etc. Default USD",
  "confirmation_number": "Booking reference/confirmation",
  "phone": "Property phone number",
  "email": "Property email",
  "notes": "Any other relevant details (amenities, policies, etc.)",
  "confidence": 0.0 to 1.0 (how confident you are in this extraction)
}

Important:
- Extract as much information as possible, even if some fields are missing
- If a date is mentioned without a year, assume the next occurrence of that date
- If no country is specified, try to infer from context or leave empty
- Include confidence score - lower for ambiguous extractions

BOOKING TEXT:
---
{$text}
---

Respond with ONLY valid JSON, no markdown code blocks or explanation.
PROMPT;
    }

    protected function parseAIResponse(string $content): array
    {
        // Clean up potential markdown code blocks
        $content = preg_replace('/^```json?\s*/m', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to extract JSON from the response
            if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        if (!is_array($data)) {
            return ['lodging' => null, 'error' => 'Failed to parse AI response'];
        }

        // Normalize the data
        $normalized = $this->normalizeLodging($data);

        if (!$normalized) {
            return ['lodging' => null, 'error' => 'Could not extract valid lodging information'];
        }

        return [
            'lodging' => $normalized,
            'parsing_notes' => $data['parsing_notes'] ?? null,
        ];
    }

    protected function normalizeLodging(array $data): ?array
    {
        // Validate required fields - need at least property name
        if (empty($data['property_name'])) {
            return null;
        }

        // Parse dates
        $checkInDate = $this->parseDate($data['check_in_date'] ?? null);
        $checkOutDate = $this->parseDate($data['check_out_date'] ?? null);

        // Calculate nights if not provided
        $nights = $data['nights'] ?? null;
        if (!$nights && $checkInDate && $checkOutDate) {
            try {
                $nights = \Carbon\Carbon::parse($checkInDate)->diffInDays(\Carbon\Carbon::parse($checkOutDate));
            } catch (\Exception $e) {
                $nights = null;
            }
        }

        return [
            'property_name' => trim($data['property_name']),
            'chain' => $data['chain'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => strtoupper(substr($data['country'] ?? '', 0, 2)) ?: null,
            'check_in_date' => $checkInDate,
            'check_in_time' => $this->parseTime($data['check_in_time'] ?? null),
            'check_out_date' => $checkOutDate,
            'check_out_time' => $this->parseTime($data['check_out_time'] ?? null),
            'room_type' => $data['room_type'] ?? null,
            'nights' => $nights,
            'nightly_rate' => is_numeric($data['nightly_rate'] ?? null) ? (float) $data['nightly_rate'] : null,
            'total_cost' => is_numeric($data['total_cost'] ?? null) ? (float) $data['total_cost'] : null,
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'confirmation_number' => $data['confirmation_number'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
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
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        // Strip tags and decode entities
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Limit length to avoid overly long prompts
        if (strlen($text) > 10000) {
            $text = substr($text, 0, 10000) . '...';
        }

        return $text;
    }

    protected function extractMetadataFromHtml(string $html): array
    {
        $metadata = [];

        // Extract Open Graph meta tags
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/', $html, $matches)) {
            $metadata['property_name'] = html_entity_decode($matches[1]);
        }

        // Extract schema.org JSON-LD
        if (preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            $jsonLd = json_decode($matches[1], true);
            if (is_array($jsonLd)) {
                if (isset($jsonLd['name'])) {
                    $metadata['property_name'] = $jsonLd['name'];
                }
                if (isset($jsonLd['address'])) {
                    if (is_array($jsonLd['address'])) {
                        $metadata['address'] = $jsonLd['address']['streetAddress'] ?? null;
                        $metadata['city'] = $jsonLd['address']['addressLocality'] ?? null;
                        $metadata['country'] = $jsonLd['address']['addressCountry'] ?? null;
                    } else {
                        $metadata['address'] = $jsonLd['address'];
                    }
                }
            }
        }

        // Extract title tag as fallback
        if (empty($metadata['property_name']) && preg_match('/<title>([^<]+)<\/title>/i', $html, $matches)) {
            $metadata['property_name'] = html_entity_decode(trim($matches[1]));
        }

        return $metadata;
    }
}
