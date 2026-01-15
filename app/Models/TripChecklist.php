<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'item',
        'category',
        'is_completed',
        'ai_suggested',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'ai_suggested' => 'boolean',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'documents' => '📄',
            'electronics' => '💻',
            'clothing' => '👔',
            'presentation_materials' => '📊',
            'gifts_swag' => '🎁',
            'health_safety' => '🏥',
            default => '📦',
        };
    }

    public static function getCategoryOptions(): array
    {
        return [
            'documents' => 'Documents',
            'electronics' => 'Electronics',
            'clothing' => 'Clothing',
            'presentation_materials' => 'Presentation Materials',
            'gifts_swag' => 'Gifts/Swag',
            'health_safety' => 'Health & Safety',
            'other' => 'Other',
        ];
    }
}
