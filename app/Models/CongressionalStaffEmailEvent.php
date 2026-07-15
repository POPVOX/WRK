<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongressionalStaffEmailEvent extends Model
{
    protected $fillable = [
        'staff_email_id',
        'user_id',
        'gmail_message_id',
        'campaign_recipient_id',
        'event_key',
        'event_type',
        'evidence_strength',
        'evidence_excerpt',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function staffEmail(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffEmail::class, 'staff_email_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gmailMessage(): BelongsTo
    {
        return $this->belongsTo(GmailMessage::class);
    }

    public function campaignRecipient(): BelongsTo
    {
        return $this->belongsTo(OutreachCampaignRecipient::class, 'campaign_recipient_id');
    }
}
