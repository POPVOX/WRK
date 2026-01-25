<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class ProcessDeployedCommits extends Command
{
    protected $signature = 'feedback:process-deploy 
                            {--since= : Process commits since this commit SHA}
                            {--count=10 : Number of recent commits to check}';

    protected $description = 'Process recent commits and auto-resolve linked feedback items';

    /**
     * Patterns to match feedback references in commit messages.
     * Examples: "fixes #42", "closes feedback:42", "resolves FB-42"
     */
    protected array $patterns = [
        '/(?:fix(?:es)?|clos(?:es)?|resolv(?:es)?)\s*#(\d+)/i',
        '/(?:fix(?:es)?|clos(?:es)?|resolv(?:es)?)\s*feedback[:\s#]+(\d+)/i',
        '/(?:fix(?:es)?|clos(?:es)?|resolv(?:es)?)\s*FB-(\d+)/i',
        '/\[FB-(\d+)\]/i',
    ];

    public function handle(): int
    {
        $since = $this->option('since');
        $count = (int) $this->option('count');

        // Get recent commits
        if ($since) {
            $gitLog = "git log {$since}..HEAD --oneline";
        } else {
            $gitLog = "git log -n {$count} --oneline";
        }

        $result = Process::run($gitLog);

        if (!$result->successful()) {
            $this->error('Failed to get git log: ' . $result->errorOutput());
            return Command::FAILURE;
        }

        $commits = explode("\n", trim($result->output()));
        $resolvedCount = 0;

        $this->info("Scanning " . count($commits) . " commits for feedback references...");

        foreach ($commits as $commitLine) {
            if (empty($commitLine))
                continue;

            [$sha, $message] = explode(' ', $commitLine, 2);
            $feedbackIds = $this->extractFeedbackIds($message);

            foreach ($feedbackIds as $id) {
                $feedback = Feedback::find($id);

                if (!$feedback) {
                    $this->warn("  Feedback #{$id} not found (referenced in {$sha})");
                    continue;
                }

                if ($feedback->resolved_at) {
                    $this->line("  #{$id} already resolved, skipping");
                    continue;
                }

                $feedback->update([
                    'status' => 'addressed',
                    'resolved_at' => now(),
                    'resolution_notes' => "Auto-resolved from commit {$sha}",
                    'resolution_type' => 'fix',
                    'resolution_commit' => $sha,
                ]);

                $this->info("  ✓ #{$id} resolved (commit {$sha})");
                $resolvedCount++;
            }
        }

        $this->newLine();
        $this->info("Done! Auto-resolved {$resolvedCount} feedback items.");

        return Command::SUCCESS;
    }

    /**
     * Extract feedback IDs from a commit message.
     */
    protected function extractFeedbackIds(string $message): array
    {
        $ids = [];

        foreach ($this->patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                $ids = array_merge($ids, $matches[1]);
            }
        }

        return array_unique(array_map('intval', $ids));
    }
}
