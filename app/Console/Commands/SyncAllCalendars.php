<?php

namespace App\Console\Commands;

use App\Jobs\SyncCalendarEvents;
use App\Models\User;
use Illuminate\Console\Command;

class SyncAllCalendars extends Command
{
    protected $signature = 'calendars:sync {--user= : Sync a specific user ID}';
    protected $description = 'Sync Google Calendar events for all connected users';

    public function handle(): int
    {
        $userId = $this->option('user');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User {$userId} not found");
                return 1;
            }

            if (!$user->google_access_token) {
                $this->warn("User {$userId} has no calendar connected");
                return 0;
            }

            $this->info("Dispatching calendar sync for user {$userId}...");
            SyncCalendarEvents::dispatch($user);
            $this->info("Done!");
            return 0;
        }

        // Sync all users with connected calendars
        $users = User::whereNotNull('google_access_token')->get();

        $this->info("Found {$users->count()} users with connected calendars");

        foreach ($users as $user) {
            $this->info("Dispatching sync for: {$user->email}");
            SyncCalendarEvents::dispatch($user);
        }

        $this->info("All calendar sync jobs dispatched!");
        return 0;
    }
}
