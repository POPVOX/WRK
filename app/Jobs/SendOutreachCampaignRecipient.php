<?php

namespace App\Jobs;

use App\Models\OutreachCampaignRecipient;
use App\Services\GoogleGmailService;
use App\Services\Outreach\OutreachCampaignService;
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

    public function handle(GoogleGmailService $gmailService, OutreachCampaignService $campaignService): void
    {
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

        $campaign = $recipient->campaign;
        $user = $campaign->user;

        $recipient->update([
            'status' => 'sending',
            'error_message' => null,
        ]);

        try {
            $result = $gmailService->sendMessage(
                $user,
                $recipient->email,
                (string) ($campaign->subject ?? ''),
                (string) ($campaign->body_text ?? ''),
                []
            );

            $recipient->update([
                'status' => 'sent',
                'external_message_id' => trim((string) ($result['message_id'] ?? '')) ?: null,
                'sent_at' => now(),
                'error_message' => null,
            ]);

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

