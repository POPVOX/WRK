<?php

namespace App\Livewire\Meetings;

use App\Exceptions\MeetingExtractionException;
use App\Jobs\ExtractMeetingNotes;
use App\Models\Issue;
use App\Models\Meeting;
use App\Models\MeetingAttachment;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Services\MeetingAIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public ?string $extractionMessage = null;

    public string $extractionMessageType = 'info';

    public ?string $extractionRequestId = null;

    public ?string $extractionNotesHash = null;

    public array $suggestedOrganizations = [];

    public array $suggestedPeople = [];

    public array $suggestedIssues = [];

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
        $name = $this->normalizeRelationshipName($this->newOrganization);
        if ($name === '') {
            $this->dispatch('notify', type: 'warning', message: 'Enter an organization name first.');

            return;
        }

        $org = $this->findOrCreateOrganization($name);
        if (! in_array($org->id, $this->selectedOrganizations)) {
            $this->selectedOrganizations[] = $org->id;
        }
        $this->newOrganization = '';
        $this->dispatch('notify', type: 'success', message: "Added {$org->name} to this meeting.");
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
        $name = $this->normalizeRelationshipName($this->newPerson);
        if ($name === '') {
            $this->dispatch('notify', type: 'warning', message: 'Enter an attendee name first.');

            return;
        }

        $person = $this->findOrCreatePerson($name);
        if (! in_array($person->id, $this->selectedPeople)) {
            $this->selectedPeople[] = $person->id;
        }
        $this->newPerson = '';
        $this->dispatch('notify', type: 'success', message: "Added {$person->name} to this meeting.");
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
        $name = $this->normalizeRelationshipName($this->newIssue);
        if ($name === '') {
            return;
        }

        $issue = Issue::query()
            ->whereRaw('LOWER(name) = LOWER(?)', [$name])
            ->first() ?? Issue::create(['name' => $name]);
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
        $this->extractionPaused = ! $this->extractionPaused;
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
            $this->setExtractionMessage('error', 'Please enter meeting notes first.');
            $this->dispatch('notify', type: 'error', message: 'Please enter meeting notes first.');

            return;
        }

        if ($this->extractionPaused) {
            $this->setExtractionMessage('warning', 'AI extraction is paused. Unpause it to continue.');
            $this->dispatch('notify', type: 'warning', message: 'AI extraction is paused. Unpause to continue.');

            return;
        }

        $this->isExtracting = true;
        $this->extractionMessage = null;

        try {
            $extractedData = app(MeetingAIService::class)->extractMeetingData(
                $this->raw_notes,
                (string) config('ai.meeting_extraction_model'),
                35,
                1200,
            );
            $this->applyExtractedData($extractedData);
            $this->reportExtractionApplied();
            $this->isExtracting = false;

            return;
        } catch (ConnectionException $exception) {
            \Log::warning('Interactive meeting extraction exceeded its connection window; queueing fallback', [
                'user_id' => Auth::id(),
                'exception' => $exception,
            ]);
        } catch (MeetingExtractionException $exception) {
            $this->isExtracting = false;
            $this->setExtractionMessage('error', $exception->getMessage());
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());

            return;
        } catch (\Throwable $exception) {
            \Log::error('Interactive meeting extraction failed', [
                'user_id' => Auth::id(),
                'exception' => $exception,
            ]);
            $this->isExtracting = false;
            $message = 'AI extraction failed while processing the notes. Your notes were kept; please retry or check Admin → Metrics.';
            $this->setExtractionMessage('error', $message);
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        $this->queueExtractionInBackground();
    }

    protected function queueExtractionInBackground(): void
    {
        $this->extractionRequestId = (string) Str::uuid();
        $this->extractionNotesHash = hash('sha256', $this->raw_notes);
        $this->setExtractionMessage('info', 'The quick extraction is taking longer than expected, so it is continuing in the background.');

        Cache::put($this->extractionCacheKey(), [
            'status' => 'pending',
            'notes_hash' => $this->extractionNotesHash,
        ], now()->addMinutes(15));

        try {
            ExtractMeetingNotes::dispatch(
                $this->extractionRequestId,
                (int) Auth::id(),
                MeetingAIService::prepareNotes($this->raw_notes),
                $this->extractionNotesHash,
            );
        } catch (\Throwable $exception) {
            \Log::error('Meeting AI extraction could not be queued', [
                'user_id' => Auth::id(),
                'exception' => $exception,
            ]);
            Cache::forget($this->extractionCacheKey());
            $this->isExtracting = false;
            $this->clearExtractionRequest();
            $message = 'AI extraction could not be queued. Your notes were kept; please retry or ask an administrator to check the queue.';
            $this->setExtractionMessage('error', $message);
            $this->dispatch('notify', type: 'error', message: $message);

            return;
        }

        // The test/local sync queue may already have completed the fallback job.
        $this->checkExtractionStatus();
    }

    public function checkExtractionStatus(): void
    {
        if (! $this->isExtracting || ! $this->extractionRequestId) {
            return;
        }

        $result = Cache::get($this->extractionCacheKey());
        $status = $result['status'] ?? 'pending';
        if (in_array($status, ['pending', 'processing'], true)) {
            return;
        }

        Cache::forget($this->extractionCacheKey());
        $this->isExtracting = false;

        if ($status === 'failed') {
            $message = (string) ($result['message'] ?? 'AI extraction failed. Please retry or check Admin → Metrics.');
            $this->setExtractionMessage('error', $message);
            $this->dispatch('notify', type: 'error', message: $message);
            $this->clearExtractionRequest();

            return;
        }

        if (($result['notes_hash'] ?? null) !== $this->extractionNotesHash) {
            $message = 'The notes changed while AI extraction was running, so the older result was not applied. Please run extraction again.';
            $this->setExtractionMessage('warning', $message);
            $this->dispatch('notify', type: 'warning', message: $message);
            $this->clearExtractionRequest();

            return;
        }

        $extractedData = $result['data'] ?? null;
        if (! is_array($extractedData)) {
            $message = 'The background extraction finished without a usable result. Please retry.';
            $this->setExtractionMessage('error', $message);
            $this->dispatch('notify', type: 'error', message: $message);
            $this->clearExtractionRequest();

            return;
        }

        try {
            $this->applyExtractedData($extractedData);
            $this->reportExtractionApplied();
        } catch (\Throwable $exception) {
            \Log::error('Meeting AI extraction result could not be applied', [
                'user_id' => Auth::id(),
                'exception' => $exception,
            ]);
            $message = 'AI extraction finished, but its result could not be applied. Your notes were kept; please retry or check Admin → Metrics.';
            $this->setExtractionMessage('error', $message);
            $this->dispatch('notify', type: 'error', message: $message);
        } finally {
            $this->clearExtractionRequest();
        }
    }

    protected function applyExtractedData(array $extractedData): void
    {
        // Auto-fill title if empty
        if (empty($this->title) && ! empty($extractedData['suggested_title'])) {
            $this->title = $extractedData['suggested_title'];
        }

        // Auto-fill date if suggested
        if (! empty($extractedData['suggested_date'])) {
            $this->meeting_date = $extractedData['suggested_date'];
        }

        // Apply narrative fields before relationships so a relationship problem
        // never hides an otherwise successful extraction.
        $this->aiSummary = $extractedData['ai_summary'] ?? null;
        $this->keyAsk = $extractedData['key_ask'] ?? null;
        $this->commitmentsMade = $extractedData['commitments_made'] ?? null;

        // Relationships remain proposals until the user accepts them.
        $this->suggestedOrganizations = $this->suggestedNamesNotSelected(
            $extractedData['organizations'] ?? [],
            Organization::class,
            $this->selectedOrganizations,
        );
        $this->suggestedPeople = $this->suggestedNamesNotSelected(
            $extractedData['people'] ?? [],
            Person::class,
            $this->selectedPeople,
        );
        $this->suggestedIssues = $this->suggestedNamesNotSelected(
            $extractedData['issues'] ?? [],
            Issue::class,
            $this->selectedIssues,
        );
    }

    protected function reportExtractionApplied(): void
    {
        $suggestionCount = count($this->suggestedOrganizations)
            + count($this->suggestedPeople)
            + count($this->suggestedIssues);
        $message = $suggestionCount > 0
            ? "AI extraction complete. Review and accept or reject {$suggestionCount} relationship ".Str::plural('suggestion', $suggestionCount).' below.'
            : 'AI extraction complete. Review the highlighted details before saving.';
        $this->setExtractionMessage('success', $message);
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function acceptSuggestedOrganization(string $name): void
    {
        $this->acceptSuggestedRelationship('organization', $name, true);
    }

    public function acceptSuggestedOrganizationByKey(string $key): void
    {
        if ($name = $this->suggestedNameByKey($this->suggestedOrganizations, $key)) {
            $this->acceptSuggestedOrganization($name);
        }
    }

    public function acceptSuggestedPerson(string $name): void
    {
        $this->acceptSuggestedRelationship('person', $name, true);
    }

    public function acceptSuggestedPersonByKey(string $key): void
    {
        if ($name = $this->suggestedNameByKey($this->suggestedPeople, $key)) {
            $this->acceptSuggestedPerson($name);
        }
    }

    public function acceptSuggestedIssue(string $name): void
    {
        $this->acceptSuggestedRelationship('issue', $name, true);
    }

    public function acceptSuggestedIssueByKey(string $key): void
    {
        if ($name = $this->suggestedNameByKey($this->suggestedIssues, $key)) {
            $this->acceptSuggestedIssue($name);
        }
    }

    public function rejectSuggestedOrganization(string $name): void
    {
        $this->suggestedOrganizations = $this->removeSuggestedName($this->suggestedOrganizations, $name);
    }

    public function rejectSuggestedOrganizationByKey(string $key): void
    {
        if ($name = $this->suggestedNameByKey($this->suggestedOrganizations, $key)) {
            $this->rejectSuggestedOrganization($name);
        }
    }

    public function rejectSuggestedPerson(string $name): void
    {
        $this->suggestedPeople = $this->removeSuggestedName($this->suggestedPeople, $name);
    }

    public function rejectSuggestedPersonByKey(string $key): void
    {
        if ($name = $this->suggestedNameByKey($this->suggestedPeople, $key)) {
            $this->rejectSuggestedPerson($name);
        }
    }

    public function rejectSuggestedIssue(string $name): void
    {
        $this->suggestedIssues = $this->removeSuggestedName($this->suggestedIssues, $name);
    }

    public function rejectSuggestedIssueByKey(string $key): void
    {
        if ($name = $this->suggestedNameByKey($this->suggestedIssues, $key)) {
            $this->rejectSuggestedIssue($name);
        }
    }

    public function acceptAllSuggestedOrganizations(): void
    {
        $this->acceptAllSuggestedRelationships('organization', $this->suggestedOrganizations);
    }

    public function acceptAllSuggestedPeople(): void
    {
        $this->acceptAllSuggestedRelationships('person', $this->suggestedPeople);
    }

    public function acceptAllSuggestedIssues(): void
    {
        $this->acceptAllSuggestedRelationships('issue', $this->suggestedIssues);
    }

    protected function acceptAllSuggestedRelationships(string $type, array $names): void
    {
        $accepted = 0;
        foreach ($names as $name) {
            $accepted += $this->acceptSuggestedRelationship($type, $name, false) ? 1 : 0;
        }

        if ($accepted > 0) {
            $this->dispatch('notify', type: 'success', message: "Accepted {$accepted} ".Str::plural('suggestion', $accepted).'.');
        }
    }

    protected function acceptSuggestedRelationship(string $type, mixed $name, bool $notify): bool
    {
        $name = $this->normalizeRelationshipName($name);
        if ($name === '') {
            return false;
        }

        try {
            if ($type === 'organization') {
                $model = $this->findOrCreateOrganization($name);
                if (! in_array($model->id, $this->selectedOrganizations)) {
                    $this->selectedOrganizations[] = $model->id;
                }
                $this->suggestedOrganizations = $this->removeSuggestedName($this->suggestedOrganizations, $name);
            } elseif ($type === 'person') {
                $model = $this->findOrCreatePerson($name);
                if (! in_array($model->id, $this->selectedPeople)) {
                    $this->selectedPeople[] = $model->id;
                }
                $this->suggestedPeople = $this->removeSuggestedName($this->suggestedPeople, $name);
            } else {
                $model = $this->findOrCreateIssue($name);
                if (! in_array($model->id, $this->selectedIssues)) {
                    $this->selectedIssues[] = $model->id;
                }
                $this->suggestedIssues = $this->removeSuggestedName($this->suggestedIssues, $name);
            }

            if ($notify) {
                $this->dispatch('notify', type: 'success', message: "Accepted {$name}.");
            }

            return true;
        } catch (\Throwable $exception) {
            \Log::error('Meeting relationship suggestion could not be accepted', [
                'user_id' => Auth::id(),
                'relationship_type' => $type,
                'relationship_name' => $name,
                'exception' => $exception,
            ]);
            $this->dispatch('notify', type: 'error', message: "Could not accept {$name}. Please retry or check Admin → Metrics.");

            return false;
        }
    }

    protected function suggestedNamesNotSelected(array $names, string $modelClass, array $selectedIds): array
    {
        $selectedNames = $modelClass::query()
            ->whereKey($selectedIds)
            ->pluck('name')
            ->map(fn (string $name): string => Str::lower($name))
            ->all();

        return collect($names)
            ->map(fn (mixed $name): string => $this->normalizeRelationshipName($name))
            ->filter()
            ->reject(fn (string $name): bool => in_array(Str::lower($name), $selectedNames, true))
            ->unique(fn (string $name): string => Str::lower($name))
            ->values()
            ->all();
    }

    protected function removeSuggestedName(array $names, mixed $name): array
    {
        $normalized = Str::lower($this->normalizeRelationshipName($name));

        return collect($names)
            ->reject(fn (string $candidate): bool => Str::lower($candidate) === $normalized)
            ->values()
            ->all();
    }

    protected function suggestedNameByKey(array $names, string $key): ?string
    {
        foreach ($names as $name) {
            if (hash_equals(sha1($name), $key)) {
                return $name;
            }
        }

        return null;
    }

    protected function extractionCacheKey(): string
    {
        return ExtractMeetingNotes::cacheKeyFor($this->extractionRequestId, (int) Auth::id());
    }

    protected function clearExtractionRequest(): void
    {
        $this->extractionRequestId = null;
        $this->extractionNotesHash = null;
    }

    protected function setExtractionMessage(string $type, string $message): void
    {
        $this->extractionMessageType = $type;
        $this->extractionMessage = $message;
    }

    protected function normalizeRelationshipName(mixed $name): string
    {
        return Str::limit(Str::squish((string) $name), 255, '');
    }

    protected function findOrCreateOrganization(mixed $name): Organization
    {
        $normalizedName = $this->normalizeRelationshipName($name);

        return Organization::withoutEvents(fn (): Organization => Organization::query()
            ->whereRaw('LOWER(name) = LOWER(?)', [$normalizedName])
            ->first() ?? Organization::create(['name' => $normalizedName]));
    }

    protected function findOrCreatePerson(mixed $name): Person
    {
        $normalizedName = $this->normalizeRelationshipName($name);

        return Person::withoutEvents(fn (): Person => Person::query()
            ->whereRaw('LOWER(name) = LOWER(?)', [$normalizedName])
            ->first() ?? Person::create(['name' => $normalizedName]));
    }

    protected function findOrCreateIssue(mixed $name): Issue
    {
        $normalizedName = $this->normalizeRelationshipName($name);

        return Issue::query()
            ->whereRaw('LOWER(name) = LOWER(?)', [$normalizedName])
            ->first() ?? Issue::create(['name' => $normalizedName]);
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
            $path = $file->store("meetings/{$meeting->id}/attachments", \App\Support\PrivateFiles::DISK);

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
