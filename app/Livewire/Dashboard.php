<?php

namespace App\Livewire;

use App\Jobs\SyncCalendarEvents;
use App\Models\Action;
use App\Models\Grant;
use App\Models\Meeting;
use App\Models\PressClip;
use App\Models\Project;
use App\Models\ReportingRequirement;
use App\Services\ChatService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public $user;

    // Calendar sync state
    public bool $isCalendarConnected = false;

    public bool $isSyncing = false;

    public ?string $lastSyncAt = null;

    // Chat state
    public string $chatQuery = '';

    public array $chatHistory = [];

    public bool $isProcessing = false;

    public ?string $aiWarning = null;

    public ?string $calendarWarning = null;

    // Task creation state
    public bool $showAddTask = false;

    public string $newTaskTitle = '';

    public string $newTaskDescription = '';

    public ?string $newTaskDueDate = null;

    public string $newTaskPriority = 'medium';

    public ?int $newTaskProjectId = null;

    // AI Task Suggestions
    public bool $showAiSuggestions = false;

    public array $aiSuggestedTasks = [];

    public bool $isLoadingSuggestions = false;

    // Timezone prompt
    public bool $showTimezonePrompt = false;

    public function mount(GoogleCalendarService $calendarService)
    {
        $this->user = Auth::user();

        // Redirect to onboarding if profile not completed
        if (! $this->user->profile_completed_at) {
            return redirect()->route('onboarding');
        }

        // Check calendar connection
        $this->isCalendarConnected = $calendarService->isConnected($this->user);
        $this->lastSyncAt = $this->user->calendar_import_date?->diffForHumans();

        // Health banners
        if (! config('ai.enabled')) {
            $this->aiWarning = 'AI features are disabled by the administrator.';
        } else {
            $lastError = Cache::get('metrics:ai:last_error_at');
            if ($lastError && now()->diffInMinutes(Carbon::parse($lastError)) < 30) {
                $this->aiWarning = 'AI responses may be delayed; recent errors were detected.';
            }
        }

        if (! $this->isCalendarConnected) {
            $this->calendarWarning = 'Calendar is not connected. Connect to keep meetings in sync.';
        } elseif ($this->user->calendar_import_date && $this->user->calendar_import_date->lt(now()->subDays(7))) {
            $this->calendarWarning = 'Calendar has not synced in over a week.';
        }

        // Check if we should prompt for timezone (no timezone set, or not confirmed in 7 days)
        $shouldPromptTimezone = ! $this->user->timezone
            || ! $this->user->timezone_confirmed_at
            || $this->user->timezone_confirmed_at->lt(now()->subDays(7));

        if ($shouldPromptTimezone) {
            $this->showTimezonePrompt = true;
        }
    }

    /**
     * Get time-appropriate greeting
     */
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

    /**
     * Get user's first name for display
     */
    public function getFirstNameProperty(): string
    {
        return explode(' ', $this->user->name)[0];
    }

    /**
     * Get stats for the dashboard header
     */
    public function getStatsProperty(): array
    {
        $userId = $this->user->id;
        $userName = $this->user->name;
        $firstName = explode(' ', $userName)[0];
        $today = today();
        $endOfWeek = now()->endOfWeek();

        // Meetings today (where user logged or is a team member)
        $meetingsToday = Meeting::whereDate('meeting_date', $today)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('users.id', $userId));
            })
            ->count();

        $meetingsTomorrow = Meeting::whereDate('meeting_date', $today->copy()->addDay())
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('users.id', $userId));
            })
            ->count();

        // Actions/Tasks
        $actionsQuery = Action::where('assigned_to', $userId)->where('status', 'pending');
        $actionsDueToday = (clone $actionsQuery)->whereDate('due_date', $today)->count();
        $actionsOverdue = (clone $actionsQuery)->where('due_date', '<', $today)->count();
        $actionsThisWeek = (clone $actionsQuery)->whereBetween('due_date', [$today, $endOfWeek])->count();

        // Projects
        $activeProjects = Project::whereIn('status', ['active', 'planning'])
            ->where(function ($query) use ($userId, $firstName) {
                $query->where('created_by', $userId)
                    ->orWhere('lead', 'like', "%{$firstName}%")
                    ->orWhereHas('staff', fn ($q) => $q->where('user_id', $userId));
            })
            ->count();

        $projectActionsPending = Action::where('assigned_to', $userId)
            ->where('status', 'pending')
            ->whereHas('meeting.projects')
            ->count();

        // Reports (admin only)
        $reportsOverdue = 0;
        $reportsDueSoon = 0;

        if ($this->user->isAdmin()) {
            $reportsOverdue = ReportingRequirement::overdue()->count();
            $reportsDueSoon = ReportingRequirement::upcoming()->count();
        }

        return [
            'meetings_today' => $meetingsToday,
            'meetings_tomorrow' => $meetingsTomorrow,
            'actions_due_today' => $actionsDueToday,
            'actions_overdue' => $actionsOverdue,
            'actions_this_week' => $actionsThisWeek,
            'active_projects' => $activeProjects,
            'project_actions_pending' => $projectActionsPending,
            'reports_overdue' => $reportsOverdue,
            'reports_due_soon' => $reportsDueSoon,
        ];
    }

    /**
     * Get today's meetings
     */
    public function getTodaysMeetingsProperty()
    {
        $userId = $this->user->id;

        return Meeting::with(['organizations', 'projects'])
            ->whereDate('meeting_date', today())
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('users.id', $userId));
            })
            ->orderBy('meeting_date')
            ->get();
    }

    /**
     * Get tomorrow's meeting count
     */
    public function getTomorrowMeetingsCountProperty(): int
    {
        $userId = $this->user->id;

        return Meeting::whereDate('meeting_date', today()->addDay())
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('users.id', $userId));
            })
            ->count();
    }

    /**
     * Get user's upcoming meetings for the week
     */
    public function getUpcomingMeetingsProperty()
    {
        $userId = $this->user->id;

        return Meeting::with(['organizations', 'projects'])
            ->where('meeting_date', '>=', today())
            ->where('meeting_date', '<=', today()->addWeek())
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('users.id', $userId));
            })
            ->orderBy('meeting_date')
            ->limit(10)
            ->get();
    }

    /**
     * Get user's pending actions/tasks
     */
    public function getMyActionsProperty()
    {
        return Action::where('assigned_to', $this->user->id)
            ->where('status', 'pending')
            ->with('meeting.organizations')
            ->orderByRaw('CASE WHEN due_date < date("now") THEN 0 ELSE 1 END')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function ($action) {
                $action->is_overdue = $action->due_date && $action->due_date < today();

                return $action;
            });
    }

    /**
     * Get user's projects
     */
    public function getMyProjectsProperty()
    {
        $userId = $this->user->id;
        $firstName = explode(' ', $this->user->name)[0];

        return Project::whereIn('status', ['active', 'planning', 'on_hold'])
            ->where(function ($query) use ($userId, $firstName) {
                $query->where('created_by', $userId)
                    ->orWhere('lead', 'like', "%{$firstName}%")
                    ->orWhereHas('staff', fn ($q) => $q->where('user_id', $userId));
            })
            ->withCount([
                'milestones as milestones_total_count',
                'milestones as milestones_completed_count' => fn ($q) => $q->where('status', 'completed'),
                'openQuestions',
            ])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($project) {
                $total = $project->milestones_total_count;
                $completed = $project->milestones_completed_count;
                $project->progress_percent = $total > 0
                    ? round(($completed / $total) * 100)
                    : 0;
                $project->pending_milestones_count = $total - $completed;

                return $project;
            });
    }

    /**
     * Get items needing attention
     */
    public function getNeedsAttentionProperty()
    {
        $userId = $this->user->id;
        $items = collect();

        // Overdue actions
        $overdueActions = Action::where('assigned_to', $userId)
            ->where('status', 'pending')
            ->where('due_date', '<', today())
            ->with('meeting.organizations')
            ->get();

        if ($overdueActions->isNotEmpty()) {
            $items->push([
                'severity' => 'overdue',
                'title' => $overdueActions->count().' task'.($overdueActions->count() > 1 ? 's' : '').' overdue',
                'items' => $overdueActions->map(fn ($a) => [
                    'label' => $a->description,
                    'url' => $a->meeting ? route('meetings.show', $a->meeting) : '#',
                ]),
            ]);
        }

        // Meetings needing notes (past meetings without notes)
        $needsNotes = Meeting::whereDate('meeting_date', '<', today())
            ->whereDate('meeting_date', '>=', today()->subDays(7))
            ->where('user_id', $userId)
            ->whereNull('raw_notes')
            ->orWhere('raw_notes', '')
            ->limit(5)
            ->get();

        if ($needsNotes->isNotEmpty()) {
            $items->push([
                'severity' => 'urgent',
                'title' => $needsNotes->count().' meeting'.($needsNotes->count() > 1 ? 's' : '').' need notes',
                'items' => $needsNotes->map(fn ($m) => [
                    'label' => ($m->title ?: $m->organizations->pluck('name')->first() ?: 'Meeting').' ('.$m->meeting_date->format('M j').')',
                    'url' => route('meetings.show', $m),
                ]),
            ]);
        }

        // Admin: Overdue reports
        if ($this->user->isAdmin()) {
            $overdueReports = ReportingRequirement::with('grant.funder')
                ->overdue()
                ->get();

            if ($overdueReports->isNotEmpty()) {
                $items->push([
                    'severity' => 'overdue',
                    'title' => $overdueReports->count().' report'.($overdueReports->count() > 1 ? 's' : '').' overdue',
                    'items' => $overdueReports->map(fn ($r) => [
                        'label' => $r->name.' ('.($r->grant->funder->name ?? 'Unknown').')',
                        'url' => route('grants.show', $r->grant),
                    ]),
                ]);
            }

            // Grants ending soon
            $grantsEnding = Grant::with('funder')
                ->where('status', 'active')
                ->where('end_date', '<=', now()->addMonths(2))
                ->where('end_date', '>=', now())
                ->get();

            if ($grantsEnding->isNotEmpty()) {
                $items->push([
                    'severity' => 'info',
                    'title' => $grantsEnding->count().' grant'.($grantsEnding->count() > 1 ? 's' : '').' ending soon',
                    'items' => $grantsEnding->map(fn ($g) => [
                        'label' => $g->name.' ('.$g->end_date->format('M j').')',
                        'url' => route('grants.show', $g),
                    ]),
                ]);
            }
        }

        return $items->sortBy(fn ($i) => $i['severity'] === 'overdue' ? 0 : ($i['severity'] === 'urgent' ? 1 : 2));
    }

    /**
     * Get funding alerts (admin only)
     */
    public function getFundingAlertsProperty()
    {
        if (! $this->user->isAdmin()) {
            return collect();
        }

        $alerts = collect();

        // Overdue reports
        ReportingRequirement::with('grant.funder')
            ->overdue()
            ->each(function ($report) use ($alerts) {
                $alerts->push([
                    'type' => 'overdue',
                    'title' => $report->name,
                    'funder' => $report->grant->funder->name ?? 'Unknown',
                    'url' => route('grants.show', $report->grant),
                ]);
            });

        // Reports due soon
        ReportingRequirement::with('grant.funder')
            ->where('status', '!=', 'submitted')
            ->whereBetween('due_date', [now(), now()->addWeeks(2)])
            ->each(function ($report) use ($alerts) {
                $alerts->push([
                    'type' => 'due_soon',
                    'title' => $report->name,
                    'funder' => $report->grant->funder->name ?? 'Unknown',
                    'url' => route('grants.show', $report->grant),
                ]);
            });

        return $alerts->take(6);
    }

    /**
     * Get recent press coverage
     */
    public function getRecentCoverageProperty()
    {
        return PressClip::approved()
            ->with(['staffMentioned', 'outlet'])
            ->latest('published_at')
            ->limit(4)
            ->get();
    }

    /**
     * Complete an action/task
     */
    public function completeAction(int $actionId): void
    {
        $action = Action::find($actionId);

        if ($action && $action->assigned_to === $this->user->id) {
            $action->update(['status' => 'completed']);
        }
    }

    /**
     * Sync calendar
     */
    public function syncCalendar()
    {
        if (! $this->isCalendarConnected) {
            return;
        }

        $this->isSyncing = true;

        // Dispatch sync job (runs immediately in sync mode for development)
        SyncCalendarEvents::dispatchSync($this->user);

        // Refresh data
        $this->user->refresh();
        $this->lastSyncAt = 'just now';

        $this->isSyncing = false;
    }

    /**
     * Send chat message
     */
    public function sendChat()
    {
        if (empty(trim($this->chatQuery))) {
            return;
        }

        if (! config('ai.enabled')) {
            $this->chatHistory[] = [
                'role' => 'assistant',
                'content' => 'AI features are disabled by the administrator.',
                'timestamp' => now()->format('g:i A'),
            ];

            return;
        }

        $chatLimit = config('ai.limits.chat', ['max' => 30, 'decay_seconds' => 60]);
        $chatKey = 'ai-dashboard-chat:'.($this->user?->id ?? 'guest');
        if (RateLimiter::tooManyAttempts($chatKey, $chatLimit['max'])) {
            $this->chatHistory[] = [
                'role' => 'assistant',
                'content' => 'You are sending messages too quickly. Please wait a moment.',
                'timestamp' => now()->format('g:i A'),
            ];

            return;
        }
        RateLimiter::hit($chatKey, $chatLimit['decay_seconds']);

        $this->isProcessing = true;
        $query = $this->chatQuery;
        $this->chatQuery = '';

        // Add user message to history
        $this->chatHistory[] = [
            'role' => 'user',
            'content' => $query,
            'timestamp' => now()->format('g:i A'),
        ];

        // Get AI response
        $response = app(ChatService::class)->query($query, $this->chatHistory);

        // Add assistant response to history
        $this->chatHistory[] = [
            'role' => 'assistant',
            'content' => $response,
            'timestamp' => now()->format('g:i A'),
        ];

        $this->isProcessing = false;

        // Dispatch event for scroll
        $this->dispatch('chatUpdated');
    }

    public function clearChat()
    {
        $this->chatHistory = [];
    }

    // === Task Management ===

    public function toggleAddTask(): void
    {
        $this->showAddTask = ! $this->showAddTask;
        if (! $this->showAddTask) {
            $this->resetTaskForm();
        }
    }

    public function resetTaskForm(): void
    {
        $this->newTaskTitle = '';
        $this->newTaskDescription = '';
        $this->newTaskDueDate = null;
        $this->newTaskPriority = 'medium';
        $this->newTaskProjectId = null;
    }

    public function createTask(): void
    {
        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskDescription' => 'nullable|string|max:1000',
            'newTaskDueDate' => 'nullable|date',
            'newTaskPriority' => 'required|in:high,medium,low',
            'newTaskProjectId' => 'nullable|exists:projects,id',
        ]);

        Action::create([
            'title' => $this->newTaskTitle,
            'description' => $this->newTaskDescription ?: $this->newTaskTitle,
            'due_date' => $this->newTaskDueDate,
            'priority' => $this->newTaskPriority,
            'status' => 'pending',
            'source' => 'manual',
            'assigned_to' => $this->user->id,
            'project_id' => $this->newTaskProjectId,
        ]);

        $this->resetTaskForm();
        $this->showAddTask = false;
        $this->dispatch('notify', type: 'success', message: 'Task created successfully!');
    }

    public function toggleAiSuggestions(): void
    {
        $this->showAiSuggestions = ! $this->showAiSuggestions;

        if ($this->showAiSuggestions && empty($this->aiSuggestedTasks)) {
            $this->loadAiSuggestions();
        }
    }

    public function loadAiSuggestions(): void
    {
        if (! config('ai.enabled')) {
            $this->aiSuggestedTasks = [];

            return;
        }

        $this->isLoadingSuggestions = true;

        try {
            // Gather context from user's projects, meetings, and calendar
            $context = $this->gatherTaskSuggestionContext();

            // Use AI to suggest tasks
            $apiKey = config('services.anthropic.api_key');
            if (empty($apiKey)) {
                $this->aiSuggestedTasks = [];
                $this->isLoadingSuggestions = false;

                return;
            }

            $client = new \App\Support\AI\AnthropicClient($apiKey);
            $response = $client->message(
                system: 'You are a productivity assistant helping a nonprofit professional manage their tasks. Based on the context provided, suggest 3-5 actionable tasks they should work on. Respond with a JSON array of tasks, each with: title, description, priority (high/medium/low), suggested_due_date (YYYY-MM-DD format or null), and reason (why this task is important now). Only suggest concrete, actionable tasks.',
                messages: [
                    ['role' => 'user', 'content' => "Based on my current work context, suggest tasks I should add to my to-do list:\n\n{$context}"],
                ],
                maxTokens: 1500
            );

            $content = $response['content'][0]['text'] ?? '';

            // Parse JSON from response
            if (preg_match('/\[.*\]/s', $content, $matches)) {
                $this->aiSuggestedTasks = json_decode($matches[0], true) ?? [];
            } else {
                $this->aiSuggestedTasks = [];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Task Suggestions Error: '.$e->getMessage());
            $this->aiSuggestedTasks = [];
        }

        $this->isLoadingSuggestions = false;
    }

    protected function gatherTaskSuggestionContext(): string
    {
        $context = [];

        // Current user's projects
        $projects = $this->myProjects;
        if ($projects->isNotEmpty()) {
            $context[] = "## My Active Projects:\n";
            foreach ($projects->take(5) as $project) {
                $context[] = "- {$project->name} (Progress: {$project->progress_percent}%, {$project->pending_milestones_count} milestones pending)";
            }
        }

        // Upcoming meetings this week
        $meetings = Meeting::with('organizations')
            ->whereBetween('meeting_date', [now(), now()->addWeek()])
            ->where(function ($q) {
                $q->where('user_id', $this->user->id)
                    ->orWhereHas('teamMembers', fn ($t) => $t->where('users.id', $this->user->id));
            })
            ->limit(5)
            ->get();

        if ($meetings->isNotEmpty()) {
            $context[] = "\n## Upcoming Meetings This Week:\n";
            foreach ($meetings as $meeting) {
                $title = $meeting->title ?: $meeting->organizations->pluck('name')->first() ?: 'Meeting';
                $context[] = "- {$title} on {$meeting->meeting_date->format('M j')}";
            }
        }

        // Recent items needing attention
        $needsAttention = $this->needsAttention;
        if ($needsAttention->isNotEmpty()) {
            $context[] = "\n## Items Needing Attention:\n";
            foreach ($needsAttention as $item) {
                $context[] = "- {$item['title']}";
            }
        }

        // Overdue tasks
        $overdueCount = Action::where('assigned_to', $this->user->id)
            ->pending()
            ->where('due_date', '<', today())
            ->count();

        if ($overdueCount > 0) {
            $context[] = "\n## Warning: {$overdueCount} overdue tasks!";
        }

        // Admin: Grant reporting
        if ($this->user->isAdmin()) {
            $upcomingReports = ReportingRequirement::with('grant')
                ->upcoming()
                ->limit(3)
                ->get();

            if ($upcomingReports->isNotEmpty()) {
                $context[] = "\n## Upcoming Grant Reports:\n";
                foreach ($upcomingReports as $report) {
                    $context[] = "- {$report->name} for {$report->grant->name} (Due: {$report->due_date->format('M j')})";
                }
            }
        }

        return implode("\n", $context);
    }

    public function addSuggestedTask(int $index): void
    {
        if (! isset($this->aiSuggestedTasks[$index])) {
            return;
        }

        $suggestion = $this->aiSuggestedTasks[$index];

        Action::create([
            'title' => $suggestion['title'],
            'description' => $suggestion['description'] ?? $suggestion['title'],
            'due_date' => $suggestion['suggested_due_date'] ?? null,
            'priority' => $suggestion['priority'] ?? 'medium',
            'status' => 'pending',
            'source' => 'ai_suggested',
            'assigned_to' => $this->user->id,
        ]);

        // Remove from suggestions
        unset($this->aiSuggestedTasks[$index]);
        $this->aiSuggestedTasks = array_values($this->aiSuggestedTasks);

        $this->dispatch('notify', type: 'success', message: 'Task added from AI suggestion!');
    }

    public function dismissSuggestion(int $index): void
    {
        unset($this->aiSuggestedTasks[$index]);
        $this->aiSuggestedTasks = array_values($this->aiSuggestedTasks);
    }

    /**
     * Get user's available projects for task assignment
     */
    public function getAvailableProjectsProperty()
    {
        $userId = $this->user->id;
        $firstName = explode(' ', $this->user->name)[0];

        return Project::whereIn('status', ['active', 'planning'])
            ->where(function ($query) use ($userId, $firstName) {
                $query->where('created_by', $userId)
                    ->orWhere('lead', 'like', "%{$firstName}%")
                    ->orWhereHas('staff', fn ($q) => $q->where('user_id', $userId));
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'greeting' => $this->greeting,
            'firstName' => $this->firstName,
            'stats' => $this->stats,
            'todaysMeetings' => $this->todaysMeetings,
            'tomorrowMeetingsCount' => $this->tomorrowMeetingsCount,
            'upcomingMeetings' => $this->upcomingMeetings,
            'myActions' => $this->myActions,
            'myProjects' => $this->myProjects,
            'needsAttention' => $this->needsAttention,
            'fundingAlerts' => $this->fundingAlerts,
            'recentCoverage' => $this->recentCoverage,
            'availableProjects' => $this->availableProjects,
        ]);
    }
}
