<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function currentPosition(): HasOne
    {
        return $this->hasOne(CongressionalPosition::class, 'profile_id')
            ->where('is_current', true)
            ->latestOfMany('last_reported_end');
    }

    public function latestPosition(): HasOne
    {
        return $this->hasOne(CongressionalPosition::class, 'profile_id')
            ->latestOfMany('last_reported_end');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(CongressionalStaffObservation::class, 'profile_id');
    }

    public function latestObservation(): HasOne
    {
        return $this->hasOne(CongressionalStaffObservation::class, 'profile_id')->latestOfMany();
    }

    public function emails(): HasMany
    {
        return $this->hasMany(CongressionalStaffEmail::class, 'profile_id');
    }

    public function staffLists(): BelongsToMany
    {
        return $this->belongsToMany(
            CongressionalStaffList::class,
            'congressional_staff_list_members',
            'congressional_staff_profile_id',
            'congressional_staff_list_id'
        )->withPivot(['added_by'])->withTimestamps();
    }
}
