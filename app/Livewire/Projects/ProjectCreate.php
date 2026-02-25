<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Create Project')]
class ProjectCreate extends Component
{
    public string $name = '';

    public string $description = '';

    public string $goals = '';

    public ?string $start_date = null;

    public ?string $target_end_date = null;

    public string $status = 'active';

    // New fields
    public string $scope = '';

    public string $lead = '';

    public string $url = '';

    public string $tagsInput = ''; // Comma-separated tags

    // Hierarchy fields
    public ?int $parent_project_id = null;

    public string $project_type = 'initiative';

    // Geographic tags
    public array $selectedRegions = [];

    public array $selectedCountries = [];

    public array $selectedUsStates = [];

    // AI Extraction
    public bool $showAiExtract = false;

    public string $freeText = '';

    public bool $isExtracting = false;

    public bool $hasExtracted = false;

    // Chat-first project creation
    public string $chatInput = '';

    public array $chatMessages = [];

    // Lead options
    public array $leadOptions = ['Anne', 'Aubrey', 'Caitlin', 'Chloe', 'Danielle', 'Marci'];

    public array $scopeOptions = ['US', 'Global', 'Comms'];

    // Duplicate tracking
    public bool $isDuplicate = false;

    public string $sourceProjectName = '';

    public function mount(?Project $project = null): void
    {
        $this->chatMessages[] = [
            'role' => 'assistant',
            'content' => 'Tell me about the project in plain language. I will populate the project profile for you.',
            'timestamp' => now()->format('g:i A'),
        ];

        if ($project && $project->exists) {
            // Pre-fill form with data from source project
            $this->isDuplicate = true;
            $this->sourceProjectName = $project->name;
            $this->name = $project->name.' (Copy)';
            $this->description = $project->description ?? '';
            $this->goals = $project->goals ?? '';
            $this->status = 'planning'; // Start as planning
            $this->scope = $project->scope ?? '';
            $this->lead = $project->lead ?? '';
            $this->url = $project->url ?? '';
            $this->parent_project_id = $project->parent_project_id;
            $this->project_type = $project->project_type ?? 'initiative';

            // Convert tags array back to comma-separated string
            if ($project->tags && is_array($project->tags)) {
                $this->tagsInput = implode(', ', $project->tags);
            }

            // Copy geographic tags
            $this->selectedRegions = $project->geographicTags()->where('geographic_type', 'region')->pluck('geographic_id')->toArray();
            $this->selectedCountries = $project->geographicTags()->where('geographic_type', 'country')->pluck('geographic_id')->toArray();
            $this->selectedUsStates = $project->geographicTags()->where('geographic_type', 'us_state')->pluck('geographic_id')->toArray();

            return;
        }

        $parentId = (int) request()->integer('parent');
        if ($parentId > 0 && Project::query()->whereKey($parentId)->exists()) {
            $this->parent_project_id = $parentId;
        }
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'goals' => 'nullable|string',
        'start_date' => 'nullable|date',
        'target_end_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'required|in:planning,active,on_hold,completed,archived',
        'scope' => 'nullable|string|max:50',
        'lead' => 'nullable|string|max:100',
        'url' => 'nullable|url|max:500',
        'tagsInput' => 'nullable|string',
        'parent_project_id' => 'nullable|exists:projects,id',
        'project_type' => 'required|string|in:initiative,publication,event,chapter,newsletter,tool,research,outreach,component',
    ];

    #[On('geographic-tags-updated')]
    public function updateGeographicTags(array $data): void
    {
        $this->selectedRegions = $data['regions'] ?? [];
        $this->selectedCountries = $data['countries'] ?? [];
        $this->selectedUsStates = $data['usStates'] ?? [];
    }

    public function toggleAiExtract()
    {
        $this->showAiExtract = ! $this->showAiExtract;
    }

    public function sendChatMessage(): void
    {
        $message = trim($this->chatInput);
        if ($message === '') {
            return;
        }

        $this->chatMessages[] = [
            'role' => 'user',
            'content' => $message,
            'timestamp' => now()->format('g:i A'),
        ];

        $this->chatInput = '';
        $this->freeText = trim($this->freeText."\n".$message);

        $success = $this->runExtraction($this->freeText, notify: false);

        $this->chatMessages[] = [
            'role' => 'assistant',
            'content' => $success
                ? $this->buildAssistantSummary()
                : 'I could not extract project details from that yet. Add a little more context and I will try again.',
            'timestamp' => now()->format('g:i A'),
        ];
    }

    public function extractFromText()
    {
        $this->runExtraction($this->freeText, notify: true);
    }

    protected function getExtractionSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an assistant that extracts structured project information from free-form text.

Extract the following fields from the provided text:
1. **title**: A concise project name/title (max 100 chars)
2. **description**: A clear description of what the project is about
3. **goals**: Specific goals or objectives (as bullet points or numbered list)
4. **start_date**: Start date if mentioned (format: YYYY-MM-DD)
5. **end_date**: Target end date or deadline if mentioned (format: YYYY-MM-DD)
6. **status**: One of: planning, active, on_hold, completed, archived (default to "planning" if unclear)
7. **scope**: One of: US, Global, Comms (based on whether it's US-focused, international/global, or communications)
8. **lead**: The lead person's first name if mentioned (Anne, Aubrey, Caitlin, Chloe, Danielle, Marci)
9. **url**: Any URL/link mentioned
10. **tags**: An array of relevant themes/tags (e.g., "Bridge-building", "Interbranch feedback loops")

Return your response in this exact JSON format:
```json
{
    "title": "...",
    "description": "...",
    "goals": "...",
    "start_date": "YYYY-MM-DD or null",
    "end_date": "YYYY-MM-DD or null",
    "status": "planning",
    "scope": "US",
    "lead": "Anne",
    "url": "https://... or null",
    "tags": ["tag1", "tag2"]
}
```

If a field cannot be determined from the text, use reasonable defaults:
- title: Create a title from the main topic
- description: Summarize the main points
- goals: Extract any objectives or outcomes mentioned
- dates: Use null if not mentioned
- status: Default to "planning"
- scope: Default to "US" if unclear
- lead: null if not mentioned
- url: null if not mentioned
- tags: Empty array if no themes identified

Be thorough but concise. The goals should be actionable and clear.
PROMPT;
    }

