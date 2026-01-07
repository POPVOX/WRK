<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grant extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'status',
        'amount',
        'start_date',
        'end_date',
        'description',
        'deliverables',
        'visibility',
        'notes',
        'scope',
        'primary_project_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function funder(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot(['allocated_amount', 'notes'])
            ->withTimestamps();
    }

    public function reportingRequirements(): HasMany
    {
        return $this->hasMany(ReportingRequirement::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GrantDocument::class);
    }

    public const STATUSES = [
        'prospective' => 'Prospective',
        'pending' => 'Pending',
        'active' => 'Active',
        'completed' => 'Completed',
        'declined' => 'Declined',
    ];

    public const SCOPES = [
        'all' => 'All Programs',
        'us' => 'U.S. Only',
        'global' => 'Global',
        'project' => 'Specific Project',
    ];

    public function primaryProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'primary_project_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->access_level === 'admin') {
            return $query;
        }
        if ($user->access_level === 'management') {
            return $query->whereIn('visibility', ['all', 'management']);
        }

        return $query->where('visibility', 'all');
    }
}
