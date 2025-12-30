# Knowledge Hub Implementation Specification

## Overview

Transform the Knowledge Hub from a placeholder into the organizational intelligence center—the first place users go to understand what needs attention, prepare for meetings, and find answers across all organizational knowledge.

## Design Principles

1. **Action-oriented**: Every element should either require action or enable action
2. **Contextual**: Surface relevant information based on what's happening now
3. **Proactive**: Don't wait for queries—anticipate what users need
4. **Connected**: Everything links to source data for deeper exploration

---

## Page Structure

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ HEADER                                                                      │
│ - Title, subtitle                                                           │
│ - Search bar with AI toggle                                                 │
│ - Quick action buttons                                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│ MAIN CONTENT (3-column on desktop, stacked on mobile)                       │
│                                                                             │
│ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐                │
│ │ Needs Attention │ │ This Week       │ │ Active          │                │
│ │                 │ │                 │ │ Relationships   │                │
│ │                 │ │                 │ │                 │                │
│ └─────────────────┘ └─────────────────┘ └─────────────────┘                │
│                                                                             │
│ ┌─────────────────────────────────────┐ ┌─────────────────────────────────┐│
│ │ Recent Insights                     │ │ Quick Queries                   ││
│ │                                     │ │                                 ││
│ └─────────────────────────────────────┘ └─────────────────────────────────┘│
├─────────────────────────────────────────────────────────────────────────────┤
│ SEARCH RESULTS / AI ANSWER (appears when query submitted)                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Requirements

### Ensure These Tables/Models Exist

```php
// Commitments table (if not exists)
Schema::create('commitments', function (Blueprint $table) {
    $table->id();
    $table->text('description');
    $table->enum('direction', ['from_us', 'to_us']); // We committed vs They committed
    $table->enum('status', ['open', 'completed', 'cancelled'])->default('open');
    $table->date('due_date')->nullable();
    $table->date('completed_at')->nullable();
    $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});

// Issues/Topics table (if not exists)
Schema::create('issues', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('color')->default('#6366f1'); // For visual tagging
    $table->timestamps();
});

// Pivot tables for issues
Schema::create('meeting_issues', function (Blueprint $table) {
    $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
    $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
    $table->primary(['meeting_id', 'issue_id']);
});

Schema::create('project_issues', function (Blueprint $table) {
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
    $table->primary(['project_id', 'issue_id']);
});

// Decisions table (if not exists)
Schema::create('decisions', function (Blueprint $table) {
    $table->id();
    $table->text('decision');
    $table->text('rationale')->nullable();
    $table->text('outcome')->nullable();
    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('made_by')->nullable()->constrained('users')->nullOnDelete();
    $table->date('decided_at');
    $table->timestamps();
});

// Knowledge Base Index (for search)
Schema::create('kb_entries', function (Blueprint $table) {
    $table->id();
    $table->string('source_type'); // meeting, document, decision, note, person, org, etc.
    $table->unsignedBigInteger('source_id');
    $table->string('title');
    $table->longText('content'); // Searchable text
    $table->text('summary')->nullable(); // AI-generated summary
    $table->json('metadata')->nullable(); // Dates, relationships, tags
    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamp('source_date')->nullable(); // When the source was created/occurred
    $table->timestamps();
    
    $table->index(['source_type', 'source_id']);
    $table->fullText(['title', 'content']); // For full-text search
});
```

---

## Main Livewire Component

### app/Livewire/KnowledgeHub/Index.php

