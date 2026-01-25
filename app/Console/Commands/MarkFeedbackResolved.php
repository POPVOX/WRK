<?php

namespace App\Console\Commands;

use App\Models\Feedback;
use Illuminate\Console\Command;

class MarkFeedbackResolved extends Command
{
    protected $signature = 'feedback:resolve {ids* : Feedback IDs to mark as resolved} {--notes= : Resolution notes}';

    protected $description = 'Mark feedback items as resolved by Claude AI';

    public function handle(): int
    {
        $ids = $this->argument('ids');
        $notes = $this->option('notes') ?? 'Fixed by Claude AI Assistant - automated code fix applied';

        $resolved = 0;
        foreach ($ids as $id) {
            $feedback = Feedback::find($id);
            if ($feedback) {
                $feedback->update([
                    'status' => 'addressed',
                    'resolved_at' => now(),
                    'resolution_notes' => $notes,
                    'resolution_type' => 'fix',
                ]);
                $this->info("✅ Resolved #{$id}: " . substr($feedback->message, 0, 60) . '...');
                $resolved++;
            } else {
                $this->warn("⚠️  Feedback #{$id} not found");
            }
        }

        $this->newLine();
        $this->info("🎉 {$resolved} feedback items marked as resolved!");

        return Command::SUCCESS;
    }
}
