<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inquiry extends Model
{
    protected $fillable = [
        'subject',
        'description',
        'status',
        'urgency',
        'received_at',
        'deadline',
        'journalist_id',
        'journalist_name',
        'journalist_email',
        'outlet_id',
        'outlet_name',
        'project_id',
        'handled_by',
        'response_notes',
        'ai_insights',
        'resulting_clip_id',
        'created_by',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'deadline' => 'datetime',
        'ai_insights' => 'array',
    ];

    // Relationships

    public function journalist(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'journalist_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'outlet_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resultingClip(): BelongsTo
    {
        return $this->belongsTo(PressClip::class, 'resulting_clip_id');
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class);
    }

    // Scopes

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeResponding($query)
    {
        return $query->where('status', 'responding');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['new', 'responding']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency', ['urgent', 'breaking']);
    }

    public function scopeDeadlineToday($query)
    {
        return $query->whereDate('deadline', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereIn('status', ['new', 'responding']);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'new')
                ->orWhere(function ($q2) {
                    $q2->whereIn('urgency', ['urgent', 'breaking'])
                        ->where('status', 'responding');
                })
                ->orWhere(function ($q3) {
                    $q3->where('deadline', '<=', now()->addDay())
                        ->whereIn('status', ['new', 'responding']);
                });
        });
    }

    // Accessors

    public function getJournalistDisplayNameAttribute(): string
    {
        return $this->journalist?->name ?? $this->journalist_name ?? 'Unknown';
    }

    public function getOutletDisplayNameAttribute(): string
    {
        return $this->outlet?->name ?? $this->outlet_name ?? 'Unknown';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'blue',
            'responding' => 'amber',
            'completed' => 'green',
            'declined' => 'gray',
            'no_response' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'New',
            'responding' => 'In Progress',
            'completed' => 'Completed',
            'declined' => 'Declined',
            'no_response' => 'No Response',
            default => ucfirst($this->status),
        };
    }

    public function getUrgencyColorAttribute(): string
    {
        return match ($this->urgency) {
            'breaking' => 'red',
            'urgent' => 'amber',
            default => 'gray',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline &&
            $this->deadline < now() &&
            in_array($this->status, ['new', 'responding']);
    }

    public function getDeadlineStatusAttribute(): string
    {
        if (! $this->deadline) {
            return 'none';
        }
        if ($this->deadline < now()) {
            return 'overdue';
        }
        if ($this->deadline->isToday()) {
            return 'today';
        }
        if ($this->deadline->isTomorrow()) {
            return 'tomorrow';
        }
        if ($this->deadline <= now()->addWeek()) {
            return 'this_week';
        }

        return 'future';
    }

    // Methods

    public function assignTo(User $user): void
    {
        $this->update([
            'handled_by' => $user->id,
            'status' => 'responding',
        ]);
    }

    public function markCompleted(?string $notes = null): void
    {
        $data = ['status' => 'completed'];
        if ($notes) {
            $data['response_notes'] = $notes;
        }
        $this->update($data);
    }
}
