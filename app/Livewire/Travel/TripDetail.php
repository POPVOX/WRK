<?php

namespace App\Livewire\Travel;

use App\Models\Organization;
use App\Models\Trip;
use App\Models\TripAgentAction;
use App\Models\TripAgentConversation;
use App\Models\TripChecklist;
use App\Models\TripGuest;
use App\Models\TripSegment;
use App\Models\TripSponsorship;
use App\Models\User;
use App\Services\ItineraryParserService;
use App\Services\SponsorshipParserService;
use App\Services\TripAgentService;
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

    // Agent conversation
    public ?int $agentConversationId = null;

    public string $agentMessage = '';

    public bool $agentBusy = false;

    // Edit mode
    public bool $editing = false;

    public string $editName = '';

    public string $editDescription = '';

    public string $editStatus = '';

    // Add segment modal
    public bool $showAddSegment = false;

    public ?int $segmentTravelerId = null;

    public ?int $segmentGuestId = null;

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

    public ?int $smartImportGuestId = null;

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

    public ?int $editingLodgingId = null;

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

    // Add Event modal
    public bool $showAddEvent = false;

    public ?int $editingEventId = null;

    public string $eventMode = 'manual'; // 'manual', 'smart', 'url', 'project'

    // Event form fields
    public string $eventTitle = '';

    public string $eventType = 'other';

    public string $eventStartDate = '';

    public string $eventStartTime = '';

    public string $eventEndDate = '';

    public string $eventEndTime = '';

    public string $eventLocation = '';

    public string $eventAddress = '';

    public string $eventDescription = '';

    public string $eventNotes = '';

    // Link to project event
    public ?int $eventProjectEventId = null;

    // Smart import / URL extraction
    public string $eventSmartText = '';

    public string $eventUrl = '';

    public ?array $extractedEvent = null;

    public bool $eventParsing = false;

    public ?string $eventParseError = null;

    // Add Expense modal
    public bool $showAddExpense = false;

    public ?int $editingExpenseId = null;

    public string $expenseMode = 'manual'; // 'manual', 'smart'

    // Expense form fields
    public string $expenseCategory = 'other';

    public string $expenseDescription = '';

    public string $expenseDate = '';

    public ?float $expenseAmount = null;

    public string $expenseCurrency = 'USD';

    public string $expenseVendor = '';

    public string $expenseReceiptNumber = '';

    public string $expenseNotes = '';

    public string $expenseReimbursementStatus = 'pending';

    // Receipt upload
    public $expenseReceiptFile = null;

    // Smart import
    public string $expenseSmartText = '';

    public ?array $extractedExpense = null;

    public bool $expenseParsing = false;

    public ?string $expenseParseError = null;

    public function mount(Trip $trip): void
    {
        $this->trip = $trip;
        $this->loadTripRelations();

        $this->agentConversationId = TripAgentConversation::query()
            ->where('trip_id', $this->trip->id)
            ->value('id');
    }

    protected function loadTripRelations(): void
    {
        $this->trip->load([
            'travelers.travelProfile',
            'guests.segments',
            'destinations',
            'segments.traveler',
            'segments.guest',
            'lodging.traveler',
            'groundTransport.traveler',
            'expenses.user',
            'sponsorships.organization',
            'events.projectEvent',
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
            'segmentGuestId',
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
        $this->segmentGuestId = null;
        $this->showAddSegment = true;
    }

    public function openAddSegmentForGuest(int $guestId): void
    {
        $this->reset([
            'segmentTravelerId',
            'segmentGuestId',
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
        $this->segmentTravelerId = null;
        $this->segmentGuestId = $guestId;
        $this->showAddSegment = true;
    }

    public function closeAddSegment(): void
    {
        $this->showAddSegment = false;
    }

    public function saveSegment(): void
    {
        // Either traveler or guest must be set
        if (!$this->segmentTravelerId && !$this->segmentGuestId) {
            $this->dispatch('notify', type: 'error', message: 'Please select a traveler.');
            return;
        }

        $rules = [
            'segmentType' => 'required|in:flight,train,bus,rental_car,rideshare,ferry,other_transport',
            'segmentDepartureLocation' => 'required|string|max:100',
            'segmentDepartureDatetime' => 'required|date',
            'segmentArrivalLocation' => 'required|string|max:100',
            'segmentArrivalDatetime' => 'required|date|after:segmentDepartureDatetime',
        ];

        if ($this->segmentTravelerId) {
            $rules['segmentTravelerId'] = 'required|exists:users,id';
        }
        if ($this->segmentGuestId) {
            $rules['segmentGuestId'] = 'required|exists:trip_guests,id';
        }

        $this->validate($rules);

        $this->trip->segments()->create([
            'user_id' => $this->segmentTravelerId,
            'trip_guest_id' => $this->segmentGuestId,
            'type' => $this->segmentType,
            'carrier' => $this->segmentCarrier ?: null,
            'segment_number' => $this->segmentNumber ?: null,
            'departure_location' => $this->segmentDepartureLocation,
            'departure_datetime' => $this->segmentDepartureDatetime,
            'arrival_location' => $this->segmentArrivalLocation,
            'arrival_datetime' => $this->segmentArrivalDatetime,
            'confirmation_number' => $this->segmentConfirmation ?: null,
        ]);

        $this->trip->load('segments.traveler', 'segments.guest');
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
            'smartImportGuestId',
            'smartImportText',
            'smartImportFile',
            'smartImportParsing',
            'extractedSegments',
            'smartImportError',
            'smartImportNotes',
        ]);
        $this->smartImportTravelerId = $travelerId;
        $this->smartImportGuestId = null;
        $this->showSmartImport = true;
    }

    public function openSmartImportForGuest(int $guestId): void
    {
        $this->reset([
            'smartImportTravelerId',
            'smartImportGuestId',
            'smartImportText',
            'smartImportFile',
            'smartImportParsing',
            'extractedSegments',
            'smartImportError',
            'smartImportNotes',
        ]);
        $this->smartImportTravelerId = null;
        $this->smartImportGuestId = $guestId;
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
        if (!$this->smartImportTravelerId && !$this->smartImportGuestId) {
            $this->smartImportError = 'Please select a traveler or guest.';

            return;
        }

        if (empty($this->extractedSegments)) {
            $this->smartImportError = 'No segments to save.';

            return;
        }

        try {
            $savedCount = 0;
            $skippedCount = 0;

            foreach ($this->extractedSegments as $segment) {
                // Skip if missing required fields
                if (empty($segment['departure_location']) || empty($segment['arrival_location']) || empty($segment['departure_datetime'])) {
                    $skippedCount++;
                    continue;
                }

                $this->trip->segments()->create([
                    'user_id' => $this->smartImportTravelerId,
                    'trip_guest_id' => $this->smartImportGuestId,
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

            if ($savedCount === 0) {
                $this->smartImportError = 'No segments could be saved. All segments were missing required fields (departure location, arrival location, or departure date/time).';
                return;
            }

            $this->trip->load('segments.traveler', 'segments.guest');
            $this->closeSmartImport();

            $message = "{$savedCount} segment(s) imported successfully!";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} skipped due to missing data)";
            }
            $this->dispatch('notify', type: 'success', message: $message);
        } catch (\Exception $e) {
            \Log::error('Smart import save error: ' . $e->getMessage(), ['exception' => $e]);
            $this->smartImportError = 'Error saving segments: ' . $e->getMessage();
        }
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
            'editingLodgingId',
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

    public function editLodging(int $lodgingId): void
    {
        $lodging = \App\Models\TripLodging::where('id', $lodgingId)
            ->where('trip_id', $this->trip->id)
            ->first();

        if (!$lodging) {
            return;
        }

        $this->editingLodgingId = $lodgingId;
        $this->lodgingMode = 'manual';
        $this->lodgingPropertyName = $lodging->property_name ?? '';
        $this->lodgingChain = $lodging->chain ?? '';
        $this->lodgingAddress = $lodging->address ?? '';
        $this->lodgingCity = $lodging->city ?? '';
        $this->lodgingCountry = $lodging->country ?? '';
        $this->lodgingCheckInDate = $lodging->check_in_date?->format('Y-m-d') ?? '';
        $this->lodgingCheckInTime = $lodging->check_in_time ?? '';
        $this->lodgingCheckOutDate = $lodging->check_out_date?->format('Y-m-d') ?? '';
        $this->lodgingCheckOutTime = $lodging->check_out_time ?? '';
        $this->lodgingRoomType = $lodging->room_type ?? '';
        $this->lodgingNightlyRate = $lodging->nightly_rate;
        $this->lodgingTotalCost = $lodging->total_cost;
        $this->lodgingCurrency = $lodging->currency ?? 'USD';
        $this->lodgingConfirmation = $lodging->confirmation_number ?? '';
        $this->lodgingNotes = $lodging->notes ?? '';

        // Set traveler assignment
        if ($lodging->user_id) {
            $this->lodgingAssignTo = 'specific';
            $this->lodgingSelectedTravelers = [$lodging->user_id];
        } else {
            $this->lodgingAssignTo = 'all';
            $this->lodgingSelectedTravelers = [];
        }

        $this->showAddLodging = true;
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

        $data = [
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
        ];

        // Editing existing lodging
        if ($this->editingLodgingId) {
            $lodging = \App\Models\TripLodging::where('id', $this->editingLodgingId)
                ->where('trip_id', $this->trip->id)
                ->first();

            if ($lodging) {
                // Update the user_id based on traveler selection
                if ($this->lodgingAssignTo === 'specific' && !empty($this->lodgingSelectedTravelers)) {
                    $data['user_id'] = $this->lodgingSelectedTravelers[0];
                } else {
                    $data['user_id'] = null;
                }

                $lodging->update($data);
            }

            $this->trip->load('lodging.traveler');
            $this->closeAddLodging();
            $this->dispatch('notify', type: 'success', message: 'Lodging updated!');
            return;
        }

        // Creating new lodging
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
            $this->trip->lodging()->create(array_merge($data, [
                'user_id' => $userId,
                'ai_extracted' => $this->extractedLodging !== null,
            ]));
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

    // Event Management
    public function openAddEvent(): void
    {
        $this->resetEventForm();
        $this->showAddEvent = true;
    }

    public function closeAddEvent(): void
    {
        $this->showAddEvent = false;
        $this->resetEventForm();
    }

    public function setEventMode(string $mode): void
    {
        $this->eventMode = $mode;
        $this->extractedEvent = null;
        $this->eventParseError = null;
    }

    protected function resetEventForm(): void
    {
        $this->reset([
            'editingEventId',
            'eventMode',
            'eventTitle',
            'eventType',
            'eventStartDate',
            'eventStartTime',
            'eventEndDate',
            'eventEndTime',
            'eventLocation',
            'eventAddress',
            'eventDescription',
            'eventNotes',
            'eventProjectEventId',
            'eventSmartText',
            'eventUrl',
            'extractedEvent',
            'eventParsing',
            'eventParseError',
        ]);
        $this->eventMode = 'manual';
        $this->eventType = 'other';

        // Default to trip dates
        $this->eventStartDate = $this->trip->start_date->format('Y-m-d');
        $this->eventEndDate = $this->trip->start_date->format('Y-m-d');
    }

    public function parseEventText(): void
    {
        $this->eventParseError = null;
        $this->extractedEvent = null;

        if (empty(trim($this->eventSmartText))) {
            $this->eventParseError = 'Please paste event details.';
            return;
        }

        $this->eventParsing = true;

        try {
            $parser = new \App\Services\EventParserService();
            $result = $parser->parseText($this->eventSmartText);

            if (isset($result['error'])) {
                $this->eventParseError = $result['error'];
            } elseif (isset($result['event'])) {
                $this->extractedEvent = $result['event'];
                $this->applyExtractedEvent($result['event']);
            } else {
                $this->eventParseError = 'Could not extract event information.';
            }
        } catch (\Exception $e) {
            $this->eventParseError = 'Error parsing text: ' . $e->getMessage();
        }

        $this->eventParsing = false;
    }

    public function parseEventUrl(): void
    {
        $this->eventParseError = null;
        $this->extractedEvent = null;

        if (empty(trim($this->eventUrl))) {
            $this->eventParseError = 'Please enter a URL.';
            return;
        }

        $this->eventParsing = true;

        try {
            $parser = new \App\Services\EventParserService();
            $result = $parser->parseUrl($this->eventUrl);

            if (isset($result['error'])) {
                $this->eventParseError = $result['error'];
            } elseif (isset($result['event'])) {
                $this->extractedEvent = $result['event'];
                $this->applyExtractedEvent($result['event']);
            } else {
                $this->eventParseError = 'Could not extract event information from URL.';
            }
        } catch (\Exception $e) {
            $this->eventParseError = 'Error fetching URL: ' . $e->getMessage();
        }

        $this->eventParsing = false;
    }

    public function linkProjectEvent(): void
    {
        if (!$this->eventProjectEventId) {
            $this->eventParseError = 'Please select a project event.';
            return;
        }

        $projectEvent = \App\Models\ProjectEvent::find($this->eventProjectEventId);
        if (!$projectEvent) {
            $this->eventParseError = 'Project event not found.';
            return;
        }

        // Populate form from project event
        $this->eventTitle = $projectEvent->title;
        $this->eventType = in_array($projectEvent->type, ['workshop', 'briefing', 'demo', 'launch'])
            ? ($projectEvent->type === 'briefing' ? 'presentation' : $projectEvent->type)
            : 'other';
        $this->eventStartDate = $projectEvent->event_date?->format('Y-m-d') ?? '';
        $this->eventStartTime = $projectEvent->event_date?->format('H:i') ?? '';
        $this->eventLocation = $projectEvent->location ?? '';
        $this->eventDescription = $projectEvent->description ?? '';

        $this->eventMode = 'manual'; // Switch to manual to show form
    }

    protected function applyExtractedEvent(array $data): void
    {
        $this->eventTitle = $data['title'] ?? '';
        $this->eventType = $data['type'] ?? 'other';
        $this->eventLocation = $data['location'] ?? '';
        $this->eventAddress = $data['address'] ?? '';
        $this->eventDescription = $data['description'] ?? '';
        $this->eventNotes = $data['notes'] ?? '';

        // Parse datetime
        if (!empty($data['start_datetime'])) {
            try {
                $dt = \Carbon\Carbon::parse($data['start_datetime']);
                $this->eventStartDate = $dt->format('Y-m-d');
                $this->eventStartTime = $dt->format('H:i');
            } catch (\Exception $e) {
            }
        }

        if (!empty($data['end_datetime'])) {
            try {
                $dt = \Carbon\Carbon::parse($data['end_datetime']);
                $this->eventEndDate = $dt->format('Y-m-d');
                $this->eventEndTime = $dt->format('H:i');
            } catch (\Exception $e) {
            }
        }
    }

    public function editEvent(int $eventId): void
    {
        $event = \App\Models\TripEvent::where('id', $eventId)
            ->where('trip_id', $this->trip->id)
            ->first();

        if (!$event) {
            return;
        }

        $this->editingEventId = $eventId;
        $this->eventMode = 'manual';
        $this->eventTitle = $event->title ?? '';
        $this->eventType = $event->type ?? 'other';
        $this->eventStartDate = $event->start_datetime?->format('Y-m-d') ?? '';
        $this->eventStartTime = $event->start_datetime?->format('H:i') ?? '';
        $this->eventEndDate = $event->end_datetime?->format('Y-m-d') ?? '';
        $this->eventEndTime = $event->end_datetime?->format('H:i') ?? '';
        $this->eventLocation = $event->location ?? '';
        $this->eventAddress = $event->address ?? '';
        $this->eventDescription = $event->description ?? '';
        $this->eventNotes = $event->notes ?? '';
        $this->eventProjectEventId = $event->project_event_id;

        $this->showAddEvent = true;
    }

    public function saveEvent(): void
    {
        $this->validate([
            'eventTitle' => 'required|string|max:255',
            'eventType' => 'required|in:conference_session,meeting,presentation,workshop,reception,site_visit,other',
            'eventStartDate' => 'required|date',
        ]);

        // Build datetime
        $startDatetime = $this->eventStartDate . ($this->eventStartTime ? ' ' . $this->eventStartTime : ' 00:00');
        $endDatetime = null;
        if ($this->eventEndDate) {
            $endDatetime = $this->eventEndDate . ($this->eventEndTime ? ' ' . $this->eventEndTime : ' 23:59');
        }

        $data = [
            'title' => $this->eventTitle,
            'type' => $this->eventType,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => $this->eventLocation ?: null,
            'address' => $this->eventAddress ?: null,
            'description' => $this->eventDescription ?: null,
            'notes' => $this->eventNotes ?: null,
            'project_event_id' => $this->eventProjectEventId ?: null,
        ];

        if ($this->editingEventId) {
            $event = \App\Models\TripEvent::where('id', $this->editingEventId)
                ->where('trip_id', $this->trip->id)
                ->first();

            if ($event) {
                $event->update($data);
            }

            $this->trip->load('events');
            $this->closeAddEvent();
            $this->dispatch('notify', type: 'success', message: 'Event updated!');
            return;
        }

        // Create new event
        $this->trip->events()->create(array_merge($data, [
            'ai_extracted' => $this->extractedEvent !== null,
        ]));

        $this->trip->load('events');
        $this->closeAddEvent();
        $this->dispatch('notify', type: 'success', message: 'Event added!');
    }

    public function deleteEvent(int $eventId): void
    {
        \App\Models\TripEvent::where('id', $eventId)
            ->where('trip_id', $this->trip->id)
            ->delete();

        $this->trip->load('events');
        $this->dispatch('notify', type: 'success', message: 'Event removed.');
    }

    public function getProjectEventsProperty()
    {
        return \App\Models\ProjectEvent::orderBy('event_date', 'desc')
            ->limit(50)
            ->get();
    }

    // Expense Management
    public function openAddExpense(): void
    {
        $this->resetExpenseForm();
        $this->showAddExpense = true;
    }

    public function closeAddExpense(): void
    {
        $this->showAddExpense = false;
        $this->resetExpenseForm();
    }

    public function setExpenseMode(string $mode): void
    {
        $this->expenseMode = $mode;
        $this->extractedExpense = null;
        $this->expenseParseError = null;
    }

    protected function resetExpenseForm(): void
    {
        $this->reset([
            'editingExpenseId',
            'expenseMode',
            'expenseCategory',
            'expenseDescription',
            'expenseDate',
            'expenseAmount',
            'expenseCurrency',
            'expenseVendor',
            'expenseReceiptNumber',
            'expenseNotes',
            'expenseReimbursementStatus',
            'expenseReceiptFile',
            'expenseSmartText',
            'extractedExpense',
            'expenseParsing',
            'expenseParseError',
        ]);
        $this->expenseMode = 'manual';
        $this->expenseCategory = 'other';
        $this->expenseCurrency = 'USD';
        $this->expenseReimbursementStatus = 'pending';

        // Default to trip start date
        $this->expenseDate = $this->trip->start_date->format('Y-m-d');
    }

    public function parseExpenseText(): void
    {
        $this->expenseParseError = null;
        $this->extractedExpense = null;

        if (empty(trim($this->expenseSmartText))) {
            $this->expenseParseError = 'Please paste expense/receipt details.';
            return;
        }

        $this->expenseParsing = true;

        try {
            $parser = new \App\Services\ExpenseParserService();
            $result = $parser->parseText($this->expenseSmartText);

            if (isset($result['error'])) {
                $this->expenseParseError = $result['error'];
            } elseif (isset($result['expense'])) {
                $this->extractedExpense = $result['expense'];
                $this->applyExtractedExpense($result['expense']);
            } else {
                $this->expenseParseError = 'Could not extract expense information.';
            }
        } catch (\Exception $e) {
            $this->expenseParseError = 'Error parsing text: ' . $e->getMessage();
        }

        $this->expenseParsing = false;
    }

    protected function applyExtractedExpense(array $data): void
    {
        $this->expenseCategory = $data['category'] ?? 'other';
        $this->expenseDescription = $data['description'] ?? '';
        $this->expenseDate = $data['expense_date'] ?? '';
        $this->expenseAmount = $data['amount'] ?? null;
        $this->expenseCurrency = $data['currency'] ?? 'USD';
        $this->expenseVendor = $data['vendor'] ?? '';
        $this->expenseReceiptNumber = $data['receipt_number'] ?? '';
        $this->expenseNotes = $data['notes'] ?? '';
    }

    public function editExpense(int $expenseId): void
    {
        $expense = \App\Models\TripExpense::where('id', $expenseId)
            ->where('trip_id', $this->trip->id)
            ->first();

        if (!$expense) {
            return;
        }

        // Only allow editing own expenses unless management
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($expense->user_id !== $user->id && !$user->isManagement()) {
            return;
        }

        $this->editingExpenseId = $expenseId;
        $this->expenseMode = 'manual';
        $this->expenseCategory = $expense->category ?? 'other';
        $this->expenseDescription = $expense->description ?? '';
        $this->expenseDate = $expense->expense_date?->format('Y-m-d') ?? '';
        $this->expenseAmount = $expense->amount;
        $this->expenseCurrency = $expense->currency ?? 'USD';
        $this->expenseVendor = $expense->vendor ?? '';
        $this->expenseReceiptNumber = $expense->receipt_number ?? '';
        $this->expenseNotes = $expense->notes ?? '';
        $this->expenseReimbursementStatus = $expense->reimbursement_status ?? 'pending';

        $this->showAddExpense = true;
    }

    public function saveExpense(): void
    {
        $this->validate([
            'expenseCategory' => 'required|in:' . implode(',', array_keys(\App\Models\TripExpense::getCategoryOptions())),
            'expenseDescription' => 'required|string|max:255',
            'expenseDate' => 'required|date',
            'expenseAmount' => 'required|numeric|min:0',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();

        $data = [
            'category' => $this->expenseCategory,
            'description' => $this->expenseDescription,
            'expense_date' => $this->expenseDate,
            'amount' => $this->expenseAmount,
            'currency' => $this->expenseCurrency ?: 'USD',
            'vendor' => $this->expenseVendor ?: null,
            'receipt_number' => $this->expenseReceiptNumber ?: null,
            'notes' => $this->expenseNotes ?: null,
        ];

        // Handle receipt file upload
        if ($this->expenseReceiptFile) {
            $path = $this->expenseReceiptFile->store('expense-receipts', 'local');
            $data['receipt_path'] = $path;
            $data['receipt_original_name'] = $this->expenseReceiptFile->getClientOriginalName();
        }

        if ($this->editingExpenseId) {
            $expense = \App\Models\TripExpense::where('id', $this->editingExpenseId)
                ->where('trip_id', $this->trip->id)
                ->first();

            if ($expense) {
                // Only allow editing own expenses unless management
                if ($expense->user_id !== $user->id && !$user->isManagement()) {
                    $this->expenseParseError = 'You can only edit your own expenses.';
                    return;
                }

                // Management can also update status
                if ($user->isManagement()) {
                    $data['reimbursement_status'] = $this->expenseReimbursementStatus;
                }

                $expense->update($data);
            }

            $this->trip->load('expenses.user');
            $this->closeAddExpense();
            $this->dispatch('notify', type: 'success', message: 'Expense updated!');
            return;
        }

        // Create new expense for current user
        $this->trip->expenses()->create(array_merge($data, [
            'user_id' => $user->id,
            'reimbursement_status' => 'pending',
            'ai_extracted' => $this->extractedExpense !== null,
            'source_text' => $this->expenseSmartText ?: null,
        ]));

        $this->trip->load('expenses.user');
        $this->closeAddExpense();
        $this->dispatch('notify', type: 'success', message: 'Expense added!');
    }

    public function deleteExpense(int $expenseId): void
    {
        $expense = \App\Models\TripExpense::where('id', $expenseId)
            ->where('trip_id', $this->trip->id)
            ->first();

        if (!$expense) {
            return;
        }

        $user = \Illuminate\Support\Facades\Auth::user();

        // Only allow deleting own expenses unless management
        if ($expense->user_id !== $user->id && !$user->isManagement()) {
            return;
        }

        $expense->delete();
        $this->trip->load('expenses.user');
        $this->dispatch('notify', type: 'success', message: 'Expense removed.');
    }

    public function approveExpense(int $expenseId): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user->isManagement()) {
            return;
        }

        $expense = \App\Models\TripExpense::where('id', $expenseId)
            ->where('trip_id', $this->trip->id)
            ->first();

        if ($expense) {
            $expense->update([
                'reimbursement_status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        }

        $this->trip->load('expenses.user');
        $this->dispatch('notify', type: 'success', message: 'Expense approved.');
    }

    public function markExpensePaid(int $expenseId): void
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user->isManagement()) {
            return;
        }

        $expense = \App\Models\TripExpense::where('id', $expenseId)
            ->where('trip_id', $this->trip->id)
            ->first();

        if ($expense) {
            $expense->update([
                'reimbursement_status' => 'paid',
                'reimbursement_paid_date' => now(),
            ]);
        }

        $this->trip->load('expenses.user');
        $this->dispatch('notify', type: 'success', message: 'Expense marked as paid.');
    }

    public function getVisibleExpensesProperty()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Management sees all expenses
        if ($user->isManagement()) {
            return $this->trip->expenses->sortByDesc('expense_date');
        }

        // Regular users only see their own expenses
        return $this->trip->expenses->where('user_id', $user->id)->sortByDesc('expense_date');
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

    public function sendAgentMessage(TripAgentService $service): void
    {
        if (! $this->canUseTripAgent()) {
            $this->dispatch('notify', type: 'error', message: 'You do not have access to the trip agent.');

            return;
        }

        $message = trim($this->agentMessage);
        if ($message === '') {
            return;
        }

        $this->agentBusy = true;

        try {
            $conversation = $this->ensureAgentConversation($service);
            $result = $service->proposeChanges($this->trip, Auth::user(), $message, $conversation);

            $this->agentConversationId = $conversation->id;
            $this->agentMessage = '';

            if ($result['action'] ?? null) {
                $this->dispatch('notify', type: 'success', message: 'Drafted proposed changes. Review and approve to apply.');
            } else {
                $this->dispatch('notify', type: 'success', message: 'Message sent to trip agent.');
            }
        } catch (\Throwable $exception) {
            \Log::warning('Trip agent message failed', [
                'trip_id' => $this->trip->id,
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: 'Trip agent could not process that message right now.');
        } finally {
            $this->agentBusy = false;
        }
    }

    public function applyAgentAction(int $actionId, TripAgentService $service): void
    {
        if (! $this->canEdit()) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to apply trip agent actions.');

            return;
        }

        $action = TripAgentAction::query()
            ->where('id', $actionId)
            ->whereHas('conversation', fn ($query) => $query->where('trip_id', $this->trip->id))
            ->first();

        if (! $action) {
            $this->dispatch('notify', type: 'error', message: 'Trip agent action not found.');

            return;
        }

        $this->agentBusy = true;

        try {
            $service->applyAction($action, Auth::user());
            $this->loadTripRelations();
            $this->dispatch('notify', type: 'success', message: 'Trip updates applied.');
        } catch (\Throwable $exception) {
            \Log::warning('Trip agent apply action failed', [
                'trip_id' => $this->trip->id,
                'action_id' => $actionId,
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: 'Could not apply this proposal: '.$exception->getMessage());
        } finally {
            $this->agentBusy = false;
        }
    }

    public function rejectAgentAction(int $actionId, TripAgentService $service): void
    {
        if (! $this->canEdit()) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to reject trip agent actions.');

            return;
        }

        $action = TripAgentAction::query()
            ->where('id', $actionId)
            ->whereHas('conversation', fn ($query) => $query->where('trip_id', $this->trip->id))
            ->first();

        if (! $action) {
            $this->dispatch('notify', type: 'error', message: 'Trip agent action not found.');

            return;
        }

        try {
            $service->rejectAction($action, Auth::user());
            $this->dispatch('notify', type: 'success', message: 'Proposal rejected.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: 'Could not reject this proposal.');
        }
    }

    public function getAgentConversationProperty(): ?TripAgentConversation
    {
        if (! $this->agentConversationId) {
            return null;
        }

        return TripAgentConversation::query()
            ->where('id', $this->agentConversationId)
            ->where('trip_id', $this->trip->id)
            ->first();
    }

    public function getAgentMessagesProperty(): \Illuminate\Support\Collection
    {
        if (! $this->agentConversation) {
            return collect();
        }

        return $this->agentConversation->messages()
            ->with('user')
            ->orderBy('created_at')
            ->limit(100)
            ->get();
    }

    public function getPendingAgentActionsProperty(): \Illuminate\Support\Collection
    {
        if (! $this->agentConversation) {
            return collect();
        }

        return $this->agentConversation->actions()
            ->with('requester')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }

    protected function canUseTripAgent(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($this->canEdit()) {
            return true;
        }

        return $this->trip->isUserTraveler($user);
    }

    protected function ensureAgentConversation(TripAgentService $service): TripAgentConversation
    {
        $conversation = null;
        if ($this->agentConversationId) {
            $conversation = TripAgentConversation::query()
                ->where('id', $this->agentConversationId)
                ->where('trip_id', $this->trip->id)
                ->first();
        }

        $conversation = $service->ensureConversation($this->trip, Auth::user(), $conversation);
        $this->agentConversationId = $conversation->id;

        return $conversation;
    }

    public function canEdit(): bool
    {
        $user = Auth::user();

        return $user->isAdmin()
            || $user->isManagement()
            || $this->trip->created_by === $user->id
            || $this->trip->isUserLead($user);
    }

    public function deleteTrip(): void
    {
        if (!$this->canEdit()) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to delete this trip.');
            return;
        }

        $tripName = $this->trip->name;
        $this->trip->delete();

        session()->flash('success', "Trip '{$tripName}' has been deleted.");
        $this->redirect(route('travel.index'), navigate: true);
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
            'canUseTripAgent' => $this->canUseTripAgent(),
            'countries' => $this->countries,
            'organizations' => $this->organizations,
            'agentConversation' => $this->agentConversation,
            'agentMessages' => $this->agentMessages,
            'pendingAgentActions' => $this->pendingAgentActions,
        ])->title($this->title);
    }
}
