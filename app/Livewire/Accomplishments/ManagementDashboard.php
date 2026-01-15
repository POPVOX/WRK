<?php

namespace App\Livewire\Accomplishments;

use App\Jobs\CalculateUserAccomplishments;
use App\Models\Accomplishment;
use App\Models\User;
use App\Models\UserActivityStats;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Team Dashboard')]
class ManagementDashboard extends Component
{
    #[Url]
    public string $period = 'month';

    public ?int $selectedUserId = null;

    public array $teamStats = [];

    public array $topPerformers = [];

    public bool $hasMigrationError = false;

    public string $migrationErrorMessage = '';

    public function mount(): void
    {
        // Check if user has permission (admin or manager)
        if (! Auth::user()->isAdmin()) {
            abort(403, 'You do not have permission to access this page.');
        }

        $this->loadData();
    }

    protected function loadData(): void
    {
        // Check if required tables exist
        if (! $this->checkRequiredTables()) {
            return;
        }

        try {
            [$startDate, $endDate] = $this->getPeriodDates();

            // Get all users
            $users = User::orderBy('name')->get();

            // Calculate stats for each user
            $memberStats = [];
            $totalImpact = 0;
            $totalMeetings = 0;
            $totalDocuments = 0;
            $totalProjects = 0;
            $totalRecognition = 0;

            foreach ($users as $user) {
                $stats = $this->calculateUserStats($user->id, $startDate, $endDate);

                $recentWins = $this->getRecentWins($user->id);

                $memberStats[] = [
                    'user' => $user,
                    'stats' => $stats,
                    'recent_wins' => $recentWins,
                ];

                $totalImpact += $stats->total_impact_score ?? 0;
                $totalMeetings += $stats->meetings_attended ?? 0;
                $totalDocuments += $stats->documents_authored ?? 0;
                $totalProjects += ($stats->projects_owned ?? 0) + ($stats->projects_contributed ?? 0);
                $totalRecognition += ($stats->recognition_received ?? 0) + ($stats->recognition_given ?? 0);
            }

            // Calculate team totals
            $this->teamStats = [
                'total_team_members' => count($users),
                'total_meetings' => $totalMeetings,
                'total_documents' => $totalDocuments,
                'total_projects' => $totalProjects,
                'total_recognition' => $totalRecognition,
                'average_impact_score' => count($users) > 0 ? round($totalImpact / count($users), 1) : 0,
                'member_stats' => collect($memberStats)->sortByDesc(fn ($m) => $m['stats']->total_impact_score ?? 0)->values()->all(),
            ];

            // Get top performers (top 3 by impact score)
            $this->topPerformers = collect($memberStats)
                ->sortByDesc(fn ($m) => $m['stats']->total_impact_score ?? 0)
                ->take(3)
                ->values()
                ->all();
        } catch (\Exception $e) {
            Log::error('ManagementDashboard loadData error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->hasMigrationError = true;
            $this->migrationErrorMessage = 'An error occurred while loading team data. Please ensure all database migrations have been run.';
            $this->setEmptyStats();
        }
    }

    /**
     * Check if required database tables exist
     */
    protected function checkRequiredTables(): bool
    {
        $requiredTables = ['user_activity_stats', 'accomplishments'];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->hasMigrationError = true;
                $this->migrationErrorMessage = "Required database table '{$table}' is missing. Please run: php artisan migrate";
                $this->setEmptyStats();

                return false;
            }
        }

        return true;
    }

    /**
     * Calculate user stats with error handling
     */
    protected function calculateUserStats(int $userId, string $startDate, string $endDate): UserActivityStats
    {
        try {
            $job = new CalculateUserAccomplishments($userId, $startDate, $endDate);

            return $job->handle();
        } catch (\Exception $e) {
            Log::warning("Failed to calculate stats for user {$userId}: ".$e->getMessage());

            // Return empty stats object
            return new UserActivityStats([
                'user_id' => $userId,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'meetings_attended' => 0,
                'meetings_organized' => 0,
                'documents_authored' => 0,
                'projects_owned' => 0,
                'projects_contributed' => 0,
                'decisions_made' => 0,
                'grant_deliverables' => 0,
                'grant_reports' => 0,
                'accomplishments_added' => 0,
                'recognition_received' => 0,
                'recognition_given' => 0,
                'total_impact_score' => 0,
            ]);
        }
    }

    /**
     * Get recent wins for a user with error handling
     */
    protected function getRecentWins(int $userId)
    {
        try {
            return Accomplishment::where('user_id', $userId)
                ->where('date', '>=', now()->subDays(14))
                ->orderBy('date', 'desc')
                ->limit(3)
                ->get(['id', 'title', 'type', 'date']);
        } catch (\Exception $e) {
            Log::warning("Failed to get recent wins for user {$userId}: ".$e->getMessage());

            return collect();
        }
    }

    /**
     * Set empty stats when there's an error
     */
    protected function setEmptyStats(): void
    {
        $this->teamStats = [
            'total_team_members' => 0,
            'total_meetings' => 0,
            'total_documents' => 0,
            'total_projects' => 0,
            'total_recognition' => 0,
            'average_impact_score' => 0,
            'member_stats' => [],
        ];
        $this->topPerformers = [];
    }

    protected function getPeriodDates(): array
    {
        return match ($this->period) {
            'week' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'quarter' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
            'year' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->loadData();
    }

    public function viewUser(int $userId): void
    {
        $this->redirect(route('accomplishments.user', ['userId' => $userId]));
    }

    public function getRecentAccomplishmentsProperty()
    {
        if ($this->hasMigrationError) {
            return collect();
        }

        try {
            return Accomplishment::with(['user', 'addedBy'])
                ->whereIn('visibility', ['team', 'organizational'])
                ->where('date', '>=', now()->subDays(14))
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Failed to get recent accomplishments: '.$e->getMessage());

            return collect();
        }
    }

    public function render()
    {
        return view('livewire.accomplishments.management-dashboard', [
            'recentAccomplishments' => $this->recentAccomplishments,
        ]);
    }
}

