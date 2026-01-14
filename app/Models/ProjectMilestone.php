<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workstream_id',
        'publication_id',
        'event_id',
        'title',
        'description',
        'status',
        'due_date',
        'completed_date',
        'completed_by',
        'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'blocked' => 'Blocked',
        'deferred' => 'Deferred',
    ];

    public const STATUS_COLORS = [
        'pending' => 'bg-gray-100 text-gray-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-green-100 text-green-700',
        'blocked' => 'bg-red-100 text-red-700',
        'deferred' => 'bg-yellow-100 text-yellow-700',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workstream(): BelongsTo
    {
        return $this->belongsTo(ProjectWorkstream::class, 'workstream_id');
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(ProjectPublication::class, 'publication_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ProjectEvent::class, 'event_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date &&
            $this->due_date->isPast() &&
            ! in_array($this->status, ['completed', 'deferred']);
    }

    // Keep backwards compatibility with existing code
    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function markComplete(?int $userId = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completed_by' => $userId ?? auth()->id(),
        ]);
    }

    public function markIncomplete(): void
    {
        $this->update([
            'status' => 'pending',
            'completed_date' => null,
            'completed_by' => null,
        ]);
    }
}
