<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaSearchTerm extends Model
{
    protected $fillable = [
        'term',
        'is_active',
        'last_searched_at',
        'clips_found',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_searched_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function recordSearch(int $clipsFound = 0): void
    {
        $this->update([
            'last_searched_at' => now(),
            'clips_found' => $this->clips_found + $clipsFound,
        ]);
    }
}
