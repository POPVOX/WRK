<?php

use App\Models\Agent;
use App\Models\AgentSuggestion;
use App\Models\ApprovalRequest;
use App\Models\Trip;
use App\Models\User;
use App\Services\Agents\AgentOrchestratorService;
use App\Services\TripAgentService;

test('trip agent action is held for approval for non-management users', function () {
    config()->set('ai.enabled', false);
    config()->set('approvals.enabled', true);
    config()->set('approvals.risk_map.trip.auto_apply', 'medium');

    $user = User::factory()->create([
        'access_level' => 'staff',
    ]);

    $trip = Trip::query()->create([
        'name' => 'Policy Fly-In',
        'type' => 'other',
        'status' => 'planning',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-03',
        'primary_destination_city' => 'Washington',
        'primary_destination_country' => 'US',
        'created_by' => $user->id,
    ]);

    $result = app(TripAgentService::class)->proposeChanges(
        $trip,
        $user,
        'Please move this trip to 2026-04-10 through 2026-04-12 and update hotel plans.'
    );

    $action = $result['action'];
    expect($action)->not->toBeNull();
    expect($action->status)->toBe('pending');
    expect($action->approval_request_id)->not->toBeNull();

    $approvalRequest = ApprovalRequest::query()->find($action->approval_request_id);
    expect($approvalRequest)->not->toBeNull();
    expect($approvalRequest->approval_status)->toBe(ApprovalRequest::STATUS_PENDING);
    expect($approvalRequest->action_type)->toBe('trip.auto_apply');
});

test('autonomous agent execution is blocked by approval gate for medium-risk work', function () {
    config()->set('approvals.enabled', true);
    config()->set('approvals.risk_map.agent.autonomous_execute', 'medium');

    $actor = User::factory()->create([
        'access_level' => 'staff',
    ]);

    $agent = Agent::query()->create([
        'name' => 'Ops Agent',
        'scope' => 'specialist',
        'status' => 'active',
        'specialty' => 'operations',
        'created_by' => $actor->id,
        'owner_user_id' => $actor->id,
        'governance_tiers' => [
            'low' => 'autonomous',
            'medium' => 'autonomous',
            'high' => 'management_approval',
        ],
        'autonomy_mode' => 'tiered',
        'is_persistent' => true,
    ]);

    $result = app(AgentOrchestratorService::class)->direct(
        $agent,
        $actor,
        'Draft an email to the board summarizing funding risks this quarter.'
    );

    expect(is_array($result['auto_executed'] ?? null) ? count($result['auto_executed']) : 0)->toBe(0);

    $suggestion = AgentSuggestion::query()
        ->where('run_id', $result['run']->id)
        ->first();

    expect($suggestion)->not->toBeNull();
    expect($suggestion->approval_status)->toBe(AgentSuggestion::STATUS_PENDING);
    expect($suggestion->approval_request_id)->not->toBeNull();

    $approvalRequest = ApprovalRequest::query()->find($suggestion->approval_request_id);
    expect($approvalRequest)->not->toBeNull();
    expect($approvalRequest->approval_status)->toBe(ApprovalRequest::STATUS_PENDING);
    expect($approvalRequest->action_type)->toBe('agent.autonomous_execute');
});
