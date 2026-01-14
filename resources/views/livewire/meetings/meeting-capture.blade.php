<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Log New Meeting') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <form wire:submit="save" class="p-6 space-y-6">

                    <!-- Meeting Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Meeting Title
                        </label>
                        <input type="text" id="title" wire:model="title"
                            placeholder="e.g., Housing Policy Discussion with City Council"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Meeting Date -->
                    <div>
                        <label for="meeting_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Meeting Date
                        </label>
                        <input type="date" id="meeting_date" wire:model="meeting_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        @error('meeting_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- POPVOX Team Members -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            POPVOX Team Members
                        </label>
                        <div class="flex gap-2 mb-2">
                            <select wire:change="addTeamMember($event.target.value); $event.target.value = ''"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                                <option value="">Select team member to add...</option>
                                @foreach($teamMembers as $member)
                                    @if(!in_array($member->id, $selectedTeamMembers))
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedTeamMemberModels as $member)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    {{ $member->name }}
                                    <button type="button" wire:click="removeTeamMember({{ $member->id }})"
                                        class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Voice Recording Section -->
                    <div
                        x-data="voiceRecorder()"
                        class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700 rounded-lg p-4 border border-purple-200 dark:border-gray-600">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            <svg class="w-5 h-5 inline-block mr-1 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            Voice Memo
                            <span x-show="!hasPermission" class="text-xs text-yellow-600 dark:text-yellow-400 ml-2">(microphone permission required)</span>
                        </label>

                        <div class="flex flex-wrap gap-3 items-center">
                            <!-- Record Button -->
                            <button type="button" @click="toggleRecording()"
                                :class="recording ? 'bg-red-600 hover:bg-red-700' : 'bg-purple-600 hover:bg-purple-700'"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white transition-all duration-200">
                                <template x-if="recording">
                                    <span class="inline-flex items-center">
                                        <span class="relative flex h-3 w-3 mr-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                                        </span>
                                        Stop Recording
                                    </span>
                                </template>
                                <template x-if="!recording">
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="6" />
                                        </svg>
                                        Start Recording
                                    </span>
                                </template>
                            </button>

                            <!-- Recording duration -->
                            <span x-show="recording" x-text="recordingTime" class="text-sm font-mono text-red-600 dark:text-red-400"></span>

                            <!-- Or separator -->
                            <span class="text-gray-500 dark:text-gray-400 text-sm">or</span>

                            <!-- Audio Upload -->
                            <label
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 hover:bg-gray-50 dark:hover:bg-gray-500 cursor-pointer transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Upload Audio
                                <input type="file" wire:model="audioFile" accept="audio/*" class="hidden" />
                            </label>

                            <!-- Transcribe Button -->
                            @if($audioFile || $audioPath)
                                <button type="button" wire:click="transcribeAudio" wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors">
                                    <span wire:loading.remove wire:target="transcribeAudio">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Transcribe
                                    </span>
                                    <span wire:loading wire:target="transcribeAudio">
                                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        Transcribing...
                                    </span>
                                </button>
                            @endif
                        </div>

                        <!-- Recording Error Message -->
                        <template x-if="error">
                            <div class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="error"></div>
                        </template>

                        <!-- Audio file name display -->
                        @if($audioFile)
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                📎 {{ $audioFile->getClientOriginalName() }}
                            </div>
                        @endif

                        @if($audioPath)
                            <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                                ✓ Recording saved
                            </div>
                        @endif
                    </div>

                    <!-- Notes with AI extraction -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="raw_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Meeting Notes
                            </label>
                            <div class="flex gap-2 items-center">
                                {{-- Pause/Resume AI --}}
                                <button type="button" wire:click="toggleExtractionPause"
                                    class="inline-flex items-center px-2 py-1.5 text-xs font-medium rounded-md transition-all {{ $extractionPaused ? 'text-yellow-700 bg-yellow-100 hover:bg-yellow-200 dark:text-yellow-300 dark:bg-yellow-900' : 'text-gray-600 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-700' }}"
                                    title="{{ $extractionPaused ? 'Resume AI extraction' : 'Pause AI extraction' }}">
                                    @if($extractionPaused)
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>
                                {{-- Extract Button --}}
                                <button type="button" wire:click="extractWithAI" wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-white shadow-sm transition-all {{ $extractionPaused ? 'bg-gray-400 cursor-not-allowed' : '' }}"
                                    style="{{ !$extractionPaused ? 'background-color: #7c3aed;' : '' }}"
                                    {{ $extractionPaused ? 'disabled' : '' }}>
                                    <span wire:loading.remove wire:target="extractWithAI">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Extract with AI
                                    </span>
                                    <span wire:loading wire:target="extractWithAI">
                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        Extracting...
                                    </span>
                                </button>
                            </div>
                        </div>
                        <x-mention-textarea id="raw_notes" wire:model.live.debounce.500ms="raw_notes" rows="8"
                            placeholder="Enter your meeting notes here. Type @ to mention people, organizations, or staff. Record/upload audio above, then click 'Extract with AI' to auto-fill..." />
                        @error('raw_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Organizations -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Organizations
                        </label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" wire:model.live="newOrganization" wire:keydown.enter.prevent="addOrganization"
                                placeholder="Type name to add (new or existing)..." list="org-suggestions"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <datalist id="org-suggestions">
                                @foreach($organizations as $org)
                                    <option value="{{ $org->name }}">
                                @endforeach
                            </datalist>
                            <button type="button" wire:click="addOrganization"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                + Add
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedOrganizationModels as $org)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                    {{ $org->name }}
                                    <button type="button" wire:click="removeOrganization({{ $org->id }})"
                                        class="ml-2 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- People -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Attendees
                        </label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" wire:model.live="newPerson" wire:keydown.enter.prevent="addPerson"
                                placeholder="Type name to add (new or existing)..." list="person-suggestions"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <datalist id="person-suggestions">
                                @foreach($people as $person)
                                    <option value="{{ $person->name }}">
                                @endforeach
                            </datalist>
                            <button type="button" wire:click="addPerson"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                + Add
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedPeopleModels as $person)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    {{ $person->name }}
                                    <button type="button" wire:click="removePerson({{ $person->id }})"
                                        class="ml-2 text-green-600 hover:text-green-800 dark:text-green-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Issues -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Issues/Topics
                        </label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" wire:model.live="newIssue" wire:keydown.enter.prevent="addIssue"
                                placeholder="Type issue or topic..." list="issue-suggestions"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <datalist id="issue-suggestions">
                                @foreach($issues as $issue)
                                    <option value="{{ $issue->name }}">
                                @endforeach
                            </datalist>
                            <button type="button" wire:click="addIssue"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Add
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($selectedIssueModels as $issue)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                    {{ $issue->name }}
                                    <button type="button" wire:click="removeIssue({{ $issue->id }})"
                                        class="ml-2 text-purple-600 hover:text-purple-800 dark:text-purple-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Attachments
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label
                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">PDF, Images, Documents</p>
                                </div>
                                <input type="file" wire:model="attachments" multiple class="hidden" />
                            </label>
                        </div>
                        @if($attachments)
                            <div class="mt-3 space-y-2">
                                @foreach($attachments as $index => $file)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <span
                                            class="text-sm text-gray-700 dark:text-gray-300">{{ $file->getClientOriginalName() }}</span>
                                        <button type="button" wire:click="$set('attachments.{{ $index }}', null)"
                                            class="text-red-500 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @error('attachments.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('meetings.index') }}"
                            class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Save Meeting
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Audio Recording Script with Web Speech API Transcription -->
    <script>
        function voiceRecorder() {
            return {
                recording: false,
                hasPermission: true,
                error: null,
                recordingTime: '0:00',
                mediaRecorder: null,
                audioChunks: [],
                recognition: null,
                fullTranscript: '',
                stream: null,
                timerInterval: null,
                startTime: null,

                init() {
                    // Check for browser support
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.error = 'Your browser does not support audio recording.';
                        this.hasPermission = false;
                        return;
                    }

                    // Initialize Web Speech API if available
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (SpeechRecognition) {
                        this.recognition = new SpeechRecognition();
                        this.recognition.continuous = true;
                        this.recognition.interimResults = true;
                        this.recognition.lang = 'en-US';

                        this.recognition.onresult = (event) => {
                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const transcript = event.results[i][0].transcript;
                                if (event.results[i].isFinal) {
                                    this.fullTranscript += transcript + ' ';
                                }
                            }
                        };

                        this.recognition.onerror = (event) => {
                            console.error('Speech recognition error:', event.error);
                        };
                    }
                },

                async toggleRecording() {
                    if (this.recording) {
                        this.stopRecording();
                    } else {
                        await this.startRecording();
                    }
                },

                async startRecording() {
                    this.error = null;

                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.hasPermission = true;
                        this.mediaRecorder = new MediaRecorder(this.stream);
                        this.audioChunks = [];
                        this.fullTranscript = '';

                        this.mediaRecorder.ondataavailable = (event) => {
                            this.audioChunks.push(event.data);
                        };

                        this.mediaRecorder.onstop = () => {
                            const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                            const reader = new FileReader();
                            reader.onloadend = () => {
                                @this.call('saveRecordedAudio', reader.result);
                            };
                            reader.readAsDataURL(audioBlob);

                            // Stop all tracks
                            if (this.stream) {
                                this.stream.getTracks().forEach(track => track.stop());
                            }

                            // Append transcript to notes
                            if (this.fullTranscript.trim()) {
                                const currentNotes = @this.get('raw_notes') || '';
                                const separator = currentNotes ? '\n\n--- Voice Memo ---\n' : '';
                                @this.set('raw_notes', currentNotes + separator + this.fullTranscript.trim());
                            }
                        };

                        this.mediaRecorder.start();
                        this.recording = true;

                        // Start timer
                        this.startTime = Date.now();
                        this.timerInterval = setInterval(() => {
                            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                            const minutes = Math.floor(elapsed / 60);
                            const seconds = elapsed % 60;
                            this.recordingTime = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                        }, 1000);

                        // Start speech recognition if available
                        if (this.recognition) {
                            try {
                                this.recognition.start();
                            } catch (e) {
                                console.log('Speech recognition not started:', e);
                            }
                        }

                    } catch (error) {
                        console.error('Error accessing microphone:', error);
                        this.hasPermission = false;
                        if (error.name === 'NotAllowedError') {
                            this.error = 'Microphone access denied. Please allow microphone access in your browser settings and try again.';
                        } else if (error.name === 'NotFoundError') {
                            this.error = 'No microphone found. Please connect a microphone and try again.';
                        } else {
                            this.error = 'Could not access microphone: ' + error.message;
                        }
                    }
                },

                stopRecording() {
                    if (this.mediaRecorder && this.mediaRecorder.state === 'recording') {
                        this.mediaRecorder.stop();
                    }
                    if (this.recognition) {
                        try {
                            this.recognition.stop();
                        } catch (e) {
                            // Already stopped
                        }
                    }
                    if (this.timerInterval) {
                        clearInterval(this.timerInterval);
                        this.timerInterval = null;
                    }
                    this.recording = false;
                    this.recordingTime = '0:00';
                }
            };
        }
    </script>
</div>