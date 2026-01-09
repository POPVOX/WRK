<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Support\AI\AnthropicClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyzeFeedback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        public int $feedbackId
    ) {}

    public function handle(): void
    {
        $feedback = Feedback::with('user')->find($this->feedbackId);
        if (! $feedback) {
            return;
        }

        // Skip if already analyzed
        if ($feedback->ai_analyzed_at) {
            return;
        }

        try {
            $client = app(AnthropicClient::class);

            $prompt = $this->buildPrompt($feedback);

            $response = $client->message(
                system: 'You are a product feedback analyst. Analyze user feedback and provide actionable insights. Respond only with valid JSON.',
                messages: [['role' => 'user', 'content' => $prompt]],
                maxTokens: 1000
            );

            $content = $response['content'][0]['text'] ?? '';

            // Extract JSON from response
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $jsonStr = $matches[1];
            } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
                $jsonStr = $matches[0];
            } else {
                $jsonStr = $content;
            }

            $analysis = json_decode($jsonStr, true);

            if ($analysis) {
                $feedback->update([
                    'ai_summary' => $analysis['summary'] ?? null,
                    'ai_recommendations' => $analysis['recommendations'] ?? null,
                    'ai_tags' => $analysis['tags'] ?? [],
                    'priority' => $analysis['suggested_priority'] ?? null,
                    'ai_analyzed_at' => now(),
                ]);

                // Auto-create GitHub issue for bugs (if enabled)
                if ($feedback->feedback_type === 'bug' && config('services.github.auto_create_issues')) {
                    $this->createGitHubIssue($feedback, $analysis);
                }
            }
        } catch (\Exception $e) {
            Log::error('Feedback AI analysis failed: '.$e->getMessage(), [
                'feedback_id' => $this->feedbackId,
            ]);
        }
    }

    /**
     * Create a GitHub issue from the feedback
     */
    protected function createGitHubIssue(Feedback $feedback, array $analysis): void
    {
        $token = config('services.github.token');
        $repo = config('services.github.repo');

        if (empty($token) || empty($repo)) {
            Log::info('GitHub issue creation skipped: missing token or repo config');

            return;
        }

        try {
            $title = '[Bug] '.Str::limit($feedback->message, 60);

            $body = $this->formatGitHubIssueBody($feedback, $analysis);

            $labels = ['bug', 'from-feedback'];
            if (($analysis['suggested_priority'] ?? '') === 'critical') {
                $labels[] = 'priority:critical';
            } elseif (($analysis['suggested_priority'] ?? '') === 'high') {
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

                Log::info("GitHub issue created for feedback #{$feedback->id}: {$issueData['html_url']}");
            } else {
                Log::error('GitHub issue creation failed: '.$response->body());
            }
        } catch (\Exception $e) {
            Log::error('GitHub issue creation exception: '.$e->getMessage());
        }
    }

    /**
     * Format the GitHub issue body
     */
    protected function formatGitHubIssueBody(Feedback $feedback, array $analysis): string
    {
        $screenshotSection = '';
        if ($feedback->screenshot_path) {
            $screenshotSection = "\n## Screenshot\n![Screenshot]({$feedback->screenshot_path})\n";
        }

        $priorityBadge = match ($analysis['suggested_priority'] ?? 'medium') {
            'critical' => '🔴 Critical',
            'high' => '🟠 High',
            'medium' => '🟡 Medium',
            'low' => '🟢 Low',
            default => '⚪ Unknown',
        };

        $affectedArea = $analysis['affected_area'] ?? 'Unknown';
        $effortEstimate = $analysis['effort_estimate'] ?? 'Unknown';
        $aiSummary = $analysis['summary'] ?? 'No summary available';
        $aiRecommendations = $analysis['recommendations'] ?? 'No recommendations available';
        $tags = implode('`, `', $analysis['tags'] ?? []);
        $reportedAt = $feedback->created_at->format('Y-m-d H:i');
        $userName = $feedback->user?->name ?? 'Unknown';
        $userEmail = $feedback->user?->email ?? 'unknown';

        return <<<BODY
## Bug Report (from User Feedback)

**Feedback ID:** #{$feedback->id}
**Priority:** {$priorityBadge}
**Affected Area:** {$affectedArea}
**Effort Estimate:** {$effortEstimate}

## Description
{$feedback->message}

## AI Analysis
{$aiSummary}

## Recommendations
{$aiRecommendations}

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
## Tags
`{$tags}`

---
*This issue was automatically created from user feedback.*
BODY;
    }

    protected function buildPrompt(Feedback $feedback): string
    {
        $context = [
            'type' => $feedback->feedback_type,
            'category' => $feedback->category,
            'message' => $feedback->message,
            'page' => $feedback->page_route ?: $feedback->page_url,
            'device' => $feedback->device_type,
            'browser' => $feedback->browser,
        ];

        return <<<PROMPT
Analyze this user feedback from our beta product:

**Feedback Type:** {$context['type']}
**Category:** {$context['category']}
**Page:** {$context['page']}
**Device:** {$context['device']} ({$context['browser']})

**User Message:**
{$context['message']}

Provide analysis in this exact JSON format:
```json
{
    "summary": "One-sentence summary of the feedback",
    "sentiment": "positive|negative|neutral|mixed",
    "suggested_priority": "low|medium|high|critical",
    "tags": ["tag1", "tag2", "tag3"],
    "recommendations": "Specific actionable recommendations for the product team (2-3 sentences)",
    "affected_area": "UI|Backend|UX|Performance|Content|Feature|Other",
    "effort_estimate": "quick-fix|medium|large|complex"
}
```
PROMPT;
    }
}
