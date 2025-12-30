<?php

namespace App\Livewire;

use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CalendarSync extends Component
{
    public bool $isConnected = false;
    public array $events = [];
    public array $importResult = [];
    public bool $showImportModal = false;
    public string $syncMessage = '';

    protected GoogleCalendarService $calendarService;

    public function boot(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function mount()
    {
        $this->isConnected = $this->calendarService->isConnected(Auth::user());
    }

    public function fetchEvents()
    {
        if (!$this->isConnected) {
            return;
        }

        $this->events = [];
        $events = $this->calendarService->getEvents(Auth::user());

        foreach ($events as $event) {
            $start = $event->getStart();
            $dateTime = $start->getDateTime() ?? $start->getDate();

            $this->events[] = [
                'id' => $event->getId(),
                'title' => $event->getSummary() ?? 'Untitled',
                'date' => \Carbon\Carbon::parse($dateTime)->format('M j, Y'),
                'description' => $event->getDescription() ?? '',
            ];
        }

        $this->showImportModal = true;
    }

    public function importEvents()
    {
        $events = $this->calendarService->getEvents(Auth::user());
        $this->importResult = $this->calendarService->importEvents(Auth::user(), $events);

        $importedCount = count($this->importResult['imported']);
        $skippedCount = count($this->importResult['skipped']);

        $this->syncMessage = "Imported {$importedCount} meetings. Skipped {$skippedCount} (already imported).";
        $this->showImportModal = false;

        $this->dispatch('meetings-imported');
    }

    public function closeModal()
    {
        $this->showImportModal = false;
        $this->events = [];
    }

    public function render()
    {
        return view('livewire.calendar-sync');
    }
}
