<?php

namespace App\Livewire\Admin;

use App\Jobs\AnalyzeFeedback;
use App\Models\Feedback;
use App\Models\User;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Feedback Management')]
class FeedbackManagement extends Component
{
    use WithPagination;

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterType = '';

    #[Url]
    public string $filterPriority = '';

    public string $search = '';

    // Detail modal
    public bool $showDetailModal = false;

    public ?int $viewingFeedbackId = null;

    public ?Feedback $viewingFeedback = null;

    // Editing
    public string $editStatus = '';

    public string $editPriority = '';

    public ?int $editAssignedTo = null;

    public string $editAdminNotes = '';

    // AI Insights
    public bool $showInsightsModal = false;

    public array $aiInsights = [];

    public bool $isGeneratingInsights = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function getStatsProperty(): array
    {
        return [
            'total' => Feedback::count(),
            'new' => Feedback::new()->count(),
            'bugs' => Feedback::bugs()->unresolved()->count(),
            'suggestions' => Feedback::suggestions()->unresolved()->count(),
            'this_week' => Feedback::where('created_at', '>=', now()->subWeek())->count(),
        ];
    }

    public function viewFeedback(int $id): void
    {
        $this->viewingFeedback = Feedback::with(['user', 'assignee'])->find($id);
        if (! $this->viewingFeedback) {
            return;
        }

        $this->viewingFeedbackId = $id;
        $this->editStatus = $this->viewingFeedback->status;
        $this->editPriority = $this->viewingFeedback->priority ?? '';
        $this->editAssignedTo = $this->viewingFeedback->assigned_to;
        $this->editAdminNotes = $this->viewingFeedback->admin_notes ?? '';
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingFeedbackId = null;
        $this->viewingFeedback = null;
    }

    public function saveFeedbackChanges(): void
    {
        if (! $this->viewingFeedback) {
            return;
        }

        $this->viewingFeedback->update([
            'status' => $this->editStatus,
            'priority' => $this->editPriority ?: null,
            'assigned_to' => $this->editAssignedTo ?: null,
            'admin_notes' => $this->editAdminNotes ?: null,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Feedback updated!');
    }

    public function updateStatus(int $id, string $status): void
    {
        $feedback = Feedback::find($id);
        if ($feedback && array_key_exists($status, Feedback::STATUSES)) {
            $feedback->update(['status' => $status]);
            $this->dispatch('notify', type: 'success', message: 'Status updated!');
        }
    }

    public function reanalyze(int $id): void
    {
        $feedback = Feedback::find($id);
        if ($feedback) {
            $feedback->update(['ai_analyzed_at' => null]);
            AnalyzeFeedback::dispatch($id);
            $this->dispatch('notify', type: 'info', message: 'AI analysis queued.');
        }
    }

    public function deleteFeedback(int $id): void
    {
        $feedback = Feedback::find($id);
        if ($feedback) {
            if ($feedback->screenshot_path) {
                Storage::disk('public')->delete($feedback->screenshot_path);
            }
            $feedback->delete();
            $this->closeDetailModal();
            $this->dispatch('notify', type: 'success', message: 'Feedback deleted.');
        }
    }

    public function generateProductInsights(): void
    {
        $this->isGeneratingInsights = true;
        $this->showInsightsModal = true;

        try {
            // Gather recent feedback for analysis
            $recentFeedback = Feedback::with('user')
                ->where('created_at', '>=', now()->subMonth())
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            if ($recentFeedback->isEmpty()) {
                $this->aiInsights = [
                    'summary' => 'No feedback collected in the last month.',
                    'themes' => [],
                    'priorities' => [],
                    'recommendations' => [],
                ];
                $this->isGeneratingInsights = false;

                return;
            }

            $client = app(AnthropicClient::class);

            $feedbackSummary = $recentFeedback->map(function ($f) {
                return [
                    'type' => $f->feedback_type,
                    'category' => $f->category,
                    'message' => $f->message,
                    'page' => $f->page_route ?: $f->page_url,
                    'priority' => $f->priority,
                    'status' => $f->status,
                    'ai_tags' => $f->ai_tags,
                ];
            })->toArray();

            $prompt = $this->buildInsightsPrompt($feedbackSummary, $recentFeedback->count());

            $response = $client->message(
                system: 'You are a product strategist analyzing user feedback to provide actionable product recommendations. Respond only with valid JSON.',
                messages: [['role' => 'user', 'content' => $prompt]],
                maxTokens: 2000
            );

            $content = $response['content'][0]['text'] ?? '';

            // Extract JSON
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $jsonStr = $matches[1];
            } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
                $jsonStr = $matches[0];
            } else {
                $jsonStr = $content;
            }

            $this->aiInsights = json_decode($jsonStr, true) ?: [
                'error' => 'Could not parse AI response',
            ];
        } catch (\Exception $e) {
            $this->aiInsights = [
                'error' => 'AI analysis failed: '.$e->getMessage(),
            ];
        }

        $this->isGeneratingInsights = false;
    }

