<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectWorkstream extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'color',
        'icon',
        'status',
        'sort_order',
    ];

    public const STATUSES = [
        'planning' => 'Planning',
        'active' => 'Active',
        'completed' => 'Completed',
        'paused' => 'Paused',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(ProjectPublication::class, 'workstream_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProjectEvent::class, 'workstream_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'workstream_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'workstream_id');
    }
}
