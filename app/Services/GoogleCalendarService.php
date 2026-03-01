<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Agents\AgentCredentialService;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Gmail as GoogleGmail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    protected GoogleClient $client;

    public function __construct(
        protected AgentCredentialService $agentCredentialService
    ) {
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
    public function isConnected(User $user, ?Agent $agent = null): bool
    {
        $credential = $this->resolveGoogleCredential($user, $agent);

        return trim((string) ($credential['access_token'] ?? '')) !== '';
    }

    /**
     * Get the Google Calendar service for a user.
     */
    public function getCalendarService(User $user, ?Agent $agent = null): ?GoogleCalendar
    {
        $credential = $this->resolveGoogleCredential($user, $agent);
        if (trim((string) ($credential['access_token'] ?? '')) === '') {
            return null;
        }

        $expiresAt = $credential['expires_at'] ?? null;
        if ($expiresAt instanceof \Carbon\CarbonInterface && $expiresAt->isPast()) {
            $this->refreshToken($user, $agent, $credential);
            $user->refresh();
            $credential = $this->resolveGoogleCredential($user, $agent);
        }

        $this->client->setAccessToken((string) ($credential['access_token'] ?? ''));

        return new GoogleCalendar($this->client);
    }

    /**
     * @param  array<string,mixed>|null  $credential
     */
    protected function refreshToken(User $user, ?Agent $agent = null, ?array $credential = null): bool
    {
        $credential ??= $this->resolveGoogleCredential($user, $agent);
        $refreshToken = trim((string) ($credential['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            return false;
        }

        $token = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (! is_array($token)) {
            $token = $this->client->getAccessToken();
        }

        if (! empty($token['error']) || empty($token['access_token'])) {
            \Log::warning('Google token refresh failed for Calendar', [
                'user_id' => $user->id,
                'agent_id' => $agent?->id,
                'error' => $token['error'] ?? 'missing_access_token',
                'error_description' => $token['error_description'] ?? null,
            ]);

            return false;
        }

        if ($agent) {
            $existing = $this->agentCredentialService->getCredential($agent, 'gcal');
            $existingTokenData = is_array($existing?->token_data) ? $existing->token_data : [];
            $tokenData = array_merge($existingTokenData, [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? $refreshToken,
                'token_type' => $token['token_type'] ?? ($existingTokenData['token_type'] ?? 'Bearer'),
            ]);

            $this->agentCredentialService->storeCredential(
                $agent,
                'gcal',
                $tokenData,
                is_array($existing?->scopes) ? $existing->scopes : [],
                now()->addSeconds((int) ($token['expires_in'] ?? 3600))
            );

            return true;
        }

        $user->update([
            'google_access_token' => $token['access_token'],
            'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
            'google_token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
        ]);

        return true;
    }

    /**
     * @return array{
     *   source:string,
     *   access_token:string,
     *   refresh_token:string,
     *   expires_at:\Carbon\CarbonInterface|null
     * }
     */
    protected function resolveGoogleCredential(User $user, ?Agent $agent = null): array
    {
        if ($agent) {
            $agentCredential = $this->agentCredentialService->getCredential($agent, 'gcal');
            $tokenData = is_array($agentCredential?->token_data) ? $agentCredential->token_data : [];
            $expiresAt = $agentCredential?->expires_at;

            if (! $expiresAt && ! empty($tokenData['expires_at'])) {
                try {
                    $expiresAt = \Carbon\Carbon::parse((string) $tokenData['expires_at']);
                } catch (\Throwable) {
                    $expiresAt = null;
                }
            }

            return [
                'source' => 'agent',
                'access_token' => trim((string) ($tokenData['access_token'] ?? '')),
                'refresh_token' => trim((string) ($tokenData['refresh_token'] ?? '')),
                'expires_at' => $expiresAt,
            ];
        }

        return [
            'source' => 'user',
            'access_token' => trim((string) $user->google_access_token),
            'refresh_token' => trim((string) $user->google_refresh_token),
            'expires_at' => $user->google_token_expires_at,
        ];
    }

    /**
     * Get calendar events for a date range.
     */
    public function getEvents(User $user, $startDate = null, $endDate = null, ?Agent $agent = null): array
    {
        // Convert dates to Carbon if they aren't already
        // Default to 6 months back to 6 months ahead for wider coverage
        $start = $startDate instanceof \Carbon\Carbon
            ? $startDate
            : ($startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonths(6));

        $end = $endDate instanceof \Carbon\Carbon
            ? $endDate
            : ($endDate ? \Carbon\Carbon::parse($endDate) : now()->addMonths(6));

        $calendarId = 'primary';
        $allowRetry = true;

        while (true) {
            $service = $this->getCalendarService($user, $agent);
            if (! $service) {
                return [];
            }

            $allEvents = [];
            $pageToken = null;

            // Paginate through all results
            try {
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
            } catch (\Throwable $e) {
                if ($allowRetry && $this->isAuthError($e) && $this->refreshToken($user, $agent)) {
                    $allowRetry = false;
                    continue;
                }

                \Log::error('Google Calendar Error: '.$e->getMessage());

                return [];
            }
        }
    }

    protected function isAuthError(\Throwable $exception): bool
    {
        $code = (int) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($code, [401, 403], true)
            || str_contains($message, 'invalid credentials')
            || str_contains($message, 'autherror')
            || str_contains($message, 'unauthenticated')
            || str_contains($message, 'login required');
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

            $meeting->teamMembers()->syncWithoutDetaching([$user->id]);

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
