<?php

namespace App\Models;

use App\Models\Traits\HasGeographicTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, HasGeographicTags, SoftDeletes;

    protected $fillable = [
        'name',
        'suggested_name',
        'abbreviation',
        'type',
        'website',
        'email',
        'phone',
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
        return $this->hasMany(Person::class)->where('is_journalist', true);
    }

    /**
     * Get a clean display version of the website URL (without protocol/www).
     */
    public function getDisplayWebsiteAttribute(): ?string
    {
        if (!$this->website) {
            return null;
        }

        $url = $this->website;
        $url = preg_replace('#^https?://#i', '', $url);
        $url = preg_replace('#^www\.#i', '', $url);
        $url = rtrim($url, '/');

        return $url;
    }

    /**
     * Get a clean display version of the LinkedIn URL.
     */
    public function getDisplayLinkedinAttribute(): ?string
    {
        if (!$this->linkedin_url) {
            return null;
        }

        $url = $this->linkedin_url;
        $url = preg_replace('#^https?://#i', '', $url);
        $url = preg_replace('#^www\.#i', '', $url);
        $url = rtrim($url, '/');

        return $url;
    }

    /**
     * Check if the organization name looks like concatenated words.
     */
    public function hasCondensedName(): bool
    {
        $name = $this->name;

        if (str_contains($name, ' ')) {
            return false;
        }

        if (preg_match('/[a-z][A-Z]/', $name)) {
            return true;
        }

        if (strlen($name) >= 15 && !str_contains($name, ' ') && !str_contains($name, '-')) {
            return true;
        }

        return false;
    }

    /**
     * Check if the name looks like a domain/URL (e.g. "americanprogress.org").
     */
    public function looksLikeDomain(): bool
    {
        $name = trim($this->name);

        // Matches patterns like "example.org", "my-site.com", "heritage.org"
        if (preg_match('/^[\w\-]+\.(org|com|net|edu|gov|io|co|us|info)$/i', $name)) {
            return true;
        }

        // Matches full URLs that were stored as names
        if (preg_match('#^https?://#i', $name)) {
            return true;
        }

        // Matches "www.something"
        if (preg_match('/^www\./i', $name)) {
            return true;
        }

        return false;
    }

    /**
     * Check if this org's name needs AI normalization.
     */
    public function needsNameNormalization(): bool
    {
        // Skip if already has a pending suggestion
        if ($this->suggested_name) {
            return false;
        }

        return $this->hasCondensedName() || $this->looksLikeDomain();
    }
}
