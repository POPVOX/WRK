<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryTravelAdvisory extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'country_name',
        'advisory_level',
        'advisory_title',
        'is_prohibited',
        'advisory_summary',
        'state_dept_url',
        'last_updated',
    ];

    protected $casts = [
        'is_prohibited' => 'boolean',
        'last_updated' => 'datetime',
    ];

    public function getAdvisoryColorAttribute(): string
    {
        return match ($this->advisory_level) {
            '1' => 'bg-green-100 text-green-800',
            '2' => 'bg-yellow-100 text-yellow-800',
            '3' => 'bg-orange-100 text-orange-800',
            '4' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getAdvisoryIconAttribute(): string
    {
        return match ($this->advisory_level) {
            '1' => '✅',
            '2' => '⚠️',
            '3' => '🔶',
            '4' => '⛔',
            default => 'ℹ️',
        };
    }

    public function requiresAdvanceNotice(): bool
    {
        return in_array($this->advisory_level, ['2', '3', '4']);
    }

    public function requiresStepRegistration(): bool
    {
        return in_array($this->advisory_level, ['2', '3', '4']);
    }

    public function requiresTravelInsurance(): bool
    {
        return in_array($this->advisory_level, ['3', '4']);
    }

    public function requiresApproval(): bool
    {
        return in_array($this->advisory_level, ['3', '4']) || $this->is_prohibited;
    }

    public static function getAdvisoryLevelOptions(): array
    {
        return [
            '1' => 'Level 1: Exercise Normal Precautions',
            '2' => 'Level 2: Exercise Increased Caution',
            '3' => 'Level 3: Reconsider Travel',
            '4' => 'Level 4: Do Not Travel',
        ];
    }

    public static function findByCountryCode(string $code): ?self
    {
        return static::where('country_code', strtoupper($code))->first();
    }
}
