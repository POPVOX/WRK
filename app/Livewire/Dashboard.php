<?php

namespace App\Livewire;

use App\Jobs\SyncCalendarEvents;
use App\Models\Accomplishment;
use App\Models\Action;
use App\Models\Meeting;
use App\Models\PressClip;
use App\Models\Project;
use App\Models\Trip;
use App\Services\ChatService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Workspace')]
class Dashboard extends Component
{
    public $user;

    public bool $isCalendarConnected = false;

    public bool $isSyncing = false;

    public ?string $lastSyncAt = null;

    public ?string $aiWarning = null;

    public ?string $calendarWarning = null;

    public ?string $passportWarning = null;

    public bool $showTimezonePrompt = false;

    public string $omniInput = '';

    public array $omniConversation = [];

    public bool $omniBusy = false;

    public function mount(GoogleCalendarService $calendarService)
    {
        $this->user = Auth::user();

        if (! $this->user?->profile_completed_at) {
            return redirect()->route('onboarding');
        }

        $this->isCalendarConnected = $calendarService->isConnected($this->user);
        $this->lastSyncAt = $this->user->calendar_import_date?->diffForHumans();

        if (! config('ai.enabled')) {
            $this->aiWarning = 'AI features are disabled by the administrator.';
        }

        if (! $this->isCalendarConnected) {
            $this->calendarWarning = 'Calendar is not connected. Connect to keep meetings and focus windows accurate.';
        } elseif ($this->user->calendar_import_date && $this->user->calendar_import_date->lt(now()->subDays(7))) {
            $this->calendarWarning = 'Calendar has not synced in over a week.';
        }

        $travelProfile = $this->user->travelProfile;
        if ($travelProfile) {
            if ($travelProfile->isPassportExpired()) {
                $this->passportWarning = 'Your passport has expired. Update your travel profile.';
            } elseif ($travelProfile->isPassportExpiringSoon(6)) {
                $this->passportWarning = 'Your passport expires '.$travelProfile->passport_expiration->diffForHumans().'. Consider renewing soon.';
            }
        }

        $shouldPromptTimezone = ! $this->user->timezone
            || ! $this->user->timezone_confirmed_at
            || $this->user->timezone_confirmed_at->lt(now()->subDays(7));

        if ($shouldPromptTimezone) {
            $this->showTimezonePrompt = true;
        }

        $storedConversation = session('workspace.omniConversation', []);
        if (is_array($storedConversation)) {
            $this->omniConversation = collect($storedConversation)
                ->filter(fn ($item) => is_array($item) && isset($item['role'], $item['content']))
                ->take(-24)
                ->values()
                ->all();
        }
    }

