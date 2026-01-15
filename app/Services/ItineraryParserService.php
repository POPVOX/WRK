<?php

namespace App\Services;

use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

class ItineraryParserService
{
    /**
     * Parse raw itinerary text and extract travel segments using AI.
     *
     * @return array{segments: array, error?: string}
     */
    public function parseText(string $text): array
    {
        if (empty(trim($text))) {
            return ['segments' => [], 'error' => 'No text provided'];
        }

        if (! config('ai.enabled', true)) {
            return ['segments' => [], 'error' => 'AI features are disabled'];
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
                'max_tokens' => 4000,
            ]);

            if (isset($response['error']) && $response['error']) {
                Log::error('ItineraryParser AI error', ['response' => $response]);

                return ['segments' => [], 'error' => 'Failed to connect to AI service'];
            }

            $content = $response['content'][0]['text'] ?? '';

            return $this->parseAIResponse($content);
        } catch (\Exception $e) {
            Log::error('ItineraryParser exception: '.$e->getMessage());

            return ['segments' => [], 'error' => 'Error processing itinerary: '.$e->getMessage()];
        }
    }

    /**
     * Parse a PDF file and extract travel segments.
     *
     * @return array{segments: array, error?: string}
     */
    public function parsePdf(string $filePath): array
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                return ['segments' => [], 'error' => 'Could not extract text from PDF'];
            }

            return $this->parseText($text);
        } catch (\Exception $e) {
            Log::error('ItineraryParser PDF error: '.$e->getMessage());

            return ['segments' => [], 'error' => 'Error reading PDF: '.$e->getMessage()];
        }
    }

    /**
     * Parse uploaded file content based on mime type.
     *
     * @return array{segments: array, error?: string}
     */
    public function parseFile(string $filePath, string $mimeType): array
    {
        if (str_contains($mimeType, 'pdf')) {
            return $this->parsePdf($filePath);
        }

        // For text files, just read and parse
        if (str_contains($mimeType, 'text') || str_contains($mimeType, 'html')) {
            $content = file_get_contents($filePath);
            // Strip HTML tags if present
            if (str_contains($mimeType, 'html')) {
                $content = strip_tags($content);
            }

            return $this->parseText($content);
        }

        return ['segments' => [], 'error' => 'Unsupported file type: '.$mimeType];
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert at parsing travel itineraries, confirmation emails, and booking documents. 
Your job is to extract structured travel segment data from unstructured text.

You should identify and extract:
- Flights (airline, flight number, departure/arrival airports, times, confirmation numbers)
- Train travel (Amtrak, etc.)
- Bus travel
- Rental cars
- Rideshares
- Ferries

Be precise about:
- Airport codes (3-letter IATA codes like DCA, LAX, NBO)
- Dates and times (convert to ISO format: YYYY-MM-DDTHH:MM)
- Carrier names and codes (United/UA, American/AA, Delta/DL, etc.)
- Flight/train numbers
- Confirmation/booking reference numbers

If timezone information is provided, include it. If not, note in the segment that timezone is assumed local.
PROMPT;
    }

    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
Please analyze the following travel itinerary text and extract all travel segments.

Return a JSON object with this structure:
{
  "segments": [
    {
      "type": "flight|train|bus|rental_car|rideshare|ferry|other_transport",
      "carrier": "Airline or company name (e.g., United Airlines)",
      "carrier_code": "Two-letter code if applicable (e.g., UA)",
      "segment_number": "Flight/train number (e.g., 234)",
      "confirmation_number": "Booking reference if found",
      "departure_location": "Airport code or city (e.g., DCA)",
      "departure_city": "Full city name (e.g., Washington DC)",
      "departure_datetime": "ISO format YYYY-MM-DDTHH:MM",
      "departure_terminal": "Terminal if mentioned",
      "arrival_location": "Airport code or city",
      "arrival_city": "Full city name",
      "arrival_datetime": "ISO format YYYY-MM-DDTHH:MM",
      "arrival_terminal": "Terminal if mentioned",
      "seat_assignment": "Seat if mentioned (e.g., 12A)",
      "cabin_class": "economy|premium_economy|business|first",
      "cost": "Cost as decimal if mentioned",
      "currency": "USD, EUR, etc. Default USD",
      "notes": "Any other relevant details",
      "confidence": 0.0 to 1.0 (how confident you are in this extraction)
    }
  ],
  "parsing_notes": "Any notes about assumptions made or ambiguities"
}

