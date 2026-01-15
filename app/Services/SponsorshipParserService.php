<?php

namespace App\Services;

use App\Models\TripSponsorship;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Log;

class SponsorshipParserService
{
    /**
     * Parse sponsorship agreement text and extract terms
     */
    public function parseAgreement(string $text, ?TripSponsorship $sponsorship = null): array
    {
        if (!config('ai.enabled', true)) {
            return [
                'success' => false,
                'error' => 'AI features are disabled',
            ];
        }

        try {
            $response = AnthropicClient::send([
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($text),
                    ],
                ],
                'max_tokens' => 4000,
            ]);

            if (isset($response['error']) && $response['error']) {
                Log::error('SponsorshipParser AI error', ['response' => $response]);

                return [
                    'success' => false,
                    'error' => 'Failed to connect to AI service',
                ];
            }

            $content = $response['content'][0]['text'] ?? '';

            // Parse JSON from response
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $jsonString = $matches[1];
            } else {
                $jsonString = $content;
            }

            $parsed = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse sponsorship extraction JSON', [
                    'error' => json_last_error_msg(),
                    'content' => $content,
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to parse AI response',
                ];
            }

            return [
                'success' => true,
                'data' => $parsed,
            ];
        } catch (\Exception $e) {
            Log::error('Sponsorship parsing failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Apply parsed terms to a sponsorship record
     */
    public function applyToSponsorship(TripSponsorship $sponsorship, array $parsedData): void
    {
        $data = $parsedData['data'] ?? $parsedData;

        // Store extracted terms
        $sponsorship->extracted_terms = $data;
        $sponsorship->terms_extracted_at = now();

        // Apply line items
        if (!empty($data['line_items'])) {
            $sponsorship->line_items = $data['line_items'];
        }

        // Apply financial totals
        if (isset($data['totals'])) {
            $totals = $data['totals'];
            $sponsorship->total_consulting_fees = $totals['consulting_fees'] ?? null;
            $sponsorship->total_reimbursable = $totals['reimbursable'] ?? null;

            if (!empty($totals['total']) && empty($sponsorship->amount)) {
                $sponsorship->amount = $totals['total'];
            }

            if (!empty($totals['currency']) && empty($sponsorship->currency)) {
                $sponsorship->currency = $totals['currency'];
            }
        }

        // Apply exchange rate
        if (!empty($data['exchange_rate'])) {
            $sponsorship->exchange_rate_note = $data['exchange_rate'];
        }

        // Apply payment terms
        if (!empty($data['payment_terms'])) {
            $sponsorship->payment_terms = $data['payment_terms'];
        }

        // Apply deliverables
        if (!empty($data['deliverables'])) {
            $deliverables = array_map(function ($d) {
                return [
                    'description' => is_string($d) ? $d : ($d['description'] ?? $d),
                    'is_completed' => false,
                    'completed_at' => null,
                    'notes' => null,
                ];
            }, $data['deliverables']);
            $sponsorship->deliverables = $deliverables;
        }

        // Apply coverage flags
        if (!empty($data['covers'])) {
            $covers = $data['covers'];
            $sponsorship->covers_airfare = $covers['airfare'] ?? false;
            $sponsorship->covers_lodging = $covers['lodging'] ?? false;
            $sponsorship->covers_ground_transport = $covers['ground_transport'] ?? false;
            $sponsorship->covers_meals = $covers['meals'] ?? $covers['subsistence'] ?? false;
            $sponsorship->covers_registration = $covers['registration'] ?? false;
        }

        // Apply covered travelers
        if (!empty($data['covered_travelers'])) {
            $sponsorship->covered_travelers = $data['covered_travelers'];
        }

        // Apply billing info if available
        if (!empty($data['billing'])) {
            $billing = $data['billing'];
            if (!empty($billing['contact_name'])) {
                $sponsorship->billing_contact_name = $billing['contact_name'];
            }
            if (!empty($billing['contact_email'])) {
                $sponsorship->billing_contact_email = $billing['contact_email'];
            }
            if (!empty($billing['address'])) {
                $sponsorship->billing_address = $billing['address'];
            }
            if (!empty($billing['instructions'])) {
                $sponsorship->billing_instructions = $billing['instructions'];
            }
        }

        // Apply notes/summary
        if (!empty($data['summary'])) {
            $sponsorship->coverage_notes = $data['summary'];
        }

        $sponsorship->save();
    }

    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
Analyze this sponsorship/reimbursement agreement and extract structured information.

AGREEMENT TEXT:
---
{$text}
---

Extract and return a JSON object with the following structure:

```json
{
  "sponsor_organization": "Name of sponsoring organization",
  "summary": "Brief 1-2 sentence summary of the sponsorship",
  
  "line_items": [
    {
      "description": "Description of the line item",
      "category": "consulting|travel|accommodation|meals|registration|other",
      "amount": 1234.56,
      "currency": "USD|GBP|EUR|etc",
      "rate": "Rate if specified (e.g., $480/day)",
      "quantity": "Quantity if applicable (e.g., 4 days)",
      "is_reimbursable": true,
      "notes": "Any special notes about this item"
    }
  ],
  
  "totals": {
    "consulting_fees": 3120.00,
    "reimbursable": 2156.47,
    "total": 5276.47,
    "currency": "GBP"
  },
  
  "exchange_rate": "Exchange rate info if mentioned (e.g., $1 = £0.75)",
  
  "covers": {
    "airfare": true,
    "lodging": true,
    "ground_transport": false,
    "meals": true,
    "registration": false,
    "subsistence": true
  },
  
  "covered_travelers": ["Dr Marci Harris", "Other Name"],
  
  "payment_terms": "Net 30 from valid invoice",
  
  "deliverables": [
    "Complete certified course preparation and on-site support",
    "WFD PolTech Workshop participation",
    "Report with recommendations"
  ],
  
  "billing": {
    "contact_name": "Contact name if available",
    "contact_email": "email if available",
    "address": "Billing address if provided",
    "instructions": "Any special invoicing instructions"
  },
  
  "important_notes": [
    "Any important conditions or caveats",
    "Deadlines or timing requirements"
  ]
}
```

Important:
- Extract ALL line items mentioned, both fees and reimbursables
- Identify the currency correctly (GBP £, USD $, EUR €)
- List all deliverables required before payment can be collected
- Note any exchange rate conversions mentioned
- If accommodation is shared (e.g., "1/6 of team AirBnB"), note this in the line item
- Include per diem/subsistence rates if mentioned
- Identify which travelers are covered by name if mentioned

Return ONLY the JSON object, no other text.
PROMPT;
    }

    /**
     * Extract text from uploaded PDF
     */
    public function extractTextFromPdf(string $filePath): ?string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);

            return $pdf->getText();
        } catch (\Exception $e) {
            Log::error('PDF text extraction failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
