<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lead_contact_id',
        'title',
        'meeting_date',
        'location',
        'meeting_link',
        'meeting_link_type',
        'prep_notes',
        'prep_analysis',
        'audio_path',
        'transcript',
        'raw_notes',
        'ai_summary',
        'notes_summary',
        'key_ask',
        'commitments_made',
        'status',
        'google_event_id',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'prep_analysis' => 'array',
    ];

    /**
     * Meeting status constants.
     */
    public const STATUS_NEW = 'new';

    public const STATUS_ACTION_NEEDED = 'action_needed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETE = 'complete';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_ACTION_NEEDED,
        self::STATUS_PENDING,
        self::STATUS_COMPLETE,
    ];

    /**
     * Get the user who logged this meeting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lead POPVOX Fdn contact for this meeting.
     */
    public function leadContact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_contact_id');
    }

    /**
     * Get the organizations involved in this meeting.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'meeting_organization')
            ->withTimestamps();
    }

    /**
     * Get the people who attended this meeting.
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'meeting_person')
            ->withTimestamps();
    }

    /**
     * Get the issues discussed in this meeting.
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'meeting_issue')
            ->withTimestamps();
    }

    /**
     * Get the attachments for this meeting.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class);
    }

    /**
     * Get the actions/follow-ups from this meeting.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    /**
     * Get the POPVOX team members who attended this meeting.
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_user')
            ->withTimestamps();
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if meeting needs action.
     */
    public function needsAction(): bool
    {
        return $this->status === self::STATUS_ACTION_NEEDED;
    }

    /**
     * Get the projects this meeting is linked to.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('relevance_note')
            ->withTimestamps();
    }

    // === Scopes for Meetings Redesign ===

    /**
     * Scope for upcoming meetings (today and future).
     */
    public function scopeUpcoming($query)
    {
        return $query->where('meeting_date', '>=', now()->startOfDay())
            ->orderBy('meeting_date', 'asc');
    }

    /**
     * Scope for past meetings (today or earlier).
     */
    public function scopePast($query)
    {
        return $query->where('meeting_date', '<=', now()->startOfDay())
            ->orderBy('meeting_date', 'desc');
    }

    /**
     * Scope for past meetings that need notes.
     */
    public function scopeNeedsNotes($query)
    {
        return $query->past()
            ->where(function ($q) {
                $q->whereNull('raw_notes')
                    ->orWhere('raw_notes', '');
            });
    }

    /**
     * Scope for past meetings that have notes.
     */
    public function scopeWithNotes($query)
    {
        return $query->past()
            ->whereNotNull('raw_notes')
            ->where('raw_notes', '!=', '');
    }

    // === Helper Methods ===

    /**
     * Check if meeting has notes.
     */
    public function hasNotes(): bool
    {
        return ! empty($this->raw_notes);
    }

    /**
     * Check if meeting is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->meeting_date > now()->startOfDay();
    }

    /**
     * Check if meeting is past.
     */
    public function isPast(): bool
    {
        return $this->meeting_date < now()->startOfDay();
    }

    /**
     * Get a preview of the notes for display.
     */
    public function getNotesPreviewAttribute(): ?string
    {
        if (! $this->raw_notes) {
            return null;
        }

        return \Illuminate\Support\Str::limit(strip_tags($this->raw_notes), 120);
    }
}
