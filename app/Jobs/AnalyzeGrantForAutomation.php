<?php

namespace App\Jobs;

use App\Models\Grant;
use App\Models\GrantReportingSchema;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeGrantForAutomation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $grantId,
        public ?int $createdBy = null
    ) {}

    public function handle(): ?GrantReportingSchema
    {
        $grant = Grant::with(['documents', 'reportingRequirements'])->find($this->grantId);
        if (! $grant) {
            Log::warning("AnalyzeGrantForAutomation: Grant ID {$this->grantId} not found.");

            return null;
        }

        // Check if already has a schema
        if ($grant->activeReportingSchema()->exists()) {
            Log::info("AnalyzeGrantForAutomation: Grant ID {$this->grantId} already has active schema.");

            return null;
        }

        // Gather extracted requirements from documents
        $extractedData = $this->gatherExtractedData($grant);

        if (empty($extractedData)) {
            Log::info("AnalyzeGrantForAutomation: No extracted data for Grant ID {$this->grantId}.");

            return null;
        }

        // Use AI to suggest a reporting schema
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            Log::warning('AnalyzeGrantForAutomation: No Anthropic API key configured.');

            return null;
        }

        $prompt = $this->buildPrompt($grant, $extractedData);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('ai.model', 'claude-sonnet-4-20250514'),
                'max_tokens' => 8000,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json('content.0.text', '');
                $schemaData = $this->parseSchemaResponse($result);

                if (empty($schemaData) || isset($schemaData['error'])) {
                    Log::error("AnalyzeGrantForAutomation: Failed to parse AI response for Grant ID {$this->grantId}");

                    return null;
                }

                // Create the draft schema
                $schema = GrantReportingSchema::create([
                    'grant_id' => $this->grantId,
                    'version' => 1,
                    'status' => 'draft',
                    'schema_data' => $schemaData,
                    'created_by' => $this->createdBy,
                ]);

                Log::info("AnalyzeGrantForAutomation: Created draft schema for Grant ID {$this->grantId}");

                return $schema;
            } else {
                Log::error('AnalyzeGrantForAutomation: API error - '.$response->body());

                return null;
            }
        } catch (\Exception $e) {
            Log::error('AnalyzeGrantForAutomation: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Gather extracted data from grant documents and requirements.
     */
    protected function gatherExtractedData(Grant $grant): array
    {
        $data = [
            'grant_name' => $grant->name,
            'funder' => $grant->funder?->name,
            'description' => $grant->description,
            'deliverables' => $grant->deliverables,
            'start_date' => $grant->start_date?->format('Y-m-d'),
            'end_date' => $grant->end_date?->format('Y-m-d'),
            'amount' => $grant->amount,
            'extracted_requirements' => [],
            'document_insights' => [],
        ];

        // Gather existing reporting requirements
        foreach ($grant->reportingRequirements as $req) {
            $data['extracted_requirements'][] = [
                'name' => $req->name,
                'type' => $req->type,
                'due_date' => $req->due_date?->format('Y-m-d'),
                'notes' => $req->notes,
                'source_quote' => $req->source_quote,
            ];
        }

        // Gather AI insights from documents
        foreach ($grant->documents as $doc) {
            if ($doc->ai_processed && $doc->ai_extracted_data) {
                $data['document_insights'][] = [
                    'document_title' => $doc->title,
                    'document_type' => $doc->type,
                    'insights' => $doc->ai_extracted_data,
                ];
            }
        }

        return $data;
    }

    /**
     * Build the AI prompt for schema generation.
     */
    protected function buildPrompt(Grant $grant, array $extractedData): string
    {
        $dataJson = json_encode($extractedData, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are helping a nonprofit organization set up automated reporting for a grant.
Analyze the grant requirements and suggest a comprehensive reporting schema that can 
be automated from their organizational data.

## Grant Information
{$dataJson}

## Available Data Sources in WRK (their project management system)
- **Meetings**: Can filter by date, participants, tags, external organization count, grant associations
  - Tags like: "convening", "briefing", "government_officials", "cross-ideological"
- **Documents**: Can filter by type, tags, authors, date, grant associations
  - Types: policy_brief, testimony, model_legislation, report, analysis, factsheet, publication, blog_post, op_ed
  - Tags like: "bipartisan", "stakeholder_feedback", "public_comment"
- **Contacts**: Can filter by contact type (government_official, funder, grantee, partner, stakeholder), political affiliation
- **Projects**: Can filter by status, tags, milestone completion, grant associations

## Your Task
Create a JSON reporting schema with the following structure:
1. Organize by pathways/themes if the grant has them, otherwise use a single "General" pathway
2. Group metrics into outcomes within pathways
3. For each metric, determine if it can be auto-calculated or needs manual entry
4. Suggest specific tags needed for filtering
5. Be practical - only suggest automation for things that make sense

## Output JSON Schema Structure
```json
{
  "version": "1.0",
  "reporting_periods": "quarterly", // or "monthly", "annual"
  "pathways": [
    {
      "id": "pathway_1",
      "name": "Pathway Name",
      "outcomes": [
        {
          "id": "outcome_1",
          "name": "Outcome Name",
          "timeframe": "MT", // ST (short-term), MT (medium-term), LT (long-term)
          "description": "Brief description",
          "metrics": [
            {
              "id": "metric_id",
              "name": "Metric display name",
              "type": "count", // count, narrative, percentage
              "calculation": "auto", // auto or manual
              "data_source": "meetings", // meetings, documents, contacts, projects
              "filters": {
                "required_tags": ["tag1", "tag2"],
                "document_type": "policy_brief",
                "grant_associations": ["current_grant"]
              },
              "target": 3, // optional target number
              "target_period": "quarterly"
            },
            {
              "id": "quality_assessment",
              "name": "Quality Assessment",
              "type": "narrative",
              "calculation": "manual",
              "prompt": "Describe the quality and impact...",
              "data_source_description": "Grantee assessment"
            }
          ]
        }
      ]
    }
  ],
  "tags_config": [
    {
      "tag_name": "convening",
      "applies_to": ["meetings"],
      "description": "Multi-stakeholder convenings",
      "metrics_used_in": ["outcome_1.convenings"]
    }
  ]
}
```

Generate the complete JSON schema now. Only output valid JSON, no other text.
PROMPT;
    }

    /**
     * Parse the AI response into a schema array.
     */
    protected function parseSchemaResponse(string $response): array
    {
        $json = trim($response);

        // Remove markdown code blocks if present
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

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AnalyzeGrantForAutomation: JSON parse error - '.json_last_error_msg());

            return ['error' => 'Failed to parse AI response'];
        }

        return $decoded;
    }
}

