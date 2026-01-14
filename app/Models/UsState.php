<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UsState extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'abbreviation',
        'type',
        'sort_order',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($state) {
            if (empty($state->slug)) {
                $state->slug = Str::slug($state->name);
            }
        });
    }

    /**
     * Scope: States only
     */
    public function scopeStatesOnly($query)
    {
        return $query->where('type', 'state');
    }

    /**
     * Scope: Territories only
     */
    public function scopeTerritoriesOnly($query)
    {
        return $query->where('type', 'territory');
    }

    /**
     * Get display name with abbreviation
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->abbreviation})";
    }

    /**
     * Check if this is a territory
     */
    public function getIsTerritoryAttribute(): bool
    {
        return $this->type === 'territory';
    }

    /**
     * Default US States and Territories
     */
    public static function getDefaults(): array
    {
        return [
            // States
            ['name' => 'Alabama', 'abbreviation' => 'AL', 'type' => 'state'],
            ['name' => 'Alaska', 'abbreviation' => 'AK', 'type' => 'state'],
            ['name' => 'Arizona', 'abbreviation' => 'AZ', 'type' => 'state'],
            ['name' => 'Arkansas', 'abbreviation' => 'AR', 'type' => 'state'],
            ['name' => 'California', 'abbreviation' => 'CA', 'type' => 'state'],
            ['name' => 'Colorado', 'abbreviation' => 'CO', 'type' => 'state'],
            ['name' => 'Connecticut', 'abbreviation' => 'CT', 'type' => 'state'],
            ['name' => 'Delaware', 'abbreviation' => 'DE', 'type' => 'state'],
            ['name' => 'Florida', 'abbreviation' => 'FL', 'type' => 'state'],
            ['name' => 'Georgia', 'abbreviation' => 'GA', 'type' => 'state'],
            ['name' => 'Hawaii', 'abbreviation' => 'HI', 'type' => 'state'],
            ['name' => 'Idaho', 'abbreviation' => 'ID', 'type' => 'state'],
            ['name' => 'Illinois', 'abbreviation' => 'IL', 'type' => 'state'],
            ['name' => 'Indiana', 'abbreviation' => 'IN', 'type' => 'state'],
            ['name' => 'Iowa', 'abbreviation' => 'IA', 'type' => 'state'],
            ['name' => 'Kansas', 'abbreviation' => 'KS', 'type' => 'state'],
            ['name' => 'Kentucky', 'abbreviation' => 'KY', 'type' => 'state'],
            ['name' => 'Louisiana', 'abbreviation' => 'LA', 'type' => 'state'],
            ['name' => 'Maine', 'abbreviation' => 'ME', 'type' => 'state'],
            ['name' => 'Maryland', 'abbreviation' => 'MD', 'type' => 'state'],
            ['name' => 'Massachusetts', 'abbreviation' => 'MA', 'type' => 'state'],
            ['name' => 'Michigan', 'abbreviation' => 'MI', 'type' => 'state'],
            ['name' => 'Minnesota', 'abbreviation' => 'MN', 'type' => 'state'],
            ['name' => 'Mississippi', 'abbreviation' => 'MS', 'type' => 'state'],
            ['name' => 'Missouri', 'abbreviation' => 'MO', 'type' => 'state'],
            ['name' => 'Montana', 'abbreviation' => 'MT', 'type' => 'state'],
            ['name' => 'Nebraska', 'abbreviation' => 'NE', 'type' => 'state'],
            ['name' => 'Nevada', 'abbreviation' => 'NV', 'type' => 'state'],
            ['name' => 'New Hampshire', 'abbreviation' => 'NH', 'type' => 'state'],
            ['name' => 'New Jersey', 'abbreviation' => 'NJ', 'type' => 'state'],
            ['name' => 'New Mexico', 'abbreviation' => 'NM', 'type' => 'state'],
            ['name' => 'New York', 'abbreviation' => 'NY', 'type' => 'state'],
            ['name' => 'North Carolina', 'abbreviation' => 'NC', 'type' => 'state'],
            ['name' => 'North Dakota', 'abbreviation' => 'ND', 'type' => 'state'],
            ['name' => 'Ohio', 'abbreviation' => 'OH', 'type' => 'state'],
            ['name' => 'Oklahoma', 'abbreviation' => 'OK', 'type' => 'state'],
            ['name' => 'Oregon', 'abbreviation' => 'OR', 'type' => 'state'],
            ['name' => 'Pennsylvania', 'abbreviation' => 'PA', 'type' => 'state'],
            ['name' => 'Rhode Island', 'abbreviation' => 'RI', 'type' => 'state'],
            ['name' => 'South Carolina', 'abbreviation' => 'SC', 'type' => 'state'],
            ['name' => 'South Dakota', 'abbreviation' => 'SD', 'type' => 'state'],
            ['name' => 'Tennessee', 'abbreviation' => 'TN', 'type' => 'state'],
            ['name' => 'Texas', 'abbreviation' => 'TX', 'type' => 'state'],
            ['name' => 'Utah', 'abbreviation' => 'UT', 'type' => 'state'],
            ['name' => 'Vermont', 'abbreviation' => 'VT', 'type' => 'state'],
            ['name' => 'Virginia', 'abbreviation' => 'VA', 'type' => 'state'],
            ['name' => 'Washington', 'abbreviation' => 'WA', 'type' => 'state'],
            ['name' => 'West Virginia', 'abbreviation' => 'WV', 'type' => 'state'],
            ['name' => 'Wisconsin', 'abbreviation' => 'WI', 'type' => 'state'],
            ['name' => 'Wyoming', 'abbreviation' => 'WY', 'type' => 'state'],
            // District
            ['name' => 'District of Columbia', 'abbreviation' => 'DC', 'type' => 'district'],
            // Territories
            ['name' => 'American Samoa', 'abbreviation' => 'AS', 'type' => 'territory'],
            ['name' => 'Guam', 'abbreviation' => 'GU', 'type' => 'territory'],
            ['name' => 'Northern Mariana Islands', 'abbreviation' => 'MP', 'type' => 'territory'],
            ['name' => 'Puerto Rico', 'abbreviation' => 'PR', 'type' => 'territory'],
            ['name' => 'U.S. Virgin Islands', 'abbreviation' => 'VI', 'type' => 'territory'],
        ];
    }
}