```php
<?php

namespace App\Livewire\KnowledgeHub;

use App\Models\Commitment;
use App\Models\Decision;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Issue;
use App\Models\KbEntry;
use App\Services\KnowledgeHubService;
use Livewire\Component;

class Index extends Component
{
    // Search state
    public string $query = '';
    public bool $useAI = false;
    public bool $searching = false;
    public ?array $searchResults = null;
    public ?string $aiAnswer = null;

    // Modal state
    public bool $showPrepModal = false;
    public ?int $prepMeetingId = null;

    protected $queryString = [
        'query' => ['except' => ''],
    ];

    public function search()
    {
        if (empty($this->query)) {
            $this->searchResults = null;
            $this->aiAnswer = null;
            return;
        }

        $this->searching = true;
        
        $service = app(KnowledgeHubService::class);
        
        if ($this->useAI) {
            $result = $service->queryWithAI($this->query);
            $this->aiAnswer = $result['answer'];
            $this->searchResults = $result['sources'];
        } else {
            $this->searchResults = $service->search($this->query);
            $this->aiAnswer = null;
        }
        
        $this->searching = false;
    }

    public function runQuickQuery(string $query)
    {
        $this->query = $query;
        $this->useAI = true;
        $this->search();
    }

    public function clearSearch()
    {
        $this->query = '';
        $this->searchResults = null;
        $this->aiAnswer = null;
    }

    // Computed properties for dashboard widgets
    
    public function getNeedsAttentionProperty(): array
    {
        $user = auth()->user();
        
        return [
            'overdue_commitments' => Commitment::where('status', 'open')
                ->where('direction', 'from_us')
                ->where('due_date', '<', now())
                ->with(['organization', 'person', 'meeting'])
                ->limit(5)
                ->get(),
            
            'overdue_count' => Commitment::where('status', 'open')
                ->where('direction', 'from_us')
                ->where('due_date', '<', now())
                ->count(),
            
            'meetings_need_notes' => Meeting::needsNotes()
                ->with(['organizations'])
                ->limit(3)
                ->get(),
            
            'meetings_need_notes_count' => Meeting::needsNotes()->count(),
            
            'reports_due_soon' => $user->isManagement() 
                ? \App\Models\ReportingRequirement::where('status', '!=', 'submitted')
                    ->where('due_date', '<=', now()->addWeek())
                    ->with(['grant.organization'])
                    ->limit(3)
                    ->get()
                : collect(),
        ];
    }

    public function getThisWeekMeetingsProperty()
    {
        return Meeting::whereBetween('scheduled_at', [now(), now()->endOfWeek()])
            ->with(['attendees', 'organizations', 'issues'])
            ->withCount(['commitments' => fn($q) => $q->where('status', 'open')])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->groupBy(fn($m) => $m->scheduled_at->format('Y-m-d'));
    }

    public function getActiveRelationshipsProperty()
    {
        // Organizations with most activity in last 90 days
        return Organization::withCount([
                'meetings' => fn($q) => $q->where('scheduled_at', '>=', now()->subDays(90))
            ])
            ->withCount([
                'commitments' => fn($q) => $q->where('status', 'open')
            ])
            ->having('meetings_count', '>', 0)
            ->orderByDesc('meetings_count')
            ->limit(5)
            ->get();
    }

    public function getRecentInsightsProperty(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        // Topics discussed this month
        $topicCounts = Issue::withCount([
                'meetings' => fn($q) => $q->where('scheduled_at', '>=', $thirtyDaysAgo)
            ])
            ->having('meetings_count', '>', 0)
            ->orderByDesc('meetings_count')
            ->limit(5)
            ->get();

        // Top organizations this month
        $orgCounts = Organization::withCount([
                'meetings' => fn($q) => $q->where('scheduled_at', '>=', $thirtyDaysAgo)
            ])
            ->having('meetings_count', '>', 0)
            ->orderByDesc('meetings_count')
            ->limit(5)
            ->get();

        // Recent decisions
        $recentDecisions = Decision::with(['project', 'madeBy'])
            ->where('decided_at', '>=', $thirtyDaysAgo)
            ->orderByDesc('decided_at')
            ->limit(3)
            ->get();

        return [
            'topics' => $topicCounts,
            'organizations' => $orgCounts,
            'decisions' => $recentDecisions,
            'total_meetings_this_month' => Meeting::where('scheduled_at', '>=', $thirtyDaysAgo)
                ->where('scheduled_at', '<=', now())
                ->count(),
        ];
    }

    public function getQuickQueriesProperty(): array
    {
        $queries = [];
        
        // Based on upcoming meetings
        $nextMeeting = Meeting::upcoming()
            ->with('organizations')
            ->first();
        
        if ($nextMeeting && $nextMeeting->organizations->first()) {
            $orgName = $nextMeeting->organizations->first()->name;
            $queries[] = "What's our history with {$orgName}?";
        }

        // Based on active topics
        $topTopic = Issue::withCount([
                'meetings' => fn($q) => $q->where('scheduled_at', '>=', now()->subDays(30))
            ])
            ->having('meetings_count', '>', 0)
            ->orderByDesc('meetings_count')
            ->first();
        
        if ($topTopic) {
            $queries[] = "Summarize our {$topTopic->name} discussions this month";
        }

        // Standard queries
        $queries[] = "What commitments are due this week?";
        $queries[] = "What did we decide about REBOOT CONGRESS?";
        $queries[] = "Who should I talk to about AI policy?";

        return array_slice($queries, 0, 4);
    }

    public function render()
    {
        return view('livewire.knowledge-hub.index', [
            'needsAttention' => $this->needsAttention,
            'thisWeekMeetings' => $this->thisWeekMeetings,
            'activeRelationships' => $this->activeRelationships,
            'recentInsights' => $this->recentInsights,
            'quickQueries' => $this->quickQueries,
        ]);
    }
}
```

---

## Main View Template

### resources/views/livewire/knowledge-hub/index.blade.php

```blade
<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Knowledge Hub</h1>
        <p class="text-gray-600">Your organizational intelligence at a glance</p>
    </div>

    {{-- Search Section --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <form wire:submit="search" class="flex gap-3">
            <div class="flex-1 relative">
                <x-heroicon-o-magnifying-glass class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input type="text"
                       wire:model="query"
                       placeholder="Ask anything... (or search across all content)"
                       class="w-full pl-12 pr-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <button type="submit"
                    wire:click="$set('useAI', false)"
                    class="px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition">
                Search
            </button>
            <button type="submit"
                    wire:click="$set('useAI', true)"
                    class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition inline-flex items-center gap-2">
                <x-heroicon-o-sparkles class="w-5 h-5" />
                Ask AI
            </button>
        </form>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ route('knowledge-hub.prep') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition">
                <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-gray-500" />
                Prep for Meeting
            </a>
            <a href="{{ route('knowledge-hub.commitments') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition">
                <x-heroicon-o-check class="w-4 h-4 text-gray-500" />
                Find Commitments
            </a>
            <a href="{{ route('knowledge-hub.relationships') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition">
                <x-heroicon-o-link class="w-4 h-4 text-gray-500" />
                Relationship History
            </a>
            <a href="{{ route('knowledge-hub.decisions') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition">
                <x-heroicon-o-flag class="w-4 h-4 text-gray-500" />
                Recent Decisions
            </a>
            <a href="{{ route('knowledge-hub.browse') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition">
                <x-heroicon-o-folder class="w-4 h-4 text-gray-500" />
                Browse All
            </a>
        </div>
    </div>

    {{-- Search Results (when query is submitted) --}}
    @if($searchResults !== null || $aiAnswer !== null)
        <div class="mb-6">
            @include('livewire.knowledge-hub.partials.search-results')
        </div>
    @endif

    {{-- Dashboard Widgets --}}
    @if($searchResults === null && $aiAnswer === null)
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Column 1: Needs Attention --}}
            <div class="space-y-6">
                @include('livewire.knowledge-hub.partials.needs-attention-widget')
            </div>

            {{-- Column 2: This Week --}}
            <div class="space-y-6">
                @include('livewire.knowledge-hub.partials.this-week-widget')
            </div>

            {{-- Column 3: Active Relationships --}}
            <div class="space-y-6">
                @include('livewire.knowledge-hub.partials.active-relationships-widget')
            </div>
        </div>

        {{-- Bottom Row --}}
        <div class="grid gap-6 lg:grid-cols-2 mt-6">
            {{-- Recent Insights --}}
            @include('livewire.knowledge-hub.partials.recent-insights-widget')

            {{-- Quick Queries --}}
            @include('livewire.knowledge-hub.partials.quick-queries-widget')
        </div>
    @endif
</div>
```

