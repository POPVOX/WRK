# Dashboard Feature Spec - AI-Powered Intelligence Hub

## Overview

Transform the Dashboard from a simple list view into an intelligence hub with:
1. **Project cards with AI-generated status summaries** - At-a-glance understanding of where each project stands
2. **System-wide chat interface** - Ask questions across all meetings, projects, organizations, people, and decisions

This is the "intelligence layer" that makes the tool more than a CRM.

---

## Part 1: Project Cards with AI Status

### Concept

Each active project displays as a card showing:
- Basic info (name, status)
- Key metrics (meetings, open questions, pending milestones)
- **AI-generated status summary** - 2-3 sentences synthesizing recent activity, current state, and what needs attention

The AI summary answers: "What's happening with this project right now?"

### Database Changes

Add a field to cache the AI-generated summary (to avoid regenerating on every page load):

```php
// database/migrations/xxxx_add_ai_status_to_projects_table.php

Schema::table('projects', function (Blueprint $table) {
    $table->text('ai_status_summary')->nullable();
    $table->timestamp('ai_status_generated_at')->nullable();
});
```

### Model Updates

```php
// In App\Models\Project.php, add:

protected $casts = [
    'start_date' => 'date',
    'target_end_date' => 'date',
    'actual_end_date' => 'date',
    'ai_status_generated_at' => 'datetime',
];

public function needsStatusRefresh(): bool
{
    // Refresh if: never generated, older than 24 hours, or project updated since last generation
    if (!$this->ai_status_generated_at) {
        return true;
    }
    
    if ($this->ai_status_generated_at->diffInHours(now()) > 24) {
        return true;
    }
    
    if ($this->updated_at > $this->ai_status_generated_at) {
        return true;
    }
    
    // Check if any related items updated since last generation
    $latestMeeting = $this->meetings()->max('updated_at');
    $latestDecision = $this->decisions()->max('updated_at');
    $latestMilestone = $this->milestones()->max('updated_at');
    $latestQuestion = $this->questions()->max('updated_at');
    
    $latestActivity = collect([$latestMeeting, $latestDecision, $latestMilestone, $latestQuestion])
        ->filter()
        ->max();
    
    return $latestActivity && $latestActivity > $this->ai_status_generated_at;
}
```

### AI Status Generation Service

```php
// App\Services\ProjectStatusService.php

<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

class ProjectStatusService
{
    public function generateStatus(Project $project): string
    {
        $context = $this->buildContext($project);
        
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 300,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($project, $context),
                ]
            ]
        ]);
        
        $summary = $response->json('content.0.text');
        
        // Cache the result
        $project->update([
            'ai_status_summary' => $summary,
            'ai_status_generated_at' => now(),
        ]);
        
        return $summary;
    }
    
    protected function buildContext(Project $project): array
    {
        // Load all relevant data
        $project->load([
            'meetings' => fn($q) => $q->latest('meeting_date')->limit(5),
            'meetings.organizations',
            'decisions' => fn($q) => $q->latest('decision_date')->limit(3),
            'milestones',
            'questions' => fn($q) => $q->where('status', 'open'),
            'organizations',
            'people',
        ]);
        
        return [
            'recent_meetings' => $project->meetings->map(fn($m) => [
                'date' => $m->meeting_date?->format('M j, Y'),
                'orgs' => $m->organizations->pluck('name')->join(', '),
                'summary' => $m->ai_summary ?? $m->raw_notes,
                'key_ask' => $m->key_ask,
            ]),
            'recent_decisions' => $project->decisions->map(fn($d) => [
                'title' => $d->title,
                'date' => $d->decision_date?->format('M j, Y'),
                'rationale' => $d->rationale,
            ]),
            'milestones' => [
                'completed' => $project->milestones->where('status', 'completed')->count(),
                'pending' => $project->milestones->where('status', '!=', 'completed')->values()->map(fn($m) => [
                    'title' => $m->title,
                    'target_date' => $m->target_date?->format('M j, Y'),
                    'is_overdue' => $m->is_overdue,
                ]),
            ],
            'open_questions' => $project->questions->where('status', 'open')->pluck('question'),
            'organizations_count' => $project->organizations->count(),
            'people_count' => $project->people->count(),
        ];
    }
    
    protected function buildPrompt(Project $project, array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
You are providing a brief status update for a project. Be concise and actionable.

PROJECT: {$project->name}
DESCRIPTION: {$project->description}
GOALS: {$project->goals}

RECENT CONTEXT:
{$contextJson}

Write a 2-3 sentence status summary that captures:
1. Current momentum (active, stalled, wrapping up?)
2. Most important recent development
3. What needs attention next (if anything)

Be specific and concrete. Don't be generic. If there are overdue milestones or open questions, mention them.

Respond with ONLY the summary, no preamble.
PROMPT;
    }
    
    public function refreshIfNeeded(Project $project): string
    {
        if ($project->needsStatusRefresh()) {
            return $this->generateStatus($project);
        }
        
        return $project->ai_status_summary ?? $this->generateStatus($project);
    }
}
```

