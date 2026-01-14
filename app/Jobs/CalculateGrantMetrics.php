<?php

namespace App\Jobs;

use App\Models\Grant;
use App\Models\Meeting;
use App\Models\MetricCalculation;
use App\Models\Person;
use App\Models\Project;
use App\Models\ProjectDocument;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CalculateGrantMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $grantId,
        public string $startDate,
        public string $endDate,
        public bool $forceRecalculate = false
    ) {}

    public function handle(): array
    {
        $grant = Grant::with('activeReportingSchema')->find($this->grantId);
        if (! $grant) {
            Log::warning("CalculateGrantMetrics: Grant ID {$this->grantId} not found.");

            return [];
        }

        $schema = $grant->activeReportingSchema;
        if (! $schema) {
            Log::info("CalculateGrantMetrics: No active schema for Grant ID {$this->grantId}.");

            return [];
        }

        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $results = [];

        foreach ($schema->getAutoMetrics() as $metric) {
            $metricId = $metric['id'] ?? null;
            if (! $metricId) {
                continue;
            }

            // Check if we have a cached calculation and should skip
            if (! $this->forceRecalculate) {
                $existing = MetricCalculation::where('grant_id', $this->grantId)
                    ->where('metric_id', $metricId)
                    ->forPeriod($startDate, $endDate)
                    ->where('calculation_method', 'auto')
                    ->where('calculated_at', '>', now()->subHour())
                    ->first();

                if ($existing) {
                    $results[$metricId] = $existing->calculated_value;

                    continue;
                }
            }

            // Calculate the metric
            $calculatedValue = $this->calculateMetric($metric, $startDate, $endDate);

            // Store the calculation
            MetricCalculation::updateOrCreate(
                [
                    'grant_id' => $this->grantId,
                    'metric_id' => $metricId,
                    'reporting_period_start' => $startDate,
                    'reporting_period_end' => $endDate,
                ],
                [
                    'schema_id' => $schema->id,
                    'calculated_value' => $calculatedValue,
                    'calculation_method' => 'auto',
                    'calculated_at' => now(),
                    'calculated_by' => Auth::id(),
                ]
            );

            $results[$metricId] = $calculatedValue;
        }

        Log::info('CalculateGrantMetrics: Calculated '.count($results)." metrics for Grant ID {$this->grantId}");

        return $results;
    }

    /**
     * Calculate a single metric based on its configuration.
     */
    protected function calculateMetric(array $metric, Carbon $startDate, Carbon $endDate): array
    {
        $dataSource = $metric['data_source'] ?? null;
        $filters = $metric['filters'] ?? [];
        $target = $metric['target'] ?? null;

        $items = match ($dataSource) {
            'meetings' => $this->queryMeetings($filters, $startDate, $endDate),
            'documents' => $this->queryDocuments($filters, $startDate, $endDate),
            'contacts' => $this->queryContacts($filters, $startDate, $endDate),
            'projects' => $this->queryProjects($filters, $startDate, $endDate),
            default => collect(),
        };

        $value = $items->count();

        return [
            'value' => $value,
            'items' => $items->map(fn ($item) => [
                'type' => $dataSource,
                'id' => $item->id,
                'title' => $item->title ?? $item->name ?? "Item #{$item->id}",
                'date' => ($item->meeting_date ?? $item->created_at)?->format('Y-m-d'),
            ])->toArray(),
            'filters_applied' => $filters,
            'date_range' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
            'target' => $target,
            'status' => $this->determineStatus($value, $target),
        ];
    }

    /**
     * Query meetings based on filters.
     */
    protected function queryMeetings(array $filters, Carbon $startDate, Carbon $endDate)
    {
        $query = Meeting::query()
            ->whereBetween('meeting_date', [$startDate, $endDate]);

        // Filter by grant association
        if (! empty($filters['grant_associations'])) {
            foreach ($filters['grant_associations'] as $grantAssoc) {
                if ($grantAssoc === 'current_grant') {
                    $query->whereJsonContains('grant_associations', $this->grantId);
                } else {
                    $query->whereJsonContains('grant_associations', $grantAssoc);
                }
            }
        } else {
            // Default: filter by current grant
            $query->whereJsonContains('grant_associations', $this->grantId);
        }

        // Filter by required tags
        if (! empty($filters['required_tags'])) {
            foreach ($filters['required_tags'] as $tag) {
                $query->whereJsonContains('metric_tags', $tag);
            }
        }

        // Filter by minimum external organizations
        if (! empty($filters['min_external_organizations'])) {
            $query->where('external_organizations_count', '>=', $filters['min_external_organizations']);
        }

        // Filter by participant types (e.g., government officials)
        if (! empty($filters['participant_types'])) {
            $query->whereHas('people', function ($q) use ($filters) {
                $q->whereIn('contact_type', $filters['participant_types']);
            });
        }

        return $query->get();
    }

    /**
     * Query documents based on filters.
     */
    protected function queryDocuments(array $filters, Carbon $startDate, Carbon $endDate)
    {
        $query = ProjectDocument::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Filter by grant association
        if (! empty($filters['grant_associations'])) {
            foreach ($filters['grant_associations'] as $grantAssoc) {
                if ($grantAssoc === 'current_grant') {
                    $query->whereJsonContains('grant_associations', $this->grantId);
                } else {
                    $query->whereJsonContains('grant_associations', $grantAssoc);
                }
            }
        } else {
            $query->whereJsonContains('grant_associations', $this->grantId);
        }

        // Filter by document type
        if (! empty($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        // Filter by required tags
        if (! empty($filters['required_tags'])) {
            foreach ($filters['required_tags'] as $tag) {
                $query->whereJsonContains('metric_tags', $tag);
            }
        }

        return $query->get();
    }

    /**
     * Query contacts based on filters.
     */
    protected function queryContacts(array $filters, Carbon $startDate, Carbon $endDate)
    {
        // For contacts, we typically count unique contacts engaged during the period
        // This requires looking at meetings in the period that have these contacts
        $query = Person::query()
            ->whereHas('meetings', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('meeting_date', [$startDate, $endDate]);

                // Also filter meetings by grant if needed
                $q->whereJsonContains('grant_associations', $this->grantId);
            });

        // Filter by contact type
        if (! empty($filters['contact_types'])) {
            $query->whereIn('contact_type', $filters['contact_types']);
        }

        // Filter by political affiliation
        if (! empty($filters['political_affiliations'])) {
            $query->whereIn('political_affiliation', $filters['political_affiliations']);
        }

        return $query->distinct()->get();
    }

    /**
     * Query projects based on filters.
     */
    protected function queryProjects(array $filters, Carbon $startDate, Carbon $endDate)
    {
        $query = Project::query()
            ->whereJsonContains('grant_associations', $this->grantId);

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by required tags
        if (! empty($filters['required_tags'])) {
            foreach ($filters['required_tags'] as $tag) {
                $query->whereJsonContains('metric_tags', $tag);
            }
        }

        // Filter by date range (projects active during period)
        $query->where(function ($q) use ($startDate, $endDate) {
            $q->where(function ($inner) use ($startDate, $endDate) {
                $inner->where('start_date', '<=', $endDate)
                    ->where(function ($final) use ($startDate) {
                        $final->whereNull('actual_end_date')
                            ->orWhere('actual_end_date', '>=', $startDate);
                    });
            });
        });

        return $query->get();
    }

    /**
     * Determine the status relative to target.
     */
    protected function determineStatus(int $value, ?int $target): string
    {
        if ($target === null) {
            return 'no_target';
        }

        if ($value >= $target) {
            return $value > $target ? 'above_target' : 'on_track';
        }

        // Calculate percentage
        $percentage = $target > 0 ? ($value / $target) * 100 : 0;

        if ($percentage >= 75) {
            return 'on_track';
        }

        return 'below_target';
    }
}

