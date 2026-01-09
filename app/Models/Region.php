<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Region extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($region) {
            if (empty($region->slug)) {
                $region->slug = Str::slug($region->name);
            }
        });
    }

    /**
     * Countries in this region
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class)->orderBy('name');
    }

    /**
     * Get all geographables of this type
     */
    public function geographables()
    {
        return Geographable::where('geographic_type', 'region')
            ->where('geographic_id', $this->id);
    }

    /**
     * Default regions for seeding
     */
    public static function getDefaults(): array
    {
        return [
            ['name' => 'North America', 'slug' => 'north-america', 'sort_order' => 1],
            ['name' => 'Latin America', 'slug' => 'latin-america', 'sort_order' => 2],
            ['name' => 'Caribbean', 'slug' => 'caribbean', 'sort_order' => 3],
            ['name' => 'Europe', 'slug' => 'europe', 'sort_order' => 4],
            ['name' => 'Africa', 'slug' => 'africa', 'sort_order' => 5],
            ['name' => 'Middle East', 'slug' => 'middle-east', 'sort_order' => 6],
            ['name' => 'Asia', 'slug' => 'asia', 'sort_order' => 7],
            ['name' => 'Oceania', 'slug' => 'oceania', 'sort_order' => 8],
        ];
    }
}