### Dashboard Livewire Component

```php
// App\Livewire\Dashboard.php

<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Meeting;
use App\Models\Action;
use App\Services\ProjectStatusService;
use Livewire\Component;

class Dashboard extends Component
{
    public $projects;
    public $recentMeetings;
    public $pendingActions;
    
    // Chat state
    public $chatQuery = '';
    public $chatHistory = [];
    public $isProcessing = false;
    
    public function mount(ProjectStatusService $statusService)
    {
        $this->projects = Project::where('status', 'active')
            ->withCount(['meetings', 'openQuestions', 'milestones as pending_milestones_count' => function($q) {
                $q->where('status', '!=', 'completed');
            }])
            ->get();
        
        // Refresh AI status for projects that need it (do this async in production)
        foreach ($this->projects as $project) {
            $statusService->refreshIfNeeded($project);
        }
        
        $this->recentMeetings = Meeting::with('organizations')
            ->latest('meeting_date')
            ->limit(5)
            ->get();
            
        $this->pendingActions = Action::where('status', 'pending')
            ->with('meeting')
            ->orderBy('due_date')
            ->limit(10)
            ->get();
    }
    
    public function refreshProjectStatus($projectId)
    {
        $project = Project::find($projectId);
        $service = app(ProjectStatusService::class);
        $service->generateStatus($project);
        
        // Refresh the projects collection
        $this->projects = Project::where('status', 'active')
            ->withCount(['meetings', 'openQuestions', 'milestones as pending_milestones_count' => function($q) {
                $q->where('status', '!=', 'completed');
            }])
            ->get();
    }
    
    public function sendChat()
    {
        if (empty(trim($this->chatQuery))) {
            return;
        }
        
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
        $response = app(\App\Services\ChatService::class)->query($query, $this->chatHistory);
        
        // Add assistant response to history
        $this->chatHistory[] = [
            'role' => 'assistant',
            'content' => $response,
            'timestamp' => now()->format('g:i A'),
        ];
        
        $this->isProcessing = false;
    }
    
    public function clearChat()
    {
        $this->chatHistory = [];
    }
    
    public function render()
    {
        return view('livewire.dashboard');
    }
}
```

---

## Part 2: System-Wide Chat Interface

### Concept

A chat interface that can answer questions across the entire knowledge base:
- "What have we heard about metadata standards?"
- "Who are our key contacts in Jamaica?"
- "What decisions have we made about AI transcription?"
- "What's pending across all projects?"
- "Summarize our meetings from last month"

The chat uses RAG (Retrieval Augmented Generation): search the database for relevant context, then use AI to synthesize an answer.

### Chat Service

