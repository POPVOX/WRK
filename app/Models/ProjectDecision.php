<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDecision extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'rationale',
        'context',
        'meeting_id',
        'decision_date',
        'decided_by',
        'created_by',
    ];

    protected $casts = [
        'decision_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
