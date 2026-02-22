<?php

namespace App\Livewire\Intelligence;

use App\Models\Agent;
use App\Models\AgentMessage;
use App\Models\AgentPermission;
use App\Models\AgentRun;
use App\Models\AgentSuggestion;
use App\Models\AgentTemplate;
use App\Models\Grant;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Trip;
use App\Services\Agents\AgentOrchestratorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Intelligence')]
class IntelligenceIndex extends Component
{
    public bool $migrationReady = false;

    public string $migrationMessage = '';

    public array $agentCouncil = [];

    public array $insights = [];

    public string $generatedAt = '';

    public array $agentDirectory = [];

    public array $agentMessages = [];

    public array $pendingSuggestions = [];

    public array $recentRuns = [];

    public array $templateOptions = [];

    public array $projectOptions = [];

    public array $permissionSnapshot = [];

    public ?int $selectedAgentId = null;

    public string $directiveInput = '';

    public array $suggestionOverrides = [];

    public array $createForm = [
        'name' => '',
        'scope' => Agent::SCOPE_SPECIALIST,
        'specialty' => 'policy',
        'project_id' => '',
        'template_id' => '',
        'mission' => '',
        'instructions' => '',
        'autonomy_mode' => 'tiered',
        'governance_low' => 'autonomous',
        'governance_medium' => 'team_approval',
        'governance_high' => 'management_approval',
    ];

    public function mount(): void
    {
        $this->migrationReady = $this->hasAgentSchema();

        if (! $this->migrationReady) {
            $this->migrationMessage = 'Agent management tables are not available yet. Run migrations to enable build/direct workflows.';
            $this->agentCouncil = $this->buildAgentCouncil();
            $this->insights = $this->buildInsightStream();
            $this->generatedAt = now()->format('M j, Y g:i A');

            return;
        }

        $this->ensureDefaultTemplates();
        $this->loadFormOptions();
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->agentCouncil = $this->buildAgentCouncil();
        $this->insights = $this->buildInsightStream();

        if ($this->migrationReady) {
            $this->loadPermissionSnapshot();
            $this->loadAgentDirectory();
            $this->hydrateSelectedAgentWorkspace();
        }

        $this->generatedAt = now()->format('M j, Y g:i A');
    }

    public function updatedCreateFormScope(string $scope): void
    {
        if ($scope === Agent::SCOPE_PROJECT && empty($this->createForm['project_id']) && ! empty($this->projectOptions)) {
            $this->createForm['project_id'] = (string) ($this->projectOptions[0]['id'] ?? '');
        }
    }

    public function selectAgent(int $agentId): void
    {
        $this->selectedAgentId = $agentId;
        $this->hydrateSelectedAgentWorkspace();
    }

    public function createAgent(): void
    {
        if (! $this->migrationReady) {
            $this->dispatch('notify', type: 'error', message: 'Run migrations before creating agents.');

            return;
        }

        $validated = $this->validate([
            'createForm.name' => ['required', 'string', 'max:120'],
            'createForm.scope' => ['required', Rule::in([Agent::SCOPE_SPECIALIST, Agent::SCOPE_PROJECT])],
            'createForm.specialty' => ['nullable', 'string', 'max:64'],
            'createForm.project_id' => [
                Rule::requiredIf(fn () => ($this->createForm['scope'] ?? Agent::SCOPE_SPECIALIST) === Agent::SCOPE_PROJECT),
                'nullable',
                'integer',
                Rule::exists('projects', 'id'),
            ],
            'createForm.template_id' => ['nullable', 'integer', Rule::exists('agent_templates', 'id')],
            'createForm.mission' => ['nullable', 'string', 'max:1000'],
            'createForm.instructions' => ['nullable', 'string', 'max:8000'],
            'createForm.autonomy_mode' => ['required', Rule::in(['tiered', 'propose_only'])],
            'createForm.governance_low' => ['required', Rule::in(['autonomous', 'team_approval', 'management_approval'])],
            'createForm.governance_medium' => ['required', Rule::in(['autonomous', 'team_approval', 'management_approval'])],
            'createForm.governance_high' => ['required', Rule::in(['autonomous', 'team_approval', 'management_approval'])],
        ]);

        $scope = $validated['createForm']['scope'];
        $projectId = (int) ($validated['createForm']['project_id'] ?? 0);

        if (! $this->canCreateAgent($scope, $projectId ?: null)) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to create this type of agent.');

            return;
        }

