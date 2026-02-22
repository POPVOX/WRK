<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachSubstackConnection extends Model
{
    protected $fillable = [
        'user_id',
        'publication_name',
        'publication_url',
        'rss_feed_url',
        'api_key',
        'email_from',
        'status',
        'last_synced_at',
        'last_error',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

