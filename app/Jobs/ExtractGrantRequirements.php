<?php

namespace App\Jobs;

use App\Models\GrantDocument;
use App\Models\ReportingRequirement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtractGrantRequirements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $documentId
    ) {}

    public function handle(): void
    {
        $document = GrantDocument::with('grant')->find($this->documentId);
        if (! $document || ! $document->grant) {
            return;
        }

        // Read the file content
        $fullPath = storage_path('app/public/'.$document->file_path);
        if (! file_exists($fullPath)) {
            Log::warning("ExtractGrantRequirements: File not found at {$fullPath}");

            return;
        }

        $content = $this->extractTextFromFile($fullPath, $document->file_type);
        if (empty($content)) {
            Log::warning("ExtractGrantRequirements: Could not extract text from {$document->title}");

            return;
        }

        // Send to AI for extraction
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            Log::warning('ExtractGrantRequirements: No Anthropic API key configured. Set ANTHROPIC_API_KEY in .env');

            return;
        }

        $prompt = $this->buildPrompt($content, $document->grant->name);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('ai.model', 'claude-sonnet-4-20250514'),
                'max_tokens' => 4000,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json('content.0.text', '');
                $extracted = $this->parseExtraction($result);

                // Update document with extracted data
                $document->update([
                    'ai_extracted_data' => $extracted,
                    'ai_processed' => true,
                    'ai_summary' => $extracted['summary'] ?? null,
                ]);

                // Create reporting requirements from extraction
                $this->createRequirements($document->grant_id, $document->id, $extracted['requirements'] ?? []);

                Log::info("ExtractGrantRequirements: Successfully processed document {$document->id}");
            } else {
                Log::error('ExtractGrantRequirements: API error - '.$response->body());
            }
        } catch (\Exception $e) {
            Log::error('ExtractGrantRequirements: '.$e->getMessage());
        }
    }

    protected function extractTextFromFile(string $path, ?string $type): string
    {
        $ext = strtolower($type ?? pathinfo($path, PATHINFO_EXTENSION));

        try {
            // Handle text-based files directly
            if (in_array($ext, ['txt', 'md'])) {
                $content = file_get_contents($path) ?: '';

                return $this->cleanUtf8($content);
            }

            // Handle PDF files with PdfParser
            if ($ext === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser;
                $pdf = $parser->parseFile($path);
                $text = $pdf->getText();

                return $this->cleanUtf8($text);
            }

            // Handle DOCX files (basic extraction via ZipArchive)
            if ($ext === 'docx') {
                $zip = new \ZipArchive;
                if ($zip->open($path) === true) {
                    $content = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($content) {
                        // Strip XML tags to get plain text
                        $text = strip_tags($content);

                        return $this->cleanUtf8($text);
                    }
                }
            }

            // DOC files - try to extract readable text
            if ($ext === 'doc') {
                $content = file_get_contents($path) ?: '';
                // Extract ASCII text from DOC binary
                $text = preg_replace('/[^\x20-\x7E\n\r\t]/', ' ', $content);

                return $this->cleanUtf8($text);
            }

        } catch (\Exception $e) {
            Log::warning('ExtractGrantRequirements: Error extracting text from file: '.$e->getMessage());

            return '';
        }

        return '';
    }

    protected function cleanUtf8(string $text): string
    {
        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Remove null bytes and other control characters (except newlines/tabs)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Collapse multiple spaces/newlines
        $text = preg_replace('/\s+/', ' ', $text);

        // Limit content length for API (avoid token limits)
        if (strlen($text) > 50000) {
            $text = substr($text, 0, 50000)."\n\n[Content truncated for processing...]";
        }

        return trim($text);
    }

    protected function buildPrompt(string $content, string $grantName): string
    {
        return <<<PROMPT
You are analyzing a grant document for "{$grantName}". Extract comprehensive insights in JSON format:

## FUNDER INSIGHTS (Understanding the Funder)

1. **summary**: A 2-3 sentence overview of what this grant/funder is about and their key focus areas.

2. **funder_priorities**: An array of the funder's priorities, interests, and what they care about:
   - priority: The priority or interest area
   - importance: high, medium, or low
   - notes: Any specific context

3. **funder_approach**: Brief description of the funder's approach, philosophy, or style (e.g., "hands-on partner", "minimal reporting", "impact-focused", "data-driven")

4. **funder_values**: Key values or themes the funder emphasizes (e.g., "equity", "innovation", "collaboration", "sustainability")

## OPERATIONAL REQUIREMENTS (What We Need to Do)

5. **goals**: An array of project goals/objectives:
   - goal: The goal statement
   - priority: high, medium, or low

6. **milestones**: Key milestones and deliverables:
   - name: Name of milestone or deliverable
   - description: Brief description
   - due_description: When it's due (e.g., "Q1 2024", "6 months after start")
   
7. **requirements**: Reporting requirements (ONLY include if explicitly mentioned in the document):
   - name: Name of the requirement
   - type: One of [progress_report, financial_report, narrative_report, final_report, impact_assessment, other]
   - frequency: How often (monthly, quarterly, annually, one-time)
   - due_description: When it's due (e.g., "30 days after period end", "April 30, 2026")
   - source_quote: A brief quote from the document that mentions this requirement (for verification)

8. **key_dates**: Important dates (start, end, renewals, deadlines, etc.):
   - event: What the date is for
   - date_description: The date or timing

9. **budget_highlights**: Budget information (award amount, indirect rate, matching requirements, etc.)

10. **restrictions**: Any restrictions, limitations, or things NOT allowed (spending restrictions, activity limitations, etc.)

11. **compliance_notes**: Administrative or compliance requirements to be aware of

Return ONLY valid JSON, no other text.

DOCUMENT CONTENT:
{$content}
PROMPT;
    }

    protected function parseExtraction(string $aiResponse): array
    {
        // Extract JSON from response
        $json = trim($aiResponse);
        if (str_starts_with($json, '```json')) {
            $json = trim(substr($json, 7));
        }
        if (str_starts_with($json, '```')) {
            $json = trim(substr($json, 3));
        }
        if (str_ends_with($json, '```')) {
            $json = trim(substr($json, 0, -3));
        }

        $decoded = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return ['raw_response' => $aiResponse];
    }

    protected function createRequirements(int $grantId, int $documentId, array $requirements): void
    {
        foreach ($requirements as $req) {
            if (empty($req['name'])) {
                continue;
            }

            // Check if already exists
            $exists = ReportingRequirement::where('grant_id', $grantId)
                ->where('name', $req['name'])
                ->exists();

            if (! $exists) {
                // Parse due date from description if possible, otherwise default to 3 months
                $dueDate = now()->addMonths(3);
                if (! empty($req['due_description'])) {
                    try {
                        $parsed = \Carbon\Carbon::parse($req['due_description']);
                        if ($parsed->isFuture()) {
                            $dueDate = $parsed;
                        }
                    } catch (\Exception $e) {
                        // Keep default
                    }
                }

                ReportingRequirement::create([
                    'grant_id' => $grantId,
                    'source_document_id' => $documentId,
                    'name' => $req['name'],
                    'type' => $req['type'] ?? 'other',
                    'status' => 'pending',
                    'due_date' => $dueDate,
                    'format_requirements' => $req['due_description'] ?? null,
                    'notes' => isset($req['frequency']) ? "Frequency: {$req['frequency']}" : null,
                    'source_quote' => $req['source_quote'] ?? null,
                ]);
            }
        }
    }
}
