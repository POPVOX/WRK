<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Country extends Model
{
    protected $fillable = [
        'region_id',
        'name',
        'slug',
        'iso_code',
        'iso_code_3',
        'sort_order',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($country) {
            if (empty($country->slug)) {
                $country->slug = Str::slug($country->name);
            }
        });
    }

    /**
     * Region this country belongs to
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Scope: By region
     */
    public function scopeInRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Get display name with region
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->region->name})";
    }
}

