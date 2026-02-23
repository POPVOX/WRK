<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxAccessDriftFinding extends Model
{
    protected $fillable = [
        'policy_id',
        'grant_id',
        'finding_type',
        'severity',
        'expected_state',
        'actual_state',
        'detected_at',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'expected_state' => 'array',
            'actual_state' => 'array',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(BoxAccessPolicy::class, 'policy_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(BoxAccessGrant::class, 'grant_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

