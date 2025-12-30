<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workstream_id',
        'title',
        'description',
        'type',
        'status',
        'event_date',
        'location',
        'target_attendees',
        'actual_attendees',
        'deliverables',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'deliverables' => 'array',
    ];

    public const TYPES = [
        'staff_event' => 'Staff Event',
        'demo' => 'Demo',
        'launch' => 'Launch',
        'briefing' => 'Briefing',
        'workshop' => 'Workshop',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'planning' => 'Planning',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const STATUS_COLORS = [
        'planning' => 'bg-yellow-100 text-yellow-700',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workstream(): BelongsTo
    {
        return $this->belongsTo(ProjectWorkstream::class, 'workstream_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'event_id');
    }

    public function isUpcoming(): bool
    {
        return $this->event_date && $this->event_date->isFuture();
    }

    public function isPast(): bool
    {
        return $this->event_date && $this->event_date->isPast();
    }
}
