<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentRun extends Model
{
    protected $fillable = [
        'agent_id',
        'thread_id',
        'requested_by',
        'status',
        'directive',
        'result_summary',
        'reasoning_chain',
        'alternatives_considered',
        'model',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'reasoning_chain' => 'array',
        'alternatives_considered' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(AgentThread::class, 'thread_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(AgentSuggestion::class, 'run_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AgentSuggestionSource::class, 'run_id');
    }
}
