<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachRecipientEvent extends Model
{
    protected $fillable = [
        'campaign_recipient_id',
        'event_type',
        'event_key',
        'url',
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

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(OutreachCampaignRecipient::class, 'campaign_recipient_id');
    }
}
