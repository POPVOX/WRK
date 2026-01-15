<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'name',
        'email',
        'phone',
        'organization',
        'role',
        'notes',
        'home_airport_code',
        'dietary_restrictions',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(TripSegment::class)->orderBy('departure_datetime');
    }

    /**
     * Get role options for guests
     */
    public static function getRoleOptions(): array
    {
        return [
            'speaker' => 'Speaker',
            'partner' => 'Partner',
            'delegate' => 'Delegate',
            'client' => 'Client',
            'family' => 'Family',
            'guest' => 'Guest',
            'other' => 'Other',
        ];
    }
}
