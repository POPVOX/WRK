<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CongressionalOutreachDraft extends Model
{
    protected $fillable = [
        'congressional_staff_list_id',
        'user_id',
        'name',
        'subject',
        'body_text',
        'batch_size',
        'delivery_mode',
        'cadence_value',
        'cadence_unit',
        'timezone',
        'schedule_status',
        'next_send_at',
        'last_batch_at',
        'status',
        'snapshot_at',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'next_send_at' => 'datetime',
            'last_batch_at' => 'datetime',
            'batch_size' => 'integer',
            'cadence_value' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function staffList(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffList::class, 'congressional_staff_list_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CongressionalOutreachDraftRecipient::class, 'draft_id');
    }

    public function outreachCampaigns(): HasMany
    {
        return $this->hasMany(OutreachCampaign::class, 'congressional_outreach_draft_id');
    }

    public function outreachRecipients(): HasManyThrough
    {
        return $this->hasManyThrough(
            OutreachCampaignRecipient::class,
            OutreachCampaign::class,
            'congressional_outreach_draft_id',
            'campaign_id'
        );
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'congressional_outreach_draft_viewers',
            'draft_id',
            'user_id'
        )->withPivot('added_by')->withTimestamps();
    }

    public function canBeViewedBy(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->viewers()->whereKey($user->id)->exists();
    }

    public function canBeManagedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
