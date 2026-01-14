<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAgendaItem extends Model
{
    protected $fillable = [
        'meeting_id',
        'title',
        'description',
        'order',
        'duration_minutes',
        'presenter_id',
        'status',
        'notes',
        'decisions',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration_minutes' => 'integer',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'skipped' => 'Skipped',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'in_progress' => 'yellow',
            'completed' => 'green',
            'skipped' => 'red',
            default => 'gray',
        };
    }

    public function getDurationDisplayAttribute(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        if ($this->duration_minutes >= 60) {
            $hours = floor($this->duration_minutes / 60);
            $mins = $this->duration_minutes % 60;
            return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
        }

        return $this->duration_minutes . ' min';
    }
}

