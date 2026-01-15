<div class="min-h-screen">
    <!-- Back Link -->
    <div class="mb-4">
        <a href="{{ route('travel.index') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Travel
        </a>
    </div>

    <!-- Header Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        @if($editing)
            <!-- Edit Mode -->
            <div class="space-y-4">
                <input type="text" wire:model="editName" 
                       class="w-full text-2xl font-bold border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                <textarea wire:model="editDescription" rows="2"
                          placeholder="Trip description..."
                          class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
                <div class="flex items-center gap-4">
                    <select wire:model="editStatus" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        @foreach(\App\Models\Trip::getStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button wire:click="saveTrip" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    <button wire:click="cancelEditing" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Cancel</button>
                </div>
            </div>
        @else
            <!-- View Mode -->
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    <span class="text-4xl">{{ $trip->country_flag }}</span>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $trip->name }}</h1>
                        <div class="flex items-center gap-3 mt-1 text-gray-600 dark:text-gray-400">
                            <span>{{ \App\Models\Trip::getTypeIcons()[$trip->type] ?? '✈️' }} {{ \App\Models\Trip::getTypeOptions()[$trip->type] ?? $trip->type }}</span>
                            <span>•</span>
                            <span>{{ $trip->start_date->format('M j') }} - {{ $trip->end_date->format('M j, Y') }}</span>
                            <span>•</span>
                            <span>{{ $trip->duration }} days</span>
                        </div>
                        @if($trip->description)
                            <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $trip->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 text-sm font-medium rounded-full {{ \App\Models\Trip::getStatusColors()[$trip->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ \App\Models\Trip::getStatusOptions()[$trip->status] ?? $trip->status }}
                    </span>
                    @if($canEdit)
                        <button wire:click="startEditing" class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 border border-gray-300 dark:border-gray-600 rounded-lg">
                            Edit
                        </button>
                    @endif
                </div>
            </div>

            <!-- Travelers -->
            @if($trip->travelers->isNotEmpty())
                <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Travelers:</span>
                    <div class="flex -space-x-2">
                        @foreach($trip->travelers as $traveler)
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-sm font-medium border-2 border-white dark:border-gray-800"
                                 title="{{ $traveler->name }} {{ $traveler->pivot->role === 'lead' ? '(Lead)' : '' }}">
                                {{ substr($traveler->name, 0, 1) }}
                            </div>
                        @endforeach
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $trip->travelers->pluck('name')->join(', ') }}
                    </span>
                </div>
            @endif

            <!-- Compliance Warnings -->
            @if($trip->hasComplianceIssues())
                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400 font-medium mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Action Required
                    </div>
                    <ul class="text-sm text-amber-600 dark:text-amber-400 space-y-1">
                        @if($trip->step_registration_required && !$trip->step_registration_completed)
                            <li class="flex items-center justify-between">
                                <span>• Complete STEP registration</span>
                                <button wire:click="markStepCompleted" class="text-xs text-amber-700 hover:underline">Mark Complete</button>
                            </li>
                        @endif
                        @if($trip->travel_insurance_required && !$trip->travel_insurance_confirmed)
                            <li class="flex items-center justify-between">
                                <span>• Confirm travel insurance</span>
                                <button wire:click="markInsuranceConfirmed" class="text-xs text-amber-700 hover:underline">Confirm</button>
                            </li>
                        @endif
                        @if($trip->approval_required && !$trip->approved_at)
                            <li>• Awaiting approval</li>
                        @endif
                    </ul>
                </div>
            @endif
        @endif
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex gap-6 overflow-x-auto" aria-label="Tabs">
            @foreach([
                'overview' => 'Overview',
                'itinerary' => 'Itinerary',
                'expenses' => 'Expenses',
                'sponsorship' => 'Sponsorship',
                'events' => 'Events',
                'documents' => 'Documents',
                'checklist' => 'Checklist',
                'notes' => 'Notes',
            ] as $key => $label)
                <button wire:click="setTab('{{ $key }}')"
                        class="pb-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition {{ $activeTab === $key 
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="space-y-6">
        @if($activeTab === 'overview')
            <!-- Overview Tab -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Stats -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Trip Summary</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-1">{{ $trip->destinations->count() > 1 ? 'Destinations' : 'Destination' }}</dt>
                            <dd class="space-y-1">
                                @forelse($trip->destinations->sortBy('order') as $dest)
                                    <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                                        <span>{{ $dest->country_flag }}</span>
                                        <span>{{ $dest->city }}@if($dest->state_province), {{ $dest->state_province }}@endif</span>
                                        <span class="text-xs text-gray-500">({{ $dest->arrival_date->format('M j') }}-{{ $dest->departure_date->format('j') }})</span>
                                    </div>
                                @empty
                                    <span class="text-gray-500">No destinations set</span>
                                @endforelse
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Duration</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $trip->duration }} days</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Travelers</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $trip->travelers->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Flights</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $trip->segments->where('type', 'flight')->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Hotels</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $trip->lodging->count() }}</dd>
                        </div>
                        @if($trip->project)
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Project</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    <a href="{{ route('projects.show', $trip->project) }}" class="text-indigo-600 hover:underline">
                                        {{ $trip->project->name }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Expenses Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Expenses</h3>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                        ${{ number_format($expenseStats['total'], 2) }}
                    </div>
                    @if($expenseStats['pending_reimbursement'] > 0)
                        <div class="text-sm text-amber-600 dark:text-amber-400">
                            ${{ number_format($expenseStats['pending_reimbursement'], 2) }} pending reimbursement
                        </div>
                    @endif
                    <button wire:click="setTab('expenses')" class="mt-4 text-sm text-indigo-600 hover:underline">
                        View details →
                    </button>
                </div>

                <!-- Sponsorship Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Sponsorship</h3>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        ${{ number_format($sponsorshipStats['total_expected'], 2) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        ${{ number_format($sponsorshipStats['total_received'], 2) }} received
                    </div>
                    @if($sponsorshipStats['pending_invoices'] > 0)
                        <div class="text-sm text-amber-600 dark:text-amber-400 mt-2">
                            {{ $sponsorshipStats['pending_invoices'] }} pending invoice(s)
                        </div>
                    @endif
                    <button wire:click="setTab('sponsorship')" class="mt-4 text-sm text-indigo-600 hover:underline">
                        View details →
                    </button>
                </div>
            </div>

            <!-- Timeline Preview -->
            @if(count($timeline) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Itinerary Preview</h3>
                        <button wire:click="setTab('itinerary')" class="text-sm text-indigo-600 hover:underline">
                            View full itinerary →
                        </button>
                    </div>
                    <div class="space-y-4">
                        @foreach(array_slice($timeline, 0, 3, true) as $date => $events)
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ \Carbon\Carbon::parse($date)->format('l, F j') }}
                                </h4>
                                <div class="space-y-2 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                                    @foreach(array_slice($events, 0, 3) as $event)
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg">{{ $event['icon'] }}</span>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $event['title'] }}</span>
                                                @if($event['subtitle'])
                                                    <span class="text-sm text-gray-500 dark:text-gray-400"> • {{ $event['subtitle'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        @elseif($activeTab === 'itinerary')
            <!-- Destinations Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">📍 Destinations</h3>
                    @if($canEdit)
                        <button wire:click="openAddDestination" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Destination
                        </button>
                    @endif
                </div>

                @if($trip->destinations->isEmpty())
                    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                        <p>No destinations added yet</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($trip->destinations->sortBy('order') as $index => $destination)
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-2xl">{{ $destination->country_flag }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $destination->city }}@if($destination->state_province), {{ $destination->state_province }}@endif
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $countries[$destination->country] ?? $destination->country }}
                                        </span>
                                        @if($destination->state_dept_level && $destination->state_dept_level >= 3)
                                            <span class="px-2 py-0.5 text-xs rounded-full {{ $destination->state_dept_level == 4 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                                Level {{ $destination->state_dept_level }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $destination->arrival_date->format('M j') }} - {{ $destination->departure_date->format('M j, Y') }}
                                        ({{ $destination->duration }} days)
                                    </div>
                                </div>
                                @if($canEdit)
                                    <div class="flex items-center gap-1">
                                        @if($destination->order > 1)
                                            <button wire:click="moveDestinationUp({{ $destination->id }})" class="p-1 text-gray-400 hover:text-gray-600" title="Move up">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            </button>
                                        @endif
                                        @if($destination->order < $trip->destinations->max('order'))
                                            <button wire:click="moveDestinationDown({{ $destination->id }})" class="p-1 text-gray-400 hover:text-gray-600" title="Move down">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click="deleteDestination({{ $destination->id }})" 
                                                wire:confirm="Remove this destination?"
                                                class="p-1 text-gray-400 hover:text-red-600 ml-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Travel Segments -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white">✈️ Travel Segments</h3>
                    @if($canEdit)
                        <button wire:click="openAddSegment" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Segment
                        </button>
                    @endif
                </div>

                @if($trip->segments->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <div class="text-4xl mb-2">✈️</div>
                        <p>No travel segments added yet</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($trip->segments as $segment)
                            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-2xl">{{ $segment->type_icon }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $segment->route }}</span>
                                        @if($segment->flight_number)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $segment->flight_number }}</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $segment->departure_datetime->format('M j, g:i A') }} → {{ $segment->arrival_datetime->format('M j, g:i A') }}
                                        @if($segment->duration)
                                            <span class="text-gray-400">({{ $segment->duration }})</span>
                                        @endif
                                    </div>
                                    @if($segment->confirmation_number)
                                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            Confirmation: {{ $segment->confirmation_number }}
                                        </div>
                                    @endif
                                </div>
                                @if($canEdit)
                                    <button wire:click="deleteSegment({{ $segment->id }})" 
                                            wire:confirm="Delete this segment?"
                                            class="text-gray-400 hover:text-red-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Lodging -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Lodging</h3>
                @if($trip->lodging->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <div class="text-4xl mb-2">🏨</div>
                        <p>No lodging added yet</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($trip->lodging as $lodging)
                            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-2xl">🏨</span>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $lodging->property_name }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $lodging->city }}, {{ $lodging->country }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $lodging->check_in_date->format('M j') }} - {{ $lodging->check_out_date->format('M j') }}
                                        ({{ $lodging->nights_count }} nights)
                                    </div>
                                    @if($lodging->confirmation_number)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            Confirmation: {{ $lodging->confirmation_number }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        @elseif($activeTab === 'checklist')
            <!-- Checklist Tab -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Trip Preparation Checklist</h3>

                <!-- Add Item -->
                <div class="flex gap-2 mb-6">
                    <input type="text" wire:model="newChecklistItem" 
                           wire:keydown.enter="addChecklistItem"
                           placeholder="Add checklist item..."
                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <select wire:model="newChecklistCategory" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        @foreach(\App\Models\TripChecklist::getCategoryOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button wire:click="addChecklistItem" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Add
                    </button>
                </div>

                <!-- Checklist Items by Category -->
                @php
                    $groupedChecklist = $trip->checklists->groupBy('category');
                @endphp

                @if($trip->checklists->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <div class="text-4xl mb-2">📋</div>
                        <p>No checklist items yet. Add items above or generate AI suggestions.</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($groupedChecklist as $category => $items)
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                    {{ \App\Models\TripChecklist::getCategoryOptions()[$category] ?? $category }}
                                    <span class="text-xs text-gray-400">({{ $items->where('is_completed', true)->count() }}/{{ $items->count() }})</span>
                                </h4>
                                <div class="space-y-2">
                                    @foreach($items as $item)
                                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <input type="checkbox" 
                                                   wire:click="toggleChecklistItem({{ $item->id }})"
                                                   {{ $item->is_completed ? 'checked' : '' }}
                                                   class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                            <span class="{{ $item->is_completed ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white' }}">
                                                {{ $item->item }}
                                            </span>
                                            @if($item->ai_suggested)
                                                <span class="text-xs text-purple-600 bg-purple-100 dark:bg-purple-900/50 px-2 py-0.5 rounded">AI</span>
                                            @endif
                                            <button wire:click="deleteChecklistItem({{ $item->id }})" 
                                                    class="ml-auto text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        @elseif($activeTab === 'expenses')
            <!-- Expenses Tab -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Expenses</h3>
                @if($trip->expenses->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <div class="text-4xl mb-2">💰</div>
                        <p>No expenses recorded yet</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($trip->expenses as $expense)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $expense->expense_date->format('M j') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            {{ $expense->category_icon }} {{ \App\Models\TripExpense::getCategoryOptions()[$expense->category] ?? $expense->category }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $expense->description }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${{ number_format($expense->amount, 2) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full {{ \App\Models\TripExpense::getReimbursementStatusColors()[$expense->reimbursement_status] ?? 'bg-gray-100' }}">
                                                {{ \App\Models\TripExpense::getReimbursementStatusOptions()[$expense->reimbursement_status] ?? $expense->reimbursement_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">Total</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 dark:text-white">${{ number_format($expenseStats['total'], 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

        @elseif($activeTab === 'sponsorship')
            <!-- Sponsorship Tab -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Sponsorships</h3>
                @if($trip->sponsorships->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <div class="text-4xl mb-2">🤝</div>
                        <p>No sponsorships added yet</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($trip->sponsorships as $sponsorship)
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $sponsorship->organization->name }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ \App\Models\TripSponsorship::getTypeOptions()[$sponsorship->type] ?? $sponsorship->type }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full {{ \App\Models\TripSponsorship::getPaymentStatusColors()[$sponsorship->payment_status] ?? 'bg-gray-100' }}">
                                        {{ \App\Models\TripSponsorship::getPaymentStatusOptions()[$sponsorship->payment_status] ?? $sponsorship->payment_status }}
                                    </span>
                                </div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                    ${{ number_format($sponsorship->amount, 2) }}
                                </div>
                                @if($sponsorship->coverage_list)
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Covers: {{ implode(', ', $sponsorship->coverage_list) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        @elseif($activeTab === 'notes')
            <!-- Notes Tab -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Trip Notes & Debrief</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Debrief Notes</label>
                        <p class="text-gray-600 dark:text-gray-400">{{ $trip->debrief_notes ?: 'No debrief notes yet.' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Outcomes</label>
                        <p class="text-gray-600 dark:text-gray-400">{{ $trip->outcomes ?: 'No outcomes recorded yet.' }}</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Other tabs placeholder -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center text-gray-500">
                Coming soon...
            </div>
        @endif
    </div>

    <!-- Add Segment Modal -->
    @if($showAddSegment)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Travel Segment</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select wire:model="segmentType" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            @foreach(\App\Models\TripSegment::getTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carrier</label>
                            <input type="text" wire:model="segmentCarrier" placeholder="e.g., United" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Flight/Train #</label>
                            <input type="text" wire:model="segmentNumber" placeholder="e.g., UA 234" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                            <input type="text" wire:model="segmentDepartureLocation" placeholder="DCA" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                            <input type="text" wire:model="segmentArrivalLocation" placeholder="NBO" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departure</label>
                            <input type="datetime-local" wire:model="segmentDepartureDatetime" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arrival</label>
                            <input type="datetime-local" wire:model="segmentArrivalDatetime" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmation #</label>
                        <input type="text" wire:model="segmentConfirmation" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="closeAddSegment" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Cancel</button>
                    <button wire:click="saveSegment" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Segment</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Destination Modal -->
    @if($showAddDestination)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Destination</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City *</label>
                        <input type="text" wire:model="destCity" placeholder="e.g., Paris" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        @error('destCity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State/Province</label>
                        <input type="text" wire:model="destStateProvince" placeholder="Optional" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country *</label>
                        <select wire:model="destCountry" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select country...</option>
                            @foreach($countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('destCountry') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arrival Date *</label>
                            <input type="date" wire:model="destArrivalDate" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            @error('destArrivalDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departure Date *</label>
                            <input type="date" wire:model="destDepartureDate" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            @error('destDepartureDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="closeAddDestination" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Cancel</button>
                    <button wire:click="saveDestination" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Destination</button>
                </div>
            </div>
        </div>
    @endif
</div>
