<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PressClip extends Model
{
    protected $fillable = [
        'title',
        'url',
        'outlet_name',
        'outlet_id',
        'journalist_id',
        'journalist_name',
        'published_at',
        'clip_type',
        'sentiment',
        'status',
        'reach',
        'summary',
        'quotes',
        'notes',
        'source',
        'created_by',
        'image_url',
    ];

    protected $casts = [
        'published_at' => 'date',
    ];

    // Relationships

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'outlet_id');
    }

    public function journalist(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'journalist_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'press_clip_project');
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'press_clip_issue');
    }

    public function staffMentioned(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'press_clip_person', 'press_clip_id', 'person_id')
            ->withPivot('mention_type');
    }

    // Scopes

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeThisMonth($query)
    {
        return $query->where('published_at', '>=', now()->startOfMonth());
    }

    public function scopeThisQuarter($query)
    {
        return $query->where('published_at', '>=', now()->startOfQuarter());
    }

    public function scopeThisYear($query)
    {
        return $query->where('published_at', '>=', now()->startOfYear());
    }

    public function scopeByOutlet($query, string $outlet)
    {
        return $query->where('outlet_name', $outlet);
    }

    public function scopeBySentiment($query, string $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }

    // Accessors

    public function getJournalistDisplayNameAttribute(): string
    {
        return $this->journalist?->name ?? $this->journalist_name ?? 'Unknown';
    }

    public function getOutletDisplayNameAttribute(): string
    {
        return $this->outlet?->name ?? $this->outlet_name;
    }

    public function getSentimentColorAttribute(): string
    {
        return match ($this->sentiment) {
            'positive' => 'green',
            'negative' => 'red',
            'mixed' => 'amber',
            default => 'gray',
        };
    }

    public function getClipTypeIconAttribute(): string
    {
        return match ($this->clip_type) {
            'article' => 'newspaper',
            'broadcast' => 'tv',
            'podcast' => 'microphone',
            'opinion' => 'chat-bubble-left-right',
            'interview' => 'user',
            default => 'document-text',
        };
    }

    public function getClipTypeLabelAttribute(): string
    {
        return match ($this->clip_type) {
            'article' => 'Article',
            'broadcast' => 'Broadcast',
            'podcast' => 'Podcast',
            'opinion' => 'Opinion/Editorial',
            'mention' => 'Mention',
            'interview' => 'Interview',
            default => 'Article',
        };
    }
}
