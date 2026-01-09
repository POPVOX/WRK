<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'scope',
        'lead',
        'description',
        'status',
        'is_initiative',
        'project_path',
        'success_metrics',
        'goals',
        'url',
        'tags',
        'start_date',
        'target_end_date',
        'actual_end_date',
        'created_by',
        'ai_status_summary',
        'ai_status_generated_at',
        'parent_project_id',
        'project_type',
        'sort_order',
        'grant_associations',
        'metric_tags',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_end_date' => 'date',
        'actual_end_date' => 'date',
        'ai_status_generated_at' => 'datetime',
        'tags' => 'array',
        'is_initiative' => 'boolean',
        'success_metrics' => 'array',
        'grant_associations' => 'array',
        'metric_tags' => 'array',
    ];

    public const STATUSES = [
        'planning' => 'Planning',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ];

    // Relationships
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class)
            ->withPivot('relevance_note')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'project_organization')
            ->withPivot(['role', 'notes'])
            ->withTimestamps();
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'project_person')
            ->withPivot(['role', 'notes'])
            ->withTimestamps();
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'project_issue');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ProjectDecision::class)->orderBy('decision_date', 'desc');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProjectQuestion::class);
    }

    public function openQuestions(): HasMany
    {
        return $this->hasMany(ProjectQuestion::class)->where('status', 'open');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Staff (POPVOX Fdn team members)
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_staff')
            ->withPivot('role', 'added_at')
            ->orderByPivot('added_at', 'desc');
    }

    // Documents (files and links)
    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->orderBy('created_at', 'desc');
    }

    // Notes (activity log)
    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class)->orderBy('created_at', 'desc');
    }

    // Pinned notes
    public function pinnedNotes(): HasMany
    {
        return $this->hasMany(ProjectNote::class)->where('is_pinned', true)->orderBy('created_at', 'desc');
    }

    // Workspace relationships (for initiatives)
    public function workstreams(): HasMany
    {
        return $this->hasMany(ProjectWorkstream::class)->orderBy('sort_order');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(ProjectPublication::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProjectEvent::class)->orderBy('event_date');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ProjectChatMessage::class)->orderBy('created_at');
    }

    // Parent/Child relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'parent_project_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Project::class, 'parent_project_id')->orderBy('sort_order');
    }

    // Recursive children for tree building
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function grants(): BelongsToMany
    {
        return $this->belongsToMany(Grant::class)
            ->withPivot(['allocated_amount', 'notes'])
            ->withTimestamps();
    }

    public function ancestors(): array
    {
        $ancestors = [];
        $project = $this->parent;
        while ($project) {
            array_unshift($ancestors, $project);
            $project = $project->parent;
        }

        return $ancestors;
    }

    public function rootAncestor(): ?Project
    {
        $ancestors = $this->ancestors();

        return $ancestors[0] ?? null;
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_project_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    // Get depth in hierarchy
    public function getDepthAttribute(): int
    {
        return count($this->ancestors());
    }

    // Get breadcrumb path
    public function getBreadcrumbAttribute(): array
    {
        $path = $this->ancestors();
        $path[] = $this;

        return $path;
    }

    // Scope: only root projects (no parent)
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_project_id');
    }

    // Get icon based on project type
    public function getTypeIconAttribute(): string
    {
        return match ($this->project_type) {
            'publication' => 'document-text',
            'event' => 'calendar',
            'chapter' => 'bookmark',
            'newsletter' => 'newspaper',
            'tool' => 'wrench-screwdriver',
            'research' => 'academic-cap',
            'outreach' => 'megaphone',
            'component' => 'puzzle-piece',
            default => 'folder',
        };
    }

    // Get color based on project type
    public function getTypeColorAttribute(): string
    {
        return match ($this->project_type) {
            'publication' => 'text-blue-500',
            'event' => 'text-purple-500',
            'chapter' => 'text-indigo-500',
            'newsletter' => 'text-green-500',
            'tool' => 'text-orange-500',
            'research' => 'text-cyan-500',
            'outreach' => 'text-pink-500',
            'component' => 'text-gray-500',
            default => 'text-gray-400',
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // AI Status Methods
    public function needsStatusRefresh(): bool
    {
        // Refresh if: never generated
        if (! $this->ai_status_generated_at) {
            return true;
        }

        // Older than 24 hours
        if ($this->ai_status_generated_at->diffInHours(now()) > 24) {
            return true;
        }

        // Project updated since last generation
        if ($this->updated_at > $this->ai_status_generated_at) {
            return true;
        }

        // Check if any related items updated since last generation
        $latestMeeting = $this->meetings()->max('updated_at');
        $latestDecision = $this->decisions()->max('updated_at');
        $latestMilestone = $this->milestones()->max('updated_at');
        $latestQuestion = $this->questions()->max('updated_at');

        $latestActivity = collect([$latestMeeting, $latestDecision, $latestMilestone, $latestQuestion])
            ->filter()
            ->max();

        return $latestActivity && $latestActivity > $this->ai_status_generated_at;
    }

    // Accessors
    public function getOpenQuestionsCountAttribute(): int
    {
        return $this->questions()->where('status', 'open')->count();
    }

    public function getPendingMilestonesCountAttribute(): int
    {
        return $this->milestones()->whereIn('status', ['pending', 'in_progress'])->count();
    }

    public function getCompletedMilestonesCountAttribute(): int
    {
        return $this->milestones()->where('status', 'completed')->count();
    }
}
