<?php

namespace App\Console\Commands;

use App\Models\Trip;
use App\Models\User;
use App\Notifications\WorkspaceAlert;
use App\Services\Notifications\WorkspaceNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendTripUpcomingNotifications extends Command
{
    protected $signature = 'notifications:trip-upcoming
        {--days=7 : Number of days before trip start for reminder}';

    protected $description = 'Send in-app reminders for trips starting soon';

    public function handle(WorkspaceNotificationService $notifications): int
    {
        if (! Schema::hasTable('notifications')) {
            $this->warn('Notifications table not found. Run migrations first.');

            return 0;
        }

        $days = max(1, min((int) $this->option('days'), 30));
        $targetDate = now()->addDays($days)->toDateString();

        $trips = Trip::query()
            ->with('travelers:id,name,email,is_visible')
            ->whereDate('start_date', $targetDate)
            ->whereIn('status', ['planning', 'booked'])
            ->get();

        if ($trips->isEmpty()) {
            $this->info('No upcoming trips found for reminder window.');

            return 0;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($trips as $trip) {
            foreach ($trip->travelers as $traveler) {
                if (! $traveler instanceof User || ! $traveler->is_visible) {
                    continue;
                }

                if ($this->alreadySentReminder($traveler->id, $trip->id, $days)) {
                    $skipped++;

                    continue;
                }

                $sent += $notifications->sendToUsers(
                    [$traveler],
                    'trip_upcoming',
                    "You have a trip coming up in {$days} days",
                    "{$trip->name} starts on ".$trip->start_date?->format('M j, Y'),
                    [
                        'category' => 'travel',
                        'level' => 'info',
                        'action_label' => 'Open Trip',
                        'action_url' => route('travel.show', $trip),
                        'meta' => [
                            'trip_id' => $trip->id,
                            'days' => $days,
                        ],
                    ],
                );
            }
        }

        $this->info("Trip reminders sent: {$sent}. Duplicates skipped: {$skipped}.");

        return 0;
    }

    protected function alreadySentReminder(int $userId, int $tripId, int $days): bool
    {
        return DB::table('notifications')
            ->where('type', WorkspaceAlert::class)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->whereRaw("(data::jsonb ->> 'kind') = 'trip_upcoming'")
            ->whereRaw("((data::jsonb -> 'meta' ->> 'trip_id')::bigint) = ?", [$tripId])
            ->whereRaw("((data::jsonb -> 'meta' ->> 'days')::int) = ?", [$days])
            ->exists();
    }
}

