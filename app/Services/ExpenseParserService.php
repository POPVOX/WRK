<?php

namespace App\Services;

use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Log;

class ExpenseParserService
{
    /**
     * Parse unstructured text (receipts, expense descriptions) using AI.
     *
     * @return array{expense: array, error?: string}
     */
    public function parseText(string $text): array
    {
        if (empty(trim($text))) {
            return ['expense' => null, 'error' => 'No text provided'];
        }

        if (!config('ai.enabled', true)) {
            return ['expense' => null, 'error' => 'AI features are disabled'];
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
                Log::error('ExpenseParser AI error', ['response' => $response]);

                return ['expense' => null, 'error' => 'Failed to connect to AI service'];
            }

            $content = $response['content'][0]['text'] ?? '';

            return $this->parseAIResponse($content);
        } catch (\Exception $e) {
            Log::error('ExpenseParser exception: ' . $e->getMessage());

            return ['expense' => null, 'error' => 'Error processing expense info: ' . $e->getMessage()];
        }
    }

    /**
     * Extract text from uploaded receipt image/PDF (placeholder for OCR).
     */
    public function extractFromReceipt(string $filePath): array
    {
        // For now, we'll try to extract text if it's a PDF
        if (str_ends_with(strtolower($filePath), '.pdf')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();

                if (!empty(trim($text))) {
                    return $this->parseText($text);
                }
            } catch (\Exception $e) {
                Log::warning('PDF text extraction failed', ['error' => $e->getMessage()]);
            }
        }

        // For images, we'd need OCR integration (future enhancement)
        return ['expense' => null, 'error' => 'Could not extract text from receipt. Please enter details manually.'];
    }

    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert at parsing expense and receipt information. You should identify:
- Expense category (airfare, lodging, ground_transport, meals, registration_fees, baggage_fees, wifi_connectivity, tips_gratuities, visa_fees, travel_insurance, office_supplies, other)
- Description of the expense
- Date of the expense
- Amount and currency
- Vendor/merchant name
- Receipt/confirmation number if available

Be precise about amounts - include the correct decimal places.
Convert dates to YYYY-MM-DD format.
PROMPT;
    }

    protected function buildPrompt(string $text): string
    {
        return <<<PROMPT
Please analyze the following expense/receipt information and extract all relevant details.

Return a JSON object with this structure:
{
  "category": "airfare|lodging|ground_transport|meals|registration_fees|baggage_fees|wifi_connectivity|tips_gratuities|visa_fees|travel_insurance|office_supplies|other",
  "description": "Brief description of the expense",
  "expense_date": "YYYY-MM-DD",
  "amount": 123.45,
  "currency": "USD|GBP|EUR|etc",
  "vendor": "Vendor/merchant name",
  "receipt_number": "Receipt or confirmation number if available",
  "notes": "Any additional relevant details",
  "confidence": 0.0 to 1.0 (how confident you are in this extraction)
}

Important:
- Extract the exact amount with correct decimal places
- Identify the correct category based on the expense type
- If currency is not specified, assume USD
- If date is ambiguous, use the most recent likely date

EXPENSE TEXT:
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
            return ['expense' => null, 'error' => 'Failed to parse AI response'];
        }

        $normalized = $this->normalizeExpense($data);

        if (!$normalized) {
            return ['expense' => null, 'error' => 'Could not extract valid expense information'];
        }

        return ['expense' => $normalized];
    }

    protected function normalizeExpense(array $data): ?array
    {
        // Validate category
        $validCategories = [
            'airfare',
            'lodging',
            'ground_transport',
            'meals',
            'registration_fees',
            'baggage_fees',
            'wifi_connectivity',
            'tips_gratuities',
            'visa_fees',
            'travel_insurance',
            'office_supplies',
            'other',
        ];
        $category = strtolower($data['category'] ?? 'other');
        if (!in_array($category, $validCategories)) {
            $category = 'other';
        }

        // Parse date
        $expenseDate = $this->parseDate($data['expense_date'] ?? null);

        // Parse amount
        $amount = $data['amount'] ?? null;
        if (is_string($amount)) {
            $amount = (float) preg_replace('/[^0-9.]/', '', $amount);
        }

        return [
            'category' => $category,
            'description' => $data['description'] ?? '',
            'expense_date' => $expenseDate,
            'amount' => $amount,
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'vendor' => $data['vendor'] ?? null,
            'receipt_number' => $data['receipt_number'] ?? null,
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
}
