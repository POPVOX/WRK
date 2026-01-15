<?php

namespace App\Livewire\Travel;

use App\Models\CountryTravelAdvisory;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Plan a Trip')]
class TripCreate extends Component
{
    public int $step = 1;

    public int $totalSteps = 3;

    // Step 1: Basic Info
    public string $name = '';

    public string $description = '';

    public string $type = 'conference_event';

    public string $startDate = '';

    public string $endDate = '';

    // Multiple destinations support
    public array $destinations = [];

    // For adding new destination
    public string $newDestCity = '';

    public string $newDestStateProvince = '';

    public string $newDestCountry = '';

    public string $newDestArrivalDate = '';

    public string $newDestDepartureDate = '';

    // Multiple projects support
    public array $selectedProjectIds = [];

    public ?int $partnerOrganizationId = null;

    public string $partnerProgramName = '';

    // Add new organization inline
    public bool $showAddOrg = false;

    public string $newOrgName = '';

    public string $newOrgType = 'nonprofit';

    public string $newOrgWebsite = '';

    // Step 2: Travelers
    public array $selectedTravelers = [];

    public ?int $leadTravelerId = null;

    // Step 3: Compliance (auto-populated)
    public ?array $travelAdvisory = null;

    public bool $stepRegistrationRequired = false;

    public bool $travelInsuranceRequired = false;

    public bool $approvalRequired = false;

    public string $riskLevel = 'standard';

    // Country list for dropdown
    public array $countries = [];

    public function mount(): void
    {
        // Default start date to next week
        $this->startDate = now()->addWeek()->format('Y-m-d');
        $this->endDate = now()->addWeeks(2)->format('Y-m-d');

        // Set default dates for destination form
        $this->newDestArrivalDate = $this->startDate;
        $this->newDestDepartureDate = $this->endDate;

        // Add current user as default traveler and lead
        $this->selectedTravelers = [Auth::id()];
        $this->leadTravelerId = Auth::id();

        // Load countries
        $this->countries = $this->getCountryList();
    }

    public function updatedStartDate(): void
    {
        // Update default arrival date for new destinations
        if (empty($this->newDestArrivalDate) || $this->newDestArrivalDate < $this->startDate) {
            $this->newDestArrivalDate = $this->startDate;
        }
    }

    public function updatedEndDate(): void
    {
        // Update default departure date for new destinations
        if (empty($this->newDestDepartureDate) || $this->newDestDepartureDate > $this->endDate) {
            $this->newDestDepartureDate = $this->endDate;
        }
    }

    protected function getCountryList(): array
    {
        // Common travel destinations first
        return [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'DE' => 'Germany',
            'FR' => 'France',
            'BE' => 'Belgium',
            'NL' => 'Netherlands',
            'CH' => 'Switzerland',
            'MX' => 'Mexico',
            'BR' => 'Brazil',
            'AR' => 'Argentina',
            'CO' => 'Colombia',
            'CL' => 'Chile',
            'KE' => 'Kenya',
            'ZA' => 'South Africa',
            'NG' => 'Nigeria',
            'GH' => 'Ghana',
            'EG' => 'Egypt',
            'MA' => 'Morocco',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'TW' => 'Taiwan',
            'SG' => 'Singapore',
            'IN' => 'India',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'PH' => 'Philippines',
            'ID' => 'Indonesia',
            'TH' => 'Thailand',
            'VN' => 'Vietnam',
            'AE' => 'United Arab Emirates',
            'IL' => 'Israel',
            'JO' => 'Jordan',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'PT' => 'Portugal',
            'PL' => 'Poland',
            'CZ' => 'Czech Republic',
            'AT' => 'Austria',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'IE' => 'Ireland',
            'GR' => 'Greece',
        ];
    }

    // Destination Management
    public function addDestination(): void
    {
        $this->validate([
            'newDestCity' => 'required|string|max:100',
            'newDestCountry' => 'required|string|size:2',
            'newDestArrivalDate' => 'required|date',
            'newDestDepartureDate' => 'required|date|after_or_equal:newDestArrivalDate',
        ]);

        $advisory = CountryTravelAdvisory::findByCountryCode($this->newDestCountry);

        $this->destinations[] = [
            'city' => $this->newDestCity,
            'state_province' => $this->newDestStateProvince ?: null,
            'country' => $this->newDestCountry,
            'country_name' => $this->countries[$this->newDestCountry] ?? $this->newDestCountry,
            'arrival_date' => $this->newDestArrivalDate,
            'departure_date' => $this->newDestDepartureDate,
            'advisory_level' => $advisory?->advisory_level,
            'is_prohibited' => $advisory?->is_prohibited ?? false,
        ];

        // Reset form
        $this->reset(['newDestCity', 'newDestStateProvince', 'newDestCountry', 'newDestArrivalDate', 'newDestDepartureDate']);

        // Set default dates for next destination
        $this->newDestArrivalDate = $this->startDate;
        $this->newDestDepartureDate = $this->endDate;
    }

    public function removeDestination(int $index): void
    {
        unset($this->destinations[$index]);
        $this->destinations = array_values($this->destinations);
    }

    // Organization Management
    public function openAddOrg(): void
    {
        $this->reset(['newOrgName', 'newOrgType', 'newOrgWebsite']);
        $this->showAddOrg = true;
    }

    public function closeAddOrg(): void
    {
        $this->showAddOrg = false;
    }

