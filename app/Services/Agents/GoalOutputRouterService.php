<?php

namespace App\Services\Agents;

use App\Models\AgentGoal;
use App\Models\AgentGoalRun;
use App\Models\AgentMessage;
use App\Models\AgentRun;
use App\Models\AgentThread;
use App\Models\User;
use App\Services\ApprovalGateService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoalOutputRouterService
{
    public function __construct(
        protected ApprovalGateService $approvalGateService
    ) {}

    /**
     * @param  array<string,mixed>  $context
     * @return array{channel:string,summary:string,approval_request_id:int|null}
     */
    public function route(AgentGoal $goal, AgentGoalRun $goalRun, AgentRun $agentRun, array $context = []): array
    {
        $outputConfig = is_array($goal->output_config) ? $goal->output_config : [];
        $channel = Str::lower(trim((string) Arr::get($outputConfig, 'channel', 'wrk_thread')));

        if ($channel === '') {
            $channel = 'wrk_thread';
        }

        $summary = 'Goal "'.$goal->title.'" triggered and processed.';
        $approvalRequestId = null;
        $requiresApproval = (bool) Arr::get($outputConfig, 'requires_approval', false)
            || in_array($channel, ['approval', 'approval_queue'], true);

        if (in_array($channel, ['wrk_thread', 'wrk', 'notification', 'approval', 'approval_queue'], true)) {
            $this->postThreadMessage(
                $agentRun,
                $summary,
                array_merge($context, [
                    'goal_id' => (int) $goal->id,
                    'goal_run_id' => (int) $goalRun->id,
                    'output_channel' => $channel,
                ])
            );
        }

        if ($requiresApproval) {
            $requestActor = $agentRun->requested_by ? User::query()->find($agentRun->requested_by) : null;
            if ($requestActor) {
                $decision = $this->approvalGateService->evaluate($requestActor, 'agent.goal.output', [
                    'title' => 'Goal output approval: '.$goal->title,
                    'summary' => $summary,
                    'resource_type' => 'agent_goal_run',
                    'resource_id' => (int) $goalRun->id,
                    'risk_level' => Arr::get($outputConfig, 'risk_level', 'medium'),
                    'fingerprint' => $goalRun->idempotency_key,
                ]);

                $approvalRequestId = (int) ($decision['request']?->id ?: 0) ?: null;

                if ($approvalRequestId) {
                    $summary .= ' Queued for approval request #'.$approvalRequestId.'.';
                }
            }
        }

        if (in_array($channel, ['slack', 'email'], true)) {
            $summary .= ' '.$channel.' routing is pending connector integration.';
            $this->postThreadMessage(
                $agentRun,
                ucfirst($channel).' routing is not active yet; result retained in WRK thread.',
                ['goal_run_id' => (int) $goalRun->id, 'output_channel' => $channel, 'stub' => true]
            );
        }

        return [
            'channel' => $channel,
            'summary' => $summary,
            'approval_request_id' => $approvalRequestId,
        ];
    }

    /**
     * @param  array<string,mixed>  $meta
     */
    protected function postThreadMessage(AgentRun $agentRun, string $content, array $meta = []): void
    {
        if (! $agentRun->thread_id) {
            return;
        }

        $payload = [
            'thread_id' => $agentRun->thread_id,
            'user_id' => null,
            'role' => 'assistant',
            'content' => $content,
            'meta' => $meta,
        ];

        if (Schema::hasColumn('agent_messages', 'visibility')) {
            $threadVisibility = AgentThread::query()
                ->where('id', $agentRun->thread_id)
                ->value('visibility');

            $payload['visibility'] = trim((string) $threadVisibility) ?: AgentThread::VISIBILITY_PRIVATE;
        }

        AgentMessage::query()->create($payload);
    }
}
