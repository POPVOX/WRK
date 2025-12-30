<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactView extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];
}
