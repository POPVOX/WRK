<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoxWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'trigger',
        'source_type',
        'source_id',
        'headers',
        'payload',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
