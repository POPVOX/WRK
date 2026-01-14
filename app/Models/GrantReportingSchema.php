<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrantReportingSchema extends Model
{
    protected $fillable = [
        'grant_id',
        'version',
        'status',
        'schema_data',
        'created_by',
    ];

    protected $casts = [
        'schema_data' => 'array',
        'version' => 'integer',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'archived' => 'Archived',
    ];

    public function grant(): BelongsTo
    {
        return $this->belongsTo(Grant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function metricCalculations(): HasMany
    {
        return $this->hasMany(MetricCalculation::class, 'schema_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(SchemaChatbotConversation::class, 'schema_id');
    }

    /**
     * Get all pathways from the schema.
     */
    public function getPathwaysAttribute(): array
    {
        return $this->schema_data['pathways'] ?? [];
    }

    /**
     * Get all outcomes across all pathways.
     */
    public function getAllOutcomes(): array
    {
        $outcomes = [];
        foreach ($this->pathways as $pathway) {
            foreach ($pathway['outcomes'] ?? [] as $outcome) {
                $outcome['pathway_id'] = $pathway['id'] ?? null;
                $outcome['pathway_name'] = $pathway['name'] ?? null;
                $outcomes[] = $outcome;
            }
        }

        return $outcomes;
    }

    /**
     * Get all metrics across all pathways and outcomes.
     */
    public function getAllMetrics(): array
    {
        $metrics = [];
        foreach ($this->getAllOutcomes() as $outcome) {
            foreach ($outcome['metrics'] ?? [] as $metric) {
                $metric['outcome_id'] = $outcome['id'] ?? null;
                $metric['outcome_name'] = $outcome['name'] ?? null;
                $metric['pathway_id'] = $outcome['pathway_id'] ?? null;
                $metric['pathway_name'] = $outcome['pathway_name'] ?? null;
                $metrics[] = $metric;
            }
        }

        return $metrics;
    }

    /**
     * Get a specific metric by ID.
     */
    public function getMetric(string $metricId): ?array
    {
        foreach ($this->getAllMetrics() as $metric) {
            if (($metric['id'] ?? null) === $metricId) {
                return $metric;
            }
        }

        return null;
    }

    /**
     * Get all auto-calculable metrics.
     */
    public function getAutoMetrics(): array
    {
        return array_filter($this->getAllMetrics(), function ($metric) {
            return ($metric['calculation'] ?? 'manual') === 'auto';
        });
    }

    /**
     * Get all manual-entry metrics.
     */
    public function getManualMetrics(): array
    {
        return array_filter($this->getAllMetrics(), function ($metric) {
            return ($metric['calculation'] ?? 'manual') === 'manual';
        });
    }

    /**
     * Get the tags configuration.
     */
    public function getTagsConfigAttribute(): array
    {
        return $this->schema_data['tags_config'] ?? [];
    }

    /**
     * Get all required tag names.
     */
    public function getRequiredTags(): array
    {
        return array_column($this->tags_config, 'tag_name');
    }

    /**
     * Get reporting period configuration.
     */
    public function getReportingPeriodsAttribute(): string
    {
        return $this->schema_data['reporting_periods'] ?? 'quarterly';
    }

    /**
     * Check if schema is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if schema is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Activate this schema (mark as active, archive previous).
     */
    public function activate(): void
    {
        // Archive any currently active schema for this grant
        self::where('grant_id', $this->grant_id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $this->update(['status' => 'active']);
    }

    /**
     * Create a new version of this schema.
     */
    public function createNewVersion(?array $schemaData = null): self
    {
        return self::create([
            'grant_id' => $this->grant_id,
            'version' => $this->version + 1,
            'status' => 'draft',
            'schema_data' => $schemaData ?? $this->schema_data,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Scope to get active schemas.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get draft schemas.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}

