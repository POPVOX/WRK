<?php

namespace App\Services\Agents;

use App\Models\Agent;
use App\Models\AgentGoal;
use App\Models\AgentGoalContext;
use App\Models\AgentGoalRun;
use App\Models\AgentMessage;
use App\Models\AgentRun;
use App\Models\AgentThread;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoalEvaluationService
{
    protected ?bool $threadVisibilityColumnExists = null;

    protected ?bool $messageVisibilityColumnExists = null;

    public function __construct(
        protected GoalTriggerService $goalTriggerService,
        protected GoalOutputRouterService $goalOutputRouterService
    ) {}

    /**
     * @return array{
     *   schema_ready:bool,
     *   evaluated:int,
     *   due:int,
     *   triggered:int,
     *   skipped:int,
     *   duplicates:int,
     *   failed:int,
     *   evaluated_at:string
     * }
     */
    public function evaluateDueGoals(int $limit = 100, ?CarbonInterface $now = null): array
    {
        $tickAt = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();
        $limit = max(1, min($limit, 500));
        $summary = [
            'schema_ready' => false,
            'evaluated' => 0,
            'due' => 0,
            'triggered' => 0,
            'skipped' => 0,
            'duplicates' => 0,
            'failed' => 0,
            'evaluated_at' => $tickAt->toIso8601String(),
        ];

        if (! $this->hasGoalSchema()) {
            return $summary;
        }

        $summary['schema_ready'] = true;

        $goals = AgentGoal::query()
            ->with(['agent:id,name,status,created_by,owner_user_id'])
            ->whereIn('status', [
                AgentGoal::STATUS_ACTIVE,
                AgentGoal::STATUS_PAUSED,
                AgentGoal::STATUS_COMPLETED,
                AgentGoal::STATUS_DRAFT,
            ])
            ->whereHas('agent', fn ($query) => $query->where('status', Agent::STATUS_ACTIVE))
            ->orderByDesc('priority')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($goals as $goal) {
            $summary['evaluated']++;

            if ($goal->status !== AgentGoal::STATUS_ACTIVE) {
                $summary['skipped']++;
                continue;
            }

            $trigger = $this->goalTriggerService->evaluate($goal, $tickAt);
            if (! ($trigger['due'] ?? false)) {
                $summary['skipped']++;
                continue;
            }

            $summary['due']++;
            $idempotencyKey = trim((string) ($trigger['idempotency_key'] ?? ''));
            if ($idempotencyKey === '') {
                $idempotencyKey = hash('sha256', $goal->id.'|'.$tickAt->format('YmdHi'));
            }

            $goalRun = $this->firstOrCreateGoalRun($goal, $idempotencyKey, $trigger, $tickAt);
            if (! $goalRun || ! $goalRun->wasRecentlyCreated) {
                $summary['duplicates']++;
                continue;
            }

            $summary['triggered']++;
            $goalRun->update(['status' => AgentGoalRun::STATUS_RUNNING]);

            try {
                $agentRun = $this->createAgentRun($goal, $goalRun, $tickAt, (string) ($trigger['trigger_reason'] ?? 'scheduled trigger'));
                $route = $this->goalOutputRouterService->route($goal, $goalRun, $agentRun, [
                    'trigger' => $trigger,
                    'evaluated_at' => $tickAt->toIso8601String(),
                ]);

                $agentRun->update([
                    'status' => 'completed',
                    'result_summary' => $route['summary'],
                    'reasoning_chain' => [
                        'goal_id='.$goal->id,
                        'goal_type='.$goal->goal_type,
                        'trigger_type='.$goal->trigger_type,
                        'trigger_reason='.(string) ($trigger['trigger_reason'] ?? 'n/a'),
                        'output_channel='.$route['channel'],
                    ],
                    'alternatives_considered' => [],
                    'completed_at' => $tickAt,
                ]);

                $goalRun->update([
                    'agent_run_id' => $agentRun->id,
                    'status' => AgentGoalRun::STATUS_COMPLETED,
                    'output_summary' => $route['summary'],
                    'completed_at' => $tickAt,
                ]);

                $this->persistGoalContext($goal, $goalRun, $agentRun, $route['summary'], $tickAt);
            } catch (\Throwable $exception) {
                report($exception);
                $summary['failed']++;

                $goalRun->update([
                    'status' => AgentGoalRun::STATUS_FAILED,
                    'output_summary' => Str::limit($exception->getMessage(), 1000),
                    'completed_at' => $tickAt,
                ]);
            }
        }

        return $summary;
    }

    /**
     * @param  array<string,mixed>  $trigger
     */
    protected function firstOrCreateGoalRun(
        AgentGoal $goal,
        string $idempotencyKey,
        array $trigger,
        CarbonImmutable $tickAt
    ): ?AgentGoalRun {
        try {
            return AgentGoalRun::query()->firstOrCreate(
                [
                    'goal_id' => $goal->id,
                    'idempotency_key' => $idempotencyKey,
                ],
                [
                    'agent_run_id' => null,
                    'triggered_at' => $tickAt,
                    'trigger_reason' => Str::limit((string) ($trigger['trigger_reason'] ?? 'Goal trigger matched.'), 255),
                    'status' => AgentGoalRun::STATUS_PENDING,
                    'output_summary' => null,
                    'completed_at' => null,
                ]
            );
        } catch (QueryException $exception) {
            // Handle racing scheduler workers creating the same idempotency key.
            if ($this->isDuplicateKey($exception)) {
                return AgentGoalRun::query()
                    ->where('goal_id', $goal->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
            }

            throw $exception;
        }
    }

    protected function createAgentRun(
        AgentGoal $goal,
        AgentGoalRun $goalRun,
        CarbonImmutable $tickAt,
        string $triggerReason
    ): AgentRun {
        $agent = $goal->agent;
        $requesterId = (int) ($agent->owner_user_id ?: $agent->created_by);

        $threadDefaults = ['title' => $agent->name.' Thread'];
        if ($this->threadVisibilityColumnExists()) {
            $threadDefaults['visibility'] = AgentThread::VISIBILITY_PRIVATE;
        }

        $thread = AgentThread::query()->firstOrCreate(
            ['agent_id' => $agent->id, 'user_id' => $requesterId],
            $threadDefaults
        );

        $directive = $this->buildGoalDirective($goal, $goalRun, $triggerReason);

        $messagePayload = [
            'thread_id' => $thread->id,
            'user_id' => $requesterId,
            'role' => 'system',
            'content' => '[Scheduled Goal] '.$goal->title,
            'meta' => [
                'goal_id' => $goal->id,
                'goal_run_id' => $goalRun->id,
                'trigger_reason' => $triggerReason,
            ],
        ];

        if ($this->messageVisibilityColumnExists()) {
            $messagePayload['visibility'] = trim((string) ($thread->visibility ?? '')) ?: AgentThread::VISIBILITY_PRIVATE;
        }

        AgentMessage::query()->create($messagePayload);

        return AgentRun::query()->create([
            'agent_id' => $agent->id,
            'thread_id' => $thread->id,
            'requested_by' => $requesterId ?: null,
            'status' => 'running',
            'directive' => $directive,
            'started_at' => $tickAt,
        ]);
    }

    protected function buildGoalDirective(AgentGoal $goal, AgentGoalRun $goalRun, string $triggerReason): string
    {
        $lines = [
            'Execute standing goal: '.$goal->title,
            'Goal type: '.$goal->goal_type,
            'Priority: '.(int) $goal->priority,
            'Trigger reason: '.$triggerReason,
            'Goal run id: '.$goalRun->id,
        ];

        $description = trim((string) $goal->description);
        if ($description !== '') {
            $lines[] = 'Description: '.$description;
        }

        return implode("\n", $lines);
    }

    protected function persistGoalContext(
        AgentGoal $goal,
        AgentGoalRun $goalRun,
        AgentRun $agentRun,
        string $summary,
        CarbonImmutable $tickAt
    ): void {
        $entries = [
            'last_triggered_at' => ['value' => $tickAt->toIso8601String()],
            'last_goal_run_id' => ['value' => (int) $goalRun->id],
            'last_agent_run_id' => ['value' => (int) $agentRun->id],
            'last_output_summary' => ['value' => Str::limit($summary, 2000)],
        ];

        foreach ($entries as $key => $value) {
            AgentGoalContext::query()->updateOrCreate(
                [
                    'goal_id' => $goal->id,
                    'context_key' => $key,
                ],
                [
                    'context_value' => $value,
                    'updated_at' => $tickAt,
                ]
            );
        }
    }

    protected function hasGoalSchema(): bool
    {
        return Schema::hasTable('agent_goals')
            && Schema::hasTable('agent_goal_runs')
            && Schema::hasTable('agent_goal_context')
            && Schema::hasTable('agent_runs')
            && Schema::hasTable('agent_threads')
            && Schema::hasTable('agent_messages');
    }

    protected function isDuplicateKey(QueryException $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'constraint');
    }

    protected function threadVisibilityColumnExists(): bool
    {
        if ($this->threadVisibilityColumnExists === null) {
            $this->threadVisibilityColumnExists = Schema::hasColumn('agent_threads', 'visibility');
        }

        return $this->threadVisibilityColumnExists;
    }

    protected function messageVisibilityColumnExists(): bool
    {
        if ($this->messageVisibilityColumnExists === null) {
            $this->messageVisibilityColumnExists = Schema::hasColumn('agent_messages', 'visibility');
        }

        return $this->messageVisibilityColumnExists;
    }
}
