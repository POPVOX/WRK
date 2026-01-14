<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityStats extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'meetings_attended',
        'meetings_organized',
        'documents_authored',
        'projects_owned',
        'projects_contributed',
        'decisions_made',
        'grant_deliverables',
        'grant_reports',
        'accomplishments_added',
        'recognition_received',
        'recognition_given',
        'total_impact_score',
        'last_calculated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'last_calculated_at' => 'datetime',
        'total_impact_score' => 'decimal:2',
    ];

    /**
     * Impact score weights
     */
    public const IMPACT_WEIGHTS = [
        'meetings_organized' => 2.0,
        'meetings_attended' => 0.5,
        'documents_authored' => 3.0,
        'projects_owned' => 5.0,
        'projects_contributed' => 1.0,
        'decisions_made' => 2.0,
        'grant_deliverables' => 4.0,
        'grant_reports' => 3.0,
        'accomplishments_added' => 1.0,
        'recognition_received' => 2.0,
        'recognition_given' => 1.0,
    ];

    /**
     * The user these stats belong to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate impact score from metrics
     */
    public function calculateImpactScore(): float
    {
        $score = 0;

        foreach (self::IMPACT_WEIGHTS as $metric => $weight) {
            $score += ($this->{$metric} ?? 0) * $weight;
        }

        return round($score, 2);
    }

    /**
     * Recalculate and save impact score
     */
    public function recalculateImpactScore(): void
    {
        $this->total_impact_score = $this->calculateImpactScore();
        $this->save();
    }

    /**
     * Get total activities count
     */
    public function getTotalActivitiesAttribute(): int
    {
        return $this->meetings_attended
            + $this->documents_authored
            + $this->projects_owned
            + $this->projects_contributed
            + $this->accomplishments_added;
    }

    /**
     * Scope: For user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For period
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', $startDate)
            ->where('period_end', $endDate);
    }

    /**
     * Scope: Overlapping period
     */
    public function scopeOverlappingPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '<=', $endDate)
            ->where('period_end', '>=', $startDate);
    }

    /**
     * Get or create stats for user and period
     */
    public static function getOrCreateForPeriod(int $userId, $startDate, $endDate): self
    {
        return self::firstOrCreate([
            'user_id' => $userId,
            'period_start' => $startDate,
            'period_end' => $endDate,
        ]);
    }
}

