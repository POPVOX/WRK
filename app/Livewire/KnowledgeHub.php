<?php

namespace App\Livewire;

use App\Models\Commitment;
use App\Models\Decision;
use App\Models\Issue;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Models\ReportingRequirement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class KnowledgeHub extends Component
{
    // Search state
    public string $query = '';
    public bool $useAI = false;
    public bool $searching = false;
    public ?array $searchResults = null;
    public ?string $aiAnswer = null;

    protected $queryString = [
        'query' => ['except' => ''],
    ];

    public function search(): void
    {
        if (empty(trim($this->query))) {
            $this->searchResults = null;
            $this->aiAnswer = null;
            return;
        }

        $this->searching = true;

        if ($this->useAI) {
            $this->queryWithAI();
        } else {
            $this->performSearch();
        }

        $this->searching = false;
    }

    protected function performSearch(): void
    {
        $results = [];
        $term = '%' . $this->query . '%';

        // Search Meetings
        $meetings = Meeting::where('title', 'like', $term)
            ->orWhere('raw_notes', 'like', $term)
            ->orWhere('ai_summary', 'like', $term)
            ->orderByDesc('meeting_date')
            ->take(10)
            ->get();

        foreach ($meetings as $meeting) {
            $results[] = [
                'source_type' => 'meeting',
                'title' => $meeting->title ?: 'Meeting on ' . $meeting->meeting_date?->format('M j, Y'),
                'snippet' => \Str::limit($meeting->raw_notes ?? $meeting->ai_summary, 150),
                'url' => route('meetings.show', $meeting),
                'date' => $meeting->meeting_date,
                'organization' => $meeting->organizations->first()?->name,
            ];
        }

        // Search Projects
        $projects = Project::where('name', 'like', $term)
            ->orWhere('description', 'like', $term)
            ->take(5)
            ->get();

        foreach ($projects as $project) {
            $results[] = [
                'source_type' => 'project',
                'title' => $project->name,
                'snippet' => \Str::limit($project->description, 150),
                'url' => route('projects.show', $project),
                'date' => $project->created_at,
            ];
        }

        // Search People
        $people = Person::where('name', 'like', $term)
            ->orWhere('title', 'like', $term)
            ->take(5)
            ->get();

        foreach ($people as $person) {
            $results[] = [
                'source_type' => 'person',
                'title' => $person->name,
                'snippet' => $person->title . ($person->organization ? ' at ' . $person->organization->name : ''),
                'url' => route('contacts.show', $person),
                'organization' => $person->organization?->name,
            ];
        }

        // Search Organizations
        $orgs = Organization::where('name', 'like', $term)
            ->orWhere('description', 'like', $term)
            ->take(5)
            ->get();

        foreach ($orgs as $org) {
            $results[] = [
                'source_type' => 'organization',
                'title' => $org->name,
                'snippet' => \Str::limit($org->description, 150),
                'url' => route('organizations.show', $org),
            ];
        }

        // Search Commitments
        $commitments = Commitment::where('description', 'like', $term)
            ->with(['organization', 'person', 'meeting'])
            ->take(5)
            ->get();

        foreach ($commitments as $commitment) {
            $results[] = [
                'source_type' => 'commitment',
                'title' => \Str::limit($commitment->description, 80),
                'snippet' => ($commitment->direction === 'from_us' ? 'We committed to ' : 'They committed to ') . $commitment->context_name,
                'url' => $commitment->meeting ? route('meetings.show', $commitment->meeting) : '#',
                'date' => $commitment->due_date,
                'organization' => $commitment->organization?->name,
            ];
        }

        // Search Decisions
        $decisions = Decision::where('decision', 'like', $term)
            ->orWhere('rationale', 'like', $term)
            ->with(['project', 'meeting'])
            ->take(5)
            ->get();

        foreach ($decisions as $decision) {
            $results[] = [
                'source_type' => 'decision',
                'title' => \Str::limit($decision->decision, 80),
                'snippet' => $decision->rationale ? \Str::limit($decision->rationale, 100) : null,
                'url' => $decision->meeting ? route('meetings.show', $decision->meeting) : ($decision->project ? route('projects.show', $decision->project) : '#'),
                'date' => $decision->decided_at,
                'project' => $decision->project?->name,
            ];
        }

        $this->searchResults = $results;
        $this->aiAnswer = null;
    }

    protected function queryWithAI(): void
    {
        $context = $this->buildContext();

        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            $this->aiAnswer = 'AI features are not configured. Please set up the Anthropic API key.';
            $this->searchResults = [];
            return;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                        'model' => 'claude-sonnet-4-20250514',
                        'max_tokens' => 1500,
                        'system' => "You are a helpful assistant for POPVOX Foundation staff. Answer questions based on the organizational knowledge provided. Be concise but thorough. If the context doesn't contain enough information, say so. Cite specific meetings, projects, or people when relevant.",
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => "Question: {$this->query}\n\nOrganizational Context:\n{$context}"
                            ]
                        ],
                    ]);

            if ($response->successful()) {
                $this->aiAnswer = $response->json()['content'][0]['text'] ?? 'No response generated.';
                // Also do a regular search to show sources
                $this->performSearch();
            } else {
                $this->aiAnswer = 'Unable to generate AI response. Please try a keyword search instead.';
                $this->searchResults = [];
            }
        } catch (\Exception $e) {
            $this->aiAnswer = 'Error connecting to AI service: ' . $e->getMessage();
            $this->searchResults = [];
        }
    }

    protected function buildContext(): string
    {
        $context = [];
        $term = '%' . $this->query . '%';

        // Get relevant meetings
        $meetings = Meeting::where('title', 'like', $term)
            ->orWhere('raw_notes', 'like', $term)
            ->orWhere('ai_summary', 'like', $term)
            ->orderByDesc('meeting_date')
            ->take(5)
            ->get();

        foreach ($meetings as $m) {
            $orgs = $m->organizations->pluck('name')->join(', ');
            $context[] = "MEETING ({$m->meeting_date?->format('M j, Y')}) - {$m->title}\nOrganizations: {$orgs}\nNotes: " . \Str::limit($m->raw_notes, 500);
        }

        // Get relevant projects
        $projects = Project::where('name', 'like', $term)
            ->orWhere('description', 'like', $term)
            ->take(3)
            ->get();

        foreach ($projects as $p) {
            $context[] = "PROJECT: {$p->name}\nStatus: {$p->status}\nDescription: " . \Str::limit($p->description, 300);
        }

        // Get relevant commitments
        $commitments = Commitment::where('description', 'like', $term)
            ->with(['organization', 'person'])
            ->take(3)
            ->get();

        foreach ($commitments as $c) {
            $dir = $c->direction === 'from_us' ? 'We committed' : 'They committed';
            $context[] = "COMMITMENT ({$c->due_date?->format('M j, Y')}): {$dir} - {$c->description} | Status: {$c->status}";
        }

        return implode("\n\n---\n\n", $context) ?: 'No relevant context found in the knowledge base.';
    }

    public function runQuickQuery(string $queryText): void
    {
        $this->query = $queryText;
        $this->useAI = true;
        $this->search();
    }

    public function clearSearch(): void
    {
        $this->query = '';
        $this->searchResults = null;
        $this->aiAnswer = null;
    }

    // Computed: Needs Attention
    public function getNeedsAttentionProperty(): array
    {
        $user = Auth::user();

        return [
            'overdue_commitments' => Commitment::where('status', 'open')
                ->where('direction', 'from_us')
                ->where('due_date', '<', now())
                ->with(['organization', 'person', 'meeting'])
                ->orderBy('due_date')
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

            'reports_due_soon' => $user?->is_admin
                ? ReportingRequirement::where('status', '!=', 'submitted')
                    ->where('due_date', '<=', now()->addWeek())
                    ->with(['grant.funder'])
                    ->limit(3)
                    ->get()
                : collect(),
        ];
    }

    // Computed: This Week's Meetings
    public function getThisWeekMeetingsProperty()
    {
        return Meeting::whereBetween('meeting_date', [now()->startOfDay(), now()->endOfWeek()])
            ->with(['people', 'organizations', 'issues'])
            ->orderBy('meeting_date')
            ->limit(8)
            ->get()
            ->groupBy(fn($m) => $m->meeting_date->format('Y-m-d'));
    }

    // Computed: Active Relationships
    public function getActiveRelationshipsProperty()
    {
        return Organization::withCount([
            'meetings' => fn($q) => $q->where('meeting_date', '>=', now()->subDays(90))
        ])
            ->withCount([
                'commitments' => fn($q) => $q->where('status', 'open')
            ])
            ->get()
            ->filter(fn($org) => $org->meetings_count > 0)
            ->sortByDesc('meetings_count')
            ->take(5)
            ->values();
    }

    // Computed: Recent Insights
    public function getRecentInsightsProperty(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        // Topics discussed this month
        $topicCounts = Issue::withCount([
            'meetings' => fn($q) => $q->where('meeting_date', '>=', $thirtyDaysAgo)
        ])
            ->get()
            ->filter(fn($t) => $t->meetings_count > 0)
            ->sortByDesc('meetings_count')
            ->take(5)
            ->values();

        // Top organizations this month
        $orgCounts = Organization::withCount([
            'meetings' => fn($q) => $q->where('meeting_date', '>=', $thirtyDaysAgo)
        ])
            ->get()
            ->filter(fn($o) => $o->meetings_count > 0)
            ->sortByDesc('meetings_count')
            ->take(5)
            ->values();

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
            'total_meetings_this_month' => Meeting::where('meeting_date', '>=', $thirtyDaysAgo)
                ->where('meeting_date', '<=', now())
                ->count(),
        ];
    }

    // Computed: Quick Queries
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
            'meetings' => fn($q) => $q->where('meeting_date', '>=', now()->subDays(30))
        ])
            ->get()
            ->filter(fn($t) => $t->meetings_count > 0)
            ->sortByDesc('meetings_count')
            ->first();

        if ($topTopic) {
            $queries[] = "Summarize our {$topTopic->name} discussions this month";
        }

        // Standard queries
        $queries[] = "What commitments are due this week?";
        $queries[] = "What are our current project priorities?";

        return array_slice($queries, 0, 4);
    }

    public function render()
    {
        return view('livewire.knowledge-hub', [
            'needsAttention' => $this->needsAttention,
            'thisWeekMeetings' => $this->thisWeekMeetings,
            'activeRelationships' => $this->activeRelationships,
            'recentInsights' => $this->recentInsights,
            'quickQueries' => $this->quickQueries,
        ])->title('Knowledge Hub');
    }
}
