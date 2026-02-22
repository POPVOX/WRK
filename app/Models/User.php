<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'title',
        'role',
        'access_level',
        'start_date',
        'end_date',
        'reports_to',
        'responsibilities',
        'bio',
        'phone',
        'linkedin',
        'onboarding_checklist',
        'photo_url',
        'profile_completed_at',
        'location',
        'timezone',
        'timezone_confirmed_at',
        'bio_short',
        'bio_medium',
        'publications',
        'is_visible',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'calendar_import_date',
        'gmail_import_date',
        'gmail_history_id',
        'activation_token',
        'activation_token_expires_at',
        'activated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'activation_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'onboarding_checklist' => 'array',
            'publications' => 'array',
            'profile_completed_at' => 'datetime',
            'google_token_expires_at' => 'datetime',
            'calendar_import_date' => 'datetime',
            'gmail_import_date' => 'datetime',
            'activation_token_expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'timezone_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Check if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true || $this->access_level === 'admin';
    }

    /**
     * Check if the user is management level.
     */
    public function isManagement(): bool
    {
        return in_array($this->access_level, ['management', 'admin']);
    }

    /**
     * Check if user can view content with given visibility.
     */
    public function canView(string $visibility): bool
    {
        if ($visibility === 'all') {
            return true;
        }
        if ($visibility === 'management' && $this->isManagement()) {
            return true;
        }
        if ($visibility === 'admin' && $this->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get the user's manager.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reports_to');
    }

    /**
     * Get the user's direct reports.
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'reports_to');
    }

    /**
     * Get the meetings logged by this user.
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    /**
     * Get trips the user is traveling on.
     */
    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class, 'trip_travelers')
            ->withPivot(['role', 'calendar_events_created', 'personal_notes'])
            ->withTimestamps();
    }

    /**
     * Get the user's travel profile.
     */
    public function travelProfile(): HasOne
    {
        return $this->hasOne(TravelProfile::class);
    }

    /**
     * Get the actions assigned to this user.
     */
    public function assignedActions(): HasMany
    {
        return $this->hasMany(Action::class, 'assigned_to');
    }

    /**
     * Get projects this user is staffed on.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_staff')
            ->withPivot(['role', 'added_at'])
            ->orderByPivot('added_at', 'desc');
    }

    /**
     * Get Gmail messages synced for this user.
     */
    public function gmailMessages(): HasMany
    {
        return $this->hasMany(GmailMessage::class);
    }

    /**
     * Get inbox action audit logs for this user.
     */
    public function inboxActionLogs(): HasMany
    {
        return $this->hasMany(InboxActionLog::class);
    }
}
