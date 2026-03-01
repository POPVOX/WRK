<?php

use App\Models\Agent;
use App\Models\AgentGoal;
use App\Models\AgentGoalRun;
use App\Models\User;
use App\Services\Agents\GoalEvaluationService;
use Illuminate\Support\Carbon;

function buildGoal(array $overrides = []): AgentGoal
{
    $user = User::factory()->create([
        'access_level' => 'management',
    ]);

    $agent = Agent::query()->create([
        'name' => 'Scheduler Test Agent',
        'scope' => Agent::SCOPE_SPECIALIST,
        'status' => Agent::STATUS_ACTIVE,
        'specialty' => 'operations',
        'created_by' => $user->id,
        'owner_user_id' => $user->id,
        'is_persistent' => true,
    ]);

    return AgentGoal::query()->create(array_merge([
        'agent_id' => $agent->id,
        'title' => 'Daily Inbox Monitoring',
        'description' => 'Summarize relevant inbox updates.',
        'goal_type' => AgentGoal::TYPE_MONITOR,
        'status' => AgentGoal::STATUS_ACTIVE,
        'trigger_type' => AgentGoal::TRIGGER_CRON,
        'trigger_config' => [
            'expression' => '* * * * *',
            'window_minutes' => 15,
            'timezone' => 'UTC',
        ],
        'output_config' => [
            'channel' => 'wrk_thread',
        ],
        'priority' => 80,
    ], $overrides));
}

afterEach(function () {
    Carbon::setTestNow();
});

test('due goal creates run once per trigger window', function () {
    Carbon::setTestNow('2026-03-02 10:00:00');
    buildGoal();

    $service = app(GoalEvaluationService::class);
    $first = $service->evaluateDueGoals(50);
    $second = $service->evaluateDueGoals(50);

    expect($first['triggered'])->toBe(1);
    expect($second['triggered'])->toBe(0);
    expect($second['duplicates'])->toBe(1);

    $this->assertDatabaseCount('agent_goal_runs', 1);
    $this->assertDatabaseCount('agent_runs', 1);
});

test('paused and completed goals are skipped', function () {
    Carbon::setTestNow('2026-03-02 11:15:00');

    buildGoal([
        'title' => 'Active Goal',
        'status' => AgentGoal::STATUS_ACTIVE,
    ]);
    buildGoal([
        'title' => 'Paused Goal',
        'status' => AgentGoal::STATUS_PAUSED,
    ]);
    buildGoal([
        'title' => 'Completed Goal',
        'status' => AgentGoal::STATUS_COMPLETED,
    ]);

    $summary = app(GoalEvaluationService::class)->evaluateDueGoals(100);

    expect($summary['evaluated'])->toBe(3);
    expect($summary['triggered'])->toBe(1);
    expect($summary['skipped'])->toBe(2);

    $this->assertDatabaseCount('agent_goal_runs', 1);
});

test('duplicate trigger windows are idempotent through command execution', function () {
    Carbon::setTestNow('2026-03-02 12:30:00');
    $goal = buildGoal();

    $this->artisan('agents:evaluate-goals --limit=25')->assertExitCode(0);
    $this->artisan('agents:evaluate-goals --limit=25')->assertExitCode(0);

    $runs = AgentGoalRun::query()->where('goal_id', $goal->id)->get();

    expect($runs)->toHaveCount(1);
    expect((string) $runs->first()->status)->toBe(AgentGoalRun::STATUS_COMPLETED);
});
