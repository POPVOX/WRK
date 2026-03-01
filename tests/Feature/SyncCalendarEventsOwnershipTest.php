<?php

use App\Jobs\SyncCalendarEvents;
use App\Models\Meeting;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;

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
});

test('calendar sync does not update import timestamp when sync fails', function () {
    $user = User::factory()->create();

    $calendarService = \Mockery::mock(GoogleCalendarService::class);
    $calendarService->shouldReceive('isConnected')->once()->with($user)->andReturn(true);
    $calendarService->shouldReceive('getEvents')->once()->andThrow(new RuntimeException('API unavailable'));

    $job = new SyncCalendarEvents($user);
    $job->handle($calendarService);

    $user->refresh();
    expect($user->calendar_import_date)->toBeNull();
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
