<?php

use App\Models\Agent;
use App\Models\AgentPromptLayer;
use App\Models\User;
use App\Services\Agents\PromptAssemblyService;

test('org-level prohibition cannot be overridden by lower layers', function () {
    $actor = User::factory()->create([
        'access_level' => 'staff',
    ]);

    $agent = Agent::query()->create([
        'name' => 'Policy Guard Agent',
        'scope' => Agent::SCOPE_SPECIALIST,
        'status' => Agent::STATUS_ACTIVE,
        'specialty' => 'policy',
        'created_by' => $actor->id,
        'owner_user_id' => $actor->id,
        'is_persistent' => true,
    ]);

    AgentPromptLayer::query()->create([
        'agent_id' => null,
        'layer_type' => AgentPromptLayer::LAYER_ORG,
        'content' => "policy.external_send: requires_management_approval\nstyle.tone: formal",
        'version' => 1,
        'updated_by' => $actor->id,
    ]);

    AgentPromptLayer::query()->create([
        'agent_id' => null,
        'layer_type' => AgentPromptLayer::LAYER_ROLE,
        'content' => "policy.external_send: allowed\nstyle.tone: collaborative",
        'version' => 1,
        'updated_by' => $actor->id,
    ]);

    AgentPromptLayer::query()->create([
        'agent_id' => $agent->id,
        'layer_type' => AgentPromptLayer::LAYER_PERSONAL,
        'content' => "policy.external_send: allowed\nstyle.tone: casual",
        'version' => 1,
        'updated_by' => $actor->id,
    ]);

    $preview = app(PromptAssemblyService::class)->assembleForAgent($agent, $actor, [
        'directive' => 'draft a board update',
    ]);

    expect($preview['merged_directives']['policy.external_send'] ?? null)->toBe('requires_management_approval');
    expect($preview['merged_directives']['style.tone'] ?? null)->toBe('formal');
    expect(collect($preview['diagnostics'])->where('code', 'prohibited_override')->count())->toBeGreaterThan(0);
});

test('role layer overrides personal layer when org does not set key', function () {
    $actor = User::factory()->create([
        'access_level' => 'staff',
    ]);

    $agent = Agent::query()->create([
        'name' => 'Comms Agent',
        'scope' => Agent::SCOPE_SPECIALIST,
        'status' => Agent::STATUS_ACTIVE,
        'specialty' => 'communications',
        'created_by' => $actor->id,
        'owner_user_id' => $actor->id,
        'is_persistent' => true,
    ]);

    AgentPromptLayer::query()->create([
        'agent_id' => null,
        'layer_type' => AgentPromptLayer::LAYER_ROLE,
        'content' => 'style.reply_length: concise',
        'version' => 1,
        'updated_by' => $actor->id,
    ]);

    AgentPromptLayer::query()->create([
        'agent_id' => $agent->id,
        'layer_type' => AgentPromptLayer::LAYER_PERSONAL,
        'content' => 'style.reply_length: verbose',
        'version' => 1,
        'updated_by' => $actor->id,
    ]);

    $preview = app(PromptAssemblyService::class)->assembleForAgent($agent, $actor);

    expect($preview['merged_directives']['style.reply_length'] ?? null)->toBe('concise');
});

test('admin prompt preview endpoint returns effective merged content', function () {
    $admin = User::factory()->admin()->create();

    $agent = Agent::query()->create([
        'name' => 'Operations Agent',
        'scope' => Agent::SCOPE_SPECIALIST,
        'status' => Agent::STATUS_ACTIVE,
        'specialty' => 'operations',
        'created_by' => $admin->id,
        'owner_user_id' => $admin->id,
        'instructions' => 'style.reply_length: medium',
        'is_persistent' => true,
    ]);

    AgentPromptLayer::query()->create([
        'agent_id' => null,
        'layer_type' => AgentPromptLayer::LAYER_ORG,
        'content' => 'policy.no_external_send: required_approval',
        'version' => 1,
        'updated_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.agents.prompt-preview', ['agent' => $agent->id]));

    $response->assertOk()
        ->assertJsonPath('data.agent_id', $agent->id);

    $mergedDirectives = (array) $response->json('data.merged_directives');
    expect($mergedDirectives['policy.no_external_send'] ?? null)->toBe('required_approval');

    expect((string) $response->json('data.effective_prompt'))->toContain('policy.no_external_send: required_approval');
});
