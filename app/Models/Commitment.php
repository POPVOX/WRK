<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'direction',
        'status',
        'due_date',
        'completed_at',
        'meeting_id',
        'project_id',
        'person_id',
        'organization_id',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'open')
            ->where('due_date', '<', now());
    }

    public function scopeFromUs($query)
    {
        return $query->where('direction', 'from_us');
    }

    public function scopeToUs($query)
    {
        return $query->where('direction', 'to_us');
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('status', 'open')
            ->where('due_date', '<=', now()->addDays($days))
            ->where('due_date', '>=', now());
    }

    // Relationships
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public function isOverdue(): bool
    {
        return $this->status === 'open'
            && $this->due_date
            && $this->due_date < now();
    }

    public function markComplete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function getContextNameAttribute(): string
    {
        if ($this->organization) {
            return $this->organization->name;
        }
        if ($this->person) {
            return $this->person->name;
        }
        if ($this->project) {
            return $this->project->name;
        }

        return 'Unknown';
    }
}
