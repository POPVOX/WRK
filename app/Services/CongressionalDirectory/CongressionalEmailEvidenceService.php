<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalOutreachDraftRecipient;
use App\Models\CongressionalPosition;
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

            if ($campaignRecipientId) {
                $recipientUpdates = match ($eventType) {
                    'human_reply' => ['replied_at' => $occurredAt],
                    'hard_bounce' => ['bounced_at' => $occurredAt],
                    'unsubscribed', 'newsletter_unsubscribed' => ['unsubscribed_at' => $occurredAt],
                    default => [],
                };
                if ($recipientUpdates !== []) {
                    OutreachCampaignRecipient::query()->whereKey($campaignRecipientId)->update($recipientUpdates);
                }
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
                'auto_reply' => in_array($staffEmail->verification_status, ['unverified', 'not_bounced', 'sourced'], true)
                    ? [
                        'verification_status' => 'observed',
                        'first_observed_at' => $staffEmail->first_observed_at ?? $occurredAt,
                        'last_observed_at' => $occurredAt,
                    ]
                    : ['last_observed_at' => $occurredAt],
                'departure_auto_reply' => in_array($staffEmail->verification_status, ['hard_bounced', 'unsubscribed', 'suppressed'], true)
                    ? ['last_observed_at' => $occurredAt]
                    : ['verification_status' => 'departed', 'last_observed_at' => $occurredAt],
                'human_reply' => [
                    'verification_status' => 'replied',
                    'first_observed_at' => $staffEmail->first_observed_at ?? $occurredAt,
                    'last_observed_at' => $occurredAt,
                    'last_replied_at' => $occurredAt,
                ],
                'confirmed' => ['verification_status' => 'confirmed'],
                'hard_bounce' => ['verification_status' => 'hard_bounced', 'hard_bounced_at' => $occurredAt],
                'unsubscribed' => ['verification_status' => 'unsubscribed', 'unsubscribed_at' => $occurredAt],
                'manual_suppressed' => ['verification_status' => 'suppressed'],
                default => [],
            };

            if ($updates !== []) {
                $staffEmail->update($updates);
            }

            if (in_array($eventType, ['hard_bounce', 'departure_auto_reply', 'unsubscribed', 'manual_suppressed'], true)) {
                $reason = match ($eventType) {
                    'hard_bounce' => 'hard_bounce',
                    'departure_auto_reply' => 'departed',
                    'unsubscribed' => 'unsubscribe',
                    default => 'manual',
                };
                $suppression = OutreachEmailSuppression::query()->firstOrNew([
                    'email_normalized' => $staffEmail->email_normalized,
                ]);
                if (! $suppression->exists || $this->suppressionRank($reason) >= $this->suppressionRank($suppression->reason)) {
                    $suppression->fill([
                        'reason' => $reason,
                        'source_type' => $eventType,
                        'gmail_message_id' => $gmailMessageId,
                        'created_by' => $userId,
                        'notes' => $evidenceExcerpt,
                        'metadata' => $metadata === [] ? null : $metadata,
                        'suppressed_at' => $occurredAt,
                    ])->save();
                }
            }

            $retireProfile = $eventType === 'hard_bounce'
                || ($eventType === 'departure_auto_reply' && (bool) data_get($metadata, 'retire_profile', true));
            if ($retireProfile) {
                $this->markProfileInactive(
                    $staffEmail,
                    $eventType,
                    $occurredAt,
                    $gmailMessageId,
                    $evidenceExcerpt
                );
            }

            return $event;
        });
    }

    protected function markProfileInactive(
        CongressionalStaffEmail $staffEmail,
        string $eventType,
        CarbonInterface $occurredAt,
        ?int $gmailMessageId,
        ?string $evidenceExcerpt
    ): void {
        $profile = CongressionalStaffProfile::query()
            ->lockForUpdate()
            ->find($staffEmail->profile_id);
        if (! $profile) {
            return;
        }

        $metadata = $profile->metadata ?? [];
        $metadata['inactivity'] = [
            'reason' => $eventType,
            'detected_at' => $occurredAt->toIso8601String(),
            'gmail_message_id' => $gmailMessageId,
            'staff_email_id' => $staffEmail->id,
            'evidence_excerpt' => $evidenceExcerpt ? Str::limit(Str::squish($evidenceExcerpt), 600) : null,
        ];

        $profile->update([
            'status' => CongressionalStaffProfile::STATUS_INACTIVE,
            'metadata' => $metadata,
        ]);
        $profile->positions()->where('is_current', true)->update([
            'is_current' => false,
            'updated_at' => now(),
        ]);

        CongressionalOutreachDraftRecipient::query()
            ->where('profile_id', $profile->id)
            ->whereDoesntHave('outreachCampaignRecipients')
            ->whereIn('review_status', ['pending', 'approved'])
            ->eachById(function (CongressionalOutreachDraftRecipient $recipient): void {
                $metadata = $recipient->metadata ?? [];
                $metadata['base_exclusion_reason'] = 'inactive_profile';
                $recipient->update([
                    'review_status' => 'excluded',
                    'exclusion_reason' => 'inactive_profile',
                    'approved_by' => null,
                    'reviewed_at' => null,
                    'metadata' => $metadata,
                ]);
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
            ->with('profile')
            ->get();

        if ($signal->signal_type === 'departure_redirect' && $matched->isNotEmpty()) {
            $this->recordReplacementContacts($signal, $matched->first());
        }

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
            $retireProfile = ! $this->replacementKeepsProfileActive($signal, $staffEmail);
            $this->recordEvent(
                $staffEmail,
                $eventType,
                userId: $signal->reviewed_by,
                gmailMessageId: $signal->gmail_message_id,
                campaignRecipientId: $campaignRecipient?->id,
                evidenceExcerpt: $signal->evidence_excerpt,
                metadata: [
                    'change_signal_id' => $signal->id,
                    'signal_type' => $signal->signal_type,
                    'retire_profile' => $retireProfile,
                ],
                occurredAt: $signal->detected_at,
                eventKey: hash('sha256', "change-signal|{$signal->id}|{$staffEmail->id}|{$eventType}")
            );
        }

        return $matched->count();
    }

    protected function replacementKeepsProfileActive(
        CongressionalStaffChangeSignal $signal,
        CongressionalStaffEmail $sourceEmail
    ): bool {
        if ($signal->signal_type !== 'departure_redirect' || ! $sourceEmail->profile) {
            return false;
        }

        $sourceName = Str::upper(Str::squish($sourceEmail->profile->display_name));

        return collect($signal->replacement_contacts ?? [])->contains(function (array $contact) use ($sourceName): bool {
            $replacementName = Str::upper(Str::squish((string) ($contact['display_name'] ?? '')));

            return $replacementName !== '' && $replacementName === $sourceName;
        });
    }

    protected function recordReplacementContacts(
        CongressionalStaffChangeSignal $signal,
        CongressionalStaffEmail $sourceEmail
    ): void {
        $sourceProfile = $sourceEmail->profile()->with('currentPosition')->first();
        if (! $sourceProfile) {
            return;
        }

        foreach ($signal->replacement_contacts ?? [] as $contact) {
            $email = Str::lower(trim((string) ($contact['email'] ?? '')));
            $displayName = trim((string) ($contact['display_name'] ?? ''));
            if (! $this->isPersonReplacement($email, $displayName)) {
                continue;
            }

            $existingEmail = CongressionalStaffEmail::query()
                ->where('email_normalized', $email)
                ->with('profile')
                ->first();
            $normalizedName = Str::upper(Str::squish($displayName));
            $profile = $existingEmail?->profile
                ?: CongressionalStaffProfile::query()
                    ->where('chamber', $sourceProfile->chamber)
                    ->where('normalized_name', $normalizedName)
                    ->first();

            if (! $profile) {
                $profile = CongressionalStaffProfile::query()->firstOrCreate(
                    ['profile_key' => hash('sha256', 'gmail-redirect|'.$email)],
                    [
                        'chamber' => $sourceProfile->chamber,
                        'display_name' => $displayName,
                        'normalized_name' => $normalizedName,
                        'identity_hint' => preg_replace('/[^A-Z0-9]/', '', $normalizedName),
                        'status' => 'reported_active',
                        'review_status' => 'provisional',
                        'first_seen_at' => $signal->detected_at?->toDateString(),
                        'last_seen_at' => $signal->detected_at?->toDateString(),
                        'metadata' => [
                            'source' => 'gmail_redirect',
                            'change_signal_id' => $signal->id,
                        ],
                    ]
                );
            }

            $officeId = $sourceProfile->currentPosition?->office_id;
            if ($officeId && ! $profile->positions()->where('office_id', $officeId)->where('is_current', true)->exists()) {
                CongressionalPosition::query()->firstOrCreate(
                    ['position_key' => hash('sha256', "gmail-redirect|{$profile->id}|{$officeId}")],
                    [
                        'profile_id' => $profile->id,
                        'office_id' => $officeId,
                        'title' => 'Referred congressional contact',
                        'normalized_title' => 'REFERRED CONGRESSIONAL CONTACT',
                        'first_reported_start' => $signal->detected_at?->toDateString(),
                        'last_reported_end' => $signal->detected_at?->toDateString(),
                        'is_current' => true,
                        'confidence' => 'observed',
                    ]
                );
            }

            $this->addAddress(
                $profile,
                $email,
                'redirected',
                $signal->reviewed_by,
                sourceNotes: 'Observed in a congressional staff departure response. Signal #'.$signal->id
            );
        }
    }

    protected function isPersonReplacement(string $email, string $displayName): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || count(preg_split('/\s+/', $displayName) ?: []) < 2) {
            return false;
        }

        $local = Str::before($email, '@');

        return preg_match('/(?:^|[._-])(schedule|scheduling|office|tours?|press|media|info|contact|general|casework|help|communications?)(?:$|[._-])/i', $local) !== 1;
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

    protected function suppressionRank(?string $reason): int
    {
        return match ($reason) {
            'manual', 'unsubscribe' => 4,
            'hard_bounce' => 3,
            'departed' => 2,
            default => 1,
        };
    }
}
