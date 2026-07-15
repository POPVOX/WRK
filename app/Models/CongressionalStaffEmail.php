<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CongressionalStaffEmail extends Model
{
    protected $fillable = [
        'profile_id',
        'email',
        'email_normalized',
        'source_type',
        'verification_status',
        'is_primary',
        'source_url',
        'source_notes',
        'metadata',
        'first_observed_at',
        'last_observed_at',
        'last_sent_at',
        'last_replied_at',
        'hard_bounced_at',
        'unsubscribed_at',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'metadata' => 'array',
            'first_observed_at' => 'datetime',
            'last_observed_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'last_replied_at' => 'datetime',
            'hard_bounced_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffProfile::class, 'profile_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CongressionalStaffEmailEvent::class, 'staff_email_id');
    }
}
