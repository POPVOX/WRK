<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccomplishmentReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'accomplishment_id',
        'user_id',
        'reaction_type',
        'comment',
    ];

    /**
     * Reaction types with emojis
     */
    public const TYPES = [
        'celebrate' => ['label' => 'Celebrate', 'emoji' => '🎉'],
        'support' => ['label' => 'Support', 'emoji' => '💪'],
        'inspiring' => ['label' => 'Inspiring', 'emoji' => '✨'],
        'helpful' => ['label' => 'Helpful', 'emoji' => '🙏'],
    ];

    /**
     * The accomplishment this reaction belongs to
     */
    public function accomplishment(): BelongsTo
    {
        return $this->belongsTo(Accomplishment::class);
    }

    /**
     * The user who reacted
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get emoji for reaction type
     */
    public function getEmojiAttribute(): string
    {
        return self::TYPES[$this->reaction_type]['emoji'] ?? '👍';
    }

    /**
     * Get label for reaction type
     */
    public function getLabelAttribute(): string
    {
        return self::TYPES[$this->reaction_type]['label'] ?? 'React';
    }
}
