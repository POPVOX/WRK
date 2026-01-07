<?php

namespace App\Livewire\Grants;

use App\Jobs\ExtractGrantRequirements;
use App\Jobs\GenerateGrantReport;
use App\Models\Grant;
use App\Models\GrantDocument;
use App\Models\Project;
use App\Models\ReportingRequirement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class GrantShow extends Component
{
    use WithFileUploads;

    public Grant $grant;

    public string $activeTab = 'overview';

    // Document upload
    public $uploadFile;

    public string $uploadTitle = '';

    public string $uploadType = 'other';

    public string $uploadMode = 'file'; // 'file' or 'paste'

    public string $pasteContent = '';

    // Requirements
    public bool $showAddRequirement = false;

    public string $reqName = '';

    public string $reqType = 'progress_report';

    public ?string $reqDueDate = null;

    public string $reqNotes = '';

    // Report generation
    public bool $isGenerating = false;

    public bool $isExtracting = false;

    public ?string $generatedReport = null;

    public string $reportType = 'progress';

    // Edit Grant Modal
    public bool $showEditModal = false;

    public string $editName = '';

    public string $editStatus = 'pending';

    public ?string $editAmount = '';

    public ?string $editStartDate = null;

    public ?string $editEndDate = null;

    public string $editDescription = '';

    public string $editDeliverables = '';

    public string $editVisibility = 'management';

    public string $editNotes = '';

    public string $editScope = 'all';

    public ?int $editPrimaryProjectId = null;

    public function mount(Grant $grant): void
    {
        if (! Auth::user()?->isAdmin()) {
            abort(403, 'Access denied. Admin only.');
        }

        $this->grant = $grant->load([
            'funder',
            'documents.uploader',
            'reportingRequirements',
            'projects',
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // === Edit Grant ===
    public function openEditModal(): void
    {
        $this->editName = $this->grant->name;
        $this->editStatus = $this->grant->status ?? 'pending';
        $this->editAmount = $this->grant->amount ? (string) $this->grant->amount : '';
        $this->editStartDate = $this->grant->start_date?->format('Y-m-d');
        $this->editEndDate = $this->grant->end_date?->format('Y-m-d');
        $this->editDescription = $this->grant->description ?? '';
        $this->editDeliverables = $this->grant->deliverables ?? '';
        $this->editVisibility = $this->grant->visibility ?? 'management';
        $this->editNotes = $this->grant->notes ?? '';
        $this->editScope = $this->grant->scope ?? 'all';
        $this->editPrimaryProjectId = $this->grant->primary_project_id;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
    }

    public function saveGrant(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editStatus' => 'required|in:pending,active,completed,declined',
            'editAmount' => 'nullable|numeric|min:0',
            'editStartDate' => 'nullable|date',
            'editEndDate' => 'nullable|date|after_or_equal:editStartDate',
            'editDescription' => 'nullable|string',
            'editDeliverables' => 'nullable|string',
            'editVisibility' => 'required|in:all,management',
            'editScope' => 'required|in:all,project',
        ]);

        $this->grant->update([
            'name' => $this->editName,
            'status' => $this->editStatus,
            'amount' => $this->editAmount ?: null,
            'start_date' => $this->editStartDate ?: null,
            'end_date' => $this->editEndDate ?: null,
            'description' => $this->editDescription ?: null,
            'deliverables' => $this->editDeliverables ?: null,
            'visibility' => $this->editVisibility,
            'notes' => $this->editNotes ?: null,
            'scope' => $this->editScope,
            'primary_project_id' => $this->editPrimaryProjectId,
        ]);

        $this->grant->refresh();
        $this->showEditModal = false;
        session()->flash('message', 'Grant updated successfully.');
    }

    // === Document Upload ===
    public function uploadDocument(): void
    {
        $this->validate([
            'uploadFile' => 'required|file|mimes:pdf,doc,docx,txt,md|max:20480',
            'uploadTitle' => 'required|string|max:255',
            'uploadType' => 'required|in:application,agreement,report,amendment,other',
        ]);

        $ext = strtolower($this->uploadFile->getClientOriginalExtension());
        $fileName = \Illuminate\Support\Str::slug($this->uploadTitle).'-'.time().'.'.$ext;
        $path = $this->uploadFile->storeAs('grant_documents/'.$this->grant->id, $fileName, 'public');

        $fullPath = Storage::disk('public')->path($path);

        GrantDocument::create([
            'grant_id' => $this->grant->id,
            'title' => $this->uploadTitle,
            'type' => $this->uploadType,
            'file_path' => $path,
            'file_type' => $ext,
            'mime_type' => @mime_content_type($fullPath) ?: null,
            'file_size' => @filesize($fullPath) ?: null,
            'uploaded_by' => Auth::id(),
        ]);

        $this->uploadFile = null;
        $this->uploadTitle = '';
        $this->uploadType = 'other';
        $this->pasteContent = '';
        $this->grant->refresh();
        $this->grant->load('documents.uploader');
    }

    public function savePastedText(): void
    {
        $this->validate([
            'pasteContent' => 'required|string|min:10',
            'uploadTitle' => 'required|string|max:255',
            'uploadType' => 'required|in:application,agreement,report,amendment,other',
        ]);

        $fileName = \Illuminate\Support\Str::slug($this->uploadTitle).'-'.time().'.txt';
        $path = 'grant_documents/'.$this->grant->id.'/'.$fileName;

        Storage::disk('public')->put($path, $this->pasteContent);
        $fullPath = Storage::disk('public')->path($path);

        GrantDocument::create([
            'grant_id' => $this->grant->id,
            'title' => $this->uploadTitle,
            'type' => $this->uploadType,
            'file_path' => $path,
            'file_type' => 'txt',
            'mime_type' => 'text/plain',
            'file_size' => strlen($this->pasteContent),
            'uploaded_by' => Auth::id(),
        ]);

        $this->pasteContent = '';
        $this->uploadTitle = '';
        $this->uploadType = 'other';
        $this->uploadMode = 'file';
        $this->grant->refresh();
        $this->grant->load('documents.uploader');
    }

    public function extractRequirements(int $documentId): void
    {
        $document = GrantDocument::where('grant_id', $this->grant->id)->findOrFail($documentId);

        if (! config('ai.enabled')) {
            session()->flash('error', 'AI features are disabled.');

            return;
        }

        if ($this->isExtracting) {
            session()->flash('message', 'Extraction already in progress...');

            return;
        }

        $this->isExtracting = true;

        // Dispatch AI extraction job
        ExtractGrantRequirements::dispatch($document->id);
        session()->flash('message', 'Extraction started! This happens in the background. Refresh the page in a minute to see results.');

        $this->isExtracting = false;
    }

    public function extractAllRequirements(): void
    {
        if (! config('ai.enabled')) {
            session()->flash('error', 'AI features are disabled.');

            return;
        }

        if ($this->isExtracting) {
            session()->flash('message', 'Extraction already in progress...');

            return;
        }

        $unprocessedDocs = $this->grant->documents()->where('ai_processed', false)->get();

        if ($unprocessedDocs->isEmpty()) {
            session()->flash('message', 'All documents have already been processed.');

            return;
        }

        $this->isExtracting = true;

        foreach ($unprocessedDocs as $document) {
            ExtractGrantRequirements::dispatch($document->id);
        }

        session()->flash('message', "Processing {$unprocessedDocs->count()} document(s) in background. Refresh the page in a minute to see results.");

        $this->isExtracting = false;
    }

    public function deleteDocument(int $documentId): void
    {
        $document = GrantDocument::where('grant_id', $this->grant->id)->findOrFail($documentId);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
        $this->grant->refresh();
        $this->grant->load('documents.uploader');
    }

    // === Requirements ===
    public function toggleAddRequirement(): void
    {
        $this->showAddRequirement = ! $this->showAddRequirement;
        if (! $this->showAddRequirement) {
            $this->resetRequirementForm();
        }
    }

    public function resetRequirementForm(): void
    {
        $this->reqName = '';
        $this->reqType = 'progress_report';
        $this->reqDueDate = null;
        $this->reqNotes = '';
    }

    public function addRequirement(): void
    {
        $this->validate([
            'reqName' => 'required|string|max:255',
            'reqType' => 'required|string',
            'reqDueDate' => 'required|date',
            'reqNotes' => 'nullable|string',
        ]);

        ReportingRequirement::create([
            'grant_id' => $this->grant->id,
            'name' => $this->reqName,
            'type' => $this->reqType,
            'due_date' => $this->reqDueDate,
            'status' => 'pending',
            'notes' => $this->reqNotes,
        ]);

        $this->resetRequirementForm();
        $this->showAddRequirement = false;
        $this->grant->refresh();
        $this->grant->load('reportingRequirements');
    }

    public function updateRequirementStatus(int $reqId, string $status): void
    {
        $req = ReportingRequirement::where('grant_id', $this->grant->id)->findOrFail($reqId);
        $req->update(['status' => $status]);
        $this->grant->refresh();
        $this->grant->load('reportingRequirements');
    }

    // === Report Generation ===
    public function generateReport(): void
    {
        if (! config('ai.enabled')) {
            session()->flash('error', 'AI features are disabled.');

            return;
        }

        $this->isGenerating = true;

        // For now, we'll generate inline (could be dispatched as job for async)
        GenerateGrantReport::dispatchSync($this->grant->id, $this->reportType);

        // Fetch the generated report from cache/storage
        $cachePath = "grant_reports/{$this->grant->id}-{$this->reportType}.md";
        if (Storage::disk('local')->exists($cachePath)) {
            $this->generatedReport = Storage::disk('local')->get($cachePath);
        } else {
            $this->generatedReport = 'Report generation in progress...';
        }

        $this->isGenerating = false;
    }

    public function clearReport(): void
    {
        $this->generatedReport = null;
    }

    public function render()
    {
        $linkedProjects = $this->grant->projects;

        $upcomingRequirements = $this->grant->reportingRequirements()
            ->where('status', '!=', 'submitted')
            ->orderBy('due_date')
            ->get();

        // Get contacts from the funder organization
        $funderContacts = $this->grant->funder
            ? \App\Models\Person::where('organization_id', $this->grant->funder->id)
                ->orderBy('name')
                ->get()
            : collect();

        // Get consolidated insights from all documents
        $consolidatedInsights = $this->getConsolidatedInsights();

        return view('livewire.grants.grant-show', [
            'statuses' => Grant::STATUSES,
            'documentTypes' => GrantDocument::TYPES,
            'linkedProjects' => $linkedProjects,
            'upcomingRequirements' => $upcomingRequirements,
            'funderContacts' => $funderContacts,
            'consolidatedInsights' => $consolidatedInsights,
            'requirementTypes' => [
                'progress_report' => 'Progress Report',
                'financial_report' => 'Financial Report',
                'narrative_report' => 'Narrative Report',
                'final_report' => 'Final Report',
                'impact_assessment' => 'Impact Assessment',
                'other' => 'Other',
            ],
        ])->title($this->grant->name.' - Grant');
    }

    protected function getConsolidatedInsights(): array
    {
        $docs = $this->grant->documents()->where('ai_processed', true)->get();

        if ($docs->isEmpty()) {
            return ['hasInsights' => false];
        }

        $priorities = [];
        $values = [];
        $approaches = [];
        $goals = [];
        $milestones = [];
        $keyDates = [];
        $restrictions = [];
        $compliance = [];
        $summaries = [];

        foreach ($docs as $doc) {
            $data = $doc->ai_extracted_data ?? [];

            // Collect summaries
            if (! empty($data['summary'])) {
                $summaries[] = $data['summary'];
            }

            // Funder priorities
            if (! empty($data['funder_priorities'])) {
                foreach ($data['funder_priorities'] as $p) {
                    $priorities[] = is_array($p) ? ($p['priority'] ?? $p) : $p;
                }
            }

            // Funder values
            if (! empty($data['funder_values'])) {
                if (is_array($data['funder_values'])) {
                    $values = array_merge($values, $data['funder_values']);
                } else {
                    $values[] = $data['funder_values'];
                }
            }

            // Funder approach
            if (! empty($data['funder_approach'])) {
                $approaches[] = $data['funder_approach'];
            }

            // Goals
            if (! empty($data['goals'])) {
                foreach ($data['goals'] as $g) {
                    $goals[] = is_array($g) ? ($g['goal'] ?? $g) : $g;
                }
            }

            // Milestones
            if (! empty($data['milestones'])) {
                foreach ($data['milestones'] as $m) {
                    $milestones[] = $m;
                }
            }

            // Key dates
            if (! empty($data['key_dates'])) {
                foreach ($data['key_dates'] as $d) {
                    $keyDates[] = $d;
                }
            }

            // Restrictions
            if (! empty($data['restrictions'])) {
                if (is_array($data['restrictions'])) {
                    $restrictions = array_merge($restrictions, $data['restrictions']);
                } else {
                    $restrictions[] = $data['restrictions'];
                }
            }

            // Compliance
            if (! empty($data['compliance_notes'])) {
                if (is_array($data['compliance_notes'])) {
                    $compliance = array_merge($compliance, $data['compliance_notes']);
                } else {
                    $compliance[] = $data['compliance_notes'];
                }
            }
        }

        // Get project activities that might be relevant to highlight
        $relevantActivities = $this->getRelevantProjectActivities($priorities, $values, $goals);

        return [
            'hasInsights' => true,
            'summaries' => array_unique($summaries),
            'priorities' => array_unique($priorities),
            'values' => array_unique($values),
            'approach' => implode(' ', array_unique($approaches)),
            'goals' => array_unique($goals),
            'milestones' => $milestones,
            'keyDates' => $keyDates,
            'restrictions' => array_unique($restrictions),
            'compliance' => array_unique($compliance),
            'relevantActivities' => $relevantActivities,
        ];
    }

    protected function getRelevantProjectActivities(array $priorities, array $values, array $goals): array
    {
        $activities = [];

        // Get linked projects
        $projects = $this->grant->projects;

        foreach ($projects as $project) {
            // Get recent decisions
            $decisions = $project->decisions()->latest('decision_date')->take(5)->get();
            foreach ($decisions as $decision) {
                $activities[] = [
                    'type' => 'decision',
                    'project' => $project->name,
                    'title' => $decision->title,
                    'date' => $decision->decision_date?->format('M j, Y'),
                    'description' => $decision->rationale,
                ];
            }

            // Get recent milestones
            $projectMilestones = $project->milestones()
                ->where('status', 'completed')
                ->latest('completed_at')
                ->take(5)
                ->get();
            foreach ($projectMilestones as $milestone) {
                $activities[] = [
                    'type' => 'milestone',
                    'project' => $project->name,
                    'title' => $milestone->name,
                    'date' => $milestone->completed_at?->format('M j, Y'),
                    'status' => 'Completed',
                ];
            }
        }

        // Get recent meetings related to linked projects
        $projectIds = $projects->pluck('id');
        if ($projectIds->isNotEmpty()) {
            $meetings = \App\Models\Meeting::whereHas('projects', function ($q) use ($projectIds) {
                $q->whereIn('projects.id', $projectIds);
            })->latest('meeting_date')->take(5)->get();

            foreach ($meetings as $meeting) {
                $activities[] = [
                    'type' => 'meeting',
                    'project' => $meeting->projects->first()?->name ?? 'General',
                    'title' => $meeting->title ?? 'Meeting',
                    'date' => $meeting->meeting_date?->format('M j, Y'),
                    'description' => $meeting->ai_summary ?? substr($meeting->raw_notes ?? '', 0, 150),
                ];
            }
        }

        // Sort by date
        usort($activities, function ($a, $b) {
            return strtotime($b['date'] ?? '1970-01-01') - strtotime($a['date'] ?? '1970-01-01');
        });

        return array_slice($activities, 0, 10);
    }
}
