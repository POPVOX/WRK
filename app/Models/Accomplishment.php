<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accomplishment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'visibility',
        'date',
        'source',
        'attachment_path',
        'added_by',
        'is_recognition',
        'contributors',
        'project_id',
        'grant_id',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recognition' => 'boolean',
        'contributors' => 'array',
    ];

    /**
     * Accomplishment types
     */
    public const TYPES = [
        'recognition' => 'Recognition',
        'award' => 'Award',
        'feedback' => 'Positive Feedback',
        'milestone' => 'Milestone Achieved',
        'speaking' => 'Speaking Engagement',
        'media' => 'Media Mention',
        'learning' => 'Learning/Development',
        'other' => 'Other',
    ];

    /**
     * Visibility options
     */
    public const VISIBILITY = [
        'personal' => 'Personal (only me)',
        'team' => 'Team (all team members)',
        'organizational' => 'Organizational (public)',
    ];

    /**
     * The user this accomplishment belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user who added this accomplishment (for recognition)
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Associated project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Associated grant
     */
    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }

    /**
     * Reactions to this accomplishment
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(AccomplishmentReaction::class);
    }

    /**
     * Get contributor users
     */
    public function getContributorUsersAttribute()
    {
        if (empty($this->contributors)) {
            return collect();
        }

        $userIds = collect($this->contributors)->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Check if user has reacted
     */
    public function hasUserReacted(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->reactions()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's reaction
     */
    public function getUserReaction(?int $userId): ?AccomplishmentReaction
    {
        if (! $userId) {
            return null;
        }

        return $this->reactions()->where('user_id', $userId)->first();
    }

    /**
     * Get reaction count by type
     */
    public function getReactionCountByType(): array
    {
        return $this->reactions()
            ->selectRaw('reaction_type, COUNT(*) as count')
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();
    }

    /**
     * Scope: Personal visibility
     */
    public function scopePersonal($query)
    {
        return $query->where('visibility', 'personal');
    }

    /**
     * Scope: Team visibility
     */
    public function scopeTeam($query)
    {
        return $query->whereIn('visibility', ['team', 'organizational']);
    }

    /**
     * Scope: Organizational visibility
     */
    public function scopeOrganizational($query)
    {
        return $query->where('visibility', 'organizational');
    }

    /**
     * Scope: Recognition (added by others)
     */
    public function scopeRecognition($query)
    {
        return $query->where('is_recognition', true);
    }

    /**
     * Scope: Self-added accomplishments
     */
    public function scopeSelfAdded($query)
    {
        return $query->where('is_recognition', false);
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: Visible to user
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // Own accomplishments (any visibility)
            $q->where('user_id', $user->id)
                // Or team/organizational visibility
                ->orWhereIn('visibility', ['team', 'organizational'])
                // Or user is tagged as contributor
                ->orWhereJsonContains('contributors', ['user_id' => $user->id]);
        });
    }

    /**
     * Get type emoji
     */
    public function getTypeEmojiAttribute(): string
    {
        return match ($this->type) {
            'recognition' => '🌟',
            'award' => '🏆',
            'feedback' => '💬',
            'milestone' => '🎯',
            'speaking' => '🎤',
            'media' => '📰',
            'learning' => '📚',
            default => '✨',
        };
    }

    /**
     * Get type color for badges
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'recognition' => 'yellow',
            'award' => 'amber',
            'feedback' => 'blue',
            'milestone' => 'green',
            'speaking' => 'purple',
            'media' => 'pink',
            'learning' => 'cyan',
            default => 'gray',
        };
    }
}
