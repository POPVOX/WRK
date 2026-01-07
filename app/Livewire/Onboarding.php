<?php

namespace App\Livewire;

use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Welcome to WRK')]
class Onboarding extends Component
{
    use WithFileUploads;

    // Current step (1-4)
    public int $step = 1;

    // Profile fields
    public string $bio = '';

    public string $title = '';

    public string $linkedin = '';

    public string $timezone = '';

    public $photo;

    // Calendar import fields
    public bool $isCalendarConnected = false;

    public string $importRange = '3_months'; // How far back to import

    public array $calendarEvents = [];

    public array $selectedEvents = [];

    public array $extractedPeople = [];

    public array $selectedPeople = [];

    public array $extractedOrgs = [];

    public array $selectedOrgs = [];

    public bool $isLoadingEvents = false;

    public bool $isImporting = false;

    public string $importMessage = '';

    protected GoogleCalendarService $calendarService;

    public function boot(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function mount()
    {
        // Refresh user from database to get latest tokens
        $user = Auth::user()->fresh();

        // If profile already completed, redirect to dashboard
        if ($user->profile_completed_at) {
            return redirect()->route('dashboard');
        }

        // Pre-fill from existing user data
        $this->bio = $user->bio ?? '';
        $this->title = $user->title ?? '';
        $this->linkedin = $user->linkedin ?? '';
        $this->timezone = $user->timezone ?? 'America/New_York';

        // Check if returning from Google OAuth (URL parameter)
        if (request()->has('calendarConnected')) {
            // Re-check connection status with fresh user data
            $this->isCalendarConnected = $this->calendarService->isConnected($user);
            $this->step = 2;
            session(['onboarding_step' => 2]);

            return;
        }

        // Check if calendar is connected
        $this->isCalendarConnected = $this->calendarService->isConnected($user);

        // Restore step from session
        $savedStep = session('onboarding_step', 1);
        if ($savedStep > 1 && $savedStep <= 4) {
            $this->step = $savedStep;
        }
    }

    public function updatedStep($value)
    {
        // Persist step to session so it survives OAuth redirects
        session(['onboarding_step' => $value]);
    }

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:2048', // 2MB max
        ]);
    }

    public function saveProfile()
    {
        $this->validate([
            'bio' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:255',
            'linkedin' => 'nullable|url|max:255',
            'timezone' => 'required|string',
        ]);

        $user = Auth::user();

        $updateData = [
            'bio' => $this->bio,
            'title' => $this->title,
            'linkedin' => $this->linkedin,
            'timezone' => $this->timezone,
        ];

        // Handle photo upload
        if ($this->photo) {
            $path = $this->photo->store('profile-photos', 'public');
            $updateData['photo_url'] = '/storage/'.$path;
        }

        $user->update($updateData);

        $this->step = 2;
        session(['onboarding_step' => 2]);
    }

    public function connectCalendar()
    {
        // Save current step before OAuth redirect
        session(['onboarding_step' => 2]);

        return redirect($this->calendarService->getAuthUrl());
    }

    public function skipCalendar()
    {
        $this->step = 3;
        session(['onboarding_step' => 3]);
    }

    public function fetchCalendarEvents()
    {
        if (! $this->isCalendarConnected) {
            return;
        }

        $this->isLoadingEvents = true;
        $user = Auth::user();

        // Determine date range based on selection (past meetings)
        $startDate = match ($this->importRange) {
            '1_month' => now()->subMonth(),
            '3_months' => now()->subMonths(3),
            '6_months' => now()->subMonths(6),
            '1_year' => now()->subYear(),
            '2_years' => now()->subYears(2),
            default => now()->subMonths(3),
        };

        // Fetch past meetings AND future meetings (next 2 weeks)
        $endDate = now()->addWeeks(2);
        $events = $this->calendarService->getEvents($user, $startDate, $endDate);

        $this->calendarEvents = [];
        $this->extractedPeople = [];
        $this->extractedOrgs = [];
        $peopleEmails = [];
        $orgDomains = [];

        foreach ($events as $event) {
            $eventId = $event->getId();
            $attendees = $event->getAttendees() ?? [];
            $eventAttendees = [];

            foreach ($attendees as $attendee) {
                $email = $attendee->getEmail();
                $name = $attendee->getDisplayName() ?? explode('@', $email)[0];

                // Skip the current user
                if ($email === $user->email) {
                    continue;
                }

                $eventAttendees[] = [
                    'email' => $email,
                    'name' => $name,
                ];

                // Extract unique people
                if (! isset($peopleEmails[$email])) {
                    $peopleEmails[$email] = [
                        'email' => $email,
                        'name' => $name,
                        'meeting_count' => 1,
                    ];
                } else {
                    $peopleEmails[$email]['meeting_count']++;
                }

                // Extract organizations from email domains
                $domain = explode('@', $email)[1] ?? null;
                if ($domain && ! in_array($domain, ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com'])) {
                    if (! isset($orgDomains[$domain])) {
                        $orgDomains[$domain] = [
                            'domain' => $domain,
                            'name' => $this->formatOrgName($domain),
                            'contact_count' => 1,
                        ];
                    } else {
                        $orgDomains[$domain]['contact_count']++;
                    }
                }
            }

            $start = $event->getStart();
            $dateTime = $start->getDateTime() ?? $start->getDate();
            $eventDate = \Carbon\Carbon::parse($dateTime);

            $this->calendarEvents[] = [
                'id' => $eventId,
                'title' => $event->getSummary() ?? 'Untitled Event',
                'date' => $eventDate->format('M j, Y'),
                'dateRaw' => $dateTime,
                'description' => $event->getDescription() ?? '',
                'attendees' => $eventAttendees,
                'attendee_count' => count($eventAttendees),
                'isUpcoming' => $eventDate->isFuture(),
            ];
        }

        // Convert to arrays with keys for selection
        $this->extractedPeople = array_values($peopleEmails);
        $this->extractedOrgs = array_values($orgDomains);

        // Pre-select all
        $this->selectedEvents = array_column($this->calendarEvents, 'id');
        $this->selectedPeople = array_column($this->extractedPeople, 'email');
        $this->selectedOrgs = array_column($this->extractedOrgs, 'domain');

        $this->isLoadingEvents = false;
        $this->step = 3;
    }

    protected function formatOrgName(string $domain): string
    {
        // Remove common TLDs and format nicely
        $name = explode('.', $domain)[0];

        return ucwords(str_replace(['-', '_'], ' ', $name));
    }

    /**
     * Generate a meaningful meeting title from available data.
     */
    protected function generateMeetingTitle(?string $rawTitle, string $description, array $attendees, \Carbon\Carbon $date): string
    {
        // If we have a good summary that's not just a date or generic, use it
        if ($rawTitle && ! preg_match('/^\d{1,2}\/\d{1,2}|^\w+day|^meeting$|^untitled/i', $rawTitle)) {
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

    public function toggleEvent(string $eventId)
    {
        if (in_array($eventId, $this->selectedEvents)) {
            $this->selectedEvents = array_filter($this->selectedEvents, fn ($id) => $id !== $eventId);
        } else {
            $this->selectedEvents[] = $eventId;
        }
    }

    public function togglePerson(string $email)
    {
        if (in_array($email, $this->selectedPeople)) {
            $this->selectedPeople = array_filter($this->selectedPeople, fn ($e) => $e !== $email);
        } else {
            $this->selectedPeople[] = $email;
        }
    }

    public function toggleOrg(string $domain)
    {
        if (in_array($domain, $this->selectedOrgs)) {
            $this->selectedOrgs = array_filter($this->selectedOrgs, fn ($d) => $d !== $domain);
        } else {
            $this->selectedOrgs[] = $domain;
        }
    }

    public function selectAllEvents()
    {
        $this->selectedEvents = array_column($this->calendarEvents, 'id');
    }

    public function deselectAllEvents()
    {
        $this->selectedEvents = [];
    }

    public function selectAllPeople()
    {
        $this->selectedPeople = array_column($this->extractedPeople, 'email');
    }

    public function deselectAllPeople()
    {
        $this->selectedPeople = [];
    }

    public function selectAllOrgs()
    {
        $this->selectedOrgs = array_column($this->extractedOrgs, 'domain');
    }

    public function deselectAllOrgs()
    {
        $this->selectedOrgs = [];
    }

    public function importSelected()
    {
        $this->isImporting = true;
        $user = Auth::user();

        $importedMeetings = 0;
        $importedPeople = 0;
        $importedOrgs = 0;

        // Import selected organizations first
        $orgMap = []; // domain => org_id
        foreach ($this->extractedOrgs as $org) {
            if (in_array($org['domain'], $this->selectedOrgs)) {
                $existing = Organization::where('website', 'like', '%'.$org['domain'].'%')->first();
                if (! $existing) {
                    $newOrg = Organization::create([
                        'name' => $org['name'],
                        'website' => 'https://'.$org['domain'],
                    ]);
                    $orgMap[$org['domain']] = $newOrg->id;
                    $importedOrgs++;
                } else {
                    $orgMap[$org['domain']] = $existing->id;
                }
            }
        }

        // Import selected people
        foreach ($this->extractedPeople as $person) {
            if (in_array($person['email'], $this->selectedPeople)) {
                $existing = Person::where('email', $person['email'])->first();
                if (! $existing) {
                    $domain = explode('@', $person['email'])[1] ?? null;
                    $orgId = $domain ? ($orgMap[$domain] ?? null) : null;

                    Person::create([
                        'name' => $person['name'],
                        'email' => $person['email'],
                        'organization_id' => $orgId,
                    ]);
                    $importedPeople++;
                }
            }
        }

        // Import selected meetings
        foreach ($this->calendarEvents as $event) {
            if (in_array($event['id'], $this->selectedEvents)) {
                // Skip if already imported
                if (Meeting::where('google_event_id', $event['id'])->exists()) {
                    continue;
                }

                // Generate a meaningful title
                $title = $this->generateMeetingTitle(
                    $event['title'],
                    $event['description'] ?? '',
                    $event['attendees'] ?? [],
                    \Carbon\Carbon::parse($event['dateRaw'])
                );

                $meeting = Meeting::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'meeting_date' => \Carbon\Carbon::parse($event['dateRaw']),
                    'raw_notes' => $event['description'] ?? '',
                    'google_event_id' => $event['id'],
                    'status' => Meeting::STATUS_NEW,
                ]);

                // Link people to meeting
                foreach ($event['attendees'] as $attendee) {
                    $person = Person::where('email', $attendee['email'])->first();
                    if ($person) {
                        $meeting->people()->syncWithoutDetaching([$person->id]);
                    }
                }

                $importedMeetings++;
            }
        }

        // Update user's calendar import date
        $user->update(['calendar_import_date' => now()]);

        $this->importMessage = "Imported {$importedMeetings} meetings, {$importedPeople} contacts, and {$importedOrgs} organizations.";
        $this->isImporting = false;
        $this->step = 4;
    }

    public function skipImport()
    {
        $this->step = 4;
        session(['onboarding_step' => 4]);
    }

    public function completeOnboarding()
    {
        Auth::user()->update(['profile_completed_at' => now()]);
        session()->forget('onboarding_step');

        return redirect()->route('dashboard');
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
            session(['onboarding_step' => $this->step]);
        }
    }

    public function render()
    {
        return view('livewire.onboarding');
    }
}
