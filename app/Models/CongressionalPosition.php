<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CongressionalPosition extends Model
{
    protected $fillable = [
        'profile_id',
        'office_id',
        'position_key',
        'title',
        'normalized_title',
        'first_reported_start',
        'last_reported_end',
        'is_current',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'first_reported_start' => 'date',
            'last_reported_end' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffProfile::class, 'profile_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(CongressionalOffice::class, 'office_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(CongressionalStaffObservation::class, 'position_id');
    }
}
