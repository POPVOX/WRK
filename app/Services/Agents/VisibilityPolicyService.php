<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentMemory;
use App\Models\AgentPermission;
use App\Models\AgentThread;
use App\Models\User;

class VisibilityPolicyService
{
    public function canViewThread(User $actor, AgentThread $thread): bool
    {
        $thread->loadMissing('agent');
        if (! $thread->agent) {
            return false;
        }

        if ($thread->visibility === AgentThread::VISIBILITY_PUBLIC) {
            return $this->canViewPublicAgentData($actor, $thread->agent);
        }

        return $this->canViewPrivateAgentData($actor, $thread->agent, $thread->user_id);
    }

    public function canToggleThreadVisibility(User $actor, AgentThread $thread): bool
    {
        $thread->loadMissing('agent');
        if (! $thread->agent) {
            return false;
        }

        if ($this->canManageAgent($actor, $thread->agent)) {
            return true;
        }

        return (int) $thread->user_id === (int) $actor->id;
    }

    public function canViewMemory(User $actor, AgentMemory $memory): bool
    {
        $memory->loadMissing('agent');
        if (! $memory->agent) {
            return false;
        }

        if ($memory->visibility === AgentMemory::VISIBILITY_PUBLIC) {
            return $this->canViewPublicAgentData($actor, $memory->agent);
        }

        return $this->canViewPrivateAgentData($actor, $memory->agent, null);
    }

    public function canQueryOrganizationPublicMemory(User $actor): bool
    {
        if ($actor->isManagement()) {
            return true;
        }

        $permission = AgentPermission::query()->where('user_id', $actor->id)->first();
        if (! $permission) {
            return false;
        }

        return (bool) ($permission->can_create_specialist || $permission->can_create_project);
    }

    protected function canViewPublicAgentData(User $actor, Agent $agent): bool
    {
        if ($this->canManageAgent($actor, $agent)) {
            return true;
        }

        return $this->canQueryOrganizationPublicMemory($actor);
    }

    protected function canViewPrivateAgentData(User $actor, Agent $agent, ?int $threadUserId): bool
    {
        if ($threadUserId !== null && (int) $threadUserId === (int) $actor->id) {
            return true;
        }

        return $this->canManageAgent($actor, $agent);
    }

    protected function canManageAgent(User $actor, Agent $agent): bool
    {
        if ($actor->isManagement()) {
            return true;
        }

        return (int) $agent->created_by === (int) $actor->id
            || (int) ($agent->owner_user_id ?? 0) === (int) $actor->id
            || (int) ($agent->staffer_id ?? 0) === (int) $actor->id;
    }
}
