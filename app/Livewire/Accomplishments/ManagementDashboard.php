<?php

namespace App\Livewire\Accomplishments;

use App\Jobs\CalculateUserAccomplishments;
use App\Models\Accomplishment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
            $job = new CalculateUserAccomplishments($user->id, $startDate, $endDate);
            $stats = $job->handle();

            $recentWins = Accomplishment::where('user_id', $user->id)
                ->where('date', '>=', now()->subDays(14))
                ->orderBy('date', 'desc')
                ->limit(3)
                ->get(['id', 'title', 'type', 'date']);

            $memberStats[] = [
                'user' => $user,
                'stats' => $stats,
                'recent_wins' => $recentWins,
            ];

            $totalImpact += $stats->total_impact_score;
            $totalMeetings += $stats->meetings_attended;
            $totalDocuments += $stats->documents_authored;
            $totalProjects += $stats->projects_owned + $stats->projects_contributed;
            $totalRecognition += $stats->recognition_received + $stats->recognition_given;
        }

        // Calculate team totals
        $this->teamStats = [
            'total_team_members' => count($users),
            'total_meetings' => $totalMeetings,
            'total_documents' => $totalDocuments,
            'total_projects' => $totalProjects,
            'total_recognition' => $totalRecognition,
            'average_impact_score' => count($users) > 0 ? round($totalImpact / count($users), 1) : 0,
            'member_stats' => collect($memberStats)->sortByDesc(fn ($m) => $m['stats']->total_impact_score)->values()->all(),
        ];

        // Get top performers (top 3 by impact score)
        $this->topPerformers = collect($memberStats)
            ->sortByDesc(fn ($m) => $m['stats']->total_impact_score)
            ->take(3)
            ->values()
            ->all();
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
        return Accomplishment::with(['user', 'addedBy'])
            ->whereIn('visibility', ['team', 'organizational'])
            ->where('date', '>=', now()->subDays(14))
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.accomplishments.management-dashboard', [
            'recentAccomplishments' => $this->recentAccomplishments,
        ]);
    }
}

