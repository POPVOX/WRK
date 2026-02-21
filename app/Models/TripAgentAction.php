<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAgentAction extends Model
{
    protected $fillable = [
        'conversation_id',
        'proposed_by_message_id',
        'requested_by',
        'status',
        'summary',
        'payload',
        'execution_log',
        'error_message',
        'approved_by',
        'approved_at',
        'executed_by',
        'executed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'execution_log' => 'array',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TripAgentConversation::class, 'conversation_id');
    }

    public function proposedByMessage(): BelongsTo
    {
        return $this->belongsTo(TripAgentMessage::class, 'proposed_by_message_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
