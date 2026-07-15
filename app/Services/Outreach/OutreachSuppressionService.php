<?php

namespace App\Services\Outreach;

use App\Models\OutreachEmailSuppression;
use Illuminate\Support\Str;

class OutreachSuppressionService
{
    public function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function isSuppressed(string $email): bool
    {
        return OutreachEmailSuppression::query()
            ->where('email_normalized', $this->normalize($email))
            ->exists();
    }

    /**
     * @param  array<int,string>  $emails
     * @return array<int,string>
     */
    public function suppressedEmails(array $emails): array
    {
        $normalized = collect($emails)
            ->map(fn ($email) => $this->normalize((string) $email))
            ->filter()
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return [];
        }

        return OutreachEmailSuppression::query()
            ->whereIn('email_normalized', $normalized)
            ->pluck('email_normalized')
            ->all();
    }
}
