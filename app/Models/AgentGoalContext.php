<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentGoalContext extends Model
{
    protected $table = 'agent_goal_context';

    public $timestamps = false;

    protected $fillable = [
        'goal_id',
        'context_key',
        'context_value',
        'updated_at',
    ];

    protected $casts = [
        'context_value' => 'array',
        'updated_at' => 'datetime',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(AgentGoal::class, 'goal_id');
    }
}
