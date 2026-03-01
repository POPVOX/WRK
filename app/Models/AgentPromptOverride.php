<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPromptOverride extends Model
{
    protected $fillable = [
        'agent_id',
        'override_key',
        'override_value',
        'source_layer',
    ];

    protected $casts = [
        'override_value' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
