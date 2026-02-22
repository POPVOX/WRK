<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'newsletter_id',
        'campaign_id',
        'automation_id',
        'action',
        'summary',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(OutreachNewsletter::class, 'newsletter_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OutreachCampaign::class, 'campaign_id');
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(OutreachAutomation::class, 'automation_id');
    }
}

