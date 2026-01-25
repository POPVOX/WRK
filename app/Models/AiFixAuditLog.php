<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFixAuditLog extends Model
{
    protected $fillable = [
        'proposal_id',
        'user_id',
        'action',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public const ACTIONS = [
        'requested' => 'Fix Requested',
        'generating' => 'Generation Started',
        'generated' => 'Generation Complete',
        'viewed' => 'Proposal Viewed',
        'approved' => 'Fix Approved',
        'deployed' => 'Fix Deployed',
        'rejected' => 'Fix Rejected',
        'failed' => 'Generation Failed',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(AiFixProposal::class, 'proposal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an action for a proposal.
     */
    public static function logAction(
        AiFixProposal $proposal,
        string $action,
        ?array $details = null
    ): self {
        return self::create([
            'proposal_id' => $proposal->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? ucfirst($this->action);
    }
}
