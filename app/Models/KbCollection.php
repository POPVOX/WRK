<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbCollection extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'query',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];
}
