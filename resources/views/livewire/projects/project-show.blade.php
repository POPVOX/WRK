@push('styles')
    <style>
        .wrk-project-page {
            --wrk-page-bg-top: #f8f9fc;
            --wrk-page-bg-bottom: #f3f5f8;
            --wrk-card-bg: #ffffff;
            --wrk-card-border: rgba(15, 23, 42, 0.14);
            --wrk-text: #0f172a;
            --wrk-muted: #475569;
            --wrk-muted-soft: #64748b;
            --wrk-input-bg: #f3f4f7;
            --wrk-input-border: rgba(15, 23, 42, 0.2);
            --wrk-focus: rgba(79, 70, 229, 0.22);
            --wrk-card-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 10px 24px -18px rgba(15, 23, 42, 0.4);
            background: linear-gradient(180deg, var(--wrk-page-bg-top) 0%, var(--wrk-page-bg-bottom) 100%);
            color: var(--wrk-text);
        }

        .wrk-project-page .wrk-card {
            background-color: var(--wrk-card-bg);
            border: 1px solid var(--wrk-card-border);
            box-shadow: var(--wrk-card-shadow);
        }

        .wrk-project-page .text-gray-500 {
            color: var(--wrk-muted-soft);
        }

        .wrk-project-page .text-gray-600 {
            color: var(--wrk-muted);
        }

        .wrk-project-page .text-gray-700,
        .wrk-project-page .text-gray-800,
        .wrk-project-page .text-gray-900 {
            color: var(--wrk-text);
        }

        .wrk-project-page .border-gray-200 {
            border-color: var(--wrk-card-border);
        }

        .wrk-project-page .bg-gray-50 {
            background-color: #f4f6fb;
        }

        .wrk-project-page input[type='text'],
        .wrk-project-page input[type='url'],
        .wrk-project-page input[type='date'],
        .wrk-project-page textarea,
        .wrk-project-page select {
            background-color: var(--wrk-input-bg);
            border-color: var(--wrk-input-border);
            color: var(--wrk-text);
        }

        .wrk-project-page textarea::placeholder,
        .wrk-project-page input::placeholder {
            color: var(--wrk-muted-soft);
        }

        .wrk-project-page textarea:focus,
        .wrk-project-page input[type='text']:focus,
        .wrk-project-page input[type='url']:focus,
        .wrk-project-page input[type='date']:focus,
        .wrk-project-page select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px var(--wrk-focus);
        }

        .wrk-project-page .bg-indigo-600 {
            background-color: #4f46e5;
        }

        .wrk-project-page .hover\:bg-indigo-700:hover {
            background-color: #4338ca;
        }

        .dark .wrk-project-page {
            --wrk-page-bg-top: #10131a;
            --wrk-page-bg-bottom: #0c1118;
            --wrk-card-bg: #171c25;
            --wrk-card-border: rgba(148, 163, 184, 0.24);
            --wrk-text: #f8fafc;
            --wrk-muted: #cbd5e1;
            --wrk-muted-soft: #94a3b8;
            --wrk-input-bg: #1d2430;
            --wrk-input-border: rgba(148, 163, 184, 0.35);
            --wrk-focus: rgba(129, 140, 248, 0.35);
            --wrk-card-shadow: 0 1px 1px rgba(2, 6, 23, 0.6), 0 12px 28px -20px rgba(2, 6, 23, 0.95);
        }

        .dark .wrk-project-page .dark\:text-gray-400 {
            color: var(--wrk-muted-soft);
        }

        .dark .wrk-project-page .dark\:text-gray-300,
        .dark .wrk-project-page .dark\:text-gray-200,
        .dark .wrk-project-page .dark\:text-white {
            color: var(--wrk-text);
        }

        .dark .wrk-project-page .dark\:bg-gray-800,
        .dark .wrk-project-page .dark\:bg-gray-700,
        .dark .wrk-project-page .dark\:bg-gray-700\/40 {
            background-color: var(--wrk-card-bg);
        }

        .dark .wrk-project-page .dark\:border-gray-700,
        .dark .wrk-project-page .dark\:border-gray-600 {
            border-color: var(--wrk-card-border);
        }
    </style>
@endpush

