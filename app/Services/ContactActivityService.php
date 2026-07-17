<?php

namespace App\Services;

use App\Models\CongressionalStaffEmail;
use App\Models\ContactActivity;
use App\Models\GmailMessage;
use App\Models\OutreachCampaignRecipient;
use App\Services\Outreach\OutreachResponseClassifier;
use Illuminate\Support\Str;

class ContactActivityService
{
    public function __construct(
        protected OutreachResponseClassifier $responseClassifier
    ) {}

    public function recordCampaignSend(OutreachCampaignRecipient $recipient): void
    {
        $recipient->loadMissing(['campaign', 'person', 'congressionalOutreachDraftRecipient.profile']);
        $profile = $recipient->congressionalOutreachDraftRecipient?->profile;
        $personId = $recipient->person_id ?: $profile?->person_id;

        $this->recordTargets(
            personId: $personId,
            profileIds: $profile ? [$profile->id] : [],
            attributes: [
                'user_id' => $recipient->campaign?->user_id,
                'campaign_recipient_id' => $recipient->id,
                'activity_type' => 'email',
                'direction' => 'outbound',
                'subject' => data_get($recipient->metadata, 'subject', $recipient->campaign?->subject),
                'summary' => 'Sent outreach email to '.$recipient->email.'.',
                'source_type' => 'campaign',
                'occurred_at' => $recipient->sent_at ?: now(),
                'metadata' => [
                    'campaign_id' => $recipient->campaign_id,
                    'campaign_name' => $recipient->campaign?->name,
                    'email' => $recipient->email,
                ],
            ],
            sourceSeed: 'campaign-send|'.$recipient->id
        );
    }

    public function recordGmailMessage(GmailMessage $message): void
    {
        $counterpart = $message->is_inbound
            ? Str::lower((string) $message->from_email)
            : collect(array_merge($message->to_emails ?? [], $message->cc_emails ?? [], $message->bcc_emails ?? []))
                ->map(fn ($email) => Str::lower(trim((string) $email)))
                ->first(fn ($email) => $email !== '' && $email !== Str::lower((string) $message->user?->email));

        if (! $counterpart) {
            return;
        }

        $profileIds = CongressionalStaffEmail::query()
            ->where('email_normalized', $counterpart)
            ->pluck('profile_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $campaignRecipient = $this->campaignRecipient($message, $counterpart);
        if ($message->is_inbound
            && $campaignRecipient
            && ! $campaignRecipient->replied_at
            && $this->responseClassifier->classify($message) === 'human_reply') {
            $campaignRecipient->update(['replied_at' => $message->sent_at ?: now()]);
        }

        // Campaign sends are logged immediately when Gmail accepts them. When the
        // same outbound message later arrives through Gmail sync, enrich those
        // timeline entries instead of creating a visually duplicate activity.
        if (! $message->is_inbound && $campaignRecipient) {
            $updated = ContactActivity::query()
                ->where('campaign_recipient_id', $campaignRecipient->id)
                ->where('activity_type', 'email')
                ->where('direction', 'outbound')
                ->update(['gmail_message_id' => $message->id]);

            if ($updated > 0) {
                return;
            }
        }

        $this->recordTargets(
            personId: $message->person_id,
            profileIds: $profileIds,
            attributes: [
                'user_id' => $message->user_id,
                'campaign_recipient_id' => $campaignRecipient?->id,
                'gmail_message_id' => $message->id,
                'activity_type' => 'email',
                'direction' => $message->is_inbound ? 'inbound' : 'outbound',
                'subject' => $message->subject,
                'summary' => $message->snippet,
                'source_type' => 'gmail',
                'occurred_at' => $message->sent_at ?: now(),
                'metadata' => ['counterpart_email' => $counterpart, 'gmail_thread_id' => $message->gmail_thread_id],
            ],
            sourceSeed: 'gmail|'.$message->id
        );
    }

    protected function campaignRecipient(GmailMessage $message, string $counterpart): ?OutreachCampaignRecipient
    {
        if (! $message->is_inbound) {
            return OutreachCampaignRecipient::query()->where('external_message_id', $message->gmail_message_id)->first();
        }

        return OutreachCampaignRecipient::query()
            ->where('email', $counterpart)
            ->whereNotNull('sent_at')
            ->whereHas('campaign', fn ($query) => $query->where('user_id', $message->user_id))
            ->when($message->sent_at, fn ($query) => $query->where('sent_at', '<=', $message->sent_at))
            ->latest('sent_at')
            ->first();
    }

    /** @param array<int,int> $profileIds @param array<string,mixed> $attributes */
    protected function recordTargets(?int $personId, array $profileIds, array $attributes, string $sourceSeed): void
    {
        if ($personId) {
            ContactActivity::query()->firstOrCreate(
                ['source_key' => hash('sha256', $sourceSeed.'|person|'.$personId)],
                ['person_id' => $personId, ...$attributes]
            );
        }

        foreach (array_unique($profileIds) as $profileId) {
            ContactActivity::query()->firstOrCreate(
                ['source_key' => hash('sha256', $sourceSeed.'|staff|'.$profileId)],
                ['congressional_staff_profile_id' => $profileId, ...$attributes]
            );
        }
    }
}
