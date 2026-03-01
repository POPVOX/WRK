<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPromptLayer extends Model
{
    public const LAYER_ORG = 'org';
    public const LAYER_ROLE = 'role';
    public const LAYER_PERSONAL = 'personal';

    protected $fillable = [
        'agent_id',
        'layer_type',
        'content',
        'version',
        'updated_by',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
