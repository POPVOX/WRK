<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;

class ResolveJan25Session extends Command
{
    protected $signature = 'feedback:resolve-jan25-session';

    protected $description = 'Mark all Jan 25 fixed/already-implemented items as resolved';

    /**
     * Items fixed in Jan 25 session with resolution notes.
     */
    protected array $resolutions = [
        // Bugs fixed in code
        36 => 'Milestone edit button added',
        37 => 'Milestone dates now display correctly',
        38 => 'Task edit button added',
        39 => 'Project leads display fixed',
        40 => 'Task soft-delete recovery implemented',
        44 => 'AI Product Insights fixed (AnthropicClient)',
        35 => 'Overdue task links fixed',
        32 => 'Feedback button repositioned to avoid covering pagination',
        34 => 'Dark mode styling in notes already correct',
        46 => 'Smart import error handling improved - now shows errors instead of blank modal',

        // Already implemented previously
        23 => 'URL display already strips https:// and www. (display_website accessor)',
        25 => 'Org name fixing already implemented (suggested_name accessor)',
        31 => 'Same as #35 - tasks/items now hyperlinked',
        28 => 'Map already zooms out to show Hawaii (minZoom: 1, maxZoom: 4)',
        24 => 'Edit org name in list view - already implemented with inline edit in table view',

        // Suggestions implemented today
        45 => 'Parliamentary Visit added to trip types',
        41 => 'Needs attention now shows 5 items instead of 3',
        29 => 'Removed mention from clip type options',
        42 => 'Added outlet filter to media coverage search',
        47 => 'Press clip cards now have prominent Edit button',
        26 => 'Calendar import now filters out Lunch, OOO, Focus Time, etc.',
        33 => 'Quick Add Task floating widget added to all pages',

        // General items that can be closed
        43 => 'User resolved own issue (DB limitation understood)',
    ];

    public function handle(): int
    {
        $this->info('Marking feedback items as resolved...');
        $this->newLine();

        $resolvedCount = 0;
        foreach ($this->resolutions as $id => $note) {
            $feedback = Feedback::find($id);

            if (!$feedback) {
                $this->warn("  #{$id}: Not found, skipping");
                continue;
            }

            if ($feedback->status === 'addressed') {
                $this->line("  #{$id}: Already resolved");
                continue;
            }

            $feedback->update([
                'status' => 'addressed',
                'resolved_at' => now(),
                'resolution_notes' => "Fixed Jan 25: {$note}",
                'resolution_type' => 'fix',
            ]);

            $this->info("  ✓ #{$id}: {$note}");
            $resolvedCount++;
        }

        $this->newLine();
        $this->info("Done! Marked {$resolvedCount} feedback items as addressed.");

        return Command::SUCCESS;
    }
}
