<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the meetings that discuss this issue.
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_issue')
            ->withTimestamps();
    }
}
