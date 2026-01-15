<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class TravelProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'birthday',
        'home_airport_code',
        'home_airport_name',
        'passport_number_encrypted',
        'passport_country',
        'passport_expiration',
        'tsa_precheck_number_encrypted',
        'global_entry_number_encrypted',
        'frequent_flyer_programs_encrypted',
        'hotel_programs_encrypted',
        'rental_car_programs_encrypted',
        'seat_preference',
        'dietary_restrictions',
        'travel_notes',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_email',
    ];

    protected $casts = [
        'birthday' => 'date',
        'passport_expiration' => 'date',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Encrypted Attribute Accessors
    protected function passportNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->passport_number_encrypted 
                ? Crypt::decryptString($this->passport_number_encrypted) 
                : null,
            set: fn ($value) => ['passport_number_encrypted' => $value ? Crypt::encryptString($value) : null],
        );
    }

    protected function tsaPrecheckNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tsa_precheck_number_encrypted 
                ? Crypt::decryptString($this->tsa_precheck_number_encrypted) 
                : null,
            set: fn ($value) => ['tsa_precheck_number_encrypted' => $value ? Crypt::encryptString($value) : null],
        );
    }

    protected function globalEntryNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->global_entry_number_encrypted 
                ? Crypt::decryptString($this->global_entry_number_encrypted) 
                : null,
            set: fn ($value) => ['global_entry_number_encrypted' => $value ? Crypt::encryptString($value) : null],
        );
    }

    protected function frequentFlyerPrograms(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->frequent_flyer_programs_encrypted 
                ? json_decode(Crypt::decryptString($this->frequent_flyer_programs_encrypted), true) 
                : [],
            set: fn ($value) => ['frequent_flyer_programs_encrypted' => $value ? Crypt::encryptString(json_encode($value)) : null],
        );
    }

    protected function hotelPrograms(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hotel_programs_encrypted 
                ? json_decode(Crypt::decryptString($this->hotel_programs_encrypted), true) 
                : [],
            set: fn ($value) => ['hotel_programs_encrypted' => $value ? Crypt::encryptString(json_encode($value)) : null],
        );
    }

    protected function rentalCarPrograms(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->rental_car_programs_encrypted 
                ? json_decode(Crypt::decryptString($this->rental_car_programs_encrypted), true) 
                : [],
            set: fn ($value) => ['rental_car_programs_encrypted' => $value ? Crypt::encryptString(json_encode($value)) : null],
        );
    }

    // Masked display for management view
    public function getMaskedPassportAttribute(): ?string
    {
        $passport = $this->passport_number;
        if (! $passport) {
            return null;
        }

        return '****'.substr($passport, -4);
    }

    public function getMaskedTsaPrecheckAttribute(): ?string
    {
        $number = $this->tsa_precheck_number;
        if (! $number) {
            return null;
        }

        return '****'.substr($number, -4);
    }

    public function getMaskedGlobalEntryAttribute(): ?string
    {
        $number = $this->global_entry_number;
        if (! $number) {
            return null;
        }

        return '****'.substr($number, -4);
    }

    // Helper methods
    public function isPassportExpiringSoon(int $months = 6): bool
    {
        if (! $this->passport_expiration) {
            return false;
        }

        return $this->passport_expiration->lt(now()->addMonths($months));
    }

    public function isPassportExpired(): bool
    {
        if (! $this->passport_expiration) {
            return false;
        }

        return $this->passport_expiration->lt(now());
    }

    public static function getSeatPreferenceOptions(): array
    {
        return [
            'window' => 'Window',
            'aisle' => 'Aisle',
            'middle' => 'Middle',
            'no_preference' => 'No Preference',
        ];
    }
}
