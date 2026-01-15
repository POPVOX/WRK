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

    public string $destinationCity = '';

    public string $destinationCountry = '';

    public ?int $projectId = null;

    public ?int $partnerOrganizationId = null;

    public string $partnerProgramName = '';

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

        // Add current user as default traveler and lead
        $this->selectedTravelers = [Auth::id()];
        $this->leadTravelerId = Auth::id();

        // Load countries
        $this->countries = $this->getCountryList();
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

    public function updatedDestinationCountry(): void
    {
        $this->checkTravelAdvisory();
    }

    protected function checkTravelAdvisory(): void
    {
        if (empty($this->destinationCountry)) {
            $this->travelAdvisory = null;

            return;
        }

        $advisory = CountryTravelAdvisory::findByCountryCode($this->destinationCountry);

        if ($advisory) {
            $this->travelAdvisory = [
                'level' => $advisory->advisory_level,
                'title' => $advisory->advisory_title,
                'summary' => $advisory->advisory_summary,
                'is_prohibited' => $advisory->is_prohibited,
                'url' => $advisory->state_dept_url,
            ];

            $this->stepRegistrationRequired = $advisory->requiresStepRegistration();
            $this->travelInsuranceRequired = $advisory->requiresTravelInsurance();
            $this->approvalRequired = $advisory->requiresApproval();

            $this->riskLevel = match ($advisory->advisory_level) {
                '1' => 'standard',
                '2' => 'moderate',
                '3' => 'high',
                '4' => 'prohibited',
                default => 'standard',
            };
        } else {
            $this->travelAdvisory = null;
            $this->stepRegistrationRequired = false;
            $this->travelInsuranceRequired = false;
            $this->approvalRequired = false;
            $this->riskLevel = 'standard';
        }
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

        // Check advisory when moving to step 3
        if ($this->step === 3) {
            $this->checkTravelAdvisory();
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
                'destinationCity' => 'required|string|max:100',
                'destinationCountry' => 'required|string|size:2',
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

        $trip = Trip::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'status' => 'planning',
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'primary_destination_city' => $this->destinationCity,
            'primary_destination_country' => $this->destinationCountry,
            'project_id' => $this->projectId ?: null,
            'partner_organization_id' => $this->partnerOrganizationId ?: null,
            'partner_program_name' => $this->partnerProgramName ?: null,
            'created_by' => Auth::id(),
            'risk_level' => $this->riskLevel,
            'step_registration_required' => $this->stepRegistrationRequired,
            'travel_insurance_required' => $this->travelInsuranceRequired,
            'approval_required' => $this->approvalRequired,
        ]);

        // Attach travelers
        foreach ($this->selectedTravelers as $userId) {
            $trip->travelers()->attach($userId, [
                'role' => $userId === $this->leadTravelerId ? 'lead' : 'participant',
            ]);
        }

        // Create primary destination
        $trip->destinations()->create([
            'order' => 1,
            'city' => $this->destinationCity,
            'country' => $this->destinationCountry,
            'arrival_date' => $this->startDate,
            'departure_date' => $this->endDate,
            'state_dept_level' => $this->travelAdvisory['level'] ?? null,
            'is_prohibited_destination' => $this->travelAdvisory['is_prohibited'] ?? false,
        ]);

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
