<?php

namespace App\Livewire\Meetings;

use App\Models\Action;
use App\Models\Issue;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Services\MeetingAIService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Meeting Details')]
class MeetingDetail extends Component
{
    public Meeting $meeting;

    // Editing mode - default to true for inline editing
    public bool $editing = true;

    public string $title = '';

    public string $meeting_date = '';

    public ?string $meeting_time = null;

    public ?string $meeting_end_time = null;

    public string $prep_notes = '';  // Before meeting prep

    public string $raw_notes = '';   // During meeting notes

    public string $aiSummary = '';

    public string $keyAsk = '';

    public string $commitmentsMade = '';

    public ?int $leadContactId = null;

    // Relationship selections
    public array $selectedOrganizations = [];

    public array $selectedPeople = [];

    public array $selectedIssues = [];

    // Action form fields
    public string $newActionDescription = '';

    public ?string $newActionDueDate = null;

    public string $newActionPriority = 'medium';

    // New person/organization inline add
    public bool $showAddPersonForm = false;

    public string $newPersonName = '';

    public string $newPersonEmail = '';

    public string $newPersonTitle = '';

    public ?int $newPersonOrganizationId = null;

    public bool $showAddOrganizationForm = false;

    public string $newOrganizationName = '';

    public string $newOrganizationType = 'other';

    // Meeting Prep Modal
    public bool $showPrepModal = false;

    public string $prepInputText = '';

    public bool $isPrepAnalyzing = false;

    public ?array $prepAnalysis = null;

    // Voice Dictation & AI Summarization
    public bool $isRecording = false;

    public bool $isSummarizing = false;

    public string $notesSummary = '';

    // Agenda Management
    public string $agendaNotes = '';
    public bool $showAddAgendaItem = false;
    public string $newAgendaTitle = '';
    public string $newAgendaDescription = '';
    public ?int $newAgendaDuration = null;
    public ?int $newAgendaPresenter = null;
    public ?int $editingAgendaItemId = null;

    public function mount(Meeting $meeting)
    {
        $this->meeting = $meeting->load([
            'user',
            'leadContact',
            'organizations',
            'people.organization',
            'issues',
            'attachments',
            'agendaItems.presenter',
            'actions.assignedTo',
        ]);
        $this->loadMeetingData();
    }

    public function loadMeetingData()
    {
        $this->title = $this->meeting->title ?? '';
        $this->meeting_date = $this->meeting->meeting_date->format('Y-m-d');
        $this->meeting_time = $this->meeting->meeting_time;
        $this->meeting_end_time = $this->meeting->meeting_end_time;
        $this->prep_notes = $this->meeting->prep_notes ?? '';
        $this->agendaNotes = $this->meeting->agenda_notes ?? '';
        $this->raw_notes = $this->meeting->raw_notes ?? '';
        $this->aiSummary = $this->meeting->ai_summary ?? '';
        $this->keyAsk = $this->meeting->key_ask ?? '';
        $this->commitmentsMade = $this->meeting->commitments_made ?? '';

        $this->selectedOrganizations = $this->meeting->organizations->pluck('id')->toArray();
        $this->selectedPeople = $this->meeting->people->pluck('id')->toArray();
        $this->selectedIssues = $this->meeting->issues->pluck('id')->toArray();
        $this->leadContactId = $this->meeting->lead_contact_id;
    }

    public function startEditing()
    {
        $this->editing = true;
    }

    public function cancelEditing()
    {
        $this->editing = false;
        $this->loadMeetingData();
    }

    public function save()
    {
        $this->validate([
            'title' => 'nullable|string|max:255',
            'meeting_date' => 'required|date',
            'meeting_time' => 'nullable|date_format:H:i',
            'meeting_end_time' => 'nullable|date_format:H:i',
            'prep_notes' => 'nullable|string',
            'agendaNotes' => 'nullable|string',
            'raw_notes' => 'nullable|string',
        ]);

        $this->meeting->update([
            'title' => $this->title ?: null,
            'meeting_date' => $this->meeting_date,
            'meeting_time' => $this->meeting_time ?: null,
            'meeting_end_time' => $this->meeting_end_time ?: null,
            'prep_notes' => $this->prep_notes ?: null,
            'agenda_notes' => $this->agendaNotes ?: null,
            'raw_notes' => $this->raw_notes ?: null,
            'ai_summary' => $this->aiSummary ?: null,
            'key_ask' => $this->keyAsk ?: null,
            'commitments_made' => $this->commitmentsMade ?: null,
            'lead_contact_id' => $this->leadContactId,
        ]);

        // Sync relationships
        $this->meeting->organizations()->sync($this->selectedOrganizations);
        $this->meeting->people()->sync($this->selectedPeople);
        $this->meeting->issues()->sync($this->selectedIssues);

        $this->meeting->refresh();
        $this->editing = false;
        $this->dispatch('notify', type: 'success', message: 'Meeting updated successfully!');
    }

