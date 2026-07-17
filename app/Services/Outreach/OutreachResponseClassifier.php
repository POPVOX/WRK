<?php

namespace App\Services\Outreach;

use App\Models\GmailMessage;

class OutreachResponseClassifier
{
    public function classify(GmailMessage $message): string
    {
        $text = trim(implode(' ', array_filter([
            $message->subject,
            $message->snippet,
            $message->body_text,
        ])));

        if (preg_match('/no longer (?:with|at)|left (?:the )?office|has left|departed|my last day/i', $text) === 1) {
            return 'departure_auto_reply';
        }

        if (preg_match('/automatic reply|auto[ -]?reply|out of (?:the )?office|away from (?:the )?office|vacation responder/i', $text) === 1) {
            return 'auto_reply';
        }

        return 'human_reply';
    }
}
