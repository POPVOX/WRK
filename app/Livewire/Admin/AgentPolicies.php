<?php

namespace App\Livewire\Admin;

use App\Models\Agent;
use App\Models\AgentPromptLayer;
use App\Services\Agents\PromptAssemblyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Agent Policies')]
class AgentPolicies extends Component
{
    public bool $migrationReady = false;

    public string $migrationMessage = '';

    public string $generatedAt = '';

    public array $agentOptions = [];

    public ?int $selectedAgentId = null;

    public string $orgLayerContent = '';

    public string $roleLayerContent = '';

    public array $orgHistory = [];

    public array $roleHistory = [];

    public array $personalHistory = [];

    public array $promptPreview = [];

    public function mount(): void
    {
        $this->migrationReady = $this->hasPromptSchema();
        if (! $this->migrationReady) {
            $this->migrationMessage = 'Prompt layering tables are not available yet. Run migrations to enable policy editing.';
            $this->generatedAt = now()->format('M j, Y g:i A');

            return;
        }

        $this->refreshData();
    }

    public function refreshData(): void
    {
        if (! $this->migrationReady) {
            return;
        }

        $this->loadAgentOptions();
        $this->loadEditableLayers();
        $this->loadPersonalHistory();
        $this->loadPromptPreview();
        $this->generatedAt = now()->format('M j, Y g:i A');
    }

    public function updatedSelectedAgentId(): void
    {
        $this->loadPersonalHistory();
        $this->loadPromptPreview();
    }

    public function saveOrgLayer(): void
    {
        if (! $this->migrationReady) {
            return;
        }

        $validated = $this->validate([
            'orgLayerContent' => ['required', 'string', 'max:20000'],
        ]);

        $version = ((int) AgentPromptLayer::query()
            ->whereNull('agent_id')
            ->where('layer_type', AgentPromptLayer::LAYER_ORG)
            ->max('version')) + 1;

        AgentPromptLayer::query()->create([
            'agent_id' => null,
            'layer_type' => AgentPromptLayer::LAYER_ORG,
            'content' => trim($validated['orgLayerContent']),
            'version' => $version,
            'updated_by' => Auth::id(),
        ]);

        $this->loadEditableLayers();
        $this->loadPromptPreview();
        $this->generatedAt = now()->format('M j, Y g:i A');
        $this->dispatch('notify', type: 'success', message: 'Organization layer saved as version '.$version.'.');
    }

    public function saveRoleLayer(): void
    {
        if (! $this->migrationReady) {
            return;
        }

        $validated = $this->validate([
            'roleLayerContent' => ['required', 'string', 'max:20000'],
        ]);

        $version = ((int) AgentPromptLayer::query()
            ->whereNull('agent_id')
            ->where('layer_type', AgentPromptLayer::LAYER_ROLE)
            ->max('version')) + 1;

        AgentPromptLayer::query()->create([
            'agent_id' => null,
            'layer_type' => AgentPromptLayer::LAYER_ROLE,
            'content' => trim($validated['roleLayerContent']),
            'version' => $version,
            'updated_by' => Auth::id(),
        ]);

        $this->loadEditableLayers();
        $this->loadPromptPreview();
        $this->generatedAt = now()->format('M j, Y g:i A');
        $this->dispatch('notify', type: 'success', message: 'Role layer saved as version '.$version.'.');
    }

    protected function loadAgentOptions(): void
    {
        $this->agentOptions = Agent::query()
            ->orderBy('name')
            ->get(['id', 'name', 'scope', 'specialty'])
            ->map(static function (Agent $agent): array {
                return [
                    'id' => (int) $agent->id,
                    'name' => $agent->name,
                    'scope' => $agent->scope,
                    'specialty' => $agent->specialty,
                ];
            })
            ->values()
            ->all();

        if ($this->selectedAgentId && collect($this->agentOptions)->contains(fn (array $agent) => (int) $agent['id'] === (int) $this->selectedAgentId)) {
            return;
        }

        $this->selectedAgentId = ! empty($this->agentOptions)
            ? (int) $this->agentOptions[0]['id']
            : null;
    }

