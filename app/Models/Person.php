<?php

namespace App\Models;

use App\Models\Traits\HasGeographicTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Person extends Model
{
    use HasFactory, HasGeographicTags;

    protected $fillable = [
        'name',
        'organization_id',
        'title',
        'email',
        'phone',
        'linkedin_url',
        'photo_url',
        'bio',
        'photo_path',
        'notes',
        // CRM fields
        'status',
        'owner_id',
        'source',
        'tags',
        'last_contacted_at',
        'next_action_at',
        'next_action_note',
        'score',
        // Media/Journalist fields
        'is_journalist',
        'beat',
        'media_notes',
        'responsiveness',
        // Grant reporting fields
        'contact_type',
        'political_affiliation',
    ];

    protected $casts = [
        'tags' => 'array',
        'last_contacted_at' => 'datetime',
        'next_action_at' => 'datetime',
        'score' => 'integer',
        'is_journalist' => 'boolean',
    ];

    public const CONTACT_TYPES = [
        'government_official' => 'Government Official',
        'funder' => 'Funder',
        'grantee' => 'Grantee',
        'partner' => 'Partner',
        'media' => 'Media',
        'academic' => 'Academic',
        'stakeholder' => 'Stakeholder',
        'other' => 'Other',
    ];

    public const POLITICAL_AFFILIATIONS = [
        'bipartisan' => 'Bipartisan',
        'progressive' => 'Progressive',
        'conservative' => 'Conservative',
        'nonpartisan' => 'Nonpartisan',
        'democratic' => 'Democratic',
        'republican' => 'Republican',
        'independent' => 'Independent',
    ];

    public const STATUSES = [
        'lead' => 'Lead',
        'prospect' => 'Prospect',
        'active' => 'Active',
        'partner' => 'Partner',
        'inactive' => 'Inactive',
    ];

    /**
     * Get the organization this person belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Owner (assigned user).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Simple interactions (activity log).
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(PersonInteraction::class)->orderByDesc('occurred_at');
    }

    /**
     * Get the meetings this person attended.
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_person')
            ->withTimestamps();
    }

    /**
     * Get the attachments for this person.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(ProfileAttachment::class, 'attachable');
    }

    /**
     * Get the projects this person is linked to.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_person')
            ->withPivot(['role', 'notes'])
            ->withTimestamps();
    }

    // ===== Media/Journalist Relationships =====

    /**
     * Press clips where this person is the journalist.
     */
    public function pressClips(): HasMany
    {
        return $this->hasMany(PressClip::class, 'journalist_id');
    }

    /**
     * Pitches sent to this journalist.
     */
    public function pitchesReceived(): HasMany
    {
        return $this->hasMany(Pitch::class, 'journalist_id');
    }

    /**
     * Inquiries from this journalist.
     */
    public function inquiriesMade(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'journalist_id');
    }

    // ===== Scopes =====

    /**
     * Filter to journalists only.
     */
    public function scopeJournalists($query)
    {
        return $query->where('is_journalist', true);
    }

    // ===== Accessors =====

    /**
     * Get media relationship stats for this journalist.
     */
    public function getMediaStatsAttribute(): array
    {
        return [
            'clips_count' => $this->pressClips()->count(),
            'pitches_received' => $this->pitchesReceived()->count(),
            'pitches_successful' => $this->pitchesReceived()->successful()->count(),
            'inquiries_made' => $this->inquiriesMade()->count(),
            'last_contact' => $this->pressClips()
                ->latest('published_at')
                ->first()?->published_at ??
                $this->pitchesReceived()
                    ->latest('pitched_at')
                    ->first()?->pitched_at,
        ];
    }
}