    public function getGreetingProperty(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return 'Good morning';
        }

        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }

    public function getFirstNameProperty(): string
    {
        return explode(' ', $this->user->name)[0];
    }

    public function getWorkspaceDateLabelProperty(): string
    {
        return now()->format('l, F j');
    }

    public function getWorkspaceStatsProperty(): array
    {
        $today = today();

        $meetingsToday = $this->meetingsForUserQuery()
            ->whereDate('meeting_date', $today)
            ->count();

        $pendingActionsQuery = $this->pendingActionsQuery();
        $tasksDueToday = (clone $pendingActionsQuery)
            ->whereDate('due_date', $today)
            ->count();

        $tasksOverdue = (clone $pendingActionsQuery)
            ->whereDate('due_date', '<', $today)
            ->count();

        $activeProjects = $this->projectsForUserQuery()
            ->whereIn('status', ['active', 'planning', 'on_hold'])
            ->count();

        $upcomingTrips = Trip::query()
            ->whereHas('travelers', fn (Builder $q) => $q->where('users.id', $this->user->id))
            ->whereIn('status', ['planning', 'booked', 'in_progress'])
            ->whereDate('start_date', '>=', $today)
            ->whereDate('start_date', '<=', $today->copy()->addDays(30))
            ->count();

        return [
            'meetings_today' => $meetingsToday,
            'tasks_due_today' => $tasksDueToday,
            'tasks_overdue' => $tasksOverdue,
            'active_projects' => $activeProjects,
            'upcoming_trips' => $upcomingTrips,
        ];
    }

    public function getNextMeetingProperty(): ?array
    {
        $meetings = $this->meetingsForUserQuery()
            ->with(['organizations'])
            ->whereDate('meeting_date', '>=', today())
            ->orderBy('meeting_date')
            ->limit(30)
            ->get();

        $next = $meetings
            ->map(function (Meeting $meeting) {
                $startAt = $this->meetingDateTime($meeting, (string) $meeting->meeting_time);
                $endAt = $this->meetingDateTime($meeting, (string) $meeting->meeting_end_time, $startAt?->copy()->addHour());

                return [
                    'meeting' => $meeting,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                ];
            })
            ->filter(fn (array $item) => $item['start_at'] && $item['start_at']->greaterThanOrEqualTo(now()->subMinutes(15)))
            ->sortBy('start_at')
            ->first();

        if (! $next) {
            return null;
        }

        /** @var Meeting $meeting */
        $meeting = $next['meeting'];
        /** @var Carbon $startAt */
        $startAt = $next['start_at'];
        /** @var Carbon|null $endAt */
        $endAt = $next['end_at'];

        return [
            'id' => $meeting->id,
            'title' => $meeting->title ?: ($meeting->organizations->pluck('name')->first() ?: 'Upcoming meeting'),
            'starts_at' => $startAt,
            'ends_at' => $endAt,
            'date_label' => $startAt->isToday() ? 'Today' : $startAt->format('D, M j'),
            'time_label' => $startAt->format('g:i A').($endAt ? ' - '.$endAt->format('g:i A') : ''),
            'relative_label' => $startAt->isFuture() ? $startAt->diffForHumans(now(), true).' from now' : 'Starting now',
            'location' => $meeting->location ?: 'Location TBD',
            'organization' => $meeting->organizations->pluck('name')->first(),
            'key_ask' => $meeting->key_ask,
            'url' => route('meetings.show', $meeting),
        ];
    }

    public function getFocusNarrativeProperty(): string
    {
        $stats = $this->workspaceStats;
        $nextMeeting = $this->nextMeeting;

        $sentences = [];

        if ($nextMeeting && $nextMeeting['starts_at'] instanceof Carbon && $nextMeeting['starts_at']->isFuture()) {
            $minutes = now()->diffInMinutes($nextMeeting['starts_at']);
            $hours = round($minutes / 60, 1);
            if ($hours >= 1) {
                $sentences[] = "You have about {$hours} hour".($hours == 1.0 ? '' : 's')." of focus time before {$nextMeeting['title']}.";
            } else {
                $sentences[] = "{$nextMeeting['title']} is coming up in {$minutes} minutes.";
            }
        } else {
            $sentences[] = 'Your calendar is clear right now, so this is a good block for deep work.';
        }

        if ($stats['tasks_overdue'] > 0) {
            $sentences[] = "{$stats['tasks_overdue']} overdue task".($stats['tasks_overdue'] === 1 ? ' needs' : 's need')." attention.";
        } elseif ($stats['tasks_due_today'] > 0) {
            $sentences[] = "{$stats['tasks_due_today']} task".($stats['tasks_due_today'] === 1 ? ' is' : 's are')." due today.";
        } else {
            $sentences[] = 'No task deadlines are due today.';
        }

        if ($stats['upcoming_trips'] > 0) {
            $sentences[] = "You have {$stats['upcoming_trips']} upcoming trip".($stats['upcoming_trips'] === 1 ? '' : 's')." in the next 30 days.";
        }

        return implode(' ', $sentences);
    }

    public function getUrgentTasksProperty()
    {
        return $this->pendingActionsQuery()
            ->with(['project'])
            ->where(function (Builder $q) {
                $q->where('priority', 'high')
                    ->orWhereDate('due_date', '<=', today());
            })
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(6)
            ->get();
    }

    public function getNowTasksProperty()
    {
        return $this->pendingActionsQuery()
            ->where(function (Builder $q) {
                $q->whereDate('due_date', '<', today())
                    ->orWhereDate('due_date', today());
            })
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    public function getNextTasksProperty()
    {
        return $this->pendingActionsQuery()
            ->whereBetween('due_date', [today()->copy()->addDay(), today()->copy()->addDays(7)])
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    public function getLaterTasksProperty()
    {
        return $this->pendingActionsQuery()
            ->where(function (Builder $q) {
                $q->whereDate('due_date', '>', today()->copy()->addDays(7))
                    ->orWhereNull('due_date');
            })
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    public function getDailyPulseProperty(): array
    {
        $accomplishment = Accomplishment::query()
            ->visibleTo($this->user)
            ->latest('date')
            ->first();

        if ($accomplishment) {
            return [
                'title' => 'Recent Team Win',
                'body' => $accomplishment->title,
                'meta' => $accomplishment->user?->name ? 'Shared by '.$accomplishment->user->name : 'Team update',
                'url' => route('accomplishments.index'),
            ];
        }

        $clip = PressClip::query()->approved()->latest('published_at')->first();
        if ($clip) {
            return [
                'title' => 'Latest Coverage',
                'body' => $clip->title,
                'meta' => ($clip->outlet_display_name ?: 'Media').($clip->published_at ? ' · '.$clip->published_at->format('M j') : ''),
                'url' => route('media.index'),
            ];
        }

        return [
            'title' => 'Daily Pulse',
            'body' => 'No critical alerts right now. Use this block for strategic work.',
            'meta' => 'Stay focused on outcomes, not busywork.',
            'url' => route('dashboard'),
        ];
    }

    public function getSmartActionsProperty(): array
    {
        $actions = [
            [
                'key' => 'focus',
                'label' => 'What should I focus on?',
                'command' => 'What should I focus on first today based on my meetings and tasks?',
                'auto_submit' => true,
            ],
            [
                'key' => 'task',
                'label' => 'Capture a task',
                'command' => '/task ',
                'auto_submit' => false,
            ],
            [
                'key' => 'remind',
                'label' => 'Add reminder',
                'command' => '/remind ',
                'auto_submit' => false,
            ],
        ];

        if ($this->nextMeeting) {
            $actions[] = [
                'key' => 'prep_next_meeting',
                'label' => 'Prep next meeting',
                'command' => 'Give me a short prep brief for my next meeting: '.$this->nextMeeting['title'],
                'auto_submit' => true,
            ];
        }

        return $actions;
    }

    public function useSmartAction(string $key): void
    {
        $action = collect($this->smartActions)->firstWhere('key', $key);
        if (! $action) {
            return;
        }

        $this->omniInput = (string) $action['command'];

        if (($action['auto_submit'] ?? false) === true) {
            $this->submitOmni();
        }
    }

    public function submitOmni(): void
    {
        $command = trim($this->omniInput);
        if ($command === '') {
            return;
        }

        if (Str::length($command) > 2000) {
            $this->addConversationMessage('assistant', 'That message is too long for one command. Please split it into smaller chunks.');
            $this->omniInput = '';

            return;
        }

        $chatLimit = config('ai.limits.chat', ['max' => 30, 'decay_seconds' => 60]);
        $chatKey = 'ai-workspace-omni:'.($this->user?->id ?? 'guest');
        if (RateLimiter::tooManyAttempts($chatKey, $chatLimit['max'])) {
            $this->addConversationMessage('assistant', 'You are sending commands too quickly. Please wait a moment.');
            $this->omniInput = '';

            return;
        }

        RateLimiter::hit($chatKey, $chatLimit['decay_seconds']);

        $this->omniBusy = true;
        $this->addConversationMessage('user', $command);
        $this->omniInput = '';

        try {
            $normalized = Str::lower($command);

            if (Str::startsWith($normalized, ['/task', 'task:'])) {
                $result = $this->createTaskFromDirective($command, false);
                $this->addConversationMessage('assistant', $result);
            } elseif (Str::startsWith($normalized, ['/remind', 'remind', 'reminder'])) {
                $result = $this->createTaskFromDirective($command, true);
                $this->addConversationMessage('assistant', $result);
            } elseif (Str::startsWith($normalized, ['/help', 'help'])) {
                $this->addConversationMessage(
                    'assistant',
                    "Try:\n• /task Draft donor follow-up | due:today | priority:high\n• /remind Send receipts | due:tomorrow\nOr just ask a question in plain language."
                );
            } elseif (Str::startsWith($normalized, ['/sync calendar'])) {
                $this->syncCalendar();
                $this->addConversationMessage('assistant', 'Calendar sync started. I updated your workspace context.');
            } else {
                $historyForAi = collect($this->omniConversation)
                    ->take(-10)
                    ->map(fn (array $message) => [
                        'role' => $message['role'],
                        'content' => $message['content'],
                    ])
                    ->values()
                    ->all();

                $response = app(ChatService::class)->query($command, $historyForAi);
                $this->addConversationMessage('assistant', $response);
            }
        } catch (\Throwable $exception) {
            \Log::warning('Workspace omni command failed', [
                'user_id' => $this->user?->id,
                'command' => $command,
                'error' => $exception->getMessage(),
            ]);

            $this->addConversationMessage('assistant', 'I hit a processing error. Please try again with a shorter or clearer command.');
        } finally {
            $this->omniBusy = false;
        }
    }

    public function completeAction(int $actionId): void
    {
        $action = Action::query()
            ->where('id', $actionId)
            ->where('assigned_to', $this->user->id)
            ->where('status', 'pending')
            ->first();

        if (! $action) {
            return;
        }

        $action->update([
            'status' => 'complete',
            'completed_at' => now(),
        ]);

        $this->dispatch('notify', type: 'success', message: 'Task marked complete.');
    }

    public function syncCalendar(): void
    {
        if (! $this->isCalendarConnected) {
            return;
        }

        $this->isSyncing = true;

        try {
            SyncCalendarEvents::dispatchSync($this->user);
        } catch (\Throwable $e) {
            \Log::warning('Workspace calendar sync failed: '.$e->getMessage());
        }

        $this->user->refresh();
        $this->lastSyncAt = $this->user->calendar_import_date
            ? $this->user->calendar_import_date->diffForHumans()
            : 'just now';

        $this->calendarWarning = null;
        $this->isSyncing = false;
    }

    protected function addConversationMessage(string $role, string $content): void
    {
        $this->omniConversation[] = [
            'role' => $role,
            'content' => trim($content),
            'timestamp' => now()->format('g:i A'),
        ];

        if (count($this->omniConversation) > 24) {
            $this->omniConversation = array_slice($this->omniConversation, -24);
        }

        session(['workspace.omniConversation' => $this->omniConversation]);
    }

    protected function createTaskFromDirective(string $command, bool $isReminder): string
    {
        $parsed = $this->parseTaskDirective($command, $isReminder);
        $title = trim((string) Arr::get($parsed, 'title', ''));

        if ($title === '') {
            return $isReminder
                ? 'Reminder not created. Try: /remind Follow up with team | due:tomorrow'
                : 'Task not created. Try: /task Draft donor update | due:today | priority:high';
        }

        $action = Action::create([
            'title' => $title,
            'description' => (string) Arr::get($parsed, 'description', $title),
            'due_date' => Arr::get($parsed, 'due_date'),
            'priority' => Arr::get($parsed, 'priority', $isReminder ? 'low' : 'medium'),
            'status' => 'pending',
            'source' => 'manual',
            'assigned_to' => $this->user->id,
            'project_id' => Arr::get($parsed, 'project_id'),
        ]);

        $due = $action->due_date ? Carbon::parse($action->due_date)->format('M j') : 'no due date';
        $priority = ucfirst((string) $action->priority);
        $projectName = $action->project?->name;

        return 'Created '.($isReminder ? 'reminder' : 'task').": "
            .$action->title
            .' ('.$priority.', due '.$due.')'
            .($projectName ? ' under '.$projectName : '.');
    }

    protected function parseTaskDirective(string $command, bool $isReminder): array
    {
        $clean = preg_replace('/^\/?(task|remind(?:er)?):?\s*/i', '', trim($command));
        $parts = array_map('trim', explode('|', (string) $clean));

        $title = (string) array_shift($parts);
        $description = $title;
        $priority = $isReminder ? 'low' : 'medium';
        $dueDate = null;
        $projectId = null;

        foreach ($parts as $part) {
            $lower = Str::lower($part);

            if (Str::startsWith($lower, 'due:')) {
                $dueDate = $this->parseDueDateToken(trim(substr($part, 4)));
                continue;
            }

            if (Str::startsWith($lower, 'priority:')) {
                $candidate = trim(Str::lower(substr($part, 9)));
                if (in_array($candidate, ['high', 'medium', 'low'], true)) {
                    $priority = $candidate;
                }
                continue;
            }

            if (Str::startsWith($lower, 'project:')) {
                $projectName = trim(substr($part, 8));
                if ($projectName !== '') {
                    $projectId = $this->resolveUserProjectIdByName($projectName);
                }
                continue;
            }

            if (Str::startsWith($lower, 'description:')) {
                $description = trim(substr($part, 12));
            }
        }

        if ($dueDate === null) {
            if (preg_match('/\b(today)\b/i', $title)) {
                $dueDate = today()->toDateString();
                $title = trim(preg_replace('/\btoday\b/i', '', $title) ?? $title);
            } elseif (preg_match('/\b(tomorrow)\b/i', $title)) {
                $dueDate = today()->addDay()->toDateString();
                $title = trim(preg_replace('/\btomorrow\b/i', '', $title) ?? $title);
            }
        }

        if ($title === '') {
            $title = $description;
        }

        return [
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'due_date' => $dueDate,
            'project_id' => $projectId,
        ];
    }

    protected function parseDueDateToken(?string $token): ?string
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }

        $lower = Str::lower($token);
        if ($lower === 'today') {
            return today()->toDateString();
        }
        if ($lower === 'tomorrow') {
            return today()->addDay()->toDateString();
        }
        if ($lower === 'next-week' || $lower === 'next week') {
            return today()->addWeek()->toDateString();
        }

        try {
            return Carbon::parse($token)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveUserProjectIdByName(string $projectName): ?int
    {
        return $this->projectsForUserQuery()
            ->where('name', 'like', '%'.$projectName.'%')
            ->value('id');
    }

    protected function meetingsForUserQuery(): Builder
    {
        $userId = (int) $this->user->id;

        return Meeting::query()
            ->where(function (Builder $q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('teamMembers', fn (Builder $t) => $t->where('users.id', $userId));
            });
    }

    protected function pendingActionsQuery(): Builder
    {
        return Action::query()
            ->where('assigned_to', $this->user->id)
            ->where('status', 'pending');
    }

    protected function projectsForUserQuery(): Builder
    {
        $userId = (int) $this->user->id;
        $firstName = explode(' ', (string) $this->user->name)[0];

        return Project::query()
            ->where(function (Builder $query) use ($userId, $firstName) {
                $query->where('created_by', $userId)
                    ->orWhere('lead', 'like', '%'.$firstName.'%')
                    ->orWhereHas('staff', fn (Builder $q) => $q->where('user_id', $userId));
            });
    }

    protected function meetingDateTime(Meeting $meeting, ?string $timeValue, ?Carbon $fallback = null): ?Carbon
    {
        if (! $meeting->meeting_date) {
            return $fallback;
        }

        $base = Carbon::parse($meeting->meeting_date->format('Y-m-d'));

        if ($timeValue && trim($timeValue) !== '') {
            try {
                $parsedTime = Carbon::parse($timeValue)->format('H:i:s');
                return Carbon::parse($base->format('Y-m-d').' '.$parsedTime);
            } catch (\Throwable) {
                // Fall through to fallback behavior.
            }
        }

        return $fallback ?? $base->setTime(9, 0, 0);
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'greeting' => $this->greeting,
            'firstName' => $this->firstName,
            'workspaceDateLabel' => $this->workspaceDateLabel,
            'focusNarrative' => $this->focusNarrative,
            'workspaceStats' => $this->workspaceStats,
            'nextMeeting' => $this->nextMeeting,
            'urgentTasks' => $this->urgentTasks,
            'nowTasks' => $this->nowTasks,
            'nextTasks' => $this->nextTasks,
            'laterTasks' => $this->laterTasks,
            'dailyPulse' => $this->dailyPulse,
            'smartActions' => $this->smartActions,
            'recentMessages' => array_slice($this->omniConversation, -8),
        ]);
    }
}
