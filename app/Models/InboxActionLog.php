<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxActionLog extends Model
{
    protected $fillable = [
        'user_id',
        'gmail_message_id',
        'project_id',
        'thread_key',
        'suggestion_key',
        'action_label',
        'action_status',
        'subject',
        'counterpart_name',
        'counterpart_email',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gmailMessage(): BelongsTo
    {
        return $this->belongsTo(GmailMessage::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