---

## Widget Partials

### resources/views/livewire/knowledge-hub/partials/needs-attention-widget.blade.php

```blade
<div class="bg-gradient-to-br from-rose-50 to-orange-50 rounded-xl border border-rose-200 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 bg-gradient-to-r from-rose-500 to-orange-500">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <x-heroicon-s-exclamation-triangle class="w-5 h-5" />
            Needs Attention
        </h2>
        <p class="text-rose-100 text-sm">Things that need your action</p>
    </div>

    <div class="p-5 space-y-4">
        {{-- Overdue Commitments --}}
        @if($needsAttention['overdue_count'] > 0)
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 flex items-center gap-2">
                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                        Overdue Commitments
                    </span>
                    <span class="text-sm text-red-600 font-medium">{{ $needsAttention['overdue_count'] }}</span>
                </div>
                
                @foreach($needsAttention['overdue_commitments']->take(3) as $commitment)
                    <a href="{{ route('knowledge-hub.commitments', ['status' => 'overdue']) }}" 
                       class="block pl-4 py-2 text-sm text-gray-700 hover:bg-white/50 rounded-lg transition">
                        <div class="font-medium truncate">{{ Str::limit($commitment->description, 50) }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $commitment->organization?->name ?? $commitment->person?->name ?? 'Unknown' }}
                            • Due {{ $commitment->due_date->diffForHumans() }}
                        </div>
                    </a>
                @endforeach
                
                @if($needsAttention['overdue_count'] > 3)
                    <a href="{{ route('knowledge-hub.commitments', ['status' => 'overdue']) }}" 
                       class="block pl-4 text-sm text-rose-600 hover:text-rose-800">
                        View all {{ $needsAttention['overdue_count'] }} →
                    </a>
                @endif
            </div>
        @endif

        {{-- Meetings Need Notes --}}
        @if($needsAttention['meetings_need_notes_count'] > 0)
            <div class="space-y-2 pt-2 border-t border-rose-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 flex items-center gap-2">
                        <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                        Meetings Need Notes
                    </span>
                    <span class="text-sm text-amber-600 font-medium">{{ $needsAttention['meetings_need_notes_count'] }}</span>
                </div>
                
                @foreach($needsAttention['meetings_need_notes'] as $meeting)
                    <a href="{{ route('meetings.edit', $meeting) }}#notes" 
                       class="block pl-4 py-2 text-sm text-gray-700 hover:bg-white/50 rounded-lg transition">
                        <div class="font-medium truncate">{{ $meeting->title }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $meeting->scheduled_at->format('M j, Y') }}
                            @if($meeting->organizations->first())
                                • {{ $meeting->organizations->first()->name }}
                            @endif
                        </div>
                    </a>
                @endforeach
                
                @if($needsAttention['meetings_need_notes_count'] > 3)
                    <a href="{{ route('meetings.index', ['status' => 'needs_notes']) }}" 
                       class="block pl-4 text-sm text-amber-600 hover:text-amber-800">
                        View all {{ $needsAttention['meetings_need_notes_count'] }} →
                    </a>
                @endif
            </div>
        @endif

        {{-- Reports Due (Management only) --}}
        @if($needsAttention['reports_due_soon']->count() > 0)
            <div class="space-y-2 pt-2 border-t border-rose-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                        Reports Due Soon
                    </span>
                    <span class="text-sm text-blue-600 font-medium">{{ $needsAttention['reports_due_soon']->count() }}</span>
                </div>
                
                @foreach($needsAttention['reports_due_soon'] as $report)
                    <a href="{{ route('funders.grants.show', $report->grant) }}" 
                       class="block pl-4 py-2 text-sm text-gray-700 hover:bg-white/50 rounded-lg transition">
                        <div class="font-medium truncate">{{ $report->name }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $report->grant->organization->name }}
                            • Due {{ $report->due_date->format('M j') }}
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- All Clear State --}}
        @if($needsAttention['overdue_count'] === 0 && $needsAttention['meetings_need_notes_count'] === 0 && $needsAttention['reports_due_soon']->isEmpty())
            <div class="text-center py-6">
                <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-green-500 mb-2" />
                <p class="text-sm text-gray-600">All caught up! Nothing needs attention right now.</p>
            </div>
        @endif
    </div>
</div>
```

---

### resources/views/livewire/knowledge-hub/partials/this-week-widget.blade.php

