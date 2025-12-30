<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
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
}
