<?php

namespace App\Livewire\Intelligence;

use App\Models\Grant;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Intelligence')]
class IntelligenceIndex extends Component
{
    public array $agents = [];

    public array $insights = [];

    public string $generatedAt = '';

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->agents = $this->buildAgentCouncil();
        $this->insights = $this->buildInsightStream();
        $this->generatedAt = now()->format('M j, Y g:i A');
    }

    protected function buildAgentCouncil(): array
    {
        $user = Auth::user();
        $today = today();

        $overdueTasks = ProjectTask::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->count();

        $meetingsNeedingNotes = Meeting::query()->needsNotes()->count();

        $projectsNearTarget = Project::query()
            ->whereIn('status', ['planning', 'active'])
            ->whereNotNull('target_end_date')
            ->whereDate('target_end_date', '<=', $today->copy()->addDays(14))
            ->count();

        $fundingDeadlines = Grant::query()
            ->visibleTo($user)
            ->active()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $today->copy()->addDays(45))
            ->count();

        $tripLogisticsGaps = Trip::query()
            ->upcoming()
            ->whereDoesntHave('lodging')
            ->count();

        return [
            [
                'name' => 'Execution Sentinel',
                'status' => $overdueTasks > 0 ? 'watch' : 'active',
                'monitoring' => 'Task deadlines, ownership, and delivery risk',
                'signal_count' => $overdueTasks,
                'next_focus' => $overdueTasks > 0 ? 'Clear overdue tasks' : 'No overdue delivery blockers',
                'last_run_human' => now()->subMinutes(6)->diffForHumans(),
            ],
            [
                'name' => 'Coordination Analyst',
                'status' => $meetingsNeedingNotes > 0 ? 'watch' : 'active',
                'monitoring' => 'Meeting follow-through and missing context',
                'signal_count' => $meetingsNeedingNotes,
                'next_focus' => $meetingsNeedingNotes > 0 ? 'Collect notes from past meetings' : 'Meeting notes are current',
                'last_run_human' => now()->subMinutes(9)->diffForHumans(),
            ],
            [
                'name' => 'Program Horizon',
                'status' => $projectsNearTarget > 0 ? 'watch' : 'active',
                'monitoring' => 'Project timeline compression and sequencing',
                'signal_count' => $projectsNearTarget,
                'next_focus' => $projectsNearTarget > 0 ? 'Review near-term project targets' : 'No immediate timeline conflicts',
                'last_run_human' => now()->subMinutes(11)->diffForHumans(),
            ],
            [
                'name' => 'Funding Radar',
                'status' => $fundingDeadlines > 0 ? 'watch' : 'active',
                'monitoring' => 'Active grant windows and reporting pressure',
                'signal_count' => $fundingDeadlines,
                'next_focus' => $fundingDeadlines > 0 ? 'Prioritize expiring grants' : 'Funding cycle looks stable',
                'last_run_human' => now()->subMinutes(14)->diffForHumans(),
            ],
            [
                'name' => 'Travel Watch',
                'status' => $tripLogisticsGaps > 0 ? 'watch' : 'active',
                'monitoring' => 'Upcoming travel with missing logistics',
                'signal_count' => $tripLogisticsGaps,
                'next_focus' => $tripLogisticsGaps > 0 ? 'Resolve lodging gaps for upcoming trips' : 'Travel logistics look complete',
                'last_run_human' => now()->subMinutes(18)->diffForHumans(),
            ],
        ];
    }

    protected function buildInsightStream(): array
    {
        $user = Auth::user();
        $today = today();
        $insights = [];

        $overdueTasks = ProjectTask::query()
            ->with('project:id,name')
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->orderBy('due_date')
            ->limit(3)
            ->get();

        if ($overdueTasks->isNotEmpty()) {
            $insights[] = [
                'severity' => 'high',
                'agent' => 'Execution Sentinel',
                'title' => $overdueTasks->count().' overdue tasks are currently blocking delivery',
                'summary' => 'Overdue work is clustering in active projects. Resolving these first will reduce downstream slippage.',
                'evidence' => $overdueTasks->map(function (ProjectTask $task): string {
                    $projectName = $task->project?->name ?? 'Unlinked project';
                    $due = $task->due_date ? Carbon::parse($task->due_date)->format('M j') : 'No due date';

                    return "{$task->title} ({$projectName}, due {$due})";
                })->all(),
                'confidence' => 'high',
                'action_label' => 'Review tasks in Workspace',
                'action_url' => route('dashboard'),
            ];
        }

        $meetingsNeedingNotes = Meeting::query()
            ->needsNotes()
            ->orderBy('meeting_date', 'desc')
            ->limit(3)
            ->get(['id', 'title', 'meeting_date']);

        if ($meetingsNeedingNotes->isNotEmpty()) {
            $insights[] = [
                'severity' => 'medium',
                'agent' => 'Coordination Analyst',
                'title' => 'Meeting intelligence is incomplete for '.$meetingsNeedingNotes->count().' recent meetings',
                'summary' => 'Missing notes reduce context quality for follow-ups, project summaries, and agent recommendations.',
                'evidence' => $meetingsNeedingNotes->map(function (Meeting $meeting): string {
                    $date = $meeting->meeting_date ? Carbon::parse($meeting->meeting_date)->format('M j') : 'Unknown date';

                    return "{$meeting->title} ({$date})";
                })->all(),
                'confidence' => 'high',
                'action_label' => 'Open meetings',
                'action_url' => route('meetings.index'),
            ];
        }

        $projectsNearTarget = Project::query()
            ->whereIn('status', ['planning', 'active'])
            ->whereNotNull('target_end_date')
            ->whereDate('target_end_date', '<=', $today->copy()->addDays(14))
            ->orderBy('target_end_date')
            ->limit(3)
            ->get(['id', 'name', 'target_end_date', 'status']);

        if ($projectsNearTarget->isNotEmpty()) {
            $insights[] = [
                'severity' => 'medium',
                'agent' => 'Program Horizon',
                'title' => 'Project timelines are tightening over the next two weeks',
                'summary' => 'A quick sequence review now can prevent deadline collisions across projects and dependencies.',
                'evidence' => $projectsNearTarget->map(function (Project $project): string {
                    $targetDate = $project->target_end_date ? Carbon::parse($project->target_end_date)->format('M j') : 'No target date';

                    return "{$project->name} ({$project->status}, target {$targetDate})";
                })->all(),
                'confidence' => 'medium',
                'action_label' => 'Open projects',
                'action_url' => route('projects.index'),
            ];
        }

        $fundingDeadlines = Grant::query()
            ->visibleTo($user)
            ->active()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', $today->copy()->addDays(45))
            ->orderBy('end_date')
            ->limit(3)
            ->get(['id', 'name', 'end_date']);

        if ($fundingDeadlines->isNotEmpty()) {
            $insights[] = [
                'severity' => 'medium',
                'agent' => 'Funding Radar',
                'title' => $fundingDeadlines->count().' active funding windows are approaching end dates',
                'summary' => 'This is a good window to align reporting, documentation, and renewal strategy.',
                'evidence' => $fundingDeadlines->map(function (Grant $grant): string {
                    $endDate = $grant->end_date ? Carbon::parse($grant->end_date)->format('M j') : 'No end date';

                    return "{$grant->name} (ends {$endDate})";
                })->all(),
                'confidence' => 'medium',
                'action_label' => 'Open funding',
                'action_url' => route('funding.index'),
            ];
        }

        $upcomingTripsWithoutLodging = Trip::query()
            ->upcoming()
            ->whereDoesntHave('lodging')
            ->orderBy('start_date')
            ->limit(3)
            ->get(['id', 'name', 'start_date']);

        if ($upcomingTripsWithoutLodging->isNotEmpty()) {
            $insights[] = [
                'severity' => 'low',
                'agent' => 'Travel Watch',
                'title' => 'Some upcoming trips still need lodging details',
                'summary' => 'Flagging these now avoids late-booking cost and coordination overhead.',
                'evidence' => $upcomingTripsWithoutLodging->map(function (Trip $trip): string {
                    $startDate = $trip->start_date ? Carbon::parse($trip->start_date)->format('M j') : 'Unknown';

                    return "{$trip->name} (starts {$startDate})";
                })->all(),
                'confidence' => 'medium',
                'action_label' => 'Open travel',
                'action_url' => route('travel.index'),
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'severity' => 'low',
                'agent' => 'WRK Intelligence',
                'title' => 'No urgent cross-domain risks detected right now',
                'summary' => 'Agents are still watching for timeline conflicts, overdue deliverables, and travel/funding pressure points.',
                'evidence' => ['Signals look stable across projects, meetings, travel, and funding.'],
                'confidence' => 'high',
                'action_label' => 'Go to workspace',
                'action_url' => route('dashboard'),
            ];
        }

        return $insights;
    }

    public function render()
    {
        return view('livewire.intelligence.intelligence-index', [
            'activeAgentCount' => collect($this->agents)->where('status', 'active')->count(),
            'watchAgentCount' => collect($this->agents)->where('status', 'watch')->count(),
        ]);
    }
}
