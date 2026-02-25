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

                <form wire:submit.prevent="sendChatMessage" class="mt-4 space-y-3">
                    <textarea wire:model="chatInput" rows="4"
                        class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Example: We are launching a multi-paper academic project under ACADEMIC PAPERS. First paper on civic AI governance due in June, led by Marci, global scope. Main goals are literature review, expert interviews, and publication draft."></textarea>

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                wire:click="$set('chatInput', 'Create a project for a new paper in our academic series.')"
                                class="px-2.5 py-1.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Paper project
                            </button>
                            <button type="button"
                                wire:click="$set('chatInput', 'Global initiative led by Marci, starts next month, targets policy report by end of year.')"
                                class="px-2.5 py-1.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Initiative
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="extractFromText"
                                class="px-3 py-2 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                @disabled($isExtracting)>
                                Re-analyze
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700"
                                @disabled($isExtracting)>
                                Send
                            </button>
                        </div>
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
                        <div class="font-medium text-gray-900 dark:text-white">{{ $lead ?: 'Not set' }}</div>
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
                        @disabled($name === '' || $isExtracting)>
                        Create Project
                    </button>
                </div>

                @error('name')
                    <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>
