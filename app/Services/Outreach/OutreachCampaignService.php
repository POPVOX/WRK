<?php

namespace App\Services\Outreach;

use App\Jobs\SendOutreachCampaignRecipient;
use App\Models\OutreachActivityLog;
use App\Models\OutreachCampaign;
use App\Models\OutreachCampaignRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OutreachCampaignService
{
    /**
     * @param  array<int,array{email:string,name:?string,person_id:?int}>  $recipients
     */
    public function seedRecipients(OutreachCampaign $campaign, array $recipients): int
    {
        $rows = [];
        $now = now();
        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['email'] ?? '')));
            if ($email === '') {
                continue;
            }

            $rows[] = [
                'campaign_id' => $campaign->id,
                'person_id' => isset($recipient['person_id']) ? (int) $recipient['person_id'] : null,
                'email' => $email,
                'name' => trim((string) ($recipient['name'] ?? '')) ?: null,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('outreach_campaign_recipients')->upsert(
                $rows,
                ['campaign_id', 'email'],
                ['person_id', 'name', 'updated_at']
            );
        }

        $count = OutreachCampaignRecipient::query()
            ->where('campaign_id', $campaign->id)
            ->count();

        $campaign->update([
            'recipients_count' => $count,
            'sent_count' => OutreachCampaignRecipient::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'sent')
                ->count(),
            'failed_count' => OutreachCampaignRecipient::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'failed')
                ->count(),
        ]);

        return $count;
    }

    public function queueCampaign(OutreachCampaign $campaign): int
    {
        $campaign = OutreachCampaign::query()->findOrFail($campaign->id);
        if ($campaign->recipients_count <= 0) {
            throw new RuntimeException('Campaign has no recipients to send.');
        }

        if ($campaign->channel === 'substack') {
            throw new RuntimeException('Substack direct send is not enabled yet. Use this campaign as a planning draft or switch channel to Gmail/Hybrid.');
        }

        if (! in_array($campaign->status, ['draft', 'scheduled', 'failed'], true)) {
            return 0;
        }

        $campaign->update([
            'status' => 'sending',
            'launched_at' => now(),
            'completed_at' => null,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        OutreachCampaignRecipient::query()
            ->where('campaign_id', $campaign->id)
            ->whereIn('status', ['pending', 'failed'])
            ->update([
                'status' => 'queued',
                'error_message' => null,
                'updated_at' => now(),
            ]);

        $queueName = (string) (config('queue.outreach_queue') ?: env('OUTREACH_QUEUE', 'default'));
        $recipientIds = OutreachCampaignRecipient::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'queued')
            ->pluck('id');

        foreach ($recipientIds as $recipientId) {
            SendOutreachCampaignRecipient::dispatch((int) $recipientId)->onQueue($queueName);
        }

        $this->log(
            campaignId: $campaign->id,
            userId: $campaign->user_id,
            action: 'campaign_queued',
            summary: 'Queued outreach campaign for delivery.',
            details: [
                'queued_recipients' => $recipientIds->count(),
                'queue' => $queueName,
            ]
        );

        return $recipientIds->count();
    }

    public function finalizeCampaignIfComplete(int $campaignId): void
    {
        $campaign = OutreachCampaign::query()->find($campaignId);
        if (! $campaign) {
            return;
        }

        $pending = OutreachCampaignRecipient::query()
            ->where('campaign_id', $campaignId)
            ->whereIn('status', ['pending', 'queued', 'sending'])
            ->count();

        $sent = OutreachCampaignRecipient::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->count();

        $failed = OutreachCampaignRecipient::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'failed')
            ->count();

        $updates = [
            'recipients_count' => (int) OutreachCampaignRecipient::query()->where('campaign_id', $campaignId)->count(),
            'sent_count' => $sent,
            'failed_count' => $failed,
        ];

        if ($pending === 0 && $campaign->status === 'sending') {
            $updates['status'] = $sent > 0 ? 'sent' : 'failed';
            $updates['completed_at'] = now();
        }

        $campaign->update($updates);
    }

    public function createSlug(string $name): string
    {
        $base = Str::slug($name);

        return $base !== '' ? $base : 'campaign-'.Str::lower(Str::random(8));
    }

    /**
     * @param  array<string,mixed>  $details
     */
    public function log(
        ?int $campaignId,
        ?int $userId,
        string $action,
        ?string $summary = null,
        array $details = [],
        ?int $newsletterId = null,
        ?int $automationId = null
    ): void {
        OutreachActivityLog::query()->create([
            'user_id' => $userId,
            'campaign_id' => $campaignId,
            'newsletter_id' => $newsletterId,
            'automation_id' => $automationId,
            'action' => $action,
            'summary' => $summary,
            'details' => $details === [] ? null : $details,
        ]);
    }
}
