<?php

use App\Models\Agent;
use App\Models\AgentMemory;
use App\Models\AgentThread;
use App\Models\User;
use App\Services\Agents\MemoryQueryService;

function makeVisibilityAgent(User $user, string $name): Agent
{
    return Agent::query()->create([
        'name' => $name,
        'scope' => Agent::SCOPE_SPECIALIST,
        'status' => Agent::STATUS_ACTIVE,
        'specialty' => 'operations',
        'created_by' => $user->id,
        'owner_user_id' => $user->id,
        'staffer_id' => $user->id,
        'is_persistent' => true,
    ]);
}

test('private thread and memory are not returned in cross-agent query', function () {
    $ownerA = User::factory()->create(['access_level' => 'staff']);
    $ownerB = User::factory()->create(['access_level' => 'staff']);

    $agentA = makeVisibilityAgent($ownerA, 'Private Memory Agent');
    $agentB = makeVisibilityAgent($ownerB, 'Query Agent');

    AgentThread::query()->create([
        'agent_id' => $agentA->id,
        'user_id' => $ownerA->id,
        'title' => 'Private Thread',
        'visibility' => AgentThread::VISIBILITY_PRIVATE,
    ]);

    $privateMemory = AgentMemory::query()->create([
        'agent_id' => $agentA->id,
        'memory_type' => 'fact',
        'content' => ['text' => 'Private note about donor outreach cadence.'],
        'visibility' => AgentMemory::VISIBILITY_PRIVATE,
        'confidence' => 0.91,
    ]);

    $results = app(MemoryQueryService::class)->queryForAgent(
        $agentB,
        $ownerB,
        'donor',
        20,
        true
    );

    expect($results->pluck('id')->contains($privateMemory->id))->toBeFalse();
});

test('public memory is queryable organization-wide for authorized role', function () {
    $ownerA = User::factory()->create(['access_level' => 'staff']);
    $manager = User::factory()->create(['access_level' => 'management']);

    $agentA = makeVisibilityAgent($ownerA, 'Institutional Memory Agent');
    $managerAgent = makeVisibilityAgent($manager, 'Manager Agent');

    $publicMemory = AgentMemory::query()->create([
        'agent_id' => $agentA->id,
        'memory_type' => 'decision',
        'content' => ['text' => 'Public institutional memory about weekly leadership update format.'],
        'visibility' => AgentMemory::VISIBILITY_PUBLIC,
        'confidence' => 0.83,
    ]);

    $results = app(MemoryQueryService::class)->queryForAgent(
        $managerAgent,
        $manager,
        'leadership update',
        20,
        true
    );

    expect($results->pluck('id')->contains($publicMemory->id))->toBeTrue();
});
