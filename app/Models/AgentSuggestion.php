<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentSuggestion extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_MODIFIED = 'modified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'agent_id',
        'run_id',
        'thread_id',
        'suggestion_type',
        'title',
        'reasoning',
        'payload',
        'risk_level',
        'approval_status',
        'reviewed_by',
        'reviewed_at',
        'executed_at',
        'review_notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'run_id');
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(AgentThread::class, 'thread_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AgentSuggestionSource::class, 'suggestion_id');
    }
}
