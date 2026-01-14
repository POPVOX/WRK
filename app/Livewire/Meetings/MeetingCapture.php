<?php

namespace App\Livewire\Meetings;

use App\Models\Issue;
use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Services\MeetingAIService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Log New Meeting')]
class MeetingCapture extends Component
{
    use WithFileUploads;

    #[Rule('required|date')]
    public $meeting_date;

    #[Rule('nullable|string|max:255')]
    public $title = '';

    #[Rule('nullable|string')]
    public $raw_notes = '';

    #[Rule('nullable|array')]
    public $attachments = [];

    #[Rule('nullable|string')]
    public $newOrganization = '';

    #[Rule('nullable|string')]
    public $newPerson = '';

    #[Rule('nullable|string')]
    public $newIssue = '';

    public array $selectedOrganizations = [];

    public array $selectedPeople = [];

    public array $selectedIssues = [];

    public array $selectedTeamMembers = [];

    // Audio recording/upload properties
    public $audioFile = null;

    public $recordedAudioData = null; // Base64 audio from browser recording

    public $audioPath = null;

    // AI extraction state
    public bool $isProcessing = false;

    public bool $isExtracting = false;

    public bool $extractionPaused = false;

    public ?string $transcript = null;

    // AI-extracted fields (for review before saving)
    public ?string $aiSummary = null;

    public ?string $keyAsk = null;

    public ?string $commitmentsMade = null;

    // Editing mode
    public ?Meeting $editingMeeting = null;

    public function mount(?Meeting $meeting = null)
    {
        if ($meeting && $meeting->exists) {
            // Editing existing meeting
            $this->editingMeeting = $meeting;
            $this->title = $meeting->title ?? '';
            $this->meeting_date = $meeting->meeting_date->format('Y-m-d');
            $this->raw_notes = $meeting->raw_notes ?? '';
            $this->transcript = $meeting->transcript;
            $this->aiSummary = $meeting->ai_summary;
            $this->keyAsk = $meeting->key_ask;
            $this->commitmentsMade = $meeting->commitments_made;
            $this->audioPath = $meeting->audio_path;

            $this->selectedOrganizations = $meeting->organizations->pluck('id')->toArray();
            $this->selectedPeople = $meeting->people->pluck('id')->toArray();
            $this->selectedIssues = $meeting->issues->pluck('id')->toArray();
            $this->selectedTeamMembers = $meeting->teamMembers->pluck('id')->toArray();
        } else {
            // New meeting
            $this->meeting_date = now()->format('Y-m-d');
            $this->selectedTeamMembers = [Auth::id()];
        }
    }

    public function addOrganization()
    {
        if (empty($this->newOrganization)) {
            return;
        }

        $org = Organization::firstOrCreate(['name' => $this->newOrganization]);
        if (! in_array($org->id, $this->selectedOrganizations)) {
            $this->selectedOrganizations[] = $org->id;
        }
        $this->newOrganization = '';
    }

    public function removeOrganization($id)
    {
        $this->selectedOrganizations = array_values(array_filter(
            $this->selectedOrganizations,
            fn ($orgId) => $orgId != $id
        ));
    }

    public function addPerson()
    {
        if (empty($this->newPerson)) {
            return;
        }

        $person = Person::firstOrCreate(['name' => $this->newPerson]);
        if (! in_array($person->id, $this->selectedPeople)) {
            $this->selectedPeople[] = $person->id;
        }
        $this->newPerson = '';
    }

    public function removePerson($id)
    {
        $this->selectedPeople = array_values(array_filter(
            $this->selectedPeople,
            fn ($personId) => $personId != $id
        ));
    }

    public function addIssue()
    {
        if (empty($this->newIssue)) {
            return;
        }

        $issue = Issue::firstOrCreate(['name' => $this->newIssue]);
        if (! in_array($issue->id, $this->selectedIssues)) {
            $this->selectedIssues[] = $issue->id;
        }
        $this->newIssue = '';
    }

    public function removeIssue($id)
    {
        $this->selectedIssues = array_values(array_filter(
            $this->selectedIssues,
            fn ($issueId) => $issueId != $id
        ));
    }

    public function addTeamMember($id)
    {
        if (! in_array($id, $this->selectedTeamMembers)) {
            $this->selectedTeamMembers[] = $id;
        }
    }

