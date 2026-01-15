<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripGroundTransport extends Model
{
    use HasFactory;

    protected $table = 'trip_ground_transport';

    protected $fillable = [
        'trip_id',
        'user_id',
        'trip_destination_id',
        'type',
        'provider',
        'confirmation_number',
        'pickup_datetime',
        'pickup_location',
        'return_datetime',
        'return_location',
        'vehicle_type',
        'license_plate',
        'cost',
        'currency',
        'notes',
        'ai_extracted',
    ];

    protected $casts = [
        'pickup_datetime' => 'datetime',
        'return_datetime' => 'datetime',
        'cost' => 'decimal:2',
        'ai_extracted' => 'boolean',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function traveler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(TripDestination::class, 'trip_destination_id');
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'rental_car' => '🚗',
            'taxi' => '🚕',
            'rideshare' => '🚙',
            'public_transit' => '🚇',
            'shuttle' => '🚐',
            'parking' => '🅿️',
            default => '🚐',
        };
    }

    public static function getTypeOptions(): array
    {
        return [
            'rental_car' => 'Rental Car',
            'taxi' => 'Taxi',
            'rideshare' => 'Rideshare',
            'public_transit' => 'Public Transit',
            'shuttle' => 'Shuttle',
            'parking' => 'Parking',
            'other' => 'Other',
        ];
    }
}
