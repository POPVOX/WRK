<?php

namespace App\Livewire\Projects;

use App\Jobs\FetchLinkContent;
use App\Jobs\IndexDocumentContent;
use App\Jobs\RunStyleCheck;
use App\Jobs\SendChatMessage;
use App\Jobs\SuggestDocumentTags;
use App\Models\BoxItem;
use App\Models\BoxProjectDocumentLink;
use App\Models\Issue;
use App\Models\Meeting;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Project;
use App\Models\ProjectChatMessage;
use App\Models\ProjectDecision;
use App\Models\ProjectDocument;
use App\Models\ProjectMilestone;
use App\Models\ProjectNote;
use App\Models\ProjectQuestion;
use App\Models\User;
use App\Services\Box\BoxProjectDocumentService;
use App\Services\ChatService;
use App\Services\DocumentSafety;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ProjectShow extends Component
{
    use WithFileUploads;

    public Project $project;

    public string $activeTab = 'overview';

    // Editing
    public bool $editing = false;

    public string $name = '';

    public string $description = '';

    public string $goals = '';

    public ?string $start_date = null;

    public ?string $target_end_date = null;

    public string $status = 'active';

    // Decision form
    public bool $showDecisionForm = false;

    public string $decisionTitle = '';

    public string $decisionDescription = '';

    public string $decisionRationale = '';

    public string $decisionContext = '';

    public ?string $decisionDate = null;

    public string $decisionDecidedBy = '';

    // Milestone form
    public bool $showMilestoneForm = false;

    public string $milestoneTitle = '';

    public string $milestoneDescription = '';

    public ?string $milestoneTargetDate = null;

    public ?int $editingMilestoneId = null;

    // Question form
    public bool $showQuestionForm = false;

    public string $questionText = '';

    public string $questionContext = '';

    // Answer form
    public ?int $answeringQuestionId = null;

    public string $answerText = '';

    // Staff management
    public bool $showAddStaffModal = false;

    public string $staffSearch = '';

    public ?int $selectedStaffId = null;

    public string $staffRole = 'contributor';

    // Documents
    public bool $showDocumentForm = false;

    public string $documentType = 'link';

    public string $documentTitle = '';

    public string $documentDescription = '';

    public string $documentUrl = '';

    public $documentFile = null;

    public bool $showBoxLinkForm = false;

    public string $boxItemSearch = '';

    public string $boxLinkVisibility = 'all';

    // Notes
    public bool $showNoteForm = false;

    public string $noteContent = '';

    public string $noteType = 'general';

    // Project Chat (enhanced from workspace)
    public string $projectChatQuery = '';

    public array $projectChatHistory = [];

    public bool $isChatProcessing = false;

    // Document Viewer (from workspace)
    public bool $showDocumentViewer = false;

    public ?int $viewingDocumentId = null;

    public string $documentContent = '';

    public string $viewingDocumentTitle = '';

    // Style Check (from workspace)
    public bool $isStyleChecking = false;

    public array $styleCheckSuggestions = [];

    public bool $styleCheckComplete = false;

    // Upload / Link (from workspace)
    public $uploadFile = null;

    public string $uploadTitle = '';

    public string $linkTitle = '';

    public string $linkUrl = '';

    // Geographic tags
    public array $selectedRegions = [];

    public array $selectedCountries = [];

    public array $selectedUsStates = [];

    // Sync Preview (from workspace)
    public bool $showSyncPreviewModal = false;

    public array $syncPreview = ['add' => [], 'update' => [], 'missing' => []];

    // Tags editing (from workspace)
    public array $tagsEdit = [];

    public array $commonTags = [];

    // AI flags
    public bool $aiEnabled = true;

    public ?string $aiNotice = null;

    public ?string $styleNotice = null;

    protected $queryString = ['activeTab'];

    public function mount(Project $project)
    {
        $this->project = $project->load([
            'meetings.organizations',
            'organizations',
            'people.organization',
            'issues',
            'decisions.meeting',
            'milestones',
            'questions',
            'createdBy',
            'staff',
            'documents.uploadedBy',
            'boxDocumentLinks.boxItem',
            'boxDocumentLinks.projectDocument',
            'notes.user',
            'children',
            'parent',
            'publications',
            'events',
        ]);
        $this->loadProjectData();
        $this->aiEnabled = (bool) config('ai.enabled');
        $this->loadChatHistory();

        // Open document viewer from deep link
        $docToOpen = (int) request()->query('doc', 0);
        if ($docToOpen > 0) {
            try {
                $this->viewDocument($docToOpen);
            } catch (\Throwable $e) {
                // ignore invalid ids
            }
        }
    }

    public function loadProjectData()
    {
        $this->name = $this->project->name;
        $this->description = $this->project->description ?? '';
        $this->goals = $this->project->goals ?? '';
        $this->start_date = $this->project->start_date?->format('Y-m-d');
        $this->target_end_date = $this->project->target_end_date?->format('Y-m-d');
        $this->status = $this->project->status;

        // Load geographic tags
        $this->selectedRegions = $this->project->geographicTags()->where('geographic_type', 'region')->pluck('geographic_id')->toArray();
        $this->selectedCountries = $this->project->geographicTags()->where('geographic_type', 'country')->pluck('geographic_id')->toArray();
        $this->selectedUsStates = $this->project->geographicTags()->where('geographic_type', 'us_state')->pluck('geographic_id')->toArray();
    }

    #[On('geographic-tags-updated')]
    public function updateGeographicTags(array $data): void
    {
        $this->selectedRegions = $data['regions'] ?? [];
        $this->selectedCountries = $data['countries'] ?? [];
        $this->selectedUsStates = $data['usStates'] ?? [];
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    // --- Project editing ---
    public function startEditing()
    {
        $this->editing = true;
    }

    public function cancelEditing()
    {
        $this->editing = false;
        $this->loadProjectData();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goals' => 'nullable|string',
            'start_date' => 'nullable|date',
            'target_end_date' => 'nullable|date',
            'status' => 'required|in:active,on_hold,completed,archived',
        ]);

        $this->project->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'goals' => $this->goals ?: null,
            'start_date' => $this->start_date ?: null,
            'target_end_date' => $this->target_end_date ?: null,
            'status' => $this->status,
        ]);

        // Sync geographic tags
        $this->project->syncGeographicTags(
            $this->selectedRegions,
            $this->selectedCountries,
            $this->selectedUsStates
        );

        $this->project->refresh();
        $this->editing = false;
        $this->dispatch('notify', type: 'success', message: 'Project updated successfully!');
    }

    // --- Decisions ---
    public function toggleDecisionForm()
    {
        $this->showDecisionForm = ! $this->showDecisionForm;
        $this->resetDecisionForm();
    }

    public function resetDecisionForm()
    {
        $this->decisionTitle = '';
        $this->decisionDescription = '';
        $this->decisionRationale = '';
        $this->decisionContext = '';
        $this->decisionDate = null;
        $this->decisionDecidedBy = '';
    }

    public function addDecision()
    {
        $this->validate([
            'decisionTitle' => 'required|string|max:255',
            'decisionDescription' => 'required|string',
        ]);

        $this->project->decisions()->create([
            'title' => $this->decisionTitle,
            'description' => $this->decisionDescription,
            'rationale' => $this->decisionRationale ?: null,
            'context' => $this->decisionContext ?: null,
            'decision_date' => $this->decisionDate ?: null,
            'decided_by' => $this->decisionDecidedBy ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->resetDecisionForm();
        $this->showDecisionForm = false;
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Decision added!');
    }

    public function deleteDecision(int $decisionId)
    {
        ProjectDecision::where('id', $decisionId)->where('project_id', $this->project->id)->delete();
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Decision deleted.');
    }

    // --- Milestones ---
    public function toggleMilestoneForm()
    {
        $this->showMilestoneForm = ! $this->showMilestoneForm;
        $this->resetMilestoneForm();
    }

    public function resetMilestoneForm()
    {
        $this->milestoneTitle = '';
        $this->milestoneDescription = '';
        $this->milestoneTargetDate = null;
        $this->editingMilestoneId = null;
    }

    public function addMilestone()
    {
        $this->validate([
            'milestoneTitle' => 'required|string|max:255',
        ]);

        $maxOrder = $this->project->milestones()->max('sort_order') ?? 0;

        $this->project->milestones()->create([
            'title' => $this->milestoneTitle,
            'description' => $this->milestoneDescription ?: null,
            'due_date' => $this->milestoneTargetDate ?: null,
            'status' => 'pending',
            'sort_order' => $maxOrder + 1,
        ]);

        $this->resetMilestoneForm();
        $this->showMilestoneForm = false;
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Milestone added!');
    }

    public function completeMilestone(int $milestoneId)
    {
        $milestone = ProjectMilestone::where('id', $milestoneId)->where('project_id', $this->project->id)->first();
        if ($milestone) {
            $milestone->markComplete();
            $this->project->refresh();
            $this->dispatch('notify', type: 'success', message: 'Milestone completed!');
        }
    }

    public function uncompleteMilestone(int $milestoneId)
    {
        $milestone = ProjectMilestone::where('id', $milestoneId)->where('project_id', $this->project->id)->first();
        if ($milestone) {
            $milestone->markIncomplete();
            $this->project->refresh();
            $this->dispatch('notify', type: 'success', message: 'Milestone reopened.');
        }
    }

    public function deleteMilestone(int $milestoneId)
    {
        ProjectMilestone::where('id', $milestoneId)->where('project_id', $this->project->id)->delete();
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Milestone deleted.');
    }

    public function startEditMilestone(int $milestoneId)
    {
        $milestone = ProjectMilestone::where('id', $milestoneId)->where('project_id', $this->project->id)->first();
        if ($milestone) {
            $this->editingMilestoneId = $milestoneId;
            $this->milestoneTitle = $milestone->title;
            $this->milestoneDescription = $milestone->description ?? '';
            $this->milestoneTargetDate = $milestone->due_date?->format('Y-m-d');
            $this->showMilestoneForm = true;
        }
    }

    public function updateMilestone()
    {
        $this->validate([
            'milestoneTitle' => 'required|string|max:255',
        ]);

        $milestone = ProjectMilestone::where('id', $this->editingMilestoneId)
            ->where('project_id', $this->project->id)
            ->first();

        if ($milestone) {
            $milestone->update([
                'title' => $this->milestoneTitle,
                'description' => $this->milestoneDescription ?: null,
                'due_date' => $this->milestoneTargetDate ?: null,
            ]);
        }

        $this->resetMilestoneForm();
        $this->showMilestoneForm = false;
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Milestone updated!');
    }

    // --- Questions ---
    public function toggleQuestionForm()
    {
        $this->showQuestionForm = ! $this->showQuestionForm;
        $this->resetQuestionForm();
    }

    public function resetQuestionForm()
    {
        $this->questionText = '';
        $this->questionContext = '';
    }

    public function addQuestion()
    {
        $this->validate([
            'questionText' => 'required|string',
        ]);

        $this->project->questions()->create([
            'question' => $this->questionText,
            'context' => $this->questionContext ?: null,
            'status' => 'open',
            'raised_by' => auth()->id(),
        ]);

        $this->resetQuestionForm();
        $this->showQuestionForm = false;
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Question added!');
    }

    public function startAnswering(int $questionId)
    {
        $this->answeringQuestionId = $questionId;
        $this->answerText = '';
    }

    public function cancelAnswering()
    {
        $this->answeringQuestionId = null;
        $this->answerText = '';
    }

    public function submitAnswer()
    {
        $this->validate([
            'answerText' => 'required|string',
        ]);

        ProjectQuestion::where('id', $this->answeringQuestionId)
            ->where('project_id', $this->project->id)
            ->update([
                'answer' => $this->answerText,
                'status' => 'answered',
                'answered_date' => now(),
            ]);

        $this->answeringQuestionId = null;
        $this->answerText = '';
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Question answered!');
    }

    public function deleteQuestion(int $questionId)
    {
        ProjectQuestion::where('id', $questionId)->where('project_id', $this->project->id)->delete();
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Question deleted.');
    }

    // --- Organization Linking ---
    public bool $showAddOrgModal = false;

    public string $orgSearch = '';

    public ?int $selectedOrgId = null;

    public string $orgRole = '';

    public string $orgNotes = '';

    public function toggleAddOrgModal()
    {
        $this->showAddOrgModal = ! $this->showAddOrgModal;
        $this->resetOrgForm();
    }

    public function resetOrgForm()
    {
        $this->orgSearch = '';
        $this->selectedOrgId = null;
        $this->orgRole = '';
        $this->orgNotes = '';
    }

    public function selectOrg(int $orgId)
    {
        $this->selectedOrgId = $orgId;
        $org = Organization::find($orgId);
        $this->orgSearch = $org ? $org->name : '';
    }

    public function linkOrganization()
    {
        if (! $this->selectedOrgId) {
            return;
        }

        // Check if already linked
        if ($this->project->organizations()->where('organization_id', $this->selectedOrgId)->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Organization already linked.');

            return;
        }

        $this->project->organizations()->attach($this->selectedOrgId, [
            'role' => $this->orgRole ?: null,
            'notes' => $this->orgNotes ?: null,
        ]);

        $this->resetOrgForm();
        $this->showAddOrgModal = false;
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Organization linked!');
    }

    public function unlinkOrganization(int $orgId)
    {
        $this->project->organizations()->detach($orgId);
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Organization removed.');
    }

    // --- Person Linking ---
    public bool $showAddPersonModal = false;

    public string $personSearch = '';

    public ?int $selectedPersonId = null;

    public string $personRole = '';

    public string $personNotes = '';

    public function toggleAddPersonModal()
    {
        $this->showAddPersonModal = ! $this->showAddPersonModal;
        $this->resetPersonForm();
    }

    public function resetPersonForm()
    {
        $this->personSearch = '';
        $this->selectedPersonId = null;
        $this->personRole = '';
        $this->personNotes = '';
    }

    public function selectPerson(int $personId)
    {
        $this->selectedPersonId = $personId;
        $person = Person::find($personId);
        $this->personSearch = $person ? $person->name : '';
    }

    public function linkPerson()
    {
        if (! $this->selectedPersonId) {
            return;
        }

        if ($this->project->people()->where('person_id', $this->selectedPersonId)->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Person already linked.');

            return;
        }

        $this->project->people()->attach($this->selectedPersonId, [
            'role' => $this->personRole ?: null,
            'notes' => $this->personNotes ?: null,
        ]);

        $this->resetPersonForm();
        $this->showAddPersonModal = false;
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Person linked!');
    }

    public function unlinkPerson(int $personId)
    {
        $this->project->people()->detach($personId);
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Person removed.');
    }

    // --- Issue Linking ---
    public bool $showAddIssueModal = false;

    public string $issueSearch = '';

    public ?int $selectedIssueId = null;

    public function toggleAddIssueModal()
    {
        $this->showAddIssueModal = ! $this->showAddIssueModal;
        $this->issueSearch = '';
        $this->selectedIssueId = null;
    }

    public function selectIssue(int $issueId)
    {
        $this->selectedIssueId = $issueId;
        $issue = Issue::find($issueId);
        $this->issueSearch = $issue ? $issue->name : '';
    }

    public function linkIssue()
    {
        if (! $this->selectedIssueId) {
            return;
        }

        if ($this->project->issues()->where('issue_id', $this->selectedIssueId)->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Issue already linked.');

            return;
        }

        $this->project->issues()->attach($this->selectedIssueId);

        $this->toggleAddIssueModal();
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Issue linked!');
    }

    public function unlinkIssue(int $issueId)
    {
        $this->project->issues()->detach($issueId);
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Issue removed.');
    }

    // --- Meeting Linking ---
    public bool $showAddMeetingModal = false;

    public string $meetingSearch = '';

    public ?int $selectedMeetingId = null;

    public string $meetingRelevance = '';

    public function toggleAddMeetingModal()
    {
        $this->showAddMeetingModal = ! $this->showAddMeetingModal;
        $this->meetingSearch = '';
        $this->selectedMeetingId = null;
        $this->meetingRelevance = '';
    }

    public function selectMeeting(int $meetingId)
    {
        $this->selectedMeetingId = $meetingId;
        $meeting = Meeting::find($meetingId);
        $this->meetingSearch = $meeting ? ($meeting->title ?: $meeting->meeting_date->format('M j, Y')) : '';
    }

    public function linkMeeting()
    {
        if (! $this->selectedMeetingId) {
            return;
        }

        if ($this->project->meetings()->where('meeting_id', $this->selectedMeetingId)->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Meeting already linked.');

            return;
        }

        $this->project->meetings()->attach($this->selectedMeetingId, [
            'relevance_note' => $this->meetingRelevance ?: null,
        ]);

        $this->toggleAddMeetingModal();
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Meeting linked!');
    }

    public function unlinkMeeting(int $meetingId)
    {
        $this->project->meetings()->detach($meetingId);
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Meeting removed.');
    }

    // --- Staff Management ---
    public function toggleAddStaffModal()
    {
        $this->showAddStaffModal = ! $this->showAddStaffModal;
        $this->staffSearch = '';
        $this->selectedStaffId = null;
        $this->staffRole = 'contributor';
    }

    public function selectStaff(int $userId)
    {
        $this->selectedStaffId = $userId;
        $user = User::find($userId);
        $this->staffSearch = $user?->name ?? '';
    }

    public function addStaff()
    {
        if (! $this->selectedStaffId) {
            return;
        }

        if ($this->project->staff()->where('user_id', $this->selectedStaffId)->exists()) {
            $this->dispatch('notify', type: 'error', message: 'This person is already on the team.');

            return;
        }

        $this->project->staff()->attach($this->selectedStaffId, [
            'role' => $this->staffRole,
            'added_at' => now(),
        ]);

        $this->project->refresh();
        $this->toggleAddStaffModal();
        $this->dispatch('notify', type: 'success', message: 'Team member added!');
    }

    public function updateStaffRole(int $userId, string $role)
    {
        $this->project->staff()->updateExistingPivot($userId, ['role' => $role]);
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Role updated.');
    }

    public function removeStaff(int $userId)
    {
        $this->project->staff()->detach($userId);
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Team member removed.');
    }

    // --- Documents ---
    public function toggleDocumentForm()
    {
        $this->showDocumentForm = ! $this->showDocumentForm;
        $this->resetDocumentForm();
    }

    public function resetDocumentForm()
    {
        $this->documentType = 'link';
        $this->documentTitle = '';
        $this->documentDescription = '';
        $this->documentUrl = '';
        $this->documentFile = null;
    }

    public function toggleBoxLinkForm(): void
    {
        $this->showBoxLinkForm = ! $this->showBoxLinkForm;

        if (! $this->showBoxLinkForm) {
            $this->boxItemSearch = '';
            $this->boxLinkVisibility = 'all';
        }
    }

    public function linkExistingBoxItem(int $boxItemId): void
    {
        $boxItem = BoxItem::query()
            ->files()
            ->whereNull('trashed_at')
            ->find($boxItemId);

        if (! $boxItem) {
            $this->dispatch('notify', type: 'error', message: 'Box file not found or no longer available.');

            return;
        }

        try {
            $service = app(BoxProjectDocumentService::class);
            $link = $service->linkItemToProject($boxItem, $this->project, Auth::id(), $this->boxLinkVisibility);
            $service->syncLink($link);

            $this->project->refresh();
            $this->project->load(['documents.uploadedBy', 'boxDocumentLinks.boxItem', 'boxDocumentLinks.projectDocument']);
            $this->boxItemSearch = '';
            $this->showBoxLinkForm = false;

            $this->dispatch('notify', type: 'success', message: 'Box file linked and synced.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: 'Box link failed: '.$exception->getMessage());
        }
    }

    public function syncBoxLink(int $linkId): void
    {
        $link = BoxProjectDocumentLink::query()
            ->where('project_id', $this->project->id)
            ->find($linkId);

        if (! $link) {
            $this->dispatch('notify', type: 'error', message: 'Box link not found for this project.');

            return;
        }

        try {
            app(BoxProjectDocumentService::class)->syncLink($link);

            $this->project->refresh();
            $this->project->load(['documents.uploadedBy', 'boxDocumentLinks.boxItem', 'boxDocumentLinks.projectDocument']);
            $this->dispatch('notify', type: 'success', message: 'Box link synced.');
        } catch (\Throwable $exception) {
            $this->dispatch('notify', type: 'error', message: 'Sync failed: '.$exception->getMessage());
        }
    }

    public function unlinkBoxLink(int $linkId): void
    {
        $link = BoxProjectDocumentLink::query()
            ->with('projectDocument')
            ->where('project_id', $this->project->id)
            ->find($linkId);

        if (! $link) {
            $this->dispatch('notify', type: 'error', message: 'Box link not found for this project.');

            return;
        }

        DB::transaction(function () use ($link) {
            $linkedDocument = $link->projectDocument;
            $link->delete();

            if (
                $linkedDocument &&
                $linkedDocument->project_id === $this->project->id &&
                $linkedDocument->type === 'link'
            ) {
                $linkedDocument->delete();
            }
        });

        $this->project->refresh();
        $this->project->load(['documents.uploadedBy', 'boxDocumentLinks.boxItem', 'boxDocumentLinks.projectDocument']);
        $this->dispatch('notify', type: 'success', message: 'Box link removed.');
    }

    public function addDocument()
    {
        $rules = [
            'documentTitle' => 'required|string|max:255',
            'documentDescription' => 'nullable|string',
            'documentType' => 'required|in:file,link',
        ];

        if ($this->documentType === 'link') {
            $rules['documentUrl'] = 'required|url';
        } else {
            $rules['documentFile'] = 'required|file|max:10240'; // 10MB max
        }

        $this->validate($rules);

        $data = [
            'project_id' => $this->project->id,
            'title' => $this->documentTitle,
            'description' => $this->documentDescription ?: null,
            'type' => $this->documentType,
            'uploaded_by' => auth()->id(),
        ];

        if ($this->documentType === 'link') {
            $data['url'] = $this->documentUrl;
        } else {
            $path = $this->documentFile->store('project-documents', 'public');
            $data['file_path'] = $path;
            $data['mime_type'] = $this->documentFile->getMimeType();
            $data['file_size'] = $this->documentFile->getSize();
        }

        ProjectDocument::create($data);

        $this->project->refresh();
        $this->toggleDocumentForm();
        $this->dispatch('notify', type: 'success', message: 'Document added!');
    }

    public function deleteDocument(int $documentId)
    {
        $document = ProjectDocument::findOrFail($documentId);

        BoxProjectDocumentLink::query()
            ->where('project_id', $this->project->id)
            ->where('project_document_id', $document->id)
            ->delete();

        if ($document->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Document deleted.');
    }

    // --- Notes ---
    public function toggleNoteForm()
    {
        $this->showNoteForm = ! $this->showNoteForm;
        $this->noteContent = '';
        $this->noteType = 'general';
    }

    public function addNote()
    {
        $this->validate([
            'noteContent' => 'required|string',
            'noteType' => 'required|in:update,decision,blocker,general',
        ]);

        ProjectNote::create([
            'project_id' => $this->project->id,
            'user_id' => auth()->id(),
            'content' => $this->noteContent,
            'note_type' => $this->noteType,
        ]);

        $this->project->refresh();
        $this->toggleNoteForm();
        $this->dispatch('notify', type: 'success', message: 'Note added!');
    }

    public function togglePinNote(int $noteId)
    {
        $note = ProjectNote::findOrFail($noteId);
        $note->update(['is_pinned' => ! $note->is_pinned]);
        $this->project->refresh();
    }

    public function deleteNote(int $noteId)
    {
        ProjectNote::findOrFail($noteId)->delete();
        $this->project->refresh();
        $this->dispatch('notify', type: 'success', message: 'Note deleted.');
    }

    // --- Project Chat ---
    public function sendProjectChat()
    {
        if (empty(trim($this->projectChatQuery))) {
            return;
        }

        if (! config('ai.enabled')) {
            $this->projectChatHistory[] = [
                'role' => 'assistant',
                'content' => 'AI features are disabled by the administrator.',
                'timestamp' => now()->format('g:i A'),
            ];

            return;
        }

        $chatLimit = config('ai.limits.chat', ['max' => 30, 'decay_seconds' => 60]);
        $chatKey = 'ai-project-chat:'.Auth::id().':'.$this->project->id;
        if (RateLimiter::tooManyAttempts($chatKey, $chatLimit['max'])) {
            $this->projectChatHistory[] = [
                'role' => 'assistant',
                'content' => 'You are sending messages too quickly. Please wait a moment.',
                'timestamp' => now()->format('g:i A'),
            ];

            return;
        }
        RateLimiter::hit($chatKey, $chatLimit['decay_seconds']);

        $query = $this->projectChatQuery;
        $this->projectChatQuery = '';
        $this->isChatProcessing = true;

        $this->projectChatHistory[] = [
            'role' => 'user',
            'content' => $query,
            'timestamp' => now()->format('g:i A'),
        ];

        $this->dispatch('chatUpdated');

        try {
            $chatService = app(ChatService::class);
            $response = $chatService->askAboutProject($this->project, $query);

            $this->projectChatHistory[] = [
                'role' => 'assistant',
                'content' => $response,
                'timestamp' => now()->format('g:i A'),
            ];
        } catch (\Exception $e) {
            $this->projectChatHistory[] = [
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error processing your question.',
                'timestamp' => now()->format('g:i A'),
            ];
        }

        $this->isChatProcessing = false;
        $this->dispatch('chatUpdated');
    }

    public function clearProjectChat()
    {
        $this->projectChatHistory = [];
    }

    // ========================================
    // WORKSPACE METHODS (merged from ProjectWorkspace)
    // ========================================

    // --- AI Chat Methods ---
    public function loadChatHistory(): void
    {
        $messages = $this->project->chatMessages()
            ->where('user_id', Auth::id())
            ->orderBy('created_at')
            ->get();

        $this->projectChatHistory = $messages->map(fn ($m) => [
            'role' => $m->role,
            'content' => $m->content,
            'timestamp' => $m->created_at->format('g:i A'),
        ])->toArray();
    }

    public function sendEnhancedChat(): void
    {
        if (empty(trim($this->projectChatQuery))) {
            return;
        }

        if (! config('ai.enabled')) {
            $this->aiNotice = 'AI features are disabled by the administrator.';

            return;
        }

        $chatLimit = config('ai.limits.chat', ['max' => 30, 'decay_seconds' => 60]);
        $chatKey = 'ai-chat:'.Auth::id().':'.$this->project->id;
        if (RateLimiter::tooManyAttempts($chatKey, $chatLimit['max'])) {
            $this->aiNotice = 'You are sending messages too quickly. Please wait a moment.';

            return;
        }
        RateLimiter::hit($chatKey, $chatLimit['decay_seconds']);

        $this->isChatProcessing = true;
        $query = $this->projectChatQuery;
        $this->projectChatQuery = '';

        ProjectChatMessage::create([
            'project_id' => $this->project->id,
            'user_id' => Auth::id(),
            'role' => 'user',
            'content' => $query,
        ]);

        $this->projectChatHistory[] = [
            'role' => 'user',
            'content' => $query,
            'timestamp' => now()->format('g:i A'),
        ];

        SendChatMessage::dispatch($this->project->id, Auth::id(), $query, $this->getChatSystemPrompt());

        $this->isChatProcessing = false;
        $this->dispatch('chatUpdated');
        $this->dispatch('chatStarted');
    }

    protected function getChatSystemPrompt(): string
    {
        $context = $this->buildChatContext();

        return <<<PROMPT
You are an AI collaborator helping with the project "{$this->project->name}".

## Your Role
You are a knowledgeable assistant helping plan, organize, and execute this project. You have access to all project context.

## Project Context
{$context}

## Guidelines
- Be helpful, concise, and actionable
- Reference specific documents or sections when relevant
- Suggest next steps when appropriate
- Help with planning, content drafting, research, and analysis
PROMPT;
    }

    protected function buildChatContext(): string
    {
        $context = [];
        $context[] = "**Project Name:** {$this->project->name}";
        $context[] = '**Description:** '.($this->project->description ?? 'No description');
        $context[] = "**Status:** {$this->project->status}";

        if ($this->project->project_path) {
            $projectDir = base_path($this->project->project_path);
            $readmePath = $projectDir.'/README.md';
            if (file_exists($readmePath)) {
                $context[] = "\n## Project README\n".file_get_contents($readmePath);
            }
        }

        return implode("\n", $context);
    }

    public function refreshChatHistory(): void
    {
        $this->loadChatHistory();
        $this->dispatch('chatUpdated');
    }

    // --- Document Viewer Methods ---
    public function viewDocument(int $documentId): void
    {
        $document = ProjectDocument::find($documentId);
        if (! $document || $document->project_id !== $this->project->id) {
            return;
        }

        $fullPath = base_path($document->file_path);
        if (! file_exists($fullPath)) {
            return;
        }

        $this->viewingDocumentId = $documentId;
        $this->viewingDocumentTitle = $document->title;
        $this->documentContent = file_get_contents($fullPath);
        $this->showDocumentViewer = true;
        $this->styleCheckSuggestions = [];
        $this->styleCheckComplete = false;
    }

    public function closeDocumentViewer(): void
    {
        $this->showDocumentViewer = false;
        $this->viewingDocumentId = null;
        $this->documentContent = '';
        $this->viewingDocumentTitle = '';
        $this->styleCheckSuggestions = [];
        $this->styleCheckComplete = false;
    }

    // --- Style Check Methods ---
    public function runStyleCheckQueued(): void
    {
        if (! $this->viewingDocumentId) {
            return;
        }

        if (! config('ai.enabled')) {
            $this->styleNotice = 'AI features are disabled.';

            return;
        }

        $limit = config('ai.limits.style_check', ['max' => 10, 'decay_seconds' => 300]);
        $key = 'ai-style:'.Auth::id().':'.$this->project->id;
        if (RateLimiter::tooManyAttempts($key, $limit['max'])) {
            $this->styleNotice = 'Too many style checks. Please try again in a few minutes.';

            return;
        }
        RateLimiter::hit($key, $limit['decay_seconds']);

        $document = ProjectDocument::find($this->viewingDocumentId);
        if (! $document || $document->project_id !== $this->project->id) {
            return;
        }

        $fullPath = base_path($document->file_path);
        if (! file_exists($fullPath) || ! DocumentSafety::withinBase(base_path(), $fullPath)) {
            return;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (! in_array($ext, ['md', 'markdown', 'txt'], true)) {
            return;
        }

        $content = file_get_contents($fullPath) ?: '';
        $this->documentContent = $content;
        $this->viewingDocumentTitle = $document->title ?? basename($fullPath);

        $hash = DocumentSafety::hashContent($content);
        $cachePath = "style_checks/{$document->id}-{$hash}.json";

        if (Storage::disk('local')->exists($cachePath)) {
            $payload = json_decode(Storage::disk('local')->get($cachePath), true) ?: [];
            $this->styleCheckSuggestions = array_map(function ($s) {
                $s['status'] = $s['status'] ?? 'pending';

                return $s;
            }, $payload['suggestions'] ?? []);
            $this->styleCheckComplete = true;
            $this->isStyleChecking = false;
        } else {
            $this->isStyleChecking = true;
            $this->styleCheckComplete = false;
            $this->styleCheckSuggestions = [];
            RunStyleCheck::dispatch($this->project->id, $document->id, $document->file_path, $content);
            $this->dispatch('styleCheckStarted');
        }
    }

    public function checkStyleCheckStatus(): void
    {
        if (! $this->viewingDocumentId || empty($this->documentContent)) {
            return;
        }
        $document = ProjectDocument::find($this->viewingDocumentId);
        if (! $document || $document->project_id !== $this->project->id) {
            return;
        }

        $hash = DocumentSafety::hashContent($this->documentContent);
        $cachePath = "style_checks/{$document->id}-{$hash}.json";

        if (Storage::disk('local')->exists($cachePath)) {
            $payload = json_decode(Storage::disk('local')->get($cachePath), true) ?: [];
            $this->styleCheckSuggestions = array_map(function ($s) {
                $s['status'] = $s['status'] ?? 'pending';

                return $s;
            }, $payload['suggestions'] ?? []);
            $this->styleCheckComplete = true;
            $this->isStyleChecking = false;
            $this->dispatch('styleCheckCompleted');
        }
    }

    public function acceptSuggestion(int $index): void
    {
        if (isset($this->styleCheckSuggestions[$index])) {
            $this->styleCheckSuggestions[$index]['status'] = 'accepted';
        }
    }

    public function rejectSuggestion(int $index): void
    {
        if (isset($this->styleCheckSuggestions[$index])) {
            $this->styleCheckSuggestions[$index]['status'] = 'rejected';
        }
    }

    public function applyAcceptedSuggestions(): void
    {
        if (! $this->viewingDocumentId) {
            return;
        }

        $document = ProjectDocument::find($this->viewingDocumentId);
        if (! $document) {
            return;
        }

        $fullPath = base_path($document->file_path);
        if (! file_exists($fullPath)) {
            return;
        }

        $content = $this->documentContent;
        foreach ($this->styleCheckSuggestions as $suggestion) {
            if ($suggestion['status'] === 'accepted' && ! empty($suggestion['original'])) {
                $content = str_replace($suggestion['original'], $suggestion['replacement'], $content);
            }
        }

        file_put_contents($fullPath, $content);
        $this->documentContent = $content;
        $this->styleCheckSuggestions = array_values(array_filter($this->styleCheckSuggestions, fn ($s) => $s['status'] !== 'accepted'));
    }

    // --- Document Upload Methods ---
    public function uploadDocument(): void
    {
        $this->validate([
            'uploadFile' => 'required|file|max:20480',
            'uploadTitle' => 'nullable|string|max:255',
        ]);

        $ext = strtolower($this->uploadFile->getClientOriginalExtension());
        if (! DocumentSafety::isAllowedExtension($ext)) {
            $this->aiNotice = 'File type not allowed.';

            return;
        }

        $baseName = $this->uploadTitle !== ''
            ? \Illuminate\Support\Str::slug($this->uploadTitle)
            : pathinfo($this->uploadFile->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = \Illuminate\Support\Str::limit(preg_replace('/[^A-Za-z0-9\-_]+/', '-', $baseName), 120, '').'.'.$ext;
        $dir = 'project_uploads/'.$this->project->id;
        $path = $this->uploadFile->storeAs($dir, $fileName, 'public');

        $fullPath = Storage::disk('public')->path($path);
        $hash = DocumentSafety::hashFile($fullPath);
        $size = @filesize($fullPath) ?: null;
        $mime = @mime_content_type($fullPath) ?: null;

        $doc = ProjectDocument::create([
            'project_id' => $this->project->id,
            'title' => $this->uploadTitle !== '' ? $this->uploadTitle : $fileName,
            'type' => 'file',
            'file_path' => $path,
            'file_type' => $ext,
            'mime_type' => $mime,
            'file_size' => $size,
            'uploaded_by' => Auth::id(),
            'ai_indexed' => false,
            'content_hash' => $hash,
            'last_seen_at' => now(),
            'is_knowledge_base' => true,
        ]);

        if (in_array($ext, ['md', 'markdown', 'txt'], true)) {
            IndexDocumentContent::dispatch($doc->id);
            if (config('ai.enabled')) {
                SuggestDocumentTags::dispatch($doc->id);
            }
        }

        $this->uploadFile = null;
        $this->uploadTitle = '';
        $this->project->refresh();
        $this->project->load('documents');
    }

    public function addDocumentLink(): void
    {
        $this->validate([
            'linkTitle' => 'required|string|min:3|max:255',
            'linkUrl' => 'required|url|max:2048',
        ]);

        $doc = ProjectDocument::create([
            'project_id' => $this->project->id,
            'title' => $this->linkTitle,
            'type' => 'link',
            'url' => $this->linkUrl,
            'uploaded_by' => Auth::id(),
            'ai_indexed' => false,
            'is_knowledge_base' => true,
        ]);

        FetchLinkContent::dispatch($doc->id);

        $this->linkTitle = '';
        $this->linkUrl = '';
        $this->project->refresh();
        $this->project->load('documents');
    }

    // --- Document Sync Methods ---
    public function previewSyncDocumentsFromFolder(): void
    {
        if (! $this->project->project_path) {
            return;
        }

        $projectDir = base_path($this->project->project_path);
        if (! is_dir($projectDir)) {
            return;
        }

        $files = $this->scanDirectory($projectDir, DocumentSafety::allowedExtensions());
        $onDisk = [];
        foreach ($files as $file) {
            $relativePath = str_replace(base_path().'/', '', $file);
            $onDisk[$relativePath] = [
                'size' => @filesize($file) ?: null,
                'hash' => DocumentSafety::hashFile($file),
                'title' => basename($file),
                'ext' => strtolower(pathinfo($file, PATHINFO_EXTENSION)),
            ];
        }

        $existingDocs = $this->project->documents->keyBy('file_path');

        $add = [];
        $update = [];
        $missing = [];

        foreach ($onDisk as $path => $meta) {
            if (! $existingDocs->has($path)) {
                $add[] = ['file_path' => $path, 'title' => $meta['title'], 'file_type' => $meta['ext'], 'file_size' => $meta['size']];
            } else {
                $doc = $existingDocs[$path];
                $changed = ($doc->content_hash ?? null) !== $meta['hash'] || ($doc->file_size ?? null) !== $meta['size'];
                if ($changed) {
                    $update[] = ['file_path' => $path, 'title' => $doc->title, 'from_size' => $doc->file_size, 'to_size' => $meta['size']];
                }
            }
        }

        foreach ($existingDocs as $doc) {
            if (! isset($onDisk[$doc->file_path])) {
                $missing[] = ['file_path' => $doc->file_path, 'title' => $doc->title, 'file_size' => $doc->file_size];
            }
        }

        $this->syncPreview = compact('add', 'update', 'missing');
        $this->showSyncPreviewModal = true;
    }

    public function applySyncDocumentsFromFolder(): void
    {
        $this->syncDocumentsFromFolder();
        $this->showSyncPreviewModal = false;
    }

    protected function syncDocumentsFromFolder(): void
    {
        if (! $this->project->project_path) {
            return;
        }

        $projectDir = base_path($this->project->project_path);
        if (! is_dir($projectDir)) {
            return;
        }

        $files = $this->scanDirectory($projectDir, DocumentSafety::allowedExtensions());
        $seenPaths = [];

        foreach ($files as $file) {
            $relativePath = str_replace(base_path().'/', '', $file);
            $seenPaths[$relativePath] = true;
            $filename = basename($file);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $size = @filesize($file) ?: null;
            $hash = DocumentSafety::hashFile($file);

            $existing = $this->project->documents()->where('file_path', $relativePath)->first();

            if ($existing) {
                $dirty = [];
                if ($hash && $existing->content_hash !== $hash) {
                    $dirty['content_hash'] = $hash;
                    $dirty['ai_indexed'] = false;
                    $dirty['ai_summary'] = null;
                }
                if ($size !== null && $existing->file_size !== $size) {
                    $dirty['file_size'] = $size;
                }
                $dirty['last_seen_at'] = now();
                $dirty['missing_on_disk'] = false;

                if (! empty($dirty)) {
                    $existing->update($dirty);
                }

                if (in_array($extension, ['md', 'markdown', 'txt'], true) && ($dirty['content_hash'] ?? false)) {
                    IndexDocumentContent::dispatch($existing->id);
                }
            } else {
                $doc = ProjectDocument::create([
                    'project_id' => $this->project->id,
                    'title' => $filename,
                    'file_path' => $relativePath,
                    'file_type' => $extension,
                    'file_size' => $size,
                    'type' => 'file',
                    'content_hash' => $hash,
                    'last_seen_at' => now(),
                    'missing_on_disk' => false,
                    'is_knowledge_base' => true,
                ]);

                if (in_array($extension, ['md', 'markdown', 'txt'], true)) {
                    IndexDocumentContent::dispatch($doc->id);
                }
            }
        }

        foreach ($this->project->documents as $doc) {
            if ($doc->type === 'file') {
                $isSeen = isset($seenPaths[$doc->file_path]);
                if ($doc->missing_on_disk !== ! $isSeen) {
                    $doc->update(['missing_on_disk' => ! $isSeen]);
                }
            }
        }

        $this->project->refresh();
        $this->project->load('documents');
    }

    protected function scanDirectory(string $dir, array $extensions = ['md', 'txt', 'pdf', 'docx', 'xlsx']): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $extensions)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // --- Stats and Timeline (from workspace) ---
    public function getStats(): array
    {
        return [
            'milestones_total' => $this->project->milestones->count(),
            'milestones_completed' => $this->project->milestones->where('status', 'completed')->count(),
            'documents' => $this->project->documents->count(),
            'decisions' => $this->project->decisions->count(),
            'questions_open' => $this->project->questions->where('status', 'open')->count(),
        ];
    }

    public function render()
    {
        $openQuestions = $this->project->questions()->where('status', 'open')->get();
        $answeredQuestions = $this->project->questions()->where('status', 'answered')->get();
        $pendingMilestones = $this->project->milestones()->whereIn('status', ['pending', 'in_progress'])->get();
        $completedMilestones = $this->project->milestones()->where('status', 'completed')->get();

        // Search results for modals
        $orgResults = $this->orgSearch && strlen($this->orgSearch) >= 2
            ? Organization::where('name', 'like', '%'.$this->orgSearch.'%')->limit(10)->get()
            : collect();

        $personResults = $this->personSearch && strlen($this->personSearch) >= 2
            ? Person::with('organization')->where('name', 'like', '%'.$this->personSearch.'%')->limit(10)->get()
            : collect();

        $issueResults = $this->issueSearch && strlen($this->issueSearch) >= 2
            ? Issue::where('name', 'like', '%'.$this->issueSearch.'%')->limit(10)->get()
            : collect();

        $meetingResults = $this->meetingSearch && strlen($this->meetingSearch) >= 2
            ? Meeting::with('organizations')
                ->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->meetingSearch.'%')
                        ->orWhere('raw_notes', 'like', '%'.$this->meetingSearch.'%');
                })
                ->orderBy('meeting_date', 'desc')
                ->limit(10)
                ->get()
            : collect();

        // Staff search - all users for now
        $staffResults = $this->staffSearch && strlen($this->staffSearch) >= 2
            ? User::where('name', 'like', '%'.$this->staffSearch.'%')->limit(10)->get()
            : collect();

        $boxItemResults = collect();
        $boxSearch = trim($this->boxItemSearch);
        if ($this->activeTab === 'documents' && mb_strlen($boxSearch) >= 2) {
            $boxItemResults = BoxItem::query()
                ->files()
                ->whereNull('trashed_at')
                ->where(function ($query) use ($boxSearch) {
                    $query->where('name', 'like', '%'.$boxSearch.'%')
                        ->orWhere('path_display', 'like', '%'.$boxSearch.'%')
                        ->orWhere('box_item_id', 'like', '%'.$boxSearch.'%');
                })
                ->orderByDesc('modified_at')
                ->orderBy('name')
                ->limit(12)
                ->get();
        }

        $projectBoxLinks = BoxProjectDocumentLink::query()
            ->with(['boxItem', 'projectDocument'])
            ->where('project_id', $this->project->id)
            ->orderByRaw("CASE sync_status WHEN 'failed' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->get();

        $projectDocuments = $this->project->documents()
            ->with(['uploadedBy', 'boxLink'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.projects.project-show', [
            'statuses' => Project::STATUSES,
            'openQuestions' => $openQuestions,
            'answeredQuestions' => $answeredQuestions,
            'pendingMilestones' => $pendingMilestones,
            'completedMilestones' => $completedMilestones,
            'orgResults' => $orgResults,
            'personResults' => $personResults,
            'issueResults' => $issueResults,
            'meetingResults' => $meetingResults,
            'staffResults' => $staffResults,
            'projectDocuments' => $projectDocuments,
            'boxItemResults' => $boxItemResults,
            'projectBoxLinks' => $projectBoxLinks,
            'boxVisibilityOptions' => [
                'all' => 'All Team',
                'management' => 'Management',
                'admin' => 'Admin Only',
            ],
            'noteTypes' => ProjectNote::NOTE_TYPES,
            'staffRoles' => ['lead' => 'Lead', 'contributor' => 'Contributor', 'observer' => 'Observer'],
        ])->title($this->project->name.' - Project');
    }
}