```blade
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-calendar class="w-5 h-5 text-indigo-500" />
            This Week
        </h2>
        <p class="text-gray-500 text-sm">Upcoming meetings</p>
    </div>

    <div class="p-5">
        @if($thisWeekMeetings->isEmpty())
            <div class="text-center py-6">
                <x-heroicon-o-calendar class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                <p class="text-sm text-gray-500">No meetings scheduled this week</p>
                <a href="{{ route('meetings.create') }}" class="text-sm text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                    + Log a meeting
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($thisWeekMeetings as $date => $meetings)
                    @php
                        $dateObj = \Carbon\Carbon::parse($date);
                        $isToday = $dateObj->isToday();
                        $isTomorrow = $dateObj->isTomorrow();
                        $dayLabel = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $dateObj->format('l'));
                    @endphp
                    
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide {{ $isToday ? 'text-indigo-600' : 'text-gray-500' }} mb-2">
                            {{ $dayLabel }}
                            @unless($isToday || $isTomorrow)
                                <span class="font-normal normal-case">{{ $dateObj->format('M j') }}</span>
                            @endunless
                        </h3>
                        
                        <div class="space-y-2">
                            @foreach($meetings as $meeting)
                                <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('meetings.show', $meeting) }}" 
                                               class="font-medium text-gray-900 group-hover:text-indigo-600 transition block truncate">
                                                {{ $meeting->title }}
                                            </a>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $meeting->scheduled_at->format('g:i A') }}
                                                @if($meeting->attendees->count() > 0)
                                                    • {{ $meeting->attendees->count() }} attendee{{ $meeting->attendees->count() > 1 ? 's' : '' }}
                                                @endif
                                            </div>
                                            @if($meeting->organizations->first())
                                                <div class="text-xs text-gray-500">
                                                    {{ $meeting->organizations->first()->name }}
                                                </div>
                                            @endif
                                        </div>
                                        <a href="{{ route('meetings.prep', $meeting) }}"
                                           class="flex-shrink-0 px-2 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 rounded hover:bg-indigo-100 transition opacity-0 group-hover:opacity-100">
                                            Prep →
                                        </a>
                                    </div>
                                    
                                    @if($meeting->commitments_count > 0)
                                        <div class="mt-2 flex items-center gap-1 text-xs text-amber-700 bg-amber-50 px-2 py-1 rounded">
                                            <x-heroicon-s-exclamation-triangle class="w-3 h-3" />
                                            {{ $meeting->commitments_count }} open commitment{{ $meeting->commitments_count > 1 ? 's' : '' }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
        <a href="{{ route('meetings.index', ['status' => 'upcoming']) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
            View all upcoming →
        </a>
    </div>
</div>
```

---

### resources/views/livewire/knowledge-hub/partials/active-relationships-widget.blade.php

```blade
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-user-group class="w-5 h-5 text-green-500" />
            Active Relationships
        </h2>
        <p class="text-gray-500 text-sm">Most engaged this quarter</p>
    </div>

    <div class="p-5">
        @if($activeRelationships->isEmpty())
            <div class="text-center py-6">
                <x-heroicon-o-user-group class="w-12 h-12 mx-auto text-gray-300 mb-2" />
                <p class="text-sm text-gray-500">No relationship activity yet</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($activeRelationships as $org)
                    <a href="{{ route('organizations.show', $org) }}" 
                       class="block p-3 rounded-lg hover:bg-gray-50 transition group">
                        <div class="flex items-center gap-3">
                            {{-- Org Avatar --}}
                            @if($org->logo_url)
                                <img src="{{ $org->logo_url }}" 
                                     alt="{{ $org->name }}"
                                     class="w-10 h-10 rounded-lg object-cover">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                    <span class="text-sm font-bold text-gray-500">
                                        {{ substr($org->name, 0, 2) }}
                                    </span>
                                </div>
                            @endif
                            
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 group-hover:text-indigo-600 transition truncate">
                                    {{ $org->name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $org->meetings_count }} meeting{{ $org->meetings_count > 1 ? 's' : '' }}
                                    @if($org->commitments_count > 0)
                                        • {{ $org->commitments_count }} open commitment{{ $org->commitments_count > 1 ? 's' : '' }}
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Activity Indicator --}}
                            <div class="flex gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="w-2 h-2 rounded-full {{ $i <= min($org->meetings_count, 5) ? 'bg-green-500' : 'bg-gray-200' }}"></span>
                                @endfor
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
        <a href="{{ route('knowledge-hub.relationships') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
            View all relationships →
        </a>
    </div>
</div>
```

---

### resources/views/livewire/knowledge-hub/partials/recent-insights-widget.blade.php

```blade
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-light-bulb class="w-5 h-5 text-amber-500" />
            Recent Insights
        </h2>
        <p class="text-gray-500 text-sm">Activity patterns from the last 30 days</p>
    </div>

    <div class="p-5">
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Topics This Month --}}
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">Topics Discussed</h3>
                @if($recentInsights['topics']->isEmpty())
                    <p class="text-sm text-gray-500">No topics tagged yet</p>
                @else
                    <div class="space-y-2">
                        @php $maxCount = $recentInsights['topics']->max('meetings_count'); @endphp
                        @foreach($recentInsights['topics'] as $topic)
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="text-gray-700">{{ $topic->name }}</span>
                                        <span class="text-gray-500">{{ $topic->meetings_count }}</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full" 
                                             style="width: {{ ($topic->meetings_count / $maxCount) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Top Organizations --}}
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">Top Organizations</h3>
                @if($recentInsights['organizations']->isEmpty())
                    <p class="text-sm text-gray-500">No meeting activity yet</p>
                @else
                    <div class="space-y-2">
                        @foreach($recentInsights['organizations'] as $org)
                            <a href="{{ route('organizations.show', $org) }}" 
                               class="flex items-center justify-between py-1 text-sm hover:text-indigo-600 transition">
                                <span class="text-gray-700">{{ Str::limit($org->name, 25) }}</span>
                                <span class="text-gray-400">{{ $org->meetings_count }} meetings</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Decisions --}}
        @if($recentInsights['decisions']->isNotEmpty())
            <div class="mt-6 pt-6 border-t border-gray-100">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Recent Decisions</h3>
                <div class="space-y-3">
                    @foreach($recentInsights['decisions'] as $decision)
                        <div class="flex items-start gap-3">
                            <x-heroicon-s-flag class="w-4 h-4 text-indigo-500 mt-0.5 flex-shrink-0" />
                            <div>
                                <p class="text-sm text-gray-700">{{ Str::limit($decision->decision, 80) }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $decision->decided_at->format('M j') }}
                                    @if($decision->project)
                                        • {{ $decision->project->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Summary Stat --}}
        <div class="mt-6 pt-6 border-t border-gray-100 text-center">
            <div class="text-3xl font-bold text-gray-900">{{ $recentInsights['total_meetings_this_month'] }}</div>
            <div class="text-sm text-gray-500">meetings in the last 30 days</div>
        </div>
    </div>
</div>
```

