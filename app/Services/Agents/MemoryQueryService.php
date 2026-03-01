<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentMemory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MemoryQueryService
{
    public function __construct(
        protected VisibilityPolicyService $visibilityPolicyService
    ) {}

    /**
     * @return Collection<int,AgentMemory>
     */
    public function queryForAgent(
        Agent $agent,
        User $actor,
        string $query = '',
        int $limit = 20,
        bool $includeOrganizationPublic = true
    ): Collection {
        if (! Schema::hasTable('agent_memory')) {
            return collect();
        }

        $limit = max(1, min($limit, 100));
        $maxScan = max(40, $limit * 5);
        $queryLower = Str::lower(trim($query));
        $includeOrg = $includeOrganizationPublic && $this->visibilityPolicyService->canQueryOrganizationPublicMemory($actor);

        $candidates = AgentMemory::query()
            ->with('agent:id,name,created_by,owner_user_id,staffer_id')
            ->where(function ($builder) use ($agent, $includeOrg) {
                $builder->where('agent_id', $agent->id);

                if ($includeOrg) {
                    $builder->orWhere('visibility', AgentMemory::VISIBILITY_PUBLIC);
                }
            })
            ->latest('created_at')
            ->limit($maxScan)
            ->get();

        $filtered = $candidates->filter(function (AgentMemory $memory) use ($actor, $queryLower): bool {
            if (! $this->visibilityPolicyService->canViewMemory($actor, $memory)) {
                return false;
            }

            if ($queryLower === '') {
                return true;
            }

            return str_contains(Str::lower($this->memoryText($memory)), $queryLower);
        });

        return $filtered
            ->sortByDesc(fn (AgentMemory $memory) => $memory->created_at?->getTimestamp() ?? 0)
            ->values()
            ->take($limit);
    }

    protected function memoryText(AgentMemory $memory): string
    {
        $content = is_array($memory->content) ? $memory->content : [];
        $text = trim((string) ($content['text'] ?? ''));
        if ($text !== '') {
            return $text;
        }

        return trim((string) ($content['summary'] ?? ''));
    }
}
