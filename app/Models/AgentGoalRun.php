<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentGoalRun extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'goal_id',
        'agent_run_id',
        'triggered_at',
        'trigger_reason',
        'status',
        'output_summary',
        'completed_at',
        'idempotency_key',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(AgentGoal::class, 'goal_id');
    }

    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'agent_run_id');
    }
}
