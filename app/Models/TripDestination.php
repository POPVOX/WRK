<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripDestination extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'order',
        'city',
        'state_province',
        'country',
        'region',
        'arrival_date',
        'departure_date',
        'state_dept_level',
        'is_prohibited_destination',
        'travel_advisory_notes',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'departure_date' => 'date',
        'is_prohibited_destination' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function getFullLocationAttribute(): string
    {
        $parts = [$this->city];
        
        if ($this->state_province) {
            $parts[] = $this->state_province;
        }
        
        if ($this->country) {
            $parts[] = $this->country;
        }

        return implode(', ', $parts);
    }

    public function getDurationAttribute(): int
    {
        return $this->arrival_date->diffInDays($this->departure_date) + 1;
    }

    public function getCountryFlagAttribute(): string
    {
        $code = strtoupper($this->country);
        if (strlen($code) !== 2) {
            return '🌍';
        }

        $flag = '';
        foreach (str_split($code) as $char) {
            $flag .= mb_chr(ord($char) + 127397);
        }

        return $flag;
    }
}
