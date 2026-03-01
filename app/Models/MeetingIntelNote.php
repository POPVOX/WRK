<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingIntelNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'source_ref',
        'slack_channel_id',
        'slack_message_ts',
        'slack_thread_ts',
        'author_user_id',
        'author_label',
        'content',
        'meeting_id',
        'project_id',
        'grant_id',
        'person_ids',
        'organization_ids',
        'funder_organization_ids',
        'project_ids',
        'grant_ids',
        'metadata',
        'captured_at',
    ];

    protected $casts = [
        'person_ids' => 'array',
        'organization_ids' => 'array',
        'funder_organization_ids' => 'array',
        'project_ids' => 'array',
        'grant_ids' => 'array',
        'metadata' => 'array',
        'captured_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }
}