    public function toggleActionComplete(int $actionId)
    {
        $action = Action::find($actionId);

        if ($action->status === Action::STATUS_PENDING) {
            $action->markComplete();
        } else {
            $action->update([
                'status' => Action::STATUS_PENDING,
                'completed_at' => null,
            ]);
        }

        $this->meeting->refresh();
    }

    public function addAction()
    {
        $this->validate([
            'newActionDescription' => 'required|string|min:3',
            'newActionDueDate' => 'nullable|date',
            'newActionPriority' => 'required|in:high,medium,low',
        ]);

        Action::create([
            'meeting_id' => $this->meeting->id,
            'description' => $this->newActionDescription,
            'due_date' => $this->newActionDueDate,
            'priority' => $this->newActionPriority,
            'assigned_to' => Auth::id(),
        ]);

        $this->newActionDescription = '';
        $this->newActionDueDate = null;
        $this->newActionPriority = 'medium';

        $this->meeting->refresh();
    }

    public function deleteAction(int $actionId)
    {
        Action::destroy($actionId);
        $this->meeting->refresh();
    }

    public function updateStatus(string $status)
    {
        if (in_array($status, Meeting::STATUSES)) {
            $this->meeting->update(['status' => $status]);
            $this->meeting->refresh();
        }
    }

    public function addNewPerson()
    {
        $this->validate([
            'newPersonName' => 'required|string|min:2|max:255',
            'newPersonEmail' => 'nullable|email|max:255',
            'newPersonTitle' => 'nullable|string|max:255',
        ]);

        $person = Person::create([
            'name' => $this->newPersonName,
            'email' => $this->newPersonEmail ?: null,
            'title' => $this->newPersonTitle ?: null,
            'organization_id' => $this->newPersonOrganizationId,
        ]);

        // Add to this meeting
        $this->selectedPeople[] = $person->id;
        $this->meeting->people()->attach($person->id);

        // Reset form
        $this->newPersonName = '';
        $this->newPersonEmail = '';
        $this->newPersonTitle = '';
        $this->newPersonOrganizationId = null;
        $this->showAddPersonForm = false;

        $this->meeting->refresh();
        $this->dispatch('notify', type: 'success', message: "Added {$person->name} to meeting");
    }

    public function addNewOrganization()
    {
        $this->validate([
            'newOrganizationName' => 'required|string|min:2|max:255',
            'newOrganizationType' => 'required|string',
        ]);

        $org = Organization::create([
            'name' => $this->newOrganizationName,
            'type' => $this->newOrganizationType,
        ]);

        // Add to this meeting
        $this->selectedOrganizations[] = $org->id;
        $this->meeting->organizations()->attach($org->id);

        // Reset form
        $this->newOrganizationName = '';
        $this->newOrganizationType = 'other';
        $this->showAddOrganizationForm = false;

        $this->meeting->refresh();
        $this->dispatch('notify', type: 'success', message: "Added {$org->name} to meeting");
    }

    public function deleteMeeting()
    {
        $this->meeting->delete();

        session()->flash('success', 'Meeting deleted successfully.');

        return redirect()->route('meetings.index');
    }

    // ==========================================
    // Voice Dictation & AI Summarization Methods
    // ==========================================

    /**
     * Append dictated text to raw notes
     */
    public function appendDictation(string $text): void
    {
        if (empty(trim($text))) {
            return;
        }

        // Add to raw_notes with timestamp separator
        $timestamp = now()->format('g:i A');
        $separator = empty($this->raw_notes) ? '' : "\n\n";
        $this->raw_notes .= $separator."[{$timestamp}] ".trim($text);

        $this->dispatch('notify', type: 'success', message: 'Voice note added');
    }

