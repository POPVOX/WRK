<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxAccessGrant extends Model
{
    protected $fillable = [
        'policy_id',
        'subject_type',
        'subject_id',
        'wrk_permission',
        'box_role',
        'applies_to_subtree',
        'state',
        'box_collaboration_id',
        'last_synced_at',
        'last_error',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'applies_to_subtree' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(BoxAccessPolicy::class, 'policy_id');
    }

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function operationItems(): HasMany
    {
        return $this->hasMany(BoxAccessOperationItem::class, 'grant_id')->latest('created_at');
    }

    public function driftFindings(): HasMany
    {
        return $this->hasMany(BoxAccessDriftFinding::class, 'grant_id')->latest('detected_at');
    }
}

