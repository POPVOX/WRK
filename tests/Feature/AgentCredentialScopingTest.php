<?php

use App\Models\Agent;
use App\Models\User;
use App\Services\Agents\AgentCredentialService;

function buildScopedAgent(User $user, string $name): Agent
{
    return Agent::query()->create([
        'name' => $name,
        'scope' => Agent::SCOPE_SPECIALIST,
        'status' => Agent::STATUS_ACTIVE,
        'specialty' => 'ops',
        'created_by' => $user->id,
        'owner_user_id' => $user->id,
        'staffer_id' => $user->id,
        'is_persistent' => true,
    ]);
}

test('agent cannot use another agents credentials', function () {
    $userA = User::factory()->create(['access_level' => 'management']);
    $userB = User::factory()->create(['access_level' => 'management']);
    $agentA = buildScopedAgent($userA, 'Agent A');
    $agentB = buildScopedAgent($userB, 'Agent B');

    $service = app(AgentCredentialService::class);
    $service->storeCredential(
        $agentA,
        'gmail',
        [
            'access_token' => 'token-a',
            'refresh_token' => 'refresh-a',
        ],
        ['gmail.readonly'],
        now()->addHour()
    );

    $tokenA = $service->getTokenData($agentA, 'gmail');
    $tokenB = $service->getTokenData($agentB, 'gmail');

    expect($tokenA['access_token'] ?? null)->toBe('token-a');
    expect($tokenB)->toBeNull();
});

test('missing agent credentials fail closed with explicit error', function () {
    $user = User::factory()->create(['access_level' => 'management']);
    $agent = buildScopedAgent($user, 'No Credential Agent');

    $service = app(AgentCredentialService::class);

    expect(fn () => $service->requireTokenData($agent, 'gmail'))
        ->toThrow(\RuntimeException::class, 'Missing gmail credentials for agent');
});
