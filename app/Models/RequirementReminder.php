<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementReminder extends Model
{
    protected $fillable = [
        'reporting_requirement_id',
        'reminder_date',
        'days_before_due',
        'sent_at',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ReportingRequirement::class, 'reporting_requirement_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('reminder_date', today());
    }

    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }
}

