<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Action extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'meeting_id',
        'project_id',
        'description',
        'notes',
        'due_date',
        'priority',
        'status',
        'source',
        'assigned_to',
        'completed_at',
    ];

    /**
     * Source constants.
     */
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_MEETING = 'meeting';

    public const SOURCE_AI_SUGGESTED = 'ai_suggested';

    public const SOURCE_CALENDAR = 'calendar';

    public const SOURCES = [
        self::SOURCE_MANUAL,
        self::SOURCE_MEETING,
        self::SOURCE_AI_SUGGESTED,
        self::SOURCE_CALENDAR,
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * Priority constants.
     */
    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_LOW = 'low';

    public const PRIORITIES = [
        self::PRIORITY_HIGH,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_LOW,
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETE = 'complete';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETE,
    ];

    /**
     * Get the meeting this action belongs to.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the project this action belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user this action is assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if this is a standalone task (not from a meeting).
     */
    public function isStandalone(): bool
    {
        return is_null($this->meeting_id);
    }

    /**
     * Get display title (title or description).
     */
    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: $this->description;
    }

    /**
     * Scope for standalone tasks.
     */
    public function scopeStandalone($query)
    {
        return $query->whereNull('meeting_id');
    }

    /**
     * Scope for AI suggested tasks.
     */
    public function scopeAiSuggested($query)
    {
        return $query->where('source', self::SOURCE_AI_SUGGESTED);
    }

    /**
     * Mark the action as complete.
     */
    public function markComplete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETE,
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope to get pending actions.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get overdue actions.
     */
    public function scopeOverdue($query)
    {
        return $query->pending()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay());
    }

    /**
     * Check if action is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->due_date
            && $this->due_date->isPast();
    }
}
