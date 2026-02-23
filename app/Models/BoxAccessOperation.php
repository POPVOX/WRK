<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxAccessOperation extends Model
{
    protected $fillable = [
        'operation_uuid',
        'actor_user_id',
        'operation_type',
        'status',
        'target_policy_id',
        'payload',
        'started_at',
        'completed_at',
        'error_summary',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(BoxAccessPolicy::class, 'target_policy_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BoxAccessOperationItem::class, 'operation_id')->orderBy('id');
    }
}

