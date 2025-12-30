<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'category',
        'audience',
        'url',
        'file_path',
        'icon',
        'sort_order',
        'is_featured',
        'created_by',
        'last_reviewed',
        'review_frequency_days',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'last_reviewed' => 'date',
    ];

    public const CATEGORIES = [
        'onboarding' => 'Onboarding',
        'hr' => 'HR',
        'operations' => 'Operations',
        'style' => 'Style Guide',
        'tools' => 'Tools',
        'policy' => 'Policy',
        'resource' => 'Resource',
        'howto' => 'How-To Guide',
        'template' => 'Template',
        'general' => 'General',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForAudience($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->isManagement()) {
            return $query->whereIn('audience', ['all', 'staff', 'management']);
        }
        return $query->whereIn('audience', ['all', 'staff']);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function needsReview(): bool
    {
        if (!$this->review_frequency_days || !$this->last_reviewed) {
            return false;
        }
        return $this->last_reviewed->addDays($this->review_frequency_days)->isPast();
    }
}