Important:
- Extract ALL segments found, even if some fields are missing
- If a date is mentioned without a year, assume the next occurrence of that date
- If times use 12-hour format, convert to 24-hour
- For multi-leg flights on a single ticket, extract each leg as a separate segment
- Include confidence scores - lower for ambiguous extractions

ITINERARY TEXT:
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

        if (! is_array($data)) {
            return ['segments' => [], 'error' => 'Failed to parse AI response'];
        }

        $segments = $data['segments'] ?? [];

        // Normalize and validate each segment
        $normalizedSegments = [];
        foreach ($segments as $segment) {
            $normalized = $this->normalizeSegment($segment);
            if ($normalized) {
                $normalizedSegments[] = $normalized;
            }
        }

        return [
            'segments' => $normalizedSegments,
            'parsing_notes' => $data['parsing_notes'] ?? null,
        ];
    }

    protected function normalizeSegment(array $segment): ?array
    {
        // Validate required fields
        if (empty($segment['departure_location']) || empty($segment['arrival_location'])) {
            return null;
        }

        // Normalize type
        $validTypes = ['flight', 'train', 'bus', 'rental_car', 'rideshare', 'ferry', 'other_transport'];
        $type = strtolower($segment['type'] ?? 'other_transport');
        if (! in_array($type, $validTypes)) {
            $type = 'other_transport';
        }

        // Normalize cabin class
        $validCabinClasses = ['economy', 'premium_economy', 'business', 'first'];
        $cabinClass = strtolower($segment['cabin_class'] ?? '');
        if (! in_array($cabinClass, $validCabinClasses)) {
            $cabinClass = null;
        }

        // Parse datetimes
        $departureDatetime = $this->parseDateTime($segment['departure_datetime'] ?? null);
        $arrivalDatetime = $this->parseDateTime($segment['arrival_datetime'] ?? null);

        // If we can't parse departure datetime, skip this segment
        if (! $departureDatetime) {
            return null;
        }

        return [
            'type' => $type,
            'carrier' => $segment['carrier'] ?? null,
            'carrier_code' => strtoupper($segment['carrier_code'] ?? ''),
            'segment_number' => $segment['segment_number'] ?? null,
            'confirmation_number' => $segment['confirmation_number'] ?? null,
            'departure_location' => strtoupper(trim($segment['departure_location'])),
            'departure_city' => $segment['departure_city'] ?? null,
            'departure_datetime' => $departureDatetime,
            'departure_terminal' => $segment['departure_terminal'] ?? null,
            'arrival_location' => strtoupper(trim($segment['arrival_location'])),
            'arrival_city' => $segment['arrival_city'] ?? null,
            'arrival_datetime' => $arrivalDatetime,
            'arrival_terminal' => $segment['arrival_terminal'] ?? null,
            'seat_assignment' => $segment['seat_assignment'] ?? null,
            'cabin_class' => $cabinClass,
            'cost' => is_numeric($segment['cost'] ?? null) ? (float) $segment['cost'] : null,
            'currency' => strtoupper($segment['currency'] ?? 'USD'),
            'notes' => $segment['notes'] ?? null,
            'confidence' => min(1, max(0, (float) ($segment['confidence'] ?? 0.8))),
        ];
    }

    protected function parseDateTime(?string $datetime): ?string
    {
        if (empty($datetime)) {
            return null;
        }

        try {
            $parsed = \Carbon\Carbon::parse($datetime);

            return $parsed->format('Y-m-d\TH:i');
        } catch (\Exception $e) {
            return null;
        }
    }
}
