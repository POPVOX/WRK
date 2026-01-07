<?php

namespace App\Livewire\Meetings;

use App\Models\Issue;
use App\Models\Meeting;
use App\Models\Organization;
use App\Services\BulkMeetingImportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Meetings')]
class MeetingList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $view = ''; // '', 'upcoming', 'needs_notes', 'completed'

    #[Url]
    public string $viewMode = 'sections'; // 'sections', 'list', 'cards', 'kanban'

    #[Url]
    public string $organization = '';

    #[Url]
    public string $issue = '';

    #[Url]
    public string $completedPeriod = 'month'; // week, month, quarter, year, all

    // Bulk import state
    public bool $showBulkImportModal = false;

    public string $bulkImportText = '';

    public array $extractedMeetings = [];

    public bool $isExtracting = false;

    public bool $isImporting = false;

    public ?string $importError = null;

    public ?string $importSuccess = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingView()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->view = '';
        $this->organization = '';
        $this->issue = '';
        $this->completedPeriod = 'month';
        $this->resetPage();
    }

    // Bulk Import Methods
    public function openBulkImport()
    {
        $this->showBulkImportModal = true;
        $this->bulkImportText = '';
        $this->extractedMeetings = [];
        $this->importError = null;
        $this->importSuccess = null;
    }

    public function closeBulkImport()
    {
        $this->showBulkImportModal = false;
        $this->bulkImportText = '';
        $this->extractedMeetings = [];
        $this->importError = null;
        $this->importSuccess = null;
    }

    public function extractMeetings()
    {
        if (empty(trim($this->bulkImportText))) {
            $this->importError = 'Please paste some text to extract meetings from.';

            return;
        }

        $this->isExtracting = true;
        $this->importError = null;
        $this->extractedMeetings = [];

        $service = app(BulkMeetingImportService::class);
        $result = $service->extractMeetings($this->bulkImportText);

        $this->isExtracting = false;

        if ($result['error']) {
            $this->importError = $result['error'];

            return;
        }

        if (empty($result['meetings'])) {
            $this->importError = 'No meetings could be extracted from the text. Please try with more structured information.';

            return;
        }

        $this->extractedMeetings = $result['meetings'];
    }

    public function removeExtractedMeeting($index)
    {
        unset($this->extractedMeetings[$index]);
        $this->extractedMeetings = array_values($this->extractedMeetings);
    }

    public function importMeetings()
    {
        if (empty($this->extractedMeetings)) {
            $this->importError = 'No meetings to import.';

            return;
        }

        $this->isImporting = true;
        $this->importError = null;

        $service = app(BulkMeetingImportService::class);
        $result = $service->createMeetings($this->extractedMeetings);

        $this->isImporting = false;

        $createdCount = count($result['created']);

        if ($createdCount > 0) {
            $this->importSuccess = "Successfully imported {$createdCount} meeting(s)!";
            $this->extractedMeetings = [];
            $this->bulkImportText = '';

            // Close modal after short delay to show success
            $this->dispatch('notify', type: 'success', message: "{$createdCount} meetings imported!");
        }

        if (! empty($result['errors'])) {
            $this->importError = implode("\n", $result['errors']);
        }
    }

    public function deleteMeeting(int $id): void
    {
        $meeting = Meeting::find($id);

        if (! $meeting) {
            $this->dispatch('notify', type: 'error', message: 'Meeting not found.');

            return;
        }

        // Detach relationships
        $meeting->organizations()->detach();
        $meeting->people()->detach();
        $meeting->issues()->detach();
        $meeting->teamMembers()->detach();

        // Delete attachments
        foreach ($meeting->attachments as $attachment) {
            if ($attachment->file_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
            }
            $attachment->delete();
        }

        // Delete the meeting
        $meeting->delete();

        $this->dispatch('notify', type: 'success', message: 'Meeting deleted successfully.');
    }

    // Stats for section headers
    public function getStatsProperty()
    {
        return [
            'upcoming' => Meeting::upcoming()->count(),
            'needs_notes' => Meeting::needsNotes()->count(),
            'completed_this_month' => Meeting::withNotes()->where('meeting_date', '>=', now()->subMonth())->count(),
        ];
    }

    // Upcoming meetings (no pagination, show all)
    public function getUpcomingMeetingsProperty()
    {
        $query = Meeting::upcoming()
            ->with(['people', 'organizations', 'issues'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organization, fn ($q) => $q->whereHas('organizations', fn ($o) => $o->where('organizations.id', $this->organization)))
            ->when($this->issue, fn ($q) => $q->whereHas('issues', fn ($i) => $i->where('issues.id', $this->issue)));

        return $query->get();
    }

    // Meetings needing notes
    public function getNeedsNotesMeetingsProperty()
    {
        $query = Meeting::needsNotes()
            ->with(['people', 'organizations'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organization, fn ($q) => $q->whereHas('organizations', fn ($o) => $o->where('organizations.id', $this->organization)))
            ->when($this->issue, fn ($q) => $q->whereHas('issues', fn ($i) => $i->where('issues.id', $this->issue)));

        return $query->limit(10)->get();
    }

    // Completed meetings with notes
    public function getCompletedMeetingsProperty()
    {
        $query = Meeting::withNotes()
            ->with(['people', 'organizations', 'issues'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organization, fn ($q) => $q->whereHas('organizations', fn ($o) => $o->where('organizations.id', $this->organization)))
            ->when($this->issue, fn ($q) => $q->whereHas('issues', fn ($i) => $i->where('issues.id', $this->issue)));

        // Period filter
        $query->when($this->completedPeriod === 'week', fn ($q) => $q->where('meeting_date', '>=', now()->subWeek()))
            ->when($this->completedPeriod === 'month', fn ($q) => $q->where('meeting_date', '>=', now()->subMonth()))
            ->when($this->completedPeriod === 'quarter', fn ($q) => $q->where('meeting_date', '>=', now()->subQuarter()))
            ->when($this->completedPeriod === 'year', fn ($q) => $q->where('meeting_date', '>=', now()->subYear()));

        return $query->paginate(15, pageName: 'completed');
    }

    // All meetings for list/cards view
    public function getAllMeetingsProperty()
    {
        $query = Meeting::with(['people', 'organizations', 'issues'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organization, fn ($q) => $q->whereHas('organizations', fn ($o) => $o->where('organizations.id', $this->organization)))
            ->when($this->issue, fn ($q) => $q->whereHas('issues', fn ($i) => $i->where('issues.id', $this->issue)));

        // Filter by view type
        if ($this->view === 'upcoming') {
            $query->upcoming();
        } elseif ($this->view === 'needs_notes') {
            $query->needsNotes();
        } elseif ($this->view === 'completed') {
            $query->withNotes();
        } else {
            $query->orderBy('meeting_date', 'desc');
        }

        return $query->paginate(24);
    }

    // Kanban meetings grouped by month
    public function getKanbanMeetingsProperty()
    {
        $query = Meeting::with(['people', 'organizations', 'issues'])
            ->where('meeting_date', '>=', now()->startOfMonth())
            ->where('meeting_date', '<=', now()->addMonths(6)->endOfMonth())
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->organization, fn ($q) => $q->whereHas('organizations', fn ($o) => $o->where('organizations.id', $this->organization)))
            ->when($this->issue, fn ($q) => $q->whereHas('issues', fn ($i) => $i->where('issues.id', $this->issue)))
            ->orderBy('meeting_date')
            ->get();

        return $query->groupBy(fn ($m) => $m->meeting_date->format('Y-m'));
    }

    public function render()
    {
        $organizations = Organization::orderBy('name')->get();
        $issues = Issue::orderBy('name')->get();

        return view('livewire.meetings.meeting-list', [
            'upcomingMeetings' => $this->upcomingMeetings,
            'needsNotesMeetings' => $this->needsNotesMeetings,
            'completedMeetings' => $this->completedMeetings,
            'allMeetings' => $this->allMeetings,
            'kanbanMeetings' => $this->kanbanMeetings,
            'stats' => $this->stats,
            'organizations' => $organizations,
            'issues' => $issues,
        ]);
    }
}
