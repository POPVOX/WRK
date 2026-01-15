<?php

namespace App\Livewire\Profile;

use App\Models\TravelProfile;
use App\Support\Countries;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TravelProfileEditor extends Component
{
    // Personal Info
    public ?string $birthday = null;
    public ?string $homeAirportCode = null;
    public ?string $homeAirportName = null;
    public ?string $passportNumber = null;
    public ?string $passportCountry = null;
    public ?string $passportExpiration = null;

    // Travel Programs
    public ?string $tsaPrecheckNumber = null;
    public ?string $globalEntryNumber = null;

    // Frequent Flyer Programs (as simple key-value pairs)
    public array $frequentFlyerPrograms = [];
    public string $newFfAirline = '';
    public string $newFfNumber = '';

    // Hotel Programs
    public array $hotelPrograms = [];
    public string $newHotelChain = '';
    public string $newHotelNumber = '';

    // Rental Car Programs
    public array $rentalCarPrograms = [];
    public string $newRentalCompany = '';
    public string $newRentalNumber = '';

    // Preferences
    public string $seatPreference = 'no_preference';
    public ?string $dietaryRestrictions = null;
    public ?string $travelNotes = null;

    // Emergency Contact
    public ?string $emergencyContactName = null;
    public ?string $emergencyContactRelationship = null;
    public ?string $emergencyContactPhone = null;
    public ?string $emergencyContactEmail = null;

    public bool $showSuccess = false;

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->travelProfile;

        if ($profile) {
            $this->birthday = $profile->birthday?->format('Y-m-d');
            $this->homeAirportCode = $profile->home_airport_code;
            $this->homeAirportName = $profile->home_airport_name;
            $this->passportNumber = $profile->passport_number;
            $this->passportCountry = $profile->passport_country;
            $this->passportExpiration = $profile->passport_expiration?->format('Y-m-d');
            $this->tsaPrecheckNumber = $profile->tsa_precheck_number;
            $this->globalEntryNumber = $profile->global_entry_number;
            $this->frequentFlyerPrograms = $profile->frequent_flyer_programs ?: [];
            $this->hotelPrograms = $profile->hotel_programs ?: [];
            $this->rentalCarPrograms = $profile->rental_car_programs ?: [];
            $this->seatPreference = $profile->seat_preference ?? 'no_preference';
            $this->dietaryRestrictions = $profile->dietary_restrictions;
            $this->travelNotes = $profile->travel_notes;
            $this->emergencyContactName = $profile->emergency_contact_name;
            $this->emergencyContactRelationship = $profile->emergency_contact_relationship;
            $this->emergencyContactPhone = $profile->emergency_contact_phone;
            $this->emergencyContactEmail = $profile->emergency_contact_email;
        }
    }

    public function addFrequentFlyer(): void
    {
        if ($this->newFfAirline && $this->newFfNumber) {
            $this->frequentFlyerPrograms[] = [
                'airline' => $this->newFfAirline,
                'number' => $this->newFfNumber,
            ];
            $this->newFfAirline = '';
            $this->newFfNumber = '';
        }
    }

    public function removeFrequentFlyer(int $index): void
    {
        unset($this->frequentFlyerPrograms[$index]);
        $this->frequentFlyerPrograms = array_values($this->frequentFlyerPrograms);
    }

    public function addHotelProgram(): void
    {
        if ($this->newHotelChain && $this->newHotelNumber) {
            $this->hotelPrograms[] = [
                'chain' => $this->newHotelChain,
                'number' => $this->newHotelNumber,
            ];
            $this->newHotelChain = '';
            $this->newHotelNumber = '';
        }
    }

    public function removeHotelProgram(int $index): void
    {
        unset($this->hotelPrograms[$index]);
        $this->hotelPrograms = array_values($this->hotelPrograms);
    }

    public function addRentalCarProgram(): void
    {
        if ($this->newRentalCompany && $this->newRentalNumber) {
            $this->rentalCarPrograms[] = [
                'company' => $this->newRentalCompany,
                'number' => $this->newRentalNumber,
            ];
            $this->newRentalCompany = '';
            $this->newRentalNumber = '';
        }
    }

    public function removeRentalCarProgram(int $index): void
    {
        unset($this->rentalCarPrograms[$index]);
        $this->rentalCarPrograms = array_values($this->rentalCarPrograms);
    }

    public function save(): void
    {
        $this->validate([
            'birthday' => 'nullable|date',
            'homeAirportCode' => 'nullable|string|max:5',
            'homeAirportName' => 'nullable|string|max:255',
            'passportNumber' => 'nullable|string|max:50',
            'passportCountry' => 'nullable|string|size:2',
            'passportExpiration' => 'nullable|date',
            'tsaPrecheckNumber' => 'nullable|string|max:50',
            'globalEntryNumber' => 'nullable|string|max:50',
            'seatPreference' => 'required|in:window,aisle,middle,no_preference',
            'dietaryRestrictions' => 'nullable|string|max:500',
            'travelNotes' => 'nullable|string|max:1000',
            'emergencyContactName' => 'nullable|string|max:255',
            'emergencyContactRelationship' => 'nullable|string|max:100',
            'emergencyContactPhone' => 'nullable|string|max:50',
            'emergencyContactEmail' => 'nullable|email|max:255',
        ]);

        $user = Auth::user();
        $profile = $user->travelProfile ?? new TravelProfile(['user_id' => $user->id]);

        $profile->birthday = $this->birthday ?: null;
        $profile->home_airport_code = $this->homeAirportCode ? strtoupper($this->homeAirportCode) : null;
        $profile->home_airport_name = $this->homeAirportName ?: null;
        $profile->passport_number = $this->passportNumber ?: null;
        $profile->passport_country = $this->passportCountry ?: null;
        $profile->passport_expiration = $this->passportExpiration ?: null;
        $profile->tsa_precheck_number = $this->tsaPrecheckNumber ?: null;
        $profile->global_entry_number = $this->globalEntryNumber ?: null;
        $profile->frequent_flyer_programs = $this->frequentFlyerPrograms ?: null;
        $profile->hotel_programs = $this->hotelPrograms ?: null;
        $profile->rental_car_programs = $this->rentalCarPrograms ?: null;
        $profile->seat_preference = $this->seatPreference;
        $profile->dietary_restrictions = $this->dietaryRestrictions ?: null;
        $profile->travel_notes = $this->travelNotes ?: null;
        $profile->emergency_contact_name = $this->emergencyContactName ?: null;
        $profile->emergency_contact_relationship = $this->emergencyContactRelationship ?: null;
        $profile->emergency_contact_phone = $this->emergencyContactPhone ?: null;
        $profile->emergency_contact_email = $this->emergencyContactEmail ?: null;

        $profile->save();

        $this->showSuccess = true;
        $this->dispatch('notify', type: 'success', message: 'Travel profile saved!');
    }

    public function getCountriesProperty(): array
    {
        return Countries::all();
    }

    public function render()
    {
        return view('livewire.profile.travel-profile-editor', [
            'seatOptions' => TravelProfile::getSeatPreferenceOptions(),
        ])->title('My Travel Profile');
    }
}
