<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('projects.index') }}" wire:navigate
                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">New Project</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if($isDuplicate)
                <div
                    class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-3 flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="text-blue-700 dark:text-blue-300 text-sm">
                        Duplicating <strong>{{ $sourceProjectName }}</strong>. Continue in chat to update details.
                    </span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create With Chat</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Describe the project naturally. WRK will populate the project profile automatically.
                        </p>
                    </div>
                    @if($isExtracting)
                        <div class="text-xs font-medium text-indigo-600 dark:text-indigo-300">Analyzing...</div>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4 h-72 overflow-y-auto space-y-3">
                    @foreach($chatMessages as $message)
                        <div class="flex {{ ($message['role'] ?? 'assistant') === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div
                                class="max-w-[88%] px-3 py-2 rounded-lg text-sm {{ ($message['role'] ?? 'assistant') === 'user' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600' }}">
                                <div class="whitespace-pre-line">{{ $message['content'] ?? '' }}</div>
                                @if(!empty($message['timestamp']))
                                    <div
                                        class="mt-1 text-[11px] {{ ($message['role'] ?? 'assistant') === 'user' ? 'text-indigo-100' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $message['timestamp'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <form wire:submit.prevent="sendChatMessage" class="mt-4">
                    <div class="flex items-end gap-2">
                        <textarea wire:model="chatInput" rows="3"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        <button type="submit" aria-label="Send"
                            class="h-11 w-11 shrink-0 inline-flex items-center justify-center text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60"
                            @disabled($isExtracting)>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 12h14m-7-7l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project Profile Preview</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Auto-filled from chat. Keep chatting to refine before creating.</p>
                    </div>
                    @if($hasExtracted)
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                            Populated from chat
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="lead_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Lead (POPVOX Staff)
                        </label>
                        <select id="lead_user_id" wire:model="lead_user_id"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Not set</option>
                            @foreach($staffMembers as $staffMember)
                                <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                            @endforeach
                        </select>
                        @if($lead_user_id === null && $lead !== '')
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                AI suggested lead: {{ $lead }} (not matched to staff directory yet)
                            </p>
                        @endif
                        @error('lead_user_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="staff_collaborators" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Collaborating Staff (POPVOX)
                        </label>
                        <select id="staff_collaborators" wire:model="selectedStaffCollaboratorIds" multiple size="5"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach($staffMembers as $staffMember)
                                <option value="{{ $staffMember->id }}" @disabled((int) $lead_user_id === (int) $staffMember->id)>
                                    {{ $staffMember->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Hold Cmd/Ctrl to select multiple staff collaborators.
                        </p>
                        @error('selectedStaffCollaboratorIds')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                        @error('selectedStaffCollaboratorIds.*')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="contact_collaborators_search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            External Collaborators (Contacts)
                        </label>
                        <input id="contact_collaborators_search" type="text" wire:model.live.debounce.300ms="contactCollaboratorSearch"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Type contact name or organization...">

                        @if(mb_strlen(trim($contactCollaboratorSearch)) >= 2)
                            <div class="mt-2 rounded-lg border border-gray-200 dark:border-gray-700 max-h-44 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($contactSearchResults as $contactResult)
                                    <button type="button" wire:click="addContactCollaborator({{ $contactResult->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $contactResult->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            @if($contactResult->organization)
                                                {{ $contactResult->organization->name }}
                                            @elseif($contactResult->email)
                                                {{ $contactResult->email }}
                                            @else
                                                Contact
                                            @endif
                                        </div>
                                    </button>
                                @empty
                                    <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        No matching contacts.
                                    </div>
                                @endforelse
                            </div>
                        @endif

                        @if($selectedContactCollaborators->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($selectedContactCollaborators as $selectedContact)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                        <span>{{ $selectedContact->name }}</span>
                                        <button type="button" wire:click="removeContactCollaborator({{ $selectedContact->id }})"
                                            class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-100"
                                            aria-label="Remove collaborator">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @error('selectedContactCollaboratorIds')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                        @error('selectedContactCollaboratorIds.*')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Name</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $name ?: 'Not set yet' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Type</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $projectTypes[$project_type] ?? ucfirst($project_type) }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Scope</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $scope ?: 'Not set' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Lead</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $leadDisplay ?: 'Not set' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Parent Project</div>
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ optional($parentProjects->firstWhere('id', $parent_project_id))->name ?: 'None' }}
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Status</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 md:col-span-2">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Collaborators</div>
                        @if(!empty($selectedCollaboratorLabels))
                            <div class="font-medium text-gray-900 dark:text-white">{{ implode(', ', $selectedCollaboratorLabels) }}</div>
                        @else
                            <div class="font-medium text-gray-900 dark:text-white">Not set</div>
                        @endif
                    </div>
                </div>

                @if($description)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Description</div>
                        <div class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $description }}</div>
                    </div>
                @endif

                @if($goals)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Goals</div>
                        <div class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $goals }}</div>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                    <a href="{{ route('projects.index') }}" wire:navigate
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                        Cancel
                    </a>
                    <button type="button" wire:click="save"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60"
                        @disabled($name === '' || $isExtracting)
                        wire:loading.attr="disabled"
                        wire:target="save">
                        <span wire:loading.remove wire:target="save">Create Project</span>
                        <span wire:loading wire:target="save">Creating...</span>
                    </button>
                </div>

                @error('name')
                    <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror

                @if($errors->any())
                    <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
                        <p class="text-sm font-medium text-red-700 dark:text-red-300">Could not create project yet:</p>
                        <ul class="mt-1 text-sm text-red-700 dark:text-red-300 list-disc pl-5 space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
