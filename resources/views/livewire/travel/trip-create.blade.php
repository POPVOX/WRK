<div class="min-h-screen max-w-3xl mx-auto">
    <!-- Back Link -->
    <div class="mb-4">
        <a href="{{ route('travel.index') }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Travel
        </a>
    </div>

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🌍 Plan a Trip</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Create a new trip for your team</p>
    </div>

    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $step >= $i ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                        @if($step > $i)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <span class="ml-2 text-sm {{ $step >= $i ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                        @if($i === 1) Basic Info
                        @elseif($i === 2) Travelers
                        @elseif($i === 3) Review & Compliance
                        @endif
                    </span>
                    @if($i < $totalSteps)
                        <div class="flex-1 mx-4 h-0.5 {{ $step > $i ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <!-- Step Content -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        @if($step === 1)
            <!-- Step 1: Basic Info -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trip Name *</label>
                    <input type="text" wire:model="name" 
                           placeholder="e.g., NDI Democracy Conference - Nairobi"
                           class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trip Type *</label>
                    <select wire:model="type" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea wire:model="description" rows="2"
                              placeholder="Purpose of the trip..."
                              class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date *</label>
                        <input type="date" wire:model="startDate" 
                               class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        @error('startDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date *</label>
                        <input type="date" wire:model="endDate" 
                               class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        @error('endDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Destinations Section -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">📍 Destinations *</label>
                        <span class="text-xs text-gray-500">Add one or more destinations</span>
                    </div>

                    @if(count($destinations) > 0)
                        <div class="space-y-2 mb-4">
                            @foreach($destinations as $index => $dest)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <span class="text-lg">{{ $this->getCountryFlag($dest['country']) }}</span>
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $dest['city'] }}</span>
                                            <span class="text-gray-500 dark:text-gray-400">, {{ $dest['country_name'] }}</span>
                                            @if(($dest['advisory_level'] ?? 0) >= 3)
                                                <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $dest['advisory_level'] == 4 ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                                    Level {{ $dest['advisory_level'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($dest['arrival_date'])->format('M j') }} - {{ \Carbon\Carbon::parse($dest['departure_date'])->format('M j') }}
                                        </span>
                                        <button type="button" wire:click="removeDestination({{ $index }})" class="text-red-500 hover:text-red-700 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('destinations') <div class="text-red-500 text-sm mb-3">{{ $message }}</div> @enderror

                    <!-- Add Destination Form -->
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <input type="text" wire:model="newDestCity" 
                                       placeholder="City (e.g., Nairobi)"
                                       class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                @error('newDestCity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <select wire:model="newDestCountry" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select country...</option>
                                    @foreach($countries as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('newDestCountry') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <input type="text" wire:model="newDestStateProvince" 
                                       placeholder="State/Province (optional)"
                                       class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <input type="date" wire:model="newDestArrivalDate" 
                                       class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                @error('newDestArrivalDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <input type="date" wire:model="newDestDepartureDate" 
                                       class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500">
                                @error('newDestDepartureDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="button" wire:click="addDestination" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Destination
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Associated Project</label>
                    <select wire:model="projectId" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">None</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Partner Organization (for delegations)</label>
                    <select wire:model="partnerOrganizationId" class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">None</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($partnerOrganizationId)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program Name</label>
                        <input type="text" wire:model="partnerProgramName" 
                               placeholder="e.g., World Forum for Democracy 2026"
                               class="w-full border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>
                @endif
            </div>

        @elseif($step === 2)
            <!-- Step 2: Travelers -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Who's traveling? *</label>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($teamMembers as $member)
                            <div class="flex items-center justify-between p-3 rounded-lg border {{ in_array($member->id, $selectedTravelers) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                                <label class="flex items-center gap-3 flex-1 cursor-pointer">
                                    <input type="checkbox" 
                                           wire:click="toggleTraveler({{ $member->id }})"
                                           {{ in_array($member->id, $selectedTravelers) ? 'checked' : '' }}
                                           class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-medium">
                                            {{ substr($member->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $member->email }}</div>
                                        </div>
                                    </div>
                                </label>
                                @if(in_array($member->id, $selectedTravelers))
                                    <button wire:click="setLead({{ $member->id }})"
                                            class="px-3 py-1 text-sm rounded-full {{ $leadTravelerId === $member->id ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300' }}">
                                        {{ $leadTravelerId === $member->id ? '★ Lead' : 'Set as Lead' }}
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('selectedTravelers') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                @if(count($selectedTravelers) > 0)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Selected Travelers ({{ count($selectedTravelers) }})</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($teamMembers->whereIn('id', $selectedTravelers) as $traveler)
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded-full text-sm">
                                    {{ $traveler->name }}
                                    @if($leadTravelerId === $traveler->id)
                                        <span class="text-xs">★</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

        @elseif($step === 3)
            <!-- Step 3: Review & Compliance -->
            <div class="space-y-6">
                <!-- Trip Summary -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Trip Summary</h4>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                            <dd class="text-gray-900 dark:text-white font-medium">{{ $name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $typeOptions[$type] ?? $type }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-1">Destinations</dt>
                            <dd class="space-y-1">
                                @foreach($destinations as $dest)
                                    <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                                        <span>{{ $this->getCountryFlag($dest['country']) }}</span>
                                        <span>{{ $dest['city'] }}, {{ $dest['country_name'] }}</span>
                                        <span class="text-xs text-gray-500">({{ \Carbon\Carbon::parse($dest['arrival_date'])->format('M j') }} - {{ \Carbon\Carbon::parse($dest['departure_date'])->format('M j') }})</span>
                                    </div>
                                @endforeach
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Dates</dt>
                            <dd class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($startDate)->format('M j') }} - {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Travelers</dt>
                            <dd class="text-gray-900 dark:text-white">{{ count($selectedTravelers) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Travel Advisory -->
                @if($travelAdvisory)
                    <div class="p-4 rounded-lg border-2 
                        {{ $travelAdvisory['is_prohibited'] ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 
                           ($travelAdvisory['level'] === '4' ? 'border-red-400 bg-red-50 dark:bg-red-900/20' :
                           ($travelAdvisory['level'] === '3' ? 'border-orange-400 bg-orange-50 dark:bg-orange-900/20' :
                           ($travelAdvisory['level'] === '2' ? 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20' :
                           'border-green-400 bg-green-50 dark:bg-green-900/20'))) }}">
                        
                        <div class="flex items-start gap-3">
                            <span class="text-2xl">
                                @if($travelAdvisory['is_prohibited']) ⛔
                                @elseif($travelAdvisory['level'] === '4') ⛔
                                @elseif($travelAdvisory['level'] === '3') 🔶
                                @elseif($travelAdvisory['level'] === '2') ⚠️
                                @else ✅
                                @endif
                            </span>
                            <div class="flex-1">
                                <h4 class="font-medium 
                                    {{ $travelAdvisory['is_prohibited'] || $travelAdvisory['level'] === '4' ? 'text-red-800 dark:text-red-300' :
                                       ($travelAdvisory['level'] === '3' ? 'text-orange-800 dark:text-orange-300' :
                                       ($travelAdvisory['level'] === '2' ? 'text-yellow-800 dark:text-yellow-300' :
                                       'text-green-800 dark:text-green-300')) }}">
                                    @if($travelAdvisory['is_prohibited'])
                                        ⚠️ PROHIBITED DESTINATION
                                    @else
                                        Level {{ $travelAdvisory['level'] }}: {{ $travelAdvisory['title'] }}
                                    @endif
                                </h4>
                                @if($travelAdvisory['summary'])
                                    <p class="text-sm mt-1 text-gray-600 dark:text-gray-400">{{ $travelAdvisory['summary'] }}</p>
                                @endif
                                @if($travelAdvisory['url'])
                                    <a href="{{ $travelAdvisory['url'] }}" target="_blank" class="text-sm text-indigo-600 hover:underline mt-2 inline-block">
                                        View State Dept Advisory →
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Compliance Requirements -->
                @if($stepRegistrationRequired || $travelInsuranceRequired || $approvalRequired)
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <h4 class="font-medium text-amber-800 dark:text-amber-300 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Required Actions for This Trip
                        </h4>
                        <ul class="space-y-2 text-sm text-amber-700 dark:text-amber-400">
                            @if($stepRegistrationRequired)
                                <li class="flex items-center gap-2">
                                    <span>☐</span>
                                    <span>Register with STEP (Smart Traveler Enrollment Program) at <a href="https://step.state.gov" target="_blank" class="underline">step.state.gov</a></span>
                                </li>
                            @endif
                            @if($travelInsuranceRequired)
                                <li class="flex items-center gap-2">
                                    <span>☐</span>
                                    <span>Confirm travel insurance coverage for this destination</span>
                                </li>
                            @endif
                            @if($approvalRequired)
                                <li class="flex items-center gap-2">
                                    <span>☐</span>
                                    <span>Obtain approval from Managing Director before booking</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if($travelAdvisory && $travelAdvisory['is_prohibited'])
                    <div class="p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg">
                        <p class="text-red-800 dark:text-red-300 text-sm">
                            <strong>Note:</strong> Travel to this destination is prohibited under POPVOX Foundation travel policy without written exception from the Executive Director. You may still create this trip for planning purposes, but it will require approval before any bookings are made.
                        </p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Navigation Buttons -->
        <div class="flex justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            @if($step > 1)
                <button wire:click="previousStep" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    ← Previous
                </button>
            @else
                <div></div>
            @endif

            @if($step < $totalSteps)
                <button wire:click="nextStep" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Next →
                </button>
            @else
                <button wire:click="createTrip" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Create Trip
                </button>
            @endif
        </div>
    </div>
</div>
