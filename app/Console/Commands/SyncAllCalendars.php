<?php

namespace App\Console\Commands;

use App\Jobs\SyncCalendarEvents;
use App\Models\User;
use Illuminate\Console\Command;

class SyncAllCalendars extends Command
{
    protected $signature = 'calendars:sync
        {--user= : Sync a specific user ID}
        {--sync : Run sync inline (no queue)}
        {--queue=default : Queue name for dispatched jobs}';

    protected $description = 'Sync Google Calendar events for all connected users';

    public function handle(): int
    {
        $userId = $this->option('user');
        $syncInline = (bool) $this->option('sync');
        $queueName = (string) ($this->option('queue') ?: 'default');

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User {$userId} not found");

                return 1;
            }

            if (! $user->google_access_token) {
                $this->warn("User {$userId} has no calendar connected");

                return 0;
            }

            $this->syncUser($user, $syncInline, $queueName);
            $this->info('Done!');

            return 0;
        }

        // Sync all users with connected calendars
        $users = User::whereNotNull('google_access_token')->get();

        $this->info("Found {$users->count()} users with connected calendars");

        foreach ($users as $user) {
            $this->syncUser($user, $syncInline, $queueName);
        }

        if ($syncInline) {
            $this->info('All calendar sync jobs completed inline.');
        } else {
            $this->info("All calendar sync jobs dispatched on [{$queueName}] queue.");
        }

        return 0;
    }

    protected function syncUser(User $user, bool $syncInline, string $queueName): void
    {
        if ($syncInline) {
            $this->info("Running inline sync for: {$user->email}");
            SyncCalendarEvents::dispatchSync($user);

            return;
        }

        $this->info("Dispatching sync for: {$user->email} on [{$queueName}] queue");
        SyncCalendarEvents::dispatch($user)->onQueue($queueName);
    }
}
