<?php

namespace App\Services\Support;

use App\Models\Action;
use App\Models\SupportSignal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SupportSignalPolicyService
{
    /**
     * @param  array<string,mixed>  $metadata
     * @return array{signal:SupportSignal,escalated:bool,auto_escalated:bool,manager:?User,followup_action_id:?int}
     */
    public function captureFromWorkspace(
        User $staffMember,
        string $summary,
        ?string $rawContext = null,
        bool $notifyManagement = false,
        bool $shareRawWithManagement = false,
        array $metadata = []
    ): array {
        $summary = trim($summary);
        if ($summary === '') {
            $summary = 'Support check-in requested from WRKspace.';
        }

        $rawContext = trim((string) $rawContext);
        $manager = $this->resolveManager($staffMember);
        $windowDays = max(1, (int) config('support.signals.repeat_window_days', 14));
        $threshold = max(1, (int) config('support.signals.repeat_threshold', 3));

        $recentCount = SupportSignal::query()
            ->where('user_id', $staffMember->id)
            ->where('created_at', '>=', now()->subDays($windowDays))
            ->count();

        $windowCount = $recentCount + 1;
        $repeatTriggered = $windowCount >= $threshold;
        $escalate = $manager !== null && ($notifyManagement || $repeatTriggered);

        $escalationReason = null;
        if ($escalate) {
            $escalationReason = $notifyManagement
                ? SupportSignal::ESCALATION_EXPLICIT
                : SupportSignal::ESCALATION_REPEAT_THRESHOLD;
        }

        $signal = SupportSignal::query()->create([
            'user_id' => $staffMember->id,
            'manager_user_id' => $manager?->id,
            'source' => 'workspace_companion',
            'status' => $escalate ? SupportSignal::STATUS_ESCALATED : SupportSignal::STATUS_DRAFT,
            'summary' => Str::limit($summary, 4000, ''),
            'raw_context' => $rawContext !== '' ? Str::limit($rawContext, 12000, '') : null,
            'share_raw_with_management' => $shareRawWithManagement,
            'escalation_reason' => $escalationReason,
            'window_signal_count' => $windowCount,
            'escalated_at' => $escalate ? now() : null,
            'metadata' => $metadata,
        ]);

        $followupActionId = null;
        if ($escalate && $manager && (bool) config('support.signals.auto_create_management_action', true)) {
            $followupActionId = $this->createManagementFollowupAction($signal, $staffMember, $manager);
            if ($followupActionId) {
                $signal->update(['followup_action_id' => $followupActionId]);
                $signal->refresh();
            }
        }

        return [
            'signal' => $signal,
            'escalated' => $escalate,
            'auto_escalated' => $repeatTriggered && ! $notifyManagement,
            'manager' => $manager,
            'followup_action_id' => $followupActionId,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function managementDigest(User $viewer, int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));
        if (! $viewer->isManagement()) {
            return [];
        }

        $query = SupportSignal::query()
            ->with([
                'user:id,name,email',
                'manager:id,name,email',
            ])
            ->where('status', SupportSignal::STATUS_ESCALATED)
            ->orderByDesc('escalated_at')
            ->orderByDesc('created_at')
            ->limit($limit);

        if (! $viewer->isAdmin()) {
            $query->where('manager_user_id', $viewer->id);
        }

        $signals = $query->get();

        return $signals->map(function (SupportSignal $signal): array {
            $rawAllowed = (bool) $signal->share_raw_with_management;

            return [
                'id' => $signal->id,
                'staff_name' => (string) ($signal->user?->name ?? 'Unknown'),
                'staff_email' => (string) ($signal->user?->email ?? ''),
                'manager_name' => (string) ($signal->manager?->name ?? ''),
                'source' => (string) $signal->source,
                'summary' => (string) $signal->summary,
                'raw_context' => $rawAllowed ? (string) ($signal->raw_context ?? '') : null,
                'raw_context_shared' => $rawAllowed,
                'escalation_reason' => (string) ($signal->escalation_reason ?? ''),
                'window_signal_count' => (int) ($signal->window_signal_count ?? 1),
                'escalated_at' => ($signal->escalated_at ?: $signal->created_at)?->format('M j, Y g:i A'),
                'followup_action_id' => $signal->followup_action_id ? (int) $signal->followup_action_id : null,
            ];
        })->values()->all();
    }

    public function resolveManager(User $staffMember): ?User
    {
        $manager = $staffMember->manager()->first();
        if ($manager && $manager->id !== $staffMember->id) {
            return $manager;
        }

        return User::query()
            ->where('id', '!=', $staffMember->id)
            ->where(function (Builder $query) {
                $query->whereIn('access_level', ['management', 'admin'])
                    ->orWhere('is_admin', true);
            })
            ->orderByRaw("CASE WHEN access_level = 'management' THEN 0 WHEN access_level = 'admin' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->first();
    }

    protected function createManagementFollowupAction(SupportSignal $signal, User $staffMember, User $manager): ?int
    {
        $notes = collect([
            'CONFIDENTIAL support check-in signal.',
            'Staff member: '.$staffMember->name.' ('.$staffMember->email.')',
            'Summary: '.$signal->summary,
            $signal->share_raw_with_management && $signal->raw_context
                ? 'Shared context: '.$signal->raw_context
                : 'Shared context: consent not granted for raw journaling.',
            $signal->escalation_reason
                ? 'Escalation reason: '.$this->labelEscalationReason($signal->escalation_reason)
                : null,
            'Signal count in recent window: '.$signal->window_signal_count,
        ])
            ->filter()
            ->implode("\n");

        $action = Action::createResilient([
            'title' => Str::limit('Support check-in: '.explode(' ', (string) $staffMember->name)[0], 190, ''),
            'description' => 'Confidential support check-in requested.',
            'due_date' => Carbon::today()->toDateString(),
            'priority' => 'high',
            'status' => 'pending',
            'source' => 'manual',
            'assigned_to' => $manager->id,
            'notes' => $notes,
        ]);

        return $action->id ? (int) $action->id : null;
    }

    protected function labelEscalationReason(string $reason): string
    {
        return match ($reason) {
            SupportSignal::ESCALATION_EXPLICIT => 'Explicit request',
            SupportSignal::ESCALATION_REPEAT_THRESHOLD => 'Repeat threshold reached',
            default => ucfirst(str_replace('_', ' ', $reason)),
        };
    }
}

