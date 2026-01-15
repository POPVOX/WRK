<?php

namespace App\Livewire\Travel;

use App\Models\Organization;
use App\Models\Trip;
use App\Models\TripChecklist;
use App\Models\TripGuest;
use App\Models\TripSegment;
use App\Models\TripSponsorship;
use App\Models\User;
use App\Services\ItineraryParserService;
use App\Services\SponsorshipParserService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class TripDetail extends Component
{
    use WithFileUploads;

    public Trip $trip;

    #[Url]
    public string $activeTab = 'overview';

    // Edit mode
    public bool $editing = false;

    public string $editName = '';

    public string $editDescription = '';

    public string $editStatus = '';

    // Add segment modal
    public bool $showAddSegment = false;

    public ?int $segmentTravelerId = null;

    public string $segmentType = 'flight';

    public string $segmentCarrier = '';

    public string $segmentNumber = '';

    public string $segmentDepartureLocation = '';

    public string $segmentDepartureDatetime = '';

    public string $segmentArrivalLocation = '';

    public string $segmentArrivalDatetime = '';

    public string $segmentConfirmation = '';

    // Smart import modal
    public bool $showSmartImport = false;

    public ?int $smartImportTravelerId = null;

    public string $smartImportText = '';

    public $smartImportFile = null;

    public bool $smartImportParsing = false;

    public array $extractedSegments = [];

    public ?string $smartImportError = null;

    public ?string $smartImportNotes = null;

    // Add checklist item
    public string $newChecklistItem = '';

    public string $newChecklistCategory = 'other';

    // Add destination modal
    public bool $showAddDestination = false;

    public string $destCity = '';

    public string $destStateProvince = '';

    public string $destCountry = '';

    public string $destArrivalDate = '';

    public string $destDepartureDate = '';

    // Add guest modal
    public bool $showAddGuest = false;

    public string $guestName = '';

    public string $guestEmail = '';

    public string $guestPhone = '';

    public string $guestOrganization = '';

    public string $guestRole = 'guest';

    public string $guestNotes = '';

    public string $guestHomeAirport = '';

    // Sponsorship modal
    public bool $showAddSponsorship = false;

    public bool $showSponsorshipDetail = false;

    public ?int $selectedSponsorshipId = null;

    public ?int $sponsorshipOrgId = null;

    public string $sponsorshipType = 'partial_sponsorship';

    public string $sponsorshipDescription = '';

    public string $sponsorshipAgreementText = '';

    public $sponsorshipFile = null;

    public bool $parsingSponsorship = false;

    public ?string $sponsorshipParseError = null;

    // Add new organization inline (for sponsorship)
    public bool $showAddSponsorOrg = false;

    public string $newSponsorOrgName = '';

    public string $newSponsorOrgType = 'nonprofit';

    public string $newSponsorOrgWebsite = '';

    // Add Lodging modal
    public bool $showAddLodging = false;

    public string $lodgingMode = 'manual'; // 'manual', 'smart', 'url'

    // Manual form fields
    public string $lodgingPropertyName = '';

    public string $lodgingChain = '';

    public string $lodgingAddress = '';

    public string $lodgingCity = '';

    public string $lodgingCountry = '';

    public string $lodgingCheckInDate = '';

    public string $lodgingCheckInTime = '';

    public string $lodgingCheckOutDate = '';

    public string $lodgingCheckOutTime = '';

    public string $lodgingRoomType = '';

    public ?float $lodgingNightlyRate = null;

    public ?float $lodgingTotalCost = null;

    public string $lodgingCurrency = 'USD';

    public string $lodgingConfirmation = '';

    public string $lodgingNotes = '';

    // Traveler assignment
    public string $lodgingAssignTo = 'all'; // 'all' or 'specific'

    public array $lodgingSelectedTravelers = [];

    // Smart import / URL extraction
    public string $lodgingSmartText = '';

    public string $lodgingUrl = '';

    public ?array $extractedLodging = null;

    public bool $lodgingParsing = false;

    public ?string $lodgingParseError = null;

    public function mount(Trip $trip): void
    {
        $this->trip = $trip->load([
            'travelers.travelProfile',
            'guests',
            'destinations',
            'segments.traveler',
            'lodging.traveler',
            'groundTransport.traveler',
            'expenses',
            'sponsorships.organization',
            'events',
            'documents',
            'checklists',
            'projects',
            'partnerOrganization',
            'creator',
        ]);
    }

    public function getTitleProperty(): string
    {
        return $this->trip->name . ' - Travel';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // Edit trip
    public function startEditing(): void
    {
        $this->editName = $this->trip->name;
        $this->editDescription = $this->trip->description ?? '';
        $this->editStatus = $this->trip->status;
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->reset(['editName', 'editDescription', 'editStatus']);
    }

    public function saveTrip(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editDescription' => 'nullable|string',
            'editStatus' => 'required|in:planning,booked,in_progress,completed,cancelled',
        ]);

        $this->trip->update([
            'name' => $this->editName,
            'description' => $this->editDescription,
            'status' => $this->editStatus,
        ]);

        $this->editing = false;
        $this->dispatch('notify', type: 'success', message: 'Trip updated successfully!');
    }

    public function updateStatus(string $status): void
    {
        $this->trip->update(['status' => $status]);
        $this->dispatch('notify', type: 'success', message: 'Status updated!');
    }

    // Segments
    public function openAddSegment(?int $travelerId = null): void
    {
        $this->reset([
            'segmentTravelerId',
            'segmentType',
            'segmentCarrier',
            'segmentNumber',
            'segmentDepartureLocation',
            'segmentDepartureDatetime',
            'segmentArrivalLocation',
            'segmentArrivalDatetime',
            'segmentConfirmation',
        ]);
        $this->segmentType = 'flight';
        $this->segmentTravelerId = $travelerId;
        $this->showAddSegment = true;
    }

    public function closeAddSegment(): void
    {
        $this->showAddSegment = false;
    }

    public function saveSegment(): void
    {
        $this->validate([
            'segmentTravelerId' => 'required|exists:users,id',
            'segmentType' => 'required|in:flight,train,bus,rental_car,rideshare,ferry,other_transport',
            'segmentDepartureLocation' => 'required|string|max:100',
            'segmentDepartureDatetime' => 'required|date',
            'segmentArrivalLocation' => 'required|string|max:100',
            'segmentArrivalDatetime' => 'required|date|after:segmentDepartureDatetime',
        ]);

        $this->trip->segments()->create([
            'user_id' => $this->segmentTravelerId,
            'type' => $this->segmentType,
            'carrier' => $this->segmentCarrier ?: null,
            'segment_number' => $this->segmentNumber ?: null,
            'departure_location' => $this->segmentDepartureLocation,
            'departure_datetime' => $this->segmentDepartureDatetime,
            'arrival_location' => $this->segmentArrivalLocation,
            'arrival_datetime' => $this->segmentArrivalDatetime,
            'confirmation_number' => $this->segmentConfirmation ?: null,
        ]);

        $this->trip->load('segments.traveler');
        $this->showAddSegment = false;
        $this->dispatch('notify', type: 'success', message: 'Segment added!');
    }

    public function deleteSegment(int $segmentId): void
    {
        TripSegment::where('id', $segmentId)
            ->where('trip_id', $this->trip->id)
            ->delete();

        $this->trip->load('segments');
        $this->dispatch('notify', type: 'success', message: 'Segment removed.');
    }

    // Smart Import
    public function openSmartImport(?int $travelerId = null): void
    {
        $this->reset([
            'smartImportTravelerId',
            'smartImportText',
            'smartImportFile',
            'smartImportParsing',
            'extractedSegments',
            'smartImportError',
            'smartImportNotes',
        ]);
        $this->smartImportTravelerId = $travelerId;
        $this->showSmartImport = true;
    }

    public function closeSmartImport(): void
    {
        $this->showSmartImport = false;
        $this->reset([
            'smartImportText',
            'smartImportFile',
            'extractedSegments',
            'smartImportError',
            'smartImportNotes',
        ]);
    }

    public function parseItinerary(): void
    {
        $this->smartImportError = null;
        $this->smartImportNotes = null;
        $this->extractedSegments = [];

        // Validate we have a traveler selected
        if (!$this->smartImportTravelerId) {
            $this->smartImportError = 'Please select a traveler first.';

            return;
        }

        $parser = new ItineraryParserService();

        // If a file was uploaded, use that
        if ($this->smartImportFile) {
            $path = $this->smartImportFile->getRealPath();
            $mimeType = $this->smartImportFile->getMimeType();
            $result = $parser->parseFile($path, $mimeType);
        } elseif (!empty(trim($this->smartImportText))) {
            // Otherwise use the pasted text
            $result = $parser->parseText($this->smartImportText);
        } else {
            $this->smartImportError = 'Please paste itinerary text or upload a document.';

            return;
        }

        if (isset($result['error'])) {
            $this->smartImportError = $result['error'];

            return;
        }

        $this->extractedSegments = $result['segments'] ?? [];
        $this->smartImportNotes = $result['parsing_notes'] ?? null;

        if (empty($this->extractedSegments)) {
            $this->smartImportError = 'No travel segments could be extracted. Try providing more detailed itinerary information.';
        }
    }

    public function removeExtractedSegment(int $index): void
    {
        unset($this->extractedSegments[$index]);
        $this->extractedSegments = array_values($this->extractedSegments);
    }

    public function updateExtractedSegment(int $index, string $field, $value): void
    {
        if (isset($this->extractedSegments[$index])) {
            $this->extractedSegments[$index][$field] = $value;
        }
    }

    public function saveExtractedSegments(): void
    {
        if (!$this->smartImportTravelerId) {
            $this->smartImportError = 'Please select a traveler.';

            return;
        }

        if (empty($this->extractedSegments)) {
            $this->smartImportError = 'No segments to save.';

            return;
        }

        $savedCount = 0;
        foreach ($this->extractedSegments as $segment) {
            // Skip if missing required fields
            if (empty($segment['departure_location']) || empty($segment['arrival_location']) || empty($segment['departure_datetime'])) {
                continue;
            }

            $this->trip->segments()->create([
                'user_id' => $this->smartImportTravelerId,
                'type' => $segment['type'] ?? 'flight',
                'carrier' => $segment['carrier'] ?? null,
                'carrier_code' => $segment['carrier_code'] ?? null,
                'segment_number' => $segment['segment_number'] ?? null,
                'confirmation_number' => $segment['confirmation_number'] ?? null,
                'departure_location' => $segment['departure_location'],
                'departure_city' => $segment['departure_city'] ?? null,
                'departure_datetime' => $segment['departure_datetime'],
                'departure_terminal' => $segment['departure_terminal'] ?? null,
                'arrival_location' => $segment['arrival_location'],
                'arrival_city' => $segment['arrival_city'] ?? null,
                'arrival_datetime' => $segment['arrival_datetime'] ?? null,
                'arrival_terminal' => $segment['arrival_terminal'] ?? null,
                'seat_assignment' => $segment['seat_assignment'] ?? null,
                'cabin_class' => $segment['cabin_class'] ?? null,
                'cost' => $segment['cost'] ?? null,
                'currency' => $segment['currency'] ?? 'USD',
                'notes' => $segment['notes'] ?? null,
                'ai_extracted' => true,
                'ai_confidence' => $segment['confidence'] ?? null,
            ]);
            $savedCount++;
        }

        $this->trip->load('segments.traveler');
        $this->closeSmartImport();
        $this->dispatch('notify', type: 'success', message: "{$savedCount} segment(s) imported successfully!");
    }

    // Destinations
    public function openAddDestination(): void
    {
        $this->reset([
            'destCity',
            'destStateProvince',
            'destCountry',
            'destArrivalDate',
            'destDepartureDate',
        ]);
        // Default dates based on trip
        $this->destArrivalDate = $this->trip->start_date->format('Y-m-d');
        $this->destDepartureDate = $this->trip->end_date->format('Y-m-d');
        $this->showAddDestination = true;
    }

    public function closeAddDestination(): void
    {
        $this->showAddDestination = false;
    }

    public function saveDestination(): void
    {
        $this->validate([
            'destCity' => 'required|string|max:100',
            'destCountry' => 'required|string|size:2',
            'destArrivalDate' => 'required|date',
            'destDepartureDate' => 'required|date|after_or_equal:destArrivalDate',
        ]);

        // Get next order
        $nextOrder = ($this->trip->destinations->max('order') ?? 0) + 1;

        // Check for travel advisory
        $advisory = \App\Models\CountryTravelAdvisory::findByCountryCode($this->destCountry);

        $this->trip->destinations()->create([
            'order' => $nextOrder,
            'city' => $this->destCity,
            'state_province' => $this->destStateProvince ?: null,
            'country' => $this->destCountry,
            'arrival_date' => $this->destArrivalDate,
            'departure_date' => $this->destDepartureDate,
            'state_dept_level' => $advisory?->advisory_level,
            'is_prohibited_destination' => $advisory?->is_prohibited ?? false,
        ]);

        $this->trip->load('destinations');
        $this->showAddDestination = false;
        $this->dispatch('notify', type: 'success', message: 'Destination added!');
    }

    public function deleteDestination(int $destinationId): void
    {
        \App\Models\TripDestination::where('id', $destinationId)
            ->where('trip_id', $this->trip->id)
            ->delete();

        // Reorder remaining destinations
        $this->trip->destinations()->orderBy('order')->get()->each(function ($dest, $index) {
            $dest->update(['order' => $index + 1]);
        });

        $this->trip->load('destinations');
        $this->dispatch('notify', type: 'success', message: 'Destination removed.');
    }

    public function moveDestinationUp(int $destinationId): void
    {
        $destination = $this->trip->destinations->find($destinationId);
        if (!$destination || $destination->order <= 1) {
            return;
        }

        $previousDest = $this->trip->destinations->where('order', $destination->order - 1)->first();
        if ($previousDest) {
            $previousDest->update(['order' => $destination->order]);
            $destination->update(['order' => $destination->order - 1]);
        }

        $this->trip->load('destinations');
    }

    public function moveDestinationDown(int $destinationId): void
    {
        $destination = $this->trip->destinations->find($destinationId);
        $maxOrder = $this->trip->destinations->max('order');
        if (!$destination || $destination->order >= $maxOrder) {
            return;
        }

        $nextDest = $this->trip->destinations->where('order', $destination->order + 1)->first();
        if ($nextDest) {
            $nextDest->update(['order' => $destination->order]);
            $destination->update(['order' => $destination->order + 1]);
        }

        $this->trip->load('destinations');
    }

    // Guest management
    public function openAddGuest(): void
    {
        $this->showAddGuest = true;
    }

    public function saveGuest(): void
    {
        $this->validate([
            'guestName' => 'required|string|max:255',
            'guestEmail' => 'nullable|email|max:255',
            'guestPhone' => 'nullable|string|max:50',
            'guestOrganization' => 'nullable|string|max:255',
            'guestRole' => 'nullable|string|max:50',
            'guestNotes' => 'nullable|string',
            'guestHomeAirport' => 'nullable|string|max:5',
        ]);

        $this->trip->guests()->create([
            'name' => $this->guestName,
            'email' => $this->guestEmail ?: null,
            'phone' => $this->guestPhone ?: null,
            'organization' => $this->guestOrganization ?: null,
            'role' => $this->guestRole ?: null,
            'notes' => $this->guestNotes ?: null,
            'home_airport_code' => $this->guestHomeAirport ? strtoupper($this->guestHomeAirport) : null,
        ]);

        $this->showAddGuest = false;
        $this->reset(['guestName', 'guestEmail', 'guestPhone', 'guestOrganization', 'guestRole', 'guestNotes', 'guestHomeAirport']);
        $this->trip->load('guests');
        $this->dispatch('notify', type: 'success', message: 'Guest added to trip!');
    }

    public function deleteGuest(int $guestId): void
    {
        TripGuest::where('id', $guestId)
            ->where('trip_id', $this->trip->id)
            ->delete();

        $this->trip->load('guests');
        $this->dispatch('notify', type: 'success', message: 'Guest removed from trip.');
    }

    // Checklists
    public function addChecklistItem(): void
    {
        if (empty(trim($this->newChecklistItem))) {
            return;
        }

        $this->trip->checklists()->create([
            'item' => $this->newChecklistItem,
            'category' => $this->newChecklistCategory,
            'user_id' => null, // Applies to all
        ]);

        $this->newChecklistItem = '';
        $this->trip->load('checklists');
    }

    public function toggleChecklistItem(int $itemId): void
    {
        $item = TripChecklist::where('id', $itemId)
            ->where('trip_id', $this->trip->id)
            ->first();

        if ($item) {
            $item->update(['is_completed' => !$item->is_completed]);
            $this->trip->load('checklists');
        }
    }

    public function deleteChecklistItem(int $itemId): void
    {
        TripChecklist::where('id', $itemId)
            ->where('trip_id', $this->trip->id)
            ->delete();

        $this->trip->load('checklists');
    }

    // Compliance
    public function markStepCompleted(): void
    {
        $this->trip->update(['step_registration_completed' => true]);
        $this->dispatch('notify', type: 'success', message: 'STEP registration marked complete.');
    }

    public function markInsuranceConfirmed(): void
    {
        $this->trip->update(['travel_insurance_confirmed' => true]);
        $this->dispatch('notify', type: 'success', message: 'Travel insurance confirmed.');
    }

    // Stats
    public function getExpenseStatsProperty(): array
    {
        $expenses = $this->trip->expenses;

        $byCategory = $expenses->groupBy('category')->map(fn($items) => $items->sum('amount'));

        return [
            'total' => $expenses->sum('amount'),
            'by_category' => $byCategory,
            'pending_reimbursement' => $expenses->where('reimbursement_status', 'pending')->sum('amount'),
        ];
    }

    public function getSponsorshipStatsProperty(): array
    {
        $sponsorships = $this->trip->sponsorships;

        return [
            'total_expected' => $sponsorships->sum('amount'),
            'total_received' => $sponsorships->sum('amount_received'),
            'pending_invoices' => $sponsorships->whereIn('payment_status', ['pending', 'invoiced'])->count(),
        ];
    }

    // Sponsorship Management
    public function openAddSponsorship(): void
    {
        $this->showAddSponsorship = true;
        $this->reset(['sponsorshipOrgId', 'sponsorshipType', 'sponsorshipDescription', 'sponsorshipAgreementText', 'sponsorshipFile', 'sponsorshipParseError']);
    }

    public function closeAddSponsorship(): void
    {
        $this->showAddSponsorship = false;
    }

    // Add Organization inline (from sponsorship modal)
    public function openAddSponsorOrg(): void
    {
        $this->reset(['newSponsorOrgName', 'newSponsorOrgType', 'newSponsorOrgWebsite']);
        $this->showAddSponsorOrg = true;
    }

    public function closeAddSponsorOrg(): void
    {
        $this->showAddSponsorOrg = false;
    }

    public function saveNewSponsorOrg(): void
    {
        $this->validate([
            'newSponsorOrgName' => 'required|string|max:255',
            'newSponsorOrgType' => 'required|in:nonprofit,government,corporate,university,media,other',
            'newSponsorOrgWebsite' => 'nullable|url|max:255',
        ]);

        $org = Organization::create([
            'name' => $this->newSponsorOrgName,
            'type' => $this->newSponsorOrgType,
            'website' => $this->newSponsorOrgWebsite ?: null,
        ]);

        $this->sponsorshipOrgId = $org->id;
        $this->showAddSponsorOrg = false;
        $this->dispatch('notify', type: 'success', message: 'Organization created!');
    }

    // Lodging Management
    public function openAddLodging(): void
    {
        $this->resetLodgingForm();
        $this->showAddLodging = true;
    }

    public function closeAddLodging(): void
    {
        $this->showAddLodging = false;
        $this->resetLodgingForm();
    }

    public function setLodgingMode(string $mode): void
    {
        $this->lodgingMode = $mode;
        $this->extractedLodging = null;
        $this->lodgingParseError = null;
    }

    protected function resetLodgingForm(): void
    {
        $this->reset([
            'lodgingMode',
            'lodgingPropertyName',
            'lodgingChain',
            'lodgingAddress',
            'lodgingCity',
            'lodgingCountry',
            'lodgingCheckInDate',
            'lodgingCheckInTime',
            'lodgingCheckOutDate',
            'lodgingCheckOutTime',
            'lodgingRoomType',
            'lodgingNightlyRate',
            'lodgingTotalCost',
            'lodgingCurrency',
            'lodgingConfirmation',
            'lodgingNotes',
            'lodgingAssignTo',
            'lodgingSelectedTravelers',
            'lodgingSmartText',
            'lodgingUrl',
            'extractedLodging',
            'lodgingParsing',
            'lodgingParseError',
        ]);
        $this->lodgingMode = 'manual';
        $this->lodgingCurrency = 'USD';
        $this->lodgingAssignTo = 'all';

        // Default dates based on trip
        $this->lodgingCheckInDate = $this->trip->start_date->format('Y-m-d');
        $this->lodgingCheckOutDate = $this->trip->end_date->format('Y-m-d');
    }

    public function parseLodgingText(): void
    {
        $this->lodgingParseError = null;
        $this->extractedLodging = null;

        if (empty(trim($this->lodgingSmartText))) {
            $this->lodgingParseError = 'Please paste booking confirmation text.';
            return;
        }

        $this->lodgingParsing = true;

        try {
            $parser = new \App\Services\LodgingParserService();
            $result = $parser->parseText($this->lodgingSmartText);

            if (isset($result['error'])) {
                $this->lodgingParseError = $result['error'];
            } elseif (isset($result['lodging'])) {
                $this->extractedLodging = $result['lodging'];
                $this->applyExtractedLodging($result['lodging']);
            } else {
                $this->lodgingParseError = 'Could not extract lodging information.';
            }
        } catch (\Exception $e) {
            $this->lodgingParseError = 'Error parsing text: ' . $e->getMessage();
        }

        $this->lodgingParsing = false;
    }

    public function parseLodgingUrl(): void
    {
        $this->lodgingParseError = null;
        $this->extractedLodging = null;

        if (empty(trim($this->lodgingUrl))) {
            $this->lodgingParseError = 'Please enter a URL.';
            return;
        }

        $this->lodgingParsing = true;

        try {
            $parser = new \App\Services\LodgingParserService();
            $result = $parser->parseUrl($this->lodgingUrl);

            if (isset($result['error'])) {
                $this->lodgingParseError = $result['error'];
            } elseif (isset($result['lodging'])) {
                $this->extractedLodging = $result['lodging'];
                $this->applyExtractedLodging($result['lodging']);
            } else {
                $this->lodgingParseError = 'Could not extract lodging information from URL.';
            }
        } catch (\Exception $e) {
            $this->lodgingParseError = 'Error fetching URL: ' . $e->getMessage();
        }

        $this->lodgingParsing = false;
    }

    protected function applyExtractedLodging(array $data): void
    {
        $this->lodgingPropertyName = $data['property_name'] ?? '';
        $this->lodgingChain = $data['chain'] ?? '';
        $this->lodgingAddress = $data['address'] ?? '';
        $this->lodgingCity = $data['city'] ?? '';
        $this->lodgingCountry = $data['country'] ?? '';
        $this->lodgingCheckInDate = $data['check_in_date'] ?? '';
        $this->lodgingCheckInTime = $data['check_in_time'] ?? '';
        $this->lodgingCheckOutDate = $data['check_out_date'] ?? '';
        $this->lodgingCheckOutTime = $data['check_out_time'] ?? '';
        $this->lodgingRoomType = $data['room_type'] ?? '';
        $this->lodgingNightlyRate = $data['nightly_rate'] ?? null;
        $this->lodgingTotalCost = $data['total_cost'] ?? null;
        $this->lodgingCurrency = $data['currency'] ?? 'USD';
        $this->lodgingConfirmation = $data['confirmation_number'] ?? '';
        $this->lodgingNotes = $data['notes'] ?? '';
    }

    public function saveLodging(): void
    {
        $this->validate([
            'lodgingPropertyName' => 'required|string|max:255',
            'lodgingCity' => 'required|string|max:100',
            'lodgingCountry' => 'required|string|size:2',
            'lodgingCheckInDate' => 'required|date',
            'lodgingCheckOutDate' => 'required|date|after:lodgingCheckInDate',
        ]);

        // Determine which travelers to assign
        $travelerIds = [];
        if ($this->lodgingAssignTo === 'all') {
            $travelerIds = $this->trip->travelers->pluck('id')->toArray();
        } else {
            $travelerIds = $this->lodgingSelectedTravelers;
        }

        // If no travelers selected, create one record with null user_id
        if (empty($travelerIds)) {
            $travelerIds = [null];
        }

        $savedCount = 0;
        foreach ($travelerIds as $userId) {
            $this->trip->lodging()->create([
                'user_id' => $userId,
                'property_name' => $this->lodgingPropertyName,
                'chain' => $this->lodgingChain ?: null,
                'address' => $this->lodgingAddress ?: null,
                'city' => $this->lodgingCity,
                'country' => strtoupper($this->lodgingCountry),
                'confirmation_number' => $this->lodgingConfirmation ?: null,
                'check_in_date' => $this->lodgingCheckInDate,
                'check_in_time' => $this->lodgingCheckInTime ?: null,
                'check_out_date' => $this->lodgingCheckOutDate,
                'check_out_time' => $this->lodgingCheckOutTime ?: null,
                'room_type' => $this->lodgingRoomType ?: null,
                'nightly_rate' => $this->lodgingNightlyRate,
                'total_cost' => $this->lodgingTotalCost,
                'currency' => $this->lodgingCurrency ?: 'USD',
                'notes' => $this->lodgingNotes ?: null,
                'ai_extracted' => $this->extractedLodging !== null,
            ]);
            $savedCount++;
        }

        $this->trip->load('lodging.traveler');
        $this->closeAddLodging();

        $message = $savedCount > 1
            ? "Lodging added for {$savedCount} travelers!"
            : 'Lodging added!';
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function deleteLodging(int $lodgingId): void
    {
        \App\Models\TripLodging::where('id', $lodgingId)
            ->where('trip_id', $this->trip->id)
            ->delete();

        $this->trip->load('lodging.traveler');
        $this->dispatch('notify', type: 'success', message: 'Lodging removed.');
    }


    public function parseAndCreateSponsorship(): void
    {
        $this->validate([
            'sponsorshipOrgId' => 'required|exists:organizations,id',
            'sponsorshipType' => 'required|string',
        ]);

        $text = $this->sponsorshipAgreementText;

        // If file uploaded, extract text from it
        if ($this->sponsorshipFile) {
            $filePath = $this->sponsorshipFile->store('sponsorship-docs', 'local');
            $fullPath = storage_path('app/' . $filePath);

            if (str_ends_with(strtolower($this->sponsorshipFile->getClientOriginalName()), '.pdf')) {
                $parser = new SponsorshipParserService();
                $extractedText = $parser->extractTextFromPdf($fullPath);
                if ($extractedText) {
                    $text = $extractedText;
                }
            } else {
                // For text files, read directly
                $text = file_get_contents($fullPath);
            }
        }

        // Create the sponsorship record first
        $sponsorship = $this->trip->sponsorships()->create([
            'organization_id' => $this->sponsorshipOrgId,
            'type' => $this->sponsorshipType,
            'description' => $this->sponsorshipDescription,
            'agreement_text' => $text,
            'agreement_file_path' => isset($filePath) ? $filePath : null,
            'agreement_file_name' => $this->sponsorshipFile?->getClientOriginalName(),
            'payment_status' => 'pending',
            'currency' => 'USD',
        ]);

        // If we have agreement text, parse it with AI
        if (!empty($text)) {
            $this->parsingSponsorship = true;

            try {
                $parser = new SponsorshipParserService();
                $result = $parser->parseAgreement($text, $sponsorship);

                if ($result['success']) {
                    $parser->applyToSponsorship($sponsorship, $result);
                    $this->dispatch('notify', type: 'success', message: 'Sponsorship created and terms extracted!');
                } else {
                    $this->dispatch('notify', type: 'warning', message: 'Sponsorship created but AI extraction failed. You can add details manually.');
                }
            } catch (\Exception $e) {
                $this->dispatch('notify', type: 'warning', message: 'Sponsorship created but parsing failed: ' . $e->getMessage());
            }

            $this->parsingSponsorship = false;
        } else {
            $this->dispatch('notify', type: 'success', message: 'Sponsorship added!');
        }

        $this->trip->load('sponsorships.organization');
        $this->showAddSponsorship = false;
        $this->reset(['sponsorshipOrgId', 'sponsorshipType', 'sponsorshipDescription', 'sponsorshipAgreementText', 'sponsorshipFile']);
    }

    public function viewSponsorshipDetail(int $sponsorshipId): void
    {
        $this->selectedSponsorshipId = $sponsorshipId;
        $this->showSponsorshipDetail = true;
    }

    public function closeSponsorshipDetail(): void
    {
        $this->showSponsorshipDetail = false;
        $this->selectedSponsorshipId = null;
    }

    public function toggleDeliverable(int $sponsorshipId, int $index): void
    {
        $sponsorship = TripSponsorship::find($sponsorshipId);
        if (!$sponsorship || $sponsorship->trip_id !== $this->trip->id) {
            return;
        }

        $deliverables = $sponsorship->deliverables ?? [];
        if (isset($deliverables[$index])) {
            if ($deliverables[$index]['is_completed']) {
                $sponsorship->markDeliverableIncomplete($index);
            } else {
                $sponsorship->markDeliverableComplete($index);
            }
        }

        $this->trip->load('sponsorships.organization');
    }

    public function reparseSponsorship(int $sponsorshipId): void
    {
        $sponsorship = TripSponsorship::find($sponsorshipId);
        if (!$sponsorship || $sponsorship->trip_id !== $this->trip->id) {
            return;
        }

        if (empty($sponsorship->agreement_text)) {
            $this->dispatch('notify', type: 'error', message: 'No agreement text to parse');
            return;
        }

        try {
            $parser = new SponsorshipParserService();
            $result = $parser->parseAgreement($sponsorship->agreement_text, $sponsorship);

            if ($result['success']) {
                $parser->applyToSponsorship($sponsorship, $result);
                $this->dispatch('notify', type: 'success', message: 'Terms re-extracted successfully!');
            } else {
                $this->dispatch('notify', type: 'error', message: 'Extraction failed: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Parsing error: ' . $e->getMessage());
        }

        $this->trip->load('sponsorships.organization');
    }

    public function deleteSponsorship(int $sponsorshipId): void
    {
        TripSponsorship::where('id', $sponsorshipId)
            ->where('trip_id', $this->trip->id)
            ->delete();

        $this->trip->load('sponsorships.organization');
        $this->dispatch('notify', type: 'success', message: 'Sponsorship removed.');
    }

    public function getOrganizationsProperty()
    {
        return Organization::orderBy('name')->get();
    }

    public function getTimelineProperty(): array
    {
        $events = collect();

        // Add segments
        foreach ($this->trip->segments as $segment) {
            $events->push([
                'datetime' => $segment->departure_datetime,
                'type' => 'segment_departure',
                'icon' => $segment->type_icon,
                'title' => $segment->route,
                'subtitle' => $segment->carrier ? "{$segment->carrier} {$segment->segment_number}" : null,
                'model' => $segment,
            ]);
        }

        // Add lodging check-ins
        foreach ($this->trip->lodging as $lodging) {
            $events->push([
                'datetime' => $lodging->check_in_date->setTimeFromTimeString($lodging->check_in_time ?? '15:00:00'),
                'type' => 'checkin',
                'icon' => '🏨',
                'title' => "Check In: {$lodging->property_name}",
                'subtitle' => $lodging->city,
                'model' => $lodging,
            ]);
            $events->push([
                'datetime' => $lodging->check_out_date->setTimeFromTimeString($lodging->check_out_time ?? '11:00:00'),
                'type' => 'checkout',
                'icon' => '🏨',
                'title' => "Check Out: {$lodging->property_name}",
                'subtitle' => null,
                'model' => $lodging,
            ]);
        }

        // Add events
        foreach ($this->trip->events as $event) {
            $events->push([
                'datetime' => $event->start_datetime,
                'type' => 'event',
                'icon' => $event->type_icon,
                'title' => $event->title,
                'subtitle' => $event->location,
                'model' => $event,
            ]);
        }

        return $events->sortBy('datetime')->groupBy(fn($e) => $e['datetime']->format('Y-m-d'))->toArray();
    }

    public function canEdit(): bool
    {
        $user = Auth::user();

        return $user->isAdmin()
            || $user->isManagement()
            || $this->trip->created_by === $user->id
            || $this->trip->isUserLead($user);
    }

    public function getCountriesProperty(): array
    {
        return \App\Support\Countries::all();
    }

    public function render()
    {
        return view('livewire.travel.trip-detail', [
            'expenseStats' => $this->expenseStats,
            'sponsorshipStats' => $this->sponsorshipStats,
            'timeline' => $this->timeline,
            'canEdit' => $this->canEdit(),
            'countries' => $this->countries,
            'organizations' => $this->organizations,
        ])->title($this->title);
    }
}
