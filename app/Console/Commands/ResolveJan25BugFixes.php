<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;

class ResolveJan25BugFixes extends Command
{
    protected $signature = 'feedback:resolve-jan25-fixes {--dry-run : Show what would be resolved without making changes}';

    protected $description = 'Resolve the bug fixes from the Jan 25 session based on keywords';

    /**
     * Bug fixes from Jan 25 session and their search keywords.
     */
    protected array $bugFixes = [
        [
            'keywords' => ['milestone', 'edit', 'button'],
            'description' => 'Add edit button for milestones',
        ],
        [
            'keywords' => ['milestone', 'date', 'target'],
            'description' => 'Milestone target dates not showing',
        ],
        [
            'keywords' => ['milestone', 'due'],
            'description' => 'Milestone due dates not showing',
        ],
        [
            'keywords' => ['task', 'edit'],
            'description' => 'Add edit button for tasks',
        ],
        [
            'keywords' => ['project', 'lead'],
            'description' => 'Project leads display inconsistency',
        ],
        [
            'keywords' => ['task', 'delete'],
            'description' => 'Task soft-delete recovery',
        ],
        [
            'keywords' => ['task', 'archive'],
            'description' => 'Task archiving/recovery',
        ],
        [
            'keywords' => ['task', 'recover'],
            'description' => 'Task recovery',
        ],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN - No changes will be made');
        }

        $this->info('Searching for bug reports matching Jan 25 fixes...');
        $this->newLine();

        // Get all unresolved bug reports
        $bugs = Feedback::where('feedback_type', 'bug')
            ->whereIn('status', ['new', 'reviewed', 'in_progress'])
            ->get();

        $this->line("Found {$bugs->count()} open bug reports to scan.");
        $this->newLine();

        $resolvedCount = 0;
        $resolvedIds = [];

        foreach ($this->bugFixes as $fix) {
            $this->line("Looking for: <comment>{$fix['description']}</comment>");
            $this->line("  Keywords: " . implode(', ', $fix['keywords']));

            foreach ($bugs as $bug) {
                // Skip if already processed
                if (in_array($bug->id, $resolvedIds)) {
                    continue;
                }

                $message = strtolower($bug->message ?? '');
                $matchCount = 0;

                foreach ($fix['keywords'] as $keyword) {
                    if (str_contains($message, strtolower($keyword))) {
                        $matchCount++;
                    }
                }

                // Require at least 2 keyword matches
                if ($matchCount >= 2) {
                    $resolvedIds[] = $bug->id;

                    if (!$dryRun) {
                        $bug->update([
                            'status' => 'addressed',
                            'resolved_at' => now(),
                            'resolution_notes' => "Fixed in Jan 25 bug fixes session: {$fix['description']}",
                            'resolution_type' => 'fix',
                        ]);
                    }

                    $this->info("  ✓ #{$bug->id}: " . \Illuminate\Support\Str::limit($bug->message, 50));
                    $resolvedCount++;
                }
            }
        }

        $this->newLine();

        if ($resolvedCount === 0) {
            $this->warn('No matching bug reports found.');
            $this->line('The bugs may already be resolved, or the feedback descriptions don\'t match the search keywords.');
            $this->newLine();
            $this->line('Open bugs in the system:');

            foreach ($bugs->take(10) as $bug) {
                $this->line("  #{$bug->id}: " . \Illuminate\Support\Str::limit($bug->message, 70));
            }
        } else {
            if ($dryRun) {
                $this->info("Would resolve {$resolvedCount} feedback items.");
                $this->line('Run without --dry-run to apply changes.');
            } else {
                $this->info("✅ Resolved {$resolvedCount} feedback items!");
            }
        }

        return Command::SUCCESS;
    }
}
