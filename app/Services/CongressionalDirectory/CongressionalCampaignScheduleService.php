<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalOutreachDraft;
use App\Models\OutreachCampaignRecipient;
use App\Models\User;
use App\Services\GoogleGmailService;
use DomainException;
use Illuminate\Support\Carbon;

class CongressionalCampaignScheduleService
{
    public function __construct(
        protected CongressionalOutreachBatchService $batches,
        protected CongressionalOutreachWorkbenchService $workbench,
        protected GoogleGmailService $gmail
    ) {}

    public function activate(CongressionalOutreachDraft $draft, User $user, ?Carbon $firstSendAt = null): void
    {
        if ($draft->user_id !== $user->id) {
            throw new DomainException('Only the campaign owner can activate delivery.');
        }
        if (! in_array($draft->delivery_mode, ['scheduled', 'recurring'], true)) {
            throw new DomainException('Choose scheduled or recurring delivery before activating automation.');
        }
        if (! trim((string) $draft->subject) || ! trim((string) $draft->body_text)) {
            throw new DomainException('Add and save a subject and message before activating delivery.');
        }
        $hasApprovedRecipient = $draft->recipients()
            ->where('review_status', 'approved')
            ->whereNull('exclusion_reason')
            ->whereDoesntHave('outreachCampaignRecipients')
            ->exists();
        $hasAutomaticRecipient = $draft->auto_approve_provisional
            && $this->workbench->autoApprovableProvisionalCount($draft) > 0;
        if (! $hasApprovedRecipient && ! $hasAutomaticRecipient) {
            throw new DomainException('Approve at least one recipient before activating delivery.');
        }
        if (! $this->gmail->isConnected($user)) {
            throw new DomainException('Connect Gmail before activating campaign delivery.');
        }

        $draft->update([
            'schedule_status' => 'active',
            'next_send_at' => ($firstSendAt ?: now())->utc(),
        ]);
    }

    public function pause(CongressionalOutreachDraft $draft, User $user): void
    {
        if ($draft->user_id !== $user->id) {
            throw new DomainException('Only the campaign owner can pause delivery.');
        }

        $draft->update(['schedule_status' => 'paused', 'next_send_at' => null]);
    }

    public function resume(CongressionalOutreachDraft $draft, User $user): void
    {
        if ($draft->schedule_status !== 'paused') {
            throw new DomainException('This campaign is not paused.');
        }

        $this->activate($draft, $user, now());
    }

    /** @return array{processed:int,completed:int,deferred:int,failed:int} */
    public function runDue(int $limit = 25): array
    {
        $summary = ['processed' => 0, 'completed' => 0, 'deferred' => 0, 'failed' => 0];
        $drafts = CongressionalOutreachDraft::query()
            ->where('schedule_status', 'active')
            ->whereNotNull('next_send_at')
            ->where('next_send_at', '<=', now())
            ->with('user')
            ->orderBy('next_send_at')
            ->limit(max(1, min($limit, 200)))
            ->get();

        foreach ($drafts as $draft) {
            try {
                $batchSummary = $this->batches->summary($draft);
                if ($batchSummary['active'] > 0) {
                    $draft->update(['next_send_at' => now()->addMinutes(5)]);
                    $summary['deferred']++;

                    continue;
                }

                $dailyRemaining = $this->dailyCapacityRemaining($draft);
                if ($dailyRemaining < 1) {
                    $draft->update(['next_send_at' => $this->nextDailyWindow($draft)]);
                    $summary['deferred']++;

                    continue;
                }

                if ($draft->auto_approve_provisional) {
                    $this->workbench->approveNextProvisional(
                        $draft,
                        $draft->user_id,
                        min((int) $draft->batch_size, $dailyRemaining)
                    );
                    $batchSummary = $this->batches->summary($draft->fresh());
                }
                if ($batchSummary['approved_unsent'] < 1) {
                    $draft->update(['schedule_status' => 'completed', 'next_send_at' => null]);
                    $summary['completed']++;

                    continue;
                }
                if (! $this->gmail->isConnected($draft->user)) {
                    throw new DomainException('The campaign owner’s Gmail connection is unavailable.');
                }

                $this->batches->sendNextBatch($draft, $draft->user, $dailyRemaining);
                $summary['processed']++;
                $draft->refresh();
                $draft->update([
                    'last_batch_at' => now(),
                    'schedule_status' => $draft->delivery_mode === 'recurring' ? 'active' : 'completed',
                    'next_send_at' => $draft->delivery_mode === 'recurring' ? $this->nextRun($draft) : null,
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                $metadata = $draft->metadata ?? [];
                $metadata['schedule_error'] = [
                    'message' => $exception->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                ];
                $draft->update([
                    'schedule_status' => 'paused',
                    'next_send_at' => null,
                    'metadata' => $metadata,
                ]);
                $summary['failed']++;
            }
        }

        return $summary;
    }

    public function nextRun(CongressionalOutreachDraft $draft): Carbon
    {
        $value = max(1, (int) $draft->cadence_value);

        return match ($draft->cadence_unit) {
            'minute' => now()->addMinutes($value),
            'day' => now()->addDays($value),
            'week' => now()->addWeeks($value),
            default => now()->addHours($value),
        };
    }

    public function dailyCapacityRemaining(CongressionalOutreachDraft $draft): int
    {
        $timezone = $draft->timezone ?: 'America/New_York';
        $localNow = now($timezone);
        $start = $localNow->copy()->startOfDay()->utc();
        $end = $localNow->copy()->endOfDay()->utc();
        $scheduledToday = OutreachCampaignRecipient::query()
            ->whereHas('campaign', fn ($query) => $query
                ->where('congressional_outreach_draft_id', $draft->id))
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return max(0, max(1, (int) $draft->daily_send_cap) - $scheduledToday);
    }

    public function nextDailyWindow(CongressionalOutreachDraft $draft): Carbon
    {
        $timezone = $draft->timezone ?: 'America/New_York';

        return now($timezone)->addDay()->startOfDay()->setHour(9)->utc();
    }
}
