<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutreachNewsletter extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'slug',
        'channel',
        'status',
        'cadence',
        'audience_filters',
        'planning_notes',
        'publishing_checklist',
        'next_issue_date',
        'last_issue_sent_at',
        'substack_publication_url',
        'substack_section',
        'default_subject_prefix',
    ];

    protected function casts(): array
    {
        return [
            'audience_filters' => 'array',
            'publishing_checklist' => 'array',
            'next_issue_date' => 'date',
            'last_issue_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(OutreachCampaign::class, 'newsletter_id')->latest('created_at');
    }

    public function automations(): HasMany
    {
        return $this->hasMany(OutreachAutomation::class, 'newsletter_id')->latest('created_at');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OutreachActivityLog::class, 'newsletter_id')->latest('created_at');
    }
}

