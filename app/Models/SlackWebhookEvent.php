<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlackWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'event_type',
        'slack_user_id',
        'slack_channel_id',
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