    public function removeTeamMember($id)
    {
        $this->selectedTeamMembers = array_values(array_filter(
            $this->selectedTeamMembers,
            fn ($memberId) => $memberId != $id
        ));
    }

    /**
     * Handle uploaded audio file for transcription.
     */
    public function updatedAudioFile()
    {
        if ($this->audioFile) {
            $this->validate([
                'audioFile' => 'file|mimes:mp3,wav,m4a,webm,ogg,mp4|max:25600', // 25MB max for Whisper
            ]);
        }
    }

    /**
     * Process recorded audio from browser (Base64 data).
     */
    public function saveRecordedAudio($base64Data)
    {
        if (empty($base64Data)) {
            return;
        }

        // Decode base64 and save to temp file
        $audioData = base64_decode(preg_replace('/^data:audio\/\w+;base64,/', '', $base64Data));
        $filename = 'recording_'.time().'.webm';
        $path = 'meetings/temp/'.$filename;

        Storage::disk('public')->put($path, $audioData);
        $this->audioPath = $path;

        $this->dispatch('audio-saved');
    }

    /**
     * Transcribe the uploaded or recorded audio.
     */
    public function transcribeAudio()
    {
        $this->isProcessing = true;

        try {
            $aiService = app(MeetingAIService::class);

            // Determine which audio source to use
            $audioPath = null;

            if ($this->audioFile) {
                // Save uploaded file temporarily
                $path = $this->audioFile->store('meetings/temp', 'public');
                $audioPath = $path;
            } elseif ($this->audioPath) {
                $audioPath = $this->audioPath;
            }

            if (! $audioPath) {
                $this->dispatch('notify', type: 'error', message: 'No audio file to transcribe.');
                $this->isProcessing = false;

                return;
            }

            $transcript = $aiService->transcribeAudio($audioPath);

            if ($transcript) {
                $this->transcript = $transcript;
                // Append transcript to raw notes
                if ($this->raw_notes) {
                    $this->raw_notes .= "\n\n--- Transcription ---\n".$transcript;
                } else {
                    $this->raw_notes = $transcript;
                }
                $this->dispatch('notify', type: 'success', message: 'Audio transcribed successfully!');
            } else {
                $this->dispatch('notify', type: 'error', message: 'Failed to transcribe audio.');
            }
        } catch (\Exception $e) {
            \Log::error('Transcription error: '.$e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Error transcribing audio: '.$e->getMessage());
        }

        $this->isProcessing = false;
    }

    public function toggleExtractionPause()
    {
        $this->extractionPaused = !$this->extractionPaused;
        if ($this->extractionPaused) {
            $this->dispatch('notify', type: 'info', message: 'AI extraction paused. Click again to resume.');
        } else {
            $this->dispatch('notify', type: 'info', message: 'AI extraction resumed.');
        }
    }

    /**
     * Extract structured data from notes using AI and auto-fill fields.
     */
    public function extractWithAI()
    {
        if (empty($this->raw_notes)) {
            $this->dispatch('notify', type: 'error', message: 'Please enter meeting notes first.');

            return;
        }

        if ($this->extractionPaused) {
            $this->dispatch('notify', type: 'warning', message: 'AI extraction is paused. Unpause to continue.');

            return;
        }

        $this->isExtracting = true;

        try {
            $aiService = app(MeetingAIService::class);
            $extractedData = $aiService->extractMeetingData($this->raw_notes);

            // Auto-fill title if empty
            if (empty($this->title) && ! empty($extractedData['suggested_title'])) {
                $this->title = $extractedData['suggested_title'];
            }

            // Auto-fill date if suggested
            if (! empty($extractedData['suggested_date'])) {
                $this->meeting_date = $extractedData['suggested_date'];
            }

            // Auto-fill organizations
            foreach ($extractedData['organizations'] ?? [] as $name) {
                $org = Organization::firstOrCreate(['name' => $name]);
                if (! in_array($org->id, $this->selectedOrganizations)) {
                    $this->selectedOrganizations[] = $org->id;
                }
            }

            // Auto-fill people
            foreach ($extractedData['people'] ?? [] as $name) {
                $person = Person::firstOrCreate(['name' => $name]);
                if (! in_array($person->id, $this->selectedPeople)) {
                    $this->selectedPeople[] = $person->id;
                }
            }

            // Auto-fill issues
            foreach ($extractedData['issues'] ?? [] as $name) {
                $issue = Issue::firstOrCreate(['name' => $name]);
                if (! in_array($issue->id, $this->selectedIssues)) {
                    $this->selectedIssues[] = $issue->id;
                }
            }

            // Store AI-generated text fields
            $this->aiSummary = $extractedData['ai_summary'] ?? null;
            $this->keyAsk = $extractedData['key_ask'] ?? null;
            $this->commitmentsMade = $extractedData['commitments_made'] ?? null;

            $this->dispatch('notify', type: 'success', message: 'Fields populated from AI extraction. Review and edit as needed.');
        } catch (\Exception $e) {
            \Log::error('AI extraction error: '.$e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Error extracting data: '.$e->getMessage());
        }

        $this->isExtracting = false;
    }

    public function save()
    {
        $this->validate();

        // Move audio to permanent location if exists and it's a temp path
        $permanentAudioPath = $this->editingMeeting?->audio_path;
        if ($this->audioPath && Storage::disk('public')->exists($this->audioPath) && str_starts_with($this->audioPath, 'livewire-tmp/')) {
            $newPath = 'meetings/'.Auth::id().'/'.basename($this->audioPath);
            Storage::disk('public')->move($this->audioPath, $newPath);
            $permanentAudioPath = $newPath;
        }

        $meetingData = [
            'title' => $this->title,
            'meeting_date' => $this->meeting_date,
            'raw_notes' => $this->raw_notes,
            'audio_path' => $permanentAudioPath,
            'transcript' => $this->transcript,
            'ai_summary' => $this->aiSummary,
            'key_ask' => $this->keyAsk,
            'commitments_made' => $this->commitmentsMade,
        ];

        if ($this->editingMeeting) {
            // Update existing meeting
            $this->editingMeeting->update($meetingData);
            $meeting = $this->editingMeeting;
            $message = 'Meeting updated successfully!';
        } else {
            // Create new meeting
            $meetingData['user_id'] = Auth::id();
            $meetingData['status'] = Meeting::STATUS_NEW;
            $meeting = Meeting::create($meetingData);
            $message = 'Meeting logged successfully!';
        }

        // Sync relationships
        $meeting->organizations()->sync($this->selectedOrganizations);
        $meeting->people()->sync($this->selectedPeople);
        $meeting->issues()->sync($this->selectedIssues);
        $meeting->teamMembers()->sync($this->selectedTeamMembers);

        // Handle file uploads (only for new attachments)
        foreach ($this->attachments as $file) {
            $path = $file->store("meetings/{$meeting->id}/attachments", 'public');

            // Determine file type
            $mimeType = $file->getMimeType();
            $fileType = match (true) {
                str_starts_with($mimeType, 'image/') => MeetingAttachment::TYPE_IMAGE,
                $mimeType === 'application/pdf' => MeetingAttachment::TYPE_PDF,
                default => MeetingAttachment::TYPE_DOCUMENT,
            };

            MeetingAttachment::create([
                'meeting_id' => $meeting->id,
                'file_path' => $path,
                'file_type' => $fileType,
                'original_filename' => $file->getClientOriginalName(),
            ]);
        }

        session()->flash('success', $message);

        return redirect()->route('meetings.show', $meeting);
    }

    public function render()
    {
        $organizations = Organization::orderBy('name')->get();
        $people = Person::orderBy('name')->get();
        $issues = Issue::orderBy('name')->get();
        $teamMembers = User::orderBy('name')->get();

        return view('livewire.meetings.meeting-capture', [
            'organizations' => $organizations,
            'people' => $people,
            'issues' => $issues,
            'teamMembers' => $teamMembers,
            'selectedOrganizationModels' => Organization::whereIn('id', $this->selectedOrganizations)->get(),
            'selectedPeopleModels' => Person::whereIn('id', $this->selectedPeople)->get(),
            'selectedIssueModels' => Issue::whereIn('id', $this->selectedIssues)->get(),
            'selectedTeamMemberModels' => User::whereIn('id', $this->selectedTeamMembers)->get(),
        ]);
    }
}
