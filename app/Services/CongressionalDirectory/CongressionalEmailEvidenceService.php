<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\CongressionalStaffProfile;
use App\Models\OutreachCampaignRecipient;
use App\Models\OutreachEmailSuppression;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CongressionalEmailEvidenceService
{
    public const SOURCE_TYPES = ['sourced', 'observed', 'redirected', 'guessed', 'manual'];

    public const EVENT_TYPES = [
        'address_added',
        'observed',
        'sourced',
        'send_accepted',
        'not_bounced',
        'hard_bounce',
        'auto_reply',
        'departure_auto_reply',
        'human_reply',
        'click',
        'newsletter_subscribed',
        'newsletter_unsubscribed',
        'unsubscribed',
        'confirmed',
        'manual_suppressed',
        'manual_restored',
    ];

    public function addAddress(
        CongressionalStaffProfile $profile,
        string $email,
        string $sourceType,
        ?int $userId = null,
        ?string $sourceUrl = null,
        ?string $sourceNotes = null
    ): CongressionalStaffEmail {
        $email = Str::lower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email address is required.');
        }
        if (! in_array($sourceType, self::SOURCE_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported congressional email source type.');
        }

        return DB::transaction(function () use ($profile, $email, $sourceType, $userId, $sourceUrl, $sourceNotes) {
            $verificationStatus = $this->defaultVerificationStatus($sourceType);
            $now = now();
            $staffEmail = CongressionalStaffEmail::query()
                ->where('profile_id', $profile->id)
                ->where('email_normalized', $email)
                ->first();

            if (! $staffEmail) {
                $staffEmail = CongressionalStaffEmail::query()->create([
                    'profile_id' => $profile->id,
                    'email_normalized' => $email,
                    'email' => $email,
                    'source_type' => $sourceType,
                    'verification_status' => $verificationStatus,
                    'source_url' => $sourceUrl,
                    'source_notes' => $sourceNotes,
                    'first_observed_at' => in_array($sourceType, ['guessed', 'manual'], true) ? null : $now,
                    'last_observed_at' => in_array($sourceType, ['guessed', 'manual'], true) ? null : $now,
                    'added_by' => $userId,
                ]);
                $this->recordEvent(
                    $staffEmail,
                    'address_added',
                    userId: $userId,
                    evidenceExcerpt: $sourceNotes,
                    metadata: ['source_type' => $sourceType, 'source_url' => $sourceUrl],
                    eventKey: hash('sha256', "address-added|{$staffEmail->id}")
                );

                return $staffEmail->fresh();
            }

            $updates = [];
            if ($this->sourceRank($sourceType) > $this->sourceRank($staffEmail->source_type)) {
                $updates['source_type'] = $sourceType;
            }
            if (in_array($staffEmail->verification_status, ['unverified', 'not_bounced'], true)
                && in_array($verificationStatus, ['observed', 'sourced'], true)) {
                $updates['verification_status'] = $verificationStatus;
            }
            if (! in_array($sourceType, ['guessed', 'manual'], true)) {
                $updates['first_observed_at'] = $staffEmail->first_observed_at ?? $now;
                $updates['last_observed_at'] = $now;
            }
            if ($sourceUrl) {
                $updates['source_url'] = $sourceUrl;
            }
            if ($sourceNotes) {
                $updates['source_notes'] = $sourceNotes;
            }
            if ($updates !== []) {
                $staffEmail->update($updates);
            }

            if (in_array($sourceType, ['observed', 'redirected', 'sourced'], true)) {
                $this->recordEvent(
                    $staffEmail,
                    $sourceType === 'sourced' ? 'sourced' : 'observed',
                    userId: $userId,
                    evidenceExcerpt: $sourceNotes,
                    metadata: ['source_type' => $sourceType, 'source_url' => $sourceUrl],
                    eventKey: hash('sha256', implode('|', [
                        'source-evidence',
                        $staffEmail->id,
                        $sourceType,
                        $sourceUrl,
                        $sourceNotes,
                    ]))
                );
            }

            return $staffEmail->fresh();
        });
    }

    /**
     * @param  array<string,mixed>  $metadata
     */
    public function recordEvent(
        CongressionalStaffEmail $staffEmail,
        string $eventType,
        ?int $userId = null,
        ?int $gmailMessageId = null,
        ?int $campaignRecipientId = null,
        ?string $evidenceExcerpt = null,
        array $metadata = [],
        ?CarbonInterface $occurredAt = null,
        ?string $eventKey = null
    ): CongressionalStaffEmailEvent {
        if (! in_array($eventType, self::EVENT_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported congressional email evidence event.');
        }

        return DB::transaction(function () use ($staffEmail, $eventType, $userId, $gmailMessageId, $campaignRecipientId, $evidenceExcerpt, $metadata, $occurredAt, $eventKey) {
            $occurredAt ??= now();
            $eventKey ??= hash('sha256', implode('|', [
                $staffEmail->id,
                $eventType,
                $gmailMessageId ?: '',
                $campaignRecipientId ?: '',
                Str::uuid()->toString(),
            ]));
            $event = CongressionalStaffEmailEvent::query()->firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'staff_email_id' => $staffEmail->id,
                    'user_id' => $userId,
                    'gmail_message_id' => $gmailMessageId,
                    'campaign_recipient_id' => $campaignRecipientId,
                    'event_type' => $eventType,
                    'evidence_strength' => $this->evidenceStrength($eventType),
                    'evidence_excerpt' => $evidenceExcerpt,
                    'metadata' => $metadata === [] ? null : $metadata,
                    'occurred_at' => $occurredAt,
                ]
            );

            if (! $event->wasRecentlyCreated) {
                return $event;
            }

            $updates = match ($eventType) {
                'observed' => in_array($staffEmail->verification_status, ['unverified', 'not_bounced', 'sourced'], true)
                    ? ['verification_status' => 'observed', 'last_observed_at' => $occurredAt]
                    : ['last_observed_at' => $occurredAt],
                'sourced' => in_array($staffEmail->verification_status, ['unverified', 'not_bounced'], true)
                    ? ['verification_status' => 'sourced', 'last_observed_at' => $occurredAt]
                    : ['last_observed_at' => $occurredAt],
                'send_accepted' => ['last_sent_at' => $occurredAt],
                'not_bounced' => $staffEmail->verification_status === 'unverified'
                    ? ['verification_status' => 'not_bounced']
                    : [],
                'human_reply' => ['verification_status' => 'replied', 'last_replied_at' => $occurredAt],
                'confirmed' => ['verification_status' => 'confirmed'],
                'hard_bounce' => ['verification_status' => 'hard_bounced', 'hard_bounced_at' => $occurredAt],
                'unsubscribed' => ['verification_status' => 'unsubscribed', 'unsubscribed_at' => $occurredAt],
                'manual_suppressed' => ['verification_status' => 'suppressed'],
                default => [],
            };

            if ($updates !== []) {
                $staffEmail->update($updates);
            }

            if (in_array($eventType, ['hard_bounce', 'unsubscribed', 'manual_suppressed'], true)) {
                OutreachEmailSuppression::query()->updateOrCreate(
                    ['email_normalized' => $staffEmail->email_normalized],
                    [
                        'reason' => match ($eventType) {
                            'hard_bounce' => 'hard_bounce',
                            'unsubscribed' => 'unsubscribe',
                            default => 'manual',
                        },
                        'source_type' => $eventType,
                        'gmail_message_id' => $gmailMessageId,
                        'created_by' => $userId,
                        'notes' => $evidenceExcerpt,
                        'metadata' => $metadata === [] ? null : $metadata,
                        'suppressed_at' => $occurredAt,
                    ]
                );
            }

            return $event;
        });
    }

    public function suppressManually(CongressionalStaffEmail $staffEmail, ?int $userId, ?string $notes = null): void
    {
        $this->recordEvent(
            $staffEmail,
            'manual_suppressed',
            userId: $userId,
            evidenceExcerpt: $notes,
            eventKey: hash('sha256', 'manual-suppression|'.$staffEmail->id.'|'.now()->timestamp)
        );
    }

    public function restoreManualSuppression(CongressionalStaffEmail $staffEmail, ?int $userId): bool
    {
        return DB::transaction(function () use ($staffEmail, $userId) {
            $suppression = OutreachEmailSuppression::query()
                ->where('email_normalized', $staffEmail->email_normalized)
                ->where('reason', 'manual')
                ->first();

            if (! $suppression) {
                return false;
            }

            $suppression->delete();
            $staffEmail->update([
                'verification_status' => match ($staffEmail->source_type) {
                    'observed', 'redirected' => 'observed',
                    'sourced' => 'sourced',
                    default => 'unverified',
                },
            ]);
            $this->recordEvent(
                $staffEmail,
                'manual_restored',
                userId: $userId,
                eventKey: hash('sha256', 'manual-restored|'.$staffEmail->id.'|'.now()->timestamp)
            );

            return true;
        });
    }

    public function recordAcceptedChangeSignal(CongressionalStaffChangeSignal $signal): int
    {
        if ($signal->status !== 'accepted') {
            return 0;
        }

        $eventType = $signal->signal_type === 'delivery_failure' ? 'hard_bounce' : 'departure_auto_reply';
        $emails = $signal->signal_type === 'delivery_failure'
            ? ($signal->target_emails ?? [])
            : array_values(array_filter([$signal->source_email]));
        $matched = CongressionalStaffEmail::query()
            ->whereIn('email_normalized', collect($emails)->map(fn ($email) => Str::lower(trim((string) $email))))
            ->get();

        foreach ($matched as $staffEmail) {
            $campaignRecipient = OutreachCampaignRecipient::query()
                ->where('email', $staffEmail->email_normalized)
                ->whereNotNull('sent_at')
                ->whereHas('campaign', fn ($query) => $query
                    ->where('user_id', $signal->user_id)
                    ->whereNotNull('congressional_outreach_draft_id'))
                ->when($signal->detected_at, fn ($query) => $query->where('sent_at', '<=', $signal->detected_at))
                ->latest('sent_at')
                ->first();
            $this->recordEvent(
                $staffEmail,
                $eventType,
                userId: $signal->reviewed_by,
                gmailMessageId: $signal->gmail_message_id,
                campaignRecipientId: $campaignRecipient?->id,
                evidenceExcerpt: $signal->evidence_excerpt,
                metadata: ['change_signal_id' => $signal->id, 'signal_type' => $signal->signal_type],
                occurredAt: $signal->detected_at,
                eventKey: hash('sha256', "change-signal|{$signal->id}|{$staffEmail->id}|{$eventType}")
            );
        }

        return $matched->count();
    }

    protected function evidenceStrength(string $eventType): string
    {
        return match ($eventType) {
            'hard_bounce', 'human_reply', 'unsubscribed', 'confirmed' => 'high',
            'observed', 'sourced', 'auto_reply', 'departure_auto_reply', 'newsletter_subscribed', 'newsletter_unsubscribed' => 'medium',
            default => 'low',
        };
    }

    protected function defaultVerificationStatus(string $sourceType): string
    {
        return match ($sourceType) {
            'observed', 'redirected' => 'observed',
            'sourced' => 'sourced',
            default => 'unverified',
        };
    }

    protected function sourceRank(string $sourceType): int
    {
        return match ($sourceType) {
            'observed', 'redirected' => 4,
            'sourced' => 3,
            'manual' => 2,
            'guessed' => 1,
            default => 0,
        };
    }
}
