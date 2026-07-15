<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutreachEmailSuppression extends Model
{
    protected $fillable = [
        'email_normalized',
        'reason',
        'source_type',
        'gmail_message_id',
        'created_by',
        'notes',
        'metadata',
        'suppressed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'suppressed_at' => 'datetime',
        ];
    }

    public function gmailMessage(): BelongsTo
    {
        return $this->belongsTo(GmailMessage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
