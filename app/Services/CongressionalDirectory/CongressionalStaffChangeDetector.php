<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalStaffChangeSignal;
use App\Models\CongressionalStaffEmail;
use App\Models\CongressionalStaffEmailEvent;
use App\Models\GmailMessage;
use App\Models\OutreachCampaignRecipient;
use App\Services\Outreach\OutreachResponseClassifier;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CongressionalStaffChangeDetector
{
    private const DEPARTURE_PATTERN = '/(?:no longer\s+(?:with|at)\b|no longer\s+(?:work(?:ing)?|serve|serving|employed)\s+(?:with|for|in|at|by)\b|no longer\s+(?:on\s+(?:the\s+)?(?:capitol\s+)?hill|in\s+(?:congress|the\s+house|the\s+senate))\b|(?:my|this)\s+inbox\s+is\s+no longer monitored|transitioned out of (?:my|the) role|left (?:the )?office|has left|departed|my last day|last day with|moved on from|not employed by)/i';

    public function __construct(
        protected CongressionalEmailEvidenceService $emailEvidence,
        protected OutreachResponseClassifier $responseClassifier
    ) {}

    public function mightContainSignal(string $text): bool
    {
        return $this->containsDeparture($text) || (bool) preg_match(
            '/delivery status notification|address not found|message (?:was )?not delivered|undeliverable|recipient address rejected|\b550\s+5\./i',
            $text
        );
    }

    /** @return array<int,string> */
    public function candidateSqlPatterns(): array
    {
        return [
            '%no longer with%',
            '%no longer at%',
            '%no longer work%',
            '%no longer serve%',
            '%no longer on the hill%',
            '%no longer on hill%',
            '%no longer on capitol hill%',
            '%no longer in congress%',
            '%no longer in the house%',
            '%no longer in the senate%',
            '%inbox is no longer monitored%',
            '%transitioned out of my role%',
            '%transitioned out of the role%',
            '%left the office%',
            '%has left%',
            '%departed%',
            '%my last day%',
            '%last day with%',
            '%moved on from%',
            '%not employed by%',
            '%delivery status notification%',
            '%address not found%',
            '%not delivered%',
            '%undeliverable%',
            '%recipient address rejected%',
            '%550 5.%',
        ];
    }

    public function detect(GmailMessage $message): ?CongressionalStaffChangeSignal
    {
        if (! $message->is_inbound || ! Schema::hasTable('congressional_staff_change_signals')) {
            return null;
        }

        $this->recordOutreachResponse($message);

        $text = trim(implode("\n", array_filter([
            $message->subject,
            $message->snippet,
            $message->body_text,
        ])));

        if (! $this->mightContainSignal($text)) {
            return null;
        }

        $deliveryFailure = (bool) preg_match(
            '/delivery status notification|address not found|message (?:was )?not delivered|undeliverable|recipient address rejected|\b550\s+5\./i',
            $text
        );
        $departure = $this->containsDeparture($text);
        $emails = $this->extractEmails($text);
        $excluded = collect(array_merge(
            [$message->from_email, $message->user?->email],
            $message->to_emails ?? [],
            $message->cc_emails ?? [],
            $message->bcc_emails ?? []
        ))->filter()->map(fn ($email) => Str::lower((string) $email))->all();

        $replacementEmails = $deliveryFailure
            ? collect()
            : collect($emails)
                ->reject(fn (string $email) => in_array($email, $excluded, true))
                ->filter(fn (string $email) => $this->isLikelyPersonReplacement($email))
                ->values();
        $sourceEmail = Str::lower((string) $message->from_email) ?: null;
        $targetEmails = $deliveryFailure
            ? $this->deliveryFailureTargets($message, $emails)
            : array_values(array_filter([$sourceEmail]));
        $signalType = $deliveryFailure ? 'delivery_failure' : ($replacementEmails->isNotEmpty() ? 'departure_redirect' : 'departure');
        $replacementContacts = $replacementEmails->map(fn (string $email) => [
            'email' => $email,
            'display_name' => $this->displayNameFromEmail($email),
            'verification_status' => 'observed_in_message',
        ])->all();
        $signalKey = hash('sha256', implode('|', [
            $message->gmail_message_id,
            $signalType,
            implode(',', $targetEmails),
            $replacementEmails->implode(','),
        ]));

        $matchingTypes = $deliveryFailure
            ? ['delivery_failure']
            : ['departure', 'departure_redirect'];
        $matchingSignals = CongressionalStaffChangeSignal::query()
            ->where('gmail_message_id', $message->id)
            ->whereIn('signal_type', $matchingTypes)
            ->get();
        $signal = $matchingSignals
            ->sortByDesc(fn (CongressionalStaffChangeSignal $candidate): int => ($candidate->reviewed_at ? 2 : 0) + ($candidate->signal_key === $signalKey ? 1 : 0))
            ->first()
            ?? CongressionalStaffChangeSignal::query()->firstOrNew(['signal_key' => $signalKey]);
        $isNew = ! $signal->exists;
        if ($signal->exists) {
            CongressionalStaffChangeSignal::query()
                ->whereIn('id', $matchingSignals->where('id', '!=', $signal->id)->pluck('id'))
                ->delete();
            $signal->signal_key = $signalKey;
        }
        $signal->fill([
            'gmail_message_id' => $message->id,
            'user_id' => $message->user_id,
            'signal_type' => $signalType,
            'source_email' => $sourceEmail,
            'target_emails' => $targetEmails,
            'replacement_contacts' => $replacementContacts,
            'summary' => $this->summary($signalType, $sourceEmail, $replacementEmails->all()),
            'evidence_excerpt' => Str::limit(preg_replace('/\s+/', ' ', $text) ?: $text, 600),
            'detected_at' => $message->sent_at ?? now(),
        ]);

        if ($isNew) {
            $signal->status = 'pending';
        } elseif ($signal->status === 'pending' && $signal->reviewed_at) {
            $signal->status = CongressionalStaffEmailEvent::query()
                ->where('metadata->change_signal_id', $signal->id)
                ->exists() ? 'accepted' : 'rejected';
        }

        $knownDepartureSource = ! $deliveryFailure
            && $departure
            && $sourceEmail
            && CongressionalStaffEmail::query()->where('email_normalized', $sourceEmail)->exists();
        $autoAccept = ($deliveryFailure
            && $signal->status === 'pending'
            && ! $signal->reviewed_at
            && $this->isClearHardBounce($message, $text, $targetEmails))
            || ($knownDepartureSource
                && $signal->status === 'pending'
                && ! $signal->reviewed_at);
        if ($autoAccept) {
            $signal->status = 'accepted';
            $signal->reviewed_at = now();
        }

        $signal->save();

        if ($signal->status === 'accepted') {
            $this->emailEvidence->recordAcceptedChangeSignal($signal->fresh());
        }

        return $signal->fresh();
    }

    protected function containsDeparture(string $text): bool
    {
        return preg_match(self::DEPARTURE_PATTERN, $text) === 1;
    }

    protected function recordOutreachResponse(GmailMessage $message): void
    {
        $from = Str::lower(trim((string) $message->from_email));
        if ($from === '' || ! Schema::hasTable('outreach_campaign_recipients')) {
            return;
        }

        $recipient = OutreachCampaignRecipient::query()
            ->where('email', $from)
            ->whereNotNull('sent_at')
            ->whereHas('campaign', fn ($query) => $query
                ->where('user_id', $message->user_id)
                ->whereNotNull('congressional_outreach_draft_id'))
            ->when($message->sent_at, fn ($query) => $query->where('sent_at', '<=', $message->sent_at))
            ->latest('sent_at')
            ->first();
        $staffEmailId = (int) data_get($recipient?->metadata, 'congressional_staff_email_id');
        if (! $recipient || $staffEmailId <= 0) {
            return;
        }

        $staffEmail = \App\Models\CongressionalStaffEmail::query()->find($staffEmailId);
        if (! $staffEmail) {
            return;
        }

        $text = trim(($message->subject ?? '').' '.($message->snippet ?? '').' '.($message->body_text ?? ''));
        $eventType = $this->responseClassifier->classify($message);
        $this->emailEvidence->recordEvent(
            $staffEmail,
            $eventType,
            userId: $message->user_id,
            gmailMessageId: $message->id,
            campaignRecipientId: $recipient->id,
            evidenceExcerpt: Str::limit(Str::squish($text), 600),
            occurredAt: $message->sent_at,
            eventKey: hash('sha256', "gmail-outreach-response|{$message->id}|{$staffEmail->id}|{$eventType}")
        );
    }

    /** @return array<int, string> */
    protected function extractEmails(string $text): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($email) => Str::lower(rtrim((string) $email, '.,;:)>]')))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<int, string> $emails @return array<int, string> */
    protected function deliveryFailureTargets(GmailMessage $message, array $emails): array
    {
        $candidates = collect($emails)
            ->map(fn (string $email) => Str::lower($email))
            ->reject(fn (string $email) => in_array($email, [
                Str::lower((string) $message->from_email),
                Str::lower((string) $message->user?->email),
            ], true))
            ->unique()
            ->values();
        if ($candidates->isEmpty()) {
            return [];
        }

        $known = CongressionalStaffEmail::query()
            ->whereIn('email_normalized', $candidates)
            ->pluck('email_normalized');
        $sent = OutreachCampaignRecipient::query()
            ->whereIn('email', $candidates)
            ->whereNotNull('sent_at')
            ->whereHas('campaign', fn ($query) => $query->where('user_id', $message->user_id))
            ->when($message->sent_at, fn ($query) => $query->where('sent_at', '<=', $message->sent_at))
            ->pluck('email');
        $recognized = $known->merge($sent)->unique()->values();

        return ($recognized->isNotEmpty() ? $recognized : $candidates)->all();
    }

    /** @param array<int, string> $targetEmails */
    protected function isClearHardBounce(GmailMessage $message, string $text, array $targetEmails): bool
    {
        $sender = Str::lower(trim((string) $message->from_email));
        $trustedSender = preg_match('/mailer-daemon|postmaster|mail delivery subsystem/', $sender.' '.Str::lower((string) $message->from_name)) === 1;
        $hardFailure = preg_match('/address not found|no such user|user unknown|recipient address rejected|does not exist|\b550[ -]5\.[01]\./i', $text) === 1;
        $knownAddress = CongressionalStaffEmail::query()->whereIn('email_normalized', $targetEmails)->exists();

        return $trustedSender && $hardFailure && $knownAddress;
    }

    protected function displayNameFromEmail(string $email): string
    {
        $local = Str::before($email, '@');

        return Str::title(preg_replace('/[._\-]+/', ' ', $local) ?: $local);
    }

    protected function isLikelyPersonReplacement(string $email): bool
    {
        $local = Str::before($email, '@');
        if (preg_match('/(?:^|[._-])(schedule|scheduling|office|tours?|press|media|info|contact|general|casework|help|communications?)(?:$|[._-])/i', $local) === 1) {
            return false;
        }

        return count(preg_split('/[._-]+/', $local) ?: []) >= 2;
    }

    /** @param array<int, string> $replacements */
    protected function summary(string $type, ?string $sourceEmail, array $replacements): string
    {
        if ($type === 'delivery_failure') {
            return 'Gmail reported that a congressional outreach address may no longer accept mail.';
        }

        $summary = ($sourceEmail ?: 'A sender').' reported a staff departure.';
        if ($replacements !== []) {
            $summary .= ' Suggested replacement '.Str::plural('contact', count($replacements)).': '.implode(', ', $replacements).'.';
        }

        return $summary;
    }
}