---

### resources/views/livewire/knowledge-hub/partials/quick-queries-widget.blade.php

```blade
<div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl border border-indigo-200 overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-indigo-100">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-indigo-500" />
            Quick Queries
        </h2>
        <p class="text-gray-600 text-sm">Click to ask AI</p>
    </div>

    <div class="p-5">
        <div class="space-y-2">
            @foreach($quickQueries as $query)
                <button wire:click="runQuickQuery('{{ addslashes($query) }}')"
                        class="w-full text-left px-4 py-3 bg-white/70 hover:bg-white rounded-lg border border-indigo-100 hover:border-indigo-300 transition group">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-indigo-400 group-hover:text-indigo-600 transition" />
                        <span class="text-sm text-gray-700 group-hover:text-gray-900 transition">
                            "{{ $query }}"
                        </span>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Custom Query Prompt --}}
        <div class="mt-4 pt-4 border-t border-indigo-100 text-center">
            <p class="text-sm text-gray-600">
                Or type your own question in the search bar above
            </p>
        </div>
    </div>
</div>
```

---

### resources/views/livewire/knowledge-hub/partials/search-results.blade.php

```blade
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    {{-- Header --}}
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                @if($aiAnswer)
                    AI Answer
                @else
                    Search Results
                @endif
            </h2>
            <p class="text-sm text-gray-500">
                Query: "{{ $query }}"
            </p>
        </div>
        <button wire:click="clearSearch" class="text-sm text-gray-500 hover:text-gray-700">
            ✕ Clear
        </button>
    </div>

    <div class="p-5">
        {{-- Loading State --}}
        @if($searching)
            <div class="flex items-center justify-center py-12">
                <div class="flex items-center gap-3 text-gray-500">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $useAI ? 'Thinking...' : 'Searching...' }}
                </div>
            </div>
        @else
            {{-- AI Answer --}}
            @if($aiAnswer)
                <div class="prose prose-sm max-w-none mb-6">
                    {!! Str::markdown($aiAnswer) !!}
                </div>
                
                @if($searchResults && count($searchResults) > 0)
                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Sources</h3>
                        <div class="space-y-2">
                            @foreach($searchResults as $result)
                                @include('livewire.knowledge-hub.partials.search-result-item', ['result' => $result, 'compact' => true])
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                {{-- Standard Search Results --}}
                @if($searchResults && count($searchResults) > 0)
                    <div class="space-y-3">
                        @foreach($searchResults as $result)
                            @include('livewire.knowledge-hub.partials.search-result-item', ['result' => $result, 'compact' => false])
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-magnifying-glass class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                        <p class="text-gray-500">No results found for "{{ $query }}"</p>
                        <p class="text-sm text-gray-400 mt-1">Try different keywords or ask AI for help</p>
                    </div>
                @endif
            @endif
        @endif
    </div>
</div>
```

---

### resources/views/livewire/knowledge-hub/partials/search-result-item.blade.php

```blade
@php
    $typeConfig = match($result['source_type']) {
        'meeting' => ['icon' => 'calendar', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50', 'label' => 'Meeting'],
        'document' => ['icon' => 'document-text', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50', 'label' => 'Document'],
        'decision' => ['icon' => 'flag', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50', 'label' => 'Decision'],
        'note' => ['icon' => 'pencil-square', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50', 'label' => 'Note'],
        'person' => ['icon' => 'user', 'color' => 'text-green-500', 'bg' => 'bg-green-50', 'label' => 'Person'],
        'organization' => ['icon' => 'building-office', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50', 'label' => 'Organization'],
        'commitment' => ['icon' => 'check-circle', 'color' => 'text-rose-500', 'bg' => 'bg-rose-50', 'label' => 'Commitment'],
        default => ['icon' => 'document', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50', 'label' => 'Item'],
    };
@endphp

<a href="{{ $result['url'] }}" 
   class="block p-3 rounded-lg border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/30 transition">
    <div class="flex items-start gap-3">
        {{-- Type Icon --}}
        <div class="flex-shrink-0 p-2 rounded-lg {{ $typeConfig['bg'] }}">
            <x-dynamic-component :component="'heroicon-o-' . $typeConfig['icon']" 
                                 class="w-4 h-4 {{ $typeConfig['color'] }}" />
        </div>
        
        <div class="flex-1 min-w-0">
            {{-- Title --}}
            <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900 truncate">{{ $result['title'] }}</span>
                <span class="text-xs px-1.5 py-0.5 rounded {{ $typeConfig['bg'] }} {{ $typeConfig['color'] }}">
                    {{ $typeConfig['label'] }}
                </span>
            </div>
            
            {{-- Snippet (if not compact) --}}
            @unless($compact)
                @if(isset($result['snippet']))
                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $result['snippet'] }}</p>
                @endif
            @endunless
            
            {{-- Metadata --}}
            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                @if(isset($result['date']))
                    <span>{{ \Carbon\Carbon::parse($result['date'])->format('M j, Y') }}</span>
                @endif
                @if(isset($result['project']))
                    <span class="text-gray-300">•</span>
                    <span>{{ $result['project'] }}</span>
                @endif
                @if(isset($result['organization']))
                    <span class="text-gray-300">•</span>
                    <span>{{ $result['organization'] }}</span>
                @endif
            </div>
        </div>
    </div>
</a>
```

