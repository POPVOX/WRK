<?php

namespace App\Jobs;

use App\Models\Grant;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateGrantReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $grantId,
        public string $reportType = 'progress'
    ) {
    }

    public function handle(): void
    {
        $grant = Grant::with(['funder', 'projects', 'reportingRequirements'])->find($this->grantId);
        if (!$grant) {
            return;
        }

        $context = $this->buildContext($grant);
        $prompt = $this->buildPrompt($grant, $context);

        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            Log::warning('GenerateGrantReport: No Anthropic API key configured. Set ANTHROPIC_API_KEY in .env');
            $this->saveReport($grant->id, "AI features are disabled. Please configure the Anthropic API key.");
            return;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(180)->post('https://api.anthropic.com/v1/messages', [
                        'model' => config('ai.model', 'claude-sonnet-4-20250514'),
                        'max_tokens' => 8000,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);

            if ($response->successful()) {
                $report = $response->json('content.0.text', '');
                $this->saveReport($grant->id, $report);
                Log::info("GenerateGrantReport: Successfully generated report for grant {$grant->id}");
            } else {
                Log::error('GenerateGrantReport: API error - ' . $response->body());
                $this->saveReport($grant->id, "Error generating report. Please try again.");
            }
        } catch (\Exception $e) {
            Log::error('GenerateGrantReport: ' . $e->getMessage());
            $this->saveReport($grant->id, "Error: " . $e->getMessage());
        }
    }

    protected function buildContext(Grant $grant): array
    {
        $context = [
            'grant' => [
                'name' => $grant->name,
                'funder' => $grant->funder?->name ?? 'Unknown',
                'amount' => $grant->amount,
                'start_date' => $grant->start_date?->format('Y-m-d'),
                'end_date' => $grant->end_date?->format('Y-m-d'),
                'description' => $grant->description,
                'deliverables' => $grant->deliverables,
            ],
            'projects' => [],
            'meetings' => [],
            'documents' => [],
        ];

        // Gather project data
        foreach ($grant->projects as $project) {
            $projectData = [
                'name' => $project->name,
                'status' => $project->status,
                'description' => $project->description,
                'milestones' => [],
                'decisions' => [],
            ];

            // Get milestones
            foreach ($project->milestones as $milestone) {
                $projectData['milestones'][] = [
                    'name' => $milestone->name,
                    'status' => $milestone->status,
                    'due_date' => $milestone->due_date?->format('Y-m-d'),
                ];
            }

            // Get recent decisions
            foreach ($project->decisions->take(5) as $decision) {
                $projectData['decisions'][] = [
                    'title' => $decision->title,
                    'rationale' => $decision->rationale,
                    'date' => $decision->decision_date?->format('Y-m-d'),
                ];
            }

            $context['projects'][] = $projectData;

            // Gather meetings linked to projects
            foreach ($project->meetings->take(10) as $meeting) {
                $context['meetings'][] = [
                    'title' => $meeting->title,
                    'date' => $meeting->meeting_date?->format('Y-m-d'),
                    'summary' => $meeting->ai_summary ?? substr($meeting->raw_notes ?? '', 0, 500),
                ];
            }
        }

        // Get knowledge base documents
        $docs = ProjectDocument::where('is_knowledge_base', true)
            ->whereIn('project_id', $grant->projects->pluck('id'))
            ->take(10)
            ->get();

        foreach ($docs as $doc) {
            $context['documents'][] = [
                'title' => $doc->title,
                'summary' => $doc->ai_summary ?? null,
                'type' => $doc->type,
            ];
        }

        return $context;
    }

    protected function buildPrompt(Grant $grant, array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT);

        $reportTypeInstructions = match ($this->reportType) {
            'progress' => 'Create a detailed PROGRESS REPORT covering activities, milestones achieved, challenges, and next steps.',
            'narrative' => 'Create a NARRATIVE REPORT telling the story of the work, highlighting impact and key accomplishments.',
            'financial' => 'Create a FINANCIAL SUMMARY REPORT outline (note: specific numbers would need to come from financial systems).',
            'impact' => 'Create an IMPACT ASSESSMENT highlighting outcomes, beneficiaries, and measurable results.',
            default => 'Create a comprehensive PROGRESS REPORT.',
        };

        return <<<PROMPT
You are writing a grant report for "{$grant->name}" funded by "{$grant->funder?->name}".

{$reportTypeInstructions}

Use the following context from the organization's project management system to write the report:

{$contextJson}

FORMATTING REQUIREMENTS:
- Use markdown formatting
- Include clear headers and sections
- Be professional and concise
- Highlight achievements and progress
- Be honest about challenges
- Include specific examples and data points where available
- End with next steps or future plans

Generate the complete report now:
PROMPT;
    }

    protected function saveReport(int $grantId, string $content): void
    {
        $path = "grant_reports/{$grantId}-{$this->reportType}.md";
        Storage::disk('local')->put($path, $content);
    }
}
