<?php

namespace App\Console\Commands;

use App\Jobs\SyncCalendarEvents;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SyncAllCalendars extends Command
{
    protected $signature = 'calendars:sync
        {--user= : Sync a specific user ID}
        {--past-days=30 : How many past days to refresh}
        {--future-days=365 : How many future days to refresh}
        {--sync : Run sync inline (no queue)}
        {--queue=default : Queue name for dispatched jobs}';

    protected $description = 'Sync Google Calendar events for all connected users';

    public function handle(): int
    {
        $userId = $this->option('user');
        $pastDays = max(1, min((int) $this->option('past-days'), 365));
        $futureDays = max(1, min((int) $this->option('future-days'), 730));
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

            $succeeded = $this->syncUser($user, $pastDays, $futureDays, $syncInline, $queueName);
            $this->info($succeeded ? 'Done!' : 'Calendar sync did not complete.');

            return $succeeded ? self::SUCCESS : self::FAILURE;
        }

        // Sync all users with connected calendars
        $users = User::whereNotNull('google_access_token')->get();

        $this->info("Found {$users->count()} users with connected calendars");

        $failures = 0;
        foreach ($users as $user) {
            if (! $this->syncUser($user, $pastDays, $futureDays, $syncInline, $queueName)) {
                $failures++;
            }
        }

        if ($syncInline) {
            $this->info('All calendar sync jobs completed inline.');
        } else {
            $this->info("All calendar sync jobs dispatched on [{$queueName}] queue.");
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function syncUser(
        User $user,
        int $pastDays,
        int $futureDays,
        bool $syncInline,
        string $queueName
    ): bool {
        $startDate = Carbon::now()->subDays($pastDays);
        $endDate = Carbon::now()->addDays($futureDays);

        if ($syncInline) {
            $this->info("Running inline sync for: {$user->email}");
            try {
                SyncCalendarEvents::dispatchSync($user, $startDate, $endDate);
            } catch (Throwable $exception) {
                $this->error("Calendar sync failed for {$user->email}: {$exception->getMessage()}");

                return false;
            }

            return true;
        }

        $activeQueuedSync = $user->calendar_sync_status === 'queued'
            && $user->calendar_sync_queued_at?->gt(now()->subMinutes(15));
        $activeRunningSync = $user->calendar_sync_status === 'running'
            && $user->calendar_sync_started_at?->gt(now()->subMinutes(15));

        if ($activeQueuedSync || $activeRunningSync) {
            $this->line("Calendar sync already active for: {$user->email}");

            return true;
        }

        $this->info("Dispatching sync for: {$user->email} on [{$queueName}] queue");
        $user->update([
            'calendar_sync_status' => 'queued',
            'calendar_sync_queued_at' => now(),
            'calendar_sync_error' => null,
        ]);
        SyncCalendarEvents::dispatch($user, $startDate, $endDate)->onQueue($queueName);

        return true;
    }
}
