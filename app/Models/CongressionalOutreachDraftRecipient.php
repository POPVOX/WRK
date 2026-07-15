<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongressionalOutreachDraftRecipient extends Model
{
    protected $fillable = [
        'draft_id',
        'profile_id',
        'staff_email_id',
        'email',
        'email_normalized',
        'name',
        'title',
        'office',
        'eligibility_tier',
        'source_type',
        'verification_status',
        'review_status',
        'exclusion_reason',
        'selection_reason',
        'metadata',
        'approved_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(CongressionalOutreachDraft::class, 'draft_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffProfile::class, 'profile_id');
    }

    public function staffEmail(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffEmail::class, 'staff_email_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
