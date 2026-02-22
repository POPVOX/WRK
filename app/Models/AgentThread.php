<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentThread extends Model
{
    protected $fillable = [
        'agent_id',
        'user_id',
        'title',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentMessage::class, 'thread_id')->orderBy('created_at');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AgentRun::class, 'thread_id')->latest('created_at');
    }
}
