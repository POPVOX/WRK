<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportingRequirement extends Model
{
    protected $fillable = [
        'legislative_report_id',
        'category',
        'report_title',
        'responsible_agency',
        'timeline_type',
        'timeline_value',
        'due_date',
        'description',
        'reporting_recipients',
        'source_page_reference',
        'status',
        'completed_at',
        'completed_by',
        'assigned_to',
        'project_id',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'new' => 'New',
        'prior_year' => 'Prior Year',
        'ongoing' => 'Ongoing',
    ];

    public const TIMELINE_TYPES = [
        'days_from_enactment' => 'Days from Enactment',
        'days_from_report' => 'Days from Report',
        'quarterly' => 'Quarterly',
        'annual' => 'Annual',
        'specific_date' => 'Specific Date',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'submitted' => 'Submitted',
        'overdue' => 'Overdue',
    ];

    public const AGENCIES = [
        'AOC' => 'Architect of the Capitol',
        'USCP' => 'U.S. Capitol Police',
        'CAO' => 'Chief Administrative Officer',
        'GAO' => 'Government Accountability Office',
        'CRS' => 'Congressional Research Service',
        'LOC' => 'Library of Congress',
        'GPO' => 'Government Publishing Office',
        'CBO' => 'Congressional Budget Office',
        'OOC' => 'Office of Congressional Ethics',
        'OSLA' => 'Office of the Senate Legal Counsel',
        'Other' => 'Other',
    ];

    public function legislativeReport(): BelongsTo
    {
        return $this->belongsTo(LegislativeReport::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(RequirementReminder::class);
    }

    public function calculateDueDate(): ?Carbon
    {
        if ($this->timeline_type === 'specific_date') {
            return $this->due_date;
        }

        $enactmentDate = $this->legislativeReport?->enactment_date;
        if (!$enactmentDate) {
            return null;
        }

        return match ($this->timeline_type) {
            'days_from_enactment' => Carbon::parse($enactmentDate)->addDays($this->timeline_value),
            'days_from_report' => Carbon::parse($this->legislativeReport->created_at)->addDays($this->timeline_value),
            'quarterly' => $this->calculateNextQuarterlyDate($enactmentDate),
            'annual' => Carbon::parse($enactmentDate)->addYear(),
            default => null,
        };
    }

    protected function calculateNextQuarterlyDate(Carbon $fromDate): Carbon
    {
        $now = now();
        $date = Carbon::parse($fromDate);
        
        while ($date->lte($now)) {
            $date->addMonths(3);
        }
        
        return $date;
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'submitted'
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date || $this->status === 'submitted') {
            return null;
        }
        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'submitted') {
            return 'submitted';
        }

        if ($this->isOverdue()) {
            return 'overdue';
        }

        return $this->status;
    }

    public function createReminders(): void
    {
        if (!$this->due_date) {
            return;
        }

        $reminderDays = config('services.legislative_tracker.reminder_days', [7, 14, 30]);

        foreach ($reminderDays as $daysBefore) {
            $reminderDate = $this->due_date->copy()->subDays($daysBefore);
            
            if ($reminderDate->isFuture()) {
                RequirementReminder::updateOrCreate(
                    [
                        'reporting_requirement_id' => $this->id,
                        'days_before_due' => $daysBefore,
                    ],
                    [
                        'reminder_date' => $reminderDate,
                    ]
                );
            }
        }
    }
}
