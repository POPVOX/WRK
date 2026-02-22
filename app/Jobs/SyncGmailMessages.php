<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\GoogleGmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGmailMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $daysBack = 30,
        public int $maxMessages = 250
    ) {
        $this->daysBack = max(1, min($daysBack, 365));
        $this->maxMessages = max(1, min($maxMessages, 1000));
    }

    public function handle(GoogleGmailService $gmailService): void
    {
        if (! $gmailService->isConnected($this->user)) {
            Log::info("Gmail sync skipped for user {$this->user->id} - not connected");

            return;
        }

        $summary = $gmailService->syncRecentMessages($this->user, $this->daysBack, $this->maxMessages);

        Log::info('Gmail sync completed', [
            'user_id' => $this->user->id,
            'processed' => $summary['processed'] ?? 0,
            'imported' => $summary['imported'] ?? 0,
            'updated' => $summary['updated'] ?? 0,
            'errors' => $summary['errors'] ?? 0,
        ]);
    }
}
