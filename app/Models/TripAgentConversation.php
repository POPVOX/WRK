<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripAgentConversation extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'title',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TripAgentMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(TripAgentAction::class, 'conversation_id')->latest('created_at');
    }
}
