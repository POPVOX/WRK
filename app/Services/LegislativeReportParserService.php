<?php

namespace App\Services;

use App\Services\AnthropicClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LegislativeReportParserService
{
    protected AnthropicClient $anthropic;

    public function __construct()
    {
        $this->anthropic = new AnthropicClient();
    }

    public function parseReportPDF(string $filePath): array
    {
        // Extract text from PDF
        $text = $this->extractTextFromPDF($filePath);

        if (empty(trim($text))) {
            throw new \Exception('Could not extract text from PDF');
        }

        // Send to Claude for structured extraction
        $prompt = $this->buildExtractionPrompt($text);

        try {
            $response = $this->anthropic->sendMessage($prompt, 'claude-sonnet-4-20250514');
            return $this->parseClaudeResponse($response);
        } catch (\Exception $e) {
            Log::error('Legislative report parsing failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function buildExtractionPrompt(string $reportText): string
    {
        // Truncate if too long (keeping first ~80k chars for context)
        if (strlen($reportText) > 80000) {
            $reportText = substr($reportText, 0, 80000) . "\n\n[TRUNCATED - Report continues...]";
        }

        return <<<PROMPT
You are analyzing a Congressional appropriations report to extract ALL reporting requirements.

Please identify and extract EVERY instance where an agency is required to submit a report, briefing, or information. Include:

1. NEW reporting requirements with specific timelines (30, 60, 90, 120, 180 days, etc.)
2. References to reports mandated in PREVIOUS appropriations that are still expected
3. ONGOING/RECURRING requirements (quarterly, annual, etc.)

For each requirement found, provide:
- Report title/subject
- Responsible agency (e.g., Architect of the Capitol, USCP, CAO, GAO, CRS, Library of Congress, GPO)
- Timeline (exact wording: "within 30 days", "not later than 120 days", etc.)
- Description of what's required
- Reporting recipients (which committees)
- Page reference where found
- Category: "new", "prior_year", or "ongoing"

Return your findings in this JSON structure:
```json
{
  "requirements": [
    {
      "title": "Annual Budget Justification",
      "agency": "Architect of the Capitol",
      "timeline": "within 30 days of enactment",
      "timeline_type": "days_from_enactment",
      "timeline_value": 30,
      "description": "Detailed description...",
      "recipients": "House and Senate Committees on Appropriations",
      "page_reference": "p. 15",
      "category": "new"
    }
  ]
}
```

IMPORTANT: Only return valid JSON. Make sure timeline_type is one of: "days_from_enactment", "days_from_report", "quarterly", "annual", or "specific_date".

Report text:
{$reportText}
PROMPT;
    }

    protected function extractTextFromPDF(string $filePath): string
    {
        // Check if smalot/pdfparser is available
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
            } catch (\Exception $e) {
                Log::warning('PDF Parser failed, trying pdftotext', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to pdftotext command if available
        if ($this->commandExists('pdftotext')) {
            $output = [];
            $returnCode = 0;
            exec("pdftotext -layout " . escapeshellarg($filePath) . " -", $output, $returnCode);
            
            if ($returnCode === 0) {
                return implode("\n", $output);
            }
        }

        throw new \Exception('No PDF parser available. Install smalot/pdfparser or pdftotext.');
    }

    protected function commandExists(string $command): bool
    {
        $result = shell_exec("which " . escapeshellarg($command));
        return !empty(trim($result ?? ''));
    }

    protected function parseClaudeResponse(string $response): array
    {
        // Extract JSON from Claude's response
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Try to find JSON object in response
        if (preg_match('/\{[\s\S]*"requirements"[\s\S]*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Fallback: try to parse entire response as JSON
        $decoded = json_decode($response, true);
        if ($decoded !== null) {
            return $decoded;
        }

        Log::warning('Could not parse Claude response as JSON', ['response' => substr($response, 0, 500)]);
        return ['requirements' => []];
    }

    /**
     * Parse requirements from manual text input (not PDF).
     */
    public function parseFromText(string $text): array
    {
        $prompt = $this->buildExtractionPrompt($text);

        try {
            $response = $this->anthropic->sendMessage($prompt, 'claude-sonnet-4-20250514');
            return $this->parseClaudeResponse($response);
        } catch (\Exception $e) {
            Log::error('Legislative text parsing failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

