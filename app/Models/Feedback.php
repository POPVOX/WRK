<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'page_url',
        'page_title',
        'page_route',
        'feedback_type',
        'category',
        'message',
        'screenshot_path',
        'user_agent',
        'browser',
        'browser_version',
        'os',
        'device_type',
        'screen_resolution',
        'viewport_size',
        'status',
        'priority',
        'admin_notes',
        'assigned_to',
        'ai_summary',
        'ai_recommendations',
        'ai_tags',
        'ai_analyzed_at',
        'github_issue_url',
        'github_issue_number',
    ];

    protected function casts(): array
    {
        return [
            'ai_tags' => 'array',
            'ai_analyzed_at' => 'datetime',
        ];
    }

    public const TYPES = [
        'bug' => 'Bug Report',
        'suggestion' => 'Suggestion',
        'compliment' => 'Compliment',
        'question' => 'Question',
        'general' => 'General Feedback',
    ];

    public const CATEGORIES = [
        'ui' => 'User Interface',
        'performance' => 'Performance',
        'feature' => 'Feature Request',
        'content' => 'Content',
        'navigation' => 'Navigation',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'new' => 'New',
        'reviewed' => 'Reviewed',
        'in_progress' => 'In Progress',
        'addressed' => 'Addressed',
        'dismissed' => 'Dismissed',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['new', 'reviewed', 'in_progress']);
    }

    public function scopeBugs($query)
    {
        return $query->where('feedback_type', 'bug');
    }

    public function scopeSuggestions($query)
    {
        return $query->where('feedback_type', 'suggestion');
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->feedback_type) {
            'bug' => 'red',
            'suggestion' => 'blue',
            'compliment' => 'green',
            'question' => 'purple',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'yellow',
            'reviewed' => 'blue',
            'in_progress' => 'indigo',
            'addressed' => 'green',
            'dismissed' => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'gray',
            default => 'gray',
        };
    }
}
