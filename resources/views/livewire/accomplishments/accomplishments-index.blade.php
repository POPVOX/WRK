<div class="min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    @if($isOwnProfile)
                        🏆 My Accomplishments
                    @else
                        🏆 {{ $viewingUser->name }}'s Accomplishments
                    @endif
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Track your wins, celebrate achievements, and document your impact
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($isOwnProfile)
                    <button wire:click="openAddModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Accomplishment
                    </button>
                @else
                    <button wire:click="openRecognizeModal({{ $viewingUserId }})"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        Recognize
                    </button>
                @endif
                <button wire:click="exportAccomplishments"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </button>
            </div>
        </div>

        {{-- Stats Grid --}}
        @if($stats)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats->meetings_attended }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Meetings</div>
                    @if($stats->meetings_organized > 0)
                        <div class="text-xs text-blue-500 mt-1">{{ $stats->meetings_organized }} organized</div>
                    @endif
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $stats->documents_authored }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Documents</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats->projects_owned + $stats->projects_contributed }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Projects</div>
                    <div class="text-xs text-green-500 mt-1">{{ $stats->projects_owned }} led</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $stats->grant_deliverables }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Grant Deliverables</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats->recognition_received }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recognition</div>
                    @if($stats->recognition_given > 0)
                        <div class="text-xs text-yellow-500 mt-1">{{ $stats->recognition_given }} given</div>
                    @endif
                </div>
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-4 text-center text-white">
                    <div class="text-3xl font-bold">{{ number_format($stats->total_impact_score, 1) }}</div>
                    <div class="text-xs opacity-90 mt-1">Impact Score</div>
                </div>
            </div>
        @endif

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2 mb-6">
            {{-- Period Filter --}}
            <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                @foreach(['week' => 'Week', 'month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Year', 'all' => 'All'] as $key => $label)
                    <button wire:click="setPeriod('{{ $key }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition {{ $period === $key ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Type Filter --}}
            <select wire:model.live="type"
                class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-lg focus:ring-indigo-500">
                <option value="">All Types</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            {{-- Visibility Filter (own profile only) --}}
            @if($isOwnProfile)
                <select wire:model.live="visibility"
                    class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-lg focus:ring-indigo-500">
                    <option value="">All Visibility</option>
                    <option value="personal">Personal Only</option>
                    <option value="team">Team</option>
                    <option value="organizational">Organizational</option>
                </select>
            @endif

            {{-- View Mode Toggle --}}
            <button wire:click="toggleViewMode"
                class="ml-auto p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                @if($viewMode === 'list')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                @endif
            </button>
        </div>

        {{-- Accomplishments List --}}
        @if($accomplishments->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="text-6xl mb-4">🏆</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No accomplishments yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2">
                    @if($isOwnProfile)
                        Start tracking your wins by adding an accomplishment!
                    @else
                        No accomplishments recorded for this period.
                    @endif
                </p>
                @if($isOwnProfile)
                    <button wire:click="openAddModal"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Your First Win
                    </button>
                @endif
            </div>
        @else
            <div class="space-y-4">
                @foreach($accomplishments as $accomplishment)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition">
                        <div class="flex items-start gap-4">
                            {{-- Type Emoji --}}
                            <div class="text-3xl">{{ $accomplishment->type_emoji }}</div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $accomplishment->title }}</h3>
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-{{ $accomplishment->type_color }}-100 dark:bg-{{ $accomplishment->type_color }}-900/40 text-{{ $accomplishment->type_color }}-700 dark:text-{{ $accomplishment->type_color }}-300 rounded-full">
                                                {{ $types[$accomplishment->type] ?? $accomplishment->type }}
                                            </span>
                                            @if($accomplishment->is_recognition)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 rounded-full">
                                                    🌟 Recognition
                                                </span>
                                            @endif
                                            @if($accomplishment->visibility === 'personal')
                                                <span class="text-xs text-gray-400">🔒 Personal</span>
                                            @elseif($accomplishment->visibility === 'organizational')
                                                <span class="text-xs text-gray-400">🌐 Public</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $accomplishment->date->format('M j, Y') }}
                                    </div>
                                </div>

                                @if($accomplishment->description)
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $accomplishment->description }}</p>
                                @endif

                                @if($accomplishment->source)
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Source: {{ $accomplishment->source }}
                                    </p>
                                @endif

                                @if($accomplishment->project)
                                    <p class="mt-2 text-xs text-indigo-600 dark:text-indigo-400">
                                        📁 {{ $accomplishment->project->name }}
                                    </p>
                                @endif

                                @if($accomplishment->is_recognition && $accomplishment->addedBy)
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        — Recognized by <span class="font-medium">{{ $accomplishment->addedBy->name }}</span>
                                    </p>
                                @endif

                                {{-- Contributors --}}
                                @if($accomplishment->contributor_users->isNotEmpty())
                                    <div class="mt-3 flex items-center gap-2">
                                        <span class="text-xs text-gray-500">With:</span>
                                        <div class="flex -space-x-2">
                                            @foreach($accomplishment->contributor_users->take(5) as $contributor)
                                                <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-xs font-medium text-indigo-700 dark:text-indigo-300 border-2 border-white dark:border-gray-800" title="{{ $contributor->name }}">
                                                    {{ substr($contributor->name, 0, 1) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Reactions --}}
                                <div class="mt-3 flex items-center gap-2">
                                    @foreach($reactionTypes as $key => $reaction)
                                        @php
                                            $counts = $accomplishment->getReactionCountByType();
                                            $count = $counts[$key] ?? 0;
                                            $userReaction = $accomplishment->getUserReaction(auth()->id());
                                            $isActive = $userReaction && $userReaction->reaction_type === $key;
                                        @endphp
                                        <button wire:click="react({{ $accomplishment->id }}, '{{ $key }}')"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full transition {{ $isActive ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                            {{ $reaction['emoji'] }}
                                            @if($count > 0)
                                                <span>{{ $count }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Actions --}}
                            @if($isOwnProfile || auth()->user()->isAdmin())
                                <div class="flex items-center gap-1">
                                    <button wire:click="deleteAccomplishment({{ $accomplishment->id }})"
                                        wire:confirm="Are you sure you want to delete this accomplishment?"
                                        class="p-2 text-gray-400 hover:text-red-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $accomplishments->links() }}
            </div>
        @endif
    </div>

    {{-- Add Accomplishment Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" wire:click="closeAddModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="saveAccomplishment">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add Accomplishment</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Celebrate your wins!</p>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                                <input type="text" wire:model="newTitle"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500"
                                    placeholder="e.g., Speaking at Congressional briefing">
                                @error('newTitle') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model="newDescription" rows="3"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500"
                                    placeholder="Provide details about this accomplishment..."></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                                    <select wire:model="newType"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500">
                                        @foreach($types as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                                    <input type="date" wire:model="newDate"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                                <input type="text" wire:model="newSource"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500"
                                    placeholder="e.g., Email from Senator's office, Conference">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Visibility</label>
                                <div class="space-y-2">
                                    <label class="flex items-start gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input type="radio" wire:model="newVisibility" value="personal" class="mt-1">
                                        <div>
                                            <div class="font-medium text-sm text-gray-900 dark:text-white">Personal (only me)</div>
                                            <div class="text-xs text-gray-500">Keep this private</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input type="radio" wire:model="newVisibility" value="team" class="mt-1">
                                        <div>
                                            <div class="font-medium text-sm text-gray-900 dark:text-white">Team (all team members)</div>
                                            <div class="text-xs text-gray-500">Share with the team</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <input type="radio" wire:model="newVisibility" value="organizational" class="mt-1">
                                        <div>
                                            <div class="font-medium text-sm text-gray-900 dark:text-white">Organizational (public)</div>
                                            <div class="text-xs text-gray-500">Share in grant reports and public materials</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tag Contributors</label>
                                <select wire:model="newContributors" multiple
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500">
                                    @foreach($teamMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Related Project</label>
                                <select wire:model="newProjectId"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500">
                                    <option value="">None</option>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Attachment</label>
                                <input type="file" wire:model="newAttachment"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="text-xs text-gray-500 mt-1">Upload email screenshot, certificate, etc. (max 10MB)</p>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                            <button type="button" wire:click="closeAddModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                Add Accomplishment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Recognize Modal --}}
    @if($showRecognizeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" wire:click="closeRecognizeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="sendRecognition">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                🌟 Recognize {{ $viewingUser?->name ?? 'Team Member' }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Show your appreciation for their work</p>
                        </div>

                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">What did they do? *</label>
                                <input type="text" wire:model="recognizeTitle"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500"
                                    placeholder="e.g., Delivered excellent presentation">
                                @error('recognizeTitle') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Why was it valuable? *</label>
                                <textarea wire:model="recognizeDescription" rows="4"
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-indigo-500"
                                    placeholder="Explain the impact and why you're recognizing this work..."></textarea>
                                @error('recognizeDescription') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="recognizePublic"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Make this visible to the team</span>
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 ml-6">If unchecked, only the recipient will see this recognition</p>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                            <button type="button" wire:click="closeRecognizeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600 transition">
                                Send Recognition 🌟
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

