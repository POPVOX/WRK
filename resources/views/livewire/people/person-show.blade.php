<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $person->name }}
            </h2>
            <a href="{{ route('contacts.index') }}" wire:navigate
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                ← Back to Contacts
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Person Profile Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <form wire:submit="save">
                    <!-- Header with photo and actions -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex gap-4">
                            @if($person->photo_url)
                                <x-avatar :name="$person->name" :photo="$person->photo_url" size="2xl" />
                            @else
                                <x-avatar :name="$person->name" size="2xl" />
                            @endif
                            <div>
                                @if($editing)
                                    <input type="text" wire:model="name"
                                        class="text-2xl font-bold rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('name') <span class="text-red-500 text-sm block">{{ $message }}</span> @enderror
                                @else
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $person->name }}</h3>
                                @endif
                                @if($person->bio)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 max-w-xl">
                                        {{ Str::limit($person->bio, 150) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2">
                            @if($person->linkedin_url && !$person->photo_url && !$editing)
                                <button type="button" wire:click="fetchFromLinkedIn" wire:loading.attr="disabled"
                                    class="px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 rounded-md hover:bg-blue-200 dark:hover:bg-blue-800 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="fetchFromLinkedIn">Fetch LinkedIn</span>
                                    <span wire:loading wire:target="fetchFromLinkedIn">Fetching...</span>
                                </button>
                            @endif
                            @if($editing)
                                <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                    Save
                                </button>
                                <button type="button" wire:click="cancelEditing"
                                    class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                    Cancel
                                </button>
                            @else
                                <button type="button" wire:click="startEditing"
                                    class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button type="button" wire:click="delete"
                                    wire:confirm="Are you sure you want to delete this person?"
                                    class="px-3 py-2 text-sm font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900 rounded-md hover:bg-red-200 dark:hover:bg-red-800">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Profile Fields Grid -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <!-- Organization -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Organization</label>
                            @if($editing)
                                <select wire:model="organization_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">No organization</option>
                                    @foreach($organizations as $org)
                                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                @if($person->organization)
                                    <a href="{{ route('organizations.show', $person->organization) }}" wire:navigate
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $person->organization->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">Not set</span>
                                @endif
                            @endif
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Title</label>
                            @if($editing)
                                <input type="text" wire:model="title" placeholder="e.g., Policy Director"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @else
                                <span class="text-gray-900 dark:text-white">{{ $person->title ?: '—' }}</span>
                            @endif
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Email</label>
                            @if($editing)
                                <input type="email" wire:model="email"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                @if($person->email)
                                    <a href="mailto:{{ $person->email }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $person->email }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">Not set</span>
                                @endif
                            @endif
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Phone</label>
                            @if($editing)
                                <input type="text" wire:model="phone"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @else
                                @if($person->phone)
                                    <a href="tel:{{ $person->phone }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $person->phone }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">Not set</span>
                                @endif
                            @endif
                        </div>

                        <!-- LinkedIn -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">LinkedIn</label>
                            @if($editing)
                                <input type="url" wire:model="linkedin_url" placeholder="https://linkedin.com/in/..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('linkedin_url') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                @if($person->linkedin_url)
                                    <a href="{{ $person->linkedin_url }}" target="_blank"
                                        class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                        </svg>
                                        View Profile
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">Not set</span>
                                @endif
                            @endif
                        </div>

                        <!-- Notes - Full Width -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Notes</label>
                            @if($editing)
                                <textarea wire:model="notes" rows="3"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            @else
                                <p class="text-gray-900 dark:text-white">{{ $person->notes ?: '—' }}</p>
                            @endif
                        </div>

                        <!-- Journalist/Media Info -->
                        <div class="md:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-4 mt-2">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                📰 Media Contact
                                @if($editing)
                                    <label class="inline-flex items-center ml-4">
                                        <input type="checkbox" wire:model="is_journalist"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Is Journalist/Media</span>
                                    </label>
                                @elseif($person->is_journalist)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">Journalist</span>
                                @endif
                            </h4>

                            @if($editing && $is_journalist)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Beat/Coverage Area</label>
                                        <input type="text" wire:model="beat" placeholder="e.g., Technology, Politics, Climate"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Responsiveness</label>
                                        <select wire:model="responsiveness"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Select...</option>
                                            <option value="excellent">Excellent - Always responds quickly</option>
                                            <option value="good">Good - Usually responds</option>
                                            <option value="fair">Fair - Sometimes responds</option>
                                            <option value="poor">Poor - Rarely responds</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Media Notes</label>
                                        <textarea wire:model="media_notes" rows="2" placeholder="Preferred contact method, best times to reach, story preferences..."
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                    </div>
                                </div>
                            @elseif($person->is_journalist)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    @if($person->beat)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Beat:</span>
                                            <span class="text-gray-900 dark:text-white ml-1">{{ $person->beat }}</span>
                                        </div>
                                    @endif
                                    @if($person->responsiveness)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Responsiveness:</span>
                                            <span class="text-gray-900 dark:text-white ml-1 capitalize">{{ $person->responsiveness }}</span>
                                        </div>
                                    @endif
                                    @if($person->media_notes)
                                        <div class="md:col-span-2">
                                            <span class="text-gray-500 dark:text-gray-400">Media Notes:</span>
                                            <p class="text-gray-900 dark:text-white mt-1">{{ $person->media_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Geographic Focus -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Geographic Focus</label>
                            @if($editing)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <livewire:components.geographic-tag-selector
                                        :selectedRegions="$selectedRegions"
                                        :selectedCountries="$selectedCountries"
                                        :selectedUsStates="$selectedUsStates"
                                    />
                                </div>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @forelse($person->geographicTags as $tag)
                                        @php $geo = $tag->geographic; @endphp
                                        @if($geo)
                                            @if($tag->geographic_type === 'region')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                                    🌍 {{ $geo->name }}
                                                </span>
                                            @elseif($tag->geographic_type === 'country')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300">
                                                    🏳️ {{ $geo->name }}
                                                </span>
                                            @elseif($tag->geographic_type === 'us_state')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300">
                                                    🇺🇸 {{ $geo->abbreviation }}
                                                </span>
                                            @endif
                                        @endif
                                    @empty
                                        <span class="text-gray-400 dark:text-gray-500 italic">No geographic focus set</span>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>
                </form>

                {{-- Interaction Panel --}}
                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div
                        class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Log Interaction</h4>
                        <form wire:submit.prevent="addInteraction" class="space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Type</label>
                                    <select wire:model="interaction_type"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @foreach(['call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'note' => 'Note'] as $k => $label)
                                            <option value="{{ $k }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">Date</label>
                                    <input type="datetime-local" wire:model="interaction_date"
                                        class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">Summary</label>
                                <textarea wire:model="interaction_summary" rows="3"
                                    class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Notes about the interaction"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">Add
                                    Interaction</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Interactions Timeline --}}
                <div class="mt-6">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Recent Interactions</h4>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($interactions as $i)
                            <div class="p-4 flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium capitalize">{{ $i->type }}</span>
                                        <span class="text-gray-400">•</span>
                                        <span>{{ optional($i->occurred_at)->format('M j, Y g:ia') }}</span>
                                        @if($i->user)
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-500">by {{ $i->user->name }}</span>
                                        @endif
                                    </div>
                                    @if($i->summary)
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $i->summary }}
                                        </div>
                                    @endif
                                    @if($i->next_action_at || $i->next_action_note)
                                        <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                            Next: {{ optional($i->next_action_at)->format('M j, Y g:ia') }} —
                                            {{ $i->next_action_note }}
                                        </div>
                                    @endif
                                </div>
                                <button wire:click="deleteInteraction({{ $i->id }})"
                                    class="text-xs text-red-600 hover:underline">Delete</button>
                            </div>
                        @empty
                            <div class="p-6 text-sm text-gray-500 dark:text-gray-400">No interactions logged yet.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Stats Row (always visible) -->
                <div
                    class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $meetings->count() }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Meetings</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $topIssues->count() }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Topics Discussed</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">0</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Projects (coming soon)</div>
                    </div>
                </div>
            </div>

            <!-- Top Issues -->
            @if($topIssues->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Areas Discussed</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($topIssues as $issue)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                {{ $issue->name }}
                                <span class="ml-2 text-xs bg-purple-200 dark:bg-purple-800 px-1.5 py-0.5 rounded-full">
                                    {{ $issue->meetings_count }}
                                </span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Projects</h4>
                    <button wire:click="toggleAddProjectModal"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
                </div>
                @if($projects->count())
                    <div class="space-y-2">
                        @foreach($projects as $project)
                            <div class="flex items-center justify-between py-2 group">
                                <div class="flex-1">
                                    <a href="{{ route('projects.show', $project) }}" wire:navigate
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">{{ $project->name }}</a>
                                    @if($project->pivot->role)
                                        <span
                                            class="ml-2 text-xs px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded">{{ $project->pivot->role }}</span>
                                    @endif
                                    @php
                                        $statusColors = [
                                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'on_hold' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            'archived' => 'bg-gray-100 text-gray-600',
                                        ];
                                    @endphp
                                    <span
                                        class="ml-2 text-xs px-1.5 py-0.5 rounded {{ $statusColors[$project->status] ?? 'bg-gray-100' }}">{{ ucfirst($project->status) }}</span>
                                </div>
                                <button wire:click="unlinkProject({{ $project->id }})" wire:confirm="Remove from this project?"
                                    class="opacity-0 group-hover:opacity-100 text-red-600 dark:text-red-400 text-xs">×</button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Not linked to any projects yet.</p>
                @endif
            </div>

            <!-- Documents & Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Documents & Notes</h4>
                </div>

                <!-- Upload Form -->
                <form wire:submit="uploadAttachment" class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex-1">
                            <input type="file" wire:model="newAttachment"
                                class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-green-100 file:text-green-700 dark:file:bg-green-900 dark:file:text-green-300 hover:file:bg-green-200 dark:hover:file:bg-green-800">
                            @error('newAttachment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <input type="text" wire:model="attachmentNotes" placeholder="Add notes (optional)"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm">
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="uploadAttachment">Upload</span>
                            <span wire:loading wire:target="uploadAttachment">Uploading...</span>
                        </button>
                    </div>
                    <div wire:loading wire:target="newAttachment" class="text-sm text-gray-500 mt-2">Processing file...
                    </div>
                </form>

                <!-- Attachments List -->
                @if($attachments->count() > 0)
                    <div class="space-y-2">
                        @foreach($attachments as $attachment)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <a href="{{ $attachment->url }}" target="_blank"
                                            class="text-sm font-medium text-gray-900 dark:text-white hover:text-green-600 dark:hover:text-green-400">
                                            {{ $attachment->original_filename }}
                                        </a>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $attachment->human_size }} • {{ $attachment->created_at->format('M j, Y') }}
                                            @if($attachment->notes)
                                                • {{ $attachment->notes }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <button wire:click="deleteAttachment({{ $attachment->id }})"
                                    wire:confirm="Delete this document?"
                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm">
                                    Delete
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">No documents uploaded yet.</p>
                @endif
            </div>

            <!-- Meetings Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Meetings</h4>
                </div>
                @if($meetings->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Title</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Date</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Organizations</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">View</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($meetings as $meeting)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                                {{ Str::limit($meeting->title ?: 'Untitled Meeting', 40) }}
                                            </div>
                                            @if($meeting->ai_summary)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                    {{ Str::limit($meeting->ai_summary, 50) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {{ $meeting->meeting_date->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-xs">
                                                @if($meeting->organizations->count() > 0)
                                                    {{ $meeting->organizations->take(2)->pluck('name')->join(', ') }}
                                                    @if($meeting->organizations->count() > 2)
                                                        <span class="text-gray-400">+{{ $meeting->organizations->count() - 2 }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400 italic">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                ];
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$meeting->status] ?? $statusColors['pending'] }}">
                                                {{ ucfirst($meeting->status ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('meetings.show', $meeting) }}" wire:navigate
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                                View →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        No meetings recorded yet with this person.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Project Link Modal -->
    @if($showAddProjectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="toggleAddProjectModal"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Link to Project</h3>
                    <div class="space-y-4">
                        <div>
                            <input type="text" wire:model.live.debounce.300ms="projectSearch"
                                placeholder="Search projects..."
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @if($projectResults->count() && !$selectedProjectId)
                                <div
                                    class="mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md max-h-40 overflow-y-auto">
                                    @foreach($projectResults as $project)
                                        <button type="button" wire:click="selectProject({{ $project->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-white">
                                            {{ $project->name }}
                                            <span class="text-xs text-gray-500">{{ ucfirst($project->status) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role
                                (optional)</label>
                            <input type="text" wire:model="projectRole" placeholder="e.g., Primary Contact, Champion"
                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button wire:click="toggleAddProjectModal"
                                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Cancel</button>
                            <button wire:click="linkProject"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                                @if(!$selectedProjectId) disabled @endif>Link</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>