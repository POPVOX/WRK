<?php

namespace App\Livewire;

use App\Jobs\SyncCalendarEvents;
use App\Models\Accomplishment;
use App\Models\Action;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PressClip;
use App\Models\Project;
use App\Models\Trip;
use App\Services\ChatService;
use App\Services\GoogleCalendarService;
use App\Services\GoogleGmailService;
use App\Support\AI\AnthropicClient;
use App\Support\AI\PromptProfile;
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

    public ?string $gmailWarning = null;

    public ?string $passportWarning = null;

    public bool $showTimezonePrompt = false;

    public bool $isSyncingGmail = false;

    public ?string $lastGmailSyncAt = null;

    public string $omniInput = '';

    public array $omniConversation = [];

    public bool $omniBusy = false;

    public array $companionSuggestions = [];

    public function mount(GoogleCalendarService $calendarService)
    {
        $this->user = Auth::user();

        if (! $this->user?->profile_completed_at) {
            return redirect()->route('onboarding');
        }

        $this->isCalendarConnected = $calendarService->isConnected($this->user);
        $this->lastSyncAt = $this->user->calendar_import_date?->diffForHumans();
        $this->lastGmailSyncAt = $this->user->gmail_import_date?->diffForHumans();

        if (! config('ai.enabled')) {
            $this->aiWarning = 'AI features are disabled by the administrator.';
        }

        if (! $this->isCalendarConnected) {
            $this->calendarWarning = 'Calendar is not connected. Connect to keep meetings and focus windows accurate.';
        } elseif ($this->user->calendar_import_date && $this->user->calendar_import_date->lt(now()->subDays(7))) {
            $this->calendarWarning = 'Calendar has not synced in over a week.';
        }

        if ($this->isCalendarConnected && ! $this->user->gmail_import_date) {
            $this->gmailWarning = 'Gmail has not synced yet. Reconnect Google if needed, then run Sync Gmail.';
        } elseif ($this->user->gmail_import_date && $this->user->gmail_import_date->lt(now()->subDays(7))) {
            $this->gmailWarning = 'Gmail has not synced in over a week.';
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

        $storedSuggestions = session('workspace.companionSuggestions', []);
        if (is_array($storedSuggestions)) {
            $this->companionSuggestions = collect($storedSuggestions)
                ->filter(fn ($item) => is_array($item) && isset($item['id'], $item['type'], $item['title']))
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
        $now = now();
        $hour = (int) $now->hour;
        $isWeekend = $now->isWeekend();
        $isLateNight = $hour >= 21 || $hour < 6;

        $sentences = [];

        if ($isWeekend) {
            $sentences[] = 'Weekend mode: keep it light.';
        } elseif ($isLateNight) {
            $sentences[] = 'Late-night mode: capture ideas, protect rest.';
        }

        if ($nextMeeting && $nextMeeting['starts_at'] instanceof Carbon && $nextMeeting['starts_at']->isFuture()) {
            $minutes = now()->diffInMinutes($nextMeeting['starts_at']);
            $hours = $minutes / 60;

            if ($isWeekend) {
                if ($hours >= 24) {
                    $sentences[] = "{$nextMeeting['title']} is on ".$nextMeeting['starts_at']->format('l').'.';
                } elseif ($hours >= 2) {
                    $sentences[] = "{$nextMeeting['title']} is coming up ".$nextMeeting['starts_at']->diffForHumans().'.';
                } else {
                    $sentences[] = "{$nextMeeting['title']} is coming up in {$minutes} minutes.";
                }
            } elseif ($isLateNight) {
                if ($hours >= 6) {
                    $sentences[] = "{$nextMeeting['title']} is next.";
                } else {
                    $sentences[] = "{$nextMeeting['title']} is coming up in {$minutes} minutes.";
                }
            } elseif ($hours >= 8) {
                $roundedHours = (int) round($hours);
                $sentences[] = "You have about {$roundedHours} hour".($roundedHours === 1 ? '' : 's')." before {$nextMeeting['title']}.";
            } elseif ($hours >= 1) {
                $roundedHours = round($hours, 1);
                $sentences[] = "You have about {$roundedHours} hour".($roundedHours == 1.0 ? '' : 's')." before {$nextMeeting['title']}.";
            } else {
                $sentences[] = "{$nextMeeting['title']} is coming up in {$minutes} minutes.";
            }
        } else {
            $sentences[] = $isWeekend
                ? 'No meetings are pressing right now.'
                : 'Your calendar is clear right now, so this is a good block for deep work.';
        }

        if ($stats['tasks_overdue'] > 0) {
            if ($isWeekend || $isLateNight) {
                $sentences[] = "{$stats['tasks_overdue']} overdue task".($stats['tasks_overdue'] === 1 ? '' : 's').". Want a gentle Monday catch-up plan?";
            } else {
                $sentences[] = "{$stats['tasks_overdue']} overdue task".($stats['tasks_overdue'] === 1 ? ' needs' : 's need')." attention.";
            }
        } elseif ($stats['tasks_due_today'] > 0) {
            if ($isWeekend || $isLateNight) {
                $sentences[] = "{$stats['tasks_due_today']} task".($stats['tasks_due_today'] === 1 ? ' is' : 's are')." due today. Want a low-stress plan?";
            } else {
                $sentences[] = "{$stats['tasks_due_today']} task".($stats['tasks_due_today'] === 1 ? ' is' : 's are')." due today.";
            }
        } else {
            $sentences[] = $isWeekend
                ? 'No deadlines are due right now.'
                : 'No task deadlines are due today.';
        }

        if ($stats['upcoming_trips'] > 0) {
            $sentences[] = "You have {$stats['upcoming_trips']} upcoming trip".($stats['upcoming_trips'] === 1 ? '' : 's')." in the next 30 days.";
        }

        $nudge = $this->wellbeingNudge($isWeekend, $isLateNight);
        if ($nudge !== '') {
            $sentences[] = $nudge;
        }

        $sentences = collect($sentences)
            ->map(fn (string $sentence) => trim($sentence))
            ->filter(fn (string $sentence) => $sentence !== '')
            ->values()
            ->all();

        $maxSentences = ($isWeekend || $isLateNight) ? 3 : 4;

        return implode(' ', array_slice($sentences, 0, $maxSentences));
    }

    protected function wellbeingNudge(bool $isWeekend, bool $isLateNight): string
    {
        if (! $isWeekend && ! $isLateNight) {
            return '';
        }

        $weekendNudges = [
            'Quick wellness check: hydrate and get a little fresh air.',
            'Small planning pass, then unplug if you can.',
            'Low-pressure reminder: water, stretch, and one step at a time.',
        ];

        $lateNightNudges = [
            'Night check: hydrate, stretch, and leave runway for sleep.',
            'Capture ideas now, push heavy execution to tomorrow.',
            'A few focused notes now is enough.',
        ];

        $seed = now()->dayOfYear + now()->hour;
        if ($isWeekend && $isLateNight) {
            $combined = array_merge($weekendNudges, $lateNightNudges);
            return $combined[$seed % count($combined)];
        }

        if ($isWeekend) {
            return $weekendNudges[$seed % count($weekendNudges)];
        }

        return $lateNightNudges[$seed % count($lateNightNudges)];
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
                'key' => 'brain_dump',
                'label' => 'Morning brain dump',
                'command' => 'Here is everything on my mind this morning: ',
                'auto_submit' => false,
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

        if ($this->isCalendarConnected) {
            $actions[] = [
                'key' => 'sync_gmail',
                'label' => 'Sync Gmail',
                'command' => '/sync gmail',
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
                    "Try:\n• /task Draft donor follow-up | due:today | priority:high\n• /remind Send receipts | due:tomorrow\n• /sync calendar\n• /sync gmail\nOr just ask a question in plain language."
                );
            } elseif (Str::startsWith($normalized, ['/sync calendar'])) {
                $this->syncCalendar();
                $this->addConversationMessage('assistant', 'Calendar sync started. I updated your workspace context.');
            } elseif (Str::startsWith($normalized, ['/sync gmail', '/sync inbox'])) {
                $result = $this->syncGmail();
                $this->addConversationMessage('assistant', $result);
            } else {
                $proposalResult = $this->buildCompanionSuggestionResponse($command);
                $suggestions = is_array($proposalResult['suggestions'] ?? null)
                    ? $proposalResult['suggestions']
                    : [];

                if (! empty($suggestions)) {
                    $addedCount = $this->appendCompanionSuggestions($suggestions);
                    $assistantMessage = trim((string) ($proposalResult['assistant_message'] ?? ''));
                    if ($assistantMessage === '') {
                        $assistantMessage = $this->composeCompanionAssistantMessage($suggestions);
                    }
                    if ($addedCount < count($suggestions)) {
                        $assistantMessage .= "\n\nI skipped ".(count($suggestions) - $addedCount)." duplicate suggestion(s) already in your queue.";
                    }

                    $this->addConversationMessage('assistant', $assistantMessage);
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

    public function applyCompanionSuggestionAt(int $index): void
    {
        if (! isset($this->companionSuggestions[$index])) {
            return;
        }

        $suggestion = $this->companionSuggestions[$index];

        try {
            $type = (string) ($suggestion['type'] ?? '');
            $resultMessage = match ($type) {
                'task', 'reminder' => $this->applyTaskOrReminderSuggestion($suggestion),
                'draft_email' => $this->applyDraftEmailSuggestion($suggestion),
                'create_project' => $this->applyProjectSuggestion($suggestion, false),
                'create_subproject' => $this->applyProjectSuggestion($suggestion, true),
                default => 'I could not apply that suggestion because its type is not supported yet.',
            };

            unset($this->companionSuggestions[$index]);
            $this->companionSuggestions = array_values($this->companionSuggestions);
            $this->persistCompanionSuggestions();

            $this->addConversationMessage('assistant', $resultMessage);
            $this->dispatch('notify', type: 'success', message: 'Suggestion applied.');
        } catch (\Throwable $exception) {
            \Log::warning('Failed to apply companion suggestion', [
                'user_id' => $this->user?->id,
                'suggestion_id' => $suggestion['id'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            $this->addConversationMessage('assistant', 'I could not apply that suggestion: '.$exception->getMessage());
        }
    }

    public function applyCompanionSuggestion(string $suggestionId): void
    {
        $index = collect($this->companionSuggestions)
            ->search(fn (array $item) => (string) ($item['id'] ?? '') === $suggestionId);

        if ($index === false) {
            return;
        }

        $this->applyCompanionSuggestionAt((int) $index);
    }

    public function dismissCompanionSuggestionAt(int $index): void
    {
        if (! isset($this->companionSuggestions[$index])) {
            return;
        }

        unset($this->companionSuggestions[$index]);
        $this->companionSuggestions = array_values($this->companionSuggestions);
        $this->persistCompanionSuggestions();
    }

    public function dismissCompanionSuggestion(string $suggestionId): void
    {
        $index = collect($this->companionSuggestions)
            ->search(fn (array $item) => (string) ($item['id'] ?? '') === $suggestionId);

        if ($index === false) {
            return;
        }

        unset($this->companionSuggestions[$index]);
        $this->companionSuggestions = array_values($this->companionSuggestions);
        $this->persistCompanionSuggestions();
    }

    public function clearCompanionSuggestions(): void
    {
        $this->companionSuggestions = [];
        $this->persistCompanionSuggestions();
    }

    /**
     * @return array{assistant_message:string,suggestions:array<int,array<string,mixed>>}
     */
    protected function buildCompanionSuggestionResponse(string $command): array
    {
        $looksLikeQuestion = Str::contains($command, '?');
        $hasActionCue = (bool) preg_match('/\b(task|remind|todo|to do|need to|follow up|email|create|project|subproject|send|draft|schedule|review|prepare|submit|update)\b/i', $command);
        if ($looksLikeQuestion && ! $hasActionCue) {
            return [
                'assistant_message' => '',
                'suggestions' => [],
            ];
        }

        $fromAi = $this->inferCompanionSuggestionsWithAi($command);
        if ($fromAi !== null && ! empty($fromAi['suggestions'])) {
            return $fromAi;
        }

        $fromHeuristics = $this->inferCompanionSuggestionsHeuristically($command);
        if (! empty($fromHeuristics['suggestions'])) {
            return $fromHeuristics;
        }

        if ($fromAi !== null) {
            return [
                'assistant_message' => (string) ($fromAi['assistant_message'] ?? ''),
                'suggestions' => [],
            ];
        }

        return [
            'assistant_message' => '',
            'suggestions' => [],
        ];
    }

    /**
     * @return array{assistant_message:string,suggestions:array<int,array<string,mixed>>}|null
     */
    protected function inferCompanionSuggestionsWithAi(string $command): ?array
    {
        $apiKey = (string) (config('services.anthropic.api_key') ?: env('ANTHROPIC_API_KEY'));
        if (! config('ai.enabled') || trim($apiKey) === '') {
            return null;
        }

        $projectNames = $this->projectsForUserQuery()
            ->orderBy('name')
            ->limit(40)
            ->pluck('name')
            ->implode(', ');

        $systemPrompt = PromptProfile::forGeneralAssistant()."\n\n".<<<'PROMPT'
You are WRK Workspace Companion planner. Your job is to extract suggested actions from a user's stream-of-consciousness notes.

Rules:
- Suggest actions only. Do not execute anything.
- Prefer concrete, low-ambiguity actions.
- Return valid JSON only.
- Max 8 suggestions.
- Allowed suggestion types:
  1) task
  2) reminder
  3) draft_email
  4) create_project
  5) create_subproject
- For tasks/reminders/emails, include project_name when the note clearly maps to an existing or intended project.
- For due dates:
  - Convert to YYYY-MM-DD.
  - If user says "Monday" (or other weekday), use the next upcoming weekday from the provided current date.
  - If no date is stated, set due_date to null.
- Never invent names, dates, or commitments that are not implied by the note.

Output JSON shape:
{
  "assistant_message": "Short summary for the user. Include that nothing has been created yet.",
  "suggestions": [
    {
      "type": "task|reminder|draft_email|create_project|create_subproject",
      "title": "short action title",
      "description": "optional details",
      "due_date": "YYYY-MM-DD or null",
      "recipient": "email recipient name if relevant, else null",
      "project_name": "project name if relevant, else null",
      "parent_project_name": "parent project if create_subproject, else null",
      "reason": "why this suggestion was inferred",
      "confidence": "high|medium|low"
    }
  ]
}
PROMPT;

        $today = now()->format('Y-m-d');
        $todayLabel = now()->format('l, F j, Y');
        $tripNames = Trip::query()
            ->whereHas('travelers', fn (Builder $q) => $q->where('users.id', $this->user->id))
            ->orderByDesc('start_date')
            ->limit(25)
            ->pluck('name')
            ->implode(', ');
        $orgNames = Organization::query()
            ->orderBy('name')
            ->limit(40)
            ->pluck('name')
            ->implode(', ');
        $peopleNames = Person::query()
            ->orderBy('name')
            ->limit(40)
            ->pluck('name')
            ->implode(', ');
        $meetingTitles = $this->meetingsForUserQuery()
            ->whereNotNull('title')
            ->latest('meeting_date')
            ->limit(30)
            ->pluck('title')
            ->implode(', ');
        $userPrompt = <<<PROMPT
Current date: {$today} ({$todayLabel})
Known project names: {$projectNames}
Known trip names: {$tripNames}
Known organization names: {$orgNames}
Known people names: {$peopleNames}
Recent meeting titles: {$meetingTitles}

User note:
{$command}
PROMPT;

        $response = AnthropicClient::send([
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'max_tokens' => 1200,
        ]);

        $text = (string) data_get($response, 'content.0.text', '');
        $decoded = $this->decodeJsonBlock($text);

        if (! is_array($decoded)) {
            return null;
        }

        $suggestions = collect(Arr::get($decoded, 'suggestions', []))
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => $this->normalizeCompanionSuggestion($item))
            ->filter()
            ->values()
            ->all();

        return [
            'assistant_message' => trim((string) Arr::get($decoded, 'assistant_message', '')),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * @return array{assistant_message:string,suggestions:array<int,array<string,mixed>>}
     */
    protected function inferCompanionSuggestionsHeuristically(string $command): array
    {
        $chunks = preg_split('/[\r\n]+|(?<=[\.\!\?;])\s+/', trim($command)) ?: [];
        $chunks = array_values(array_filter(array_map('trim', $chunks)));
        if (empty($chunks)) {
            $chunks = [trim($command)];
        }

        $suggestions = [];

        foreach ($chunks as $chunk) {
            if ($chunk === '' || Str::length($chunk) < 6) {
                continue;
            }

            $lower = Str::lower($chunk);
            $dueDate = $this->parseDueDateFromFreeText($chunk);

            if (preg_match('/\b(remind me|please remind me|set a reminder|reminder)\b/i', $chunk)) {
                $title = preg_replace('/^.*\b(remind me(?: to)?|please remind me(?: to)?|set a reminder(?: to)?|reminder:?)\b/i', '', $chunk, 1);
                $title = trim((string) $title, " \t\n\r\0\x0B.:");
                $title = $title !== '' ? $title : $chunk;

                $suggestions[] = $this->normalizeCompanionSuggestion([
                    'type' => 'reminder',
                    'title' => $title,
                    'description' => $chunk,
                    'due_date' => $dueDate,
                    'reason' => 'You asked for a reminder.',
                    'confidence' => 'high',
                ]);
                continue;
            }

            if (preg_match('/\b(draft( an)? email|send( an)? email|email|write to)\b/i', $chunk)) {
                $recipient = null;
                if (preg_match('/\bto\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\b/', $chunk, $matches)) {
                    $recipient = trim((string) $matches[1]);
                }

                $suggestions[] = $this->normalizeCompanionSuggestion([
                    'type' => 'draft_email',
                    'title' => $recipient ? "Draft email to {$recipient}" : 'Draft follow-up email',
                    'description' => $chunk,
                    'recipient' => $recipient,
                    'due_date' => $dueDate,
                    'reason' => 'This looks like email follow-through.',
                    'confidence' => 'medium',
                ]);
                continue;
            }

            if (preg_match('/\b(sub-?project|subproject)\b/i', $chunk)) {
                $name = trim((string) preg_replace('/^.*\b(sub-?project|subproject)\b[:\s-]*/i', '', $chunk, 1));
                $name = $name !== '' ? $name : 'Untitled subproject';

                $suggestions[] = $this->normalizeCompanionSuggestion([
                    'type' => 'create_subproject',
                    'title' => Str::limit($name, 120, ''),
                    'description' => $chunk,
                    'reason' => 'This sounds like a subproject request.',
                    'confidence' => 'medium',
                ]);
                continue;
            }

            if (preg_match('/\b(new project|project idea|start a project|launch a project)\b/i', $lower)) {
                $name = trim((string) preg_replace('/^.*\b(new project|project idea|start a project|launch a project)\b[:\s-]*/i', '', $chunk, 1));
                $name = $name !== '' ? $name : 'Untitled project';

                $suggestions[] = $this->normalizeCompanionSuggestion([
                    'type' => 'create_project',
                    'title' => Str::limit($name, 120, ''),
                    'description' => $chunk,
                    'reason' => 'This sounds like a new project request.',
                    'confidence' => 'medium',
                ]);
                continue;
            }

            if (preg_match('/\b(i need to|need to|todo|to do|follow up|send|draft|review|prepare|schedule|finish|submit|share|update|reach out)\b/i', $lower)) {
                $title = trim((string) preg_replace('/\b(i need to|need to|todo|to do)\b[:\s-]*/i', '', $chunk, 1));
                $title = $title !== '' ? $title : $chunk;

                $suggestions[] = $this->normalizeCompanionSuggestion([
                    'type' => $dueDate ? 'reminder' : 'task',
                    'title' => Str::limit($title, 140, ''),
                    'description' => $chunk,
                    'due_date' => $dueDate,
                    'reason' => 'This reads like a concrete follow-up.',
                    'confidence' => 'medium',
                ]);
            }
        }

        $suggestions = collect($suggestions)
            ->filter()
            ->take(8)
            ->values()
            ->all();

        return [
            'assistant_message' => ! empty($suggestions)
                ? $this->composeCompanionAssistantMessage($suggestions)
                : '',
            'suggestions' => $suggestions,
        ];
    }

    protected function normalizeCompanionSuggestion(array $suggestion): ?array
    {
        $type = Str::lower(trim((string) ($suggestion['type'] ?? '')));
        if (! in_array($type, ['task', 'reminder', 'draft_email', 'create_project', 'create_subproject'], true)) {
            return null;
        }

        $title = Str::of((string) ($suggestion['title'] ?? ''))
            ->squish()
            ->trim()
            ->value();
        if ($title === '') {
            return null;
        }

        $description = Str::of((string) ($suggestion['description'] ?? $title))
            ->squish()
            ->trim()
            ->value();
        $reason = Str::of((string) ($suggestion['reason'] ?? ''))
            ->squish()
            ->trim()
            ->value();

        $dueDate = $this->parseDueDateToken((string) ($suggestion['due_date'] ?? ''));
        if (! $dueDate) {
            $dueDate = $this->parseDueDateFromFreeText((string) ($suggestion['due_text'] ?? ''));
        }

        $normalized = [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'title' => Str::limit($title, 140, ''),
            'description' => Str::limit($description, 320, ''),
            'due_date' => $dueDate,
            'due_label' => $dueDate ? Carbon::parse($dueDate)->format('M j, Y') : null,
            'recipient' => trim((string) ($suggestion['recipient'] ?? '')) ?: null,
            'project_name' => trim((string) ($suggestion['project_name'] ?? '')) ?: null,
            'parent_project_name' => trim((string) ($suggestion['parent_project_name'] ?? '')) ?: null,
            'reason' => Str::limit($reason, 200, ''),
            'confidence' => in_array(($suggestion['confidence'] ?? ''), ['high', 'medium', 'low'], true)
                ? (string) $suggestion['confidence']
                : 'medium',
        ];

        $normalized = $this->enrichCompanionSuggestionLinks($normalized);

        if (
            $normalized['type'] === 'create_project'
            && ! empty($normalized['linked_project_id'])
        ) {
            // Skip create-project suggestions when the project already exists.
            return null;
        }

        return $normalized;
    }

    protected function appendCompanionSuggestions(array $suggestions): int
    {
        $existingKeys = collect($this->companionSuggestions)
            ->map(fn (array $item) => Str::lower(($item['type'] ?? '').'|'.($item['title'] ?? '')))
            ->all();

        $added = 0;
        foreach ($suggestions as $suggestion) {
            if (! is_array($suggestion)) {
                continue;
            }

            $normalized = $this->normalizeCompanionSuggestion($suggestion);
            if (! $normalized) {
                continue;
            }

            $dedupeKey = Str::lower($normalized['type'].'|'.$normalized['title']);
            if (in_array($dedupeKey, $existingKeys, true)) {
                continue;
            }

            $this->companionSuggestions[] = $normalized;
            $existingKeys[] = $dedupeKey;
            $added++;

            if (
                in_array($normalized['type'], ['task', 'reminder', 'draft_email'], true)
                && empty($normalized['linked_project_id'])
                && ! empty($normalized['unresolved_project_name'])
            ) {
                $createProjectSuggestion = $this->normalizeCompanionSuggestion([
                    'type' => 'create_project',
                    'title' => (string) $normalized['unresolved_project_name'],
                    'description' => 'Suggested from action: '.$normalized['title'],
                    'reason' => 'No existing project match found. Create a new project?',
                    'confidence' => $normalized['confidence'] === 'high' ? 'medium' : $normalized['confidence'],
                ]);

                if ($createProjectSuggestion) {
                    $createKey = Str::lower($createProjectSuggestion['type'].'|'.$createProjectSuggestion['title']);
                    if (! in_array($createKey, $existingKeys, true)) {
                        $this->companionSuggestions[] = $createProjectSuggestion;
                        $existingKeys[] = $createKey;
                        $added++;
                    }
                }
            }
        }

        if ($added > 0) {
            $this->persistCompanionSuggestions();
        }

        return $added;
    }

    protected function enrichCompanionSuggestionLinks(array $suggestion): array
    {
        $text = trim(((string) ($suggestion['title'] ?? '')).' '.((string) ($suggestion['description'] ?? '')));
        $projectName = trim((string) ($suggestion['project_name'] ?? ''));
        if ($projectName === '') {
            $projectName = (string) ($this->extractProjectNameFromText($text) ?? '');
        }

        $links = [];

        $project = null;
        if ($projectName !== '') {
            $project = $this->findProjectByName($projectName);
            if ($project) {
                $suggestion['linked_project_id'] = $project->id;
                $suggestion['linked_project_name'] = $project->name;
                $links[] = ['type' => 'project', 'name' => $project->name];
            } else {
                $suggestion['unresolved_project_name'] = Str::limit($projectName, 120, '');
            }
        }

        $meeting = $this->findMeetingByText($text);
        if ($meeting) {
            $suggestion['linked_meeting_id'] = $meeting->id;
            $suggestion['linked_meeting_title'] = $meeting->title ?: 'Meeting #'.$meeting->id;
            $links[] = ['type' => 'meeting', 'name' => $suggestion['linked_meeting_title']];
        }

        $trip = $this->findTripByText($text);
        if ($trip) {
            $suggestion['linked_trip_id'] = $trip->id;
            $suggestion['linked_trip_name'] = $trip->name ?: 'Trip #'.$trip->id;
            $links[] = ['type' => 'trip', 'name' => $suggestion['linked_trip_name']];

            if (! $project && empty($suggestion['linked_project_id']) && ! empty($trip->project_id)) {
                $tripProject = $this->projectsForUserQuery()
                    ->where('projects.id', $trip->project_id)
                    ->first();
                if ($tripProject) {
                    $suggestion['linked_project_id'] = $tripProject->id;
                    $suggestion['linked_project_name'] = $tripProject->name;
                    $links[] = ['type' => 'project', 'name' => $tripProject->name];
                }
            }
        }

        $organization = $this->findOrganizationByText($text);
        if ($organization) {
            $suggestion['linked_organization_id'] = $organization->id;
            $suggestion['linked_organization_name'] = $organization->name;
            $links[] = ['type' => 'organization', 'name' => $organization->name];
        }

        $person = $this->findPersonByText($text);
        if ($person) {
            $suggestion['linked_person_id'] = $person->id;
            $suggestion['linked_person_name'] = $person->name;
            $links[] = ['type' => 'person', 'name' => $person->name];
        }

        $dedupedLinks = collect($links)
            ->filter(fn ($item) => is_array($item) && ! empty($item['type']) && ! empty($item['name']))
            ->unique(fn ($item) => Str::lower($item['type'].'|'.$item['name']))
            ->values()
            ->all();

        $suggestion['links'] = $dedupedLinks;

        return $suggestion;
    }

    protected function extractProjectNameFromText(string $text): ?string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $patterns = [
            '/\bnew project[:\s-]*([A-Za-z0-9][A-Za-z0-9\s&\/\-\_]+)$/i',
            '/\bfor\s+([A-Za-z0-9][A-Za-z0-9\s&\/\-\_]+?)\s+project\b/i',
            '/\bproject[:\s-]*([A-Za-z0-9][A-Za-z0-9\s&\/\-\_]+)$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $trimmed, $matches)) {
                $candidate = trim((string) ($matches[1] ?? ''));
                $candidate = trim($candidate, " \t\n\r\0\x0B.,;:-");
                if ($candidate !== '' && Str::length($candidate) >= 3) {
                    return Str::limit($candidate, 120, '');
                }
            }
        }

        return null;
    }

    protected function findProjectByName(string $projectName): ?Project
    {
        $name = trim($projectName);
        if ($name === '') {
            return null;
        }

        $exact = $this->projectsForUserQuery()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();
        if ($exact) {
            return $exact;
        }

        return $this->projectsForUserQuery()
            ->where('name', 'like', '%'.$name.'%')
            ->orderByRaw('LENGTH(name)')
            ->first();
    }

    protected function findMeetingByText(string $text): ?Meeting
    {
        $token = $this->extractLikelyEntityToken($text);
        if ($token === '') {
            return null;
        }

        return $this->meetingsForUserQuery()
            ->whereNotNull('title')
            ->where('title', 'like', '%'.$token.'%')
            ->latest('meeting_date')
            ->first();
    }

    protected function findTripByText(string $text): ?Trip
    {
        $token = $this->extractLikelyEntityToken($text);
        if ($token === '') {
            return null;
        }

        return Trip::query()
            ->whereHas('travelers', fn (Builder $q) => $q->where('users.id', $this->user->id))
            ->where(function (Builder $query) use ($token) {
                $query->where('name', 'like', '%'.$token.'%')
                    ->orWhere('description', 'like', '%'.$token.'%');
            })
            ->latest('start_date')
            ->first();
    }

    protected function findOrganizationByText(string $text): ?Organization
    {
        $token = $this->extractLikelyEntityToken($text);
        if ($token === '') {
            return null;
        }

        $exact = Organization::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($token)])
            ->first();
        if ($exact) {
            return $exact;
        }

        return Organization::query()
            ->where('name', 'like', '%'.$token.'%')
            ->orderByRaw('LENGTH(name)')
            ->first();
    }

    protected function findPersonByText(string $text): ?Person
    {
        $token = $this->extractLikelyEntityToken($text);
        if ($token === '') {
            return null;
        }

        $exact = Person::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($token)])
            ->first();
        if ($exact) {
            return $exact;
        }

        return Person::query()
            ->where('name', 'like', '%'.$token.'%')
            ->orderByRaw('LENGTH(name)')
            ->first();
    }

    protected function extractLikelyEntityToken(string $text): string
    {
        $value = Str::of($text)
            ->replaceMatches('/\b(i need to|need to|todo|to do|follow up|send|draft|review|prepare|schedule|finish|submit|share|update|reach out|please|remind me(?: to)?|for|about|regarding|on)\b/i', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        if ($value === '') {
            return '';
        }

        if (Str::length($value) > 60) {
            $value = Str::of($value)->limit(60, '')->value();
        }

        return trim($value);
    }

    protected function composeCompanionAssistantMessage(array $suggestions): string
    {
        $count = count($suggestions);
        if ($count === 0) {
            return 'I did not detect concrete follow-up actions yet.';
        }

        $lines = [
            "I pulled {$count} possible action".($count === 1 ? '' : 's')." from your notes. I have not created anything yet.",
        ];

        foreach (array_slice($suggestions, 0, 5) as $suggestion) {
            if (! is_array($suggestion)) {
                continue;
            }

            $type = (string) ($suggestion['type'] ?? 'task');
            $title = (string) ($suggestion['title'] ?? 'Untitled');

            if ($type === 'task') {
                if (! empty($suggestion['unresolved_project_name'])) {
                    $lines[] = "• Should I make \"{$title}\" a task and create project \"{$suggestion['unresolved_project_name']}\"?";
                } elseif (! empty($suggestion['linked_project_name'])) {
                    $lines[] = "• Should I make \"{$title}\" a new task under {$suggestion['linked_project_name']}?";
                } else {
                    $lines[] = "• Should I make \"{$title}\" a new task?";
                }
                continue;
            }

            if ($type === 'reminder') {
                $due = (string) ($suggestion['due_label'] ?? 'later');
                $lines[] = "• Should I set a reminder for \"{$title}\"".($due !== '' ? " (due {$due})" : '').'?';
                continue;
            }

            if ($type === 'draft_email') {
                $recipient = (string) ($suggestion['recipient'] ?? '');
                $lines[] = $recipient !== ''
                    ? "• Should I draft an email to {$recipient}?"
                    : '• Should I draft an email for this follow-up?';
                continue;
            }

            if ($type === 'create_project') {
                $lines[] = "• Sounds like \"{$title}\" might be a new project. Should I create it?";
                continue;
            }

            if ($type === 'create_subproject') {
                $lines[] = "• Sounds like \"{$title}\" might be a subproject. Should I create it under an existing project?";
            }
        }

        if ($count > 5) {
            $lines[] = '• I found additional suggestions in the queue below.';
        }

        $lines[] = 'Use the Suggested Actions panel to approve what you want me to execute.';

        return implode("\n", $lines);
    }

    protected function parseDueDateFromFreeText(string $text): ?string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/\b(today|tomorrow|next week)\b/i', $trimmed, $matches)) {
            return $this->parseDueDateToken((string) $matches[1]);
        }

        if (preg_match('/\b(next\s+)?(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i', $trimmed, $matches)) {
            $forceNext = ! empty($matches[1]);
            $weekday = (string) $matches[2];
            return $this->nextWeekdayDate($weekday, $forceNext)?->toDateString();
        }

        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $trimmed, $matches)) {
            return $this->parseDueDateToken((string) $matches[1]);
        }

        if (preg_match('/\b([A-Za-z]{3,9}\s+\d{1,2}(?:,\s*\d{4})?)\b/', $trimmed, $matches)) {
            return $this->parseDueDateToken((string) $matches[1]);
        }

        return null;
    }

    protected function nextWeekdayDate(string $weekday, bool $forceNext = false): ?Carbon
    {
        $targetIso = $this->weekdayIsoNumber($weekday);
        if (! $targetIso) {
            return null;
        }

        $today = now()->startOfDay();
        $todayIso = (int) $today->dayOfWeekIso;
        $delta = ($targetIso - $todayIso + 7) % 7;
        if ($delta === 0 && $forceNext) {
            $delta = 7;
        }

        return $today->copy()->addDays($delta);
    }

    protected function weekdayIsoNumber(string $weekday): ?int
    {
        return match (Str::lower(trim($weekday))) {
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
            default => null,
        };
    }

    protected function decodeJsonBlock(string $text): ?array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{[\s\S]*\})\s*```/i', $trimmed, $matches)) {
            $decoded = json_decode((string) $matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $firstBrace = strpos($trimmed, '{');
        $lastBrace = strrpos($trimmed, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $candidate = substr($trimmed, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function applyTaskOrReminderSuggestion(array $suggestion): string
    {
        $isReminder = ($suggestion['type'] ?? 'task') === 'reminder';
        $projectId = (int) ($suggestion['linked_project_id'] ?? 0);
        if ($projectId <= 0) {
            $projectName = (string) ($suggestion['project_name'] ?? '');
            $projectId = $projectName !== '' ? (int) ($this->resolveUserProjectIdByName($projectName) ?? 0) : 0;
        }

        $meetingId = (int) ($suggestion['linked_meeting_id'] ?? 0);
        $links = collect($suggestion['links'] ?? [])->filter(fn ($item) => is_array($item))->values();
        $linkNote = $links->isNotEmpty()
            ? 'Linked context: '.$links->map(fn ($link) => ucfirst((string) ($link['type'] ?? 'entity')).'='.$link['name'])->implode('; ')
            : null;

        $action = Action::create([
            'title' => (string) ($suggestion['title'] ?? ''),
            'description' => (string) ($suggestion['description'] ?? $suggestion['title'] ?? ''),
            'due_date' => $suggestion['due_date'] ?? null,
            'priority' => $isReminder ? 'low' : 'medium',
            'status' => 'pending',
            'source' => 'manual',
            'assigned_to' => $this->user->id,
            'meeting_id' => $meetingId > 0 ? $meetingId : null,
            'project_id' => $projectId > 0 ? $projectId : null,
            'notes' => $linkNote,
        ]);

        $due = $action->due_date
            ? Carbon::parse($action->due_date)->format('F j, Y')
            : 'no due date';

        $linkedSummary = [];
        if ($action->project?->name) {
            $linkedSummary[] = 'project '.$action->project->name;
        }
        if (! empty($suggestion['linked_meeting_title'])) {
            $linkedSummary[] = 'meeting '.$suggestion['linked_meeting_title'];
        }
        if (! empty($suggestion['linked_trip_name'])) {
            $linkedSummary[] = 'trip '.$suggestion['linked_trip_name'];
        }
        if (! empty($suggestion['linked_organization_name'])) {
            $linkedSummary[] = 'organization '.$suggestion['linked_organization_name'];
        }
        if (! empty($suggestion['linked_person_name'])) {
            $linkedSummary[] = 'person '.$suggestion['linked_person_name'];
        }

        return 'Done. I created '.($isReminder ? 'a reminder' : 'a task').": "
            .$action->title
            .' (due '.$due.')'
            .(! empty($linkedSummary) ? ' and linked it to '.implode(', ', $linkedSummary).'.' : '.');
    }

    protected function applyDraftEmailSuggestion(array $suggestion): string
    {
        $draft = $this->generateEmailDraft($suggestion);

        return "Done. I drafted this email:\n\n".$draft;
    }

    protected function applyProjectSuggestion(array $suggestion, bool $isSubproject): string
    {
        $projectName = trim((string) ($suggestion['title'] ?? ''));
        if ($projectName === '') {
            throw new \RuntimeException('Project name is missing.');
        }

        $parentProjectId = null;
        if ($isSubproject) {
            $parentName = trim((string) ($suggestion['parent_project_name'] ?? $suggestion['project_name'] ?? ''));
            if ($parentName === '') {
                throw new \RuntimeException('Please specify the parent project for this subproject.');
            }

            $parentProjectId = $this->resolveUserProjectIdByName($parentName);
            if (! $parentProjectId) {
                throw new \RuntimeException("I could not find a matching parent project for \"{$parentName}\".");
            }
        }

        $project = Project::create([
            'name' => $projectName,
            'description' => (string) ($suggestion['description'] ?? 'Created from Workspace companion notes.'),
            'status' => 'planning',
            'created_by' => $this->user->id,
            'lead' => $this->user->name,
            'project_type' => 'initiative',
            'parent_project_id' => $parentProjectId,
        ]);

        $project->staff()->syncWithoutDetaching([
            $this->user->id => [
                'role' => 'lead',
                'added_at' => now(),
            ],
        ]);

        return $isSubproject
            ? "Done. I created subproject \"{$project->name}\"."
            : "Done. I created project \"{$project->name}\".";
    }

    protected function generateEmailDraft(array $suggestion): string
    {
        $recipient = trim((string) ($suggestion['recipient'] ?? 'there'));
        $topic = trim((string) ($suggestion['title'] ?? 'Follow-up'));
        $context = trim((string) ($suggestion['description'] ?? ''));
        $dueLabel = trim((string) ($suggestion['due_label'] ?? ''));

        $subject = 'Follow-up: '.Str::limit($topic, 80, '');
        $body = [
            'Subject: '.$subject,
            '',
            'Hi '.($recipient !== '' ? $recipient : 'there').',',
            '',
            'Quick follow-up on '.$topic.'.',
        ];

        if ($context !== '') {
            $body[] = '';
            $body[] = $context;
        }

        if ($dueLabel !== '') {
            $body[] = '';
            $body[] = 'If possible, could we close this by '.$dueLabel.'?';
        }

        $body[] = '';
        $body[] = 'Thank you,';
        $body[] = $this->user->name;

        return implode("\n", $body);
    }

    protected function persistCompanionSuggestions(): void
    {
        session(['workspace.companionSuggestions' => $this->companionSuggestions]);
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

    public function syncGmail(): string
    {
        if (! $this->isCalendarConnected) {
            return 'Google Workspace is not connected yet.';
        }

        $this->isSyncingGmail = true;
        $message = 'Gmail sync completed.';

        try {
            $summary = app(GoogleGmailService::class)->syncRecentMessages($this->user, 60, 300);
            $message = sprintf(
                'Gmail sync complete. Processed %d messages (%d new, %d updated).',
                (int) ($summary['processed'] ?? 0),
                (int) ($summary['imported'] ?? 0),
                (int) ($summary['updated'] ?? 0)
            );
        } catch (\Throwable $e) {
            \Log::warning('Workspace Gmail sync failed: '.$e->getMessage());
            $message = 'Gmail sync failed: '.$e->getMessage();
            $this->gmailWarning = $message;
        }

        $this->user->refresh();
        $this->lastGmailSyncAt = $this->user->gmail_import_date
            ? $this->user->gmail_import_date->diffForHumans()
            : 'just now';

        if (! str_starts_with($message, 'Gmail sync failed')) {
            $this->gmailWarning = null;
        }
        $this->isSyncingGmail = false;

        return $message;
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
        $this->dispatch('workspace-thread-updated');
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
        if (in_array($lower, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], true)) {
            return $this->nextWeekdayDate($lower)?->toDateString();
        }
        if (preg_match('/^next\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)$/', $lower, $matches)) {
            return $this->nextWeekdayDate((string) $matches[1], true)?->toDateString();
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
            'conversationMessages' => $this->omniConversation,
            'companionSuggestions' => $this->companionSuggestions,
        ]);
    }
}
