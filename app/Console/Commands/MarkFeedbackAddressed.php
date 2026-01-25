<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;

class MarkFeedbackAddressed extends Command
{
    protected $signature = 'feedback:mark-addressed 
                            {ids* : Feedback IDs to mark as addressed}
                            {--notes= : Resolution notes}
                            {--type=fix : Resolution type (fix, enhancement, wontfix, duplicate, workaround)}';

    protected $description = 'Mark feedback items as addressed/resolved';

    public function handle(): int
    {
        $ids = $this->argument('ids');
        $notes = $this->option('notes') ?? 'Resolved via command line';
        $type = $this->option('type');

        $this->info("Marking feedback items as addressed: " . implode(', ', $ids));

        $updated = 0;
        foreach ($ids as $id) {
            $feedback = Feedback::find($id);

            if (!$feedback) {
                $this->warn("Feedback #{$id} not found, skipping.");
                continue;
            }

            $feedback->update([
                'status' => 'addressed',
                'resolved_at' => now(),
                'resolution_notes' => $notes,
                'resolution_type' => $type,
            ]);

            $this->line("  ✓ #{$id}: " . \Illuminate\Support\Str::limit($feedback->message, 50));
            $updated++;
        }

        $this->info("Done! Marked {$updated} feedback items as addressed.");

        return Command::SUCCESS;
    }
}
