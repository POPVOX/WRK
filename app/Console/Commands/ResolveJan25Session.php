<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;

class ResolveJan25Session extends Command
{
    protected $signature = 'feedback:resolve-jan25-session';

    protected $description = 'Mark the Jan 25 bug fixes as resolved (IDs: 36, 37, 38, 39, 40, 44, 35, 32)';

    public function handle(): int
    {
        $ids = [36, 37, 38, 39, 40, 44, 35, 32];

        $this->info('Marking feedback items as resolved: ' . implode(', ', $ids));

        $count = Feedback::whereIn('id', $ids)->update([
            'status' => 'addressed',
            'resolved_at' => now(),
            'resolution_notes' => 'Fixed in Jan 25 bug session: milestone editing, milestone dates, task editing, project leads, task soft-delete, AI product insights, overdue task links, feedback button positioning',
            'resolution_type' => 'fix',
        ]);

        $this->info("Done! Marked {$count} feedback items as addressed.");

        return Command::SUCCESS;
    }
}