    protected function loadEditableLayers(): void
    {
        $orgQuery = AgentPromptLayer::query()
            ->whereNull('agent_id')
            ->where('layer_type', AgentPromptLayer::LAYER_ORG);

        $roleQuery = AgentPromptLayer::query()
            ->whereNull('agent_id')
            ->where('layer_type', AgentPromptLayer::LAYER_ROLE);

        $orgCurrent = (clone $orgQuery)->orderByDesc('version')->orderByDesc('id')->first();
        $roleCurrent = (clone $roleQuery)->orderByDesc('version')->orderByDesc('id')->first();

        $this->orgLayerContent = (string) ($orgCurrent?->content ?? '');
        $this->roleLayerContent = (string) ($roleCurrent?->content ?? '');

        $this->orgHistory = (clone $orgQuery)
            ->with('updatedByUser:id,name')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(static function (AgentPromptLayer $layer): array {
                return [
                    'id' => (int) $layer->id,
                    'version' => (int) $layer->version,
                    'content' => (string) $layer->content,
                    'updated_by' => $layer->updatedByUser?->name,
                    'updated_at' => $layer->updated_at?->format('M j, Y g:i A'),
                ];
            })
            ->values()
            ->all();

        $this->roleHistory = (clone $roleQuery)
            ->with('updatedByUser:id,name')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(static function (AgentPromptLayer $layer): array {
                return [
                    'id' => (int) $layer->id,
                    'version' => (int) $layer->version,
                    'content' => (string) $layer->content,
                    'updated_by' => $layer->updatedByUser?->name,
                    'updated_at' => $layer->updated_at?->format('M j, Y g:i A'),
                ];
            })
            ->values()
            ->all();
    }

    protected function loadPersonalHistory(): void
    {
        $this->personalHistory = [];

        if (! $this->selectedAgentId) {
            return;
        }

        $agent = Agent::query()->find($this->selectedAgentId);
        if (! $agent) {
            return;
        }

        $history = AgentPromptLayer::query()
            ->where('agent_id', $agent->id)
            ->where('layer_type', AgentPromptLayer::LAYER_PERSONAL)
            ->with('updatedByUser:id,name')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(static function (AgentPromptLayer $layer): array {
                return [
                    'id' => (int) $layer->id,
                    'version' => (int) $layer->version,
                    'content' => (string) $layer->content,
                    'updated_by' => $layer->updatedByUser?->name,
                    'updated_at' => $layer->updated_at?->format('M j, Y g:i A'),
                    'source' => 'table',
                ];
            })
            ->values()
            ->all();

        if (empty($history)) {
            $fallback = trim((string) $agent->instructions);
            if ($fallback !== '') {
                $history[] = [
                    'id' => 0,
                    'version' => 0,
                    'content' => $fallback,
                    'updated_by' => 'Agent configuration',
                    'updated_at' => $agent->updated_at?->format('M j, Y g:i A'),
                    'source' => 'agent.instructions',
                ];
            }
        }

        $this->personalHistory = $history;
    }

    protected function loadPromptPreview(): void
    {
        $this->promptPreview = [];

        if (! $this->selectedAgentId) {
            return;
        }

        $agent = Agent::query()->find($this->selectedAgentId);
        if (! $agent) {
            return;
        }

        $this->promptPreview = app(PromptAssemblyService::class)->assembleForAgent($agent, Auth::user(), [
            'source' => 'admin.agent_policies_page',
        ]);
    }

    protected function hasPromptSchema(): bool
    {
        return Schema::hasTable('agent_prompt_layers')
            && Schema::hasTable('agent_prompt_overrides')
            && Schema::hasTable('agents');
    }

    public function render()
    {
        return view('livewire.admin.agent-policies');
    }
}
