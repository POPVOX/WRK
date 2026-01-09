<?php

namespace App\Livewire\Grants;

use App\Jobs\AnalyzeGrantForAutomation;
use App\Jobs\CalculateGrantMetrics;
use App\Models\Grant;
use App\Models\GrantReportingSchema;
use App\Models\Meeting;
use App\Models\MetricCalculation;
use App\Models\ProjectDocument;
use App\Models\SchemaChatbotConversation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AutomatedReports extends Component
{
    public Grant $grant;

    public string $activeSubTab = 'dashboard'; // dashboard, tag, generate, refine

    // Reporting period
    public string $periodStart;

    public string $periodEnd;

    public string $periodLabel = '';

    // Metrics data
    public array $metricsData = [];

    public bool $isCalculating = false;

    // Chatbot
    public bool $showChatbot = false;

    public string $chatMessage = '';

    public array $chatHistory = [];

    public bool $isChatProcessing = false;

    public ?SchemaChatbotConversation $activeConversation = null;

    // Schema
    public ?GrantReportingSchema $schema = null;

    public array $schemaPreview = [];

    // Tagging
    public array $untaggedItems = [];

    public int $untaggedCount = 0;

    // Report Generation
    public bool $showManualEntryModal = false;

    public array $manualEntries = [];

    public ?string $generatedReport = null;

    public bool $isGeneratingReport = false;

    public function mount(Grant $grant): void
    {
        $this->grant = $grant->load(['activeReportingSchema', 'draftReportingSchema']);
        $this->schema = $this->grant->activeReportingSchema ?? $this->grant->draftReportingSchema;

        // Default to current quarter
        $this->setCurrentQuarter();

        if ($this->schema) {
            $this->loadMetrics();
            $this->loadUntaggedItems();
            $this->schemaPreview = $this->schema->schema_data ?? [];
        }
    }

    protected function setCurrentQuarter(): void
    {
        $now = now();
        $quarter = ceil($now->month / 3);
        $year = $now->year;

        $this->periodStart = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->format('Y-m-d');
        $this->periodEnd = Carbon::create($year, $quarter * 3, 1)->endOfMonth()->format('Y-m-d');
        $this->periodLabel = "Q{$quarter} {$year}";
    }

    public function setPeriod(string $period): void
    {
        $now = now();

        switch ($period) {
            case 'this_quarter':
                $this->setCurrentQuarter();
                break;
            case 'last_quarter':
                $quarter = ceil($now->month / 3) - 1;
                $year = $now->year;
                if ($quarter < 1) {
                    $quarter = 4;
                    $year--;
                }
                $this->periodStart = Carbon::create($year, ($quarter - 1) * 3 + 1, 1)->format('Y-m-d');
                $this->periodEnd = Carbon::create($year, $quarter * 3, 1)->endOfMonth()->format('Y-m-d');
                $this->periodLabel = "Q{$quarter} {$year}";
                break;
            case 'this_year':
                $this->periodStart = Carbon::create($now->year, 1, 1)->format('Y-m-d');
                $this->periodEnd = Carbon::create($now->year, 12, 31)->format('Y-m-d');
                $this->periodLabel = (string) $now->year;
                break;
        }

        $this->loadMetrics();
    }

    public function setSubTab(string $tab): void
    {
        $this->activeSubTab = $tab;
    }

    // === Schema Setup ===

    public function startSetup(): void
    {
        // Start chatbot conversation immediately
        $this->showChatbot = true;
        $this->activeConversation = SchemaChatbotConversation::create([
            'grant_id' => $this->grant->id,
            'conversation_type' => 'setup',
            'messages' => [],
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        // Add initial AI message
        $this->chatHistory = [];
        $this->addSystemMessage("I'm analyzing your grant documents to suggest an automated reporting structure. This may take a moment...");

        // Try to analyze and suggest schema (with error handling)
        try {
            $this->analyzeAndSuggestSchema();
        } catch (\Exception $e) {
            Log::error('AutomatedReports startSetup error: '.$e->getMessage());
            $this->addSystemMessage(
                "I encountered an issue analyzing the documents automatically, but we can still set up your reporting schema together!\n\n".
                "Tell me about your grant's reporting requirements:\n".
                "- What metrics do you need to track?\n".
                "- How often do you report (quarterly, annually)?\n".
                "- What outcomes or goals are you measuring?"
            );
        }
    }

    public function openRefineChat(): void
    {
        $this->showChatbot = true;
        $this->activeSubTab = 'refine';

        // Find or create refinement conversation
        $this->activeConversation = SchemaChatbotConversation::where('grant_id', $this->grant->id)
            ->where('conversation_type', 'refinement')
            ->where('status', 'active')
            ->first();

        if (! $this->activeConversation) {
            $this->activeConversation = SchemaChatbotConversation::create([
                'grant_id' => $this->grant->id,
                'schema_id' => $this->schema?->id,
                'conversation_type' => 'refinement',
                'messages' => [],
                'status' => 'active',
                'created_by' => Auth::id(),
            ]);

            $this->chatHistory = [];
            $this->addSystemMessage("I'm ready to help you refine the reporting schema. You can:\n- Add new metrics\n- Modify how existing metrics are calculated\n- Change targets\n- Add or remove tags\n\nWhat would you like to adjust?");
        } else {
            $this->chatHistory = $this->activeConversation->messages ?? [];
        }
    }

    public function closeChatbot(): void
    {
        $this->showChatbot = false;
    }

    public function sendChatMessage(): void
    {
        if (empty(trim($this->chatMessage))) {
            return;
        }

        $userMessage = trim($this->chatMessage);
        $this->chatMessage = '';
        $this->isChatProcessing = true;

        // Add user message to history
        $this->addUserMessage($userMessage);

        // Process with AI
        $this->processAiChat($userMessage);
    }

    protected function addUserMessage(string $content): void
    {
        $message = [
            'role' => 'user',
            'content' => $content,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->chatHistory[] = $message;
        $this->activeConversation?->addMessage('user', $content);
    }

    protected function addSystemMessage(string $content, ?array $schemaChanges = null): void
    {
        $message = [
            'role' => 'assistant',
            'content' => $content,
            'timestamp' => now()->toIso8601String(),
            'schema_changes' => $schemaChanges,
        ];
        $this->chatHistory[] = $message;
        $this->activeConversation?->addMessage('assistant', $content, $schemaChanges);
    }

    protected function analyzeAndSuggestSchema(): void
    {
        // Check if AI is configured
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            $this->addSystemMessage(
                "AI analysis is not available (API key not configured).\n\n".
                "However, we can still set up your reporting schema manually! Tell me about:\n".
                "- What metrics do you need to track?\n".
                "- How often do you report?\n".
                "- What outcomes or goals are you measuring?"
            );

            return;
        }

        // Run the analysis job synchronously for immediate feedback
        $job = new AnalyzeGrantForAutomation($this->grant->id, Auth::id());
        $schema = $job->handle();

        if ($schema) {
            $this->schema = $schema;
            $this->schemaPreview = $schema->schema_data ?? [];

            $pathwayCount = count($this->schemaPreview['pathways'] ?? []);
            $metricCount = count($schema->getAllMetrics());
            $autoCount = count($schema->getAutoMetrics());
            $manualCount = count($schema->getManualMetrics());

            $this->addSystemMessage(
                "I've analyzed your grant documents and created a draft reporting schema!\n\n".
                "**Summary:**\n".
                "- {$pathwayCount} pathway(s)/theme(s)\n".
                "- {$metricCount} total metrics\n".
                "- {$autoCount} can be auto-calculated from WRK data\n".
                "- {$manualCount} will need manual entry\n\n".
                "Would you like me to walk through each outcome and explain what I've suggested? ".
                "Or you can tell me specific changes you'd like to make.",
                ['action' => 'schema_created']
            );
        } else {
            // Check if there are any grant documents
            $docCount = $this->grant->documents()->count();

            if ($docCount === 0) {
                $this->addSystemMessage(
                    "I don't see any grant documents uploaded yet. To get the best automated reporting suggestions, ".
                    "please upload your grant agreement, proposal, or reporting requirements document first.\n\n".
                    "Alternatively, tell me about your reporting requirements and I'll help you set up the schema manually:\n".
                    "- What metrics do you need to track?\n".
                    "- How often do you report (quarterly, annually)?\n".
                    "- What outcomes or goals are you measuring?"
                );
            } else {
                $this->addSystemMessage(
                    "I found {$docCount} document(s) but couldn't extract a reporting schema automatically. ".
                    "This can happen if the documents don't contain specific reporting requirements.\n\n".
                    "Let's set this up together! Tell me:\n".
                    "- What metrics do you need to track for this grant?\n".
                    "- How often do you report?\n".
                    "- What outcomes or goals are you measuring?"
                );
            }
        }
    }

    protected function processAiChat(string $userMessage): void
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            $this->addSystemMessage('AI features are currently disabled. Please configure the API key.');
            $this->isChatProcessing = false;

            return;
        }

        $prompt = $this->buildChatPrompt($userMessage);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('ai.model', 'claude-sonnet-4-20250514'),
                'max_tokens' => 2000,
                'messages' => $this->formatChatHistoryForApi(),
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json('content.0.text', '');
                $schemaChanges = $this->parseSchemaChangesFromResponse($aiResponse);

                if ($schemaChanges) {
                    $this->applySchemaChanges($schemaChanges);
                }

                // Clean the response for display (remove JSON if present)
                $displayResponse = $this->cleanResponseForDisplay($aiResponse);
                $this->addSystemMessage($displayResponse, $schemaChanges);
            } else {
                $this->addSystemMessage('I encountered an error processing your request. Please try again.');
                Log::error('AutomatedReports Chat API error: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->addSystemMessage('An error occurred: '.$e->getMessage());
            Log::error('AutomatedReports Chat Exception: '.$e->getMessage());
        }

        $this->isChatProcessing = false;
    }

    protected function buildChatPrompt(string $userMessage): string
    {
        $schemaJson = json_encode($this->schemaPreview, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are helping refine an automated reporting schema for grant "{$this->grant->name}".

## Current Schema
{$schemaJson}

## Available Data Sources
- Meetings (date, participants, tags like "convening", "briefing", external_organizations_count)
- Documents (type: policy_brief, testimony, etc., tags like "bipartisan")
- Contacts (contact_type: government_official, funder, etc., political_affiliation)
- Projects (status, tags, milestones)

## User's Request
{$userMessage}

## Instructions
1. Understand what the user wants to change
2. If making schema changes, output them in a JSON block marked with ```schema_changes
3. Be conversational and explain what you're doing
4. Ask clarifying questions if needed

If you're making changes, use this format:
```schema_changes
{
  "action": "add_metric|modify_metric|add_pathway|add_outcome|add_tag|remove_metric",
  "target": "pathway_id.outcome_id.metric_id",
  "changes": { /* the changes */ }
}
```

Respond naturally and helpfully.
PROMPT;
    }

    protected function formatChatHistoryForApi(): array
    {
        $messages = [];

        // Add system context
        $messages[] = [
            'role' => 'user',
            'content' => $this->buildChatPrompt(''),
        ];

        $messages[] = [
            'role' => 'assistant',
            'content' => 'I understand. I\'m ready to help you refine the reporting schema.',
        ];

        // Add conversation history
        foreach ($this->chatHistory as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        return $messages;
    }

    protected function parseSchemaChangesFromResponse(string $response): ?array
    {
        if (preg_match('/```schema_changes\s*(.*?)\s*```/s', $response, $matches)) {
            $json = trim($matches[1]);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    protected function cleanResponseForDisplay(string $response): string
    {
        // Remove schema_changes blocks for display
        return preg_replace('/```schema_changes\s*.*?\s*```/s', '', $response);
    }

    protected function applySchemaChanges(array $changes): void
    {
        // This would apply the schema changes to the draft schema
        // For now, just refresh the schema preview
        $this->schema?->refresh();
        $this->schemaPreview = $this->schema?->schema_data ?? [];
    }

    public function activateSchema(): void
    {
        if ($this->schema && $this->schema->isDraft()) {
            $this->schema->activate();
            $this->schema->refresh();
            $this->grant->refresh();

            $this->dispatch('notify', type: 'success', message: 'Automated reporting is now active!');
            $this->activeSubTab = 'dashboard';
            $this->showChatbot = false;

            $this->loadMetrics();
        }
    }

    // === Metrics Dashboard ===

    public function loadMetrics(): void
    {
        if (! $this->schema || ! $this->schema->isActive()) {
            return;
        }

        $this->isCalculating = true;

        // Calculate metrics
        $job = new CalculateGrantMetrics(
            $this->grant->id,
            $this->periodStart,
            $this->periodEnd,
            false
        );
        $this->metricsData = $job->handle();

        $this->isCalculating = false;
    }

    public function recalculateMetrics(): void
    {
        if (! $this->schema) {
            return;
        }

        $this->isCalculating = true;

        $job = new CalculateGrantMetrics(
            $this->grant->id,
            $this->periodStart,
            $this->periodEnd,
            true // Force recalculate
        );
        $this->metricsData = $job->handle();

        $this->isCalculating = false;
        $this->dispatch('notify', type: 'success', message: 'Metrics recalculated!');
    }

    public function getMetricValue(string $metricId): ?array
    {
        return $this->metricsData[$metricId] ?? null;
    }

    // === Tagging Interface ===

    public function loadUntaggedItems(): void
    {
        if (! $this->schema) {
            return;
        }

        $grantId = $this->grant->id;

        // Get meetings without grant associations
        $untaggedMeetings = Meeting::whereNull('grant_associations')
            ->orWhereJsonLength('grant_associations', 0)
            ->where('meeting_date', '>=', now()->subMonths(6))
            ->orderByDesc('meeting_date')
            ->limit(20)
            ->get()
            ->map(fn ($m) => [
                'type' => 'meeting',
                'id' => $m->id,
                'title' => $m->title ?? 'Meeting on '.$m->meeting_date->format('M j, Y'),
                'date' => $m->meeting_date->format('Y-m-d'),
                'current_tags' => $m->metric_tags ?? [],
                'suggested_grant' => true, // AI would suggest this
            ]);

        // Get documents without grant associations
        $untaggedDocs = ProjectDocument::whereNull('grant_associations')
            ->orWhereJsonLength('grant_associations', 0)
            ->where('created_at', '>=', now()->subMonths(6))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($d) => [
                'type' => 'document',
                'id' => $d->id,
                'title' => $d->title,
                'date' => $d->created_at->format('Y-m-d'),
                'current_tags' => $d->metric_tags ?? [],
                'suggested_grant' => true,
            ]);

        $this->untaggedItems = $untaggedMeetings->concat($untaggedDocs)->toArray();
        $this->untaggedCount = count($this->untaggedItems);
    }

    public function tagItem(string $type, int $id, array $tags, bool $associateGrant = true): void
    {
        if ($type === 'meeting') {
            $item = Meeting::find($id);
        } elseif ($type === 'document') {
            $item = ProjectDocument::find($id);
        } else {
            return;
        }

        if (! $item) {
            return;
        }

        $updates = ['metric_tags' => $tags];

        if ($associateGrant) {
            $grantAssocs = $item->grant_associations ?? [];
            if (! in_array($this->grant->id, $grantAssocs)) {
                $grantAssocs[] = $this->grant->id;
                $updates['grant_associations'] = $grantAssocs;
            }
        }

        $item->update($updates);

        // Remove from untagged list
        $this->untaggedItems = array_filter($this->untaggedItems, function ($i) use ($type, $id) {
            return ! ($i['type'] === $type && $i['id'] === $id);
        });
        $this->untaggedCount = count($this->untaggedItems);

        $this->dispatch('notify', type: 'success', message: 'Item tagged!');
    }

    public function skipItem(string $type, int $id): void
    {
        $this->untaggedItems = array_filter($this->untaggedItems, function ($i) use ($type, $id) {
            return ! ($i['type'] === $type && $i['id'] === $id);
        });
        $this->untaggedCount = count($this->untaggedItems);
    }

    // === Report Generation ===

    public function openManualEntryModal(): void
    {
        $this->manualEntries = [];

        // Get all manual metrics and pre-fill from existing calculations
        foreach ($this->schema?->getManualMetrics() ?? [] as $metric) {
            $metricId = $metric['id'];
            $existing = MetricCalculation::where('grant_id', $this->grant->id)
                ->where('metric_id', $metricId)
                ->forPeriod($this->periodStart, $this->periodEnd)
                ->first();

            $this->manualEntries[$metricId] = [
                'name' => $metric['name'],
                'prompt' => $metric['prompt'] ?? 'Enter your response',
                'value' => $existing?->manual_value ?? '',
            ];
        }

        $this->showManualEntryModal = true;
    }

    public function closeManualEntryModal(): void
    {
        $this->showManualEntryModal = false;
    }

    public function saveManualEntries(): void
    {
        foreach ($this->manualEntries as $metricId => $entry) {
            if (! empty($entry['value'])) {
                MetricCalculation::updateOrCreate(
                    [
                        'grant_id' => $this->grant->id,
                        'metric_id' => $metricId,
                        'reporting_period_start' => $this->periodStart,
                        'reporting_period_end' => $this->periodEnd,
                    ],
                    [
                        'schema_id' => $this->schema?->id,
                        'manual_value' => $entry['value'],
                        'calculation_method' => 'manual',
                        'calculated_at' => now(),
                        'calculated_by' => Auth::id(),
                    ]
                );
            }
        }

        $this->showManualEntryModal = false;
        $this->dispatch('notify', type: 'success', message: 'Manual entries saved!');
    }

    public function generateReport(): void
    {
        $this->isGeneratingReport = true;
        $this->generatedReport = null;

        // This would dispatch the GenerateAutomatedReport job
        // For now, we'll generate inline

        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            $this->generatedReport = '**Error:** AI features are disabled. Please configure the API key.';
            $this->isGeneratingReport = false;

            return;
        }

        $prompt = $this->buildReportPrompt();

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
                $this->generatedReport = $response->json('content.0.text', '');
            } else {
                $this->generatedReport = '**Error generating report.** Please try again.';
                Log::error('AutomatedReports Report Generation error: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->generatedReport = '**Error:** '.$e->getMessage();
            Log::error('AutomatedReports Report Exception: '.$e->getMessage());
        }

        $this->isGeneratingReport = false;
    }

    protected function buildReportPrompt(): string
    {
        $schemaJson = json_encode($this->schemaPreview, JSON_PRETTY_PRINT);
        $metricsJson = json_encode($this->metricsData, JSON_PRETTY_PRINT);

        // Gather manual entries
        $manualData = [];
        foreach ($this->manualEntries as $metricId => $entry) {
            if (! empty($entry['value'])) {
                $manualData[$metricId] = [
                    'name' => $entry['name'],
                    'value' => $entry['value'],
                ];
            }
        }
        $manualJson = json_encode($manualData, JSON_PRETTY_PRINT);

        return <<<PROMPT
Generate a progress report for grant "{$this->grant->name}" funded by "{$this->grant->funder?->name}".
Reporting Period: {$this->periodLabel} ({$this->periodStart} to {$this->periodEnd})

## Reporting Schema (defines structure and outcomes)
{$schemaJson}

## Calculated Metrics (auto-tracked from WRK data)
{$metricsJson}

## Manual Entries (qualitative assessments)
{$manualJson}

## Instructions
1. Organize the report by pathways and outcomes from the schema
2. Present metrics naturally in prose, not just as numbers
3. Highlight achievements and progress toward targets
4. Be honest about areas below target
5. Use markdown formatting with clear headers
6. Include specific item examples where available (from the metrics items lists)
7. End with brief next steps or focus areas

Generate the complete progress report now:
PROMPT;
    }

    public function clearReport(): void
    {
        $this->generatedReport = null;
    }

    // === Computed Properties ===

    public function getSchemaStatusProperty(): string
    {
        if (! $this->schema) {
            return 'none';
        }

        return $this->schema->status;
    }

    public function getHasSchemaProperty(): bool
    {
        return $this->schema !== null;
    }

    public function render()
    {
        return view('livewire.grants.automated-reports', [
            'pathways' => $this->schemaPreview['pathways'] ?? [],
            'tagsConfig' => $this->schemaPreview['tags_config'] ?? [],
            'availableTags' => $this->schema?->getRequiredTags() ?? [],
        ]);
    }
}
