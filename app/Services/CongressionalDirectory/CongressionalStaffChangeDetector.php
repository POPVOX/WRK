<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalStaffChangeSignal;
use App\Models\GmailMessage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CongressionalStaffChangeDetector
{
    public function mightContainSignal(string $text): bool
    {
        return (bool) preg_match(
            '/no longer (?:with|at)|left (?:the )?office|has left|departed|my last day|delivery status notification|address not found|message (?:was )?not delivered|undeliverable|recipient address rejected|\b550\s+5\./i',
            $text
        );
    }

    public function detect(GmailMessage $message): ?CongressionalStaffChangeSignal
    {
        if (! $message->is_inbound || ! Schema::hasTable('congressional_staff_change_signals')) {
            return null;
        }

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
        $departure = (bool) preg_match('/no longer (?:with|at)|left (?:the )?office|has left|departed|my last day/i', $text);
        $emails = $this->extractEmails($text);
        $excluded = collect(array_merge(
            [$message->from_email, $message->user?->email],
            $message->to_emails ?? [],
            $message->cc_emails ?? [],
            $message->bcc_emails ?? []
        ))->filter()->map(fn ($email) => Str::lower((string) $email))->all();

        $replacementEmails = collect($emails)->reject(fn (string $email) => in_array($email, $excluded, true))->values();
        $sourceEmail = Str::lower((string) $message->from_email) ?: null;
        $targetEmails = $deliveryFailure
            ? collect($emails)->reject(fn (string $email) => $email === Str::lower((string) $message->user?->email))->values()->all()
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

        return CongressionalStaffChangeSignal::query()->updateOrCreate(
            ['signal_key' => $signalKey],
            [
                'gmail_message_id' => $message->id,
                'user_id' => $message->user_id,
                'signal_type' => $signalType,
                'status' => 'pending',
                'source_email' => $sourceEmail,
                'target_emails' => $targetEmails,
                'replacement_contacts' => $replacementContacts,
                'summary' => $this->summary($signalType, $sourceEmail, $replacementEmails->all()),
                'evidence_excerpt' => Str::limit(preg_replace('/\s+/', ' ', $text) ?: $text, 600),
                'detected_at' => $message->sent_at ?? now(),
            ]
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

    protected function displayNameFromEmail(string $email): string
    {
        $local = Str::before($email, '@');

        return Str::title(preg_replace('/[._\-]+/', ' ', $local) ?: $local);
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
