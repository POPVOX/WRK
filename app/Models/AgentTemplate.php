<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentTemplate extends Model
{
    protected $fillable = [
        'name',
        'agent_type',
        'specialty',
        'description',
        'system_prompt',
        'default_config',
        'is_active',
        'is_global',
        'times_used',
        'created_by',
    ];

    protected $casts = [
        'default_config' => 'array',
        'is_active' => 'boolean',
        'is_global' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'template_id');
    }
}
