<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pitch extends Model
{
    protected $fillable = [
        'subject',
        'description',
        'status',
        'pitched_at',
        'journalist_id',
        'journalist_name',
        'journalist_email',
        'outlet_id',
        'outlet_name',
        'project_id',
        'pitched_by',
        'follow_ups',
        'resulting_clip_id',
        'notes',
    ];

    protected $casts = [
        'pitched_at' => 'datetime',
        'follow_ups' => 'array',
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

    public function pitchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pitched_by');
    }

    public function resultingClip(): BelongsTo
    {
        return $this->belongsTo(PressClip::class, 'resulting_clip_id');
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PitchAttachment::class);
    }

    // Scopes

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFollowingUp($query)
    {
        return $query->where('status', 'following_up');
    }

    public function scopeAwaitingResponse($query)
    {
        return $query->whereIn('status', ['sent', 'following_up']);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['accepted', 'published']);
    }

    // Accessors

    public function getJournalistDisplayNameAttribute(): string
    {
        return $this->journalist?->name ?? $this->journalist_name ?? 'TBD';
    }

    public function getOutletDisplayNameAttribute(): string
    {
        return $this->outlet?->name ?? $this->outlet_name ?? 'TBD';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'following_up' => 'amber',
            'accepted' => 'green',
            'published' => 'indigo',
            'declined' => 'red',
            'no_response' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'following_up' => 'Following Up',
            'accepted' => 'Accepted',
            'published' => 'Published',
            'declined' => 'Declined',
            'no_response' => 'No Response',
            default => ucfirst($this->status),
        };
    }

    public function getDaysSincePitchedAttribute(): ?int
    {
        return $this->pitched_at?->diffInDays(now());
    }

    public function getFollowUpCountAttribute(): int
    {
        return count($this->follow_ups ?? []);
    }

    // Methods

    public function addFollowUp(string $note, string $method = 'email'): void
    {
        $followUps = $this->follow_ups ?? [];
        $followUps[] = [
            'date' => now()->toDateString(),
            'note' => $note,
            'method' => $method,
        ];
        $this->update([
            'follow_ups' => $followUps,
            'status' => 'following_up',
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'pitched_at' => $this->pitched_at ?? now(),
        ]);
    }
}