        $template = null;
        $templateId = (int) ($validated['createForm']['template_id'] ?? 0);
        if ($templateId > 0) {
            $template = AgentTemplate::query()->find($templateId);
        }

        $templateConfig = is_array($template?->default_config) ? $template->default_config : [];

        $agent = Agent::query()->create([
            'name' => trim((string) $validated['createForm']['name']),
            'scope' => $scope,
            'specialty' => trim((string) ($validated['createForm']['specialty'] ?? '')) ?: null,
            'status' => Agent::STATUS_ACTIVE,
            'project_id' => $scope === Agent::SCOPE_PROJECT ? $projectId : null,
            'template_id' => $template?->id,
            'created_by' => Auth::id(),
            'owner_user_id' => Auth::id(),
            'mission' => trim((string) ($validated['createForm']['mission'] ?? '')) ?: ($template?->description ?: null),
            'instructions' => trim((string) ($validated['createForm']['instructions'] ?? '')) ?: ($template?->system_prompt ?: null),
            'knowledge_sources' => $this->buildKnowledgeSources($scope, $projectId ?: null, $templateConfig),
            'governance_tiers' => [
                'low' => $validated['createForm']['governance_low'],
                'medium' => $validated['createForm']['governance_medium'],
                'high' => $validated['createForm']['governance_high'],
            ],
            'autonomy_mode' => $validated['createForm']['autonomy_mode'],
            'is_persistent' => true,
        ]);

        if ($template) {
            $template->increment('times_used');
        }

        $this->selectedAgentId = $agent->id;

        $this->createForm['name'] = '';
        $this->createForm['mission'] = '';
        $this->createForm['instructions'] = '';
        $this->createForm['template_id'] = '';