```php
// App\Services\ChatService.php

<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\Organization;
use App\Models\Person;
use App\Models\ProjectDecision;
use App\Models\Issue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatService
{
    public function query(string $query, array $conversationHistory = []): string
    {
        // Step 1: Retrieve relevant context from database
        $context = $this->retrieveContext($query);
        
        // Step 2: Build prompt with context
        $prompt = $this->buildPrompt($query, $context, $conversationHistory);
        
        // Step 3: Get AI response
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 1000,
            'system' => $this->getSystemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ]
            ]
        ]);
        
        return $response->json('content.0.text') ?? 'Sorry, I encountered an error processing your request.';
    }
    
    protected function retrieveContext(string $query): array
    {
        $context = [];
        
        // Extract potential search terms
        $searchTerms = $this->extractSearchTerms($query);
        
        // Search meetings
        $meetings = Meeting::query()
            ->where(function($q) use ($searchTerms, $query) {
                $q->where('ai_summary', 'ILIKE', "%{$query}%")
                  ->orWhere('raw_notes', 'ILIKE', "%{$query}%")
                  ->orWhere('transcript', 'ILIKE', "%{$query}%")
                  ->orWhere('key_ask', 'ILIKE', "%{$query}%");
                  
                foreach ($searchTerms as $term) {
                    $q->orWhere('ai_summary', 'ILIKE', "%{$term}%")
                      ->orWhere('raw_notes', 'ILIKE', "%{$term}%");
                }
            })
            ->with(['organizations', 'people', 'projects'])
            ->latest('meeting_date')
            ->limit(10)
            ->get();
            
        if ($meetings->isNotEmpty()) {
            $context['meetings'] = $meetings->map(fn($m) => [
                'date' => $m->meeting_date?->format('M j, Y'),
                'organizations' => $m->organizations->pluck('name')->join(', '),
                'people' => $m->people->pluck('name')->join(', '),
                'projects' => $m->projects->pluck('name')->join(', '),
                'summary' => Str::limit($m->ai_summary ?? $m->raw_notes, 500),
                'key_ask' => $m->key_ask,
            ])->toArray();
        }
        
        // Search projects
        $projects = Project::query()
            ->where(function($q) use ($searchTerms, $query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%")
                  ->orWhere('goals', 'ILIKE', "%{$query}%");
                  
                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'ILIKE', "%{$term}%")
                      ->orWhere('description', 'ILIKE', "%{$term}%");
                }
            })
            ->with(['milestones', 'openQuestions'])
            ->limit(5)
            ->get();
            
        if ($projects->isNotEmpty()) {
            $context['projects'] = $projects->map(fn($p) => [
                'name' => $p->name,
                'status' => $p->status,
                'description' => Str::limit($p->description, 300),
                'goals' => $p->goals,
                'ai_status' => $p->ai_status_summary,
                'pending_milestones' => $p->milestones->where('status', '!=', 'completed')->pluck('title'),
                'open_questions' => $p->openQuestions->pluck('question'),
            ])->toArray();
        }
        
        // Search decisions
        $decisions = ProjectDecision::query()
            ->where(function($q) use ($searchTerms, $query) {
                $q->where('title', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%")
                  ->orWhere('rationale', 'ILIKE', "%{$query}%")
                  ->orWhere('context', 'ILIKE', "%{$query}%");
                  
                foreach ($searchTerms as $term) {
                    $q->orWhere('title', 'ILIKE', "%{$term}%")
                      ->orWhere('rationale', 'ILIKE', "%{$term}%");
                }
            })
            ->with('project')
            ->latest('decision_date')
            ->limit(10)
            ->get();
            
        if ($decisions->isNotEmpty()) {
            $context['decisions'] = $decisions->map(fn($d) => [
                'title' => $d->title,
                'project' => $d->project?->name,
                'date' => $d->decision_date?->format('M j, Y'),
                'description' => $d->description,
                'rationale' => $d->rationale,
            ])->toArray();
        }
        
        // Search organizations
        $organizations = Organization::query()
            ->where(function($q) use ($searchTerms, $query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('notes', 'ILIKE', "%{$query}%");
                  
                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'ILIKE', "%{$term}%");
                }
            })
            ->withCount('meetings')
            ->with('people')
            ->limit(5)
            ->get();
            
        if ($organizations->isNotEmpty()) {
            $context['organizations'] = $organizations->map(fn($o) => [
                'name' => $o->name,
                'type' => $o->type,
                'meetings_count' => $o->meetings_count,
                'people' => $o->people->map(fn($p) => $p->name . ($p->title ? " ({$p->title})" : ''))->join(', '),
                'notes' => Str::limit($o->notes, 200),
            ])->toArray();
        }
        
        // Search people
        $people = Person::query()
            ->where(function($q) use ($searchTerms, $query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('title', 'ILIKE', "%{$query}%")
                  ->orWhere('notes', 'ILIKE', "%{$query}%");
                  
                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'ILIKE', "%{$term}%");
                }
            })
            ->with('organization')
            ->withCount('meetings')
            ->limit(5)
            ->get();
            
        if ($people->isNotEmpty()) {
            $context['people'] = $people->map(fn($p) => [
                'name' => $p->name,
                'title' => $p->title,
                'organization' => $p->organization?->name,
                'meetings_count' => $p->meetings_count,
                'notes' => Str::limit($p->notes, 200),
            ])->toArray();
        }
        
        // Search issues
        $issues = Issue::query()
            ->where('name', 'ILIKE', "%{$query}%")
            ->withCount('meetings')
            ->limit(5)
            ->get();
            
        if ($issues->isNotEmpty()) {
            $context['issues'] = $issues->map(fn($i) => [
                'name' => $i->name,
                'meetings_count' => $i->meetings_count,
            ])->toArray();
        }
        
        // If query asks about "recent" or time-based, add recent meetings regardless of search match
        if (Str::contains(strtolower($query), ['recent', 'last week', 'last month', 'this week', 'today', 'yesterday', 'latest'])) {
            $recentMeetings = Meeting::with(['organizations', 'projects'])
                ->latest('meeting_date')
                ->limit(10)
                ->get();
                
            $context['recent_meetings'] = $recentMeetings->map(fn($m) => [
                'date' => $m->meeting_date?->format('M j, Y'),
                'organizations' => $m->organizations->pluck('name')->join(', '),
                'projects' => $m->projects->pluck('name')->join(', '),
                'summary' => Str::limit($m->ai_summary ?? $m->raw_notes, 300),
            ])->toArray();
        }
        
        // If query asks about "pending" or "todo" or "action", add pending actions
        if (Str::contains(strtolower($query), ['pending', 'todo', 'action', 'follow up', 'overdue', 'due'])) {
            $pendingActions = \App\Models\Action::where('status', 'pending')
                ->with('meeting.organizations')
                ->orderBy('due_date')
                ->limit(10)
                ->get();
                
            $context['pending_actions'] = $pendingActions->map(fn($a) => [
                'description' => $a->description,
                'due_date' => $a->due_date?->format('M j, Y'),
                'priority' => $a->priority,
                'from_meeting' => $a->meeting?->organizations->pluck('name')->join(', '),
            ])->toArray();
            
            // Also get overdue milestones
            $overdueMilestones = \App\Models\ProjectMilestone::where('status', '!=', 'completed')
                ->whereNotNull('target_date')
                ->where('target_date', '<', now())
                ->with('project')
                ->get();
                
            $context['overdue_milestones'] = $overdueMilestones->map(fn($m) => [
                'title' => $m->title,
                'project' => $m->project?->name,
                'target_date' => $m->target_date?->format('M j, Y'),
            ])->toArray();
        }
        
        // Add summary stats
        $context['system_stats'] = [
            'total_meetings' => Meeting::count(),
            'total_projects' => Project::where('status', 'active')->count(),
            'total_organizations' => Organization::count(),
            'total_people' => Person::count(),
            'open_questions' => \App\Models\ProjectQuestion::where('status', 'open')->count(),
            'pending_actions' => \App\Models\Action::where('status', 'pending')->count(),
        ];
        
        return $context;
    }
    
    protected function extractSearchTerms(string $query): array
    {
        // Remove common words and extract meaningful terms
        $stopWords = ['what', 'who', 'where', 'when', 'how', 'why', 'is', 'are', 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'about', 'have', 'has', 'had', 'do', 'does', 'did', 'our', 'we', 'us', 'me', 'my', 'i', 'you', 'your', 'they', 'them', 'their', 'it', 'its', 'this', 'that', 'these', 'those', 'from', 'been', 'being', 'was', 'were', 'will', 'would', 'could', 'should', 'can', 'may', 'might', 'must'];
        
        $words = preg_split('/\s+/', strtolower($query));
        $terms = array_filter($words, fn($word) => 
            strlen($word) > 2 && !in_array($word, $stopWords)
        );
        
        return array_values($terms);
    }
    
    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an AI assistant for the POPVOX Foundation Meetings Intel tool. You help users understand their meetings, projects, relationships, and decisions.

Your knowledge base includes:
- Meetings (with summaries, organizations involved, key asks)
- Projects (with status, milestones, decisions, open questions)
- Organizations and People (contacts, relationships)
- Decisions (what was decided, why, in what context)
- Issues/topics being tracked

When answering:
1. Be specific and reference actual data when available
2. If you find relevant meetings, mention dates and who was involved
3. If you find relevant decisions, explain the rationale
4. If information isn't found, say so clearly
5. Be concise but complete
6. If the user asks about something you don't have data on, suggest what they might search for

You're helping a small nonprofit team stay on top of their work. Be helpful and practical.
PROMPT;
    }
    
    protected function buildPrompt(string $query, array $context, array $conversationHistory): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT);
        
        // Include recent conversation for continuity
        $recentHistory = '';
        if (!empty($conversationHistory)) {
            $recent = array_slice($conversationHistory, -4); // Last 2 exchanges
            foreach ($recent as $msg) {
                $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
                $recentHistory .= "{$role}: {$msg['content']}\n\n";
            }
        }
        
        $prompt = "RETRIEVED CONTEXT:\n{$contextJson}\n\n";
        
        if ($recentHistory) {
            $prompt .= "RECENT CONVERSATION:\n{$recentHistory}\n";
        }
        
        $prompt .= "USER QUESTION: {$query}\n\n";
        $prompt .= "Please answer based on the retrieved context. If the context doesn't contain relevant information, say so.";
        
        return $prompt;
    }
}
```

