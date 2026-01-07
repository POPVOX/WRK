<?php

namespace App\Livewire\Organizations;

use App\Models\Issue;
use App\Models\Organization;
use App\Models\Person;
use App\Models\ProfileAttachment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class OrganizationShow extends Component
{
    use WithFileUploads;

    public Organization $organization;

    public bool $editing = false;

    public string $name = '';

    public string $abbreviation = '';

    public string $type = '';

    public string $website = '';

    public string $linkedin_url = '';

    public string $notes = '';

    // File upload
    public $newAttachment;

    public string $attachmentNotes = '';

    // Add person form
    public bool $showAddPersonForm = false;

    public string $newPersonName = '';

    public string $newPersonTitle = '';

    public string $newPersonEmail = '';

    public string $newPersonLinkedIn = '';

    // Project linking
    public bool $showAddProjectModal = false;

    public string $projectSearch = '';

    public ?int $selectedProjectId = null;

    public string $projectRole = '';

    public function mount(Organization $organization)
    {
        $this->organization = $organization;
        $this->loadOrganizationData();
    }

    public function loadOrganizationData()
    {
        $this->name = $this->organization->name;
        $this->abbreviation = $this->organization->abbreviation ?? '';
        $this->type = $this->organization->type ?? '';
        $this->website = $this->organization->website ?? '';
        $this->linkedin_url = $this->organization->linkedin_url ?? '';
        $this->notes = $this->organization->notes ?? '';
    }

    public function startEditing()
    {
        $this->editing = true;
    }

    public function cancelEditing()
    {
        $this->editing = false;
        $this->loadOrganizationData();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
        ]);

        $linkedinChanged = $this->linkedin_url && $this->linkedin_url !== $this->organization->linkedin_url;

        $this->organization->update([
            'name' => $this->name,
            'abbreviation' => $this->abbreviation ?: null,
            'type' => $this->type ?: null,
            'website' => $this->website ?: null,
            'linkedin_url' => $this->linkedin_url ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->editing = false;

        // Auto-fetch LinkedIn if URL was added/changed and no logo exists
        if ($linkedinChanged && ! $this->organization->logo_url) {
            $this->fetchFromLinkedIn();
        } else {
            $this->dispatch('notify', type: 'success', message: 'Organization updated successfully!');
        }
    }

    public function fetchFromLinkedIn()
    {
        if (empty($this->organization->linkedin_url)) {
            $this->dispatch('notify', type: 'error', message: 'Please add a LinkedIn URL first.');

            return;
        }

        $scraper = app(\App\Services\LinkedInScraperService::class);
        $data = $scraper->extractCompanyData($this->organization->linkedin_url);

        if ($data['logo_url'] || $data['description']) {
            $updates = [];

            if ($data['logo_url']) {
                $updates['logo_url'] = $data['logo_url'];
            }

            if ($data['description']) {
                $updates['description'] = $data['description'];
            }

            $this->organization->update($updates);
            $this->dispatch('notify', type: 'success', message: 'LinkedIn data fetched successfully!');
        } else {
            $this->dispatch('notify', type: 'warning', message: 'Could not extract data from LinkedIn. The page may require login or the URL may be incorrect.');
        }
    }

    public function delete()
    {
        // Detach relationships
        $this->organization->meetings()->detach();

        // Optionally remove people or keep them orphaned
        $this->organization->people()->update(['organization_id' => null]);

        $this->organization->delete();

        return $this->redirect(route('organizations.index'), navigate: true);
    }

    public function getTopIssues()
    {
        // Get the most discussed issues for this organization across all meetings
        return Issue::whereHas('meetings', function ($query) {
            $query->whereHas('organizations', function ($q) {
                $q->where('organizations.id', $this->organization->id);
            });
        })
            ->withCount([
                'meetings' => function ($query) {
                    $query->whereHas('organizations', function ($q) {
                        $q->where('organizations.id', $this->organization->id);
                    });
                },
            ])
            ->orderByDesc('meetings_count')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        $meetings = $this->organization->meetings()
            ->with(['people', 'issues'])
            ->orderByDesc('meeting_date')
            ->get();

        $people = $this->organization->people()
            ->orderBy('name')
            ->get();

        $topIssues = $this->getTopIssues();

        $attachments = $this->organization->attachments()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get();

        $projects = $this->organization->projects()->orderBy('name')->get();

        $projectResults = $this->projectSearch && strlen($this->projectSearch) >= 2
            ? \App\Models\Project::where('name', 'like', '%'.$this->projectSearch.'%')
                ->where('status', '!=', 'archived')
                ->limit(10)
                ->get()
            : collect();

        // Media outlet data
        $pressClips = collect();
        $pitches = collect();
        $inquiries = collect();

        if ($this->organization->type === 'media' || strtolower($this->organization->type ?? '') === 'media') {
            $pressClips = $this->organization->pressClips()
                ->with(['journalist', 'outlet', 'issues', 'staffMentioned'])
                ->orderByDesc('published_at')
                ->limit(20)
                ->get();

            $pitches = $this->organization->pitches()
                ->with(['assignee', 'issues'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            $inquiries = $this->organization->inquiries()
                ->with(['handledBy', 'journalist'])
                ->orderByDesc('received_at')
                ->limit(20)
                ->get();
        }

        return view('livewire.organizations.organization-show', [
            'meetings' => $meetings,
            'people' => $people,
            'topIssues' => $topIssues,
            'types' => Organization::TYPES,
            'attachments' => $attachments,
            'projects' => $projects,
            'projectResults' => $projectResults,
            'pressClips' => $pressClips,
            'pitches' => $pitches,
            'mediaInquiries' => $inquiries,
        ])->title($this->organization->name.' - Organization');
    }

    public function uploadAttachment()
    {
        $this->validate([
            'newAttachment' => 'required|file|max:10240', // 10MB max
        ]);

        $path = $this->newAttachment->store('attachments/organizations', 'public');

        $this->organization->attachments()->create([
            'uploaded_by' => auth()->id(),
            'filename' => basename($path),
            'original_filename' => $this->newAttachment->getClientOriginalName(),
            'mime_type' => $this->newAttachment->getMimeType(),
            'size' => $this->newAttachment->getSize(),
            'path' => $path,
            'notes' => $this->attachmentNotes ?: null,
        ]);

        $this->newAttachment = null;
        $this->attachmentNotes = '';
        $this->dispatch('notify', type: 'success', message: 'Document uploaded successfully!');
    }

    public function deleteAttachment(int $attachmentId)
    {
        $attachment = ProfileAttachment::find($attachmentId);
        if ($attachment && $attachment->attachable_id === $this->organization->id) {
            \Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
            $this->dispatch('notify', type: 'success', message: 'Document deleted.');
        }
    }

    public function toggleAddPersonForm()
    {
        $this->showAddPersonForm = ! $this->showAddPersonForm;
        $this->resetPersonForm();
    }

    public function resetPersonForm()
    {
        $this->newPersonName = '';
        $this->newPersonTitle = '';
        $this->newPersonEmail = '';
        $this->newPersonLinkedIn = '';
    }

    public function addPerson()
    {
        $this->validate([
            'newPersonName' => 'required|string|max:255',
            'newPersonTitle' => 'nullable|string|max:255',
            'newPersonEmail' => 'nullable|email|max:255',
            'newPersonLinkedIn' => 'nullable|url|max:255',
        ]);

        Person::create([
            'name' => $this->newPersonName,
            'organization_id' => $this->organization->id,
            'title' => $this->newPersonTitle ?: null,
            'email' => $this->newPersonEmail ?: null,
            'linkedin_url' => $this->newPersonLinkedIn ?: null,
        ]);

        $this->resetPersonForm();
        $this->showAddPersonForm = false;
        $this->dispatch('notify', type: 'success', message: 'Person added to organization!');
    }

    public function removePerson(int $personId)
    {
        $person = Person::find($personId);
        if ($person && $person->organization_id === $this->organization->id) {
            $person->update(['organization_id' => null]);
            $this->dispatch('notify', type: 'success', message: 'Person removed from organization.');
        }
    }

    // --- Project Linking ---
    public function toggleAddProjectModal()
    {
        $this->showAddProjectModal = ! $this->showAddProjectModal;
        $this->projectSearch = '';
        $this->selectedProjectId = null;
        $this->projectRole = '';
    }

    public function selectProject(int $projectId)
    {
        $this->selectedProjectId = $projectId;
        $project = \App\Models\Project::find($projectId);
        $this->projectSearch = $project ? $project->name : '';
    }

    public function linkProject()
    {
        if (! $this->selectedProjectId) {
            return;
        }

        if ($this->organization->projects()->where('project_id', $this->selectedProjectId)->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Project already linked.');

            return;
        }

        $this->organization->projects()->attach($this->selectedProjectId, [
            'role' => $this->projectRole ?: null,
        ]);

        $this->toggleAddProjectModal();
        $this->organization->refresh();
        $this->dispatch('notify', type: 'success', message: 'Project linked!');
    }

    public function unlinkProject(int $projectId)
    {
        $this->organization->projects()->detach($projectId);
        $this->organization->refresh();
        $this->dispatch('notify', type: 'success', message: 'Project removed.');
    }
}