    public function saveNewOrg(): void
    {
        $this->validate([
            'newOrgName' => 'required|string|max:255',
            'newOrgType' => 'required|in:nonprofit,government,corporate,university,media,other',
            'newOrgWebsite' => 'nullable|url|max:255',
        ]);

        $org = Organization::create([
            'name' => $this->newOrgName,
            'type' => $this->newOrgType,
            'website' => $this->newOrgWebsite ?: null,
        ]);

        $this->partnerOrganizationId = $org->id;
        $this->showAddOrg = false;
        $this->dispatch('notify', type: 'success', message: 'Organization created!');
    }

    protected function checkTravelAdvisories(): void
    {
        // Check advisories for all destinations
        $this->stepRegistrationRequired = false;
        $this->travelInsuranceRequired = false;
        $this->approvalRequired = false;
        $this->riskLevel = 'standard';
        $this->travelAdvisory = null;

        $highestLevel = '1';

        foreach ($this->destinations as $dest) {
            $advisory = CountryTravelAdvisory::findByCountryCode($dest['country']);
            if ($advisory) {
                if ($advisory->advisory_level > $highestLevel) {
                    $highestLevel = $advisory->advisory_level;
                    $this->travelAdvisory = [
                        'level' => $advisory->advisory_level,
                        'title' => $advisory->advisory_title,
                        'summary' => $advisory->advisory_summary,
                        'is_prohibited' => $advisory->is_prohibited,
                        'url' => $advisory->state_dept_url,
                        'country' => $dest['city'].', '.$dest['country_name'],
                    ];
                }

                if ($advisory->requiresStepRegistration()) {
                    $this->stepRegistrationRequired = true;
                }
                if ($advisory->requiresTravelInsurance()) {
                    $this->travelInsuranceRequired = true;
                }
                if ($advisory->requiresApproval()) {
                    $this->approvalRequired = true;
                }
            }
        }

        $this->riskLevel = match ($highestLevel) {
            '1' => 'standard',
            '2' => 'moderate',
            '3' => 'high',
            '4' => 'prohibited',
            default => 'standard',
        };
    }

    public function toggleTraveler(int $userId): void
    {
        if (in_array($userId, $this->selectedTravelers)) {
            $this->selectedTravelers = array_values(array_diff($this->selectedTravelers, [$userId]));

            // If removing lead, reset lead
            if ($this->leadTravelerId === $userId) {
                $this->leadTravelerId = $this->selectedTravelers[0] ?? null;
            }
        } else {
            $this->selectedTravelers[] = $userId;
        }
    }

    public function setLead(int $userId): void
    {
        if (in_array($userId, $this->selectedTravelers)) {
            $this->leadTravelerId = $userId;
        }
    }

    public function nextStep(): void
    {
        $this->validateStep();

        if ($this->step < $this->totalSteps) {
            $this->step++;
        }

        // Check advisories when moving to step 3
        if ($this->step === 3) {
            $this->checkTravelAdvisories();
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    protected function validateStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:'.implode(',', array_keys(Trip::getTypeOptions())),
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
                'destinations' => 'required|array|min:1',
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'selectedTravelers' => 'required|array|min:1',
                'leadTravelerId' => 'required|integer',
            ]);
        }
    }

    public function createTrip(): void
    {
        $this->validateStep();

        // Use first destination as primary
        $primaryDest = $this->destinations[0] ?? null;

        $trip = Trip::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'status' => 'planning',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'primary_destination_city' => $primaryDest['city'] ?? '',
            'primary_destination_country' => $primaryDest['country'] ?? '',
            'partner_organization_id' => $this->partnerOrganizationId ?: null,
            'partner_program_name' => $this->partnerProgramName ?: null,
            'created_by' => Auth::id(),
            'risk_level' => $this->riskLevel,
            'step_registration_required' => $this->stepRegistrationRequired,
            'travel_insurance_required' => $this->travelInsuranceRequired,
            'approval_required' => $this->approvalRequired,
        ]);

        // Attach projects (many-to-many)
        if (! empty($this->selectedProjectIds)) {
            $trip->projects()->attach($this->selectedProjectIds);
        }

        // Attach travelers
        foreach ($this->selectedTravelers as $userId) {
            $trip->travelers()->attach($userId, [
                'role' => $userId === $this->leadTravelerId ? 'lead' : 'participant',
            ]);
        }

        // Create all destinations
        foreach ($this->destinations as $index => $dest) {
            $trip->destinations()->create([
                'order' => $index + 1,
                'city' => $dest['city'],
                'state_province' => $dest['state_province'] ?? null,
                'country' => $dest['country'],
                'arrival_date' => $dest['arrival_date'],
                'departure_date' => $dest['departure_date'],
                'state_dept_level' => $dest['advisory_level'] ?? null,
                'is_prohibited_destination' => $dest['is_prohibited'] ?? false,
            ]);
        }

        $this->redirect(route('travel.show', $trip), navigate: true);
    }

    public function getTeamMembersProperty()
    {
        return User::where('role', '!=', 'guest')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function getProjectsProperty()
    {
        return Project::whereIn('status', ['active', 'planning'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getOrganizationsProperty()
    {
        return Organization::orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCountryFlag(string $code): string
    {
        // Convert country code to flag emoji
        $flag = '';
        foreach (str_split(strtoupper($code)) as $char) {
            $flag .= mb_chr(0x1F1E6 + ord($char) - ord('A'));
        }

        return $flag;
    }

    public function render()
    {
        return view('livewire.travel.trip-create', [
            'teamMembers' => $this->teamMembers,
            'projects' => $this->projects,
            'organizations' => $this->organizations,
            'typeOptions' => Trip::getTypeOptions(),
        ]);
    }
}
