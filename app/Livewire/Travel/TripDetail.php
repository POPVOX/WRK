<?php

namespace App\Livewire\Travel;

use App\Models\Trip;
use App\Models\TripChecklist;
use App\Models\TripSegment;
use App\Models\User;
use App\Services\ItineraryParserService;
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

    public function mount(Trip $trip): void
    {
        $this->trip = $trip->load([
            'travelers',
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
        return $this->trip->name.' - Travel';
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
        if (! $this->smartImportTravelerId) {
            $this->smartImportError = 'Please select a traveler first.';

            return;
        }

        $parser = new ItineraryParserService();

        // If a file was uploaded, use that
        if ($this->smartImportFile) {
            $path = $this->smartImportFile->getRealPath();
            $mimeType = $this->smartImportFile->getMimeType();
            $result = $parser->parseFile($path, $mimeType);
        } elseif (! empty(trim($this->smartImportText))) {
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
        if (! $this->smartImportTravelerId) {
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
        if (! $destination || $destination->order <= 1) {
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
        if (! $destination || $destination->order >= $maxOrder) {
            return;
        }

        $nextDest = $this->trip->destinations->where('order', $destination->order + 1)->first();
        if ($nextDest) {
            $nextDest->update(['order' => $destination->order]);
            $destination->update(['order' => $destination->order + 1]);
        }

        $this->trip->load('destinations');
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
            $item->update(['is_completed' => ! $item->is_completed]);
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

        $byCategory = $expenses->groupBy('category')->map(fn ($items) => $items->sum('amount'));

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

        return $events->sortBy('datetime')->groupBy(fn ($e) => $e['datetime']->format('Y-m-d'))->toArray();
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
        ])->title($this->title);
    }
}
