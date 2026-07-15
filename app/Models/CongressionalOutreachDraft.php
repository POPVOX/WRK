<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
