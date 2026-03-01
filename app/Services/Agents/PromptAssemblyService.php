<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentPromptLayer;
use App\Models\AgentPromptOverride;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PromptAssemblyService
{
    public function __construct(
        protected PolicyConflictService $policyConflictService
    ) {}

    /**
     * @param  array<string,mixed>  $goalContext
     * @return array{
     *   agent_id:int,
     *   precedence:array<int,string>,
     *   layers:array<string,array<string,mixed>>,
     *   layer_versions:array<string,int|null>,
     *   merged_directives:array<string,string>,
     *   overrides:array<int,array<string,mixed>>,
     *   conflicts:array<int,array<string,mixed>>,
     *   diagnostics:array<int,array<string,string>>,
     *   effective_prompt:string,
     *   effective_prompt_hash:string
     * }
     */
    public function assembleForAgent(Agent $agent, ?User $actor = null, array $goalContext = []): array
    {
        $layers = $this->resolveLayerSnapshots($agent);
        $directiveSets = [
            'org' => $this->parseDirectiveMap((string) ($layers['org']['content'] ?? '')),
            'role' => $this->parseDirectiveMap((string) ($layers['role']['content'] ?? '')),
            'personal' => $this->parseDirectiveMap((string) ($layers['personal']['content'] ?? '')),
        ];

        [$directiveSets, $overrides] = $this->applyOverrides($agent, $directiveSets);

        $conflicts = $this->policyConflictService->detectLayerConflicts($directiveSets);
        $diagnostics = $this->policyConflictService->buildDiagnostics($conflicts);

        $mergedDirectives = $this->mergeDirectives($directiveSets);
        $effectivePrompt = $this->renderEffectivePrompt(
            $agent,
            $layers,
            $mergedDirectives,
            $goalContext,
            $diagnostics,
            $actor
        );

        return [
            'agent_id' => (int) $agent->id,
            'precedence' => ['org', 'role', 'personal'],
            'layers' => $layers,
            'layer_versions' => [
                'org' => $layers['org']['version'],
                'role' => $layers['role']['version'],
                'personal' => $layers['personal']['version'],
            ],
            'merged_directives' => $mergedDirectives,
            'overrides' => $overrides,
            'conflicts' => $conflicts,
            'diagnostics' => $diagnostics,
            'effective_prompt' => $effectivePrompt,
            'effective_prompt_hash' => hash('sha256', $effectivePrompt),
        ];
    }

    /**
     * @return array{
     *   org:array<string,mixed>,
     *   role:array<string,mixed>,
     *   personal:array<string,mixed>
     * }
     */
    protected function resolveLayerSnapshots(Agent $agent): array
    {
        $empty = [
            'id' => null,
            'layer_type' => null,
            'content' => '',
            'version' => null,
            'updated_by' => null,
            'updated_at' => null,
            'source' => null,
        ];

        if (! Schema::hasTable('agent_prompt_layers')) {
            $personalFallback = trim((string) $agent->instructions);

            return [
                'org' => $empty,
                'role' => $empty,
                'personal' => array_merge($empty, [
                    'layer_type' => AgentPromptLayer::LAYER_PERSONAL,
                    'content' => $personalFallback,
                    'source' => $personalFallback !== '' ? 'agent.instructions' : null,
                ]),
            ];
        }

        $org = AgentPromptLayer::query()
            ->whereNull('agent_id')
            ->where('layer_type', AgentPromptLayer::LAYER_ORG)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();

        $roleSpecific = AgentPromptLayer::query()
            ->where('agent_id', $agent->id)
            ->where('layer_type', AgentPromptLayer::LAYER_ROLE)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();

        $roleGlobal = AgentPromptLayer::query()
            ->whereNull('agent_id')
            ->where('layer_type', AgentPromptLayer::LAYER_ROLE)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();

        $personal = AgentPromptLayer::query()
            ->where('agent_id', $agent->id)
            ->where('layer_type', AgentPromptLayer::LAYER_PERSONAL)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();

        $role = $roleSpecific ?: $roleGlobal;

        $personalFallback = trim((string) $agent->instructions);
        $personalSnapshot = $personal
            ? $this->toSnapshot($personal, 'table')
            : array_merge($empty, [
                'layer_type' => AgentPromptLayer::LAYER_PERSONAL,
                'content' => $personalFallback,
                'source' => $personalFallback !== '' ? 'agent.instructions' : null,
            ]);

        return [
            'org' => $org ? $this->toSnapshot($org, 'table') : $empty,
            'role' => $role ? $this->toSnapshot($role, 'table') : $empty,
            'personal' => $personalSnapshot,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function toSnapshot(AgentPromptLayer $layer, string $source): array
    {
        return [
            'id' => (int) $layer->id,
            'layer_type' => $layer->layer_type,
            'content' => (string) $layer->content,
            'version' => (int) $layer->version,
            'updated_by' => $layer->updated_by ? (int) $layer->updated_by : null,
            'updated_at' => $layer->updated_at?->toIso8601String(),
            'source' => $source,
        ];
    }

    /**
     * @return array<string,string>
     */
    protected function parseDirectiveMap(string $content): array
    {
        $map = [];
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];

        foreach ($lines as $line) {
            if (! preg_match('/^\s*([a-zA-Z][a-zA-Z0-9_.-]{1,119})\s*:\s*(.+?)\s*$/', (string) $line, $matches)) {
                continue;
            }

            $key = Str::lower(trim((string) $matches[1]));
            $value = trim((string) $matches[2]);
            if ($key === '' || $value === '') {
                continue;
            }

            $map[$key] = $value;
        }

        ksort($map);

        return $map;
    }

    /**
     * @param  array{
     *   org:array<string,string>,
     *   role:array<string,string>,
     *   personal:array<string,string>
     * }  $directiveSets
     * @return array{
     *   0:array{
     *     org:array<string,string>,
     *     role:array<string,string>,
     *     personal:array<string,string>
     *   },
     *   1:array<int,array<string,mixed>>
     * }
     */
    protected function applyOverrides(Agent $agent, array $directiveSets): array
    {
        if (! Schema::hasTable('agent_prompt_overrides')) {
            return [$directiveSets, []];
        }

        $overrides = AgentPromptOverride::query()
            ->where('agent_id', $agent->id)
            ->orderBy('override_key')
            ->get();

        $applied = [];
        foreach ($overrides as $override) {
            $key = Str::lower(trim((string) $override->override_key));
            if ($key === '') {
                continue;
            }

            $sourceLayer = Str::lower(trim((string) $override->source_layer));
            if (! in_array($sourceLayer, ['org', 'role', 'personal'], true)) {
                $sourceLayer = 'personal';
            }

            $value = $this->stringifyValue($override->override_value);
            if ($value === '') {
                continue;
            }

            $directiveSets[$sourceLayer][$key] = $value;
            $applied[] = [
                'id' => (int) $override->id,
                'source_layer' => $sourceLayer,
                'override_key' => $key,
                'override_value' => $value,
                'updated_at' => $override->updated_at?->toIso8601String(),
            ];
        }

        foreach (['org', 'role', 'personal'] as $layer) {
            ksort($directiveSets[$layer]);
        }

        return [$directiveSets, $applied];
    }

    /**
     * @param  array{
     *   org:array<string,string>,
     *   role:array<string,string>,
     *   personal:array<string,string>
     * }  $directiveSets
     * @return array<string,string>
     */
    protected function mergeDirectives(array $directiveSets): array
    {
        $merged = $directiveSets['personal'];
        $merged = array_replace($merged, $directiveSets['role']);
        $merged = array_replace($merged, $directiveSets['org']);
        ksort($merged);

        return $merged;
    }

    /**
     * @param  array<string,mixed>  $layers
     * @param  array<string,string>  $mergedDirectives
     * @param  array<string,mixed>  $goalContext
     * @param  array<int,array<string,string>>  $diagnostics
     */
    protected function renderEffectivePrompt(
        Agent $agent,
        array $layers,
        array $mergedDirectives,
        array $goalContext,
        array $diagnostics,
        ?User $actor
    ): string {
        $lines = [];
        $lines[] = 'Agent: '.$agent->name;
        $lines[] = 'Precedence: org > role > personal';

        if ($actor) {
            $lines[] = 'Requested by: '.$actor->name.' <'.$actor->email.'>';
        }

        $mission = trim((string) $agent->mission);
        if ($mission !== '') {
            $lines[] = '';
            $lines[] = 'Mission';
            $lines[] = $mission;
        }

        $lines[] = '';
        $lines[] = 'Effective Directives';
        if (empty($mergedDirectives)) {
            $lines[] = '- none';
        } else {
            foreach ($mergedDirectives as $key => $value) {
                $lines[] = '- '.$key.': '.$value;
            }
        }

        $lines[] = '';
        $lines[] = 'Layer Content';
        foreach (['org', 'role', 'personal'] as $layerType) {
            $snapshot = $layers[$layerType] ?? [];
            $version = Arr::get($snapshot, 'version');
            $content = trim((string) Arr::get($snapshot, 'content', ''));
            $label = strtoupper($layerType).' v'.($version ?? 0);
            $lines[] = '['.$label.']';
            $lines[] = $content !== '' ? $content : '(empty)';
            $lines[] = '';
        }

        if (! empty($goalContext)) {
            $lines[] = 'Goal Context';
            foreach ($goalContext as $key => $value) {
                $normalized = Str::snake((string) $key);
                $lines[] = '- '.$normalized.': '.$this->stringifyValue($value);
            }
            $lines[] = '';
        }

        if (! empty($diagnostics)) {
            $lines[] = 'Diagnostics';
            foreach ($diagnostics as $diagnostic) {
                $lines[] = '- ['.$diagnostic['severity'].'] '.$diagnostic['message'];
            }
        }

        return trim(implode("\n", $lines));
    }

    protected function stringifyValue(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        try {
            return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return '[unserializable]';
        }
    }
}
