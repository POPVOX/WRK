<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutreachCampaign extends Model
{
    protected $fillable = [
        'newsletter_id',
        'user_id',
        'project_id',
        'name',
        'campaign_type',
        'channel',
        'status',
        'subject',
        'preheader',
        'body_text',
        'body_markdown',
        'send_mode',
        'scheduled_for',
        'launched_at',
        'completed_at',
        'recipients_count',
        'sent_count',
        'failed_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'launched_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(OutreachNewsletter::class, 'newsletter_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(OutreachCampaignRecipient::class, 'campaign_id')->orderBy('id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OutreachActivityLog::class, 'campaign_id')->latest('created_at');
    }
}

