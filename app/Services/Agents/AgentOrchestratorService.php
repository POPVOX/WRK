<?php

namespace App\Services\Agents;

use App\Models\Action;
use App\Models\Agent;
use App\Models\AgentMessage;
use App\Models\AgentPermission;
use App\Models\AgentRun;
use App\Models\AgentSuggestion;
use App\Models\AgentSuggestionSource;
use App\Models\AgentThread;
use App\Models\Grant;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AgentOrchestratorService
{
    public function direct(Agent $agent, User $actor, string $message): array
    {
        $cleanMessage = trim($message);
        if ($cleanMessage === '') {
            throw new RuntimeException('Directive cannot be empty.');
        }

        $thread = AgentThread::firstOrCreate(
            ['agent_id' => $agent->id, 'user_id' => $actor->id],
            ['title' => $agent->name.' Thread']
        );

        AgentMessage::create([
            'thread_id' => $thread->id,
            'user_id' => $actor->id,
            'role' => 'user',
            'content' => $cleanMessage,
            'meta' => ['source' => 'intelligence'],
        ]);

        $run = AgentRun::create([
            'agent_id' => $agent->id,
            'thread_id' => $thread->id,
            'requested_by' => $actor->id,
            'status' => 'running',
            'directive' => $cleanMessage,
            'started_at' => now(),
        ]);

        try {
            $entityIndex = $this->buildEntityIndex();
            $lines = $this->extractDirectiveLines($cleanMessage);
            $reasoning = [];
            $alternatives = [];
            $createdSuggestions = [];

            foreach ($lines as $line) {
                $suggestion = $this->buildSuggestion($agent, $run, $thread, $line, $entityIndex);
                $createdSuggestions[] = $suggestion;

                $reasoning[] = $suggestion['reasoning'];
                if (! empty($suggestion['alternative'])) {
                    $alternatives[] = $suggestion['alternative'];
                }
            }

            $suggestionModels = collect($createdSuggestions)->map(function (array $entry) use ($agent, $run, $thread): AgentSuggestion {
                $model = AgentSuggestion::create([
                    'agent_id' => $agent->id,
                    'run_id' => $run->id,
                    'thread_id' => $thread->id,
                    'suggestion_type' => $entry['type'],
                    'title' => $entry['title'],
                    'reasoning' => $entry['reasoning'],
                    'payload' => $entry['payload'],
                    'risk_level' => $entry['risk_level'],
                    'approval_status' => AgentSuggestion::STATUS_PENDING,
                ]);

                foreach ($entry['sources'] as $source) {
                    AgentSuggestionSource::create([
                        'suggestion_id' => $model->id,
                        'run_id' => $run->id,
                        'source_type' => $source['source_type'],
                        'source_id' => (string) ($source['source_id'] ?? ''),
                        'source_title' => $source['source_title'] ?? null,
                        'excerpt' => $source['excerpt'] ?? null,
                        'confidence' => $source['confidence'] ?? null,
                        'source_url' => $source['source_url'] ?? null,
                    ]);
                }

                return $model;
            });

            $autoExecuted = [];
            foreach ($suggestionModels as $suggestionModel) {
                if (! $this->shouldAutonomouslyExecute($agent, $suggestionModel)) {
                    continue;
                }

                $executionSummary = $this->approveSuggestion($suggestionModel, $actor, null, true);
                $autoExecuted[] = [
                    'id' => $suggestionModel->id,
                    'summary' => $executionSummary,
                ];
            }

            $suggestionModels = $suggestionModels->map(fn (AgentSuggestion $suggestion) => $suggestion->fresh(['sources']));

            $response = $this->buildAssistantResponse($agent, $suggestionModels->all(), count($autoExecuted));

            AgentMessage::create([
                'thread_id' => $thread->id,
                'role' => 'assistant',
                'content' => $response,
                'meta' => [
                    'run_id' => $run->id,
                    'suggestion_ids' => $suggestionModels->pluck('id')->all(),
                    'auto_executed_count' => count($autoExecuted),
                ],
            ]);

            $run->update([
                'status' => 'completed',
                'result_summary' => $this->summarizeSuggestionMix($suggestionModels->all()),
                'reasoning_chain' => array_values(array_unique($reasoning)),
                'alternatives_considered' => array_values(array_unique($alternatives)),
                'completed_at' => now(),
            ]);

            $agent->update(['last_directed_at' => now()]);

            return [
                'thread' => $thread,
                'run' => $run->fresh(),
                'response' => $response,
                'suggestions' => $suggestionModels,
                'auto_executed' => $autoExecuted,
            ];
        } catch (\Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 2000),
                'completed_at' => now(),
            ]);

            AgentMessage::create([
                'thread_id' => $thread->id,
                'role' => 'assistant',
                'content' => 'I could not process that directive cleanly. Please try rephrasing with one item per line.',
                'meta' => ['run_id' => $run->id, 'error' => true],
            ]);

            throw $exception;
        }
    }

    public function approveSuggestion(AgentSuggestion $suggestion, User $reviewer, ?string $overrideTitle = null, bool $force = false): string
    {
        return DB::transaction(function () use ($suggestion, $reviewer, $overrideTitle, $force): string {
            $suggestion->refresh();
            if ($suggestion->approval_status !== AgentSuggestion::STATUS_PENDING) {
                throw new RuntimeException('Suggestion is no longer pending.');
            }

            if (! $force) {
                $this->assertReviewerCanApprove($suggestion, $reviewer);
            }

            $status = $overrideTitle ? AgentSuggestion::STATUS_MODIFIED : AgentSuggestion::STATUS_APPROVED;
            if ($overrideTitle) {
                $suggestion->title = trim($overrideTitle);
            }

            $executionSummary = $this->executeSuggestion($suggestion, $reviewer);

            $suggestion->update([
                'title' => $suggestion->title,
                'approval_status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'executed_at' => now(),
                'review_notes' => $executionSummary,
            ]);

            if ($suggestion->thread_id) {
                AgentMessage::create([
                    'thread_id' => $suggestion->thread_id,
                    'user_id' => $reviewer->id,
                    'role' => 'assistant',
                    'content' => 'Executed: '.$executionSummary,
                    'meta' => ['suggestion_id' => $suggestion->id, 'executed' => true],
                ]);
            }

            return $executionSummary;
        });
    }

    public function dismissSuggestion(AgentSuggestion $suggestion, User $reviewer, ?string $reason = null): void
    {
        $this->assertReviewerCanApprove($suggestion, $reviewer);

        $suggestion->update([
            'approval_status' => AgentSuggestion::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reason ?: 'Dismissed by reviewer.',
        ]);

        if ($suggestion->thread_id) {
            AgentMessage::create([
                'thread_id' => $suggestion->thread_id,
                'user_id' => $reviewer->id,
                'role' => 'assistant',
                'content' => 'Dismissed: '.$suggestion->title,
                'meta' => ['suggestion_id' => $suggestion->id, 'dismissed' => true],
            ]);
        }
    }

    protected function assertReviewerCanApprove(AgentSuggestion $suggestion, User $reviewer): void
    {
        $agent = $suggestion->agent;
        $mode = $this->governanceModeFor($agent, $suggestion->risk_level);

        if ($mode === 'autonomous') {
            return;
        }

        $isOwner = (int) $agent->created_by === (int) $reviewer->id
            || (! empty($agent->owner_user_id) && (int) $agent->owner_user_id === (int) $reviewer->id);

        if ($this->isManagement($reviewer)) {
            return;
        }

        $permission = AgentPermission::query()->where('user_id', $reviewer->id)->first();

        if ($mode === 'team_approval') {
            if ($isOwner) {
                return;
            }

            if ($permission && ($permission->can_approve_medium_risk || $permission->can_approve_high_risk)) {
                return;
            }

            throw new RuntimeException('You do not have approval rights for medium-risk agent actions.');
        }

        if ($mode === 'management_approval') {
            if ($permission && $permission->can_approve_high_risk) {
                return;
            }

            throw new RuntimeException('Management approval is required for high-risk actions.');
        }
    }

    protected function shouldAutonomouslyExecute(Agent $agent, AgentSuggestion $suggestion): bool
    {
        if ($agent->autonomy_mode === 'propose_only') {
            return false;
        }

        return $this->governanceModeFor($agent, $suggestion->risk_level) === 'autonomous';
    }

    protected function governanceModeFor(Agent $agent, string $riskLevel): string
    {
        $risk = Str::lower(trim($riskLevel));
        $governance = (array) ($agent->governance_tiers ?? []);

        $default = match ($risk) {
            'low' => 'autonomous',
            'high' => 'management_approval',
            default => 'team_approval',
        };

        $mode = Str::lower((string) Arr::get($governance, $risk, $default));
        if (! in_array($mode, ['autonomous', 'team_approval', 'management_approval'], true)) {
            return $default;
        }

        return $mode;
    }

    protected function executeSuggestion(AgentSuggestion $suggestion, User $reviewer): string
    {
        $payload = $suggestion->payload ?? [];

        return match ($suggestion->suggestion_type) {
            'task', 'reminder' => $this->executeTaskSuggestion($suggestion, $reviewer, $payload),
            'email_draft' => $this->executeEmailDraftSuggestion($suggestion, $reviewer, $payload),
            'project_create', 'subproject_create' => $this->executeProjectSuggestion($suggestion, $reviewer, $payload),
            default => throw new RuntimeException('Unsupported suggestion type: '.$suggestion->suggestion_type),
        };
    }

    protected function executeTaskSuggestion(AgentSuggestion $suggestion, User $reviewer, array $payload): string
    {
        $projectId = (int) (Arr::get($payload, 'project_id') ?: $suggestion->agent->project_id ?: 0);

        $dueDate = Arr::get($payload, 'due_date');
        if (empty($dueDate)) {
            $inferred = $this->inferDueDateFromText($suggestion->title);
            $dueDate = $inferred?->toDateString();
        }

        if ($projectId > 0) {
            $task = ProjectTask::create([
                'project_id' => $projectId,
                'assigned_to' => $reviewer->id,
                'created_by' => $reviewer->id,
                'title' => $suggestion->title,
                'description' => Arr::get($payload, 'description'),
                'due_date' => $dueDate,
                'priority' => $suggestion->suggestion_type === 'reminder' ? 'low' : Arr::get($payload, 'priority', 'medium'),
                'status' => 'pending',
            ]);

            return 'Created task "'.$task->title.'" on project #'.$projectId.'.';
        }

        $action = Action::createResilient([
            'title' => $suggestion->title,
            'meeting_id' => null,
            'project_id' => null,
            'description' => Arr::get($payload, 'description', $suggestion->title),
            'notes' => Arr::get($payload, 'description'),
            'due_date' => $dueDate,
            'priority' => $suggestion->suggestion_type === 'reminder' ? 'low' : Arr::get($payload, 'priority', 'medium'),
            'status' => Action::STATUS_PENDING,
            'source' => Action::SOURCE_AI_SUGGESTED,
            'assigned_to' => $reviewer->id,
        ]);

        return 'Created unlinked action #'.$action->id.' for "'.$suggestion->title.'".';
    }

    protected function executeEmailDraftSuggestion(AgentSuggestion $suggestion, User $reviewer, array $payload): string
    {
        $projectId = Arr::get($payload, 'project_id');
        $subject = Arr::get($payload, 'subject', $suggestion->title);
        $recipient = Arr::get($payload, 'recipient', 'Unspecified recipient');

        $action = Action::createResilient([
            'title' => 'Draft email: '.$subject,
            'meeting_id' => null,
            'project_id' => $projectId,
            'description' => 'Draft outbound email to '.$recipient,
            'notes' => Arr::get($payload, 'body_outline', Arr::get($payload, 'description')),
            'due_date' => Arr::get($payload, 'due_date'),
            'priority' => Arr::get($payload, 'priority', 'medium'),
            'status' => Action::STATUS_PENDING,
            'source' => Action::SOURCE_AI_SUGGESTED,
            'assigned_to' => $reviewer->id,
        ]);

        return 'Created follow-up action #'.$action->id.' to draft that email.';
    }

    protected function executeProjectSuggestion(AgentSuggestion $suggestion, User $reviewer, array $payload): string
    {
        $name = trim((string) Arr::get($payload, 'name', $suggestion->title));
        if ($name === '') {
            throw new RuntimeException('Project suggestions require a project name.');
        }

        $existing = Project::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
        if ($existing) {
            return 'Project already exists: '.$existing->name.' (#'.$existing->id.').';
        }

        $parentProjectId = Arr::get($payload, 'parent_project_id');
        if ($suggestion->suggestion_type === 'subproject_create' && ! $parentProjectId) {
            $parentProjectId = $suggestion->agent->project_id;
        }

        $project = Project::create([
            'name' => $name,
            'description' => Arr::get($payload, 'description'),
            'status' => 'planning',
            'created_by' => $reviewer->id,
            'parent_project_id' => $parentProjectId,
            'project_type' => Arr::get($payload, 'project_type', 'component'),
        ]);

        return 'Created project "'.$project->name.'" (#'.$project->id.').';
    }

    protected function buildSuggestion(Agent $agent, AgentRun $run, AgentThread $thread, string $line, array $entityIndex): array
    {
        $line = trim($line);
        $type = $this->inferSuggestionType($line);
        $sources = $this->findEntitySources($line, $entityIndex, $agent);

        $linkedProjectId = $this->firstSourceIdByType($sources, 'project')
            ?? ($agent->project_id ? (int) $agent->project_id : null);

        $title = $this->cleanSuggestionTitle($line);
        $reasoning = 'Parsed directive as '.str_replace('_', ' ', $type).' based on phrasing and matched entities.';
        $alternative = null;

        $payload = [
            'description' => $line,
            'project_id' => $linkedProjectId,
            'linked_entities' => array_map(function ($source) {
                return [
                    'type' => $source['source_type'],
                    'id' => $source['source_id'],
                    'title' => $source['source_title'],
                ];
            }, $sources),
            'thread_id' => $thread->id,
            'run_id' => $run->id,
        ];

        if ($type === 'email_draft') {
            $payload['subject'] = $this->extractSubjectHint($line);
            $payload['recipient'] = $this->extractRecipientHint($line);
            $payload['body_outline'] = $line;
            $alternative = 'Could also be captured as a generic follow-up task instead of email draft.';
        }

        if ($type === 'project_create' || $type === 'subproject_create') {
            $projectName = $this->extractProjectName($line) ?: Str::limit($title, 80, '');
            $payload['name'] = trim($projectName);
            if ($type === 'subproject_create') {
                $payload['parent_project_id'] = $agent->project_id;
            }

            $existing = Project::query()->whereRaw('LOWER(name) = ?', [Str::lower((string) $payload['name'])])->first();
            if ($existing) {
                $type = 'task';
                $title = 'Follow up on existing project: '.$existing->name;
                $payload['project_id'] = $existing->id;
                $reasoning = 'Detected this project already exists, so proposing an execution task instead of creating a duplicate project.';
                $alternative = 'Could create a subproject under '.$existing->name.' if you need a new workstream.';
            } else {
                $reasoning = 'Detected new project language and no exact existing project match.';
                $alternative = 'Could instead attach this as a task under an existing project if preferred.';
            }
        }

        if (in_array($type, ['task', 'reminder', 'email_draft'], true)) {
            $projectNameCandidate = $this->extractProjectName($line);
            if ($projectNameCandidate && empty($payload['project_id'])) {
                $existingProject = Project::query()->whereRaw('LOWER(name) = ?', [Str::lower($projectNameCandidate)])->first();
                if ($existingProject) {
                    $payload['project_id'] = $existingProject->id;
                } else {
                    $payload['project_name_candidate'] = $projectNameCandidate;
                    $alternative = 'This may be a net-new project. Consider creating "'.$projectNameCandidate.'" first.';
                }
            }
        }

        $dueDate = $this->inferDueDateFromText($line);
        if ($dueDate) {
            $payload['due_date'] = $dueDate->toDateString();
        }

        $risk = $this->riskForType($type);

        return [
            'type' => $type,
            'title' => $title,
            'reasoning' => $reasoning,
            'alternative' => $alternative,
            'risk_level' => $risk,
            'payload' => $payload,
            'sources' => $sources,
        ];
    }

    protected function buildAssistantResponse(Agent $agent, array $suggestions, int $autoExecutedCount = 0): string
    {
        $counts = collect($suggestions)->countBy('suggestion_type');

        $lines = [];
        $lines[] = 'I reviewed your direction and prepared '.count($suggestions).' suggested action'.(count($suggestions) === 1 ? '' : 's').'.';

        if ($autoExecutedCount > 0) {
            $lines[] = $autoExecutedCount.' low-risk action'.($autoExecutedCount === 1 ? ' was' : 's were').' executed automatically based on this agent\'s governance settings.';
        }

        $lines[] = '';
        $lines[] = 'Proposed mix:';

        foreach ($counts as $type => $count) {
            $lines[] = '- '.$count.' '.str_replace('_', ' ', $type).($count > 1 ? ' items' : ' item');
        }

        $governance = (array) ($agent->governance_tiers ?? []);
        if (! empty($governance)) {
            $lines[] = '';
            $lines[] = 'Governance mode for this agent:';
            $lines[] = '- Low risk: '.($governance['low'] ?? 'autonomous');
            $lines[] = '- Medium risk: '.($governance['medium'] ?? 'team_approval');
            $lines[] = '- High risk: '.($governance['high'] ?? 'management_approval');
        }

        $pendingCount = collect($suggestions)->where('approval_status', AgentSuggestion::STATUS_PENDING)->count();
        if ($pendingCount > 0) {
            $lines[] = '';
            $lines[] = 'Use Approve, Modify, or Dismiss in the queue for the remaining '.$pendingCount.' pending action'.($pendingCount === 1 ? '' : 's').'.';
        }

        return implode("\n", $lines);
    }

    protected function summarizeSuggestionMix(array $suggestions): string
    {
        $counts = collect($suggestions)->countBy('suggestion_type');

        return $counts->map(fn ($count, $type) => $count.' '.str_replace('_', ' ', $type))->implode(', ');
    }

    protected function extractDirectiveLines(string $message): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $message) ?: [];
        $clean = collect($lines)
            ->map(fn ($line) => trim(preg_replace('/^[\-\*\d\.\)\s]+/', '', (string) $line)))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();

        if (empty($clean)) {
            return [trim($message)];
        }

        return $clean;
    }

    protected function inferSuggestionType(string $line): string
    {
        $normalized = Str::lower($line);

        if (preg_match('/\b(remind|reminder|follow up on monday|next monday|on monday|on tuesday|on wednesday|on thursday|on friday)\b/', $normalized)) {
            return 'reminder';
        }

        if (preg_match('/\b(sub[\-\s]?project)\b/', $normalized)) {
            return 'subproject_create';
        }

        if (preg_match('/\b(new|start|launch|create)\s+(a\s+)?project\b|\bproject\s+(called|named)\b/', $normalized)) {
            return 'project_create';
        }

        if (preg_match('/\b(email|draft\s+(an\s+)?email|send\s+(an\s+)?email)\b/', $normalized)) {
            return 'email_draft';
        }

        return 'task';
    }

    protected function riskForType(string $type): string
    {
        return match ($type) {
            'reminder' => 'low',
            'task', 'email_draft', 'subproject_create' => 'medium',
            'project_create' => 'high',
            default => 'medium',
        };
    }

    protected function cleanSuggestionTitle(string $line): string
    {
        $title = trim($line);
        $title = preg_replace('/\s+/', ' ', $title) ?: $title;

        return Str::limit($title, 140, '');
    }

    protected function extractSubjectHint(string $line): string
    {
        if (preg_match('/email\s+(to\s+[^\,\.]+)\s+(about|re|regarding)?\s*(.*)$/i', $line, $matches)) {
            $subject = trim($matches[3] ?? '');
            if ($subject !== '') {
                return Str::limit($subject, 120, '');
            }
        }

        return Str::limit($line, 120, '');
    }

    protected function extractRecipientHint(string $line): string
    {
        if (preg_match('/(?:email|send)\s+(?:to\s+)?([^\,\.]+?)(?:\s+about|\s+re\b|\s+regarding|$)/i', $line, $matches)) {
            return trim($matches[1]);
        }

        return 'Recipient to be confirmed';
    }

    protected function extractProjectName(string $line): ?string
    {
        $patterns = [
            '/project\s+(?:called|named)\s+["“]?(.+?)["”]?(?:[\.,]|$)/i',
            '/new\s+project\s+["“]?(.+?)["”]?(?:[\.,]|$)/i',
            '/project\s*:\s*["“]?(.+?)["”]?(?:[\.,]|$)/i',
            '/for\s+(.+?)\s+project\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $name = trim((string) ($matches[1] ?? ''));
                if ($name !== '') {
                    return Str::limit($name, 120, '');
                }
            }
        }

        return null;
    }

    protected function inferDueDateFromText(string $line): ?Carbon
    {
        $normalized = Str::lower($line);

        if (preg_match('/\b(today)\b/', $normalized)) {
            return now()->startOfDay();
        }
        if (preg_match('/\b(tomorrow)\b/', $normalized)) {
            return now()->addDay()->startOfDay();
        }

        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $weekday) {
            if (str_contains($normalized, 'next '.$weekday)) {
                return Carbon::parse('next '.$weekday)->startOfDay();
            }
            if (preg_match('/\bon\s+'.$weekday.'\b/', $normalized)) {
                $date = Carbon::parse($weekday)->startOfDay();
                if ($date->isPast()) {
                    $date = Carbon::parse('next '.$weekday)->startOfDay();
                }

                return $date;
            }
        }

        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $normalized, $matches)) {
            try {
                return Carbon::parse($matches[1])->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function buildEntityIndex(): array
    {
        return [
            'projects' => Project::query()->select(['id', 'name'])->limit(300)->get(),
            'organizations' => Organization::query()->select(['id', 'name'])->limit(300)->get(),
            'people' => Person::query()->select(['id', 'name'])->limit(300)->get(),
            'trips' => Trip::query()->select(['id', 'name'])->limit(250)->get(),
            'meetings' => Meeting::query()->select(['id', 'title'])->limit(300)->get(),
            'grants' => Grant::query()->select(['id', 'name'])->limit(300)->get(),
        ];
    }

    protected function findEntitySources(string $line, array $entityIndex, Agent $agent): array
    {
        $normalized = Str::lower($line);
        $matches = [];

        foreach ($entityIndex as $type => $records) {
            foreach ($records as $record) {
                $label = trim((string) ($record->name ?? $record->title ?? ''));
                if ($label === '' || mb_strlen($label) < 3) {
                    continue;
                }

                $needle = Str::lower($label);
                if (! Str::contains($normalized, $needle)) {
                    continue;
                }

                $score = mb_strlen($label);
                if ($type === 'projects' && $agent->project_id === $record->id) {
                    $score += 30;
                }

                $sourceType = rtrim($type, 's');
                $matches[] = [
                    'source_type' => $sourceType,
                    'source_id' => (string) $record->id,
                    'source_title' => $label,
                    'confidence' => min(99, 50 + ($score / 2)),
                    'score' => $score,
                    'source_url' => $this->sourceUrlFor($sourceType, (int) $record->id),
                ];
            }
        }

        if ($agent->project_id && ! collect($matches)->contains(fn ($item) => $item['source_type'] === 'project')) {
            $project = Project::query()->find($agent->project_id, ['id', 'name']);
            if ($project) {
                $matches[] = [
                    'source_type' => 'project',
                    'source_id' => (string) $project->id,
                    'source_title' => $project->name,
                    'confidence' => 72,
                    'score' => 72,
                    'source_url' => $this->sourceUrlFor('project', (int) $project->id),
                ];
            }
        }

        return collect($matches)
            ->sortByDesc('score')
            ->take(5)
            ->map(fn ($item) => Arr::except($item, ['score']))
            ->values()
            ->all();
    }

    protected function sourceUrlFor(string $sourceType, int $sourceId): ?string
    {
        if ($sourceId <= 0) {
            return null;
        }

        try {
            return match ($sourceType) {
                'project' => route('projects.show', $sourceId),
                'meeting' => route('meetings.show', $sourceId),
                'trip' => route('travel.show', $sourceId),
                'organization' => route('organizations.show', $sourceId),
                'person' => route('contacts.show', $sourceId),
                'grant' => route('grants.show', $sourceId),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    protected function firstSourceIdByType(array $sources, string $type): ?int
    {
        $match = collect($sources)
            ->first(fn ($source) => (string) ($source['source_type'] ?? '') === $type);

        if (! $match) {
            return null;
        }

        return (int) ($match['source_id'] ?? 0) ?: null;
    }

    protected function isManagement(User $user): bool
    {
        return $user->isAdmin() || $user->isManagement();
    }
}
