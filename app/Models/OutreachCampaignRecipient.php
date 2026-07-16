<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutreachCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'person_id',
        'congressional_outreach_draft_recipient_id',
        'email',
        'name',
        'status',
        'external_message_id',
        'tracking_token',
        'sent_at',
        'opened_at',
        'clicked_at',
        'replied_at',
        'bounced_at',
        'unsubscribed_at',
        'open_count',
        'click_count',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'replied_at' => 'datetime',
            'bounced_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'open_count' => 'integer',
            'click_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OutreachCampaign::class, 'campaign_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function congressionalOutreachDraftRecipient(): BelongsTo
    {
        return $this->belongsTo(CongressionalOutreachDraftRecipient::class, 'congressional_outreach_draft_recipient_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OutreachRecipientEvent::class, 'campaign_recipient_id')->latest('occurred_at');
    }

    public function contactActivities(): HasMany
    {
        return $this->hasMany(ContactActivity::class, 'campaign_recipient_id');
    }
}
