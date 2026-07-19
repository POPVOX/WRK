<div class="desk-page meeting-flow-page" @if($isExtracting) wire:poll.2s="checkExtractionStatus" @endif>
    <x-slot name="header"><h2 class="hidden">{{ __('Log New Meeting') }}</h2></x-slot>

    <x-desk-page-header
        eyebrow="Meetings"
        :title="$editingMeeting ? 'Edit meeting' : 'Capture a meeting'"
        description="Bring whatever you have. Save the source notes first; ask WRK to enhance them when it is useful."
    >
        <x-slot:actions>
            <a href="{{ route('meetings.index') }}" wire:navigate class="desk-button-secondary">← Meetings</a>
        </x-slot:actions>
    </x-desk-page-header>

    <form wire:submit="save" class="meeting-capture-form meeting-flow-grid" id="meeting-capture-form">
        <main class="meeting-capture-main">
            <section class="meeting-document-section meeting-capture-intro">
                <div class="meeting-section-heading">
                    <div>
                        <p class="desk-kicker">1 · Set the context</p>
                        <h2>What kind of conversation was this?</h2>
                    </div>
                    <span class="meeting-source-badge">Human context</span>
                </div>

                <div class="meeting-purpose-grid">
                    @foreach($meetingTypeLabels as $value => $label)
                        <label class="meeting-purpose-option @if($meetingType === $value) is-selected @endif">
                            <input type="radio" wire:model.live="meetingType" value="{{ $value }}">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <label class="meeting-field">
                    <span>What should WRK notice? <em>Optional</em></span>
                    <textarea wire:model="aiFocus" rows="2" placeholder="For example: Pay special attention to what they need from us and who owns each follow-up."></textarea>
                    <small>This instruction changes the AI draft, never your original notes.</small>
                    @error('aiFocus') <small class="meeting-error">{{ $message }}</small> @enderror
                </label>
            </section>

            <section class="meeting-document-section">
                <div class="meeting-section-heading">
                    <div>
                        <p class="desk-kicker">2 · Capture the source</p>
                        <h2>Your notes</h2>
                    </div>
                    <span class="meeting-source-badge">Source of truth</span>
                </div>

                <textarea
                    id="raw_notes"
                    wire:model="raw_notes"
                    rows="16"
                    class="meeting-notes-editor"
                    placeholder="Paste notes, type rough bullets, or record a voice memo. Imperfect notes are welcome."
                ></textarea>
                @error('raw_notes') <small class="meeting-error">{{ $message }}</small> @enderror

                <div x-data="voiceRecorder()" x-init="init()" class="meeting-capture-tools">
                    <button type="button" class="desk-button-secondary" @click="toggleRecording()">
                        <span x-show="!recording">● Record voice memo</span>
                        <span x-show="recording">■ Stop <span x-text="recordingTime"></span></span>
                    </button>
                    <label class="desk-button-secondary meeting-file-button">
                        ↑ Upload audio
                        <input type="file" wire:model="audioFile" accept="audio/*" hidden>
                    </label>
                    @if($audioFile || $audioPath)
                        <button type="button" wire:click="transcribeAudio" wire:loading.attr="disabled" class="desk-link">Add transcript to notes</button>
                    @endif
                    <span x-show="error" x-text="error" class="meeting-error"></span>
                </div>

                @if($audioFile)
                    <p class="meeting-file-note">Attached audio: {{ $audioFile->getClientOriginalName() }}</p>
                @elseif($audioPath)
                    <p class="meeting-file-note">A recorded voice memo is attached.</p>
                @endif

                <div class="meeting-enhance-bar">
                    <div>
                        <strong>Turn rough notes into a reviewable draft</strong>
                        <span>WRK can suggest a recap, people, topics, and follow-ups. Nothing is linked or assigned without your review.</span>
                    </div>
                    <button type="button" wire:click="extractWithAI" wire:loading.attr="disabled" wire:target="extractWithAI" class="desk-button-dark">
                        <span wire:loading.remove wire:target="extractWithAI">✦ Enhance notes</span>
                        <span wire:loading wire:target="extractWithAI">Thinking…</span>
                    </button>
                </div>

                @if($isExtracting)
                    <div class="desk-alert desk-alert-info">✦ Extracting in background... You can keep editing; an older result will not overwrite changed notes.</div>
                @endif
                @if($extractionMessage)
                    <div class="desk-alert desk-alert-{{ $extractionMessageType === 'error' ? 'danger' : ($extractionMessageType === 'success' ? 'success' : 'info') }}">{{ $extractionMessage }}</div>
                @endif
            </section>

            @if($aiSummary || $keyAsk || $commitmentsMade || count($suggestedActions) || count($acceptedSuggestedActions))
                <section class="meeting-document-section meeting-ai-review">
                    <div class="meeting-section-heading">
                        <div>
                            <p class="desk-kicker">3 · Review the AI draft</p>
                            <h2>AI-extracted meeting details</h2>
                            <p>Generated from your notes. Edit the wording and accept only the follow-ups that are real.</p>
                        </div>
                        <span class="meeting-ai-badge">✦ AI draft</span>
                    </div>

                    <label class="meeting-field">
                        <span>Recap</span>
                        <textarea wire:model="aiSummary" rows="5"></textarea>
                    </label>
                    <div class="meeting-two-column-fields">
                        <label class="meeting-field">
                            <span>Key ask</span>
                            <textarea wire:model="keyAsk" rows="4"></textarea>
                        </label>
                        <label class="meeting-field">
                            <span>Commitments and next steps</span>
                            <textarea wire:model="commitmentsMade" rows="4"></textarea>
                        </label>
                    </div>

                    @if(count($suggestedActions))
                        <div class="meeting-suggestion-group">
                            <div class="meeting-suggestion-header">
                                <div><strong>Proposed follow-ups</strong><span>These are not tasks yet.</span></div>
                                <button type="button" wire:click="acceptAllSuggestedActions" class="desk-link">Accept all</button>
                            </div>
                            @foreach($suggestedActions as $action)
                                @php($actionKey = $this->actionSuggestionKey($action))
                                <div class="meeting-action-proposal" wire:key="suggested-action-{{ $actionKey }}">
                                    <div>
                                        <strong>{{ $action['description'] }}</strong>
                                        @if($action['owner_name'] ?? null)<span>Owner mentioned: {{ $action['owner_name'] }}</span>@endif
                                        @if($action['due_date'] ?? null)<span>Due {{ $action['due_date'] }}</span>@endif
                                    </div>
                                    <div>
                                        <button type="button" wire:click="acceptSuggestedAction('{{ $actionKey }}')" class="meeting-accept-button" aria-label="Accept follow-up">✓ Accept</button>
                                        <button type="button" wire:click="rejectSuggestedAction('{{ $actionKey }}')" class="meeting-reject-button" aria-label="Reject follow-up">×</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(count($acceptedSuggestedActions))
                        <div class="meeting-accepted-actions">
                            <strong>Will become tasks when saved</strong>
                            @foreach($acceptedSuggestedActions as $action)
                                @php($actionKey = $this->actionSuggestionKey($action))
                                <div>
                                    <span>✓ {{ $action['description'] }}</span>
                                    <button type="button" wire:click="removeAcceptedSuggestedAction('{{ $actionKey }}')">Undo</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            <section class="meeting-document-section">
                <div class="meeting-section-heading">
                    <div>
                        <p class="desk-kicker">Attachments</p>
                        <h2>Supporting material</h2>
                    </div>
                </div>
                <label class="meeting-dropzone">
                    <span>↑ Add PDFs, images, or documents</span>
                    <input type="file" wire:model="attachments" multiple hidden>
                </label>
                @foreach($attachments as $attachment)
                    <p class="meeting-file-note">{{ $attachment->getClientOriginalName() }}</p>
                @endforeach
            </section>
        </main>

        <aside class="meeting-context-rail">
            <section class="meeting-rail-section">
                <p class="desk-kicker">Meeting record</p>
                <label class="meeting-field">
                    <span>Title</span>
                    <input type="text" wire:model="title" placeholder="Leave blank and WRK can suggest one">
                    @error('title') <small class="meeting-error">{{ $message }}</small> @enderror
                </label>
                <label class="meeting-field">
                    <span>Date</span>
                    <input type="date" wire:model="meeting_date">
                    @error('meeting_date') <small class="meeting-error">{{ $message }}</small> @enderror
                </label>
            </section>

            <section class="meeting-rail-section">
                <div class="meeting-suggestion-header"><strong>POPVOX team</strong></div>
                <select wire:change="addTeamMember($event.target.value); $event.target.value = ''">
                    <option value="">Add a team member…</option>
                    @foreach($teamMembers as $member)
                        @if(!in_array($member->id, $selectedTeamMembers))<option value="{{ $member->id }}">{{ $member->name }}</option>@endif
                    @endforeach
                </select>
                <div class="meeting-chip-list">
                    @foreach($selectedTeamMemberModels as $member)
                        <span>{{ $member->name }} <button type="button" wire:click="removeTeamMember({{ $member->id }})">×</button></span>
                    @endforeach
                </div>
            </section>

            @include('livewire.meetings.partials.relationship-suggestions', [
                'heading' => 'Organizations',
                'inputModel' => 'newOrganization',
                'addMethod' => 'addOrganization',
                'selectedModels' => $selectedOrganizationModels,
                'removeMethod' => 'removeOrganization',
                'suggestions' => $suggestedOrganizations,
                'acceptMethod' => 'acceptSuggestedOrganizationByKey',
                'rejectMethod' => 'rejectSuggestedOrganizationByKey',
                'acceptAllMethod' => 'acceptAllSuggestedOrganizations',
                'wirePrefix' => 'organization',
            ])

            @include('livewire.meetings.partials.relationship-suggestions', [
                'heading' => 'People',
                'inputModel' => 'newPerson',
                'addMethod' => 'addPerson',
                'selectedModels' => $selectedPeopleModels,
                'removeMethod' => 'removePerson',
                'suggestions' => $suggestedPeople,
                'acceptMethod' => 'acceptSuggestedPersonByKey',
                'rejectMethod' => 'rejectSuggestedPersonByKey',
                'acceptAllMethod' => 'acceptAllSuggestedPeople',
                'wirePrefix' => 'person',
            ])

            @include('livewire.meetings.partials.relationship-suggestions', [
                'heading' => 'Topics',
                'inputModel' => 'newIssue',
                'addMethod' => 'addIssue',
                'selectedModels' => $selectedIssueModels,
                'removeMethod' => 'removeIssue',
                'suggestions' => $suggestedIssues,
                'acceptMethod' => 'acceptSuggestedIssueByKey',
                'rejectMethod' => 'rejectSuggestedIssueByKey',
                'acceptAllMethod' => 'acceptAllSuggestedIssues',
                'wirePrefix' => 'issue',
            ])
        </aside>

        <div class="meeting-capture-actions" id="meeting-capture-actions">
            <div>
                @error('save') <span class="meeting-error">{{ $message }}</span> @enderror
                <span>Your notes remain editable after saving.</span>
            </div>
            <div>
                <a href="{{ route('meetings.index') }}" wire:navigate class="desk-link">Cancel</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="desk-button-primary">
                    <span wire:loading.remove wire:target="save">{{ $editingMeeting ? 'Save changes' : 'Save meeting' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </div>
    </form>

    <script>
        function voiceRecorder() {
            return {
                recording: false, error: null, recordingTime: '0:00', mediaRecorder: null,
                audioChunks: [], recognition: null, fullTranscript: '', stream: null,
                timerInterval: null, startTime: null,
                init() {
                    if (!navigator.mediaDevices?.getUserMedia) {
                        this.error = 'Audio recording is not supported in this browser.';
                        return;
                    }
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (SpeechRecognition) {
                        this.recognition = new SpeechRecognition();
                        this.recognition.continuous = true;
                        this.recognition.interimResults = true;
                        this.recognition.onresult = (event) => {
                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                if (event.results[i].isFinal) this.fullTranscript += event.results[i][0].transcript + ' ';
                            }
                        };
                    }
                },
                async toggleRecording() { this.recording ? this.stopRecording() : await this.startRecording(); },
                async startRecording() {
                    this.error = null;
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.mediaRecorder = new MediaRecorder(this.stream);
                        this.audioChunks = []; this.fullTranscript = '';
                        this.mediaRecorder.ondataavailable = event => this.audioChunks.push(event.data);
                        this.mediaRecorder.onstop = () => {
                            const reader = new FileReader();
                            reader.onloadend = () => @this.call('saveRecordedAudio', reader.result);
                            reader.readAsDataURL(new Blob(this.audioChunks, { type: 'audio/webm' }));
                            this.stream?.getTracks().forEach(track => track.stop());
                            if (this.fullTranscript.trim()) {
                                const current = @this.get('raw_notes') || '';
                                @this.set('raw_notes', current + (current ? '\n\n— Voice memo —\n' : '') + this.fullTranscript.trim());
                            }
                        };
                        this.mediaRecorder.start(); this.recording = true; this.startTime = Date.now();
                        this.timerInterval = setInterval(() => {
                            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                            this.recordingTime = `${Math.floor(elapsed / 60)}:${String(elapsed % 60).padStart(2, '0')}`;
                        }, 1000);
                        try { this.recognition?.start(); } catch (_) {}
                    } catch (error) {
                        this.error = error.name === 'NotAllowedError'
                            ? 'Microphone access was denied. Allow it in browser settings and try again.'
                            : 'Could not start recording.';
                    }
                },
                stopRecording() {
                    if (this.mediaRecorder?.state === 'recording') this.mediaRecorder.stop();
                    try { this.recognition?.stop(); } catch (_) {}
                    clearInterval(this.timerInterval); this.recording = false; this.recordingTime = '0:00';
                }
            }
        }
    </script>
</div>
