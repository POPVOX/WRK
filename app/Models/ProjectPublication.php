<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPublication extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'workstream_id',
        'title',
        'description',
        'type',
        'status',
        'target_date',
        'published_date',
        'sort_order',
        'metadata',
        'content_path',
    ];

    protected $casts = [
        'target_date' => 'date',
        'published_date' => 'date',
        'metadata' => 'array',
    ];

    public const TYPES = [
        'chapter' => 'Chapter',
        'report' => 'Report',
        'brief' => 'Brief',
        'appendix' => 'Appendix',
        'case_study' => 'Case Study',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'idea' => 'Idea',
        'outlined' => 'Outlined',
        'drafting' => 'Drafting',
        'editing' => 'Editing',
        'review' => 'Review',
        'ready' => 'Ready',
        'published' => 'Published',
    ];

    public const STATUS_COLORS = [
        'idea' => 'bg-gray-100 text-gray-700',
        'outlined' => 'bg-purple-100 text-purple-700',
        'drafting' => 'bg-blue-100 text-blue-700',
        'editing' => 'bg-yellow-100 text-yellow-700',
        'review' => 'bg-orange-100 text-orange-700',
        'ready' => 'bg-emerald-100 text-emerald-700',
        'published' => 'bg-green-100 text-green-700',
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
        return $this->hasMany(ProjectMilestone::class, 'publication_id');
    }

    public function isOverdue(): bool
    {
        return $this->target_date &&
            $this->target_date->isPast() &&
            ! in_array($this->status, ['ready', 'published']);
    }
}