---

## Knowledge Hub Service

### app/Services/KnowledgeHubService.php

```php
<?php

namespace App\Services;

use App\Models\KbEntry;
use App\Models\Meeting;
use App\Models\Commitment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class KnowledgeHubService
{
    /**
     * Perform a standard text search across the knowledge base
     */
    public function search(string $query, int $limit = 20): array
    {
        $results = KbEntry::whereFullText(['title', 'content'], $query)
            ->orWhere('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->orderByDesc('source_date')
            ->limit($limit)
            ->get();

        return $results->map(fn($entry) => $this->formatSearchResult($entry))->toArray();
    }

    /**
     * Query the knowledge base with AI synthesis
     */
    public function queryWithAI(string $query): array
    {
        // Get relevant context
        $entries = KbEntry::whereFullText(['title', 'content'], $query)
            ->orWhere('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->orderByDesc('source_date')
            ->limit(10)
            ->get();

        if ($entries->isEmpty()) {
            return [
                'answer' => "I couldn't find any information about that in the knowledge base. Try a different search term or browse the available content.",
                'sources' => [],
            ];
        }

        // Build context for AI
        $context = $entries->map(function ($entry) {
            return [
                'title' => $entry->title,
                'type' => $entry->source_type,
                'date' => $entry->source_date?->format('Y-m-d'),
                'content' => Str::limit($entry->content, 1000),
            ];
        })->toArray();

        // Call Claude API
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 1024,
            'system' => $this->getSystemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->buildQueryPrompt($query, $context),
                ],
            ],
        ]);

        $aiResponse = $response->json('content.0.text', 'Unable to generate a response.');

        return [
            'answer' => $aiResponse,
            'sources' => $entries->map(fn($entry) => $this->formatSearchResult($entry))->toArray(),
        ];
    }

    /**
     * Generate meeting prep data
     */
    public function generateMeetingPrep(Meeting $meeting): array
    {
        $meeting->load(['attendees.organization', 'organizations', 'issues', 'projects']);
        
        $prep = [];

        // Get relationship history
        $orgIds = $meeting->organizations->pluck('id');
        $personIds = $meeting->attendees->pluck('id');

        $previousMeetings = Meeting::where('scheduled_at', '<', $meeting->scheduled_at)
            ->where(function ($q) use ($orgIds, $personIds) {
                $q->whereHas('organizations', fn($o) => $o->whereIn('organizations.id', $orgIds))
                  ->orWhereHas('attendees', fn($a) => $a->whereIn('people.id', $personIds));
            })
            ->with(['organizations', 'attendees'])
            ->orderByDesc('scheduled_at')
            ->limit(10)
            ->get();

        // Last meeting summary
        if ($previousMeetings->isNotEmpty()) {
            $lastMeeting = $previousMeetings->first();
            $prep['last_meeting'] = [
                'id' => $lastMeeting->id,
                'title' => $lastMeeting->title,
                'date' => $lastMeeting->scheduled_at->format('F j, Y'),
                'summary' => $lastMeeting->notes 
                    ? Str::limit(strip_tags($lastMeeting->notes), 500)
                    : 'No notes recorded.',
            ];
        }

        // Relationship history narrative
        $prep['relationship_history'] = $this->buildRelationshipHistory($meeting->organizations->first(), $previousMeetings);

        // Open commitments
        $prep['open_commitments'] = Commitment::where('status', 'open')
            ->where(function ($q) use ($orgIds, $personIds) {
                $q->whereIn('organization_id', $orgIds)
                  ->orWhereIn('person_id', $personIds);
            })
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'description' => $c->description,
                'direction' => $c->direction === 'from_us' ? 'We committed' : 'They committed',
                'due_date' => $c->due_date?->format('M j, Y'),
            ])
            ->toArray();

        // Generate talking points using AI
        $prep['talking_points'] = $this->generateTalkingPoints($meeting, $prep);

        return $prep;
    }

    /**
     * Format a KB entry as a search result
     */
    protected function formatSearchResult(KbEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'source_type' => $entry->source_type,
            'source_id' => $entry->source_id,
            'title' => $entry->title,
            'snippet' => Str::limit(strip_tags($entry->content), 200),
            'date' => $entry->source_date?->format('Y-m-d'),
            'project' => $entry->project?->name,
            'organization' => $entry->organization?->name,
            'url' => $this->getSourceUrl($entry),
        ];
    }

    /**
     * Get the URL for a source entry
     */
    protected function getSourceUrl(KbEntry $entry): string
    {
        return match($entry->source_type) {
            'meeting' => route('meetings.show', $entry->source_id),
            'document' => route('documents.show', $entry->source_id),
            'decision' => route('knowledge-hub.decisions', ['highlight' => $entry->source_id]),
            'person' => route('people.show', $entry->source_id),
            'organization' => route('organizations.show', $entry->source_id),
            'project' => route('projects.show', $entry->source_id),
            'commitment' => route('knowledge-hub.commitments', ['highlight' => $entry->source_id]),
            default => '#',
        };
    }

    /**
     * Get the system prompt for AI queries
     */
    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an AI assistant helping query an organization's knowledge base. Your role is to synthesize information from the provided context and answer questions accurately.

Guidelines:
- Only use information from the provided context
- If the context doesn't contain enough information, say so
- Cite specific sources when making claims (e.g., "According to the Dec 15 meeting...")
- Be concise but thorough
- If you find conflicting information, note the discrepancy
- Suggest follow-up queries if relevant
PROMPT;
    }

    /**
     * Build the query prompt with context
     */
    protected function buildQueryPrompt(string $query, array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
User Question: {$query}

Available Context:
{$contextJson}

Please answer the user's question based on the context provided. If the context doesn't contain enough information to fully answer, acknowledge what you can answer and what information is missing.
PROMPT;
    }

    /**
     * Build relationship history narrative
     */
    protected function buildRelationshipHistory(?Organization $org, Collection $meetings): string
    {
        if (!$org || $meetings->isEmpty()) {
            return "This is your first interaction with this organization.";
        }

        $count = $meetings->count();
        $firstMeeting = $meetings->last();
        $span = $firstMeeting->scheduled_at->diffForHumans();

        return "You've had {$count} previous meeting" . ($count > 1 ? 's' : '') . " with {$org->name}, starting {$span}. " .
               "Topics have included: " . $meetings->flatMap->issues->pluck('name')->unique()->take(5)->join(', ') . ".";
    }

    /**
     * Generate AI-powered talking points
     */
    protected function generateTalkingPoints(Meeting $meeting, array $prep): array
    {
        // For now, generate rule-based talking points
        // Could be enhanced with AI later
        $points = [];

        // Open commitments
        if (!empty($prep['open_commitments'])) {
            $ourCommitments = collect($prep['open_commitments'])->where('direction', 'We committed')->count();
            if ($ourCommitments > 0) {
                $points[] = "Follow up on {$ourCommitments} commitment" . ($ourCommitments > 1 ? 's' : '') . " we made";
            }
        }

        // Projects
        if ($meeting->projects->isNotEmpty()) {
            $points[] = "Update on " . $meeting->projects->pluck('name')->join(', ');
        }

        // Issues
        if ($meeting->issues->isNotEmpty()) {
            $points[] = "Discuss " . $meeting->issues->pluck('name')->join(', ');
        }

        // Default
        if (empty($points)) {
            $points[] = "Relationship building and updates";
        }

        return $points;
    }
}
```

