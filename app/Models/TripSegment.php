<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'trip_destination_id',
        'type',
        'carrier',
        'carrier_code',
        'segment_number',
        'confirmation_number',
        'departure_location',
        'departure_city',
        'departure_datetime',
        'departure_terminal',
        'departure_gate',
        'arrival_location',
        'arrival_city',
        'arrival_datetime',
        'arrival_terminal',
        'aircraft_type',
        'seat_assignment',
        'cabin_class',
        'distance_miles',
        'cost',
        'currency',
        'status',
        'booking_reference',
        'ticket_number',
        'notes',
        'ai_extracted',
        'ai_confidence',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'arrival_datetime' => 'datetime',
        'cost' => 'decimal:2',
        'ai_confidence' => 'decimal:2',
        'ai_extracted' => 'boolean',
        'distance_miles' => 'integer',
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

    // Helpers
    public function getFlightNumberAttribute(): ?string
    {
        if ($this->type !== 'flight') {
            return null;
        }

        return trim(($this->carrier_code ?? '').' '.($this->segment_number ?? ''));
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->departure_datetime || ! $this->arrival_datetime) {
            return null;
        }

        $diff = $this->departure_datetime->diff($this->arrival_datetime);

        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d.'d';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h.'h';
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i.'m';
        }

        return implode(' ', $parts);
    }

    public function getRouteAttribute(): string
    {
        return $this->departure_location.' → '.$this->arrival_location;
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'flight' => '✈️',
            'train' => '🚆',
            'bus' => '🚌',
            'rental_car' => '🚗',
            'rideshare' => '🚕',
            'ferry' => '⛴️',
            default => '🚐',
        };
    }

    public static function getTypeOptions(): array
    {
        return [
            'flight' => 'Flight',
            'train' => 'Train',
            'bus' => 'Bus',
            'rental_car' => 'Rental Car',
            'rideshare' => 'Rideshare',
            'ferry' => 'Ferry',
            'other_transport' => 'Other',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'checked_in' => 'Checked In',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'delayed' => 'Delayed',
        ];
    }

    public static function getCabinClassOptions(): array
    {
        return [
            'economy' => 'Economy',
            'premium_economy' => 'Premium Economy',
            'business' => 'Business',
            'first' => 'First Class',
        ];
    }
}
