<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxAccessOperationItem extends Model
{
    protected $fillable = [
        'operation_id',
        'grant_id',
        'box_item_type',
        'box_item_id',
        'action',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(BoxAccessOperation::class, 'operation_id');
    }

    public function grant(): BelongsTo
    {
        return $this->belongsTo(BoxAccessGrant::class, 'grant_id');
    }
}