    protected function buildInsightsPrompt(array $feedbackData, int $totalCount): string
    {
        $feedbackJson = json_encode($feedbackData, JSON_PRETTY_PRINT);

        $stats = [
            'bugs' => collect($feedbackData)->where('type', 'bug')->count(),
            'suggestions' => collect($feedbackData)->where('type', 'suggestion')->count(),
            'compliments' => collect($feedbackData)->where('type', 'compliment')->count(),
        ];

        return <<<PROMPT
Analyze this collection of {$totalCount} user feedback items from our beta product:

**Feedback Statistics:**
- Bug Reports: {$stats['bugs']}
- Feature Suggestions: {$stats['suggestions']}
- Compliments: {$stats['compliments']}

**Feedback Data:**
{$feedbackJson}

Provide a comprehensive product analysis in this exact JSON format:
```json
{
    "executive_summary": "2-3 sentence overview of the feedback trends and overall product health",
    "sentiment_breakdown": {
        "positive_percentage": 0,
        "negative_percentage": 0,
        "neutral_percentage": 0
    },
    "top_themes": [
        {
            "theme": "Theme name",
            "frequency": "high|medium|low",
            "sentiment": "positive|negative|mixed",
            "description": "Brief description"
        }
    ],
    "critical_issues": [
        {
            "issue": "Issue description",
            "affected_pages": ["page1", "page2"],
            "urgency": "critical|high|medium",
            "suggested_fix": "Brief fix suggestion"
        }
    ],
    "feature_requests": [
        {
            "feature": "Feature name",
            "demand_level": "high|medium|low",
            "effort_estimate": "quick-fix|medium|large",
            "business_impact": "Brief impact description"
        }
    ],
    "recommended_actions": [
        {
            "priority": 1,
            "action": "Specific action to take",
            "rationale": "Why this matters",
            "effort": "quick-fix|medium|large",
            "impact": "high|medium|low"
        }
    ],
    "positive_highlights": ["What users love 1", "What users love 2"],
    "pages_needing_attention": ["page1", "page2"]
}
```
PROMPT;
    }

    public function closeInsightsModal(): void
    {
        $this->showInsightsModal = false;
        $this->aiInsights = [];
    }

    /**
     * Export all feedback as JSON for sharing with developers.
     */
    public function exportJson()
    {
        $feedback = Feedback::with(['user', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'date' => $f->created_at->toIso8601String(),
                    'type' => $f->feedback_type,
                    'status' => $f->status,
                    'priority' => $f->priority,
                    'message' => $f->message,
                    'page_url' => $f->page_url,
                    'page_route' => $f->page_route,
                    'browser' => $f->browser,
                    'device' => $f->device,
                    'screen_size' => $f->screen_size,
                    'user' => $f->user ? [
                        'id' => $f->user->id,
                        'name' => $f->user->name,
                        'email' => $f->user->email,
                    ] : null,
                    'assigned_to' => $f->assignee?->name,
                    'admin_notes' => $f->admin_notes,
                    'ai_analysis' => $f->ai_analysis,
                    'ai_tags' => $f->ai_tags,
                    'screenshot_path' => $f->screenshot_path ? url('storage/'.$f->screenshot_path) : null,
                ];
            });

