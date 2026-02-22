<?php

namespace App\Console\Commands;

use App\Jobs\SyncGmailMessages;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncAllGmail extends Command
{
    protected $signature = 'gmail:sync
        {--user= : Sync a specific user ID}
        {--days=30 : How many days back to fetch}
        {--max=250 : Max messages per user}
        {--sync : Run sync inline (no queue)}
        {--queue=default : Queue name for dispatched jobs}';

    protected $description = 'Sync Gmail metadata for all connected users';

    public function handle(): int
    {
        if (! Schema::hasTable('gmail_messages')
            || ! Schema::hasColumn('users', 'gmail_import_date')
            || ! Schema::hasColumn('users', 'gmail_history_id')) {
            $this->warn('Gmail sync tables are not ready yet. Run: php artisan migrate --force');

            return 0;
        }

        $userId = $this->option('user');
        $daysBack = max(1, min((int) $this->option('days'), 365));
        $maxMessages = max(1, min((int) $this->option('max'), 1000));
        $syncInline = (bool) $this->option('sync');
        $queueName = (string) ($this->option('queue') ?: 'default');

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User {$userId} not found");

                return 1;
            }

            if (! $user->google_access_token) {
                $this->warn("User {$userId} has no Google connection");

                return 0;
            }

            $this->syncUser($user, $daysBack, $maxMessages, $syncInline, $queueName);
            $this->info('Done!');

            return 0;
        }

        $users = User::whereNotNull('google_access_token')->get();
        $this->info("Found {$users->count()} users with Google connected");

        foreach ($users as $user) {
            $this->syncUser($user, $daysBack, $maxMessages, $syncInline, $queueName);
        }

        if ($syncInline) {
            $this->info('All Gmail sync jobs completed inline.');
        } else {
            $this->info("All Gmail sync jobs dispatched on [{$queueName}] queue.");
        }

        return 0;
    }

    protected function syncUser(
        User $user,
        int $daysBack,
        int $maxMessages,
        bool $syncInline,
        string $queueName
    ): void {
        if ($syncInline) {
            $this->info("Running inline Gmail sync for: {$user->email}");
            try {
                SyncGmailMessages::dispatchSync($user, $daysBack, $maxMessages);
            } catch (Throwable $exception) {
                $this->error("Gmail sync failed for {$user->email}: {$exception->getMessage()}");
            }

            return;
        }

        $this->info("Dispatching Gmail sync for: {$user->email} on [{$queueName}] queue");
        SyncGmailMessages::dispatch($user, $daysBack, $maxMessages)->onQueue($queueName);
    }
}