        $this->refresh();
        $this->dispatch('notify', type: 'success', message: 'Agent created and ready for direction.');
    }

    public function toggleAgentStatus(int $agentId): void
    {
        $agent = Agent::query()->find($agentId);
        if (! $agent) {
            return;
        }

        $actor = Auth::user();
        if (! $actor || ! $this->canManageAgent($agent)) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to change this agent.');

            return;
        }

        $nextStatus = $agent->status === Agent::STATUS_ACTIVE
            ? Agent::STATUS_PAUSED
            : Agent::STATUS_ACTIVE;

        $agent->update(['status' => $nextStatus]);

        $this->refresh();
        $this->dispatch('notify', type: 'success', message: 'Agent status updated.');
    }

    public function directSelectedAgent(AgentOrchestratorService $orchestrator): void
    {
        $directive = trim($this->directiveInput);
        if ($directive === '') {
            $this->dispatch('notify', type: 'error', message: 'Enter a directive first.');

            return;
        }

        $agent = $this->selectedAgent();
        $actor = Auth::user();

        if (! $agent || ! $actor) {
            $this->dispatch('notify', type: 'error', message: 'Select an agent first.');

            return;
        }

        if (! $this->canDirectAgent($agent)) {
            $this->dispatch('notify', type: 'error', message: 'You do not have access to direct this agent.');

            return;
        }

        try {
            $result = $orchestrator->direct($agent, $actor, $directive);
            $autoExecutedCount = is_array($result['auto_executed'] ?? null)
                ? count($result['auto_executed'])
                : 0;

            $this->directiveInput = '';
            $this->hydrateSelectedAgentWorkspace();
            $this->dispatch('workspace-thread-updated');

            $message = $autoExecutedCount > 0
                ? 'Directive processed. '.$autoExecutedCount.' low-risk action(s) executed automatically.'
                : 'Directive processed. Suggestions are ready for review.';

            $this->dispatch('notify', type: 'success', message: $message);
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notify', type: 'error', message: 'Agent could not process that directive right now.');
        }
    }

    public function approveSuggestion(int $suggestionId, AgentOrchestratorService $orchestrator): void
    {
        $suggestion = AgentSuggestion::query()
            ->with('agent')
            ->where('id', $suggestionId)
            ->where('approval_status', AgentSuggestion::STATUS_PENDING)
            ->first();

        $actor = Auth::user();
        if (! $suggestion || ! $actor) {
            return;
        }

        if (! $this->canDirectAgent($suggestion->agent)) {
            $this->dispatch('notify', type: 'error', message: 'You do not have access to this agent suggestion.');

            return;
        }

        try {
            $override = trim((string) ($this->suggestionOverrides[$suggestionId] ?? ''));
            $summary = $orchestrator->approveSuggestion(
                $suggestion,
                $actor,
                $override !== '' ? $override : null,
            );

            unset($this->suggestionOverrides[$suggestionId]);
            $this->hydrateSelectedAgentWorkspace();
            $this->dispatch('workspace-thread-updated');
            $this->dispatch('notify', type: 'success', message: $summary);
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());
        }
    }

    public function dismissSuggestion(int $suggestionId, AgentOrchestratorService $orchestrator): void
    {
        $suggestion = AgentSuggestion::query()
            ->with('agent')
            ->where('id', $suggestionId)
            ->where('approval_status', AgentSuggestion::STATUS_PENDING)
            ->first();

        $actor = Auth::user();
        if (! $suggestion || ! $actor) {
            return;
        }

        if (! $this->canDirectAgent($suggestion->agent)) {
            $this->dispatch('notify', type: 'error', message: 'You do not have access to this agent suggestion.');

            return;
        }

        try {
            $orchestrator->dismissSuggestion($suggestion, $actor, 'Dismissed from Intelligence review queue.');
            unset($this->suggestionOverrides[$suggestionId]);
            $this->hydrateSelectedAgentWorkspace();
            $this->dispatch('workspace-thread-updated');
            $this->dispatch('notify', type: 'success', message: 'Suggestion dismissed.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());
        }
    }

    protected function hasAgentSchema(): bool
    {
        return Schema::hasTable('agents')
            && Schema::hasTable('agent_templates')
            && Schema::hasTable('agent_permissions')
            && Schema::hasTable('agent_threads')
            && Schema::hasTable('agent_messages')
            && Schema::hasTable('agent_runs')
            && Schema::hasTable('agent_suggestions')
            && Schema::hasTable('agent_suggestion_sources');
    }

    protected function ensureDefaultTemplates(): void
    {
        if (AgentTemplate::query()->exists()) {
            return;
        }

        $templates = [
            [
                'name' => 'Policy Analyst',
                'agent_type' => Agent::SCOPE_SPECIALIST,
                'specialty' => 'policy',
                'description' => 'Tracks policy shifts, legislative movement, and strategic implications.',
                'system_prompt' => 'You are a policy strategist for POPVOX Foundation. Prioritize policy movement, strategic implications, and concrete recommended actions.',
            ],
            [
                'name' => 'Grants Navigator',
                'agent_type' => Agent::SCOPE_SPECIALIST,
                'specialty' => 'grants',
                'description' => 'Monitors grants, due diligence timelines, and reporting windows.',
                'system_prompt' => 'You are a grants operations specialist. Focus on compliance deadlines, renewal opportunities, and reporting quality.',
            ],
            [
                'name' => 'Communications Lead',
                'agent_type' => Agent::SCOPE_SPECIALIST,
                'specialty' => 'communications',
                'description' => 'Drafts outreach, media follow-up, and external messaging workstreams.',
                'system_prompt' => 'You are communications support for POPVOX Foundation. Draft clear messaging, anticipate stakeholder concerns, and suggest concise next steps.',
            ],
            [
                'name' => 'Research Partner',
                'agent_type' => Agent::SCOPE_SPECIALIST,
                'specialty' => 'research',
                'description' => 'Synthesizes documents and flags strategic patterns across projects.',
                'system_prompt' => 'You are a research partner agent. Highlight emerging themes, contradictory evidence, and where more data is needed.',
            ],
            [
                'name' => 'International Programs',
                'agent_type' => Agent::SCOPE_SPECIALIST,
                'specialty' => 'international',
                'description' => 'Supports international projects, trip prep, and partner coordination.',
                'system_prompt' => 'You support international program operations. Surface travel, partner, and program coordination risks early.',
            ],
            [
                'name' => 'Project Copilot',
                'agent_type' => Agent::SCOPE_PROJECT,
                'specialty' => 'project',
                'description' => 'Reusable template for dedicated project teammate agents.',
                'system_prompt' => 'You are a project-scoped teammate agent. Track decisions, propose tasks, suggest next moves, and connect project work to broader organizational context.',
            ],
        ];

        foreach ($templates as $template) {
            AgentTemplate::query()->create([
                ...$template,
                'default_config' => [
                    'governance_low' => 'autonomous',
                    'governance_medium' => 'team_approval',
                    'governance_high' => 'management_approval',
                ],
                'is_active' => true,
                'is_global' => true,
                'times_used' => 0,
                'created_by' => Auth::id(),
            ]);
        }
    }

    protected function loadFormOptions(): void
    {
        $this->templateOptions = AgentTemplate::query()
            ->where('is_active', true)
            ->orderBy('agent_type')
            ->orderBy('name')
            ->get(['id', 'name', 'agent_type', 'specialty', 'description'])
            ->map(fn (AgentTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'agent_type' => $template->agent_type,
                'specialty' => $template->specialty,
                'description' => $template->description,
            ])->values()->all();

        $this->projectOptions = Project::query()
            ->orderBy('name')
            ->get(['id', 'name', 'status'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
            ])->values()->all();

        if (empty($this->createForm['project_id']) && ! empty($this->projectOptions)) {
            $this->createForm['project_id'] = (string) ($this->projectOptions[0]['id'] ?? '');
        }
    }

    protected function loadPermissionSnapshot(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->permissionSnapshot = [];

            return;
        }

        $permission = AgentPermission::query()->where('user_id', $user->id)->first();

        if (! $permission) {
            $this->permissionSnapshot = [
                'can_create_specialist' => $user->isManagement(),
                'can_create_project' => true,
                'project_scope' => 'all',
                'allowed_project_ids' => [],
                'can_approve_medium_risk' => $user->isManagement(),
                'can_approve_high_risk' => $user->isManagement(),
            ];

            return;
        }

        $this->permissionSnapshot = [
            'can_create_specialist' => (bool) $permission->can_create_specialist,
            'can_create_project' => (bool) $permission->can_create_project,
            'project_scope' => (string) $permission->project_scope,
            'allowed_project_ids' => array_values(array_map('intval', $permission->allowed_project_ids ?? [])),
            'can_approve_medium_risk' => (bool) $permission->can_approve_medium_risk,
            'can_approve_high_risk' => (bool) $permission->can_approve_high_risk,
        ];
    }

    protected function loadAgentDirectory(): void
    {
        $agents = Agent::query()
            ->with(['project:id,name,status'])
            ->withCount([
                'suggestions as pending_suggestions_count' => fn ($query) => $query->where('approval_status', AgentSuggestion::STATUS_PENDING),
            ])
            ->orderByDesc('last_directed_at')
            ->orderByDesc('updated_at')
            ->get();

        $visible = $agents->filter(fn (Agent $agent) => $this->canDirectAgent($agent));

        $this->agentDirectory = $visible
            ->map(function (Agent $agent): array {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'scope' => $agent->scope,
                    'specialty' => $agent->specialty,
                    'status' => $agent->status,
                    'project_id' => $agent->project_id,
                    'project_name' => $agent->project?->name,
                    'pending_suggestions_count' => (int) ($agent->pending_suggestions_count ?? 0),
                    'last_directed_at' => $agent->last_directed_at?->diffForHumans(),
                    'governance_tiers' => (array) ($agent->governance_tiers ?? []),
                    'autonomy_mode' => $agent->autonomy_mode,
                ];
            })
            ->values()
            ->all();

        if (! empty($this->agentDirectory)) {
            $knownIds = collect($this->agentDirectory)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (! $this->selectedAgentId || ! in_array($this->selectedAgentId, $knownIds, true)) {
                $this->selectedAgentId = (int) $this->agentDirectory[0]['id'];
            }
        } else {
            $this->selectedAgentId = null;
        }
    }

    protected function hydrateSelectedAgentWorkspace(): void
    {
        $agent = $this->selectedAgent();

        if (! $agent) {
            $this->agentMessages = [];
            $this->pendingSuggestions = [];
            $this->recentRuns = [];

            return;
        }

        $thread = $agent->threads()
            ->where('user_id', Auth::id())
            ->first();

        if ($thread) {
            $this->agentMessages = AgentMessage::query()
                ->where('thread_id', $thread->id)
                ->orderBy('created_at')
                ->limit(120)
                ->get(['id', 'role', 'content', 'created_at'])
                ->map(fn (AgentMessage $message) => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'timestamp' => $message->created_at?->format('M j, g:i A'),
                ])
                ->values()
                ->all();
        } else {
            $this->agentMessages = [];
        }

        $pending = AgentSuggestion::query()
            ->with(['sources', 'run', 'agent:id,created_by,owner_user_id,governance_tiers'])
            ->where('agent_id', $agent->id)
            ->where('approval_status', AgentSuggestion::STATUS_PENDING)
            ->latest('created_at')
            ->limit(80)
            ->get();

        $this->pendingSuggestions = $pending->map(function (AgentSuggestion $suggestion): array {
            $mode = $this->governanceModeForSuggestion($suggestion);

            return [
                'id' => $suggestion->id,
                'run_id' => $suggestion->run_id,
                'suggestion_type' => $suggestion->suggestion_type,
                'title' => $suggestion->title,
                'reasoning' => $suggestion->reasoning,
                'risk_level' => $suggestion->risk_level,
                'governance_mode' => $mode,
                'payload' => is_array($suggestion->payload) ? $suggestion->payload : [],
                'created_at' => $suggestion->created_at?->format('M j, g:i A'),
                'can_review' => $this->canReviewSuggestion($suggestion),
                'sources' => $suggestion->sources->map(fn ($source) => [
                    'source_type' => $source->source_type,
                    'source_id' => $source->source_id,
                    'source_title' => $source->source_title,
                    'confidence' => $source->confidence,
                    'source_url' => $source->source_url,
                ])->values()->all(),
            ];
        })->values()->all();

        $runs = AgentRun::query()
            ->with(['suggestions.sources'])
            ->where('agent_id', $agent->id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        $this->recentRuns = $runs->map(function (AgentRun $run): array {
            return [
                'id' => $run->id,
                'status' => $run->status,
                'directive' => $run->directive,
                'result_summary' => $run->result_summary,
                'reasoning_chain' => is_array($run->reasoning_chain) ? $run->reasoning_chain : [],
                'alternatives_considered' => is_array($run->alternatives_considered) ? $run->alternatives_considered : [],
                'created_at' => $run->created_at?->format('M j, g:i A'),
                'completed_at' => $run->completed_at?->format('M j, g:i A'),
                'error_message' => $run->error_message,
                'suggestions' => $run->suggestions->map(fn (AgentSuggestion $suggestion) => [
                    'id' => $suggestion->id,
                    'type' => $suggestion->suggestion_type,
                    'title' => $suggestion->title,
                    'status' => $suggestion->approval_status,
                    'risk_level' => $suggestion->risk_level,
                    'sources' => $suggestion->sources->map(fn ($source) => [
                        'type' => $source->source_type,
                        'title' => $source->source_title,
                        'url' => $source->source_url,
                        'confidence' => $source->confidence,
                    ])->values()->all(),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    protected function selectedAgent(): ?Agent
    {
        if (! $this->selectedAgentId) {
            return null;
        }

        return Agent::query()->find($this->selectedAgentId);
    }

    protected function canCreateAgent(string $scope, ?int $projectId = null): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isManagement()) {
            return true;
        }

        if (empty($this->permissionSnapshot)) {
            $this->loadPermissionSnapshot();
        }

        if ($scope === Agent::SCOPE_SPECIALIST) {
            return (bool) ($this->permissionSnapshot['can_create_specialist'] ?? false);
        }

        if (! (bool) ($this->permissionSnapshot['can_create_project'] ?? false)) {
            return false;
        }

        if (! $projectId) {
            return true;
        }

        return $this->canAccessProjectByScope($projectId);
    }

    protected function canManageAgent(Agent $agent): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isManagement()) {
            return true;
        }

        return (int) $agent->created_by === (int) $user->id
            || (! empty($agent->owner_user_id) && (int) $agent->owner_user_id === (int) $user->id);
    }

    protected function canDirectAgent(Agent $agent): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isManagement()) {
            return true;
        }

        if (empty($this->permissionSnapshot)) {
            $this->loadPermissionSnapshot();
        }

        if ($agent->scope === Agent::SCOPE_PROJECT && $agent->project_id) {
            return $this->canAccessProjectByScope((int) $agent->project_id);
        }

        if ((int) $agent->created_by === (int) $user->id || (int) $agent->owner_user_id === (int) $user->id) {
            return true;
        }

        return (bool) ($this->permissionSnapshot['can_create_specialist'] ?? false);
    }

    protected function canReviewSuggestion(AgentSuggestion $suggestion): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $mode = $this->governanceModeForSuggestion($suggestion);
        if ($mode === 'autonomous') {
            return true;
        }

        if ($user->isManagement()) {
            return true;
        }

        if (empty($this->permissionSnapshot)) {
            $this->loadPermissionSnapshot();
        }

        if ($mode === 'team_approval') {
            if ((int) $suggestion->agent->created_by === (int) $user->id || (int) $suggestion->agent->owner_user_id === (int) $user->id) {
                return true;
            }

            return (bool) (($this->permissionSnapshot['can_approve_medium_risk'] ?? false)
                || ($this->permissionSnapshot['can_approve_high_risk'] ?? false));
        }

        return (bool) ($this->permissionSnapshot['can_approve_high_risk'] ?? false);
    }

    protected function canAccessProjectByScope(int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isManagement()) {
            return true;
        }

        if (empty($this->permissionSnapshot)) {
            $this->loadPermissionSnapshot();
        }

        $scope = (string) ($this->permissionSnapshot['project_scope'] ?? 'all');

        return match ($scope) {
            'none' => false,
            'all' => true,
            'custom' => in_array($projectId, array_map('intval', $this->permissionSnapshot['allowed_project_ids'] ?? []), true),
            'assigned' => $this->isAssignedToProject($projectId, (int) $user->id),
            default => true,
        };
    }

    protected function isAssignedToProject(int $projectId, int $userId): bool
    {
        $project = Project::query()->find($projectId);
        if (! $project) {
            return false;
        }

        if ((int) $project->created_by === $userId) {
            return true;
        }

        return $project->staff()->where('users.id', $userId)->exists();
    }

    protected function governanceModeForSuggestion(AgentSuggestion $suggestion): string
    {
        $risk = strtolower(trim((string) $suggestion->risk_level));
        $tiers = is_array($suggestion->agent->governance_tiers) ? $suggestion->agent->governance_tiers : [];

        $default = match ($risk) {
            'low' => 'autonomous',
            'high' => 'management_approval',
            default => 'team_approval',
        };

        $mode = strtolower((string) ($tiers[$risk] ?? $default));

        return in_array($mode, ['autonomous', 'team_approval', 'management_approval'], true)
            ? $mode
            : $default;
    }

    protected function buildKnowledgeSources(string $scope, ?int $projectId, array $templateConfig): array
    {
        $sources = [
            ['type' => 'box', 'scope' => 'wrk-root', 'description' => 'WRK root taxonomy and linked files'],
            ['type' => 'postgres', 'scope' => 'global', 'description' => 'Structured records across projects, meetings, travel, contacts, and funders'],
        ];

        if ($scope === Agent::SCOPE_PROJECT && $projectId) {
            $project = Project::query()->find($projectId);
            if ($project) {
                $sources[] = [
                    'type' => 'project',
                    'scope' => 'project',
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'box_folder_id' => $project->box_folder_id,
                ];
            }
        }

        if (! empty($templateConfig)) {
            $sources[] = [
                'type' => 'template',
                'scope' => 'agent-template',
                'config' => $templateConfig,
            ];
        }

        return $sources;
    }

    protected function buildAgentCouncil(): array
    {
        $user = Auth::user();
        $today = today();

        $overdueTasks = ProjectTask::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->count();

        $meetingsNeedingNotes = Meeting::query()->needsNotes()->count();

        $projectsNearTarget = Project::query()
            ->whereIn('status', ['planning', 'active'])
            ->whereNotNull('target_end_date')
            ->whereDate('target_end_date', '<=', $today->copy()->addDays(14))
            ->count();

        $fundingDeadlines = Grant::query()
            ->visibleTo($user)
            ->active()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $today->copy()->addDays(45))
            ->count();

        $tripLogisticsGaps = Trip::query()
            ->upcoming()
            ->whereDoesntHave('lodging')
            ->count();

        return [
            [
                'name' => 'Execution Sentinel',
                'status' => $overdueTasks > 0 ? 'watch' : 'active',
                'monitoring' => 'Task deadlines, ownership, and delivery risk',
                'signal_count' => $overdueTasks,
                'next_focus' => $overdueTasks > 0 ? 'Clear overdue tasks' : 'No overdue delivery blockers',
                'last_run_human' => now()->subMinutes(6)->diffForHumans(),
            ],
            [
                'name' => 'Coordination Analyst',
                'status' => $meetingsNeedingNotes > 0 ? 'watch' : 'active',
                'monitoring' => 'Meeting follow-through and missing context',
                'signal_count' => $meetingsNeedingNotes,
                'next_focus' => $meetingsNeedingNotes > 0 ? 'Collect notes from past meetings' : 'Meeting notes are current',
                'last_run_human' => now()->subMinutes(9)->diffForHumans(),
            ],
            [
                'name' => 'Program Horizon',
                'status' => $projectsNearTarget > 0 ? 'watch' : 'active',
                'monitoring' => 'Project timeline compression and sequencing',
                'signal_count' => $projectsNearTarget,
                'next_focus' => $projectsNearTarget > 0 ? 'Review near-term project targets' : 'No immediate timeline conflicts',
                'last_run_human' => now()->subMinutes(11)->diffForHumans(),
            ],
            [
                'name' => 'Funding Radar',
                'status' => $fundingDeadlines > 0 ? 'watch' : 'active',
                'monitoring' => 'Active grant windows and reporting pressure',
                'signal_count' => $fundingDeadlines,
                'next_focus' => $fundingDeadlines > 0 ? 'Prioritize expiring grants' : 'Funding cycle looks stable',
                'last_run_human' => now()->subMinutes(14)->diffForHumans(),
            ],
            [
                'name' => 'Travel Watch',
                'status' => $tripLogisticsGaps > 0 ? 'watch' : 'active',
                'monitoring' => 'Upcoming travel with missing logistics',
                'signal_count' => $tripLogisticsGaps,
                'next_focus' => $tripLogisticsGaps > 0 ? 'Resolve lodging gaps for upcoming trips' : 'Travel logistics look complete',
                'last_run_human' => now()->subMinutes(18)->diffForHumans(),
            ],
        ];
    }

    protected function buildInsightStream(): array
    {
        $user = Auth::user();
        $today = today();
        $insights = [];

        $overdueTasks = ProjectTask::query()
            ->with('project:id,name')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date')
            ->limit(3)
            ->get();

        if ($overdueTasks->isNotEmpty()) {
            $insights[] = [
                'severity' => 'high',
                'agent' => 'Execution Sentinel',
                'title' => $overdueTasks->count().' overdue tasks are currently blocking delivery',
                'summary' => 'Overdue work is clustering in active projects. Resolving these first will reduce downstream slippage.',
                'evidence' => $overdueTasks->map(function (ProjectTask $task): string {
                    $projectName = $task->project?->name ?? 'Unlinked project';
                    $due = $task->due_date ? Carbon::parse($task->due_date)->format('M j') : 'No due date';

                    return "{$task->title} ({$projectName}, due {$due})";
                })->all(),
                'confidence' => 'high',
                'action_label' => 'Review tasks in Workspace',
                'action_url' => route('dashboard'),
            ];
        }

        $meetingsNeedingNotes = Meeting::query()
            ->needsNotes()
            ->orderBy('meeting_date', 'desc')
            ->limit(3)
            ->get(['id', 'title', 'meeting_date']);

        if ($meetingsNeedingNotes->isNotEmpty()) {
            $insights[] = [
                'severity' => 'medium',
                'agent' => 'Coordination Analyst',
                'title' => 'Meeting intelligence is incomplete for '.$meetingsNeedingNotes->count().' recent meetings',
                'summary' => 'Missing notes reduce context quality for follow-ups, project summaries, and agent recommendations.',
                'evidence' => $meetingsNeedingNotes->map(function (Meeting $meeting): string {
                    $date = $meeting->meeting_date ? Carbon::parse($meeting->meeting_date)->format('M j') : 'Unknown date';

                    return "{$meeting->title} ({$date})";
                })->all(),
                'confidence' => 'high',
                'action_label' => 'Open meetings',
                'action_url' => route('meetings.index'),
            ];
        }

        $projectsNearTarget = Project::query()
            ->whereIn('status', ['planning', 'active'])
            ->whereNotNull('target_end_date')
            ->whereDate('target_end_date', '<=', $today->copy()->addDays(14))
            ->orderBy('target_end_date')
            ->limit(3)
            ->get(['id', 'name', 'target_end_date', 'status']);

        if ($projectsNearTarget->isNotEmpty()) {
            $insights[] = [
                'severity' => 'medium',
                'agent' => 'Program Horizon',
                'title' => 'Project timelines are tightening over the next two weeks',
                'summary' => 'A quick sequence review now can prevent deadline collisions across projects and dependencies.',
                'evidence' => $projectsNearTarget->map(function (Project $project): string {
                    $targetDate = $project->target_end_date ? Carbon::parse($project->target_end_date)->format('M j') : 'No target date';

                    return "{$project->name} ({$project->status}, target {$targetDate})";
                })->all(),
                'confidence' => 'medium',
                'action_label' => 'Open projects',
                'action_url' => route('projects.index'),
            ];
        }

        $fundingDeadlines = Grant::query()
            ->visibleTo($user)
            ->active()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $today->copy()->addDays(45))
            ->orderBy('end_date')
            ->limit(3)
            ->get(['id', 'name', 'end_date']);

        if ($fundingDeadlines->isNotEmpty()) {
            $insights[] = [
                'severity' => 'medium',
                'agent' => 'Funding Radar',
                'title' => $fundingDeadlines->count().' active funding windows are approaching end dates',
                'summary' => 'This is a good window to align reporting, documentation, and renewal strategy.',
                'evidence' => $fundingDeadlines->map(function (Grant $grant): string {
                    $endDate = $grant->end_date ? Carbon::parse($grant->end_date)->format('M j') : 'No end date';

                    return "{$grant->name} (ends {$endDate})";
                })->all(),
                'confidence' => 'medium',
                'action_label' => 'Open funding',
                'action_url' => route('funding.index'),
            ];
        }

        $upcomingTripsWithoutLodging = Trip::query()
            ->upcoming()
            ->whereDoesntHave('lodging')
            ->orderBy('start_date')
            ->limit(3)
            ->get(['id', 'name', 'start_date']);

        if ($upcomingTripsWithoutLodging->isNotEmpty()) {
            $insights[] = [
                'severity' => 'low',
                'agent' => 'Travel Watch',
                'title' => 'Some upcoming trips still need lodging details',
                'summary' => 'Flagging these now avoids late-booking cost and coordination overhead.',
                'evidence' => $upcomingTripsWithoutLodging->map(function (Trip $trip): string {
                    $startDate = $trip->start_date ? Carbon::parse($trip->start_date)->format('M j') : 'Unknown';

                    return "{$trip->name} (starts {$startDate})";
                })->all(),
                'confidence' => 'medium',
                'action_label' => 'Open travel',
                'action_url' => route('travel.index'),
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'severity' => 'low',
                'agent' => 'WRK Intelligence',
                'title' => 'No urgent cross-domain risks detected right now',
                'summary' => 'Agents are still watching for timeline conflicts, overdue deliverables, and travel/funding pressure points.',
                'evidence' => ['Signals look stable across projects, meetings, travel, and funding.'],
                'confidence' => 'high',
                'action_label' => 'Go to workspace',
                'action_url' => route('dashboard'),
            ];
        }

        return $insights;
    }

    public function render()
    {
        return view('livewire.intelligence.intelligence-index', [
            'activeAgentCount' => collect($this->agentCouncil)->where('status', 'active')->count(),
            'watchAgentCount' => collect($this->agentCouncil)->where('status', 'watch')->count(),
            'selectedAgent' => $this->selectedAgent(),
        ]);
    }
}