        $exportData = [
            'exported_at' => now()->toIso8601String(),
            'total_count' => $feedback->count(),
            'summary' => [
                'by_type' => $feedback->groupBy('type')->map->count(),
                'by_status' => $feedback->groupBy('status')->map->count(),
                'by_priority' => $feedback->groupBy('priority')->map->count(),
            ],
            'feedback' => $feedback->toArray(),
        ];

        $filename = 'feedback-export-'.now()->format('Y-m-d-His').'.json';
        $content = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Export all feedback as CSV.
     */
    public function exportCsv()
    {
        $feedback = Feedback::with(['user', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'feedback-export-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($feedback) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'ID',
                'Date',
                'Type',
                'Status',
                'Priority',
                'Message',
                'Page URL',
                'Page Route',
                'User Name',
                'User Email',
                'Browser',
                'Device',
                'Assigned To',
                'Admin Notes',
                'AI Tags',
            ]);

            // Data rows
            foreach ($feedback as $f) {
                fputcsv($handle, [
                    $f->id,
                    $f->created_at->format('Y-m-d H:i:s'),
                    $f->feedback_type,
                    $f->status,
                    $f->priority,
                    $f->message,
                    $f->page_url,
                    $f->page_route,
                    $f->user?->name,
                    $f->user?->email,
                    $f->browser,
                    $f->device,
                    $f->assignee?->name,
                    $f->admin_notes,
                    is_array($f->ai_tags) ? implode(', ', $f->ai_tags) : $f->ai_tags,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export filtered feedback based on current filters.
     */
    public function exportFilteredJson()
    {
        $feedback = Feedback::query()
            ->with(['user', 'assignee'])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('message', 'like', '%'.$this->search.'%')
                        ->orWhere('page_url', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType, fn ($q) => $q->where('feedback_type', $this->filterType))
            ->when($this->filterPriority, fn ($q) => $q->where('priority', $this->filterPriority))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'date' => $f->created_at->toIso8601String(),
                    'type' => $f->feedback_type,
                    'status' => $f->status,
                    'priority' => $f->priority,
                    'message' => $f->message,
                    'page_url' => $f->page_url,
                    'page_route' => $f->page_route,
                    'browser' => $f->browser,
                    'device' => $f->device,
                    'user' => $f->user?->name,
                    'user_email' => $f->user?->email,
                    'admin_notes' => $f->admin_notes,
                    'ai_analysis' => $f->ai_analysis,
                    'ai_tags' => $f->ai_tags,
                ];
            });

        $filters = array_filter([
            'status' => $this->filterStatus,
            'type' => $this->filterType,
            'priority' => $this->filterPriority,
            'search' => $this->search,
        ]);

        $exportData = [
            'exported_at' => now()->toIso8601String(),
            'filters_applied' => $filters ?: 'none',
            'total_count' => $feedback->count(),
            'feedback' => $feedback->toArray(),
        ];

        $filename = 'feedback-filtered-'.now()->format('Y-m-d-His').'.json';
        $content = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function render()
    {
        $query = Feedback::query()
            ->with(['user', 'assignee'])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('message', 'like', '%'.$this->search.'%')
                        ->orWhere('page_url', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType, fn ($q) => $q->where('feedback_type', $this->filterType))
            ->when($this->filterPriority, fn ($q) => $q->where('priority', $this->filterPriority))
            ->orderBy('created_at', 'desc');

        return view('livewire.admin.feedback-management', [
            'feedbackItems' => $query->paginate(20),
            'stats' => $this->stats,
            'statuses' => Feedback::STATUSES,
            'types' => Feedback::TYPES,
            'priorities' => Feedback::PRIORITIES,
            'staffMembers' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}

