<?php

namespace App\Services\Agents;

use App\Models\AgentGoal;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Cron\CronExpression;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GoalTriggerService
{
    /**
     * @return array{
     *   due: bool,
     *   idempotency_key: string|null,
     *   trigger_reason: string|null,
     *   triggered_at: string|null
     * }
     */
    public function evaluate(AgentGoal $goal, ?CarbonInterface $now = null): array
    {
        $tickAt = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();
        $triggerType = Str::lower(trim((string) $goal->trigger_type));
        $config = is_array($goal->trigger_config) ? $goal->trigger_config : [];

        return match ($triggerType) {
            AgentGoal::TRIGGER_DEADLINE => $this->evaluateDeadlineTrigger($goal, $tickAt, $config),
            AgentGoal::TRIGGER_EVENT,
            AgentGoal::TRIGGER_MANUAL => $this->evaluateEventTrigger($goal, $tickAt, $config),
            default => $this->evaluateCronTrigger($goal, $tickAt, $config),
        };
    }

    /**
     * @param  array<string,mixed>  $config
     * @return array{due:bool,idempotency_key:string|null,trigger_reason:string|null,triggered_at:string|null}
     */
    protected function evaluateCronTrigger(AgentGoal $goal, CarbonImmutable $tickAt, array $config): array
    {
        $expression = trim((string) Arr::get($config, 'expression', ''));
        if ($expression === '') {
            return $this->notDue('Cron expression is missing.');
        }

        $timezone = trim((string) Arr::get($config, 'timezone', config('app.timezone', 'UTC')));
        $windowMinutes = max(1, min((int) Arr::get($config, 'window_minutes', 15), 1440));

        try {
            $cron = new CronExpression($expression);
            $isDue = $cron->isDue($tickAt->toDateTimeString(), $timezone);
        } catch (\Throwable) {
            return $this->notDue('Invalid cron expression.');
        }

        if (! $isDue) {
            return $this->notDue('Cron not due at this tick.');
        }

        $windowStart = $this->windowStartFor($tickAt, $windowMinutes);
        $idempotencyKey = $this->makeIdempotencyKey([
            'goal_id' => (int) $goal->id,
            'trigger_type' => AgentGoal::TRIGGER_CRON,
            'expression' => $expression,
            'window_start' => $windowStart->timestamp,
        ]);

        return [
            'due' => true,
            'idempotency_key' => $idempotencyKey,
            'trigger_reason' => "Cron trigger matched ({$expression})",
            'triggered_at' => $tickAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string,mixed>  $config
     * @return array{due:bool,idempotency_key:string|null,trigger_reason:string|null,triggered_at:string|null}
     */
    protected function evaluateDeadlineTrigger(AgentGoal $goal, CarbonImmutable $tickAt, array $config): array
    {
        $dueAtRaw = trim((string) Arr::get($config, 'due_at', ''));
        if ($dueAtRaw === '') {
            return $this->notDue('Deadline trigger missing due_at.');
        }

        try {
            $dueAt = CarbonImmutable::parse($dueAtRaw);
        } catch (\Throwable) {
            return $this->notDue('Deadline due_at is invalid.');
        }

        $leadMinutes = max(0, min((int) Arr::get($config, 'lead_minutes', 0), 525600));
        $triggerAt = $dueAt->subMinutes($leadMinutes);

        if ($tickAt->lt($triggerAt)) {
            return $this->notDue('Deadline window not reached.');
        }

        $allowRepeat = (bool) Arr::get($config, 'allow_repeat', false);
        $windowMinutes = max(1, min((int) Arr::get($config, 'window_minutes', 60), 1440));
        $windowStart = $this->windowStartFor($tickAt, $windowMinutes);
        $idempotencyKey = $allowRepeat
            ? $this->makeIdempotencyKey([
                'goal_id' => (int) $goal->id,
                'trigger_type' => AgentGoal::TRIGGER_DEADLINE,
                'due_at' => $dueAt->toIso8601String(),
                'window_start' => $windowStart->timestamp,
            ])
            : $this->makeIdempotencyKey([
                'goal_id' => (int) $goal->id,
                'trigger_type' => AgentGoal::TRIGGER_DEADLINE,
                'due_at' => $dueAt->toIso8601String(),
                'lead_minutes' => $leadMinutes,
            ]);

        return [
            'due' => true,
            'idempotency_key' => $idempotencyKey,
            'trigger_reason' => 'Deadline threshold reached',
            'triggered_at' => $tickAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string,mixed>  $config
     * @return array{due:bool,idempotency_key:string|null,trigger_reason:string|null,triggered_at:string|null}
     */
    protected function evaluateEventTrigger(AgentGoal $goal, CarbonImmutable $tickAt, array $config): array
    {
        $forced = (bool) Arr::get($config, 'force_due', false);
        if (! $forced) {
            return $this->notDue('Event/manual trigger requires force_due=true.');
        }

        $eventKey = trim((string) Arr::get($config, 'event_key', Arr::get($config, 'event_id', '')));
        if ($eventKey === '') {
            $eventKey = $tickAt->format('YmdHi');
        }

        return [
            'due' => true,
            'idempotency_key' => $this->makeIdempotencyKey([
                'goal_id' => (int) $goal->id,
                'trigger_type' => Str::lower((string) $goal->trigger_type),
                'event_key' => $eventKey,
            ]),
            'trigger_reason' => 'Event/manual trigger fired',
            'triggered_at' => $tickAt->toIso8601String(),
        ];
    }

    /**
     * @return array{due:bool,idempotency_key:string|null,trigger_reason:string|null,triggered_at:string|null}
     */
    protected function notDue(string $reason): array
    {
        return [
            'due' => false,
            'idempotency_key' => null,
            'trigger_reason' => $reason,
            'triggered_at' => null,
        ];
    }

    protected function windowStartFor(CarbonImmutable $at, int $minutes): CarbonImmutable
    {
        $intervalSeconds = $minutes * 60;
        $windowStartEpoch = intdiv($at->timestamp, $intervalSeconds) * $intervalSeconds;

        return CarbonImmutable::createFromTimestamp($windowStartEpoch, $at->timezone);
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected function makeIdempotencyKey(array $payload): string
    {
        try {
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $encoded = implode('|', array_map(static fn ($value) => (string) $value, $payload));
        }

        return hash('sha256', $encoded);
    }
}
