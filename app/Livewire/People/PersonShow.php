<?php

namespace App\Livewire\People;

use App\Models\Issue;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonInteraction;
use App\Models\ProfileAttachment;
use App\Models\User;
use App\Services\LinkedInScraperService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class PersonShow extends Component
{
    use WithFileUploads;

    public Person $person;

    public bool $editing = false;

    public string $name = '';

    public ?int $organization_id = null;

    public string $title = '';

    public string $email = '';

    public string $phone = '';

    public string $linkedin_url = '';

    public string $notes = '';

    public ?int $owner_id = null;

    public ?string $source = null;

    public string $tagsInput = '';

    public ?string $next_action_at = null;

    public string $next_action_note = '';

    // Media/Journalist fields
    public bool $is_journalist = false;

    public string $beat = '';

    public string $media_notes = '';

    public ?string $responsiveness = null;

    // Interaction form
    public string $interaction_type = 'note'; // call,email,meeting,note

    public ?string $interaction_date = null;

    public string $interaction_summary = '';

    public ?string $interaction_next_at = null;

    public string $interaction_next_note = '';

    // File upload
    public $newAttachment;

    public string $attachmentNotes = '';

    // Project linking
    public bool $showAddProjectModal = false;

    public string $projectSearch = '';

    public ?int $selectedProjectId = null;

    public string $projectRole = '';

    // Geographic tags
    public array $selectedRegions = [];

    public array $selectedCountries = [];

    public array $selectedUsStates = [];

    public function mount(Person $person)
    {
        $this->person = $person;
        $this->loadPersonData();
    }

    public function loadPersonData()
    {
        $this->name = $this->person->name;
        $this->organization_id = $this->person->organization_id;
        $this->title = $this->person->title ?? '';
        $this->email = $this->person->email ?? '';
        $this->phone = $this->person->phone ?? '';
        $this->linkedin_url = $this->person->linkedin_url ?? '';
        $this->notes = $this->person->notes ?? '';

        // CRM fields
        $this->owner_id = $this->person->owner_id ?? null;
        $this->source = $this->person->source ?? null;
        $this->tagsInput = implode(', ', (array) ($this->person->tags ?? []));
        $this->next_action_at = optional($this->person->next_action_at)->format('Y-m-d\TH:i') ?? null;
        $this->next_action_note = $this->person->next_action_note ?? '';

        // Journalist fields
        $this->is_journalist = (bool) $this->person->is_journalist;
        $this->beat = $this->person->beat ?? '';
        $this->media_notes = $this->person->media_notes ?? '';
        $this->responsiveness = $this->person->responsiveness;

        // Geographic tags
        $this->selectedRegions = $this->person->geographicTags()->where('geographic_type', 'region')->pluck('geographic_id')->toArray();
        $this->selectedCountries = $this->person->geographicTags()->where('geographic_type', 'country')->pluck('geographic_id')->toArray();
        $this->selectedUsStates = $this->person->geographicTags()->where('geographic_type', 'us_state')->pluck('geographic_id')->toArray();
    }

    #[On('geographic-tags-updated')]
    public function updateGeographicTags(array $data): void
    {
        $this->selectedRegions = $data['regions'] ?? [];
        $this->selectedCountries = $data['countries'] ?? [];
        $this->selectedUsStates = $data['usStates'] ?? [];
    }

    public function startEditing()
    {
        $this->editing = true;
    }

    public function cancelEditing()
    {
        $this->editing = false;
        $this->loadPersonData();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
            'title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'linkedin_url' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
        ]);

        $linkedinChanged = $this->linkedin_url && $this->linkedin_url !== $this->person->linkedin_url;

        $this->person->update([
            'name' => $this->name,
            'organization_id' => $this->organization_id ?: null,
            'title' => $this->title ?: null,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'linkedin_url' => $this->linkedin_url ?: null,
            'notes' => $this->notes ?: null,
            // Journalist fields
            'is_journalist' => $this->is_journalist,
            'beat' => $this->beat ?: null,
            'media_notes' => $this->media_notes ?: null,
            'responsiveness' => $this->responsiveness ?: null,
        ]);

        // Sync geographic tags
        $this->person->syncGeographicTags(
            $this->selectedRegions,
            $this->selectedCountries,
            $this->selectedUsStates
        );

        $this->person->refresh();
        $this->editing = false;

        // Auto-fetch LinkedIn if URL was added/changed and no photo exists
        if ($linkedinChanged && ! $this->person->photo_url) {
            $this->fetchFromLinkedIn();
        } else {
            $this->dispatch('notify', type: 'success', message: 'Person updated successfully!');
        }
    }

    public function fetchFromLinkedIn()
    {
        if (empty($this->person->linkedin_url)) {
            $this->dispatch('notify', type: 'error', message: 'Please add a LinkedIn URL first.');

            return;
        }

        $scraper = app(LinkedInScraperService::class);
        $data = $scraper->extractCompanyData($this->person->linkedin_url);

        $updates = [];
        $fieldsUpdated = [];

        // Photo from og:image
        if ($data['logo_url']) {
            $updates['photo_url'] = $data['logo_url'];
            $fieldsUpdated[] = 'photo';
        }

        // Bio from og:description
        if ($data['description']) {
            $updates['bio'] = $data['description'];
            $fieldsUpdated[] = 'bio';
        }

        // Job title parsed from og:title
        if ($data['job_title'] && ! $this->person->title) {
            $updates['title'] = $data['job_title'];
            $fieldsUpdated[] = 'title';
        }

        // Organization parsed from og:title
        if ($data['company'] && ! $this->person->organization_id) {
            // Try to find or create the organization
            $org = Organization::firstOrCreate(
                ['name' => $data['company']],
                ['name' => $data['company']]
            );
            $updates['organization_id'] = $org->id;
            $fieldsUpdated[] = 'organization';
        }

        if (! empty($updates)) {
            $this->person->update($updates);
            $this->person->refresh();
            $this->loadPersonData();
            $this->dispatch('notify', type: 'success', message: 'LinkedIn data fetched: '.implode(', ', $fieldsUpdated).' updated!');
        } else {
            $this->dispatch('notify', type: 'warning', message: 'Could not extract data from LinkedIn. Personal profiles often require login.');
        }
    }

    public function delete()
    {
        $this->person->meetings()->detach();
        $this->person->delete();

        return $this->redirect(route('people.index'), navigate: true);
    }

    public function getTopIssues()
    {
        return Issue::whereHas('meetings', function ($query) {
            $query->whereHas('people', function ($q) {
                $q->where('people.id', $this->person->id);
            });
        })
            ->withCount([
                'meetings' => function ($query) {
                    $query->whereHas('people', function ($q) {
                        $q->where('people.id', $this->person->id);
                    });
                },
            ])
            ->orderByDesc('meetings_count')
            ->limit(5)
            ->get();
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

        if ($this->person->projects()->where('project_id', $this->selectedProjectId)->exists()) {
            $this->dispatch('notify', type: 'error', message: 'Project already linked.');

            return;
        }

        $this->person->projects()->attach($this->selectedProjectId, [
            'role' => $this->projectRole ?: null,
        ]);

        $this->toggleAddProjectModal();
        $this->person->refresh();
        $this->dispatch('notify', type: 'success', message: 'Project linked!');
    }

    public function unlinkProject(int $projectId)
    {
        $this->person->projects()->detach($projectId);
        $this->person->refresh();
        $this->dispatch('notify', type: 'success', message: 'Project removed.');
    }

    // --- CRM Methods ---
    public function saveCrm(): void
    {
        // Validate limited fields
        $this->validate([
            'owner_id' => 'nullable|exists:users,id',
            'next_action_at' => 'nullable|date',
            'next_action_note' => 'nullable|string|max:500',
            'tagsInput' => 'nullable|string|max:500',
        ]);

        $tags = collect(explode(',', (string) $this->tagsInput))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->person->update([
            'owner_id' => $this->owner_id ?: null,
            'source' => $this->source ?: null,
            'tags' => $tags,
            'next_action_at' => $this->next_action_at ?: null,
            'next_action_note' => $this->next_action_note ?: null,
        ]);

        $this->person->refresh();
        $this->dispatch('notify', type: 'success', message: 'Contact CRM details saved.');
    }

    public function addTag(string $tag): void
    {
        $tags = collect($this->person->tags ?? [])->merge([$tag])->map(fn ($t) => trim($t))->filter()->unique()->values()->all();
        $this->person->tags = $tags;
        $this->person->save();
        $this->tagsInput = implode(', ', $tags);
    }

    public function removeTag(string $tag): void
    {
        $tags = collect($this->person->tags ?? [])->reject(fn ($t) => trim($t) === $tag)->values()->all();
        $this->person->tags = $tags;
        $this->person->save();
        $this->tagsInput = implode(', ', $tags);
    }

    public function addInteraction(): void
    {
        $this->validate([
            'interaction_type' => 'required|string|in:call,email,meeting,note',
            'interaction_date' => 'nullable|date',
            'interaction_summary' => 'nullable|string|max:2000',
            'interaction_next_at' => 'nullable|date',
            'interaction_next_note' => 'nullable|string|max:500',
        ]);

        $interaction = PersonInteraction::create([
            'person_id' => $this->person->id,
            'user_id' => auth()->id(),
            'type' => $this->interaction_type,
            'occurred_at' => $this->interaction_date ?: now(),
            'summary' => $this->interaction_summary ?: null,
            'next_action_at' => $this->interaction_next_at ?: null,
            'next_action_note' => $this->interaction_next_note ?: null,
        ]);

        // Update person's last_contacted and next action if provided
        $this->person->last_contacted_at = $interaction->occurred_at;
        if ($interaction->next_action_at) {
            $this->person->next_action_at = $interaction->next_action_at;
            $this->person->next_action_note = $interaction->next_action_note;
        }
        $this->person->save();

        // Reset form
        $this->interaction_type = 'note';
        $this->interaction_date = null;
        $this->interaction_summary = '';
        $this->interaction_next_at = null;
        $this->interaction_next_note = '';

        $this->dispatch('notify', type: 'success', message: 'Interaction logged.');
    }

    public function deleteInteraction(int $interactionId): void
    {
        $i = PersonInteraction::find($interactionId);
        if ($i && $i->person_id === $this->person->id) {
            $i->delete();
            $this->dispatch('notify', type: 'success', message: 'Interaction removed.');
        }
    }

    public function render()
    {
        $meetings = $this->person->meetings()
            ->with(['organizations', 'issues'])
            ->orderByDesc('meeting_date')
            ->get();

        $topIssues = $this->getTopIssues();

        $attachments = $this->person->attachments()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get();

        $projects = $this->person->projects()->orderBy('name')->get();

        $projectResults = $this->projectSearch && strlen($this->projectSearch) >= 2
            ? \App\Models\Project::where('name', 'like', '%'.$this->projectSearch.'%')
                ->where('status', '!=', 'archived')
                ->limit(10)
                ->get()
            : collect();

        $interactions = $this->person->interactions()->with('user')->limit(25)->get();
        $owners = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.people.person-show', [
            'meetings' => $meetings,
            'topIssues' => $topIssues,
            'organizations' => Organization::orderBy('name')->get(),
            'attachments' => $attachments,
            'projects' => $projects,
            'projectResults' => $projectResults,
            'interactions' => $interactions,
            'owners' => $owners,
        ])->title($this->person->name.' - Contact');
    }

    public function uploadAttachment()
    {
        $this->validate([
            'newAttachment' => 'required|file|max:10240', // 10MB max
        ]);

        $path = $this->newAttachment->store('attachments/people', 'public');

        $this->person->attachments()->create([
            'uploaded_by' => auth()->id(),
            'filename' => basename($path),
            'original_filename' => $this->newAttachment->getClientOriginalName(),
            'mime_type' => $this->newAttachment->getClientMimeType(),
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
        if ($attachment && $attachment->attachable_id === $this->person->id) {
            \Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
            $this->dispatch('notify', type: 'success', message: 'Document deleted.');
        }
    }
}
