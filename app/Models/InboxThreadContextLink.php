<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxThreadContextLink extends Model
{
    protected $fillable = [
        'user_id',
        'thread_key',
        'gmail_thread_id',
        'link_type',
        'link_id',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public const TYPE_PERSON = 'person';

    public const TYPE_ORGANIZATION = 'organization';

    public const TYPE_PROJECT = 'project';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_GRANT = 'grant';

    public const TYPE_MEDIA_CONTACT = 'media_contact';

    public const TYPE_MEDIA_OUTLET = 'media_outlet';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
