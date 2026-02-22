<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmailMessage extends Model
{
    protected $fillable = [
        'user_id',
        'person_id',
        'gmail_message_id',
        'gmail_thread_id',
        'history_id',
        'subject',
        'snippet',
        'from_email',
        'from_name',
        'to_emails',
        'cc_emails',
        'bcc_emails',
        'sent_at',
        'is_inbound',
        'labels',
    ];

    protected $casts = [
        'to_emails' => 'array',
        'cc_emails' => 'array',
        'bcc_emails' => 'array',
        'labels' => 'array',
        'sent_at' => 'datetime',
        'is_inbound' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function inboxActionLogs(): HasMany
    {
        return $this->hasMany(InboxActionLog::class);
    }
}
