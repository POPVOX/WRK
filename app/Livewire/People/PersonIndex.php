<?php

namespace App\Livewire\People;

use App\Models\ContactView;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Contacts')]
class PersonIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $filterOrg = '';

    public ?int $filterOwner = null;

    public string $filterTag = '';

    public string $filterEmailDomain = '';

    public string $viewMode = 'card'; // 'card' or 'table'

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public int $perPage = 20;

    // Bulk selection/actions
    public array $selected = [];

    public bool $selectAll = false;

    public ?int $bulkOwnerId = null;

    public ?int $bulkOrgId = null;

    public string $bulkTag = '';

    public bool $confirmingBulkDelete = false;

    public bool $showTrashed = false;

    public int $trashedCount = 0;

    // Saved views
    public array $views = [];

    public string $newViewName = '';

    // Add person form
    public bool $showAddPersonForm = false;

    public string $newPersonName = '';

    public ?int $newPersonOrgId = null;

    public string $newPersonTitle = '';

    public string $newPersonEmail = '';

    public string $newPersonLinkedIn = '';

    public string $newOrgName = ''; // For creating org inline

    // Import CSV
    public bool $showImportModal = false;

    public $importFile = null;

    public array $importReport = [];

    // Inline editing
    public ?int $editingPersonId = null;
    public string $editName = '';
    public string $editTitle = '';
    public string $editEmail = '';
    public string $editPhone = '';
    public ?int $editOrgId = null;
    public string $editLinkedIn = '';

    // Modal tabs and AI extraction
    public string $addModalTab = 'single'; // 'single', 'bulk', 'csv'

    public string $bulkText = '';

    public bool $useAiExtraction = true;

    public bool $isExtracting = false;

    public array $extractedPeople = [];

    public bool $showExtractedPreview = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterOrg()
    {
        $this->resetPage();
    }

    public function updatingFilterOwner()
    {
        $this->resetPage();
    }

    public function updatingFilterEmailDomain()
    {
        $this->resetPage();
    }

    public function updatingSortBy()
    {
        $this->resetPage();
    }

    public function updatingSortDirection()
    {
        $this->resetPage();
    }

    public function assignOwner(int $personId, ?int $ownerId): void
    {
        $person = Person::find($personId);
        if ($person) {
            $person->owner_id = $ownerId ?: null;
            $person->save();
        }
    }

    // ---- Inline Editing ----
    public function startEditing(int $personId): void
    {
        $person = Person::find($personId);
        if (!$person)
            return;

        $this->editingPersonId = $personId;
        $this->editName = $person->name;
        $this->editTitle = $person->title ?? '';
        $this->editEmail = $person->email ?? '';
        $this->editPhone = $person->phone ?? '';
        $this->editOrgId = $person->organization_id;
        $this->editLinkedIn = $person->linkedin_url ?? '';
    }

    public function cancelEditing(): void
    {
        $this->editingPersonId = null;
        $this->editName = '';
        $this->editTitle = '';
        $this->editEmail = '';
        $this->editPhone = '';
        $this->editOrgId = null;
        $this->editLinkedIn = '';
    }

    public function saveInlineEdit(): void
    {
        $person = Person::find($this->editingPersonId);
        if (!$person) {
            $this->cancelEditing();
            return;
        }

        $this->validate([
            'editName' => 'required|string|max:255',
            'editTitle' => 'nullable|string|max:255',
            'editEmail' => 'nullable|email|max:255',
            'editPhone' => 'nullable|string|max:50',
            'editOrgId' => 'nullable|exists:organizations,id',
            'editLinkedIn' => 'nullable|url|max:255',
        ]);

        $person->update([
            'name' => $this->editName,
            'title' => $this->editTitle ?: null,
            'email' => $this->editEmail ?: null,
            'phone' => $this->editPhone ?: null,
            'organization_id' => $this->editOrgId,
            'linkedin_url' => $this->editLinkedIn ?: null,
        ]);

        $this->cancelEditing();
        $this->dispatch('notify', type: 'success', message: 'Contact updated!');
    }

    public function updateField(int $personId, string $field, $value): void
    {
        $person = Person::find($personId);
        if (!$person)
            return;

        $allowedFields = ['name', 'title', 'email', 'phone', 'organization_id', 'linkedin_url'];
        if (!in_array($field, $allowedFields))
            return;

        $person->$field = $value ?: null;
        $person->save();
        $this->dispatch('notify', type: 'success', message: 'Updated!');
    }

    // ---- Bulk actions ----
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectFiltered();
        } else {
            $this->selected = [];
        }
    }

    public function selectFiltered(): void
    {
        $ids = $this->buildFilteredPeopleQuery()
            ->reorder()
            ->pluck('people.id')
            ->toArray();

        $this->selected = $ids;
        $this->selectAll = count($ids) > 0;

        if (count($ids) === 0) {
            $this->dispatch('notify', type: 'error', message: 'No contacts match the current filters.');

            return;
        }

        $this->dispatch('notify', type: 'success', message: 'Selected '.count($ids).' filtered contact(s).');
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->bulkOwnerId = null;
        $this->bulkOrgId = null;
        $this->bulkTag = '';
    }

    public function applyBulkOwner(): void
    {
        if (!$this->bulkOwnerId || empty($this->selected)) {
            return;
        }
        Person::whereIn('id', $this->selected)->update(['owner_id' => $this->bulkOwnerId]);
        $this->clearSelection();
    }

    public function applyBulkOrganization(): void
    {
        if (!$this->bulkOrgId || empty($this->selected)) {
            return;
        }

        if (!Organization::whereKey($this->bulkOrgId)->exists()) {
            return;
        }

        $count = Person::whereIn('id', $this->selected)->update(['organization_id' => $this->bulkOrgId]);
        $this->clearSelection();
        $this->dispatch('notify', type: 'success', message: "Assigned organization to {$count} contact(s).");
    }

    public function applyBulkAddTag(): void
    {
        $tag = trim($this->bulkTag);
        if ($tag === '' || empty($this->selected)) {
            return;
        }
        $people = Person::whereIn('id', $this->selected)->get();
        foreach ($people as $p) {
            $tags = collect($p->tags ?? [])->merge([$tag])->map(fn($t) => trim($t))->filter()->unique()->values()->all();
            $p->tags = $tags;
            $p->save();
        }
        $this->clearSelection();
    }

    public function deletePerson(int $id): void
    {
        $person = Person::find($id);
        if (!$person) {
            return;
        }
        $person->delete(); // soft delete — sets deleted_at
        $this->selected = array_values(array_diff($this->selected, [$id]));
        $this->dispatch('notify', type: 'success', message: 'Contact moved to trash.');
    }

    public function confirmBulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $this->confirmingBulkDelete = true;
    }

    public function cancelBulkDelete(): void
    {
        $this->confirmingBulkDelete = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $count = Person::whereIn('id', $this->selected)->count();
        Person::whereIn('id', $this->selected)->delete(); // soft delete
        $this->confirmingBulkDelete = false;
        $this->clearSelection();
        $this->dispatch('notify', type: 'success', message: "{$count} contact(s) moved to trash.");
    }

    public function toggleTrashed(): void
    {
        $this->showTrashed = !$this->showTrashed;
        $this->clearSelection();
        $this->resetPage();
    }

    public function restorePerson(int $id): void
    {
        $person = Person::onlyTrashed()->find($id);
        if (!$person) {
            return;
        }
        $person->restore();
        $this->selected = array_values(array_diff($this->selected, [$id]));
        $this->dispatch('notify', type: 'success', message: 'Contact restored.');
    }

    public function bulkRestore(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $count = Person::onlyTrashed()->whereIn('id', $this->selected)->count();
        Person::onlyTrashed()->whereIn('id', $this->selected)->restore();
        $this->clearSelection();
        $this->dispatch('notify', type: 'success', message: "{$count} contact(s) restored.");
    }

    public function permanentlyDeletePerson(int $id): void
    {
        $person = Person::onlyTrashed()->find($id);
        if (!$person) {
            return;
        }
        $person->meetings()->detach();
        $person->projects()->detach();
        $person->forceDelete();
        $this->selected = array_values(array_diff($this->selected, [$id]));
        $this->dispatch('notify', type: 'success', message: 'Contact permanently deleted.');
    }

    public function bulkPermanentlyDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }
        $people = Person::onlyTrashed()->whereIn('id', $this->selected)->get();
        $count = $people->count();
        foreach ($people as $person) {
            $person->meetings()->detach();
            $person->projects()->detach();
            $person->forceDelete();
        }
        $this->confirmingBulkDelete = false;
        $this->clearSelection();
        $this->dispatch('notify', type: 'success', message: "{$count} contact(s) permanently deleted.");
    }

    // ---- Saved views ----
    protected function loadViews(): void
    {
        $this->views = ContactView::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get(['id', 'name', 'filters'])
            ->toArray();
    }

    public function saveView(): void
    {
        $name = trim($this->newViewName);
        if ($name === '') {
            return;
        }
        $filters = [
            'search' => $this->search,
            'filterOrg' => $this->filterOrg,
            'filterOwner' => $this->filterOwner,
            'filterTag' => $this->filterTag,
            'filterEmailDomain' => $this->filterEmailDomain,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'viewMode' => $this->viewMode,
        ];
        ContactView::updateOrCreate(
            ['user_id' => Auth::id(), 'name' => $name],
            ['filters' => $filters]
        );
        $this->newViewName = '';
        $this->loadViews();
    }

    public function loadView(int $id): void
    {
        $v = ContactView::where('user_id', Auth::id())->find($id);
        if (!$v) {
            return;
        }
        $f = (array) ($v->filters ?? []);
        $this->search = $f['search'] ?? '';
        $this->filterOrg = $f['filterOrg'] ?? '';
        $this->filterOwner = $f['filterOwner'] ?? null;
        $this->filterTag = $f['filterTag'] ?? '';
        $this->filterEmailDomain = $f['filterEmailDomain'] ?? '';
        $this->sortBy = $f['sortBy'] ?? 'name';
        $this->sortDirection = $f['sortDirection'] ?? 'asc';
        $this->viewMode = $f['viewMode'] ?? 'card';
        $this->resetPage();
    }

    public function deleteView(int $id): void
    {
        ContactView::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->loadViews();
    }

    // ---- Import CSV ----
    public function openImportModal(): void
    {
        $this->showImportModal = true;
        $this->importFile = null;
        $this->importReport = [];
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importReport = [];
    }

    public function importContacts(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        $path = $this->importFile->getRealPath();
        $fh = fopen($path, 'r');
        if (!$fh) {
            return;
        }

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);

            return;
        }

        $map = [];
        foreach ($header as $i => $col) {
            $key = strtolower(trim($col));
            $map[$i] = $key;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($fh)) !== false) {
            $data = [];
            foreach ($row as $i => $value) {
                $data[$map[$i] ?? "col_$i"] = trim((string) $value);
            }

            $email = $data['email'] ?? null;
            if (!$email && empty($data['name'])) {
                $skipped++;

                continue;
            }

            // Organization
            $orgId = null;
            if (!empty($data['organization'])) {
                $org = Organization::firstOrCreate(['name' => $data['organization']], ['name' => $data['organization']]);
                $orgId = $org->id;
            }

            // Tags
            $tags = [];
            if (!empty($data['tags'])) {
                $parts = preg_split('/[|,]/', (string) $data['tags']);
                $tags = collect($parts)->map(fn($t) => trim($t))->filter()->unique()->values()->all();
            }

            // Owner by email
            $ownerId = null;
            if (!empty($data['owner_email'])) {
                $owner = User::where('email', $data['owner_email'])->first();
                $ownerId = $owner?->id;
            }

            // Upsert by email if present, else create new
            $person = null;
            if ($email) {
                $person = Person::where('email', $email)->first();
            }

            $payload = [
                'name' => $data['name'] ?? ($person->name ?? ''),
                'organization_id' => $orgId,
                'title' => $data['title'] ?? null,
                'email' => $email ?: null,
                'phone' => $data['phone'] ?? null,
                'source' => $data['source'] ?? null,
                'owner_id' => $ownerId,
                'tags' => $tags,
            ];

            if ($person) {
                $person->fill($payload);
                $person->save();
                $updated++;
            } else {
                $person = Person::create($payload);
                $created++;
            }
        }

        fclose($fh);

        $this->importReport = [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];

        $this->importFile = null;

        // refresh list
        $this->resetPage();
    }

    // ---- AI Extraction ----
    public function extractPeopleFromText(): void
    {
        if (empty(trim($this->bulkText))) {
            $this->dispatch('notify', type: 'error', message: 'Please enter some text to extract from.');

            return;
        }

        $this->isExtracting = true;

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => 'claude-sonnet-4-20250514',
                        'max_tokens' => 2000,
                        'system' => $this->getExtractionSystemPrompt(),
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => "Extract people/contacts from this text:\n\n{$this->bulkText}",
                            ],
                        ],
                    ]);

            $content = $response->json('content.0.text');

            if ($content) {
                $this->parseExtractedPeople($content);
                $this->showExtractedPreview = true;
                $this->dispatch('notify', type: 'success', message: 'Extracted ' . count($this->extractedPeople) . ' people. Review and confirm.');
            } else {
                $this->dispatch('notify', type: 'error', message: 'Could not extract people. Please try again.');
            }
        } catch (\Exception $e) {
            \Log::error('People AI extraction error: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Error during extraction. Please try again.');
        }

        $this->isExtracting = false;
    }

    protected function getExtractionSystemPrompt(): string
    {
        return <<<'PROMPT'
You extract structured contact/people information from free-form text such as email signatures, meeting notes, business cards, LinkedIn profiles, or contact lists.

For each person found, extract:
- name (required): Full name
- title: Job title/position
- organization: Company/organization name
- email: Email address
- phone: Phone number
- linkedin_url: LinkedIn profile URL

Return a JSON array of people objects:
```json
[
    {
        "name": "John Smith",
        "title": "Senior Policy Analyst",
        "organization": "Congressional Research Service",
        "email": "john.smith@crs.gov",
        "phone": "202-555-1234",
        "linkedin_url": null
    }
]
```

If a field cannot be determined, use null. Extract ALL people mentioned in the text.
PROMPT;
    }

    protected function parseExtractedPeople(string $content): void
    {
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonStr = $matches[1];
        } elseif (preg_match('/\[.*\]/s', $content, $matches)) {
            $jsonStr = $matches[0];
        } else {
            $this->extractedPeople = [];

            return;
        }

        try {
            $data = json_decode($jsonStr, true);
            $this->extractedPeople = is_array($data) ? $data : [];
        } catch (\Exception $e) {
            $this->extractedPeople = [];
        }
    }

    public function removeExtractedPerson(int $index): void
    {
        unset($this->extractedPeople[$index]);
        $this->extractedPeople = array_values($this->extractedPeople);
    }

    public function saveExtractedPeople(): void
    {
        $created = 0;
        foreach ($this->extractedPeople as $personData) {
            if (empty($personData['name'])) {
                continue;
            }

            // Find or create organization
            $orgId = null;
            if (!empty($personData['organization'])) {
                $org = Organization::firstOrCreate(
                    ['name' => $personData['organization']],
                    ['name' => $personData['organization']]
                );
                $orgId = $org->id;
            }

            Person::create([
                'name' => $personData['name'],
                'title' => $personData['title'] ?? null,
                'organization_id' => $orgId,
                'email' => $personData['email'] ?? null,
                'phone' => $personData['phone'] ?? null,
                'linkedin_url' => $personData['linkedin_url'] ?? null,
            ]);
            $created++;
        }

        $this->extractedPeople = [];
        $this->showExtractedPreview = false;
        $this->bulkText = '';
        $this->showAddPersonForm = false;
        $this->dispatch('notify', type: 'success', message: "Created {$created} contacts!");
        $this->resetPage();
    }

    public function setViewMode(string $mode)
    {
        $this->viewMode = $mode;
    }

    public function toggleAddPersonForm()
    {
        $this->showAddPersonForm = !$this->showAddPersonForm;
        $this->resetPersonForm();
        $this->resetModalState();
    }

    public function resetModalState(): void
    {
        $this->addModalTab = 'single';
        $this->bulkText = '';
        $this->extractedPeople = [];
        $this->showExtractedPreview = false;
        $this->importFile = null;
        $this->importReport = [];
    }

    public function resetPersonForm()
    {
        $this->newPersonName = '';
        $this->newPersonOrgId = null;
        $this->newPersonTitle = '';
        $this->newPersonEmail = '';
        $this->newPersonLinkedIn = '';
        $this->newOrgName = '';
    }

    public function addPerson()
    {
        $this->validate([
            'newPersonName' => 'required|string|max:255',
            'newPersonOrgId' => 'nullable|exists:organizations,id',
            'newPersonTitle' => 'nullable|string|max:255',
            'newPersonEmail' => 'nullable|email|max:255',
            'newPersonLinkedIn' => 'nullable|url|max:255',
            'newOrgName' => 'nullable|string|max:255',
        ]);

        // Create org from name if provided and no org selected
        $orgId = $this->newPersonOrgId;
        if (!$orgId && $this->newOrgName) {
            $org = Organization::firstOrCreate(
                ['name' => trim($this->newOrgName)],
                ['name' => trim($this->newOrgName)]
            );
            $orgId = $org->id;
        }

        $person = Person::create([
            'name' => $this->newPersonName,
            'organization_id' => $orgId ?: null,
            'title' => $this->newPersonTitle ?: null,
            'email' => $this->newPersonEmail ?: null,
            'linkedin_url' => $this->newPersonLinkedIn ?: null,
        ]);

        $this->resetPersonForm();
        $this->showAddPersonForm = false;
        $this->dispatch('notify', type: 'success', message: 'Person created successfully!');
    }

    public function mount()
    {
        $this->loadViews();
    }

    public function render()
    {
        $query = $this->buildFilteredPeopleQuery();
        $this->applySorting($query);

        $this->trashedCount = Person::onlyTrashed()->count();

        return view('livewire.people.person-index', [
            'people' => $query->paginate($this->perPage),
            'organizations' => Organization::orderBy('name')->get(),
            'owners' => User::orderBy('name')->get(['id', 'name']),
            'views' => $this->views,
        ])->title('Contacts');
    }

    protected function buildFilteredPeopleQuery(): Builder
    {
        $emailDomainExpr = $this->emailDomainExpression();
        $query = Person::query()
            ->select('people.*')
            ->selectRaw("{$emailDomainExpr} as email_domain")
            ->with(['organization', 'owner'])
            ->withCount('meetings');

        // Show trashed or active records
        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        $query->when($this->search, function ($q) {
            $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('title', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        })
            ->when($this->filterOrg, function ($q) {
                $q->where('organization_id', $this->filterOrg);
            })
            ->when($this->filterOwner, function ($q) {
                $q->where('owner_id', $this->filterOwner);
            })
            ->when($this->filterTag, function ($q) {
                $tag = trim($this->filterTag);
                if ($tag !== '') {
                    $q->whereJsonContains('tags', $tag);
                }
            });

        $domain = $this->normalizeDomainFilter($this->filterEmailDomain);
        if ($domain !== '') {
            $query->whereRaw("{$emailDomainExpr} = ?", [$domain]);
        }

        return $query;
    }

    protected function applySorting(Builder $query): void
    {
        $direction = $this->normalizeSortDirection($this->sortDirection);
        $sortBy = $this->normalizeSortBy($this->sortBy);
        $domainExpr = $this->emailDomainExpression();

        if ($sortBy === 'organization') {
            $query->orderBy(
                Organization::select('name')->whereColumn('organizations.id', 'people.organization_id'),
                $direction
            )->orderBy('people.name');

            return;
        }

        if ($sortBy === 'email_domain') {
            $query->orderByRaw("CASE WHEN {$domainExpr} = '' THEN 1 ELSE 0 END")
                ->orderByRaw("{$domainExpr} {$direction}")
                ->orderBy('people.name');

            return;
        }

        $query->orderBy('people.name', $direction);
    }

    protected function normalizeSortBy(string $value): string
    {
        $value = trim(strtolower($value));
        $allowed = ['name', 'organization', 'email_domain'];

        return in_array($value, $allowed, true) ? $value : 'name';
    }

    protected function normalizeSortDirection(string $value): string
    {
        $value = trim(strtolower($value));

        return $value === 'desc' ? 'desc' : 'asc';
    }

    protected function normalizeDomainFilter(string $raw): string
    {
        $value = trim(strtolower($raw));
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '@')) {
            $value = ltrim($value, '@');
        }

        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#^www\.#', '', $value) ?? $value;
        $value = strtok($value, '/');

        return trim((string) $value);
    }

    protected function emailDomainExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "coalesce(split_part(lower(people.email), '@', 2), '')";
        }

        if ($driver === 'mysql') {
            return "coalesce(case when people.email like '%@%' then substring_index(lower(people.email), '@', -1) else '' end, '')";
        }

        return "coalesce(case when instr(lower(people.email), '@') > 0 then substr(lower(people.email), instr(lower(people.email), '@') + 1) else '' end, '')";
    }
}
