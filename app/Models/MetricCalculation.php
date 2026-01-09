<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricCalculation extends Model
{
    protected $fillable = [
        'grant_id',
        'schema_id',
        'reporting_period_start',
        'reporting_period_end',
        'metric_id',
        'calculated_value',
        'manual_value',
        'calculation_method',
        'calculated_at',
        'calculated_by',
        'notes',
    ];

    protected $casts = [
        'calculated_value' => 'array',
        'reporting_period_start' => 'date',
        'reporting_period_end' => 'date',
        'calculated_at' => 'datetime',
    ];

    public const CALCULATION_METHODS = [
        'auto' => 'Automatic',
        'manual' => 'Manual Entry',
        'hybrid' => 'Hybrid',
    ];

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }

    public function schema(): BelongsTo
    {
        return $this->belongsTo(GrantReportingSchema::class, 'schema_id');
    }

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * Get the calculated value.
     */
    public function getValueAttribute(): mixed
    {
        if ($this->calculation_method === 'manual') {
            return $this->manual_value;
        }

        return $this->calculated_value['value'] ?? null;
    }

    /**
     * Get the items that were counted (for auto metrics).
     */
    public function getItemsAttribute(): array
    {
        return $this->calculated_value['items'] ?? [];
    }

    /**
     * Get the target for this metric.
     */
    public function getTargetAttribute(): ?int
    {
        return $this->calculated_value['target'] ?? null;
    }

    /**
     * Get the status relative to target.
     */
    public function getStatusAttribute(): ?string
    {
        return $this->calculated_value['status'] ?? null;
    }

    /**
     * Check if metric is on track.
     */
    public function isOnTrack(): bool
    {
        return in_array($this->status, ['on_track', 'above_target']);
    }

    /**
     * Check if metric is below target.
     */
    public function isBelowTarget(): bool
    {
        return $this->status === 'below_target';
    }

    /**
     * Get the period label.
     */
    public function getPeriodLabelAttribute(): string
    {
        $start = $this->reporting_period_start;
        $end = $this->reporting_period_end;

        // Try to format as quarter
        if ($start->month === 1 && $end->month === 3) {
            return 'Q1 '.$start->year;
        }
        if ($start->month === 4 && $end->month === 6) {
            return 'Q2 '.$start->year;
        }
        if ($start->month === 7 && $end->month === 9) {
            return 'Q3 '.$start->year;
        }
        if ($start->month === 10 && $end->month === 12) {
            return 'Q4 '.$start->year;
        }

        return $start->format('M Y').' - '.$end->format('M Y');
    }

    /**
     * Scope for a specific period.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('reporting_period_start', $startDate)
            ->where('reporting_period_end', $endDate);
    }

    /**
     * Scope for auto-calculated metrics.
     */
    public function scopeAutoCalculated($query)
    {
        return $query->where('calculation_method', 'auto');
    }

    /**
     * Scope for manual-entry metrics.
     */
    public function scopeManualEntry($query)
    {
        return $query->where('calculation_method', 'manual');
    }
}
