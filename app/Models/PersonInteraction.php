<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonInteraction extends Model
{
    protected $table = 'people_interactions';

    protected $fillable = [
        'person_id',
        'user_id',
        'type',
        'occurred_at',
        'summary',
        'next_action_at',
        'next_action_note',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'next_action_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