    /**
     * Summarize meeting notes with AI
     */
    public function summarizeNotes(): void
    {
        if (empty(trim($this->raw_notes))) {
            $this->dispatch('notify', type: 'error', message: 'No notes to summarize');

            return;
        }

        $this->isSummarizing = true;

        try {
            $aiService = app(MeetingAIService::class);
            $extraction = $aiService->extractMeetingData($this->raw_notes);

            // Update the AI summary
            if (! empty($extraction['ai_summary'])) {
                $this->aiSummary = $extraction['ai_summary'];
            }

            // Update key ask if extracted
            if (! empty($extraction['key_ask'])) {
                $this->keyAsk = $extraction['key_ask'];
            }

            // Update commitments if extracted
            if (! empty($extraction['commitments_made'])) {
                $this->commitmentsMade = $extraction['commitments_made'];
            }

            // Also save a quick summary for display
            $this->notesSummary = $extraction['ai_summary'] ?? '';

            // Auto-save the extraction results
            $this->meeting->update([
                'ai_summary' => $this->aiSummary,
                'key_ask' => $this->keyAsk,
                'commitments_made' => $this->commitmentsMade,
            ]);

            $this->dispatch('notify', type: 'success', message: 'Notes summarized with AI!');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to summarize: '.$e->getMessage());
        } finally {
            $this->isSummarizing = false;
        }
    }

    /**
     * Toggle recording state (UI feedback only - actual recording handled in JS)
     */
    public function toggleRecording(): void
    {
        $this->isRecording = ! $this->isRecording;
    }

    // Meeting Prep Methods
    public function openPrepModal()
    {
        $this->showPrepModal = true;
        $this->prepInputText = '';
        $this->prepAnalysis = null;
    }

    public function closePrepModal()
    {
        $this->showPrepModal = false;
    }

    public function analyzePrepMaterial()
    {
        $this->isPrepAnalyzing = true;

        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            $this->prepAnalysis = ['error' => 'AI features are not configured.'];
            $this->isPrepAnalyzing = false;

            return;
        }

        // Build context from the meeting and related data
        $meetingContext = $this->buildMeetingContext();

