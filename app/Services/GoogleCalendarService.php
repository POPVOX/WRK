<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Gmail as GoogleGmail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    protected GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient;
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $scopes = (array) config('services.google.workspace_scopes', [
            GoogleCalendar::CALENDAR_READONLY,
            GoogleGmail::GMAIL_READONLY,
        ]);
        foreach (array_values(array_unique(array_filter($scopes))) as $scope) {
            $this->client->addScope($scope);
        }
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Get the OAuth authorization URL with CSRF state parameter.
     */
    public function getAuthUrl(): string
    {
        // Generate a random state token for CSRF protection
        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        $this->client->setState($state);

        return $this->client->createAuthUrl();
    }

    /**
     * Verify the state parameter from the callback.
     */
    public function verifyState(?string $state): bool
    {
        $sessionState = session('google_oauth_state');
        session()->forget('google_oauth_state');

        return $state && $sessionState && hash_equals($sessionState, $state);
    }

    /**
     * Handle the OAuth callback and store tokens.
     */
    public function handleCallback(string $code, User $user): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        // Check for errors in the token response
        if (isset($token['error'])) {
            \Log::error('Google OAuth token error', [
                'error' => $token['error'],
                'error_description' => $token['error_description'] ?? 'No description',
            ]);
            throw new \Exception('Google OAuth error: ' . ($token['error_description'] ?? $token['error']));
        }

        if (!isset($token['access_token'])) {
            \Log::error('Google OAuth missing access_token', ['response' => $token]);
            throw new \Exception('No access token received from Google');
        }

        $user->update([
            'google_access_token' => $token['access_token'],
            'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
            'google_token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
        ]);
    }

    /**
     * Disconnect Google Calendar.
     */
    public function disconnect(User $user): void
    {
        $updates = [
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
        ];

        if (Schema::hasColumn('users', 'gmail_import_date')) {
            $updates['gmail_import_date'] = null;
        }
        if (Schema::hasColumn('users', 'gmail_history_id')) {
            $updates['gmail_history_id'] = null;
        }

        $user->update($updates);
    }

    /**
     * Check if a user has Google Calendar connected.
     */
    public function isConnected(User $user): bool
    {
        return !empty($user->google_access_token);
    }

    /**
     * Get the Google Calendar service for a user.
     */
    public function getCalendarService(User $user): ?GoogleCalendar
    {
        if (!$this->isConnected($user)) {
            return null;
        }

        // Refresh token if expired
        if ($user->google_token_expires_at && $user->google_token_expires_at->isPast()) {
            $this->refreshToken($user);
        }

        $this->client->setAccessToken($user->google_access_token);

        return new GoogleCalendar($this->client);
    }

    /**
     * Refresh the access token.
     */
    protected function refreshToken(User $user): void
    {
        if (!$user->google_refresh_token) {
            return;
        }

        $this->client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
        $token = $this->client->getAccessToken();

        $user->update([
            'google_access_token' => $token['access_token'],
            'google_token_expires_at' => now()->addSeconds($token['expires_in']),
        ]);
    }

    /**
     * Get calendar events for a date range.
     */
    public function getEvents(User $user, $startDate = null, $endDate = null): array
    {
        $service = $this->getCalendarService($user);
        if (!$service) {
            return [];
        }

        // Convert dates to Carbon if they aren't already
        // Default to 6 months back to 6 months ahead for wider coverage
        $start = $startDate instanceof \Carbon\Carbon
            ? $startDate
            : ($startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonths(6));

        $end = $endDate instanceof \Carbon\Carbon
            ? $endDate
            : ($endDate ? \Carbon\Carbon::parse($endDate) : now()->addMonths(6));

        $calendarId = 'primary';
        $allEvents = [];
        $pageToken = null;

        try {
            // Paginate through all results
            do {
                $optParams = [
                    'maxResults' => 250,
                    'orderBy' => 'startTime',
                    'singleEvents' => true,
                    'timeMin' => $start->toRfc3339String(),
                    'timeMax' => $end->toRfc3339String(),
                ];

                if ($pageToken) {
                    $optParams['pageToken'] = $pageToken;
                }

                $results = $service->events->listEvents($calendarId, $optParams);
                $allEvents = array_merge($allEvents, $results->getItems());
                $pageToken = $results->getNextPageToken();

            } while ($pageToken);

            return $allEvents;
        } catch (\Exception $e) {
            \Log::error('Google Calendar Error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Import calendar events as meetings.
     */
    public function importEvents(User $user, array $events): array
    {
        $imported = [];
        $skipped = [];

        foreach ($events as $event) {
            $eventId = $event->getId();
            $summary = $event->getSummary() ?? '';

            // Skip if already imported
            if (Meeting::where('google_event_id', $eventId)->exists()) {
                $skipped[] = $summary;

                continue;
            }

            // Skip personal/internal events that shouldn't be imported as meetings
            // Matches: Lunch, lunch break, OOO, Out of Office, Focus Time, Busy, etc.
            if ($this->isPersonalEvent($summary)) {
                $skipped[] = $summary . ' (personal event)';

                continue;
            }

            // Get event date
            $start = $event->getStart();
            $dateTime = $start->getDateTime() ?? $start->getDate();
            $meetingDate = \Carbon\Carbon::parse($dateTime);

            // Create meeting
            $meeting = Meeting::create([
                'user_id' => $user->id,
                'meeting_date' => $meetingDate,
                'raw_notes' => $event->getDescription() ?? '',
                'google_event_id' => $eventId,
                'status' => Meeting::STATUS_NEW,
            ]);

            // Set meeting title/summary from event
            if ($summary) {
                $meeting->update(['raw_notes' => "**{$summary}**\n\n" . ($event->getDescription() ?? '')]);
            }

            $imported[] = $summary ?: 'Untitled event';
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Check if an event title indicates a personal/internal event that shouldn't be imported.
     */
    protected function isPersonalEvent(string $title): bool
    {
        $lowerTitle = strtolower(trim($title));

        // Exact matches for common personal events
        $personalKeywords = [
            'lunch',
            'lunch break',
            'ooo',
            'out of office',
            'pto',
            'vacation',
            'holiday',
            'focus time',
            'busy',
            'block',
            'work block',
            'do not book',
            'dnb',
            'personal',
            'dentist',
            'doctor',
            'appointment',
            'commute',
            'travel time',
            'break',
            'gym',
            'workout',
            // Internal team events are typically not tracked as external meetings
            'standup',
            'stand-up',
            'stand up',
            'daily standup',
            'team standup',
            'all hands',
            '1:1',
            '1-1',
            'one on one',
        ];

        foreach ($personalKeywords as $keyword) {
            // Exact match or keyword at start/end of title
            if (
                $lowerTitle === $keyword ||
                str_starts_with($lowerTitle, $keyword . ' ') ||
                str_ends_with($lowerTitle, ' ' . $keyword) ||
                str_contains($lowerTitle, ' ' . $keyword . ' ')
            ) {
                return true;
            }
        }

        return false;
    }
}
