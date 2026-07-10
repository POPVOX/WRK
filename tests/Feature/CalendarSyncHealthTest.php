<?php

use App\Jobs\SyncCalendarEvents;
use App\Livewire\Dashboard;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('calendar command queues one active sync per user with a bounded window', function () {
    Queue::fake();
    $user = User::factory()->create([
        'google_access_token' => 'calendar-access-token',
        'google_refresh_token' => 'calendar-refresh-token',
    ]);

    expect(Artisan::call('calendars:sync', [
        '--past-days' => 14,
        '--future-days' => 120,
    ]))->toBe(0);

    $user->refresh();
    expect($user->calendar_sync_status)->toBe('queued');
    expect($user->calendar_sync_queued_at)->not()->toBeNull();

    Queue::assertPushed(SyncCalendarEvents::class, function (SyncCalendarEvents $job) use ($user): bool {
        return $job->user->is($user)
            && $job->startDate?->isSameDay(now()->subDays(14))
            && $job->endDate?->isSameDay(now()->addDays(120));
    });

    Artisan::call('calendars:sync', [
        '--past-days' => 14,
        '--future-days' => 120,
    ]);

    Queue::assertPushed(SyncCalendarEvents::class, 1);
});

test('manual calendar sync queues work and reports live status instead of blocking', function () {
    Queue::fake();
    $user = User::factory()->create([
        'profile_completed_at' => now(),
        'google_access_token' => 'calendar-access-token',
        'google_refresh_token' => 'calendar-refresh-token',
        'google_token_expires_at' => now()->addHour(),
        'calendar_sync_status' => 'failed',
        'calendar_sync_failed_at' => now()->subMinute(),
        'calendar_sync_error' => 'Previous failure',
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Calendar sync failed')
        ->call('syncCalendar')
        ->assertSet('calendarSyncStatus', 'queued')
        ->assertSet('isSyncing', true)
        ->assertDispatched('notify');

    Queue::assertPushed(SyncCalendarEvents::class, fn (SyncCalendarEvents $job): bool => $job->user->is($user));
});