        try {
            $response = \Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 2000,
                'system' => 'You are an executive assistant helping prepare for a meeting. Analyze the provided material and context to create a comprehensive meeting prep brief. Return a JSON object with the following structure:
{
    "attendee_analysis": {
        "key_people": ["Name - Role/Context"],
        "organization_context": "Brief on the organizations involved"
    },
    "suggested_topics": ["Topic 1", "Topic 2"],
    "relevant_history": "Summary of past interactions if any",
    "relevant_projects": ["Project names that may be discussed"],
    "key_questions": ["Questions to ask"],
    "preparation_notes": "Any specific preparation recommendations",
    "potential_asks": ["Things they might ask for", "Things we might ask for"]
}',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Meeting Context:\n{$meetingContext}\n\nAdditional Material:\n{$this->prepInputText}\n\nPlease analyze this and provide a meeting prep brief as JSON.",
                    ],
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json()['content'][0]['text'] ?? '';
                // Try to parse JSON from the response
                if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                    $this->prepAnalysis = json_decode($matches[0], true) ?? ['raw' => $content];
                } else {
                    $this->prepAnalysis = ['raw' => $content];
                }
            } else {
                $this->prepAnalysis = ['error' => 'Failed to analyze. Try again.'];
            }
        } catch (\Exception $e) {
            $this->prepAnalysis = ['error' => 'Error: '.$e->getMessage()];
        }

        $this->isPrepAnalyzing = false;
    }

    protected function buildMeetingContext(): string
    {
        $context = [];

        $context[] = 'Meeting Title: '.($this->meeting->title ?: 'Untitled');
        $context[] = 'Date: '.($this->meeting->meeting_date?->format('F j, Y') ?: 'TBD');

        // Attendees
        if ($this->meeting->people->count() > 0) {
            $attendees = $this->meeting->people->map(
                fn ($p) => $p->name.($p->title ? " ({$p->title})" : '').($p->organization ? " at {$p->organization->name}" : '')
            )->join(', ');
            $context[] = 'Attendees: '.$attendees;
        }

        // Organizations
        if ($this->meeting->organizations->count() > 0) {
            $orgs = $this->meeting->organizations->pluck('name')->join(', ');
            $context[] = 'Organizations: '.$orgs;

            // Get past meetings with these organizations
            $pastMeetings = \App\Models\Meeting::whereHas('organizations', function ($q) {
                $q->whereIn('organizations.id', $this->meeting->organizations->pluck('id'));
            })
                ->where('id', '!=', $this->meeting->id)
                ->where('meeting_date', '<', now())
                ->orderByDesc('meeting_date')
                ->limit(5)
                ->get();

            if ($pastMeetings->count() > 0) {
                $context[] = "\nPast meetings with these organizations:";
                foreach ($pastMeetings as $pm) {
                    $context[] = "- {$pm->meeting_date->format('M j, Y')}: ".($pm->title ?: 'Untitled');
                    if ($pm->ai_summary) {
                        $context[] = '  Summary: '.\Str::limit($pm->ai_summary, 200);
                    }
                }
            }
        }

        // Issues/Topics
        if ($this->meeting->issues->count() > 0) {
            $issues = $this->meeting->issues->pluck('name')->join(', ');
            $context[] = 'Topics: '.$issues;
        }

        // Relevant Projects
        $projects = \App\Models\Project::whereHas('organizations', function ($q) {
            $q->whereIn('organizations.id', $this->meeting->organizations->pluck('id'));
        })
            ->orWhere('status', 'active')
            ->limit(5)
            ->get();

        if ($projects->count() > 0) {
            $context[] = "\nRelevant Projects:";
            foreach ($projects as $proj) {
                $context[] = "- {$proj->name}".($proj->description ? ': '.\Str::limit($proj->description, 100) : '');
            }
        }

        return implode("\n", $context);
    }

    public function applyPrepToMeeting()
    {
        $prepText = "# AI Meeting Prep\n\n";

        // Add attendee analysis
        if (! empty($this->prepAnalysis['attendee_analysis'])) {
            $prepText .= "## Who You're Meeting With\n";
            if (! empty($this->prepAnalysis['attendee_analysis']['key_people'])) {
                foreach ($this->prepAnalysis['attendee_analysis']['key_people'] as $person) {
                    $prepText .= "- {$person}\n";
                }
            }
            if (! empty($this->prepAnalysis['attendee_analysis']['organization_context'])) {
                $prepText .= "\n".$this->prepAnalysis['attendee_analysis']['organization_context']."\n";
            }
            $prepText .= "\n";
        }

        // Add suggested topics
        if (! empty($this->prepAnalysis['suggested_topics'])) {
            $prepText .= "## Suggested Topics\n";
            foreach ($this->prepAnalysis['suggested_topics'] as $topic) {
                $prepText .= "- {$topic}\n";
            }
            $prepText .= "\n";
        }

        // Add key questions
        if (! empty($this->prepAnalysis['key_questions'])) {
            $prepText .= "## Key Questions\n";
            foreach ($this->prepAnalysis['key_questions'] as $question) {
                $prepText .= "- {$question}\n";
            }
            $prepText .= "\n";
        }

        // Add potential asks
        if (! empty($this->prepAnalysis['potential_asks'])) {
            $prepText .= "## Potential Asks\n";
            foreach ($this->prepAnalysis['potential_asks'] as $ask) {
                $prepText .= "- {$ask}\n";
            }
            $prepText .= "\n";
        }

        // Add preparation notes
        if (! empty($this->prepAnalysis['preparation_notes'])) {
            $prepText .= "## Preparation Notes\n";
            $prepText .= $this->prepAnalysis['preparation_notes']."\n";
        }

        // Add relevant history
        if (! empty($this->prepAnalysis['relevant_history'])) {
            $prepText .= "\n## Relevant History\n";
            $prepText .= $this->prepAnalysis['relevant_history']."\n";
        }

        // Update prep_notes field (append to existing)
        $this->prep_notes = trim($prepText."\n\n".$this->prep_notes);

        // Also save the structured analysis for future reference
        $this->meeting->update([
            'prep_notes' => $this->prep_notes,
            'prep_analysis' => $this->prepAnalysis,
        ]);

        $this->closePrepModal();
        $this->dispatch('notify', type: 'success', message: 'AI prep saved to meeting!');
    }

    // === Agenda Management ===

    public function toggleAddAgendaItem(): void
    {
        $this->showAddAgendaItem = !$this->showAddAgendaItem;
        $this->resetAgendaForm();
    }

    public function resetAgendaForm(): void
    {
        $this->newAgendaTitle = '';
        $this->newAgendaDescription = '';
        $this->newAgendaDuration = null;
        $this->newAgendaPresenter = null;
        $this->editingAgendaItemId = null;
    }

    public function addAgendaItem(): void
    {
        $this->validate([
            'newAgendaTitle' => 'required|string|max:255',
            'newAgendaDescription' => 'nullable|string',
            'newAgendaDuration' => 'nullable|integer|min:1|max:480',
            'newAgendaPresenter' => 'nullable|exists:users,id',
        ]);

        $maxOrder = $this->meeting->agendaItems()->max('order') ?? 0;

        $this->meeting->agendaItems()->create([
            'title' => $this->newAgendaTitle,
            'description' => $this->newAgendaDescription ?: null,
            'duration_minutes' => $this->newAgendaDuration,
            'presenter_id' => $this->newAgendaPresenter,
            'order' => $maxOrder + 1,
        ]);

        $this->resetAgendaForm();
        $this->showAddAgendaItem = false;
        $this->meeting->refresh();
        $this->dispatch('notify', type: 'success', message: 'Agenda item added!');
    }

    public function updateAgendaItemStatus(int $itemId, string $status): void
    {
        $item = \App\Models\MeetingAgendaItem::find($itemId);
        if ($item && $item->meeting_id === $this->meeting->id) {
            $item->update(['status' => $status]);
            $this->meeting->refresh();
        }
    }

    public function updateAgendaItemNotes(int $itemId, string $notes): void
    {
        $item = \App\Models\MeetingAgendaItem::find($itemId);
        if ($item && $item->meeting_id === $this->meeting->id) {
            $item->update(['notes' => $notes ?: null]);
        }
    }

    public function updateAgendaItemDecisions(int $itemId, string $decisions): void
    {
        $item = \App\Models\MeetingAgendaItem::find($itemId);
        if ($item && $item->meeting_id === $this->meeting->id) {
            $item->update(['decisions' => $decisions ?: null]);
        }
    }

    public function deleteAgendaItem(int $itemId): void
    {
        $item = \App\Models\MeetingAgendaItem::find($itemId);
        if ($item && $item->meeting_id === $this->meeting->id) {
            $item->delete();
            $this->meeting->refresh();
            $this->dispatch('notify', type: 'success', message: 'Agenda item removed.');
        }
    }

    public function reorderAgendaItem(int $itemId, string $direction): void
    {
        $item = \App\Models\MeetingAgendaItem::find($itemId);
        if (!$item || $item->meeting_id !== $this->meeting->id) return;

        $items = $this->meeting->agendaItems()->orderBy('order')->get();
        $currentIndex = $items->search(fn ($i) => $i->id === $itemId);

        if ($direction === 'up' && $currentIndex > 0) {
            $swapItem = $items[$currentIndex - 1];
            $tempOrder = $item->order;
            $item->update(['order' => $swapItem->order]);
            $swapItem->update(['order' => $tempOrder]);
        } elseif ($direction === 'down' && $currentIndex < $items->count() - 1) {
            $swapItem = $items[$currentIndex + 1];
            $tempOrder = $item->order;
            $item->update(['order' => $swapItem->order]);
            $swapItem->update(['order' => $tempOrder]);
        }

        $this->meeting->refresh();
    }

    public function saveAgendaNotes(): void
    {
        $this->meeting->update(['agenda_notes' => $this->agendaNotes ?: null]);
        $this->dispatch('notify', type: 'success', message: 'Agenda notes saved!');
    }

    public function render()
    {
        // Get related meetings (same orgs or issues)
        $relatedMeetings = Meeting::where('id', '!=', $this->meeting->id)
            ->where(function ($query) {
                $orgIds = $this->meeting->organizations->pluck('id');
                $issueIds = $this->meeting->issues->pluck('id');

                $query->whereHas('organizations', function ($q) use ($orgIds) {
                    $q->whereIn('organizations.id', $orgIds);
                })->orWhereHas('issues', function ($q) use ($issueIds) {
                    $q->whereIn('issues.id', $issueIds);
                });
            })
            ->latest('meeting_date')
            ->take(5)
            ->get();

        return view('livewire.meetings.meeting-detail', [
            'relatedMeetings' => $relatedMeetings,
            'allOrganizations' => Organization::orderBy('name')->get(),
            'allPeople' => Person::orderBy('name')->get(),
            'allIssues' => Issue::orderBy('name')->get(),
            'teamMembers' => \App\Models\User::orderBy('name')->get(['id', 'name']),
            'agendaStatuses' => \App\Models\MeetingAgendaItem::STATUSES,
        ]);
    }
}
