<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxAccessPolicy extends Model
{
    protected $fillable = [
        'policy_key',
        'tier',
        'box_folder_id',
        'entity_type',
        'entity_id',
        'default_access',
        'managed_by_wrk',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'managed_by_wrk' => 'boolean',
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function grants(): HasMany
    {
        return $this->hasMany(BoxAccessGrant::class, 'policy_id')->orderBy('id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BoxAccessOperation::class, 'target_policy_id')->latest('created_at');
    }

    public function driftFindings(): HasMany
    {
        return $this->hasMany(BoxAccessDriftFinding::class, 'policy_id')->latest('detected_at');
    }
}

