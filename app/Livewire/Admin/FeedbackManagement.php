<?php

namespace App\Livewire\Admin;

use App\Jobs\AnalyzeFeedback;
use App\Jobs\GenerateAiFix;
use App\Models\AiFixAuditLog;
use App\Models\AiFixProposal;
use App\Models\Feedback;
use App\Models\User;
use App\Support\AI\AnthropicClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    // Unified view - no tabs, just filters
    #[Url]
    public string $quickFilter = ''; // 'new', 'bugs', 'suggestions', 'resolved'

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterType = '';

    #[Url]
    public string $filterPriority = '';

    public string $search = '';

    // Resolution stats panel visibility
    public bool $showResolutionStats = false;

    // Resolution form
    public bool $showResolveModal = false;
    public ?int $resolvingFeedbackId = null;
    public string $resolutionNotes = '';
    public string $resolutionType = 'fix';
    public ?int $resolutionEffortMinutes = null;
    public string $resolutionCommit = '';

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

    // AI Fix Proposals
    public bool $showFixModal = false;

    public ?int $viewingProposalId = null;

    public ?AiFixProposal $viewingProposal = null;

    public string $rejectionReason = '';

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
        if (!$this->viewingFeedback) {
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
        if (!$this->viewingFeedback) {
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

            $response = AnthropicClient::send([
                'system' => 'You are a product strategist analyzing user feedback to provide actionable product recommendations. Respond only with valid JSON.',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 2000,
            ]);

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
                'error' => 'AI analysis failed: ' . $e->getMessage(),
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
     * Create a GitHub issue from feedback (manual trigger).
     */
    public function createGitHubIssue(int $id): void
    {
        $feedback = Feedback::with('user')->find($id);
        if (!$feedback) {
            $this->dispatch('notify', type: 'error', message: 'Feedback not found.');

            return;
        }

        if ($feedback->github_issue_url) {
            $this->dispatch('notify', type: 'info', message: 'GitHub issue already exists.');

            return;
        }

        $token = config('services.github.token');
        $repo = config('services.github.repo');

        if (empty($token) || empty($repo)) {
            $this->dispatch('notify', type: 'error', message: 'GitHub integration not configured. Set GITHUB_TOKEN and GITHUB_REPO in .env');

            return;
        }

        try {
            $typeLabel = match ($feedback->feedback_type) {
                'bug' => '[Bug]',
                'suggestion' => '[Feature]',
                default => '[Feedback]',
            };

            $title = $typeLabel . ' ' . Str::limit($feedback->message, 60);

            $body = $this->formatGitHubIssueBody($feedback);

            $labels = ['from-feedback'];
            if ($feedback->feedback_type === 'bug') {
                $labels[] = 'bug';
            } elseif ($feedback->feedback_type === 'suggestion') {
                $labels[] = 'enhancement';
            }

            if ($feedback->priority === 'critical') {
                $labels[] = 'priority:critical';
            } elseif ($feedback->priority === 'high') {
                $labels[] = 'priority:high';
            }

            $response = Http::withToken($token)
                ->post("https://api.github.com/repos/{$repo}/issues", [
                    'title' => $title,
                    'body' => $body,
                    'labels' => $labels,
                ]);

            if ($response->successful()) {
                $issueData = $response->json();
                $feedback->update([
                    'github_issue_url' => $issueData['html_url'] ?? null,
                    'github_issue_number' => $issueData['number'] ?? null,
                ]);

                $this->dispatch('notify', type: 'success', message: "GitHub issue #{$issueData['number']} created!");

                // Refresh the viewing feedback if modal is open
                if ($this->viewingFeedback && $this->viewingFeedback->id === $id) {
                    $this->viewingFeedback->refresh();
                }
            } else {
                $this->dispatch('notify', type: 'error', message: 'GitHub API error: ' . $response->json('message', 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Format the GitHub issue body.
     */
    protected function formatGitHubIssueBody(Feedback $feedback): string
    {
        $priorityBadge = match ($feedback->priority) {
            'critical' => '🔴 Critical',
            'high' => '🟠 High',
            'medium' => '🟡 Medium',
            'low' => '🟢 Low',
            default => '⚪ Not Set',
        };

        $screenshotSection = '';
        if ($feedback->screenshot_path) {
            $screenshotUrl = url('storage/' . $feedback->screenshot_path);
            $screenshotSection = "\n## Screenshot\n![Screenshot]({$screenshotUrl})\n";
        }

        $aiSection = '';
        if ($feedback->ai_summary) {
            $aiSection = "\n## AI Analysis\n**Summary:** {$feedback->ai_summary}\n";
            if ($feedback->ai_recommendations) {
                $aiSection .= "**Recommendations:** {$feedback->ai_recommendations}\n";
            }
        }

        $reportedAt = $feedback->created_at->format('Y-m-d H:i');
        $userName = $feedback->user?->name ?? 'Unknown';
        $userEmail = $feedback->user?->email ?? 'unknown';

        return <<<BODY
## Feedback Report

**Feedback ID:** #{$feedback->id}
**Type:** {$feedback->feedback_type}
**Priority:** {$priorityBadge}
**Category:** {$feedback->category}

## Description
{$feedback->message}
{$aiSection}
## Context
| Field | Value |
|-------|-------|
| **Page URL** | `{$feedback->page_url}` |
| **Route** | `{$feedback->page_route}` |
| **Browser** | {$feedback->browser} |
| **Device** | {$feedback->device_type} |
| **Screen Size** | {$feedback->screen_resolution} |
| **Reported By** | {$userName} ({$userEmail}) |
| **Reported At** | {$reportedAt} |
{$screenshotSection}
---
*This issue was created from the WRK feedback system.*
BODY;
    }

    /**
     * Check if GitHub integration is configured.
     */
    public function getGithubConfiguredProperty(): bool
    {
        return !empty(config('services.github.token')) && !empty(config('services.github.repo'));
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
                    'screenshot_path' => $f->screenshot_path ? url('storage/' . $f->screenshot_path) : null,
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

        $filename = 'feedback-export-' . now()->format('Y-m-d-His') . '.json';
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

        $filename = 'feedback-export-' . now()->format('Y-m-d-His') . '.csv';

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
                    $q->where('message', 'like', '%' . $this->search . '%')
                        ->orWhere('page_url', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType, fn($q) => $q->where('feedback_type', $this->filterType))
            ->when($this->filterPriority, fn($q) => $q->where('priority', $this->filterPriority))
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

        $filename = 'feedback-filtered-' . now()->format('Y-m-d-His') . '.json';
        $content = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    // === Quick Filter Methods ===

    public function setQuickFilter(string $filter): void
    {
        // Toggle off if clicking the same filter
        if ($this->quickFilter === $filter) {
            $this->quickFilter = '';
        } else {
            $this->quickFilter = $filter;
        }
        // Clear other filters when using quick filter
        $this->filterStatus = '';
        $this->filterType = '';
        $this->filterPriority = '';
        $this->resetPage();
    }

    public function toggleResolutionStats(): void
    {
        $this->showResolutionStats = !$this->showResolutionStats;
    }

    // === Resolution Methods ===

    public function openResolveModal(int $id): void
    {
        $this->resolvingFeedbackId = $id;
        $this->resolutionNotes = '';
        $this->resolutionType = 'fix';
        $this->resolutionEffortMinutes = null;
        $this->resolutionCommit = '';
        $this->showResolveModal = true;
    }

    public function closeResolveModal(): void
    {
        $this->showResolveModal = false;
        $this->resolvingFeedbackId = null;
    }

    public function markResolved(): void
    {
        $feedback = Feedback::find($this->resolvingFeedbackId);
        if (!$feedback) {
            return;
        }

        $feedback->update([
            'status' => 'addressed',
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
            'resolution_notes' => $this->resolutionNotes ?: null,
            'resolution_type' => $this->resolutionType,
            'resolution_effort_minutes' => $this->resolutionEffortMinutes,
            'resolution_commit' => $this->resolutionCommit ?: null,
        ]);

        $this->closeResolveModal();
        $this->dispatch('notify', type: 'success', message: 'Feedback marked as resolved!');
    }

    public function markUnresolved(int $id): void
    {
        $feedback = Feedback::find($id);
        if ($feedback) {
            $feedback->update([
                'status' => 'in_progress',
                'resolved_at' => null,
                'resolved_by' => null,
            ]);
            $this->dispatch('notify', type: 'info', message: 'Resolution reverted.');
        }
    }

    public function getResolutionStatsProperty(): array
    {
        $resolved = Feedback::resolved()->get();

        $totalEffort = $resolved->sum('resolution_effort_minutes') ?? 0;
        $avgTimeToResolution = $resolved->count() > 0
            ? $resolved->map(function ($f) {
                return $f->created_at->diffInMinutes($f->resolved_at);
            })->avg()
            : 0;

        return [
            'total_resolved' => $resolved->count(),
            'total_effort_hours' => round(($totalEffort ?? 0) / 60, 1),
            'avg_resolution_time_hours' => round(($avgTimeToResolution ?? 0) / 60, 1),
            'by_type' => $resolved->groupBy('resolution_type')->map->count(),
            'by_month' => $resolved->filter(fn($f) => $f->resolved_at)->groupBy(fn($f) => $f->resolved_at->format('Y-m'))->map->count(),
        ];
    }

    // === AI Fix Proposal Methods ===

    /**
     * Request an AI-generated fix for feedback.
     */
    public function requestAiFix(int $feedbackId): void
    {
        $feedback = Feedback::find($feedbackId);
        if (!$feedback) {
            $this->dispatch('notify', type: 'error', message: 'Feedback not found.');
            return;
        }

        if (!$feedback->canGenerateAiFix()) {
            $this->dispatch('notify', type: 'error', message: 'AI fixes are only available for bugs and suggestions.');
            return;
        }

        // Check if there's already a pending/generating proposal
        $existingProposal = $feedback->fixProposals()
            ->whereIn('status', ['pending', 'generating', 'ready'])
            ->first();

        if ($existingProposal) {
            $this->dispatch('notify', type: 'info', message: 'A fix proposal is already in progress.');
            $this->viewFixProposal($existingProposal->id);
            return;
        }

        // Create new proposal
        $proposal = AiFixProposal::create([
            'feedback_id' => $feedbackId,
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // Log the request
        AiFixAuditLog::logAction($proposal, 'requested', [
            'feedback_type' => $feedback->feedback_type,
            'message_preview' => \Illuminate\Support\Str::limit($feedback->message, 100),
        ]);

        // Dispatch job to generate fix
        GenerateAiFix::dispatch($proposal);

        $this->dispatch('notify', type: 'info', message: 'AI fix generation started. This may take 30-60 seconds...');

        // Refresh the viewing feedback if modal is open
        if ($this->viewingFeedback && $this->viewingFeedback->id === $feedbackId) {
            $this->viewingFeedback->refresh();
            $this->viewingFeedback->load('latestFixProposal');
        }
    }

    /**
     * View an AI fix proposal.
     */
    public function viewFixProposal(int $proposalId): void
    {
        $this->viewingProposal = AiFixProposal::with(['feedback', 'requester', 'approver'])->find($proposalId);
        if (!$this->viewingProposal) {
            $this->dispatch('notify', type: 'error', message: 'Proposal not found.');
            return;
        }

        // Log the view
        AiFixAuditLog::logAction($this->viewingProposal, 'viewed');

        $this->viewingProposalId = $proposalId;
        $this->rejectionReason = '';
        $this->showFixModal = true;
    }

    /**
     * Close the fix proposal modal.
     */
    public function closeFixModal(): void
    {
        $this->showFixModal = false;
        $this->viewingProposal = null;
        $this->viewingProposalId = null;
        $this->rejectionReason = '';
    }

    /**
     * Approve and deploy a fix proposal.
     */
    public function deployFix(int $proposalId): void
    {
        $proposal = AiFixProposal::find($proposalId);
        if (!$proposal || !$proposal->canDeploy()) {
            $this->dispatch('notify', type: 'error', message: 'Proposal cannot be deployed.');
            return;
        }

        // Mark as approved first
        $proposal->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        // Deploy using GitDeployService
        $gitService = app(\App\Services\GitDeployService::class);

        // Check git status first
        $status = $gitService->checkStatus();
        if (!$status['available']) {
            $proposal->update([
                'status' => 'ready',
                'error_message' => 'Git is not available or repository not initialized',
            ]);
            $this->dispatch('notify', type: 'error', message: 'Git is not available.');
            return;
        }

        if ($status['uncommitted_changes']) {
            $this->dispatch('notify', type: 'warning', message: 'Warning: There are uncommitted changes in the repository.');
        }

        // Deploy the proposal
        $result = $gitService->deployProposal($proposal);

        if ($result['success']) {
            $proposal->update([
                'status' => 'deployed',
                'commit_sha' => $result['commit_sha'] ?? null,
                'deployed_at' => now(),
            ]);

            // Log the deployment
            AiFixAuditLog::logAction($proposal, 'deployed', [
                'commit_sha' => $result['commit_sha'] ?? null,
                'files_modified' => $result['files_modified'] ?? 0,
            ]);

            // Mark the feedback as resolved
            $proposal->feedback->update([
                'status' => 'addressed',
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
                'resolution_type' => 'fix',
                'resolution_notes' => 'Fixed via AI-generated code change',
                'resolution_commit' => $result['commit_sha'] ?? null,
            ]);

            $this->dispatch('notify', type: 'success', message: '🚀 Fix deployed successfully! Commit: ' . Str::limit($result['commit_sha'] ?? '', 7, ''));
        } else {
            $proposal->update([
                'status' => 'ready',
                'error_message' => $result['error'] ?? 'Deployment failed',
            ]);
            $this->dispatch('notify', type: 'error', message: 'Deployment failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        $this->closeFixModal();
    }

    /**
     * Reject a fix proposal.
     */
    public function rejectFix(int $proposalId): void
    {
        $proposal = AiFixProposal::find($proposalId);
        if (!$proposal) {
            return;
        }

        $proposal->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejectionReason ?: null,
        ]);

        // Log the rejection
        AiFixAuditLog::logAction($proposal, 'rejected', [
            'reason' => $this->rejectionReason ?: null,
        ]);

        $this->dispatch('notify', type: 'info', message: 'Fix proposal rejected.');
        $this->closeFixModal();
    }

    /**
     * Get the count of pending AI fix proposals.
     */
    public function getPendingFixesCountProperty(): int
    {
        return AiFixProposal::where('status', 'ready')->count();
    }

    public function render()
    {
        $query = Feedback::query()
            ->with(['user', 'assignee', 'resolver', 'latestFixProposal'])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('message', 'like', '%' . $this->search . '%')
                        ->orWhere('page_url', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            // Quick filter from clickable stat cards
            ->when($this->quickFilter === 'new', fn($q) => $q->where('status', 'new'))
            ->when($this->quickFilter === 'bugs', fn($q) => $q->where('feedback_type', 'bug')->whereIn('status', ['new', 'reviewed', 'in_progress']))
            ->when($this->quickFilter === 'suggestions', fn($q) => $q->where('feedback_type', 'suggestion')->whereIn('status', ['new', 'reviewed', 'in_progress']))
            ->when($this->quickFilter === 'resolved', fn($q) => $q->whereNotNull('resolved_at'))
            // Standard filters (when not using quick filter)
            ->when($this->filterStatus && !$this->quickFilter, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType && !$this->quickFilter, fn($q) => $q->where('feedback_type', $this->filterType))
            ->when($this->filterPriority, fn($q) => $q->where('priority', $this->filterPriority))
            ->orderBy('created_at', 'desc');

        return view('livewire.admin.feedback-management', [
            'feedbackItems' => $query->paginate(20),
            'stats' => $this->stats,
            'resolutionStats' => $this->resolutionStats,
            'statuses' => Feedback::STATUSES,
            'types' => Feedback::TYPES,
            'priorities' => Feedback::PRIORITIES,
            'resolutionTypes' => Feedback::RESOLUTION_TYPES,
            'staffMembers' => User::orderBy('name')->get(['id', 'name']),
            'githubConfigured' => $this->githubConfigured,
            'pendingFixesCount' => $this->pendingFixesCount,
        ]);
    }
}
