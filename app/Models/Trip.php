<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'start_date',
        'end_date',
        'primary_destination_city',
        'primary_destination_country',
        'primary_destination_region',
        'project_id',
        'created_by',
        'risk_level',
        'step_registration_required',
        'step_registration_completed',
        'travel_insurance_required',
        'travel_insurance_confirmed',
        'approval_required',
        'approved_by',
        'approved_at',
        'approval_notes',
        'partner_organization_id',
        'partner_program_name',
        'debrief_notes',
        'outcomes',
        'is_template',
        'created_from_template_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'step_registration_required' => 'boolean',
        'step_registration_completed' => 'boolean',
        'travel_insurance_required' => 'boolean',
        'travel_insurance_confirmed' => 'boolean',
        'approval_required' => 'boolean',
        'is_template' => 'boolean',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partnerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'partner_organization_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'created_from_template_id');
    }

    public function tripsFromTemplate(): HasMany
    {
        return $this->hasMany(Trip::class, 'created_from_template_id');
    }

    public function travelers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trip_travelers')
            ->withPivot(['role', 'calendar_events_created', 'personal_notes'])
            ->withTimestamps();
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(TripDestination::class)->orderBy('order');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(TripSegment::class)->orderBy('departure_datetime');
    }

    public function lodging(): HasMany
    {
        return $this->hasMany(TripLodging::class)->orderBy('check_in_date');
    }

    public function groundTransport(): HasMany
    {
        return $this->hasMany(TripGroundTransport::class)->orderBy('pickup_datetime');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TripExpense::class)->orderBy('expense_date');
    }

    public function sponsorships(): HasMany
    {
        return $this->hasMany(TripSponsorship::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TripEvent::class)->orderBy('start_datetime');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TripDocument::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TripChecklist::class);
    }

    // Scopes
    public function scopePlanning($query)
    {
        return $query->where('status', 'planning');
    }

    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planning', 'booked', 'in_progress']);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', today())
            ->whereIn('status', ['planning', 'booked'])
            ->orderBy('start_date');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('travelers', fn ($q) => $q->where('users.id', $userId));
    }

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeNotTemplates($query)
    {
        return $query->where('is_template', false);
    }

    // Helpers
    public function getDurationAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses->sum('amount_usd') ?: $this->expenses->sum('amount');
    }

    public function getTotalSponsorshipAttribute(): float
    {
        return $this->sponsorships->sum('amount');
    }

    public function getLeadTravelerAttribute(): ?User
    {
        return $this->travelers()->wherePivot('role', 'lead')->first();
    }

    public function getCountryFlagAttribute(): string
    {
        $code = strtoupper($this->primary_destination_country);
        if (strlen($code) !== 2) {
            return '🌍';
        }

        // Convert country code to flag emoji
        $flag = '';
        foreach (str_split($code) as $char) {
            $flag .= mb_chr(ord($char) + 127397);
        }

        return $flag;
    }

    public function isUserTraveler(User $user): bool
    {
        return $this->travelers->contains($user);
    }

    public function isUserLead(User $user): bool
    {
        return $this->travelers()
            ->wherePivot('role', 'lead')
            ->where('users.id', $user->id)
            ->exists();
    }

    public function hasComplianceIssues(): bool
    {
        if ($this->step_registration_required && ! $this->step_registration_completed) {
            return true;
        }

        if ($this->travel_insurance_required && ! $this->travel_insurance_confirmed) {
            return true;
        }

        if ($this->approval_required && ! $this->approved_at) {
            return true;
        }

        return false;
    }

    // Static helpers
    public static function getTypeOptions(): array
    {
        return [
            'conference_event' => 'Conference/Event',
            'funder_meeting' => 'Funder Meeting',
            'site_visit' => 'Site Visit',
            'advocacy_hill_day' => 'Advocacy/Hill Day',
            'training' => 'Training',
            'partner_delegation' => 'Partner Delegation',
            'board_meeting' => 'Board Meeting',
            'speaking_engagement' => 'Speaking Engagement',
            'research' => 'Research',
            'other' => 'Other',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'planning' => 'Planning',
            'booked' => 'Booked',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public static function getStatusColors(): array
    {
        return [
            'planning' => 'bg-yellow-100 text-yellow-800',
            'booked' => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-green-100 text-green-800',
            'completed' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
    }

    public static function getTypeIcons(): array
    {
        return [
            'conference_event' => '🎪',
            'funder_meeting' => '💰',
            'site_visit' => '🏛️',
            'advocacy_hill_day' => '🏛️',
            'training' => '📚',
            'partner_delegation' => '🤝',
            'board_meeting' => '👥',
            'speaking_engagement' => '🎤',
            'research' => '🔬',
            'other' => '✈️',
        ];
    }
}