---

## Part 3: Dashboard UI

### Blade Template

```blade
{{-- resources/views/livewire/dashboard.blade.php --}}

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600">Your projects and intelligence at a glance</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Main Content: Projects --}}
            <div class="lg:col-span-2 space-y-6">
                
                {{-- Quick Stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-gray-900">{{ $projects->count() }}</div>
                        <div class="text-sm text-gray-500">Active Projects</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-gray-900">{{ $recentMeetings->count() }}</div>
                        <div class="text-sm text-gray-500">Recent Meetings</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-amber-600">{{ $pendingActions->count() }}</div>
                        <div class="text-sm text-gray-500">Pending Actions</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-gray-900">
                            {{ $projects->sum('open_questions_count') }}
                        </div>
                        <div class="text-sm text-gray-500">Open Questions</div>
                    </div>
                </div>
                
                {{-- Projects Section --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Active Projects</h2>
                        <a href="{{ route('projects.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                            + New Project
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($projects as $project)
                            <div class="bg-white rounded-lg shadow-sm border p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <a href="{{ route('projects.show', $project) }}" 
                                           class="text-lg font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $project->name }}
                                        </a>
                                        <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                                            <span>{{ $project->meetings_count }} meetings</span>
                                            <span>•</span>
                                            <span>{{ $project->pending_milestones_count }} pending milestones</span>
                                            @if($project->open_questions_count > 0)
                                                <span>•</span>
                                                <span class="text-amber-600">{{ $project->open_questions_count }} open questions</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button wire:click="refreshProjectStatus({{ $project->id }})"
                                            wire:loading.attr="disabled"
                                            class="text-gray-400 hover:text-gray-600"
                                            title="Refresh status">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </div>
                                
                                {{-- AI Status Summary --}}
                                @if($project->ai_status_summary)
                                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4 mt-3">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                            <p class="text-sm text-gray-700">{{ $project->ai_status_summary }}</p>
                                        </div>
                                        @if($project->ai_status_generated_at)
                                            <p class="text-xs text-gray-400 mt-2">
                                                Updated {{ $project->ai_status_generated_at->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-lg p-4 mt-3">
                                        <p class="text-sm text-gray-500 italic">
                                            Generating status summary...
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
                                <p class="text-gray-500">No active projects yet.</p>
                                <a href="{{ route('projects.create') }}" 
                                   class="inline-block mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                    Create Your First Project
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
                
                {{-- Recent Meetings --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Meetings</h2>
                        <a href="{{ route('meetings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                            View all →
                        </a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border divide-y">
                        @forelse($recentMeetings as $meeting)
                            <a href="{{ route('meetings.show', $meeting) }}" 
                               class="block p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ $meeting->organizations->pluck('name')->join(', ') ?: 'Meeting' }}
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ $meeting->meeting_date?->format('M j, Y') }}
                                            @if($meeting->ai_summary)
                                                — {{ Str::limit($meeting->ai_summary, 80) }}
                                            @endif
                                        </div>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-center text-gray-500">
                                No meetings yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            
            {{-- Sidebar: Chat Interface --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border sticky top-4">
                    <div class="p-4 border-b">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                Ask Your Data
                            </h2>
                            @if(count($chatHistory) > 0)
                                <button wire:click="clearChat" class="text-sm text-gray-400 hover:text-gray-600">
                                    Clear
                                </button>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Ask questions about meetings, projects, people, decisions...
                        </p>
                    </div>
                    
                    {{-- Chat History --}}
                    <div class="h-96 overflow-y-auto p-4 space-y-4" id="chat-container">
                        @forelse($chatHistory as $message)
                            <div class="{{ $message['role'] === 'user' ? 'text-right' : 'text-left' }}">
                                <div class="{{ $message['role'] === 'user' 
                                    ? 'bg-indigo-600 text-white' 
                                    : 'bg-gray-100 text-gray-800' }} 
                                    rounded-lg px-4 py-2 inline-block max-w-[90%] text-sm">
                                    {!! nl2br(e($message['content'])) !!}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ $message['timestamp'] }}
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-400 text-sm py-8">
                                <p class="mb-4">Try asking:</p>
                                <div class="space-y-2">
                                    <button wire:click="$set('chatQuery', 'What have we heard about metadata standards?')"
                                            class="block w-full text-left px-3 py-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                                        "What have we heard about metadata standards?"
                                    </button>
                                    <button wire:click="$set('chatQuery', 'Who are our key contacts in Jamaica?')"
                                            class="block w-full text-left px-3 py-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                                        "Who are our key contacts in Jamaica?"
                                    </button>
                                    <button wire:click="$set('chatQuery', 'What decisions have we made recently?')"
                                            class="block w-full text-left px-3 py-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                                        "What decisions have we made recently?"
                                    </button>
                                    <button wire:click="$set('chatQuery', 'What\'s pending across all projects?')"
                                            class="block w-full text-left px-3 py-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                                        "What's pending across all projects?"
                                    </button>
                                </div>
                            </div>
                        @endforelse
                        
                        @if($isProcessing)
                            <div class="text-left">
                                <div class="bg-gray-100 rounded-lg px-4 py-2 inline-block">
                                    <div class="flex items-center gap-2">
                                        <div class="animate-pulse flex gap-1">
                                            <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animation-delay-200"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full animation-delay-400"></div>
                                        </div>
                                        <span class="text-sm text-gray-500">Thinking...</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Chat Input --}}
                    <div class="p-4 border-t">
                        <form wire:submit="sendChat" class="flex gap-2">
                            <input type="text"
                                   wire:model="chatQuery"
                                   placeholder="Ask a question..."
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                   @disabled($isProcessing)>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @disabled($isProcessing || empty($chatQuery))>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                {{-- Pending Actions (below chat on sidebar) --}}
                @if($pendingActions->isNotEmpty())
                    <div class="bg-white rounded-lg shadow-sm border mt-4">
                        <div class="p-4 border-b">
                            <h2 class="font-semibold text-gray-900">Pending Actions</h2>
                        </div>
                        <div class="divide-y max-h-64 overflow-y-auto">
                            @foreach($pendingActions as $action)
                                <div class="p-3">
                                    <div class="text-sm text-gray-900">{{ $action->description }}</div>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                        @if($action->due_date)
                                            <span class="{{ $action->due_date->isPast() ? 'text-red-600' : '' }}">
                                                Due {{ $action->due_date->format('M j') }}
                                            </span>
                                        @endif
                                        @if($action->meeting)
                                            <span>•</span>
                                            <span>{{ $action->meeting->organizations->pluck('name')->first() }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@script
<script>
    // Auto-scroll chat to bottom when new messages arrive
    $wire.on('chatUpdated', () => {
        const container = document.getElementById('chat-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
@endscript
```

