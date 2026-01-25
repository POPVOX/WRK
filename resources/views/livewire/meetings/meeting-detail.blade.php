<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Meeting: {{ $meeting->meeting_date->format('F j, Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Action Bar -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <!-- Back Button -->
                    <a href="{{ route('meetings.index') }}"
                        class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </a>

                    <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>

                    <!-- Status Dropdown -->
                    <label class="text-sm text-gray-600 dark:text-gray-400">Status:</label>
                    <select wire:change="updateStatus($event.target.value)"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                        @foreach(\App\Models\Meeting::STATUSES as $s)
                            <option value="{{ $s }}" {{ $meeting->status === $s ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    {{-- AI Prep Button - always visible --}}
                    <button wire:click="openPrepModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        AI Prep
                    </button>

                    @if($editing)
                        <button wire:click="save"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Save Changes
                        </button>
                        <button wire:click="cancelEditing"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                    @else
                        <button wire:click="startEditing"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>

                        <button wire:click="deleteMeeting" wire:confirm="Are you sure you want to delete this meeting?"
                            class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-600 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </button>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- AI Summary -->
                    @if($meeting->ai_summary)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    AI Summary
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ $meeting->ai_summary }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Key Ask -->
                    <div
                        class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-1">Key Ask</h4>
                        @if($editing)
                            <textarea wire:model="keyAsk" rows="2" placeholder="What did they ask for?"
                                class="w-full rounded-md border-yellow-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"></textarea>
                        @else
                            <p class="text-yellow-700 dark:text-yellow-400">{{ $meeting->key_ask ?: '—' }}</p>
                        @endif
                    </div>

                    <!-- Commitments Made -->
                    <div
                        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-1">Commitments Made</h4>
                        @if($editing)
                            <textarea wire:model="commitmentsMade" rows="2" placeholder="What commitments were made?"
                                class="w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"></textarea>
                        @else
                            <p class="text-blue-700 dark:text-blue-400">{{ $meeting->commitments_made ?: '—' }}</p>
                        @endif
                    </div>

                    {{-- Meeting Prep (Before Meeting) --}}
                    <div
                        class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h3
                                    class="text-lg font-semibold text-purple-900 dark:text-purple-300 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    Meeting Prep
                                </h3>
                                @if(!$editing && !$meeting->prep_notes)
                                    <button wire:click="openPrepModal"
                                        class="text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Generate AI Prep
                                    </button>
                                @endif
                            </div>
                            <p class="text-xs text-purple-600 dark:text-purple-400 mb-3">Research and talking points
                                before the meeting</p>

                            @if($editing)
                                <textarea wire:model="prep_notes" rows="6"
                                    placeholder="Add preparation notes, research, talking points..."
                                    class="w-full rounded-lg border-purple-200 dark:border-purple-700 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-800 dark:text-white text-sm"></textarea>
                            @else
                                @if($meeting->prep_notes)
                                    <div class="prose prose-purple dark:prose-invert max-w-none text-sm">
                                        {!! \Str::markdown($meeting->prep_notes) !!}
                                    </div>
                                @else
                                    <p class="text-purple-500 dark:text-purple-400 text-sm italic">No prep notes yet. Click "AI
                                        Prep" to generate insights.</p>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Meeting Agenda --}}
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    Agenda
                                </h3>
                                <button wire:click="toggleAddAgendaItem"
                                    class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Item
                                </button>
                            </div>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mb-4">Topics to discuss and track during the meeting</p>

                            {{-- Add Agenda Item Form --}}
                            @if($showAddAgendaItem)
                                <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Topic *</label>
                                            <input type="text" wire:model="newAgendaTitle" placeholder="Discussion topic..."
                                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                            @error('newAgendaTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Duration (min)</label>
                                                <input type="number" wire:model="newAgendaDuration" placeholder="15"
                                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Presenter</label>
                                                <select wire:model="newAgendaPresenter"
                                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                                    <option value="">—</option>
                                                    @foreach($teamMembers as $member)
                                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                        <textarea wire:model="newAgendaDescription" rows="2" placeholder="Optional details..."
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="toggleAddAgendaItem" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">
                                            Cancel
                                        </button>
                                        <button wire:click="addAgendaItem"
                                            class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                            Add Item
                                        </button>
                                    </div>
                                </div>
                            @endif

                            {{-- Agenda Items List --}}
                            @if($meeting->agendaItems->count() > 0)
                                <div class="space-y-2">
                                    @foreach($meeting->agendaItems as $item)
                                        <div class="p-3 rounded-lg border {{ $item->status === 'completed' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : ($item->status === 'in_progress' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600') }}"
                                            x-data="{ expanded: false }">
                                            <div class="flex items-start gap-3">
                                                {{-- Status indicator --}}
                                                <div class="flex flex-col gap-1 pt-1">
                                                    <button wire:click="reorderAgendaItem({{ $item->id }}, 'up')"
                                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-0.5"
                                                        title="Move up">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                        </svg>
                                                    </button>
                                                    <button wire:click="reorderAgendaItem({{ $item->id }}, 'down')"
                                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-0.5"
                                                        title="Move down">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                {{-- Main content --}}
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-medium text-gray-900 dark:text-white {{ $item->status === 'completed' ? 'line-through text-gray-500' : '' }}">
                                                                {{ $item->title }}
                                                            </span>
                                                            @if($item->duration_minutes)
                                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                    ({{ $item->duration_display }})
                                                                </span>
                                                            @endif
                                                            @if($item->presenter)
                                                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300">
                                                                    {{ $item->presenter->name }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <select wire:change="updateAgendaItemStatus({{ $item->id }}, $event.target.value)"
                                                                class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1 px-2">
                                                                @foreach($agendaStatuses as $key => $label)
                                                                    <option value="{{ $key }}" {{ $item->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                            <button @click="expanded = !expanded" class="text-gray-400 hover:text-gray-600 p-1">
                                                                <svg class="w-4 h-4 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </button>
                                                            <button wire:click="deleteAgendaItem({{ $item->id }})"
                                                                wire:confirm="Delete this agenda item?"
                                                                class="text-red-400 hover:text-red-600 p-1">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    @if($item->description)
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $item->description }}</p>
                                                    @endif

                                                    {{-- Expanded section for notes/decisions --}}
                                                    <div x-show="expanded" x-collapse class="mt-3 space-y-3">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Discussion Notes</label>
                                                            <textarea
                                                                wire:blur="updateAgendaItemNotes({{ $item->id }}, $event.target.value)"
                                                                rows="2"
                                                                class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                placeholder="Notes from discussion...">{{ $item->notes }}</textarea>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Decisions Made</label>
                                                            <textarea
                                                                wire:blur="updateAgendaItemDecisions({{ $item->id }}, $event.target.value)"
                                                                rows="2"
                                                                class="w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                placeholder="Key decisions...">{{ $item->decisions }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Total duration --}}
                                @php
                                    $totalMinutes = $meeting->agendaItems->sum('duration_minutes');
                                @endphp
                                @if($totalMinutes > 0)
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 text-sm text-gray-500 dark:text-gray-400">
                                        Total estimated time: <span class="font-medium">{{ floor($totalMinutes / 60) }}h {{ $totalMinutes % 60 }}m</span>
                                    </div>
                                @endif
                            @else
                                {{-- Free-form agenda notes --}}
                                <div class="space-y-2">
                                    <textarea wire:model.blur="agendaNotes" rows="4"
                                        placeholder="Add agenda topics or click 'Add Item' for structured tracking..."
                                        class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    @if($agendaNotes)
                                        <button wire:click="saveAgendaNotes"
                                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                            Save Notes
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Meeting Notes (During Meeting) --}}
                    <div
                        class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700"
                        x-data="voiceDictation()">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Meeting Notes
                                </h3>
                                
                                @if($editing)
                                    <div class="flex items-center gap-2">
                                        {{-- Voice Dictation Button --}}
                                        <button type="button"
                                            @click="toggleRecording()"
                                            :class="isRecording ? 'bg-red-600 hover:bg-red-700 animate-pulse' : 'bg-gray-600 hover:bg-gray-700'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white rounded-lg transition"
                                            :title="isRecording ? 'Stop recording' : 'Start voice dictation'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                            </svg>
                                            <span x-text="isRecording ? 'Stop' : 'Dictate'" class="hidden sm:inline"></span>
                                        </button>

                                        {{-- AI Summarize Button --}}
                                        <button type="button"
                                            wire:click="summarizeNotes"
                                            wire:loading.attr="disabled"
                                            wire:target="summarizeNotes"
                                            :disabled="$wire.isSummarizing"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg wire:loading.remove wire:target="summarizeNotes" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                            <svg wire:loading wire:target="summarizeNotes" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span class="hidden sm:inline" wire:loading.remove wire:target="summarizeNotes">AI Summarize</span>
                                            <span class="hidden sm:inline" wire:loading wire:target="summarizeNotes">Summarizing...</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Notes taken during or after the
                                meeting. Use voice dictation or type directly.</p>

                            {{-- Recording Indicator with Stop Button --}}
                            <div x-show="isRecording" x-cloak
                                class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                                        <span class="text-sm font-medium text-red-700 dark:text-red-400">Recording...</span>
                                        <span class="text-xs text-red-600 dark:text-red-500">Speak clearly into your microphone.</span>
                                    </div>
                                    <button type="button" @click="stopRecording()"
                                        class="px-3 py-1.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm flex items-center gap-1.5 transition">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <rect x="5" y="5" width="10" height="10" rx="1" />
                                        </svg>
                                        Stop
                                    </button>
                                </div>
                                <div x-show="interimTranscript" class="mt-2 text-sm text-red-600 dark:text-red-400 italic">
                                    <span x-text="interimTranscript"></span>
                                </div>
                            </div>

                            @if($editing)
                                <x-mention-textarea wire:model="raw_notes" rows="8"
                                    placeholder="Meeting notes... Type @ to mention people, organizations, or staff. Or click Dictate to use your voice." />
                            @else
                                @if($meeting->raw_notes)
                                    <div class="prose dark:prose-invert max-w-none">
                                        {!! nl2br(e($meeting->raw_notes)) !!}
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400">No notes yet</p>
                                @endif
                            @endif

                            {{-- AI Summary Display --}}
                            @if($aiSummary)
                                <div class="mt-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                                    <h4 class="text-sm font-semibold text-indigo-800 dark:text-indigo-300 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                        AI Summary
                                    </h4>
                                    <p class="text-sm text-indigo-700 dark:text-indigo-300">{{ $aiSummary }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Transcript -->
                    @if($meeting->transcript)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Transcript</h3>
                                <div class="prose dark:prose-invert max-w-none text-sm">
                                    {!! nl2br(e($meeting->transcript)) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Attachments -->
                    @if($meeting->attachments->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Attachments</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    @foreach($meeting->attachments as $attachment)
                                        <a href="{{ Storage::url($attachment->file_path) }}" target="_blank"
                                            class="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                            <div class="flex items-center">
                                                @if($attachment->file_type === 'image')
                                                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                @elseif($attachment->file_type === 'pdf')
                                                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                @else
                                                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                @endif
                                                <div class="ml-3 truncate">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {{ $attachment->original_filename ?? 'Attachment' }}
                                                    </p>
                                                    @if($attachment->description)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $attachment->description }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Follow-up Actions</h3>

                            <!-- Add Action Form -->
                            <form wire:submit.prevent="addAction" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="md:col-span-2">
                                        <input type="text" wire:model="newActionDescription"
                                            placeholder="Action description..."
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                                    </div>
                                    <div>
                                        <input type="date" wire:model="newActionDueDate"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                                    </div>
                                    <div class="flex gap-2">
                                        <select wire:model="newActionPriority"
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                        <button type="submit"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Actions List -->
                            @if($meeting->actions->isEmpty())
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No actions yet.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach($meeting->actions as $action)
                                                            <div
                                                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg {{ $action->status === 'complete' ? 'opacity-60' : '' }}">
                                                                <div class="flex items-center flex-1">
                                                                    <button wire:click="toggleActionComplete({{ $action->id }})" class="flex-shrink-0 w-5 h-5 rounded border-2 mr-3 flex items-center justify-center
                                                                                                                {{ $action->status === 'complete'
                                        ? 'bg-green-500 border-green-500 text-white'
                                        : 'border-gray-300 dark:border-gray-500' }}">
                                                                        @if($action->status === 'complete')
                                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd"
                                                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                                    clip-rule="evenodd" />
                                                                            </svg>
                                                                        @endif
                                                                    </button>
                                                                    <div class="flex-1">
                                                                        <p
                                                                            class="text-sm font-medium text-gray-900 dark:text-white {{ $action->status === 'complete' ? 'line-through' : '' }}">
                                                                            {{ $action->description }}
                                                                        </p>
                                                                        <div class="flex items-center gap-2 mt-1">
                                                                            @if($action->due_date)
                                                                                <span
                                                                                    class="text-xs {{ $action->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                                                    Due: {{ $action->due_date->format('M j, Y') }}
                                                                                </span>
                                                                            @endif
                                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                                                                                                    @if($action->priority === 'high') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                                                                                                    @elseif($action->priority === 'medium') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                                                                                                    @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300
                                                                                                                    @endif">
                                                                                {{ ucfirst($action->priority) }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <button wire:click="deleteAction({{ $action->id }})"
                                                                    wire:confirm="Delete this action?"
                                                                    class="text-gray-400 hover:text-red-500 ml-2">
                                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">

                    <!-- Meeting Info -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Meeting Info</h3>

                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</dt>
                                    @if($editing)
                                        <input type="text" wire:model="title" placeholder="Meeting title..."
                                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                    @else
                                        <dd class="text-sm text-gray-900 dark:text-white">{{ $meeting->title ?: '—' }}</dd>
                                    @endif
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date & Time</dt>
                                    @if($editing)
                                        <div class="mt-1 flex gap-2 flex-wrap">
                                            <input type="date" wire:model="meeting_date"
                                                class="flex-1 min-w-[140px] rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                            <input type="time" wire:model="meeting_time" placeholder="Start"
                                                class="w-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                            <span class="self-center text-gray-400">-</span>
                                            <input type="time" wire:model="meeting_end_time" placeholder="End"
                                                class="w-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                        </div>
                                    @else
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ $meeting->meeting_date->format('l, F j, Y') }}
                                            @if($meeting->meeting_time)
                                                <span class="text-gray-500 dark:text-gray-400 ml-2">
                                                    {{ \Carbon\Carbon::parse($meeting->meeting_time)->format('g:i A') }}
                                                    @if($meeting->meeting_end_time)
                                                        - {{ \Carbon\Carbon::parse($meeting->meeting_end_time)->format('g:i A') }}
                                                    @endif
                                                </span>
                                            @endif
                                        </dd>
                                    @endif
                                </div>

                                {{-- Meeting Link --}}
                                @if($meeting->meeting_link)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Join Link</dt>
                                        <dd class="mt-1">
                                            <a href="{{ $meeting->meeting_link }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition
                                                          @if($meeting->meeting_link_type === 'zoom')
                                                              bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50
                                                          @elseif($meeting->meeting_link_type === 'google_meet')
                                                              bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/50
                                                          @elseif($meeting->meeting_link_type === 'teams')
                                                              bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300 dark:hover:bg-purple-900/50
                                                          @else
                                                              bg-gray-50 text-gray-700 hover:bg-gray-100 dark:bg-gray-900/30 dark:text-gray-300 dark:hover:bg-gray-700
                                                          @endif">
                                                @if($meeting->meeting_link_type === 'zoom')
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                        <path
                                                            d="M4.5 4.5h9a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 013 15V6a1.5 1.5 0 011.5-1.5zm13.5 3l3.75-2.25a.75.75 0 011.25.56v8.38a.75.75 0 01-1.25.56L18 12.5v-5z" />
                                                    </svg>
                                                    Join Zoom Meeting
                                                @elseif($meeting->meeting_link_type === 'google_meet')
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                        <path
                                                            d="M12 11.25c1.24 0 2.25-1.01 2.25-2.25S13.24 6.75 12 6.75 9.75 7.76 9.75 9s1.01 2.25 2.25 2.25zm0 1.5c-1.50 0-4.5.76-4.5 2.25v1.5h9v-1.5c0-1.49-3-2.25-4.5-2.25z" />
                                                        <rect x="2" y="2" width="20" height="20" rx="4" fill="none"
                                                            stroke="currentColor" stroke-width="2" />
                                                    </svg>
                                                    Join Google Meet
                                                @elseif($meeting->meeting_link_type === 'teams')
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                        <path
                                                            d="M20 12v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5h16zm-8-7a3 3 0 100 6 3 3 0 000-6z" />
                                                    </svg>
                                                    Join Teams Meeting
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    Join Video Call
                                                @endif
                                                <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </dd>
                                    </div>
                                @endif

                                {{-- Location --}}
                                @if($meeting->location && !str_contains($meeting->location, 'http'))
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white flex items-start gap-2">
                                            <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>{{ $meeting->location }}</span>
                                        </dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Lead POPVOX Contact
                                    </dt>
                                    @if($editing)
                                        <div class="mt-1">
                                            <x-autocomplete-select searchUrl="/api/staff/search"
                                                :selectedItem="$meeting->leadContact" wireModel="leadContactId"
                                                placeholder="Search staff..." />
                                        </div>
                                    @else
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            {{ $meeting->leadContact?->name ?? '—' }}
                                        </dd>
                                    @endif
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Logged by</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $meeting->user->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        {{ $meeting->created_at->format('M j, Y g:i A') }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Organizations -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Organizations</h3>
                                @if($editing)
                                    <button wire:click="$toggle('showAddOrganizationForm')"
                                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                                        + Add new
                                    </button>
                                @endif
                            </div>

                            {{-- Add new organization form --}}
                            @if($showAddOrganizationForm)
                                <div
                                    class="mb-4 p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                                    <div class="space-y-2">
                                        <input type="text" wire:model="newOrganizationName" placeholder="Organization name"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                        <select wire:model="newOrganizationType"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                            <option value="other">Type...</option>
                                            <option value="nonprofit">Nonprofit</option>
                                            <option value="government">Government</option>
                                            <option value="company">Company</option>
                                            <option value="association">Association</option>
                                            <option value="foundation">Foundation</option>
                                        </select>
                                        <div class="flex gap-2">
                                            <button wire:click="addNewOrganization"
                                                class="flex-1 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded">
                                                Add Organization
                                            </button>
                                            <button wire:click="$set('showAddOrganizationForm', false)"
                                                class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($editing)
                                <x-chip-input searchUrl="/api/organizations/search" :selectedItems="$meeting->organizations"
                                    wireModel="selectedOrganizations" placeholder="Search organizations..."
                                    colorClass="bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300" />
                            @else
                                @if($meeting->organizations->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach($meeting->organizations as $org)
                                            <a href="{{ route('organizations.show', $org) }}" wire:navigate
                                                class="block p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">
                                                <p class="font-medium text-indigo-900 dark:text-indigo-300">{{ $org->name }}</p>
                                                @if($org->type)
                                                    <p class="text-xs text-indigo-700 dark:text-indigo-400">{{ $org->type }}</p>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No organizations</p>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Attendees -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Attendees</h3>
                                @if($editing)
                                    <button wire:click="$toggle('showAddPersonForm')"
                                        class="text-xs text-green-600 dark:text-green-400 hover:text-green-800">
                                        + Add new
                                    </button>
                                @endif
                            </div>

                            {{-- Add new person form --}}
                            @if($showAddPersonForm)
                                <div
                                    class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                    <div class="space-y-2">
                                        <input type="text" wire:model="newPersonName" placeholder="Name *"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                        <input type="email" wire:model="newPersonEmail" placeholder="Email (optional)"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                        <input type="text" wire:model="newPersonTitle" placeholder="Title (optional)"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                        <select wire:model="newPersonOrganizationId"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                            <option value="">Organization (optional)</option>
                                            @foreach($allOrganizations as $org)
                                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="flex gap-2">
                                            <button wire:click="addNewPerson"
                                                class="flex-1 px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded">
                                                Add Person
                                            </button>
                                            <button wire:click="$set('showAddPersonForm', false)"
                                                class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($editing)
                                <x-chip-input searchUrl="/api/people/search" :selectedItems="$meeting->people"
                                    wireModel="selectedPeople" placeholder="Search people..."
                                    colorClass="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300" />
                            @else
                                @if($meeting->people->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach($meeting->people as $person)
                                            <a href="{{ route('people.show', $person) }}" wire:navigate
                                                class="block p-3 bg-green-50 dark:bg-green-900/30 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition">
                                                <p class="font-medium text-green-900 dark:text-green-300">{{ $person->name }}</p>
                                                @if($person->title)
                                                    <p class="text-xs text-green-700 dark:text-green-400">{{ $person->title }}</p>
                                                @endif
                                                @if($person->organization)
                                                    <p class="text-xs text-green-600 dark:text-green-500">
                                                        {{ $person->organization->name }}</p>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No attendees</p>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Issues -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Issues/Topics</h3>
                            @if($editing)
                                <x-chip-input searchUrl="/api/issues/search" :selectedItems="$meeting->issues"
                                    wireModel="selectedIssues" placeholder="Search issues/topics..."
                                    colorClass="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300" />
                            @else
                                @if($meeting->issues->isNotEmpty())
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($meeting->issues as $issue)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                                {{ $issue->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No issues/topics</p>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Related Meetings -->
                    @if($relatedMeetings->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Related Meetings</h3>
                                <div class="space-y-2">
                                    @foreach($relatedMeetings as $related)
                                        <a href="{{ route('meetings.show', $related) }}"
                                            class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ $related->meeting_date->format('M j, Y') }}
                                            </p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($related->organizations->take(2) as $org)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $org->name }}</span>
                                                @endforeach
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- AI Prep Modal --}}
    @if($showPrepModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" wire:click="closePrepModal">
                </div>

                {{-- Modal Panel --}}
                <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
                    {{-- Header --}}
                    <div class="px-6 py-5 bg-gradient-to-r from-purple-600 to-indigo-600">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-white">AI Meeting Prep</h3>
                                    <p class="text-purple-100 text-sm">Get smart insights for this meeting</p>
                                </div>
                            </div>
                            <button wire:click="closePrepModal" class="p-1.5 rounded-lg hover:bg-white/20 transition">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        @if(!$prepAnalysis)
                            {{-- Input Form --}}
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Additional Context <span class="font-normal text-gray-400">(optional)</span>
                                    </label>
                                    <textarea wire:model="prepInputText" rows="5"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 resize-none"
                                        placeholder="Paste emails, agenda, background info, or any relevant material..."></textarea>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        AI will combine this with meeting attendees, past history, and relevant projects.
                                    </p>
                                </div>
                            </div>
                        @else
                            {{-- Results --}}
                            @if(isset($prepAnalysis['error']))
                                <div
                                    class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-300">
                                    {{ $prepAnalysis['error'] }}
                                </div>
                            @elseif(isset($prepAnalysis['raw']))
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! \Str::markdown($prepAnalysis['raw']) !!}
                                </div>
                            @else
                                <div class="space-y-6 max-h-[60vh] overflow-y-auto pr-2">
                                    {{-- Attendee Analysis --}}
                                    @if(isset($prepAnalysis['attendee_analysis']))
                                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Who You're Meeting With
                                            </h4>
                                            @if(isset($prepAnalysis['attendee_analysis']['key_people']))
                                                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                                    @foreach($prepAnalysis['attendee_analysis']['key_people'] as $person)
                                                        <li class="flex items-center gap-2">
                                                            <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                                                            {{ $person }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if(isset($prepAnalysis['attendee_analysis']['organization_context']))
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                    {{ $prepAnalysis['attendee_analysis']['organization_context'] }}
                                                </p>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Suggested Topics --}}
                                    @if(isset($prepAnalysis['suggested_topics']) && count($prepAnalysis['suggested_topics']) > 0)
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                                Suggested Discussion Topics
                                            </h4>
                                            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                                @foreach($prepAnalysis['suggested_topics'] as $topic)
                                                    <li class="flex items-center gap-2 py-1 px-2 bg-green-50 dark:bg-green-900/20 rounded">
                                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                                        {{ $topic }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Relevant History --}}
                                    @if(isset($prepAnalysis['relevant_history']) && $prepAnalysis['relevant_history'])
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Past Interactions
                                            </h4>
                                            <p
                                                class="text-sm text-gray-700 dark:text-gray-300 bg-blue-50 dark:bg-blue-900/20 rounded p-3">
                                                {{ $prepAnalysis['relevant_history'] }}
                                            </p>
                                        </div>
                                    @endif

                                    {{-- Key Questions --}}
                                    @if(isset($prepAnalysis['key_questions']) && count($prepAnalysis['key_questions']) > 0)
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Key Questions to Ask
                                            </h4>
                                            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                                @foreach($prepAnalysis['key_questions'] as $question)
                                                    <li class="flex items-start gap-2 py-1">
                                                        <span class="text-amber-500 font-bold">?</span>
                                                        {{ $question }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Potential Asks --}}
                                    @if(isset($prepAnalysis['potential_asks']) && count($prepAnalysis['potential_asks']) > 0)
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Potential Asks
                                            </h4>
                                            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                                @foreach($prepAnalysis['potential_asks'] as $ask)
                                                    <li class="flex items-center gap-2">
                                                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                                                        {{ $ask }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Preparation Notes --}}
                                    @if(isset($prepAnalysis['preparation_notes']) && $prepAnalysis['preparation_notes'])
                                        <div
                                            class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                                            <h4 class="font-semibold text-purple-900 dark:text-purple-300 mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                Preparation Notes
                                            </h4>
                                            <p class="text-sm text-purple-800 dark:text-purple-200">
                                                {{ $prepAnalysis['preparation_notes'] }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div
                        class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-3">
                        @if(!$prepAnalysis)
                            <button wire:click="closePrepModal"
                                class="px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white transition">
                                Cancel
                            </button>
                            <button wire:click="analyzePrepMaterial" wire:loading.attr="disabled"
                                class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl hover:from-purple-700 hover:to-indigo-700 disabled:opacity-50 shadow-lg shadow-purple-500/25 transition">
                                <span wire:loading.remove wire:target="analyzePrepMaterial" class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    Generate Prep Brief
                                </span>
                                <span wire:loading wire:target="analyzePrepMaterial" class="flex items-center">
                                    <svg class="animate-spin mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Analyzing...
                                </span>
                            </button>
                        @else
                            <button wire:click="$set('prepAnalysis', null)"
                                class="px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                                Start Over
                            </button>
                            @if(!isset($prepAnalysis['error']))
                                <button wire:click="applyPrepToMeeting"
                                    class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-xl hover:bg-green-700 shadow-lg shadow-green-500/25 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add to Notes
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Voice Dictation Alpine Component
    document.addEventListener('alpine:init', () => {
        Alpine.data('voiceDictation', () => ({
            isRecording: false,
            interimTranscript: '',
            recognition: null,

            init() {
                // Check for Web Speech API support
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                
                if (!SpeechRecognition) {
                    console.warn('Web Speech API not supported in this browser');
                    return;
                }

                this.recognition = new SpeechRecognition();
                this.recognition.continuous = true;
                this.recognition.interimResults = true;
                this.recognition.lang = 'en-US';

                this.recognition.onresult = (event) => {
                    let interim = '';
                    let final = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            final += transcript + ' ';
                        } else {
                            interim += transcript;
                        }
                    }

                    this.interimTranscript = interim;

                    if (final.trim()) {
                        // Send final transcript to Livewire
                        $wire.appendDictation(final.trim());
                        this.interimTranscript = '';
                    }
                };

                this.recognition.onerror = (event) => {
                    console.error('Speech recognition error:', event.error);
                    this.isRecording = false;
                    this.interimTranscript = '';
                    
                    if (event.error === 'not-allowed') {
                        alert('Microphone access denied. Please allow microphone access in your browser settings.');
                    }
                };

                this.recognition.onend = () => {
                    // Stop recording if it ends unexpectedly
                    if (this.isRecording) {
                        // Try to restart if we're still supposed to be recording
                        try {
                            this.recognition.start();
                        } catch (e) {
                            this.isRecording = false;
                        }
                    }
                };
            },

            toggleRecording() {
                if (!this.recognition) {
                    alert('Voice dictation is not supported in this browser. Please use Chrome, Edge, or Safari.');
                    return;
                }

                if (this.isRecording) {
                    this.stopRecording();
                } else {
                    this.startRecording();
                }
            },

            startRecording() {
                try {
                    this.recognition.start();
                    this.isRecording = true;
                    this.interimTranscript = '';
                } catch (e) {
                    console.error('Failed to start recording:', e);
                    alert('Failed to start voice dictation. Please try again.');
                }
            },

            stopRecording() {
                this.recognition.stop();
                this.isRecording = false;
                
                // Append any remaining interim transcript
                if (this.interimTranscript.trim()) {
                    $wire.appendDictation(this.interimTranscript.trim());
                }
                this.interimTranscript = '';
            }
        }));
    });
</script>
@endscript