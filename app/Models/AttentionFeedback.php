<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttentionFeedback extends Model
{
    public const RESPONSE_USEFUL = 'useful';

    public const RESPONSE_NOT_RELEVANT = 'not_relevant';

    public const RESPONSE_MISSING = 'missing';

    protected $fillable = [
        'user_id',
        'item_key',
        'source_type',
        'source_id',
        'rule_key',
        'category',
        'response',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
