<?php

namespace App\Livewire\Travel;

use App\Models\Trip;
use App\Models\TripChecklist;
use App\Models\TripSegment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class TripDetail extends Component
{
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

    public string $segmentType = 'flight';

    public string $segmentCarrier = '';

    public string $segmentNumber = '';

    public string $segmentDepartureLocation = '';

    public string $segmentDepartureDatetime = '';

    public string $segmentArrivalLocation = '';

    public string $segmentArrivalDatetime = '';

    public string $segmentConfirmation = '';

    // Add checklist item
    public string $newChecklistItem = '';

    public string $newChecklistCategory = 'other';

    public function mount(Trip $trip): void
    {
        $this->trip = $trip->load([
            'travelers',
            'destinations',
            'segments',
            'lodging',
            'groundTransport',
            'expenses',
            'sponsorships.organization',
            'events',
            'documents',
            'checklists',
            'project',
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
    public function openAddSegment(): void
    {
        $this->reset([
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
        $this->showAddSegment = true;
    }

    public function closeAddSegment(): void
    {
        $this->showAddSegment = false;
    }

    public function saveSegment(): void
    {
        $this->validate([
            'segmentType' => 'required|in:flight,train,bus,rental_car,rideshare,ferry,other_transport',
            'segmentDepartureLocation' => 'required|string|max:100',
            'segmentDepartureDatetime' => 'required|date',
            'segmentArrivalLocation' => 'required|string|max:100',
            'segmentArrivalDatetime' => 'required|date|after:segmentDepartureDatetime',
        ]);

        $this->trip->segments()->create([
            'type' => $this->segmentType,
            'carrier' => $this->segmentCarrier ?: null,
            'segment_number' => $this->segmentNumber ?: null,
            'departure_location' => $this->segmentDepartureLocation,
            'departure_datetime' => $this->segmentDepartureDatetime,
            'arrival_location' => $this->segmentArrivalLocation,
            'arrival_datetime' => $this->segmentArrivalDatetime,
            'confirmation_number' => $this->segmentConfirmation ?: null,
        ]);

        $this->trip->load('segments');
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

    public function render()
    {
        return view('livewire.travel.trip-detail', [
            'expenseStats' => $this->expenseStats,
            'sponsorshipStats' => $this->sponsorshipStats,
            'timeline' => $this->timeline,
            'canEdit' => $this->canEdit(),
        ])->title($this->title);
    }
}
