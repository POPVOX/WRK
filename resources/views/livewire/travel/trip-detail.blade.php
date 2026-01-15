<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Back Link -->
    <div class="mb-6">
        <a href="{{ route('travel.index') }}" class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Travel
        </a>
    </div>

    <!-- Header Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 mb-8">
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

            <!-- Travelers (Staff & Guests) -->
            @if($trip->travelers->isNotEmpty() || $trip->guests->isNotEmpty())
                <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Travelers:</span>
                    <div class="flex -space-x-2">
                        {{-- Staff Travelers --}}
                        @foreach($trip->travelers as $traveler)
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-sm font-medium border-2 border-white dark:border-gray-800"
                                 title="{{ $traveler->name }} (Staff) {{ $traveler->pivot->role === 'lead' ? '- Lead' : '' }}{{ $traveler->travelProfile?->home_airport_code ? ' - '.$traveler->travelProfile->home_airport_code : '' }}">
                                {{ substr($traveler->name, 0, 1) }}
                            </div>
                        @endforeach
                        {{-- Guest Travelers --}}
                        @foreach($trip->guests as $guest)
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-sm font-medium border-2 border-white dark:border-gray-800"
                                 title="{{ $guest->name }} (Guest{{ $guest->organization ? ' - '.$guest->organization : '' }}){{ $guest->home_airport_code ? ' - '.$guest->home_airport_code : '' }}">
                                {{ substr($guest->name, 0, 1) }}
                            </div>
                        @endforeach
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        @php
                            $allNames = $trip->travelers->pluck('name')->concat($trip->guests->pluck('name'));
                        @endphp
                        {{ $allNames->join(', ') }}
                    </span>
                    @if($canEdit)
                        <button wire:click="openAddGuest" class="ml-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            + Add Guest
                        </button>
                    @endif
                </div>
            @else
                @if($canEdit)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button wire:click="openAddGuest" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            + Add a guest traveler
                        </button>
                    </div>
                @endif
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
    <div class="border-b border-gray-200 dark:border-gray-700 mb-8">
        <nav class="flex gap-8 overflow-x-auto" aria-label="Tabs">
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
                        class="pb-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition {{ $activeTab === $key 
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="space-y-8">
        @if($activeTab === 'overview')
            <!-- Overview Tab -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Quick Stats -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-5 text-lg">Trip Summary</h3>
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
                            <dd class="text-gray-900 dark:text-white">{{ $trip->travelers->count() + $trip->guests->count() }}
                                @if($trip->guests->count() > 0)
                                    <span class="text-xs text-gray-500">({{ $trip->travelers->count() }} staff, {{ $trip->guests->count() }} guests)</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Flights</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $trip->segments->where('type', 'flight')->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Hotels</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $trip->lodging->count() }}</dd>
                        </div>
                        @if($trip->projects->isNotEmpty())
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400 mb-1">{{ $trip->projects->count() > 1 ? 'Projects' : 'Project' }}</dt>
                                <dd class="space-y-1">
                                    @foreach($trip->projects as $project)
                                        <a href="{{ route('projects.show', $project) }}" class="block text-indigo-600 hover:underline text-sm">
                                            {{ $project->name }}
                                        </a>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        @if($trip->guests->isNotEmpty())
                            <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                                <dt class="text-gray-500 dark:text-gray-400 mb-2">Guest Travelers</dt>
                                <dd class="space-y-2">
                                    @foreach($trip->guests as $guest)
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-xs font-medium">
                                                    {{ substr($guest->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <span class="text-gray-900 dark:text-white">{{ $guest->name }}</span>
                                                    @if($guest->organization)
                                                        <span class="text-gray-500 text-xs">({{ $guest->organization }})</span>
                                                    @endif
                                                    @if($guest->home_airport_code)
                                                        <span class="text-gray-400 text-xs ml-1">✈️ {{ $guest->home_airport_code }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($canEdit)
                                                <button wire:click="deleteGuest({{ $guest->id }})" 
                                                    wire:confirm="Remove {{ $guest->name }} from this trip?"
                                                    class="text-gray-400 hover:text-red-500">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Expenses Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-5 text-lg">Expenses</h3>
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
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-5 text-lg">Sponsorship</h3>
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

            <!-- Travel Segments (Grouped by Traveler) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white">✈️ Travel Segments</h3>
                </div>

                @php
                    $segmentsByTraveler = $trip->segments->groupBy('user_id');
                @endphp

                @if($trip->travelers->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>Add travelers to manage their itineraries</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($trip->travelers as $traveler)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-medium text-sm">
                                            {{ substr($traveler->name, 0, 1) }}
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $traveler->name }}</span>
                                        @if($traveler->pivot->role === 'lead')
                                            <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Lead</span>
                                        @endif
                                    </div>
                                    @if($canEdit)
                                        <div class="flex items-center gap-2">
                                            <button wire:click="openSmartImport({{ $traveler->id }})" class="text-sm text-purple-600 hover:text-purple-800 flex items-center gap-1" title="Paste itinerary text or upload document">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                </svg>
                                                Smart Import
                                            </button>
                                            <span class="text-gray-300 dark:text-gray-600">|</span>
                                            <button wire:click="openAddSegment({{ $traveler->id }})" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Manual
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                
                                @php
                                    $travelerSegments = $segmentsByTraveler->get($traveler->id, collect());
                                @endphp

                                @if($travelerSegments->isEmpty())
                                    <div class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                        No travel segments yet
                                    </div>
                                @else
                                    <div class="divide-y divide-gray-200 dark:divide-gray-600">
                                        @foreach($travelerSegments as $segment)
                                            <div class="flex items-start gap-4 p-4">
                                                <span class="text-xl">{{ $segment->type_icon }}</span>
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
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Lodging -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Lodging</h3>
                    <button wire:click="openAddLodging" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Lodging
                    </button>
                </div>
                @if($trip->lodging->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <div class="text-4xl mb-2">🏨</div>
                        <p>No lodging added yet</p>
                        <button wire:click="openAddLodging" class="mt-2 text-indigo-600 hover:text-indigo-800 text-sm">Add your first accommodation</button>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($trip->lodging as $lodging)
                            <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg group">
                                <span class="text-2xl">🏨</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $lodging->property_name }}</div>
                                            @if($lodging->chain)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $lodging->chain }}</div>
                                            @endif
                                        </div>
                                        <button wire:click="deleteLodging({{ $lodging->id }})" 
                                            wire:confirm="Remove this lodging?"
                                            class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $lodging->city }}, {{ $lodging->country }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-4">
                                        <span>{{ $lodging->check_in_date->format('M j') }} - {{ $lodging->check_out_date->format('M j') }}
                                        ({{ $lodging->nights_count }} nights)</span>
                                        @if($lodging->total_cost)
                                            <span class="text-indigo-600 dark:text-indigo-400">${{ number_format($lodging->total_cost, 2) }}</span>
                                        @endif
                                    </div>
                                    @if($lodging->confirmation_number)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Confirmation: <span class="font-mono">{{ $lodging->confirmation_number }}</span>
                                        </div>
                                    @endif
                                    @if($lodging->traveler)
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            {{ $lodging->traveler->name }}
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
            <div class="space-y-6">
                {{-- Header with Add button --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sponsorships & Reimbursements</h3>
                    @if($canEdit)
                        <button wire:click="openAddSponsorship" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Sponsorship
                        </button>
                    @endif
                </div>

                @if($trip->sponsorships->isEmpty())
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                        <div class="text-5xl mb-3">🤝</div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">No sponsorships added yet</p>
                        @if($canEdit)
                            <button wire:click="openAddSponsorship" class="text-indigo-600 hover:underline">
                                + Add a sponsorship agreement
                            </button>
                        @endif
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($trip->sponsorships as $sponsorship)
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                                {{-- Sponsorship Header --}}
                                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $sponsorship->organization->name ?? 'Unknown Organization' }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                {{ \App\Models\TripSponsorship::getTypeOptions()[$sponsorship->type] ?? $sponsorship->type }}
                                                @if($sponsorship->terms_extracted_at)
                                                    <span class="text-green-600 dark:text-green-400">✓ Terms extracted</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full {{ \App\Models\TripSponsorship::getPaymentStatusColors()[$sponsorship->payment_status] ?? 'bg-gray-100' }}">
                                                {{ \App\Models\TripSponsorship::getPaymentStatusOptions()[$sponsorship->payment_status] ?? $sponsorship->payment_status }}
                                            </span>
                                            @if($canEdit)
                                                <button wire:click="deleteSponsorship({{ $sponsorship->id }})" 
                                                    wire:confirm="Delete this sponsorship?"
                                                    class="text-gray-400 hover:text-red-500 p-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Financial Summary --}}
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                        <div>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Total Value</span>
                                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ $sponsorship->currency_symbol }}{{ number_format($sponsorship->amount ?? 0, 2) }}
                                            </p>
                                        </div>
                                        @if($sponsorship->total_consulting_fees)
                                            <div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Consulting Fees</span>
                                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ $sponsorship->currency_symbol }}{{ number_format($sponsorship->total_consulting_fees, 2) }}
                                                </p>
                                            </div>
                                        @endif
                                        @if($sponsorship->total_reimbursable)
                                            <div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Reimbursable</span>
                                                <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                                                    {{ $sponsorship->currency_symbol }}{{ number_format($sponsorship->total_reimbursable, 2) }}
                                                </p>
                                            </div>
                                        @endif
                                        @if($sponsorship->amount_received > 0)
                                            <div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Received</span>
                                                <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                                    {{ $sponsorship->currency_symbol }}{{ number_format($sponsorship->amount_received, 2) }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>

                                    @if($sponsorship->exchange_rate_note)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">💱 {{ $sponsorship->exchange_rate_note }}</p>
                                    @endif
                                </div>

                                {{-- Coverage --}}
                                @if(!empty($sponsorship->coverage_list))
                                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Covers:</span>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach($sponsorship->coverage_list as $coverage)
                                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded-full">✓ {{ $coverage }}</span>
                                            @endforeach
                                        </div>
                                        @if(!empty($sponsorship->covered_travelers))
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                                <span class="font-medium">For:</span> {{ implode(', ', $sponsorship->covered_travelers) }}
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                {{-- Line Items --}}
                                @if(!empty($sponsorship->line_items))
                                    <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                                        <h5 class="font-medium text-gray-900 dark:text-white mb-3">Line Items</h5>
                                        <div class="space-y-2">
                                            @foreach($sponsorship->line_items as $item)
                                                <div class="flex items-center justify-between text-sm py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                                    <div class="flex-1">
                                                        <span class="text-gray-900 dark:text-white">{{ $item['description'] ?? 'Item' }}</span>
                                                        @if(!empty($item['rate']))
                                                            <span class="text-gray-500 text-xs ml-2">({{ $item['rate'] }})</span>
                                                        @endif
                                                        @if(!empty($item['notes']))
                                                            <p class="text-xs text-gray-500">{{ $item['notes'] }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="font-medium {{ ($item['is_reimbursable'] ?? false) ? 'text-green-600' : 'text-gray-900 dark:text-white' }}">
                                                            {{ $item['currency'] ?? $sponsorship->currency ?? '' }} {{ number_format($item['amount'] ?? 0, 2) }}
                                                        </span>
                                                        @if($item['is_reimbursable'] ?? false)
                                                            <span class="text-xs text-green-600 block">reimbursable</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Deliverables (Requirements for Payment) --}}
                                @if(!empty($sponsorship->deliverables))
                                    <div class="p-5 border-b border-gray-200 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/10">
                                        <div class="flex items-center justify-between mb-3">
                                            <h5 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                📋 Required for Payment
                                            </h5>
                                            @php $progress = $sponsorship->deliverables_progress; @endphp
                                            <span class="text-sm {{ $progress['percent'] === 100 ? 'text-green-600' : 'text-amber-600' }}">
                                                {{ $progress['completed'] }}/{{ $progress['total'] }} complete
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($sponsorship->deliverables as $index => $deliverable)
                                                <label class="flex items-start gap-3 cursor-pointer group">
                                                    <input type="checkbox" 
                                                        wire:click="toggleDeliverable({{ $sponsorship->id }}, {{ $index }})"
                                                        {{ ($deliverable['is_completed'] ?? false) ? 'checked' : '' }}
                                                        class="mt-1 w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-sm {{ ($deliverable['is_completed'] ?? false) ? 'text-gray-400 line-through' : 'text-gray-700 dark:text-gray-300' }}">
                                                        {{ $deliverable['description'] ?? $deliverable }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @if($progress['percent'] === 100)
                                            <div class="mt-3 p-2 bg-green-100 dark:bg-green-900/30 rounded text-green-700 dark:text-green-400 text-sm">
                                                ✅ All deliverables complete! Ready to invoice.
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Payment Terms --}}
                                <div class="p-5 text-sm">
                                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-gray-600 dark:text-gray-400">
                                        @if($sponsorship->payment_terms)
                                            <span><strong>Terms:</strong> {{ $sponsorship->payment_terms }}</span>
                                        @endif
                                        @if($sponsorship->invoice_deadline)
                                            <span><strong>Invoice By:</strong> {{ $sponsorship->invoice_deadline->format('M j, Y') }}</span>
                                        @endif
                                        @if($sponsorship->payment_due_date)
                                            <span><strong>Payment Due:</strong> {{ $sponsorship->payment_due_date->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                    
                                    {{-- Billing Info --}}
                                    @if($sponsorship->billing_contact_name || $sponsorship->billing_contact_email)
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Billing Contact:</span>
                                            {{ $sponsorship->billing_contact_name }}
                                            @if($sponsorship->billing_contact_email)
                                                <a href="mailto:{{ $sponsorship->billing_contact_email }}" class="text-indigo-600 hover:underline">{{ $sponsorship->billing_contact_email }}</a>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($sponsorship->billing_instructions)
                                        <div class="mt-2 text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Instructions:</span> {{ $sponsorship->billing_instructions }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                @if($canEdit && $sponsorship->agreement_text)
                                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex gap-3">
                                        <button wire:click="reparseSponsorship({{ $sponsorship->id }})" class="text-sm text-indigo-600 hover:underline">
                                            🔄 Re-extract terms
                                        </button>
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Traveler *</label>
                        <select wire:model="segmentTravelerId" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select traveler...</option>
                            @foreach($trip->travelers as $traveler)
                                <option value="{{ $traveler->id }}">{{ $traveler->name }}</option>
                            @endforeach
                        </select>
                        @error('segmentTravelerId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select wire:model="segmentType" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            @foreach(\App\Models\TripSegment::getTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carrier</label>
                            <input type="text" wire:model="segmentCarrier" placeholder="e.g., United" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Flight/Train #</label>
                            <input type="text" wire:model="segmentNumber" placeholder="e.g., UA 234" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
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

    <!-- Add Guest Modal -->
    @if($showAddGuest)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </span>
                    Add Guest Traveler
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Add a non-staff traveler (speaker, partner, family member, etc.)</p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                        <input type="text" wire:model="guestName" placeholder="Full name" 
                            class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        @error('guestName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" wire:model="guestEmail" placeholder="email@example.com" 
                                class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            @error('guestEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                            <input type="text" wire:model="guestPhone" placeholder="+1 555-123-4567" 
                                class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organization</label>
                            <input type="text" wire:model="guestOrganization" placeholder="Company or affiliation" 
                                class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                            <select wire:model="guestRole" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                @foreach(\App\Models\TripGuest::getRoleOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Home Airport Code</label>
                        <input type="text" wire:model="guestHomeAirport" placeholder="e.g., JFK, LAX, ORD" maxlength="5"
                            class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white uppercase" style="text-transform: uppercase;">
                        <p class="text-xs text-gray-500 mt-1">Their preferred departure airport</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="guestNotes" rows="2" placeholder="Any special requirements or notes..." 
                            class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('showAddGuest', false)" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Cancel</button>
                    <button wire:click="saveGuest" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Guest</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Sponsorship Modal -->
    @if($showAddSponsorship)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                🤝 Add Sponsorship
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Paste agreement text or upload a document to automatically extract terms
                            </p>
                        </div>
                        <button wire:click="closeAddSponsorship" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Sponsor Organization --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sponsor Organization *</label>
                            <button type="button" wire:click="openAddSponsorOrg" class="text-xs text-indigo-600 hover:text-indigo-800">+ Add New</button>
                        </div>
                        <select wire:model="sponsorshipOrgId" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select organization...</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                        @error('sponsorshipOrgId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sponsorship Type</label>
                        <select wire:model="sponsorshipType" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            @foreach(\App\Models\TripSponsorship::getTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <input type="text" wire:model="sponsorshipDescription" placeholder="e.g., Travel reimbursement for Berlin conference"
                            class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    {{-- Agreement Text --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                Agreement/Contract Text (AI will extract terms)
                            </span>
                        </label>
                        <textarea wire:model="sponsorshipAgreementText" rows="10" 
                            placeholder="Paste the sponsorship agreement, contract text, or email with terms here...

Example:
WFD will cover the costs of Dr Marci Harris for international travel including:
- Flights: $1,030.13 (MKL-SUN)
- Accommodation: 1/6 of team AirBnB @ $5,558.01 = $926.34
- Subsistence: $40/day × 5 days = $200

Payment: Net 30 from valid invoice after completion of deliverables."
                            class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"></textarea>
                        <p class="text-xs text-gray-500 mt-1">✨ AI will analyze this text to extract line items, amounts, payment terms, and deliverables</p>
                    </div>

                    {{-- Or Upload File --}}
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-2 bg-white dark:bg-gray-800 text-sm text-gray-500">or upload a document</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload Contract/Agreement (PDF or TXT)</label>
                        <input type="file" wire:model="sponsorshipFile" accept=".pdf,.txt,.doc,.docx"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @if($sponsorshipFile)
                            <p class="text-sm text-green-600 mt-1">✓ {{ $sponsorshipFile->getClientOriginalName() }}</p>
                        @endif
                    </div>

                    @if($sponsorshipParseError)
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">
                            {{ $sponsorshipParseError }}
                        </div>
                    @endif
                </div>

                <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-3">
                    <button wire:click="closeAddSponsorship" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Cancel</button>
                    <button wire:click="parseAndCreateSponsorship" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2"
                        wire:loading.attr="disabled"
                        wire:target="parseAndCreateSponsorship">
                        <span wire:loading wire:target="parseAndCreateSponsorship" class="animate-spin">⏳</span>
                        <span wire:loading.remove wire:target="parseAndCreateSponsorship">✨</span>
                        Create & Extract Terms
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Organization Modal (from sponsorship) -->
    @if($showAddSponsorOrg)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60]">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Organization</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organization Name *</label>
                        <input type="text" wire:model="newSponsorOrgName" 
                               placeholder="e.g., Brookings Institution"
                               class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        @error('newSponsorOrgName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                        <select wire:model="newSponsorOrgType" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="nonprofit">Nonprofit</option>
                            <option value="government">Government</option>
                            <option value="corporate">Corporate</option>
                            <option value="university">University</option>
                            <option value="media">Media</option>
                            <option value="other">Other</option>
                        </select>
                        @error('newSponsorOrgType') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                        <input type="url" wire:model="newSponsorOrgWebsite" 
                               placeholder="https://example.org"
                               class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        @error('newSponsorOrgWebsite') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="closeAddSponsorOrg" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Cancel</button>
                    <button wire:click="saveNewSponsorOrg" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Organization</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Add Lodging Modal -->
    @if($showAddLodging)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                🏨 Add Lodging
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Add hotel, Airbnb, or other accommodation
                            </p>
                        </div>
                        <button wire:click="closeAddLodging" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Mode Tabs --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700 px-6">
                    <button wire:click="setLodgingMode('manual')" 
                        class="px-4 py-3 text-sm font-medium {{ $lodgingMode === 'manual' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                        ✏️ Manual Entry
                    </button>
                    <button wire:click="setLodgingMode('smart')" 
                        class="px-4 py-3 text-sm font-medium {{ $lodgingMode === 'smart' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                        ✨ Smart Import
                    </button>
                    <button wire:click="setLodgingMode('url')" 
                        class="px-4 py-3 text-sm font-medium {{ $lodgingMode === 'url' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                        🔗 From URL
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    {{-- Smart Import Text Area --}}
                    @if($lodgingMode === 'smart')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Paste Confirmation Email or Booking Details
                                </label>
                                <textarea wire:model="lodgingSmartText" rows="8" 
                                    placeholder="Paste your hotel confirmation email, booking receipt, or any text containing lodging information here...

Example:
Your reservation is confirmed at Marriott Washington DC.
Confirmation #: ABC123456
Check-in: January 20, 2026 at 3:00 PM
Check-out: January 23, 2026 at 11:00 AM
Room: King Deluxe Room
Rate: $289/night
Total: $867 + $112.71 tax = $979.71"
                                    class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button wire:click="parseLodgingText" 
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2"
                                    wire:loading.attr="disabled">
                                    <span wire:loading wire:target="parseLodgingText" class="animate-spin">⏳</span>
                                    <span wire:loading.remove wire:target="parseLodgingText">✨</span>
                                    Extract Details
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- URL Input --}}
                    @if($lodgingMode === 'url')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hotel or Booking URL
                                </label>
                                <input type="url" wire:model="lodgingUrl" 
                                    placeholder="https://www.booking.com/hotel/... or https://www.airbnb.com/rooms/..."
                                    class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <p class="text-xs text-gray-500 mt-1">Works with Booking.com, Airbnb, Hotels.com, and most hotel websites</p>
                            </div>
                            <div class="flex justify-end">
                                <button wire:click="parseLodgingUrl" 
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2"
                                    wire:loading.attr="disabled">
                                    <span wire:loading wire:target="parseLodgingUrl" class="animate-spin">⏳</span>
                                    <span wire:loading.remove wire:target="parseLodgingUrl">🔗</span>
                                    Fetch Details
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Parse Error --}}
                    @if($lodgingParseError)
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">
                            {{ $lodgingParseError }}
                        </div>
                    @endif

                    {{-- Extraction Success --}}
                    @if($extractedLodging && ($lodgingMode === 'smart' || $lodgingMode === 'url'))
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-300 text-sm">
                            ✓ Information extracted! Review and edit the fields below.
                            @if(isset($extractedLodging['confidence']))
                                <span class="text-xs opacity-75">(Confidence: {{ number_format($extractedLodging['confidence'] * 100) }}%)</span>
                            @endif
                        </div>
                    @endif

                    {{-- Manual Form Fields --}}
                    @if($lodgingMode === 'manual' || $extractedLodging)
                        <div class="space-y-4">
                            {{-- Property Name & Chain --}}
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Property Name *</label>
                                    <input type="text" wire:model="lodgingPropertyName" 
                                        placeholder="e.g., Marriott Washington DC"
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @error('lodgingPropertyName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chain</label>
                                    <input type="text" wire:model="lodgingChain" 
                                        placeholder="e.g., Marriott, Airbnb"
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                            </div>

                            {{-- Address --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                                <input type="text" wire:model="lodgingAddress" 
                                    placeholder="Street address"
                                    class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            </div>

                            {{-- City & Country --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City *</label>
                                    <input type="text" wire:model="lodgingCity" 
                                        placeholder="e.g., Washington"
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @error('lodgingCity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country (2-letter code) *</label>
                                    <input type="text" wire:model="lodgingCountry" 
                                        placeholder="US"
                                        maxlength="2"
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white uppercase">
                                    @error('lodgingCountry') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Dates --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check-in Date *</label>
                                    <input type="date" wire:model="lodgingCheckInDate" 
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @error('lodgingCheckInDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check-in Time</label>
                                    <input type="time" wire:model="lodgingCheckInTime" 
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check-out Date *</label>
                                    <input type="date" wire:model="lodgingCheckOutDate" 
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    @error('lodgingCheckOutDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check-out Time</label>
                                    <input type="time" wire:model="lodgingCheckOutTime" 
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                            </div>

                            {{-- Room & Costs --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Room Type</label>
                                    <input type="text" wire:model="lodgingRoomType" 
                                        placeholder="e.g., King Suite"
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nightly Rate</label>
                                    <input type="number" wire:model="lodgingNightlyRate" step="0.01" min="0"
                                        placeholder="0.00"
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Cost</label>
                                    <input type="number" wire:model="lodgingTotalCost" step="0.01" min="0"
                                        placeholder="0.00"
                                        class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                                    <select wire:model="lodgingCurrency" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="CAD">CAD</option>
                                        <option value="AUD">AUD</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Confirmation --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmation Number</label>
                                <input type="text" wire:model="lodgingConfirmation" 
                                    placeholder="Booking reference"
                                    class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono">
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                <textarea wire:model="lodgingNotes" rows="2" 
                                    placeholder="Special requests, amenities, contact info, etc."
                                    class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                            </div>

                            {{-- Traveler Assignment --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assign Lodging To</label>
                                <div class="space-y-3">
                                    <label class="flex items-center gap-2">
                                        <input type="radio" wire:model="lodgingAssignTo" value="all" 
                                            class="text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">All trip participants (shared accommodation)</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="radio" wire:model="lodgingAssignTo" value="specific"
                                            class="text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Specific travelers only</span>
                                    </label>

                                    @if($lodgingAssignTo === 'specific')
                                        <div class="ml-6 space-y-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                            @foreach($trip->travelers as $traveler)
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" wire:model="lodgingSelectedTravelers" value="{{ $traveler->id }}"
                                                        class="text-indigo-600 focus:ring-indigo-500 rounded">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $traveler->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-3">
                    <button wire:click="closeAddLodging" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">Cancel</button>
                    @if($lodgingMode === 'manual' || $extractedLodging)
                        <button wire:click="saveLodging" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="saveLodging" class="animate-spin">⏳</span>
                            Save Lodging
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Smart Import Modal -->
    @if($showSmartImport)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                Smart Import
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Paste itinerary text or upload a confirmation document. AI will extract all travel segments.</p>
                        </div>
                        <button wire:click="closeSmartImport" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    @if(empty($extractedSegments))
                        <!-- Input Phase -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Traveler *</label>
                                <select wire:model="smartImportTravelerId" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">Select traveler...</option>
                                    @foreach($trip->travelers as $traveler)
                                        <option value="{{ $traveler->id }}">{{ $traveler->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Paste Itinerary Text
                                </label>
                                <textarea wire:model="smartImportText" 
                                          rows="10"
                                          placeholder="Paste your itinerary confirmation email, booking details, or travel schedule here...

Example:
United Airlines Confirmation: ABC123
Flight UA 234
Departs: Washington DCA - Jan 20, 2026 at 8:30 AM
Arrives: San Francisco SFO - Jan 20, 2026 at 11:45 AM
Seat: 12A (Economy)"
                                          class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"></textarea>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                                </div>
                                <div class="relative flex justify-center">
                                    <span class="bg-white dark:bg-gray-800 px-3 text-sm text-gray-500">or upload a file</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Upload Document (PDF, TXT, HTML)
                                </label>
                                <input type="file" wire:model="smartImportFile" 
                                       accept=".pdf,.txt,.html,.htm"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-purple-900/50 dark:file:text-purple-300">
                                @if($smartImportFile)
                                    <p class="mt-1 text-sm text-green-600 dark:text-green-400">
                                        File selected: {{ $smartImportFile->getClientOriginalName() }}
                                    </p>
                                @endif
                            </div>

                            @if($smartImportError)
                                <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">
                                    {{ $smartImportError }}
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- Review Phase -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    Extracted {{ count($extractedSegments) }} Segment(s)
                                </h4>
                                <button wire:click="$set('extractedSegments', [])" class="text-sm text-gray-500 hover:text-gray-700">
                                    Start Over
                                </button>
                            </div>

                            @if($smartImportNotes)
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-blue-700 dark:text-blue-400 text-sm">
                                    <strong>Note:</strong> {{ $smartImportNotes }}
                                </div>
                            @endif

                            <div class="space-y-3">
                                @foreach($extractedSegments as $index => $segment)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 relative">
                                        <button wire:click="removeExtractedSegment({{ $index }})" 
                                                class="absolute top-2 right-2 text-gray-400 hover:text-red-600"
                                                title="Remove this segment">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>

                                        <div class="flex items-start gap-3 mb-3">
                                            <span class="text-2xl">
                                                @switch($segment['type'])
                                                    @case('flight') ✈️ @break
                                                    @case('train') 🚆 @break
                                                    @case('bus') 🚌 @break
                                                    @case('rental_car') 🚗 @break
                                                    @case('rideshare') 🚕 @break
                                                    @case('ferry') ⛴️ @break
                                                    @default 🚐
                                                @endswitch
                                            </span>
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $segment['departure_location'] }} → {{ $segment['arrival_location'] }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    @if($segment['carrier'])
                                                        {{ $segment['carrier'] }}
                                                        @if($segment['segment_number'])
                                                            {{ $segment['carrier_code'] }} {{ $segment['segment_number'] }}
                                                        @endif
                                                        •
                                                    @endif
                                                    {{ \Carbon\Carbon::parse($segment['departure_datetime'])->format('M j, g:i A') }}
                                                    @if($segment['arrival_datetime'])
                                                        → {{ \Carbon\Carbon::parse($segment['arrival_datetime'])->format('M j, g:i A') }}
                                                    @endif
                                                </div>
                                                @if($segment['confirmation_number'])
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        Confirmation: {{ $segment['confirmation_number'] }}
                                                    </div>
                                                @endif
                                            </div>
                                            @if(isset($segment['confidence']))
                                                <span class="px-2 py-0.5 text-xs rounded-full {{ $segment['confidence'] >= 0.8 ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' : ($segment['confidence'] >= 0.5 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400') }}" title="AI confidence in extraction accuracy">
                                                    {{ round($segment['confidence'] * 100) }}%
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Editable fields (collapsed by default) -->
                                        <details class="text-sm">
                                            <summary class="cursor-pointer text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                                Edit details
                                            </summary>
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                                                    <select wire:change="updateExtractedSegment({{ $index }}, 'type', $event.target.value)"
                                                            class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                        @foreach(\App\Models\TripSegment::getTypeOptions() as $value => $label)
                                                            <option value="{{ $value }}" {{ $segment['type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Carrier</label>
                                                    <input type="text" value="{{ $segment['carrier'] ?? '' }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'carrier', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Flight/Train #</label>
                                                    <input type="text" value="{{ $segment['segment_number'] ?? '' }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'segment_number', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">From</label>
                                                    <input type="text" value="{{ $segment['departure_location'] }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'departure_location', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">To</label>
                                                    <input type="text" value="{{ $segment['arrival_location'] }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'arrival_location', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Confirmation #</label>
                                                    <input type="text" value="{{ $segment['confirmation_number'] ?? '' }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'confirmation_number', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Departure</label>
                                                    <input type="datetime-local" value="{{ $segment['departure_datetime'] }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'departure_datetime', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Arrival</label>
                                                    <input type="datetime-local" value="{{ $segment['arrival_datetime'] ?? '' }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'arrival_datetime', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Seat</label>
                                                    <input type="text" value="{{ $segment['seat_assignment'] ?? '' }}"
                                                           wire:change="updateExtractedSegment({{ $index }}, 'seat_assignment', $event.target.value)"
                                                           class="w-full text-sm border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                                </div>
                                            </div>
                                        </details>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <div class="flex justify-end gap-3">
                        <button wire:click="closeSmartImport" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800">
                            Cancel
                        </button>
                        @if(empty($extractedSegments))
                            <button wire:click="parseItinerary" 
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-wait"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center gap-2">
                                <span wire:loading wire:target="parseItinerary">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                                <span wire:loading.remove wire:target="parseItinerary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                </span>
                                Extract Segments
                            </button>
                        @else
                            <button wire:click="saveExtractedSegments" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Save {{ count($extractedSegments) }} Segment(s)
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
