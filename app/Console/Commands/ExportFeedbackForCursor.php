<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExportFeedbackForCursor extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'feedback:export
                            {--type=all : Filter by type (bug, suggestion, all)}
                            {--status=new : Filter by status (new, reviewed, in_progress, all)}
                            {--limit=10 : Maximum number of items to export}
                            {--format=markdown : Output format (markdown, json, cursor)}
                            {--include-resolved : Include resolved/addressed feedback}';

    /**
     * The console command description.
     */
    protected $description = 'Export feedback in a structured format for AI-assisted development (Cursor)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Feedback::with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($this->option('type') !== 'all') {
            $query->where('feedback_type', $this->option('type'));
        }

        if ($this->option('status') !== 'all') {
            $query->where('status', $this->option('status'));
        }

        if (! $this->option('include-resolved')) {
            $query->whereNotIn('status', ['addressed', 'dismissed']);
        }

        $feedback = $query->limit($this->option('limit'))->get();

        if ($feedback->isEmpty()) {
            $this->warn('No feedback found matching the criteria.');

            return self::SUCCESS;
        }

        $format = $this->option('format');

        $output = match ($format) {
            'json' => $this->formatAsJson($feedback),
            'cursor' => $this->formatForCursor($feedback),
            default => $this->formatAsMarkdown($feedback),
        };

        $this->line($output);

        return self::SUCCESS;
    }

    /**
     * Format feedback as Markdown
     */
    protected function formatAsMarkdown($feedback): string
    {
        $output = "# Feedback Export\n\n";
        $output .= '**Generated:** '.now()->format('Y-m-d H:i:s')."\n";
        $output .= "**Total Items:** {$feedback->count()}\n\n";
        $output .= "---\n\n";

        foreach ($feedback as $item) {
            $output .= $this->formatSingleItemMarkdown($item);
            $output .= "\n---\n\n";
        }

        return $output;
    }

    /**
     * Format a single feedback item as Markdown
     */
    protected function formatSingleItemMarkdown(Feedback $item): string
    {
        $typeEmoji = match ($item->feedback_type) {
            'bug' => '🐛',
            'suggestion' => '💡',
            'compliment' => '🌟',
            'question' => '❓',
            default => '📝',
        };

        $priorityBadge = match ($item->priority) {
            'critical' => '🔴 Critical',
            'high' => '🟠 High',
            'medium' => '🟡 Medium',
            'low' => '🟢 Low',
            default => '⚪ Not Set',
        };

        $screenshotSection = '';
        if ($item->screenshot_path) {
            $screenshotSection = "\n**Screenshot:** [{$item->screenshot_path}]({$item->screenshot_path})\n";
        }

        $githubSection = '';
        if ($item->github_issue_url) {
            $githubSection = "\n**GitHub Issue:** [#{$item->github_issue_number}]({$item->github_issue_url})\n";
        }

        $aiSection = '';
        if ($item->ai_summary) {
            $aiSection = "\n### AI Analysis\n";
            $aiSection .= "**Summary:** {$item->ai_summary}\n";
            if ($item->ai_recommendations) {
                $aiSection .= "**Recommendations:** {$item->ai_recommendations}\n";
            }
            if (! empty($item->ai_tags)) {
                $aiSection .= '**Tags:** `'.implode('`, `', $item->ai_tags)."`\n";
            }
        }

        return <<<MARKDOWN
## {$typeEmoji} #{$item->id}: {$this->truncate($item->message, 60)}

**Type:** {$item->feedback_type}
**Priority:** {$priorityBadge}
**Status:** {$item->status}
**Page:** `{$item->page_route}` ({$item->page_url})
**User:** {$item->user?->name} ({$item->user?->email})
**Reported:** {$item->created_at->format('Y-m-d H:i')}
**Browser:** {$item->browser} on {$item->device_type}
{$screenshotSection}{$githubSection}
### Description
{$item->message}
{$aiSection}
MARKDOWN;
    }

    /**
     * Format feedback as JSON
     */
    protected function formatAsJson($feedback): string
    {
        return json_encode([
            'exported_at' => now()->toIso8601String(),
            'total_count' => $feedback->count(),
            'feedback' => $feedback->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->feedback_type,
                'priority' => $item->priority,
                'status' => $item->status,
                'message' => $item->message,
                'page_url' => $item->page_url,
                'page_route' => $item->page_route,
                'browser' => $item->browser,
                'device' => $item->device_type,
                'user' => [
                    'name' => $item->user?->name,
                    'email' => $item->user?->email,
                ],
                'ai_summary' => $item->ai_summary,
                'ai_recommendations' => $item->ai_recommendations,
                'ai_tags' => $item->ai_tags,
                'github_issue_url' => $item->github_issue_url,
                'screenshot_path' => $item->screenshot_path,
                'created_at' => $item->created_at->toIso8601String(),
            ]),
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Format feedback for Cursor AI assistant
     * This format is optimized for pasting directly into Cursor chat
     */
    protected function formatForCursor($feedback): string
    {
        $output = "# Bug Reports & Feature Requests for Cursor\n\n";
        $output .= "Please fix/implement the following issues:\n\n";

        $bugCount = $feedback->where('feedback_type', 'bug')->count();
        $suggestionCount = $feedback->where('feedback_type', 'suggestion')->count();
        $otherCount = $feedback->count() - $bugCount - $suggestionCount;

        $output .= "**Summary:** {$bugCount} bugs, {$suggestionCount} suggestions, {$otherCount} other\n\n";

        // Group by type
        $grouped = $feedback->groupBy('feedback_type');

        foreach (['bug', 'suggestion', 'general', 'question', 'compliment'] as $type) {
            if (! isset($grouped[$type]) || $grouped[$type]->isEmpty()) {
                continue;
            }

            $typeLabel = match ($type) {
                'bug' => '🐛 Bugs to Fix',
                'suggestion' => '💡 Feature Requests',
                'general' => '📝 General Feedback',
                'question' => '❓ Questions',
                'compliment' => '🌟 Positive Feedback',
                default => ucfirst($type),
            };

            $output .= "## {$typeLabel}\n\n";

            foreach ($grouped[$type] as $item) {
                $output .= $this->formatCursorItem($item);
            }
        }

        $output .= "\n---\n";
        $output .= '*Exported: '.now()->format('Y-m-d H:i')."*\n";

        return $output;
    }

    /**
     * Format a single item for Cursor
     */
    protected function formatCursorItem(Feedback $item): string
    {
        $priorityIcon = match ($item->priority) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪',
        };

        $output = "### {$priorityIcon} #{$item->id}: {$this->truncate($item->message, 50)}\n\n";

        // Main description
        $output .= "**Issue:** {$item->message}\n\n";

        // Location context
        $output .= "**Location:**\n";
        $output .= "- Route: `{$item->page_route}`\n";
        $output .= "- URL: `{$item->page_url}`\n\n";

        // AI recommendations if available
        if ($item->ai_recommendations) {
            $output .= "**AI Recommendation:** {$item->ai_recommendations}\n\n";
        }

        // Screenshot
        if ($item->screenshot_path) {
            $output .= "**Screenshot:** [View]({$item->screenshot_path})\n\n";
        }

        // GitHub issue link
        if ($item->github_issue_url) {
            $output .= "**GitHub:** [#{$item->github_issue_number}]({$item->github_issue_url})\n\n";
        }

        return $output;
    }

    /**
     * Truncate string helper
     */
    protected function truncate(string $text, int $length): string
    {
        return Str::limit($text, $length);
    }
}
