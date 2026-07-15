<?php

namespace App\Services\CongressionalDirectory;

use App\Models\CongressionalStaffEmail;
use App\Models\OutreachEmailSuppression;

class CongressionalEmailEligibilityService
{
    /**
     * @return array{tier:string,campaign_eligible:bool,provisional_test_eligible:bool,reason:string}
     */
    public function evaluate(CongressionalStaffEmail $staffEmail): array
    {
        $suppression = OutreachEmailSuppression::query()
            ->where('email_normalized', $staffEmail->email_normalized)
            ->first();

        if ($suppression) {
            return [
                'tier' => 'blocked',
                'campaign_eligible' => false,
                'provisional_test_eligible' => false,
                'reason' => 'Suppressed: '.str_replace('_', ' ', $suppression->reason),
            ];
        }

        if (in_array($staffEmail->verification_status, ['hard_bounced', 'unsubscribed', 'suppressed'], true)) {
            return [
                'tier' => 'blocked',
                'campaign_eligible' => false,
                'provisional_test_eligible' => false,
                'reason' => 'Address evidence blocks outreach.',
            ];
        }

        if (in_array($staffEmail->verification_status, ['observed', 'sourced', 'replied', 'confirmed'], true)
            || in_array($staffEmail->source_type, ['observed', 'sourced', 'redirected'], true)) {
            return [
                'tier' => 'eligible',
                'campaign_eligible' => true,
                'provisional_test_eligible' => false,
                'reason' => 'Observed, sourced, or confirmed address.',
            ];
        }

        return [
            'tier' => 'limited',
            'campaign_eligible' => false,
            'provisional_test_eligible' => true,
            'reason' => 'Provisional address: permit only a capped, human-approved test message.',
        ];
    }
}
