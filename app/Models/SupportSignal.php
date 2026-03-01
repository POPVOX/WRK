<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportSignal extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_RESOLVED = 'resolved';

    public const ESCALATION_EXPLICIT = 'explicit_request';

    public const ESCALATION_REPEAT_THRESHOLD = 'repeat_threshold';

    protected $fillable = [
        'user_id',
        'manager_user_id',
        'followup_action_id',
        'source',
        'status',
        'summary',
        'raw_context',
        'share_raw_with_management',
        'escalation_reason',
        'window_signal_count',
        'escalated_at',
        'resolved_at',
        'digest_included_at',
        'metadata',
    ];

    protected $casts = [
        'share_raw_with_management' => 'boolean',
        'window_signal_count' => 'integer',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'digest_included_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function followupAction(): BelongsTo
    {
        return $this->belongsTo(Action::class, 'followup_action_id');
    }
}

