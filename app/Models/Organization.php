<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'type',
        'website',
        'linkedin_url',
        'logo_url',
        'description',
        'notes',
        'is_funder',
        'funder_priorities',
        'funder_preferences',
        'is_congressional',
        'bioguide_id',
        'chamber',
        'state',
        'district',
        'party',
        'committees',
        'leadership_positions',
    ];

    protected $casts = [
        'is_funder' => 'boolean',
        'is_congressional' => 'boolean',
        'committees' => 'array',
        'leadership_positions' => 'array',
    ];

    /**
     * The organization types available for selection.
     */
    public const TYPES = [
        'Advocacy',
        'Trade Association',
        'Government Agency',
        'Nonprofit',
        'Business',
        'Labor',
        'Constituent',
        'Congressional Office',
        'Funder',
        'Media',
        'Other',
    ];

    /**
     * Get the meetings that involve this organization.
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_organization')
            ->withTimestamps();
    }

    /**
     * Get the people that belong to this organization.
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    /**
     * Get the attachments for this organization.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(ProfileAttachment::class, 'attachable');
    }

    /**
     * Get the projects this organization is linked to.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_organization')
            ->withPivot(['role', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the grants from this funder organization.
     */
    public function grants(): HasMany
    {
        return $this->hasMany(Grant::class);
    }

    // Scopes
    public function scopeFunders($query)
    {
        return $query->where('is_funder', true);
    }

    public function scopeCongressional($query)
    {
        return $query->where('is_congressional', true);
    }

    /**
     * Get the commitments related to this organization.
     */
    public function commitments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    /**
     * Get the press clips from this outlet.
     */
    public function pressClips(): HasMany
    {
        return $this->hasMany(PressClip::class, 'outlet_id');
    }

    /**
     * Get the pitches sent to this outlet.
     */
    public function pitches(): HasMany
    {
        return $this->hasMany(Pitch::class, 'outlet_id');
    }

    /**
     * Get the inquiries from this outlet.
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'outlet_id');
    }

    /**
     * Get journalists/press contacts at this media outlet.
     */
    public function journalists(): HasMany
    {
        return $this->hasMany(Person::class)->where('role', 'journalist');
    }
}
