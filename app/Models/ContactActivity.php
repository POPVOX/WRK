<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactActivity extends Model
{
    protected $fillable = [
        'person_id',
        'congressional_staff_profile_id',
        'user_id',
        'meeting_id',
        'campaign_recipient_id',
        'gmail_message_id',
        'activity_type',
        'direction',
        'subject',
        'summary',
        'source_type',
        'source_key',
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

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function congressionalStaffProfile(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function campaignRecipient(): BelongsTo
    {
        return $this->belongsTo(OutreachCampaignRecipient::class, 'campaign_recipient_id');
    }

    public function gmailMessage(): BelongsTo
    {
        return $this->belongsTo(GmailMessage::class);
    }
}
