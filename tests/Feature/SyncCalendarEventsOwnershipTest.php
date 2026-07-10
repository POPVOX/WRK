<?php

use App\Jobs\SyncCalendarEvents;
use App\Models\Meeting;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;

test('calendar sync links imported meeting to owner team membership', function () {
    $user = User::factory()->create();

    $eventStart = new class
    {
        public function getDateTime(): string
        {
            return now()->addDay()->toRfc3339String();
        }

        public function getDate(): ?string
        {
            return null;
        }
    };

    $event = new class($eventStart)
    {
        public function __construct(private object $start) {}

        public function getSummary(): string
        {
            return 'Foundation Partner Call';
        }

        public function getId(): string
        {
            return 'evt-ownership-test-1';
        }

        public function getStart(): object
        {
            return $this->start;
        }

        public function getAttendees(): array
        {
            return [];
        }

        public function getDescription(): string
        {
            return 'Review next steps.';
        }

        public function getLocation(): ?string
        {
            return null;
        }

        public function getHangoutLink(): ?string
        {
            return null;
        }

        public function getConferenceData(): mixed
        {
            return null;
        }
    };

    $calendarService = \Mockery::mock(GoogleCalendarService::class);
    $calendarService->shouldReceive('isConnected')->once()->with($user)->andReturn(true);
    $calendarService->shouldReceive('getEvents')->once()->with(
        $user,
        \Mockery::type(Carbon::class),
        \Mockery::type(Carbon::class)
    )->andReturn([$event]);

    $job = new SyncCalendarEvents($user);
    $job->handle($calendarService);

    $meeting = Meeting::where('google_event_id', 'evt-ownership-test-1')->first();
    $user->refresh();

    expect($meeting)->not()->toBeNull();
    expect($meeting->user_id)->toBe($user->id);
    expect($meeting->teamMembers()->where('users.id', $user->id)->exists())->toBeTrue();
    expect($user->calendar_import_date)->not()->toBeNull();
    expect($user->calendar_sync_status)->toBe('succeeded');
    expect($user->calendar_sync_completed_at)->not()->toBeNull();
});

test('calendar sync does not update import timestamp when sync fails', function () {
    $user = User::factory()->create();

    $calendarService = \Mockery::mock(GoogleCalendarService::class);
    $calendarService->shouldReceive('isConnected')->once()->with($user)->andReturn(true);
    $calendarService->shouldReceive('getEvents')->once()->andThrow(new RuntimeException('API unavailable'));

    $job = new SyncCalendarEvents($user);
    expect(fn () => $job->handle($calendarService))
        ->toThrow(RuntimeException::class, 'API unavailable');

    $user->refresh();
    expect($user->calendar_import_date)->toBeNull();
    expect($user->calendar_sync_status)->toBe('failed');
    expect($user->calendar_sync_failed_at)->not()->toBeNull();
    expect($user->calendar_sync_error)->toContain('API unavailable');
});

test('calendar sync jobs are unique per user and have bounded execution', function () {
    $user = User::factory()->create();
    $job = new SyncCalendarEvents($user);

    expect($job)->toBeInstanceOf(ShouldBeUnique::class);
    expect($job->uniqueId())->toBe('calendar-user-'.$user->id);
    expect($job->timeout)->toBe(75);
    expect($job->tries)->toBe(3);
});

test('calendar sync updates rescheduled meetings and adds the syncing team member', function () {
    $owner = User::factory()->create();
    $syncingUser = User::factory()->create();
    $meeting = Meeting::create([
        'user_id' => $owner->id,
        'title' => 'Original partner meeting',
        'google_event_id' => 'evt-rescheduled',
        'meeting_date' => now()->addDay()->toDateString(),
        'meeting_time' => '09:00:00',
        'meeting_end_time' => '09:30:00',
        'status' => Meeting::STATUS_NEW,
    ]);

    $start = new class
    {
        public function getDateTime(): string
        {
            return now()->addDays(2)->setTime(15, 30)->toRfc3339String();
        }

        public function getDate(): ?string
        {
            return null;
        }
    };
    $end = new class
    {
        public function getDateTime(): string
        {
            return now()->addDays(2)->setTime(16, 15)->toRfc3339String();
        }
    };
    $event = new class($start, $end)
    {
        public function __construct(private object $start, private object $end) {}

        public function getId(): string
        {
            return 'evt-rescheduled';
        }

        public function getStart(): object
        {
            return $this->start;
        }

        public function getEnd(): object
        {
            return $this->end;
        }

        public function getSummary(): string
        {
            return 'Rescheduled partner meeting';
        }

        public function getLocation(): ?string
        {
            return null;
        }

        public function getHangoutLink(): ?string
        {
            return null;
        }

        public function getDescription(): string
        {
            return '';
        }

        public function getAttendees(): array
        {
            return [];
        }
    };

    $calendarService = \Mockery::mock(GoogleCalendarService::class);
    $calendarService->shouldReceive('isConnected')->once()->andReturn(true);
    $calendarService->shouldReceive('getEvents')->once()->andReturn([$event]);

    (new SyncCalendarEvents($syncingUser))->handle($calendarService);

    $meeting->refresh();
    expect($meeting->meeting_date->isSameDay(now()->addDays(2)))->toBeTrue();
    expect($meeting->meeting_time)->toBe('15:30:00');
    expect($meeting->meeting_end_time)->toBe('16:15:00');
    expect($meeting->teamMembers()->where('users.id', $syncingUser->id)->exists())->toBeTrue();
});

test('calendar sync does not skip stakeholder meetings because of hold substring', function () {
    $user = User::factory()->create();

    $eventStart = new class
    {
        public function getDateTime(): string
        {
            return now()->addDay()->toRfc3339String();
        }

        public function getDate(): ?string
        {
            return null;
        }
    };

    $event = new class($eventStart)
    {
        public function __construct(private object $start) {}

        public function getSummary(): string
        {
            return 'Stakeholder Strategy Check-in';
        }

        public function getId(): string
        {
            return 'evt-stakeholder-test-1';
        }

        public function getStart(): object
        {
            return $this->start;
        }

        public function getAttendees(): array
        {
            return [];
        }

        public function getDescription(): string
        {
            return '';
        }

        public function getLocation(): ?string
        {
            return null;
        }

        public function getHangoutLink(): ?string
        {
            return null;
        }

        public function getConferenceData(): mixed
        {
            return null;
        }
    };

    $calendarService = \Mockery::mock(GoogleCalendarService::class);
    $calendarService->shouldReceive('isConnected')->once()->with($user)->andReturn(true);
    $calendarService->shouldReceive('getEvents')->once()->andReturn([$event]);

    $job = new SyncCalendarEvents($user);
    $job->handle($calendarService);

    expect(Meeting::where('google_event_id', 'evt-stakeholder-test-1')->exists())->toBeTrue();
});
