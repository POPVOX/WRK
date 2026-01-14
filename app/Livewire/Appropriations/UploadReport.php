<?php

namespace App\Livewire\Appropriations;

use App\Models\LegislativeReport;
use App\Models\ReportingRequirement;
use App\Services\LegislativeReportParserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Upload Appropriations Report')]
class UploadReport extends Component
{
    use WithFileUploads;

    public $reportFile;
    public string $fiscalYear = '';
    public string $reportType = 'house';
    public string $reportNumber = '';
    public string $title = '';
    public ?string $enactmentDate = null;
    public string $notes = '';

    public bool $processing = false;
    public array $extractedRequirements = [];
    public int $step = 1; // 1: Upload, 2: Review, 3: Confirm

    // For manual requirement entry
    public bool $showManualEntry = false;
    public string $manualTitle = '';
    public string $manualAgency = '';
    public string $manualTimelineType = 'days_from_enactment';
    public ?int $manualTimelineValue = null;
    public string $manualDescription = '';
    public string $manualRecipients = '';
    public string $manualCategory = 'new';
    public string $manualPageRef = '';

    // Paste text for AI parsing
    public string $pasteText = '';

    protected $rules = [
        'fiscalYear' => 'required|string',
        'reportType' => 'required|in:house,senate',
        'reportNumber' => 'required|string',
        'title' => 'required|string|max:500',
        'enactmentDate' => 'nullable|date',
    ];

    public function mount(): void
    {
        // Default to current fiscal year
        $currentYear = date('Y');
        $this->fiscalYear = 'FY' . ($currentYear + (date('n') >= 10 ? 1 : 0));
    }

    public function processReport(): void
    {
        $this->validate([
            'reportFile' => 'required|mimes:pdf|max:51200',
            ...$this->rules,
        ]);

        $this->processing = true;

        try {
            // Store PDF temporarily
            $path = $this->reportFile->store('legislative-reports', 'private');

            // Parse with AI
            $parser = app(LegislativeReportParserService::class);
            $result = $parser->parseReportPDF(Storage::disk('private')->path($path));

            $this->extractedRequirements = $result['requirements'] ?? [];

            if (count($this->extractedRequirements) > 0) {
                $this->step = 2;
                $this->dispatch('notify', type: 'success', message: 'Found ' . count($this->extractedRequirements) . ' reporting requirements');
            } else {
                $this->dispatch('notify', type: 'warning', message: 'No requirements found. You can add them manually.');
                $this->step = 2;
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to process report: ' . $e->getMessage());
        } finally {
            $this->processing = false;
        }
    }

    public function parseFromText(): void
    {
        if (empty(trim($this->pasteText))) {
            $this->dispatch('notify', type: 'error', message: 'Please paste some text to parse');
            return;
        }

        $this->processing = true;

        try {
            $parser = app(LegislativeReportParserService::class);
            $result = $parser->parseFromText($this->pasteText);

            $newRequirements = $result['requirements'] ?? [];
            $this->extractedRequirements = array_merge($this->extractedRequirements, $newRequirements);

            $this->pasteText = '';
            $this->dispatch('notify', type: 'success', message: 'Found ' . count($newRequirements) . ' additional requirements');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to parse text: ' . $e->getMessage());
        } finally {
            $this->processing = false;
        }
    }

    public function skipPdfUpload(): void
    {
        $this->validate($this->rules);
        $this->step = 2;
    }

    public function removeRequirement(int $index): void
    {
        unset($this->extractedRequirements[$index]);
        $this->extractedRequirements = array_values($this->extractedRequirements);
    }

    public function addManualRequirement(): void
    {
        $this->validate([
            'manualTitle' => 'required|string',
            'manualAgency' => 'required|string',
            'manualTimelineType' => 'required|string',
            'manualDescription' => 'required|string',
            'manualRecipients' => 'required|string',
            'manualCategory' => 'required|in:new,prior_year,ongoing',
        ]);

        $this->extractedRequirements[] = [
            'title' => $this->manualTitle,
            'agency' => $this->manualAgency,
            'timeline_type' => $this->manualTimelineType,
            'timeline_value' => $this->manualTimelineValue,
            'description' => $this->manualDescription,
            'recipients' => $this->manualRecipients,
            'page_reference' => $this->manualPageRef,
            'category' => $this->manualCategory,
        ];

        // Reset form
        $this->manualTitle = '';
        $this->manualAgency = '';
        $this->manualTimelineType = 'days_from_enactment';
        $this->manualTimelineValue = null;
        $this->manualDescription = '';
        $this->manualRecipients = '';
        $this->manualCategory = 'new';
        $this->manualPageRef = '';
        $this->showManualEntry = false;

        $this->dispatch('notify', type: 'success', message: 'Requirement added');
    }

    public function confirmAndSave(): void
    {
        // Create legislative report
        $path = null;
        if ($this->reportFile) {
            $path = $this->reportFile->store('legislative-reports', 'private');
        }

        $report = LegislativeReport::create([
            'fiscal_year' => $this->fiscalYear,
            'report_type' => $this->reportType,
            'report_number' => $this->reportNumber,
            'title' => $this->title,
            'enactment_date' => $this->enactmentDate,
            'document_path' => $path,
            'uploaded_by' => auth()->id(),
            'notes' => $this->notes,
        ]);

        // Create requirements
        foreach ($this->extractedRequirements as $req) {
            $requirement = ReportingRequirement::create([
                'legislative_report_id' => $report->id,
                'category' => $req['category'] ?? 'new',
                'report_title' => $req['title'],
                'responsible_agency' => $req['agency'],
                'timeline_type' => $req['timeline_type'] ?? 'days_from_enactment',
                'timeline_value' => $req['timeline_value'] ?? null,
                'description' => $req['description'],
                'reporting_recipients' => $req['recipients'],
                'source_page_reference' => $req['page_reference'] ?? null,
            ]);

            // Calculate due date
            if ($this->enactmentDate) {
                $dueDate = $requirement->calculateDueDate();
                if ($dueDate) {
                    $requirement->update(['due_date' => $dueDate]);
                    $requirement->createReminders();
                }
            }
        }

        $this->dispatch('notify', type: 'success', message: 'Successfully imported ' . count($this->extractedRequirements) . ' requirements');

        $this->redirect(route('appropriations.index'), navigate: true);
    }

    public function goBack(): void
    {
        if ($this->step > 1) {
            $this->step--;
        } else {
            $this->redirect(route('appropriations.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.appropriations.upload-report', [
            'agencies' => ReportingRequirement::AGENCIES,
            'timelineTypes' => ReportingRequirement::TIMELINE_TYPES,
            'categories' => ReportingRequirement::CATEGORIES,
        ]);
    }
}