<div class="wrk-project-page" x-on:open-project-edit-modal.window="$wire.startEditing()">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                @if($project->parent)
                    <a href="{{ route('projects.show', $project->parent) }}" wire:navigate
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 flex items-center gap-1"
                        title="Back to {{ $project->parent->name }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="text-xs hidden sm:inline">{{ \Illuminate\Support\Str::limit($project->parent->name, 20) }}</span>
                    </a>
                @else
                    <a href="{{ route('projects.index') }}" wire:navigate
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                @endif

                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            class="text-sm font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-300">P-{{ str_pad($project->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                            {{ $project->name }}
                        </h2>
                    </div>

                    @php
                        $statusColors = [
                            'planning' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        ];
                        $scopeColors = [
                            'US' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                            'Global' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                            'Comms' => 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
                        ];
                    @endphp

                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$project->status] ?? $statusColors['active'] }}">
                            {{ $statuses[$project->status] ?? ucfirst(str_replace('_', ' ', $project->status)) }}
                        </span>
                        @if($project->scope)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $scopeColors[$project->scope] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $project->scope }}
                            </span>
                        @endif
                        @if($project->lead)
                            <span class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ $project->lead }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button @click="$dispatch('open-project-edit-modal')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                    Edit
                </button>
                <a href="{{ route('projects.duplicate', $project) }}" wire:navigate
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Duplicate
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if($editing)
                <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelEditing"></div>
                        <div
                            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                            <form wire:submit="save" class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Project</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                    <input type="text" wire:model="name"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                    <textarea wire:model="description" rows="3"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                    <select wire:model="status"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex justify-end gap-3 pt-4">
                                    <button type="button" wire:click="cancelEditing"
                                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-md">Cancel</button>
                                    <button type="submit"
                                        class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    @if($project->description)
                        <div class="wrk-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Summary</h3>
                            <p class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $project->description }}</p>
                        </div>
                    @endif

                    <div class="wrk-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project Brief</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Fill this in as a form. WRK stores it as markdown in the backend automatically.
                                </p>
                            </div>
                            <button wire:click="saveProjectBrief"
                                class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                Save Brief
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objective</label>
                                <textarea wire:model.defer="briefObjective" rows="3"
                                    class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="What is this project trying to achieve?"></textarea>
                                @error('briefObjective')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Status</label>
                                <textarea wire:model.defer="briefCurrentStatus" rows="3"
                                    class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Current progress, updates, or blockers"></textarea>
                                @error('briefCurrentStatus')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Open Questions</label>
                                        <button type="button" wire:click="addBriefQuestion"
                                            class="text-xs font-semibold text-indigo-600 dark:text-indigo-300 hover:underline">
                                            + Add
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        @forelse($briefOpenQuestions as $index => $question)
                                            <div class="flex items-center gap-2">
                                                <input type="text" wire:model.defer="briefOpenQuestions.{{ $index }}"
                                                    class="flex-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                    placeholder="Question {{ $index + 1 }}">
                                                <button type="button" wire:click="removeBriefQuestion({{ $index }})"
                                                    class="px-2 py-1 text-xs text-red-600 dark:text-red-300 hover:underline">
                                                    Remove
                                                </button>
                                            </div>
                                            @error('briefOpenQuestions.'.$index)
                                                <p class="text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        @empty
                                            <p class="text-xs text-gray-500 dark:text-gray-400">No questions yet.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Next Steps</label>
                                        <button type="button" wire:click="addBriefNextStep"
                                            class="text-xs font-semibold text-indigo-600 dark:text-indigo-300 hover:underline">
                                            + Add
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        @forelse($briefNextSteps as $index => $step)
                                            <div class="flex items-center gap-2">
                                                <input type="text" wire:model.defer="briefNextSteps.{{ $index }}"
                                                    class="flex-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                    placeholder="Step {{ $index + 1 }}">
                                                <button type="button" wire:click="removeBriefNextStep({{ $index }})"
                                                    class="px-2 py-1 text-xs text-red-600 dark:text-red-300 hover:underline">
                                                    Remove
                                                </button>
                                            </div>
                                            @error('briefNextSteps.'.$index)
                                                <p class="text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        @empty
                                            <p class="text-xs text-gray-500 dark:text-gray-400">No steps yet.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                <textarea wire:model.defer="briefNotes" rows="4"
                                    class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Additional context, references, or comments"></textarea>
                                @error('briefNotes')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="wrk-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5" id="subprojects">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sub-Projects</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Create one sub-project per paper or workstream.</p>
                            </div>
                            <a href="{{ route('projects.create') }}?parent={{ $project->id }}" wire:navigate
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add
                            </a>
                        </div>

                        @php
                            $subStatusColors = [
                                'planning' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                                'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            ];
                        @endphp

                        @if($project->children->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400">No sub-projects yet.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($project->children->sortBy('sort_order') as $child)
                                    <a href="{{ route('projects.show', $child) }}" wire:navigate
                                        class="block p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 bg-gray-50 dark:bg-gray-700/40">
                                        <div class="font-medium text-gray-900 dark:text-white truncate">{{ $child->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full {{ $subStatusColors[$child->status] ?? $subStatusColors['active'] }}">
                                                {{ ucfirst(str_replace('_', ' ', $child->status)) }}
                                            </span>
                                            @if($child->project_type)
                                                <span>{{ ucfirst(str_replace('_', ' ', $child->project_type)) }}</span>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if($project->parent)
                        <div class="wrk-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Parent Project</h3>
                            <a href="{{ route('projects.show', $project->parent) }}" wire:navigate
                                class="text-indigo-600 dark:text-indigo-300 hover:underline font-medium">
                                {{ $project->parent->name }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="wrk-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Documents</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Upload files, add links, or connect existing Box files.</p>
                    </div>
                    <button wire:click="toggleBoxLinkForm"
                        class="px-3 py-1.5 text-sm font-semibold text-white bg-emerald-600 rounded-md hover:bg-emerald-700">
                        {{ $showBoxLinkForm ? 'Close Box Search' : 'Connect Box File' }}
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <form wire:submit="uploadDocument" class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Upload File</h4>
                        <input type="text" wire:model="uploadTitle" placeholder="Optional title"
                            class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <input type="file" wire:model="uploadFile"
                            class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700">
                        @error('uploadFile')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                            class="px-3 py-1.5 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Upload
                        </button>
                    </form>

                    <form wire:submit="addDocumentLink" class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3">
                        <h4 class="font-medium text-gray-900 dark:text-white">Add Link</h4>
                        <input type="text" wire:model="linkTitle" placeholder="Link title"
                            class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('linkTitle')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <input type="url" wire:model="linkUrl" placeholder="https://..."
                            class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('linkUrl')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                            class="px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Add Link
                        </button>
                    </form>
                </div>

                @if($showBoxLinkForm)
                    <div class="p-4 bg-emerald-50/70 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-lg space-y-3">
                        <h4 class="font-medium text-emerald-900 dark:text-emerald-100">Connect Existing Box File</h4>

                        <div class="grid gap-3 md:grid-cols-4">
                            <div class="md:col-span-3">
                                <input type="text" wire:model.live.debounce.300ms="boxItemSearch"
                                    placeholder="Search Box files by name, path, or Box ID"
                                    class="w-full rounded-md border-emerald-300 dark:bg-gray-800 dark:border-emerald-700 dark:text-white">
                            </div>
                            <div>
                                <select wire:model="boxLinkVisibility"
                                    class="w-full rounded-md border-emerald-300 dark:bg-gray-800 dark:border-emerald-700 dark:text-white">
                                    @foreach($boxVisibilityOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if(strlen(trim($boxItemSearch)) < 2)
                            <p class="text-xs text-emerald-700 dark:text-emerald-200">Type at least 2 characters to search.</p>
                        @elseif($boxItemResults->isEmpty())
                            <p class="text-sm text-emerald-800 dark:text-emerald-200">No files found for "{{ $boxItemSearch }}".</p>
                        @else
                            <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                                @foreach($boxItemResults as $item)
                                    <div class="flex items-start justify-between gap-3 p-3 bg-white dark:bg-gray-800 rounded-md border border-emerald-200 dark:border-emerald-800">
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 dark:text-white truncate">{{ $item->name }}</div>
                                            <div class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $item->path_display ?: 'No path available' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Box ID {{ $item->box_item_id }}</div>
                                        </div>
                                        <button wire:click="linkExistingBoxItem({{ $item->id }})"
                                            class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded-md hover:bg-emerald-700">
                                            Connect
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if($projectBoxLinks->isNotEmpty())
                    <div class="space-y-2">
                        <h4 class="font-medium text-gray-900 dark:text-white">Connected Box Files</h4>
                        @foreach($projectBoxLinks as $link)
                            @php
                                $statusClass = match($link->sync_status) {
                                    'synced' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200',
                                    'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-200',
                                    default => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
                                };
                            @endphp
                            <div class="flex items-start justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 rounded-md">
                                <div class="min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white truncate">{{ $link->boxItem?->name ?? 'Unknown Box file' }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $link->boxItem?->path_display ?: 'No path available' }}</div>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="px-2 py-0.5 rounded-full {{ $statusClass }}">{{ strtoupper($link->sync_status) }}</span>
                                        <span>{{ $link->visibility }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button wire:click="syncBoxLink({{ $link->id }})"
                                        class="px-2.5 py-1.5 text-xs font-semibold rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                        Sync
                                    </button>
                                    <button wire:click="unlinkBoxLink({{ $link->id }})"
                                        wire:confirm="Unlink this Box file from the project?"
                                        class="px-2.5 py-1.5 text-xs font-semibold rounded-md bg-red-600 text-white hover:bg-red-700">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white">Project Documents</h4>
                    @if($projectDocuments->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No documents yet.</p>
                    @else
                        @foreach($projectDocuments as $doc)
                            <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 rounded-md">
                                <div class="min-w-0">
                                    <a href="{{ $doc->getAccessUrl() }}" target="_blank"
                                        class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-300 truncate block">
                                        {{ $doc->title }}
                                    </a>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ strtoupper($doc->type) }} · Added {{ $doc->created_at->diffForHumans() }}
                                        @if($doc->boxLink) · Box-linked @endif
                                    </div>
                                </div>
                                <button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Delete this document?"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
