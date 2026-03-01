<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCredential extends Model
{
    public const SERVICE_GMAIL = 'gmail';
    public const SERVICE_BOX = 'box';
    public const SERVICE_GCAL = 'gcal';
    public const SERVICE_SLACK = 'slack';

    protected $fillable = [
        'agent_id',
        'service',
        'token_data',
        'scopes',
        'expires_at',
        'refreshed_at',
    ];

    protected $casts = [
        'token_data' => 'encrypted:array',
        'scopes' => 'array',
        'expires_at' => 'datetime',
        'refreshed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
