<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutreachAutomation extends Model
{
    protected $fillable = [
        'user_id',
        'newsletter_id',
        'project_id',
        'name',
        'status',
        'trigger_type',
        'rrule',
        'timezone',
        'action_type',
        'prompt',
        'config',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(OutreachNewsletter::class, 'newsletter_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OutreachActivityLog::class, 'automation_id')->latest('created_at');
    }
}

