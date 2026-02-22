<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPermission extends Model
{
    protected $fillable = [
        'user_id',
        'can_create_specialist',
        'can_create_project',
        'project_scope',
        'allowed_project_ids',
        'can_approve_medium_risk',
        'can_approve_high_risk',
    ];

    protected $casts = [
        'can_create_specialist' => 'boolean',
        'can_create_project' => 'boolean',
        'allowed_project_ids' => 'array',
        'can_approve_medium_risk' => 'boolean',
        'can_approve_high_risk' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allowsProjectId(int $projectId): bool
    {
        $allowed = collect($this->allowed_project_ids ?? [])->map(fn ($id) => (int) $id);

        return $allowed->contains($projectId);
    }
}