---

## Routes

### routes/web.php

```php
Route::middleware(['auth'])->prefix('knowledge-hub')->name('knowledge-hub.')->group(function () {
    Route::get('/', \App\Livewire\KnowledgeHub\Index::class)->name('index');
    Route::get('/prep', \App\Livewire\KnowledgeHub\PrepForMeeting::class)->name('prep');
    Route::get('/commitments', \App\Livewire\KnowledgeHub\Commitments::class)->name('commitments');
    Route::get('/relationships', \App\Livewire\KnowledgeHub\Relationships::class)->name('relationships');
    Route::get('/decisions', \App\Livewire\KnowledgeHub\Decisions::class)->name('decisions');
    Route::get('/browse', \App\Livewire\KnowledgeHub\Browse::class)->name('browse');
});
```

---

## Quick Action Pages (Stubs)

These pages are linked from the quick actions. Here are basic stubs:

### app/Livewire/KnowledgeHub/Commitments.php

```php
<?php

namespace App\Livewire\KnowledgeHub;

use App\Models\Commitment;
use Livewire\Component;
use Livewire\WithPagination;

class Commitments extends Component
{
    use WithPagination;

    public string $status = 'open'; // open, overdue, completed, all
    public string $direction = ''; // from_us, to_us, or empty for all
    public ?int $organizationId = null;
    public ?int $projectId = null;
    public string $search = '';

    protected $queryString = ['status', 'direction', 'organizationId', 'projectId', 'search'];

    public function getCommitmentsProperty()
    {
        $query = Commitment::with(['organization', 'person', 'meeting', 'project', 'assignedTo']);

        // Status filter
        if ($this->status === 'open') {
            $query->where('status', 'open');
        } elseif ($this->status === 'overdue') {
            $query->where('status', 'open')->where('due_date', '<', now());
        } elseif ($this->status === 'completed') {
            $query->where('status', 'completed');
        }

        // Direction filter
        if ($this->direction) {
            $query->where('direction', $this->direction);
        }

        // Other filters
        if ($this->organizationId) {
            $query->where('organization_id', $this->organizationId);
        }
        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }
        if ($this->search) {
            $query->where('description', 'like', "%{$this->search}%");
        }

        return $query->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                     ->orderBy('due_date')
                     ->paginate(20);
    }

    public function render()
    {
        return view('livewire.knowledge-hub.commitments', [
            'commitments' => $this->commitments,
        ]);
    }
}
```

### app/Livewire/KnowledgeHub/Decisions.php

```php
<?php

namespace App\Livewire\KnowledgeHub;

use App\Models\Decision;
use Livewire\Component;
use Livewire\WithPagination;

class Decisions extends Component
{
    use WithPagination;

    public ?int $projectId = null;
    public string $period = 'month'; // week, month, quarter, year, all
    public string $search = '';

    protected $queryString = ['projectId', 'period', 'search'];

    public function getDecisionsProperty()
    {
        $query = Decision::with(['project', 'madeBy', 'meeting']);

        // Period filter
        $query->when($this->period === 'week', fn($q) => $q->where('decided_at', '>=', now()->subWeek()))
              ->when($this->period === 'month', fn($q) => $q->where('decided_at', '>=', now()->subMonth()))
              ->when($this->period === 'quarter', fn($q) => $q->where('decided_at', '>=', now()->subQuarter()))
              ->when($this->period === 'year', fn($q) => $q->where('decided_at', '>=', now()->subYear()));

        // Project filter
        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('decision', 'like', "%{$this->search}%")
                  ->orWhere('rationale', 'like', "%{$this->search}%");
            });
        }

        return $query->orderByDesc('decided_at')->paginate(20);
    }

    public function render()
    {
        return view('livewire.knowledge-hub.decisions', [
            'decisions' => $this->decisions,
        ]);
    }
}
```

