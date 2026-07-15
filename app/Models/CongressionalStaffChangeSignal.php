<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongressionalStaffChangeSignal extends Model
{
    protected $fillable = [
        'gmail_message_id',
        'user_id',
        'profile_id',
        'signal_key',
        'signal_type',
        'status',
        'source_email',
        'target_emails',
        'replacement_contacts',
        'summary',
        'evidence_excerpt',
        'detected_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_emails' => 'array',
            'replacement_contacts' => 'array',
            'detected_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function gmailMessage(): BelongsTo
    {
        return $this->belongsTo(GmailMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CongressionalStaffProfile::class, 'profile_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
