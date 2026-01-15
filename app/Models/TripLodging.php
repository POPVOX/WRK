<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripLodging extends Model
{
    use HasFactory;

    protected $table = 'trip_lodging';

    protected $fillable = [
        'trip_id',
        'trip_destination_id',
        'property_name',
        'chain',
        'address',
        'city',
        'country',
        'confirmation_number',
        'check_in_date',
        'check_in_time',
        'check_out_date',
        'check_out_time',
        'room_type',
        'nights',
        'nightly_rate',
        'total_cost',
        'currency',
        'phone',
        'email',
        'latitude',
        'longitude',
        'notes',
        'ai_extracted',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'nightly_rate' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'ai_extracted' => 'boolean',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(TripDestination::class, 'trip_destination_id');
    }

    public function getNightsCountAttribute(): int
    {
        if ($this->nights) {
            return $this->nights;
        }

        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    public function getCalculatedTotalAttribute(): ?float
    {
        if ($this->total_cost) {
            return $this->total_cost;
        }

        if ($this->nightly_rate) {
            return $this->nightly_rate * $this->nights_count;
        }

        return null;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->property_name,
            $this->address,
            $this->city,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
