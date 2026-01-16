<?php

namespace App\Jobs;

use App\Models\Accomplishment;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Models\UserActivityStats;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CalculateUserAccomplishments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $startDate,
        public string $endDate,
        public bool $forceRecalculate = false
    ) {
    }

    public function handle(): UserActivityStats
    {
        try {
            $user = User::find($this->userId);
            if (!$user) {
                Log::warning("CalculateUserAccomplishments: User {$this->userId} not found.");

                return new UserActivityStats;
            }

            $startDate = Carbon::parse($this->startDate)->startOfDay();
            $endDate = Carbon::parse($this->endDate)->endOfDay();

            // Check for existing stats
            $stats = UserActivityStats::where('user_id', $this->userId)
                ->where('period_start', $startDate->toDateString())
                ->where('period_end', $endDate->toDateString())
                ->first();

            if ($stats && !$this->forceRecalculate) {
                // Return cached stats if recent enough (within 1 hour)
                if ($stats->last_calculated_at && $stats->last_calculated_at->diffInMinutes(now()) < 60) {
                    return $stats;
                }
            }

            // Calculate meetings attended
            $meetingsAttended = Meeting::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('teamMembers', fn($t) => $t->where('users.id', $user->id));
            })
                ->whereBetween('meeting_date', [$startDate, $endDate])
                ->count();

            // Calculate meetings organized
            $meetingsOrganized = Meeting::where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                    ->orWhere('user_id', $user->id);
            })
                ->whereBetween('meeting_date', [$startDate, $endDate])
                ->count();

            // Calculate documents authored
            $documentsAuthored = ProjectDocument::where('uploaded_by', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Calculate projects owned
            $projectsOwned = Project::where(function ($q) use ($user) {
                $firstName = explode(' ', $user->name)[0];
                $q->where('owner_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('lead', 'like', "%{$firstName}%");
            })
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('created_at', '<=', $endDate)
                                ->where(function ($q3) use ($startDate) {
                                    $q3->whereNull('actual_end_date')
                                        ->orWhere('actual_end_date', '>=', $startDate);
                                });
                        });
                })
                ->count();

            // Calculate projects contributed
            $projectsContributed = Project::whereHas('staff', fn($q) => $q->where('user_id', $user->id))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Calculate decisions made
            $decisionsMade = 0;
            try {
                if (class_exists('App\Models\Decision')) {
                    $decisionsMade = \App\Models\Decision::where('made_by', $user->id)
                        ->whereBetween('decision_date', [$startDate, $endDate])
                        ->count();
                }
            } catch (\Exception $e) {
                // Decision model might not exist
            }

            // Calculate grant deliverables
            $grantDeliverables = 0;
            try {
                if (class_exists(\App\Models\ReportingRequirement::class) && Schema::hasTable('reporting_requirements')) {
                    $grantDeliverables = \App\Models\ReportingRequirement::where('status', 'submitted')
                        ->whereBetween('submitted_at', [$startDate, $endDate])
                        ->count();
                }
            } catch (\Exception $e) {
                // ReportingRequirement table or column might not exist
            }

            // Calculate grant reports
            $grantReports = 0;
            try {
                if (class_exists(\App\Models\ReportingRequirement::class) && Schema::hasTable('reporting_requirements')) {
                    $grantReports = \App\Models\ReportingRequirement::whereIn('type', ['progress_report', 'narrative_report', 'financial_report', 'final_report'])
                        ->where('status', 'submitted')
                        ->whereBetween('submitted_at', [$startDate, $endDate])
                        ->count();
                }
            } catch (\Exception $e) {
                // ReportingRequirement table or column might not exist
            }

            // Calculate manual accomplishments added
            $accomplishmentsAdded = Accomplishment::where('user_id', $user->id)
                ->where('is_recognition', false)
                ->whereBetween('date', [$startDate, $endDate])
                ->count();

            // Calculate recognition received
            $recognitionReceived = Accomplishment::where('user_id', $user->id)
                ->where('is_recognition', true)
                ->whereBetween('date', [$startDate, $endDate])
                ->count();

            // Calculate recognition given
            $recognitionGiven = Accomplishment::where('added_by', $user->id)
                ->where('is_recognition', true)
                ->where('user_id', '!=', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->count();

            // Calculate impact score
            $impactScore = ($meetingsOrganized * UserActivityStats::IMPACT_WEIGHTS['meetings_organized'])
                + ($meetingsAttended * UserActivityStats::IMPACT_WEIGHTS['meetings_attended'])
                + ($documentsAuthored * UserActivityStats::IMPACT_WEIGHTS['documents_authored'])
                + ($projectsOwned * UserActivityStats::IMPACT_WEIGHTS['projects_owned'])
                + ($projectsContributed * UserActivityStats::IMPACT_WEIGHTS['projects_contributed'])
                + ($decisionsMade * UserActivityStats::IMPACT_WEIGHTS['decisions_made'])
                + ($grantDeliverables * UserActivityStats::IMPACT_WEIGHTS['grant_deliverables'])
                + ($grantReports * UserActivityStats::IMPACT_WEIGHTS['grant_reports'])
                + ($accomplishmentsAdded * UserActivityStats::IMPACT_WEIGHTS['accomplishments_added'])
                + ($recognitionReceived * UserActivityStats::IMPACT_WEIGHTS['recognition_received'])
                + ($recognitionGiven * UserActivityStats::IMPACT_WEIGHTS['recognition_given']);

            // Store results
            $stats = UserActivityStats::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'period_start' => $startDate->toDateString(),
                    'period_end' => $endDate->toDateString(),
                ],
                [
                    'meetings_attended' => $meetingsAttended,
                    'meetings_organized' => $meetingsOrganized,
                    'documents_authored' => $documentsAuthored,
                    'projects_owned' => $projectsOwned,
                    'projects_contributed' => $projectsContributed,
                    'decisions_made' => $decisionsMade,
                    'grant_deliverables' => $grantDeliverables,
                    'grant_reports' => $grantReports,
                    'accomplishments_added' => $accomplishmentsAdded,
                    'recognition_received' => $recognitionReceived,
                    'recognition_given' => $recognitionGiven,
                    'total_impact_score' => round($impactScore, 2),
                    'last_calculated_at' => now(),
                ]
            );

            Log::info("CalculateUserAccomplishments: Calculated stats for User {$user->id} ({$startDate->toDateString()} to {$endDate->toDateString()})");

            return $stats;
        } catch (\Exception $e) {
            Log::error('CalculateUserAccomplishments error: ' . $e->getMessage(), [
                'user_id' => $this->userId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Return empty stats on error
            return new UserActivityStats([
                'user_id' => $this->userId,
                'period_start' => $this->startDate,
                'period_end' => $this->endDate,
            ]);
        }
    }

    /**
     * Calculate stats for the current week
     */
    public static function forCurrentWeek(int $userId): self
    {
        return new self(
            $userId,
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString()
        );
    }

    /**
     * Calculate stats for the current month
     */
    public static function forCurrentMonth(int $userId): self
    {
        return new self(
            $userId,
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );
    }

    /**
     * Calculate stats for the current quarter
     */
    public static function forCurrentQuarter(int $userId): self
    {
        return new self(
            $userId,
            now()->startOfQuarter()->toDateString(),
            now()->endOfQuarter()->toDateString()
        );
    }

    /**
     * Calculate stats for the current year
     */
    public static function forCurrentYear(int $userId): self
    {
        return new self(
            $userId,
            now()->startOfYear()->toDateString(),
            now()->endOfYear()->toDateString()
        );
    }
}
