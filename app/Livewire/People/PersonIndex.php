<?php

namespace App\Livewire\People;

use App\Models\Person;
use App\Models\Organization;
use App\Models\User;
use App\Models\ContactView;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Contacts')]
class PersonIndex extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $filterOrg = '';
    public ?string $filterStatus = null;
    public ?int $filterOwner = null;
    public string $filterTag = '';
    public string $viewMode = 'card'; // 'card' or 'table'
    public int $perPage = 20;

    // Bulk selection/actions
    public array $selected = [];
    public bool $selectAll = false;
    public ?int $bulkOwnerId = null;
    public ?string $bulkStatus = null;
    public string $bulkTag = '';

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

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterOwner()
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

    public function updateStatus(int $personId, string $status): void
    {
        if (!array_key_exists($status, Person::STATUSES)) {
            return;
        }
        $person = Person::find($personId);
        if ($person) {
            $person->status = $status;
            $person->save();
        }
    }

    // ---- Bulk actions ----
    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $ids = Person::query()
                ->when($this->filterOrg, fn($q) => $q->where('organization_id', $this->filterOrg))
                ->pluck('id')->toArray();
            $this->selected = $ids;
        } else {
            $this->selected = [];
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->bulkOwnerId = null;
        $this->bulkStatus = null;
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

    public function applyBulkStatus(): void
    {
        if (!$this->bulkStatus || !array_key_exists($this->bulkStatus, Person::STATUSES) || empty($this->selected)) {
            return;
        }
        Person::whereIn('id', $this->selected)->update(['status' => $this->bulkStatus]);
        $this->clearSelection();
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
            'filterStatus' => $this->filterStatus,
            'filterOwner' => $this->filterOwner,
            'filterTag' => $this->filterTag,
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
        $this->filterStatus = $f['filterStatus'] ?? null;
        $this->filterOwner = $f['filterOwner'] ?? null;
        $this->filterTag = $f['filterTag'] ?? '';
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
                'status' => $data['status'] ?? null,
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
                            ]
                        ]
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
        return <<<PROMPT
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
                ['name' => trim($this->newOrgName), 'status' => 'active']
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
        $query = Person::query()
            ->with(['organization', 'owner'])
            ->withCount('meetings')
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('title', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterOrg, function ($q) {
                $q->where('organization_id', $this->filterOrg);
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterOwner, function ($q) {
                $q->where('owner_id', $this->filterOwner);
            })
            ->when($this->filterTag, function ($q) {
                $tag = trim($this->filterTag);
                if ($tag !== '') {
                    $q->whereJsonContains('tags', $tag);
                }
            })
            ->orderBy('name');

        return view('livewire.people.person-index', [
            'people' => $query->paginate($this->perPage),
            'organizations' => Organization::orderBy('name')->get(),
            'owners' => User::orderBy('name')->get(['id', 'name']),
            'statuses' => Person::STATUSES,
            'views' => $this->views,
        ])->title('Contacts');
    }
}
