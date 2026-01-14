<?php

namespace App\Models\Traits;

use App\Models\Country;
use App\Models\Geographable;
use App\Models\Region;
use App\Models\UsState;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasGeographicTags
{
    /**
     * Get all geographic tags for this model
     */
    public function geographicTags(): MorphMany
    {
        return $this->morphMany(Geographable::class, 'geographable');
    }

    /**
     * Get regions tagged to this model
     */
    public function getRegionsAttribute()
    {
        $regionIds = $this->geographicTags()
            ->where('geographic_type', 'region')
            ->pluck('geographic_id');

        return Region::whereIn('id', $regionIds)->orderBy('sort_order')->get();
    }

    /**
     * Get countries tagged to this model
     */
    public function getCountriesAttribute()
    {
        $countryIds = $this->geographicTags()
            ->where('geographic_type', 'country')
            ->pluck('geographic_id');

        return Country::whereIn('id', $countryIds)->orderBy('name')->get();
    }

    /**
     * Get US states tagged to this model
     */
    public function getUsStatesAttribute()
    {
        $stateIds = $this->geographicTags()
            ->where('geographic_type', 'us_state')
            ->pluck('geographic_id');

        return UsState::whereIn('id', $stateIds)->orderBy('name')->get();
    }

    /**
     * Get all geographic tags as a formatted collection
     */
    public function getAllGeographicTagsAttribute()
    {
        return $this->geographicTags->map(fn ($tag) => [
            'id' => $tag->id,
            'type' => $tag->geographic_type,
            'geographic_id' => $tag->geographic_id,
            'name' => $tag->display_name,
            'type_label' => $tag->type_label,
        ]);
    }

    /**
     * Sync regions for this model
     */
    public function syncRegions(array $regionIds): void
    {
        $this->geographicTags()->where('geographic_type', 'region')->delete();

        foreach ($regionIds as $regionId) {
            $this->geographicTags()->create([
                'geographic_type' => 'region',
                'geographic_id' => $regionId,
            ]);
        }
    }

    /**
     * Sync countries for this model
     */
    public function syncCountries(array $countryIds): void
    {
        $this->geographicTags()->where('geographic_type', 'country')->delete();

        foreach ($countryIds as $countryId) {
            $this->geographicTags()->create([
                'geographic_type' => 'country',
                'geographic_id' => $countryId,
            ]);
        }
    }

    /**
     * Sync US states for this model
     */
    public function syncUsStates(array $stateIds): void
    {
        $this->geographicTags()->where('geographic_type', 'us_state')->delete();

        foreach ($stateIds as $stateId) {
            $this->geographicTags()->create([
                'geographic_type' => 'us_state',
                'geographic_id' => $stateId,
            ]);
        }
    }

    /**
     * Sync all geographic tags at once
     */
    public function syncGeographicTags(array $regionIds = [], array $countryIds = [], array $stateIds = []): void
    {
        $this->syncRegions($regionIds);
        $this->syncCountries($countryIds);
        $this->syncUsStates($stateIds);
    }

    /**
     * Add a region tag
     */
    public function addRegion(int $regionId): void
    {
        $this->geographicTags()->firstOrCreate([
            'geographic_type' => 'region',
            'geographic_id' => $regionId,
        ]);
    }

    /**
     * Add a country tag
     */
    public function addCountry(int $countryId): void
    {
        $this->geographicTags()->firstOrCreate([
            'geographic_type' => 'country',
            'geographic_id' => $countryId,
        ]);
    }

    /**
     * Add a US state tag
     */
    public function addUsState(int $stateId): void
    {
        $this->geographicTags()->firstOrCreate([
            'geographic_type' => 'us_state',
            'geographic_id' => $stateId,
        ]);
    }

    /**
     * Remove a geographic tag
     */
    public function removeGeographicTag(string $type, int $id): void
    {
        $this->geographicTags()
            ->where('geographic_type', $type)
            ->where('geographic_id', $id)
            ->delete();
    }

    /**
     * Check if model has a specific geographic tag
     */
    public function hasGeographicTag(string $type, int $id): bool
    {
        return $this->geographicTags()
            ->where('geographic_type', $type)
            ->where('geographic_id', $id)
            ->exists();
    }

    /**
     * Scope: Filter by region
     */
    public function scopeInRegion($query, int $regionId)
    {
        return $query->whereHas('geographicTags', function ($q) use ($regionId) {
            $q->where('geographic_type', 'region')
                ->where('geographic_id', $regionId);
        });
    }

    /**
     * Scope: Filter by country
     */
    public function scopeInCountry($query, int $countryId)
    {
        return $query->whereHas('geographicTags', function ($q) use ($countryId) {
            $q->where('geographic_type', 'country')
                ->where('geographic_id', $countryId);
        });
    }

    /**
     * Scope: Filter by US state
     */
    public function scopeInUsState($query, int $stateId)
    {
        return $query->whereHas('geographicTags', function ($q) use ($stateId) {
            $q->where('geographic_type', 'us_state')
                ->where('geographic_id', $stateId);
        });
    }

    /**
     * Scope: Has any geographic tag
     */
    public function scopeHasGeographicTags($query)
    {
        return $query->whereHas('geographicTags');
    }
}