---

## Configuration

### Add Anthropic API Config

```php
// config/services.php

'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
],
```

### Environment Variable

```env
ANTHROPIC_API_KEY=sk-ant-...
```

---

## Implementation Checklist

### Database
- [ ] Add `ai_status_summary` and `ai_status_generated_at` to projects table

### Services
- [ ] Create `ProjectStatusService` for AI status generation
- [ ] Create `ChatService` for system-wide RAG queries

### Components
- [ ] Update `Dashboard` Livewire component with:
  - Project cards with AI status
  - Chat state and methods
  - Status refresh functionality
- [ ] Create dashboard blade template with:
  - Project cards grid
  - Chat interface sidebar
  - Pending actions list
  - Quick stats

### Polish
- [ ] Add loading states for AI generation
- [ ] Add error handling for API failures
- [ ] Consider rate limiting / caching for chat queries
- [ ] Add keyboard shortcut (Enter to send chat)

---

## Future Enhancements

1. **Vector embeddings** - For better semantic search instead of ILIKE
2. **Scheduled status refresh** - Background job to refresh all project statuses daily
3. **Chat history persistence** - Save chat sessions for reference
4. **Suggested follow-ups** - AI suggests next questions based on context
5. **Export chat** - Download conversation as document
6. **Voice input** - Speak questions instead of typing
7. **Proactive insights** - Dashboard shows AI-surfaced insights without asking ("You haven't followed up with Jamaica in 3 weeks")