    protected function parseExtractedData(string $content): bool
    {
        // Try to extract JSON from the response
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonStr = $matches[1];
        } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
            $jsonStr = $matches[0];
        } else {
            return false;
        }

        try {
            $data = json_decode($jsonStr, true);

            if (! $data) {
                return false;
            }

            // Populate form fields
            if (! empty($data['title'])) {
                $this->name = $data['title'];
            }
            if (! empty($data['description'])) {
                $this->description = $data['description'];
            }
            if (! empty($data['goals'])) {
                $this->goals = $data['goals'];
            }
            if (! empty($data['start_date']) && $data['start_date'] !== 'null') {
                $this->start_date = $data['start_date'];
            }
            if (! empty($data['end_date']) && $data['end_date'] !== 'null') {
                $this->target_end_date = $data['end_date'];
            }
            if (! empty($data['status']) && array_key_exists($data['status'], Project::STATUSES)) {
                $this->status = $data['status'];
            }
            if (! empty($data['scope'])) {
                $this->scope = $data['scope'];
            }
            if (! empty($data['lead'])) {
                $this->lead = $data['lead'];
            }
            if (! empty($data['url']) && $data['url'] !== 'null') {
                $this->url = $data['url'];
            }
            if (! empty($data['tags']) && is_array($data['tags'])) {
                $this->tagsInput = implode(', ', $data['tags']);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error parsing extracted project data: '.$e->getMessage());

            return false;
        }
    }

    protected function runExtraction(string $text, bool $notify = true): bool
    {
        if (empty(trim($text))) {
            if ($notify) {
                $this->dispatch('notify', type: 'error', message: 'Please enter some text to extract from.');
            }

            return false;
        }

        $this->isExtracting = true;

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 1500,
                'system' => $this->getExtractionSystemPrompt(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Extract project details from this text:\n\n{$text}",
                    ],
                ],
            ]);

            $content = (string) ($response->json('content.0.text') ?? '');
            $parsed = $content !== '' && $this->parseExtractedData($content);

            if ($parsed) {
                $this->hasExtracted = true;
                $this->showAiExtract = false;
                if ($notify) {
                    $this->dispatch('notify', type: 'success', message: 'Project details extracted.');
                }

                return true;
            }

            if ($notify) {
                $this->dispatch('notify', type: 'error', message: 'Could not extract project details. Please try again.');
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Project AI extraction error: '.$e->getMessage());
            if ($notify) {
                $this->dispatch('notify', type: 'error', message: 'Error during extraction. Please try again.');
            }

            return false;
        } finally {
            $this->isExtracting = false;
        }
    }

    protected function buildAssistantSummary(): string
    {
        $parts = [];
        $parts[] = 'Got it. I updated the project profile.';
        $parts[] = $this->name !== '' ? "Name: {$this->name}" : 'Name: (still missing)';
        $parts[] = $this->project_type !== '' ? 'Type: '.$this->project_type : null;
        $parts[] = $this->scope !== '' ? 'Scope: '.$this->scope : null;
        $parts[] = $this->lead !== '' ? 'Lead: '.$this->lead : null;
        $parts[] = $this->status !== '' ? 'Status: '.str_replace('_', ' ', $this->status) : null;

        if ($this->target_end_date) {
            $parts[] = 'Target end: '.$this->target_end_date;
        }

        $parts[] = 'Review the preview below and click Create Project when ready.';

        return implode("\n", array_values(array_filter($parts)));
    }

    public function save()
    {
        $this->validate();

        // Parse tags from comma-separated string
        $tags = [];
        if (! empty($this->tagsInput)) {
            $tags = array_map('trim', explode(',', $this->tagsInput));
            $tags = array_filter($tags); // Remove empty values
        }

        $project = Project::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'goals' => $this->goals ?: null,
            'start_date' => $this->start_date ?: null,
            'target_end_date' => $this->target_end_date ?: null,
            'status' => $this->status,
            'scope' => $this->scope ?: null,
            'lead' => $this->lead ?: null,
            'url' => $this->url ?: null,
            'tags' => ! empty($tags) ? $tags : null,
            'created_by' => auth()->id(),
            'parent_project_id' => $this->parent_project_id,
            'project_type' => $this->project_type,
        ]);

        // Sync geographic tags
        $project->syncGeographicTags(
            $this->selectedRegions,
            $this->selectedCountries,
            $this->selectedUsStates
        );

        $this->dispatch('notify', type: 'success', message: 'Project created successfully!');

        return $this->redirect(route('projects.show', $project), navigate: true);
    }

    public function render()
    {
        return view('livewire.projects.project-create', [
            'statuses' => Project::STATUSES,
            'parentProjects' => Project::roots()->orderBy('name')->get(),
            'projectTypes' => [
                'initiative' => 'Initiative',
                'publication' => 'Publication',
                'event' => 'Event',
                'chapter' => 'Chapter',
                'newsletter' => 'Newsletter',
                'tool' => 'Tool',
                'research' => 'Research',
                'outreach' => 'Outreach',
                'component' => 'Component',
            ],
        ]);
    }
}
