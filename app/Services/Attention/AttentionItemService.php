<?php

namespace App\Services\Attention;

use App\Models\Action;
use App\Models\AgentSuggestion;
use App\Models\Meeting;
use App\Models\ProjectTask;
use App\Models\ReportingRequirement;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttentionItemService
{
    /**
     * Build the read-only attention queue for a staff member.
     *
     * @return Collection<int, array{
     *     id:string,
     *     category:string,
     *     bucket:string,
     *     priority:string,
     *     title:string,
     *     summary:string,
     *     context:?string,
     *     due_label:?string,
     *     due_at:?string,
     *     url:string,
     *     action_label:string,
     *     source_type:string,
     *     source_id:int
     * }>
     */
    public function forUser(User $user): Collection
    {
        return collect()
            ->concat($this->actions($user))
            ->concat($this->projectTasks($user))
            ->concat($this->meetingPreparation($user))
            ->concat($this->meetingNotes($user))
            ->concat($this->fundingDeadlines($user))
            ->concat($this->agentApprovals($user))
            ->sortBy(fn (array $item) => sprintf(
                '%02d-%02d-%s',
                $this->bucketRank($item['bucket']),
                $this->priorityRank($item['priority']),
                $item['due_at'] ?? '9999-12-31'
            ))
            ->values();
    }

    protected function actions(User $user): Collection
    {
        return Action::query()
            ->with(['project:id,name', 'meeting:id,title'])
            ->where('assigned_to', $user->id)
            ->pending()
            ->where(function (Builder $query): void {
                $query->whereDate('due_date', '<=', today()->addDays(7))
                    ->orWhere('priority', Action::PRIORITY_HIGH);
            })
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(40)
            ->get()
            ->map(function (Action $action): array {
                $context = $action->project?->name ?: $action->meeting?->title;
                $url = $action->meeting
                    ? route('meetings.show', $action->meeting)
                    : ($action->project ? route('projects.show', $action->project) : route('dashboard'));

                return $this->item(
                    id: 'action-'.$action->id,
                    category: 'tasks',
                    bucket: $this->taskBucket($action->due_date, $action->priority),
                    priority: $this->taskPriority($action->due_date, $action->priority),
                    title: $action->display_title,
                    summary: $action->notes ?: 'An assigned action is ready for your attention.',
                    context: $context,
                    dueAt: $action->due_date,
                    url: $url,
                    actionLabel: $action->meeting ? 'Open meeting' : ($action->project ? 'Open project' : 'Open WRKspace'),
                    sourceType: 'action',
                    sourceId: $action->id,
                );
            });
    }

    protected function projectTasks(User $user): Collection
    {
        return ProjectTask::query()
            ->with('project:id,name')
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where(function (Builder $query): void {
                $query->whereDate('due_date', '<=', today()->addDays(7))
                    ->orWhere('priority', 'high');
            })
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(40)
            ->get()
            ->map(fn (ProjectTask $task): array => $this->item(
                id: 'project-task-'.$task->id,
                category: 'tasks',
                bucket: $this->taskBucket($task->due_date, $task->priority),
                priority: $this->taskPriority($task->due_date, $task->priority),
                title: $task->title,
                summary: $task->description ?: 'A project task is ready for your attention.',
                context: $task->project?->name,
                dueAt: $task->due_date,
                url: route('projects.show', $task->project),
                actionLabel: 'Open project',
                sourceType: 'project_task',
                sourceId: $task->id,
            ));
    }

    protected function meetingPreparation(User $user): Collection
    {
        return $this->meetingsForUser($user)
            ->with('organizations:id,name')
            ->whereBetween('meeting_date', [today(), today()->addDays(7)])
            ->where(function (Builder $query): void {
                $query->whereNull('prep_notes')->orWhere('prep_notes', '');
            })
            ->where('status', '!=', Meeting::STATUS_COMPLETE)
            ->orderBy('meeting_date')
            ->orderBy('meeting_time')
            ->limit(30)
            ->get()
            ->map(function (Meeting $meeting): array {
                $daysUntil = today()->diffInDays($meeting->meeting_date, false);
                $priority = $daysUntil <= 0 ? 'high' : ($daysUntil <= 2 ? 'medium' : 'low');
                $context = $meeting->organizations->pluck('name')->take(2)->implode(', ');

                return $this->item(
                    id: 'meeting-prep-'.$meeting->id,
                    category: 'meetings',
                    bucket: $daysUntil <= 2 ? 'now' : 'soon',
                    priority: $priority,
                    title: 'Prepare for '.$meeting->title,
                    summary: 'This upcoming meeting does not have preparation notes yet.',
                    context: $context !== '' ? $context : null,
                    dueAt: $meeting->meeting_date,
                    url: route('meetings.show', $meeting),
                    actionLabel: 'Prepare meeting',
                    sourceType: 'meeting',
                    sourceId: $meeting->id,
                    dueLabel: $this->meetingLabel($meeting),
                );
            });
    }

    protected function meetingNotes(User $user): Collection
    {
        return $this->meetingsForUser($user)
            ->with('organizations:id,name')
            ->whereDate('meeting_date', '>=', today()->subDays(14))
            ->whereDate('meeting_date', '<', today())
            ->where(function (Builder $query): void {
                $query->whereNull('raw_notes')->orWhere('raw_notes', '');
            })
            ->orderByDesc('meeting_date')
            ->limit(30)
            ->get()
            ->map(function (Meeting $meeting): array {
                $context = $meeting->organizations->pluck('name')->take(2)->implode(', ');

                return $this->item(
                    id: 'meeting-notes-'.$meeting->id,
                    category: 'meetings',
                    bucket: 'now',
                    priority: $meeting->meeting_date->lt(today()->subDays(2)) ? 'high' : 'medium',
                    title: 'Capture notes from '.$meeting->title,
                    summary: 'This recent meeting still needs notes, decisions, or follow-up captured.',
                    context: $context !== '' ? $context : null,
                    dueAt: $meeting->meeting_date,
                    url: route('meetings.show', $meeting),
                    actionLabel: 'Add meeting notes',
                    sourceType: 'meeting',
                    sourceId: $meeting->id,
                    dueLabel: $meeting->meeting_date->format('M j').' meeting',
                );
            });
    }

    protected function fundingDeadlines(User $user): Collection
    {
        return ReportingRequirement::query()
            ->with(['grant:id,organization_id,name,visibility', 'grant.funder:id,name'])
            ->where('status', '!=', 'submitted')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today()->addDays(45))
            ->whereHas('grant', fn (Builder $query) => $query->visibleTo($user))
            ->orderBy('due_date')
            ->limit(40)
            ->get()
            ->map(function (ReportingRequirement $requirement): array {
                $daysUntil = today()->diffInDays($requirement->due_date, false);

                return $this->item(
                    id: 'reporting-requirement-'.$requirement->id,
                    category: 'funding',
                    bucket: $daysUntil <= 14 ? 'now' : 'soon',
                    priority: $daysUntil < 0 ? 'critical' : ($daysUntil <= 7 ? 'high' : ($daysUntil <= 14 ? 'medium' : 'low')),
                    title: $requirement->name,
                    summary: 'A grant reporting requirement is approaching or overdue.',
                    context: collect([$requirement->grant?->name, $requirement->grant?->funder?->name])->filter()->implode(' · '),
                    dueAt: $requirement->due_date,
                    url: route('grants.show', $requirement->grant),
                    actionLabel: 'Open grant',
                    sourceType: 'reporting_requirement',
                    sourceId: $requirement->id,
                );
            });
    }

    protected function agentApprovals(User $user): Collection
    {
        return AgentSuggestion::query()
            ->with('agent:id,name,created_by,owner_user_id,staffer_id')
            ->where('approval_status', AgentSuggestion::STATUS_PENDING)
            ->when(! $user->isManagement(), function (Builder $query) use ($user): void {
                $query->whereHas('agent', function (Builder $agentQuery) use ($user): void {
                    $agentQuery->where('created_by', $user->id)
                        ->orWhere('owner_user_id', $user->id)
                        ->orWhere('staffer_id', $user->id);
                });
            })
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(fn (AgentSuggestion $suggestion): array => $this->item(
                id: 'agent-suggestion-'.$suggestion->id,
                category: 'approvals',
                bucket: 'review',
                priority: match ($suggestion->risk_level) {
                    'high' => 'critical',
                    'medium' => 'high',
                    default => 'medium',
                },
                title: $suggestion->title ?: 'Agent suggestion needs review',
                summary: $suggestion->reasoning ?: 'An agent has prepared a proposed action for review.',
                context: $suggestion->agent?->name,
                dueAt: $suggestion->created_at,
                url: route('intelligence.index'),
                actionLabel: 'Review suggestion',
                sourceType: 'agent_suggestion',
                sourceId: $suggestion->id,
                dueLabel: $suggestion->created_at?->diffForHumans(),
            ));
    }

    protected function meetingsForUser(User $user): Builder
    {
        return Meeting::query()->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id)
                ->orWhereHas('teamMembers', fn (Builder $teamQuery) => $teamQuery->where('users.id', $user->id));
        });
    }

    protected function taskBucket(?CarbonInterface $dueAt, ?string $priority): string
    {
        if ($dueAt?->lte(today()) || ($dueAt === null && $priority === 'high')) {
            return 'now';
        }

        return 'soon';
    }

    protected function taskPriority(?CarbonInterface $dueAt, ?string $priority): string
    {
        if ($dueAt?->lt(today())) {
            return 'critical';
        }
        if ($dueAt?->isToday() || $priority === 'high') {
            return 'high';
        }

        return 'medium';
    }

    protected function meetingLabel(Meeting $meeting): string
    {
        $date = $meeting->meeting_date->isToday()
            ? 'Today'
            : ($meeting->meeting_date->isTomorrow() ? 'Tomorrow' : $meeting->meeting_date->format('M j'));

        if ($meeting->meeting_time) {
            return $date.' · '.date('g:i A', strtotime((string) $meeting->meeting_time));
        }

        return $date;
    }

    protected function dueLabel(?CarbonInterface $dueAt): ?string
    {
        if (! $dueAt) {
            return null;
        }
        if ($dueAt->lt(today())) {
            $days = (int) $dueAt->diffInDays(today());

            return $days.' '.str('day')->plural($days).' overdue';
        }
        if ($dueAt->isToday()) {
            return 'Due today';
        }
        if ($dueAt->isTomorrow()) {
            return 'Due tomorrow';
        }

        return 'Due '.$dueAt->format('M j');
    }

    protected function item(
        string $id,
        string $category,
        string $bucket,
        string $priority,
        string $title,
        string $summary,
        ?string $context,
        ?CarbonInterface $dueAt,
        string $url,
        string $actionLabel,
        string $sourceType,
        int $sourceId,
        ?string $dueLabel = null,
    ): array {
        return [
            'id' => $id,
            'category' => $category,
            'bucket' => $bucket,
            'priority' => $priority,
            'title' => $title,
            'summary' => $summary,
            'context' => $context ?: null,
            'due_label' => $dueLabel ?? $this->dueLabel($dueAt),
            'due_at' => $dueAt?->toIso8601String(),
            'url' => $url,
            'action_label' => $actionLabel,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ];
    }

    protected function bucketRank(string $bucket): int
    {
        return match ($bucket) {
            'now' => 0,
            'review' => 1,
            default => 2,
        };
    }

    protected function priorityRank(string $priority): int
    {
        return match ($priority) {
            'critical' => 0,
            'high' => 1,
            'medium' => 2,
            default => 3,
        };
    }
}
