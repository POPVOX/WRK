<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMemory extends Model
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    protected $table = 'agent_memory';

    protected $fillable = [
        'agent_id',
        'memory_type',
        'content',
        'source_message_id',
        'visibility',
        'confidence',
    ];

    protected $casts = [
        'content' => 'array',
        'confidence' => 'decimal:2',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(AgentMessage::class, 'source_message_id');
    }
}
