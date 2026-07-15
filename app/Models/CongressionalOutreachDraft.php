<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CongressionalOutreachDraft extends Model
{
    protected $fillable = [
        'congressional_staff_list_id',
        'user_id',
        'name',
        'subject',
        'body_text',
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
