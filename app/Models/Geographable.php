<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Geographable extends Model
{
    protected $fillable = [
        'geographable_id',
        'geographable_type',
        'geographic_type',
        'geographic_id',
    ];

    /**
     * Get the parent geographable model (Person, Project, Organization)
     */
    public function geographable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the geographic entity (Region, Country, or UsState)
     */
    public function getGeographicAttribute()
    {
        return match ($this->geographic_type) {
            'region' => Region::find($this->geographic_id),
            'country' => Country::find($this->geographic_id),
            'us_state' => UsState::find($this->geographic_id),
            default => null,
        };
    }

    /**
     * Get display name for the geographic tag
     */
    public function getDisplayNameAttribute(): string
    {
        $geo = $this->geographic;
        if (! $geo) {
            return 'Unknown';
        }

        return match ($this->geographic_type) {
            'region' => "🌍 {$geo->name}",
            'country' => "🏳️ {$geo->name}",
            'us_state' => "🇺🇸 {$geo->name}",
            default => $geo->name,
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->geographic_type) {
            'region' => 'Region',
            'country' => 'Country',
            'us_state' => 'US State/Territory',
            default => 'Location',
        };
    }
}