### app/Livewire/KnowledgeHub/Relationships.php

```php
<?php

namespace App\Livewire\KnowledgeHub;

use App\Models\Organization;
use Livewire\Component;

class Relationships extends Component
{
    public string $search = '';
    public ?int $selectedOrgId = null;

    public function getOrganizationsProperty()
    {
        return Organization::withCount([
                'meetings' => fn($q) => $q->where('scheduled_at', '>=', now()->subYear())
            ])
            ->withCount(['commitments' => fn($q) => $q->where('status', 'open')])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('meetings_count')
            ->limit(50)
            ->get();
    }

    public function selectOrg(int $orgId)
    {
        $this->selectedOrgId = $orgId;
    }

    public function getSelectedOrgProperty()
    {
        if (!$this->selectedOrgId) return null;
        
        return Organization::with([
            'meetings' => fn($q) => $q->orderByDesc('scheduled_at')->limit(10),
            'people',
            'commitments' => fn($q) => $q->where('status', 'open'),
        ])->find($this->selectedOrgId);
    }

    public function render()
    {
        return view('livewire.knowledge-hub.relationships', [
            'organizations' => $this->organizations,
            'selectedOrg' => $this->selectedOrg,
        ]);
    }
}
```

---

## KB Entry Indexing

### app/Observers/MeetingObserver.php

```php
<?php

namespace App\Observers;

use App\Models\Meeting;
use App\Models\KbEntry;

class MeetingObserver
{
    public function saved(Meeting $meeting): void
    {
        KbEntry::updateOrCreate(
            [
                'source_type' => 'meeting',
                'source_id' => $meeting->id,
            ],
            [
                'title' => $meeting->title,
                'content' => $meeting->notes ?? '',
                'summary' => $meeting->ai_summary,
                'source_date' => $meeting->scheduled_at,
                'project_id' => $meeting->projects()->first()?->id,
                'organization_id' => $meeting->organizations()->first()?->id,
                'metadata' => [
                    'attendees' => $meeting->attendees->pluck('name')->toArray(),
                    'issues' => $meeting->issues->pluck('name')->toArray(),
                ],
            ]
        );
    }

    public function deleted(Meeting $meeting): void
    {
        KbEntry::where('source_type', 'meeting')
               ->where('source_id', $meeting->id)
               ->delete();
    }
}
```

Register in AppServiceProvider:

```php
use App\Models\Meeting;
use App\Observers\MeetingObserver;

public function boot(): void
{
    Meeting::observe(MeetingObserver::class);
    // Add observers for other models: Decision, Document, Project, etc.
}
```

---

## Summary

### Files to Create/Update

1. **Livewire Components**
   - `app/Livewire/KnowledgeHub/Index.php` (main hub)
   - `app/Livewire/KnowledgeHub/Commitments.php`
   - `app/Livewire/KnowledgeHub/Decisions.php`
   - `app/Livewire/KnowledgeHub/Relationships.php`
   - `app/Livewire/KnowledgeHub/Browse.php`
   - `app/Livewire/KnowledgeHub/PrepForMeeting.php`

2. **Views**
   - `resources/views/livewire/knowledge-hub/index.blade.php`
   - `resources/views/livewire/knowledge-hub/partials/needs-attention-widget.blade.php`
   - `resources/views/livewire/knowledge-hub/partials/this-week-widget.blade.php`
   - `resources/views/livewire/knowledge-hub/partials/active-relationships-widget.blade.php`
   - `resources/views/livewire/knowledge-hub/partials/recent-insights-widget.blade.php`
   - `resources/views/livewire/knowledge-hub/partials/quick-queries-widget.blade.php`
   - `resources/views/livewire/knowledge-hub/partials/search-results.blade.php`
   - `resources/views/livewire/knowledge-hub/partials/search-result-item.blade.php`
   - `resources/views/livewire/knowledge-hub/commitments.blade.php`
   - `resources/views/livewire/knowledge-hub/decisions.blade.php`
   - `resources/views/livewire/knowledge-hub/relationships.blade.php`

3. **Services**
   - `app/Services/KnowledgeHubService.php`

4. **Database**
   - Migration for `commitments` table (if not exists)
   - Migration for `decisions` table (if not exists)
   - Migration for `issues` table (if not exists)
   - Migration for `kb_entries` table

5. **Observers**
   - `app/Observers/MeetingObserver.php`
   - Similar observers for Decision, Document, Project, etc.

6. **Routes**
   - Add knowledge-hub routes group

### Testing Checklist

- [ ] Needs Attention widget shows overdue commitments
- [ ] Needs Attention widget shows meetings needing notes
- [ ] Needs Attention widget shows reports due (management only)
- [ ] This Week widget shows upcoming meetings grouped by day
- [ ] This Week widget shows commitment counts on meetings
- [ ] Active Relationships widget shows top orgs with activity indicators
- [ ] Recent Insights shows topic and org activity
- [ ] Recent Insights shows recent decisions
- [ ] Quick Queries are contextual based on upcoming meetings/topics
- [ ] Search returns relevant results
- [ ] AI query synthesizes answer from context
- [ ] Search results link to correct source pages
- [ ] All quick action pages work (Commitments, Decisions, Relationships, Browse)
- [ ] KB entries are indexed when meetings/documents/decisions are created
