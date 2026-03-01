<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class ApprovalGateService
{
    /**
     * @return array{
     *   allowed: bool,
     *   requires_approval: bool,
     *   risk_level: string,
     *   request: ApprovalRequest|null,
     *   message: string
     * }
     */
    public function evaluate(User $actor, string $actionType, array $context = []): array
    {
        $actionType = trim(Str::lower($actionType));
        if ($actionType === '') {
            throw new RuntimeException('Approval action type is required.');
        }

        $riskLevel = $this->resolveRiskLevel($actionType, $context);
        $requiresApproval = $this->requiresApproval($actor, $riskLevel);

        if (! $this->enabled() || ! $requiresApproval) {
            return [
                'allowed' => true,
                'requires_approval' => false,
                'risk_level' => $riskLevel,
                'request' => null,
                'message' => 'Approved by risk policy.',
            ];
        }

        $request = $this->findOrCreatePendingRequest($actor, $actionType, $riskLevel, $context);

        return [
            'allowed' => false,
            'requires_approval' => true,
            'risk_level' => $riskLevel,
            'request' => $request,
            'message' => 'Approval request #'.$request->id.' is pending.',
        ];
    }

    public function approve(ApprovalRequest $request, User $reviewer, ?string $notes = null): ApprovalRequest
    {
        if ($request->approval_status === ApprovalRequest::STATUS_APPROVED) {
            return $request;
        }

        if ($request->approval_status !== ApprovalRequest::STATUS_PENDING) {
            throw new RuntimeException('Only pending approval requests can be approved.');
        }

        $request->update([
            'approval_status' => ApprovalRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes ?: 'Approved.',
        ]);

        return $request->fresh();
    }

    public function reject(ApprovalRequest $request, User $reviewer, ?string $notes = null): ApprovalRequest
    {
        if ($request->approval_status === ApprovalRequest::STATUS_REJECTED) {
            return $request;
        }

        if ($request->approval_status !== ApprovalRequest::STATUS_PENDING) {
            throw new RuntimeException('Only pending approval requests can be rejected.');
        }

        $request->update([
            'approval_status' => ApprovalRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes ?: 'Rejected.',
        ]);

        return $request->fresh();
    }

    public function markExecuted(ApprovalRequest $request): ApprovalRequest
    {
        if (! $request->executed_at) {
            $request->update(['executed_at' => now()]);
        }

        return $request->fresh();
    }

    protected function enabled(): bool
    {
        return (bool) config('approvals.enabled', true);
    }

    protected function resolveRiskLevel(string $actionType, array $context): string
    {
        $risk = Str::lower(trim((string) (Arr::get($context, 'risk_level')
            ?: Arr::get(config('approvals.risk_map', []), $actionType, 'medium'))));

        if (! in_array($risk, ['low', 'medium', 'high'], true)) {
            return 'medium';
        }

        return $risk;
    }

    protected function requiresApproval(User $actor, string $riskLevel): bool
    {
        if ($riskLevel === 'low') {
            return false;
        }

        return ! $actor->isManagement();
    }

    protected function findOrCreatePendingRequest(User $actor, string $actionType, string $riskLevel, array $context): ApprovalRequest
    {
        $dedupeKey = $this->dedupeKeyFor($actor, $actionType, $context);

        $existing = ApprovalRequest::query()
            ->where('dedupe_key', $dedupeKey)
            ->where('approval_status', ApprovalRequest::STATUS_PENDING)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ApprovalRequest::query()->create([
            'requested_by' => $actor->id,
            'action_type' => $actionType,
            'risk_level' => $riskLevel,
            'approval_status' => ApprovalRequest::STATUS_PENDING,
            'title' => $this->titleFor($actionType, $context),
            'rationale' => $this->rationaleFor($context),
            'context' => $context,
            'dedupe_key' => $dedupeKey,
        ]);
    }

    protected function dedupeKeyFor(User $actor, string $actionType, array $context): string
    {
        $dedupePayload = [
            'actor_id' => (int) $actor->id,
            'action_type' => $actionType,
            'resource_type' => Arr::get($context, 'resource_type'),
            'resource_id' => Arr::get($context, 'resource_id'),
            'fingerprint' => Arr::get($context, 'fingerprint')
                ?: Arr::get($context, 'summary')
                ?: Arr::get($context, 'title'),
        ];

        try {
            $encoded = json_encode($dedupePayload, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $encoded = implode('|', array_map(static fn ($value) => (string) $value, $dedupePayload));
        }

        return hash('sha256', $encoded);
    }

    protected function titleFor(string $actionType, array $context): string
    {
        $title = trim((string) (Arr::get($context, 'title') ?: Arr::get($context, 'summary', '')));
        if ($title !== '') {
            return Str::limit($title, 255, '');
        }

        return Str::headline(str_replace('.', ' ', $actionType));
    }

    protected function rationaleFor(array $context): ?string
    {
        $rationale = trim((string) (Arr::get($context, 'rationale') ?: Arr::get($context, 'summary', '')));

        return $rationale !== '' ? Str::limit($rationale, 2000, '') : null;
    }
}
