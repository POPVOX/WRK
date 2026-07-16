<?php

namespace App\Jobs;

use App\Models\OutreachCampaignRecipient;
use App\Services\CongressionalDirectory\CongressionalEmailEvidenceService;
use App\Services\GoogleGmailService;
use App\Services\Outreach\OutreachCampaignService;
use App\Services\Outreach\OutreachSuppressionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendOutreachCampaignRecipient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $recipientId) {}

    public function handle(
        GoogleGmailService $gmailService,
        OutreachCampaignService $campaignService,
        OutreachSuppressionService $suppressionService,
        CongressionalEmailEvidenceService $emailEvidence
    ): void {
        $recipient = OutreachCampaignRecipient::query()
            ->with('campaign.user')
            ->find($this->recipientId);

        if (! $recipient || ! $recipient->campaign || ! $recipient->campaign->user) {
            return;
        }

        if (! in_array($recipient->status, ['queued', 'pending'], true)) {
            $campaignService->finalizeCampaignIfComplete((int) $recipient->campaign_id);

            return;
        }

        if ($suppressionService->isSuppressed($recipient->email)) {
            $recipient->update([
                'status' => 'suppressed',
                'error_message' => 'Suppressed by the central outreach policy.',
            ]);
            $campaignService->finalizeCampaignIfComplete((int) $recipient->campaign_id);

            return;
        }

        $campaign = $recipient->campaign;
        $user = $campaign->user;

        $recipient->update([
            'status' => 'sending',
            'error_message' => null,
        ]);

        try {
            $metadata = $recipient->metadata ?? [];
            $trackedHtml = app(\App\Services\Outreach\OutreachTrackingService::class)
                ->trackedHtml($recipient, (string) ($metadata['body_text'] ?? $campaign->body_text ?? ''));
            $result = $gmailService->sendMessage(
                $user,
                $recipient->email,
                (string) ($metadata['subject'] ?? $campaign->subject ?? ''),
                (string) ($metadata['body_text'] ?? $campaign->body_text ?? ''),
                ['html_body' => $trackedHtml]
            );

            $recipient->update([
                'status' => 'sent',
                'external_message_id' => trim((string) ($result['message_id'] ?? '')) ?: null,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            app(\App\Services\ContactActivityService::class)->recordCampaignSend($recipient->fresh());

            try {
                $staffEmailId = (int) ($metadata['congressional_staff_email_id'] ?? 0);
                if ($staffEmailId > 0) {
                    $staffEmail = \App\Models\CongressionalStaffEmail::query()->find($staffEmailId);
                    if ($staffEmail) {
                        $emailEvidence->recordEvent(
                            $staffEmail,
                            'send_accepted',
                            userId: $campaign->user_id,
                            campaignRecipientId: $recipient->id,
                            evidenceExcerpt: 'Gmail accepted the message for delivery.',
                            metadata: ['external_message_id' => $result['message_id'] ?? null],
                            eventKey: hash('sha256', "campaign-send-accepted|{$recipient->id}")
                        );
                    }
                }

                $campaignService->log(
                    campaignId: $campaign->id,
                    userId: $campaign->user_id,
                    action: 'recipient_sent',
                    summary: 'Sent campaign message.',
                    details: [
                        'recipient_email' => $recipient->email,
                        'recipient_name' => $recipient->name,
                        'gmail_message_id' => $result['message_id'] ?? null,
                    ],
                    newsletterId: $campaign->newsletter_id
                );
            } catch (\Throwable $postSendException) {
                report($postSendException);
            }
        } catch (\Throwable $exception) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 2000),
            ]);

            $campaignService->log(
                campaignId: $campaign->id,
                userId: $campaign->user_id,
                action: 'recipient_failed',
                summary: 'Failed to send campaign message.',
                details: [
                    'recipient_email' => $recipient->email,
                    'error' => $exception->getMessage(),
                ],
                newsletterId: $campaign->newsletter_id
            );
        } finally {
            $campaignService->finalizeCampaignIfComplete((int) $campaign->id);
        }
    }
}
