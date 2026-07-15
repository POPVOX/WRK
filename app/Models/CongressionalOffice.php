<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CongressionalOffice extends Model
{
    protected $fillable = [
        'office_key',
        'chamber',
        'name',
        'normalized_name',
        'office_code',
        'office_type',
        'is_active',
        'first_seen_at',
        'last_seen_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'first_seen_at' => 'date',
            'last_seen_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(CongressionalPosition::class, 'office_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(CongressionalStaffObservation::class, 'office_id');
    }
}
