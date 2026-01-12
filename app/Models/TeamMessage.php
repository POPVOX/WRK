<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'content',
        'screenshot_path',
        'is_pinned',
        'is_announcement',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_announcement' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TeamMessage::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TeamMessage::class, 'parent_id')->orderBy('created_at');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(TeamMessageReaction::class);
    }

    // Get grouped reactions for display
    public function getGroupedReactionsAttribute(): array
    {
        return $this->reactions
            ->groupBy('emoji')
            ->map(fn ($group) => [
                'emoji' => $group->first()->emoji,
                'count' => $group->count(),
                'users' => $group->pluck('user.name')->toArray(),
                'user_ids' => $group->pluck('user_id')->toArray(),
            ])
            ->values()
            ->toArray();
    }
}
