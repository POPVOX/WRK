<?php

namespace App\Livewire\Organizations;

use App\Models\Organization;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Organizations')]
class OrganizationIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $filterType = '';

    public string $viewMode = 'card'; // 'card' or 'table'

    // Add Organization Modal
    public bool $showAddModal = false;

    public string $addMode = 'single'; // 'single', 'bulk', 'csv'

    // Single org form
    public string $orgName = '';

    public string $orgAbbreviation = '';

    public string $orgType = '';

    public string $orgWebsite = '';

    public string $orgLinkedIn = '';

    public string $orgDescription = '';

    // Bulk text input
    public string $bulkText = '';

    public string $bulkType = '';

    public bool $useAiExtraction = false;

    public array $extractedOrgs = [];

    public bool $isExtracting = false;

    // CSV upload
    public $csvFile = null;

    public array $csvPreview = [];

    public bool $csvHasHeader = true;

    // Import results
    public int $importedCount = 0;

    public array $importErrors = [];

    // Inline editing
    public ?int $editingOrgId = null;

    public string $editingName = '';

    public function startEditing(int $orgId, string $name): void
    {
        $this->editingOrgId = $orgId;
        $this->editingName = $name;
    }

    public function cancelEditing(): void
    {
        $this->editingOrgId = null;
        $this->editingName = '';
    }

    public function saveInlineEdit(): void
    {
        $this->validate([
            'editingName' => 'required|min:2|max:255',
        ]);

        $org = Organization::find($this->editingOrgId);
        if ($org) {
            $org->update(['name' => $this->editingName]);
        }

        $this->editingOrgId = null;
        $this->editingName = '';
    }

    public function applySuggestedName(int $orgId): void
    {
        $org = Organization::find($orgId);
        if ($org && $org->suggested_name) {
            $org->update(['name' => $org->suggested_name]);
            $this->dispatch('notify', type: 'success', message: 'Name updated!');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode)
    {
        $this->viewMode = $mode;
    }

    public function openAddModal(string $mode = 'single'): void
    {
        $this->resetAddForm();
        $this->addMode = $mode;
        $this->showAddModal = true;
    }

    public function setAddMode(string $mode): void
    {
        $this->addMode = $mode;
    }

    public function resetAddForm(): void
    {
        $this->orgName = '';
        $this->orgAbbreviation = '';
        $this->orgType = '';
        $this->orgWebsite = '';
        $this->orgLinkedIn = '';
        $this->orgDescription = '';
        $this->bulkText = '';
        $this->bulkType = '';
        $this->useAiExtraction = false;
        $this->extractedOrgs = [];
        $this->isExtracting = false;
        $this->csvFile = null;
        $this->csvPreview = [];
        $this->importedCount = 0;
        $this->importErrors = [];
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->resetAddForm();
    }

    public function saveSingleOrg(): void
    {
        $this->validate([
            'orgName' => 'required|min:2|max:255',
            'orgAbbreviation' => 'nullable|max:20',
            'orgType' => 'nullable|in:'.implode(',', Organization::TYPES),
            'orgWebsite' => 'nullable|url|max:500',
            'orgLinkedIn' => 'nullable|url|max:500',
            'orgDescription' => 'nullable|max:2000',
        ]);

        Organization::create([
            'name' => $this->orgName,
            'abbreviation' => $this->orgAbbreviation ?: null,
            'type' => $this->orgType ?: null,
            'website' => $this->orgWebsite ?: null,
            'linkedin_url' => $this->orgLinkedIn ?: null,
            'description' => $this->orgDescription ?: null,
        ]);

        $this->closeAddModal();
        session()->flash('message', 'Organization created successfully.');
    }

    public function importBulkText(): void
    {
        $this->validate([
            'bulkText' => 'required|min:3',
        ]);

        $lines = preg_split('/\r\n|\r|\n/', $this->bulkText);
        $imported = 0;
        $errors = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Check for duplicate
            if (Organization::where('name', $line)->exists()) {
                $errors[] = "'{$line}' already exists.";

                continue;
            }

            Organization::create([
                'name' => $line,
                'type' => $this->bulkType ?: null,
            ]);
            $imported++;
        }

        $this->importedCount = $imported;
        $this->importErrors = $errors;

        if ($imported > 0) {
            session()->flash('message', "Imported {$imported} organization(s).");
        }

        if (empty($errors)) {
            $this->closeAddModal();
        }
    }

    public function extractOrgsWithAI(): void
    {
        $this->validate([
            'bulkText' => 'required|min:10',
        ]);

        $this->isExtracting = true;
        $this->extractedOrgs = [];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.openai.key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert at extracting organization names from unstructured text. Extract all distinct organization, company, foundation, nonprofit, government agency, and institution names. Return ONLY a JSON array of strings with the organization names. Do not include any explanation, just the JSON array. Remove duplicates. If you cannot find any organizations, return an empty array [].',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract all organization names from this text:\n\n".$this->bulkText,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'] ?? '[]';
                // Clean up the response - remove markdown code blocks if present
                $content = preg_replace('/^```json\s*/', '', $content);
                $content = preg_replace('/\s*```$/', '', $content);

                $orgs = json_decode($content, true);
                if (is_array($orgs)) {
                    $this->extractedOrgs = array_map(fn ($name) => [
                        'name' => trim($name),
                        'selected' => true,
                        'exists' => Organization::where('name', trim($name))->exists(),
                    ], $orgs);
                } else {
                    $this->importErrors = ['Could not parse AI response.'];
                }
            } else {
                $this->importErrors = ['AI extraction failed. Please try the line-by-line format instead.'];
            }
        } catch (\Exception $e) {
            $this->importErrors = ['Error connecting to AI service.'];
        }

        $this->isExtracting = false;
    }

    public function toggleExtractedOrg(int $index): void
    {
        if (isset($this->extractedOrgs[$index])) {
            $this->extractedOrgs[$index]['selected'] = ! $this->extractedOrgs[$index]['selected'];
        }
    }

    public function importExtractedOrgs(): void
    {
        $imported = 0;
        $errors = [];

        foreach ($this->extractedOrgs as $org) {
            if (! $org['selected']) {
                continue;
            }
            if ($org['exists']) {
                $errors[] = "'{$org['name']}' already exists.";

                continue;
            }

            Organization::create([
                'name' => $org['name'],
                'type' => $this->bulkType ?: null,
            ]);
            $imported++;
        }

        $this->importedCount = $imported;
        $this->importErrors = $errors;

        if ($imported > 0) {
            session()->flash('message', "Imported {$imported} organization(s).");
        }

        if (empty($errors)) {
            $this->closeAddModal();
        }
    }

    public function updatedCsvFile(): void
    {
        if ($this->csvFile) {
            $this->previewCsv();
        }
    }

    protected function previewCsv(): void
    {
        $this->csvPreview = [];

        if (! $this->csvFile) {
            return;
        }

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle) {
            $row = 0;
            while (($data = fgetcsv($handle)) !== false && $row < 5) {
                $this->csvPreview[] = $data;
                $row++;
            }
            fclose($handle);
        }
    }

    public function importCsv(): void
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->importErrors = ['Could not read CSV file.'];

            return;
        }

        $imported = 0;
        $errors = [];
        $row = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            // Skip header row if enabled
            if ($this->csvHasHeader && $row === 1) {
                continue;
            }

            $name = trim($data[0] ?? '');
            if (empty($name)) {
                continue;
            }

            // Check for duplicate
            if (Organization::where('name', $name)->exists()) {
                $errors[] = "Row {$row}: '{$name}' already exists.";

                continue;
            }

            Organization::create([
                'name' => $name,
                'abbreviation' => trim($data[1] ?? '') ?: null,
                'type' => trim($data[2] ?? '') ?: null,
                'website' => trim($data[3] ?? '') ?: null,
                'description' => trim($data[4] ?? '') ?: null,
            ]);
            $imported++;
        }

        fclose($handle);

        $this->importedCount = $imported;
        $this->importErrors = $errors;

        if ($imported > 0) {
            session()->flash('message', "Imported {$imported} organization(s) from CSV.");
        }

        if (empty($errors)) {
            $this->closeAddModal();
        }
    }

    public function render()
    {
        $query = Organization::query()
            ->withCount(['meetings', 'people'])
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%');
            })
            ->when($this->filterType, function ($q) {
                $q->where('type', $this->filterType);
            })
            ->orderBy('name');

        return view('livewire.organizations.organization-index', [
            'organizations' => $query->paginate(20),
            'types' => Organization::TYPES,
        ]);
    }
}
