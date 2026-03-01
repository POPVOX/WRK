<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentGoal extends Model
{
    public const TYPE_MONITOR = 'monitor';
    public const TYPE_PREPARE = 'prepare';
    public const TYPE_COORDINATE = 'coordinate';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    public const TRIGGER_CRON = 'cron';
    public const TRIGGER_DEADLINE = 'deadline';
    public const TRIGGER_EVENT = 'event';
    public const TRIGGER_MANUAL = 'manual';

    protected $fillable = [
        'agent_id',
        'title',
        'description',
        'goal_type',
        'status',
        'trigger_type',
        'trigger_config',
        'output_config',
        'priority',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'output_config' => 'array',
        'priority' => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function goalRuns(): HasMany
    {
        return $this->hasMany(AgentGoalRun::class, 'goal_id')->latest('triggered_at');
    }

    public function contextEntries(): HasMany
    {
        return $this->hasMany(AgentGoalContext::class, 'goal_id')->orderBy('context_key');
    }
}
