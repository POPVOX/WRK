<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalOutreachDraft;
use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\OutreachCampaign;
use App\Models\OutreachCampaignRecipient;
use App\Models\User;
use App\Services\Outreach\OutreachCampaignService;
use App\Services\Outreach\OutreachSuppressionService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class CongressionalOutreachBatchService
{
    public function __construct(
        protected CongressionalOutreachWorkbenchService $workbench,
        protected OutreachCampaignService $campaigns,
        protected OutreachSuppressionService $suppressions
    ) {}

    /**
     * @return array{campaign:OutreachCampaign,queued:int}
     */
    public function sendNextBatch(CongressionalOutreachDraft $draft, User $user): array
    {
        $campaign = DB::transaction(function () use ($draft, $user): OutreachCampaign {
            $lockedDraft = CongressionalOutreachDraft::query()->lockForUpdate()->findOrFail($draft->id);
            if ($lockedDraft->user_id !== $user->id) {
                throw new DomainException('Only the campaign owner can send this outreach.');
            }
            if (! trim((string) $lockedDraft->subject) || ! trim((string) $lockedDraft->body_text)) {
                throw new DomainException('Add and save a subject and message before sending.');
            }
            if ($lockedDraft->status === 'building') {
                throw new DomainException('Wait for the recipient snapshot to finish building before sending.');
            }

            $active = $lockedDraft->outreachCampaigns()
                ->whereIn('status', ['draft', 'scheduled', 'sending'])
                ->exists();
            if ($active) {
                throw new DomainException('The current batch is still in progress. Wait for it to finish before sending the next batch.');
            }

            $candidates = $lockedDraft->recipients()
                ->where('review_status', 'approved')
                ->whereNull('exclusion_reason')
                ->whereNotNull('staff_email_id')
                ->whereNotNull('email_normalized')
                ->whereDoesntHave('outreachCampaignRecipients')
                ->orderBy('id')
                ->get();

            $suppressed = array_fill_keys(
                $this->suppressions->suppressedEmails($candidates->pluck('email_normalized')->all()),
                true
            );
            $blocked = $candidates->filter(fn (CongressionalOutreachDraftRecipient $recipient) => isset($suppressed[$recipient->email_normalized]));
            if ($blocked->isNotEmpty()) {
                CongressionalOutreachDraftRecipient::query()
                    ->whereIn('id', $blocked->pluck('id'))
                    ->update([
                        'review_status' => 'excluded',
                        'exclusion_reason' => 'blocked_address',
                        'updated_at' => now(),
                    ]);
            }

            $batchSize = max(1, min((int) $lockedDraft->batch_size, 5000));
            $recipients = $candidates
                ->reject(fn (CongressionalOutreachDraftRecipient $recipient) => isset($suppressed[$recipient->email_normalized]))
                ->take($batchSize)
                ->values();
            if ($recipients->isEmpty()) {
                throw new DomainException('There are no approved, unsent recipients available for the next batch.');
            }

            $batchNumber = $lockedDraft->outreachCampaigns()->count() + 1;
            $campaign = OutreachCampaign::query()->create([
                'user_id' => $user->id,
                'congressional_outreach_draft_id' => $lockedDraft->id,
                'name' => $lockedDraft->name.' · Batch '.$batchNumber,
                'campaign_type' => 'congressional',
                'channel' => 'gmail',
                'status' => 'draft',
                'subject' => $lockedDraft->subject,
                'body_text' => $lockedDraft->body_text,
                'send_mode' => 'immediate',
                'recipients_count' => $recipients->count(),
                'metadata' => [
                    'congressional_outreach_draft_id' => $lockedDraft->id,
                    'batch_number' => $batchNumber,
                    'batch_size_limit' => $batchSize,
                ],
            ]);

            $now = now();
            $rows = $recipients->map(function (CongressionalOutreachDraftRecipient $recipient) use ($campaign, $lockedDraft, $now): array {
                $preview = $this->workbench->preview($lockedDraft, $recipient);
                if ($preview['unresolved'] !== []) {
                    throw new DomainException('Resolve these message placeholders before sending: '.implode(', ', $preview['unresolved']).'.');
                }

                return [
                    'campaign_id' => $campaign->id,
                    'congressional_outreach_draft_recipient_id' => $recipient->id,
                    'email' => $recipient->email_normalized,
                    'name' => $recipient->name,
                    'status' => 'pending',
                    'metadata' => json_encode([
                        'subject' => $preview['subject'],
                        'body_text' => $preview['body'],
                        'congressional_staff_email_id' => $recipient->staff_email_id,
                        'congressional_outreach_draft_recipient_id' => $recipient->id,
                        'eligibility_tier' => $recipient->eligibility_tier,
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            DB::table('outreach_campaign_recipients')->insert($rows);

            return $campaign;
        });

        try {
            $queued = $this->campaigns->queueCampaign($campaign);
        } catch (Throwable $exception) {
            $campaign->update(['status' => 'failed', 'completed_at' => now()]);
            throw $exception;
        }

        return ['campaign' => $campaign->fresh(), 'queued' => $queued];
    }

    /**
     * @return array{campaign:OutreachCampaign,queued:int}
     */
    public function retryFailedBatch(CongressionalOutreachDraft $draft, User $user): array
    {
        $campaign = DB::transaction(function () use ($draft, $user): OutreachCampaign {
            $lockedDraft = CongressionalOutreachDraft::query()->lockForUpdate()->findOrFail($draft->id);
            if ($lockedDraft->user_id !== $user->id) {
                throw new DomainException('Only the campaign owner can retry this outreach.');
            }
            if ($lockedDraft->outreachCampaigns()->whereIn('status', ['draft', 'scheduled', 'sending'])->exists()) {
                throw new DomainException('The current batch is still in progress. Wait for it to finish before retrying.');
            }

            $campaign = $lockedDraft->outreachCampaigns()
                ->whereHas('recipients', fn ($query) => $query->where('status', 'failed'))
                ->latest('id')
                ->first();
            if (! $campaign) {
                throw new DomainException('There are no failed recipients available to retry.');
            }

            $campaign->update(['status' => 'scheduled', 'completed_at' => null]);

            return $campaign;
        });

        try {
            $queued = $this->campaigns->queueCampaign($campaign);
        } catch (Throwable $exception) {
            $campaign->update(['status' => 'failed', 'completed_at' => now()]);
            throw $exception;
        }

        return ['campaign' => $campaign->fresh(), 'queued' => $queued];
    }

    /**
     * @return array{approved_unsent:int,sent:int,failed:int,suppressed:int,retryable:int,active:int,last_campaign:?OutreachCampaign}
     */
    public function summary(CongressionalOutreachDraft $draft): array
    {
        $campaigns = $draft->outreachCampaigns();

        return [
            'approved_unsent' => $draft->recipients()
                ->where('review_status', 'approved')
                ->whereNull('exclusion_reason')
                ->whereNotNull('staff_email_id')
                ->whereDoesntHave('outreachCampaignRecipients')
                ->count(),
            'sent' => OutreachCampaignRecipient::query()
                ->whereHas('campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $draft->id))
                ->where('status', 'sent')
                ->count(),
            'failed' => OutreachCampaignRecipient::query()
                ->whereHas('campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $draft->id))
                ->where('status', 'failed')
                ->count(),
            'suppressed' => OutreachCampaignRecipient::query()
                ->whereHas('campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $draft->id))
                ->where('status', 'suppressed')
                ->count(),
            'retryable' => OutreachCampaignRecipient::query()
                ->whereHas('campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $draft->id))
                ->where('status', 'failed')
                ->count(),
            'active' => (clone $campaigns)->whereIn('status', ['draft', 'scheduled', 'sending'])->count(),
            'last_campaign' => (clone $campaigns)->latest('id')->first(),
        ];
    }

    /**
     * @return array{campaigns:mixed,recipients:mixed,statuses:array<string,int>,events:array<string,int>,engagement:array<string,int>,bounce_signals:int,clicks_tracked:bool}
     */
    public function analytics(CongressionalOutreachDraft $draft): array
    {
        $campaigns = $draft->outreachCampaigns()
            ->withCount([
                'recipients as queued_count' => fn ($query) => $query->whereIn('status', ['queued', 'sending']),
                'recipients as suppressed_count' => fn ($query) => $query->where('status', 'suppressed'),
            ])
            ->latest('id')
            ->get();
        $recipientQuery = OutreachCampaignRecipient::query()
            ->whereHas('campaign', fn ($query) => $query->where('congressional_outreach_draft_id', $draft->id));
        $statuses = (clone $recipientQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
        $recipientIds = (clone $recipientQuery)->pluck('id');
        $recipientEmails = (clone $recipientQuery)->pluck('email')->map(fn ($email) => strtolower((string) $email))->unique();
        $events = $recipientIds->isEmpty()
            ? []
            : CongressionalStaffEmailEvent::query()
                ->whereIn('campaign_recipient_id', $recipientIds)
                ->selectRaw('event_type, COUNT(*) as aggregate')
                ->groupBy('event_type')
                ->pluck('aggregate', 'event_type')
                ->map(fn ($count) => (int) $count)
                ->all();
        $recipients = (clone $recipientQuery)
            ->with(['campaign:id,name', 'congressionalOutreachDraftRecipient.staffEmail:id,verification_status'])
            ->latest('id')
            ->limit(100)
            ->get();
        $bounceSignals = $recipientEmails->isEmpty()
            ? 0
            : CongressionalStaffChangeSignal::query()
                ->where('user_id', $draft->user_id)
                ->where('signal_type', 'delivery_failure')
                ->get(['target_emails'])
                ->filter(fn (CongressionalStaffChangeSignal $signal) => collect($signal->target_emails ?? [])
                    ->map(fn ($email) => strtolower((string) $email))
                    ->intersect($recipientEmails)
                    ->isNotEmpty())
                ->count();
        $engagement = [
            'opened' => (clone $recipientQuery)->whereNotNull('opened_at')->count(),
            'clicked' => (clone $recipientQuery)->whereNotNull('clicked_at')->count(),
            'replied' => (clone $recipientQuery)->whereNotNull('replied_at')->count(),
            'bounced' => (clone $recipientQuery)->whereNotNull('bounced_at')->count(),
            'unsubscribed' => (clone $recipientQuery)->whereNotNull('unsubscribed_at')->count(),
        ];

        return [
            'campaigns' => $campaigns,
            'recipients' => $recipients,
            'statuses' => $statuses,
            'events' => $events,
            'engagement' => $engagement,
            'bounce_signals' => $bounceSignals,
            'clicks_tracked' => (clone $recipientQuery)->whereNotNull('tracking_token')->exists(),
        ];
    }
}
