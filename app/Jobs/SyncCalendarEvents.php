<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncCalendarEvents implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 75;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 900;

    public function __construct(
        public User $user,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null
    ) {
        // Rolling window keeps sync current without hard-coded date boundaries.
        $this->startDate = $startDate ?? now()->subMonth();
        $this->endDate = $endDate ?? now()->addMonths(12);
    }

    public function uniqueId(): string
    {
        return 'calendar-user-'.$this->user->id;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(GoogleCalendarService $calendarService): void
    {
        if (! $calendarService->isConnected($this->user)) {
            Log::info("Calendar sync skipped for user {$this->user->id} - not connected");

            $this->user->update([
                'calendar_sync_status' => 'disconnected',
                'calendar_sync_error' => null,
            ]);

            return;
        }

        $this->user->update([
            'calendar_sync_status' => 'running',
            'calendar_sync_started_at' => now(),
            'calendar_sync_error' => null,
        ]);

        Log::info("Starting calendar sync for user {$this->user->id} ({$this->startDate->format('Y-m-d')} to {$this->endDate->format('Y-m-d')})");

        try {
            $events = $calendarService->getEvents($this->user, $this->startDate, $this->endDate);
            $imported = 0;
            $updated = 0;

            foreach ($events as $event) {
                // Skip events that shouldn't be imported as meetings
                if ($this->shouldSkipEvent($event)) {
                    continue;
                }

                $eventId = $event->getId();
                $start = $event->getStart();
                $dateTime = $start->getDateTime() ?? $start->getDate();
                $eventDate = Carbon::parse($dateTime);
                $meetingTime = $start->getDateTime() ? $eventDate->format('H:i:s') : null;
                $eventEnd = method_exists($event, 'getEnd') ? $event->getEnd() : null;
                $endDateTime = $eventEnd?->getDateTime();
                $meetingEndTime = $endDateTime ? Carbon::parse($endDateTime)->format('H:i:s') : null;

                // Check if meeting already exists
                $existingMeeting = Meeting::where('google_event_id', $eventId)->first();

                if ($existingMeeting) {
                    // Update title, location, and meeting link if changed
                    $title = $event->getSummary() ?? 'Untitled Event';
                    $location = $event->getLocation();
                    $hangoutLink = $event->getHangoutLink();

                    $updates = [];

                    if (! $existingMeeting->meeting_date?->isSameDay($eventDate)) {
                        $updates['meeting_date'] = $eventDate->toDateString();
                    }

                    if ($existingMeeting->meeting_time !== $meetingTime) {
                        $updates['meeting_time'] = $meetingTime;
                    }

                    if ($existingMeeting->meeting_end_time !== $meetingEndTime) {
                        $updates['meeting_end_time'] = $meetingEndTime;
                    }

                    if ($existingMeeting->title !== $title) {
                        $updates['title'] = $title;
                    }

                    // Update location if missing or changed
                    if (! $existingMeeting->location && $location) {
                        $updates['location'] = $location;
                    }

                    // Update meeting link if missing
                    if (! $existingMeeting->meeting_link) {
                        if ($hangoutLink) {
                            $updates['meeting_link'] = $hangoutLink;
                            $updates['meeting_link_type'] = 'google_meet';
                        } else {
                            // Check description for links
                            $description = $event->getDescription() ?? '';
                            if (preg_match('/(https?:\/\/[^\s]+(?:zoom\.us|meet\.google\.com|teams\.microsoft\.com)[^\s]*)/i', $description, $matches)) {
                                $updates['meeting_link'] = $matches[1];
                                if (str_contains($matches[1], 'zoom.us')) {
                                    $updates['meeting_link_type'] = 'zoom';
                                } elseif (str_contains($matches[1], 'meet.google.com')) {
                                    $updates['meeting_link_type'] = 'google_meet';
                                } elseif (str_contains($matches[1], 'teams.microsoft')) {
                                    $updates['meeting_link_type'] = 'teams';
                                }
                            }
                        }
                    }

                    if (! empty($updates)) {
                        $existingMeeting->update($updates);
                        $updated++;
                    }

                    $existingMeeting->teamMembers()->syncWithoutDetaching([$this->user->id]);

                    continue;
                }

                // Create new meeting
                $attendees = $event->getAttendees() ?? [];
                $eventAttendees = [];

                foreach ($attendees as $attendee) {
                    $email = $attendee->getEmail();
                    if ($email === $this->user->email) {
                        continue;
                    }

                    $name = $attendee->getDisplayName() ?? explode('@', $email)[0];
                    $eventAttendees[] = ['email' => $email, 'name' => $name];

                    // Create or find person
                    $person = Person::firstOrCreate(
                        ['email' => $email],
                        ['name' => $name]
                    );

                    // Try to link to organization based on email domain
                    if (! $person->organization_id) {
                        $domain = explode('@', $email)[1] ?? null;
                        if ($domain && ! in_array($domain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com'])) {
                            $org = Organization::where('website', 'like', '%'.$domain.'%')->first();
                            if ($org) {
                                $person->update(['organization_id' => $org->id]);
                            }
                        }
                    }
                }

                // Generate a meaningful title
                $rawTitle = $event->getSummary();
                $description = $event->getDescription() ?? '';
                $title = $this->generateTitle($rawTitle, $description, $eventAttendees, $eventDate);

                // Extract location
                $location = $event->getLocation();

                // Extract meeting link - check multiple sources
                $meetingLink = null;
                $meetingLinkType = null;

                // 1. Check hangoutLink (Google Meet)
                $hangoutLink = $event->getHangoutLink();
                if ($hangoutLink) {
                    $meetingLink = $hangoutLink;
                    $meetingLinkType = 'google_meet';
                }

                // 2. Check conferenceData for other video platforms
                $conferenceData = $event->getConferenceData();
                if ($conferenceData && ! $meetingLink) {
                    $entryPoints = $conferenceData->getEntryPoints();
                    if ($entryPoints) {
                        foreach ($entryPoints as $entryPoint) {
                            if ($entryPoint->getEntryPointType() === 'video') {
                                $meetingLink = $entryPoint->getUri();
                                // Detect type from URI
                                if (str_contains($meetingLink, 'zoom.us')) {
                                    $meetingLinkType = 'zoom';
                                } elseif (str_contains($meetingLink, 'meet.google.com')) {
                                    $meetingLinkType = 'google_meet';
                                } elseif (str_contains($meetingLink, 'teams.microsoft')) {
                                    $meetingLinkType = 'teams';
                                } elseif (str_contains($meetingLink, 'webex')) {
                                    $meetingLinkType = 'webex';
                                } else {
                                    $meetingLinkType = 'video';
                                }
                                break;
                            }
                        }
                    }
                }

                // 3. Check location field for Zoom/Meet links
                if (! $meetingLink && $location) {
                    if (preg_match('/(https?:\/\/[^\s]+(?:zoom\.us|meet\.google\.com|teams\.microsoft\.com)[^\s]*)/i', $location, $matches)) {
                        $meetingLink = $matches[1];
                        if (str_contains($meetingLink, 'zoom.us')) {
                            $meetingLinkType = 'zoom';
                        } elseif (str_contains($meetingLink, 'meet.google.com')) {
                            $meetingLinkType = 'google_meet';
                        } elseif (str_contains($meetingLink, 'teams.microsoft')) {
                            $meetingLinkType = 'teams';
                        }
                    }
                }

                // 4. Check description for video links
                if (! $meetingLink && $description) {
                    if (preg_match('/(https?:\/\/[^\s]+(?:zoom\.us|meet\.google\.com|teams\.microsoft\.com)[^\s]*)/i', $description, $matches)) {
                        $meetingLink = $matches[1];
                        if (str_contains($meetingLink, 'zoom.us')) {
                            $meetingLinkType = 'zoom';
                        } elseif (str_contains($meetingLink, 'meet.google.com')) {
                            $meetingLinkType = 'google_meet';
                        } elseif (str_contains($meetingLink, 'teams.microsoft')) {
                            $meetingLinkType = 'teams';
                        }
                    }
                }

                // Create meeting
                $meeting = Meeting::create([
                    'user_id' => $this->user->id,
                    'title' => $title,
                    'meeting_date' => $eventDate->toDateString(),
                    'meeting_time' => $meetingTime,
                    'meeting_end_time' => $meetingEndTime,
                    'location' => $location,
                    'meeting_link' => $meetingLink,
                    'meeting_link_type' => $meetingLinkType,
                    'raw_notes' => $description,
                    'google_event_id' => $eventId,
                    'status' => Meeting::STATUS_NEW,
                ]);

                $meeting->teamMembers()->syncWithoutDetaching([$this->user->id]);

                // Link attendees
                foreach ($eventAttendees as $att) {
                    $person = Person::where('email', $att['email'])->first();
                    if ($person) {
                        $meeting->people()->syncWithoutDetaching([$person->id]);
                    }
                }

                $imported++;
            }

            Log::info("Calendar sync completed for user {$this->user->id}: {$imported} new, {$updated} updated");

            $this->user->update([
                'calendar_import_date' => now(),
                'calendar_sync_status' => 'succeeded',
                'calendar_sync_completed_at' => now(),
                'calendar_sync_failed_at' => null,
                'calendar_sync_error' => null,
            ]);
        } catch (Throwable $exception) {
            $this->recordFailure($exception);

            Log::error("Calendar sync failed for user {$this->user->id}", [
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->recordFailure($exception ?? new \RuntimeException('Calendar sync job failed.'));
    }

    protected function recordFailure(Throwable $exception): void
    {
        $message = preg_replace(
            '/(access_token|refresh_token)=([^&\s]+)/i',
            '$1=[redacted]',
            $exception->getMessage()
        ) ?: 'Calendar sync failed.';

        $this->user->update([
            'calendar_sync_status' => 'failed',
            'calendar_sync_failed_at' => now(),
            'calendar_sync_error' => Str::limit($message, 1000),
        ]);
    }

    /**
     * Generate a meaningful meeting title from available data.
     */
    protected function generateTitle(?string $rawTitle, string $description, array $attendees, Carbon $date): string
    {
        // If we have a good summary that's not just a date, use it
        if ($rawTitle && ! preg_match('/^\d{1,2}\/\d{1,2}|^\w+day|^meeting$/i', $rawTitle)) {
            return $rawTitle;
        }

        // Try to extract a meaningful title from the description
        if ($description) {
            // Look for lines starting with ** which often contain the meeting focus
            if (preg_match('/\*\*(.+?)\*\*/', $description, $matches)) {
                $extracted = trim($matches[1]);
                if (strlen($extracted) > 5 && strlen($extracted) < 100) {
                    return $extracted;
                }
            }

            // Use first non-empty line of description if short enough
            $firstLine = trim(strtok($description, "\n"));
            if (strlen($firstLine) > 5 && strlen($firstLine) < 80) {
                return $firstLine;
            }
        }

        // Build title from attendee names
        if (! empty($attendees)) {
            $names = array_slice(array_column($attendees, 'name'), 0, 3);
            $nameList = implode(', ', $names);
            if (count($attendees) > 3) {
                $nameList .= ' +'.(count($attendees) - 3);
            }

            return "Meeting with {$nameList}";
        }

        // Fall back to date-based title
        return 'Meeting: '.$date->format('M j, Y');
    }

    /**
     * Determine if an event should be skipped (not imported as a meeting).
     * Filters out personal calendar items like lunch, work blocks, reminders, etc.
     */
    protected function shouldSkipEvent($event): bool
    {
        $summary = strtolower($event->getSummary() ?? '');

        // Skip events with no title
        if (empty(trim($summary))) {
            return true;
        }

        // Patterns for events to skip
        $skipPatterns = [
            // Meals and breaks
            'lunch',
            'breakfast',
            'dinner',
            'coffee break',
            'break time',

            // Work blocks and focus time
            'focus time',
            'focus block',
            'work block',
            'blocked',
            'do not book',
            'busy',
            'hold',
            'placeholder',

            // Personal items
            'dentist',
            'doctor',
            'appointment',
            'personal',
            'pto',
            'vacation',
            'out of office',
            'ooo',
            'day off',
            'sick',
            'errand',

            // Travel/commute
            'commute',
            'travel time',
            'flight',
            'driving',

            // Internal/recurring
            'standup',
            'stand-up',
            'daily sync',
            'team sync',
            '1:1',
            '1-1',
            'one on one',
        ];

        foreach ($skipPatterns as $pattern) {
            if ($this->containsStandalonePattern($summary, $pattern)) {
                return true;
            }
        }

        // Skip all-day events (usually OOO, holidays, etc.)
        $start = $event->getStart();
        if ($start && $start->getDate() && ! $start->getDateTime()) {
            return true;
        }

        // Skip events where user declined
        $attendees = $event->getAttendees() ?? [];
        foreach ($attendees as $attendee) {
            if ($attendee->getEmail() === $this->user->email) {
                $responseStatus = $attendee->getResponseStatus();
                if ($responseStatus === 'declined') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function containsStandalonePattern(string $summary, string $pattern): bool
    {
        $escaped = preg_quote($pattern, '/');

        return (bool) preg_match('/(^|[\\s\\-:()\\[\\],.])'.$escaped.'($|[\\s\\-:()\\[\\],.])/i', $summary);
    }
}
