<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    use HasFactory;

    protected $fillable = [
        'decision',
        'rationale',
        'outcome',
        'project_id',
        'meeting_id',
        'made_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'date',
    ];

    // Scopes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('decided_at', '>=', now()->subDays($days))
            ->orderByDesc('decided_at');
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function madeBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'made_by');
    }
}
