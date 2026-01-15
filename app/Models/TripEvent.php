<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'meeting_id',
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'location',
        'address',
        'type',
        'notes',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'conference_session' => '🎪',
            'meeting' => '🤝',
            'presentation' => '📊',
            'workshop' => '🛠️',
            'reception' => '🥂',
            'site_visit' => '🏛️',
            default => '📅',
        };
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->start_datetime || ! $this->end_datetime) {
            return null;
        }

        $diff = $this->start_datetime->diff($this->end_datetime);

        if ($diff->h > 0 && $diff->i > 0) {
            return $diff->h.'h '.$diff->i.'m';
        }
        if ($diff->h > 0) {
            return $diff->h.'h';
        }

        return $diff->i.'m';
    }

    public static function getTypeOptions(): array
    {
        return [
            'conference_session' => 'Conference Session',
            'meeting' => 'Meeting',
            'presentation' => 'Presentation',
            'workshop' => 'Workshop',
            'reception' => 'Reception',
            'site_visit' => 'Site Visit',
            'other' => 'Other',
        ];
    }
}
