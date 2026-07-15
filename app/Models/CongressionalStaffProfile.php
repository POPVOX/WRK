<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CongressionalStaffProfile extends Model
{
    protected $fillable = [
        'person_id',
        'profile_key',
        'chamber',
        'display_name',
        'normalized_name',
        'identity_hint',
        'status',
        'review_status',
        'first_seen_at',
        'last_seen_at',
        'latest_period_end',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'date',
            'last_seen_at' => 'date',
            'latest_period_end' => 'date',
            'metadata' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(CongressionalPosition::class, 'profile_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(CongressionalStaffObservation::class, 'profile_id');
    }
}
