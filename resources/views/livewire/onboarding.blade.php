<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Progress Steps --}}
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-4">
                @foreach([1 => 'Profile', 2 => 'Connect Calendar', 3 => 'Import Data', 4 => 'Complete'] as $num => $label)
                    <div class="flex items-center">
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-full {{ $step >= $num ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }} font-semibold transition-colors">
                            @if($step > $num)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        @if($num < 4)
                            <div
                                class="w-12 sm:w-24 h-1 {{ $step > $num ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }} mx-2 transition-colors">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex justify-center mt-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Step {{ $step }} of 4:
                    {{ [1 => 'Profile Setup', 2 => 'Connect Calendar', 3 => 'Import Your Data', 4 => 'All Done!'][$step] }}
                </span>
            </div>
        </div>

        {{-- Step 1: Profile Setup --}}
        @if($step === 1)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 animate-fade-in">
                <div class="text-center mb-8">
                    <div
                        class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome to WRK! 👋</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Let's set up your profile to get started.</p>
                </div>

                <form wire:submit="saveProfile" class="space-y-6">
                    {{-- Photo Upload --}}
                    <div class="flex justify-center">
                        <div class="relative">
                            <div class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                @if($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                @elseif(auth()->user()->photo_url)
                                    <img src="{{ auth()->user()->photo_url }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <label
                                class="absolute bottom-0 right-0 w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input type="file" wire:model="photo" class="hidden" accept="image/*">
                            </label>
                        </div>
                    </div>
                    @error('photo') <p class="text-red-500 text-sm text-center">{{ $message }}</p> @enderror

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Job Title</label>
                        <input type="text" wire:model="title" placeholder="e.g., Product Manager"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    {{-- Bio --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio</label>
                        <textarea wire:model="bio" rows="3" placeholder="Tell us a bit about yourself..."
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                    </div>

                    {{-- LinkedIn --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn
                            Profile</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
                                </svg>
                            </span>
                            <input type="url" wire:model="linkedin" placeholder="https://linkedin.com/in/yourprofile"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        @error('linkedin') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                        <select wire:model="timezone"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="America/New_York">Eastern Time (ET)</option>
                            <option value="America/Chicago">Central Time (CT)</option>
                            <option value="America/Denver">Mountain Time (MT)</option>
                            <option value="America/Los_Angeles">Pacific Time (PT)</option>
                            <option value="America/Anchorage">Alaska Time</option>
                            <option value="Pacific/Honolulu">Hawaii Time</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit"
                            class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                            Continue
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Step 2: Connect Calendar --}}
        @if($step === 2)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 animate-fade-in">
                <div class="text-center mb-8">
                    <div
                        class="w-16 h-16 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Connect Your Calendar</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Import your meetings and automatically populate your
                        contacts and organizations.</p>
                </div>

                @if($isCalendarConnected)
                    <div
                        class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="font-medium text-green-800 dark:text-green-300">Google Calendar Connected!</p>
                                <p class="text-sm text-green-600 dark:text-green-400">Your calendar is ready to import meetings.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">How far back should we
                            import?</label>
                        <select wire:model="importRange"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="1_month">Last month</option>
                            <option value="3_months">Last 3 months</option>
                            <option value="6_months">Last 6 months</option>
                            <option value="1_year">Last year</option>
                            <option value="2_years">Last 2 years</option>
                        </select>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            We'll also import your <strong>upcoming meetings</strong> (next 2 weeks) to help you prepare.
                        </p>
                    </div>

                    <div class="flex gap-4">
                        <button wire:click="previousStep" type="button"
                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Back
                        </button>
                        <button wire:click="fetchCalendarEvents" type="button"
                            class="flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="fetchCalendarEvents">Fetch & Preview Events</span>
                            <span wire:loading wire:target="fetchCalendarEvents">Loading events...</span>
                            <svg wire:loading.remove wire:target="fetchCalendarEvents" class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                @else
                    <div class="space-y-6">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-3">What we'll import:</h3>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Past meetings with notes and attendees
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Contacts from meeting attendees
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Organizations based on email domains
                                </li>
                            </ul>
                        </div>

                        <button wire:click="connectCalendar" type="button"
                            class="w-full px-6 py-4 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 text-gray-800 dark:text-white font-medium rounded-lg hover:border-indigo-500 hover:shadow-md transition-all flex items-center justify-center gap-3">
                            <svg class="w-6 h-6" viewBox="0 0 24 24">
                                <path fill="#4285F4"
                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                <path fill="#34A853"
                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                <path fill="#FBBC05"
                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                <path fill="#EA4335"
                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                            </svg>
                            Connect Google Calendar
                        </button>

                        <div class="flex gap-4">
                            <button wire:click="previousStep" type="button"
                                class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Back
                            </button>
                            <button wire:click="skipCalendar" type="button"
                                class="flex-1 px-6 py-3 text-gray-500 dark:text-gray-400 font-medium hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                Skip for now →
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Step 3: Import Data --}}
        @if($step === 3)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 animate-fade-in">
                <div class="text-center mb-8">
                    <div
                        class="w-16 h-16 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Review & Import</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Select which items you'd like to import. You can
                        uncheck any you don't want.</p>
                </div>

                @if(count($calendarEvents) > 0)
                    {{-- Organizations Section --}}
                    @if(count($extractedOrgs) > 0)
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Organizations
                                    ({{ count($selectedOrgs) }}/{{ count($extractedOrgs) }})</h3>
                                <div class="flex gap-2">
                                    <button wire:click="selectAllOrgs"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Select All</button>
                                    <span class="text-gray-300">|</span>
                                    <button wire:click="deselectAllOrgs" class="text-sm text-gray-500 hover:underline">Deselect
                                        All</button>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-48 overflow-y-auto space-y-2">
                                @foreach($extractedOrgs as $org)
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-gray-600 rounded cursor-pointer">
                                        <input type="checkbox" {{ in_array($org['domain'], $selectedOrgs) ? 'checked' : '' }}
                                            wire:click="toggleOrg('{{ $org['domain'] }}')"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $org['name'] }}</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ $org['contact_count'] }}
                                                contacts)</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- People Section --}}
                    @if(count($extractedPeople) > 0)
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Contacts
                                    ({{ count($selectedPeople) }}/{{ count($extractedPeople) }})</h3>
                                <div class="flex gap-2">
                                    <button wire:click="selectAllPeople"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Select All</button>
                                    <span class="text-gray-300">|</span>
                                    <button wire:click="deselectAllPeople" class="text-sm text-gray-500 hover:underline">Deselect
                                        All</button>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-48 overflow-y-auto space-y-2">
                                @foreach($extractedPeople as $person)
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-gray-600 rounded cursor-pointer">
                                        <input type="checkbox" {{ in_array($person['email'], $selectedPeople) ? 'checked' : '' }}
                                            wire:click="togglePerson('{{ $person['email'] }}')"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $person['name'] }}</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ $person['email'] }}</span>
                                        </div>
                                        <span class="text-xs text-gray-400">{{ $person['meeting_count'] }} meetings</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Meetings Section --}}
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Meetings
                                ({{ count($selectedEvents) }}/{{ count($calendarEvents) }})</h3>
                            <div class="flex gap-2">
                                <button wire:click="selectAllEvents"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Select All</button>
                                <span class="text-gray-300">|</span>
                                <button wire:click="deselectAllEvents" class="text-sm text-gray-500 hover:underline">Deselect
                                    All</button>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto space-y-2">
                            @foreach($calendarEvents as $event)
                                <label
                                    class="flex items-center gap-3 p-3 hover:bg-white dark:hover:bg-gray-600 rounded cursor-pointer border {{ $event['isUpcoming'] ?? false ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/20' : 'border-transparent' }} hover:border-gray-200 dark:hover:border-gray-500">
                                    <input type="checkbox" {{ in_array($event['id'], $selectedEvents) ? 'checked' : '' }}
                                        wire:click="toggleEvent('{{ $event['id'] }}')"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="font-medium text-gray-900 dark:text-white truncate">{{ $event['title'] }}</span>
                                            @if($event['isUpcoming'] ?? false)
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                    Upcoming
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $event['date'] }}
                                            @if($event['attendee_count'] > 0)
                                                • {{ $event['attendee_count'] }} attendee{{ $event['attendee_count'] > 1 ? 's' : '' }}
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>No calendar data to import. You can continue to complete your setup.</p>
                    </div>
                @endif

                <div class="flex gap-4">
                    <button wire:click="previousStep" type="button"
                        class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Back
                    </button>
                    @if(count($calendarEvents) > 0)
                        <button wire:click="importSelected" type="button"
                            class="flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="importSelected">Import Selected
                                ({{ count($selectedEvents) + count($selectedPeople) + count($selectedOrgs) }} items)</span>
                            <span wire:loading wire:target="importSelected">Importing...</span>
                        </button>
                    @endif
                    <button wire:click="skipImport" type="button"
                        class="px-6 py-3 text-gray-500 dark:text-gray-400 font-medium hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        {{ count($calendarEvents) > 0 ? 'Skip' : 'Continue' }} →
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 4: Complete --}}
        @if($step === 4)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 animate-fade-in text-center">
                <div
                    class="w-20 h-20 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">You're All Set! 🎉</h2>

                @if($importMessage)
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ $importMessage }}</p>
                @else
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Your profile is ready. Welcome to WRK!</p>
                @endif

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-8 text-left">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Next steps:</h3>
                    <ul class="space-y-3 text-gray-600 dark:text-gray-300">
                        <li class="flex items-center gap-3">
                            <span
                                class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-medium">1</span>
                            Create or join a project to start tracking your work
                        </li>
                        <li class="flex items-center gap-3">
                            <span
                                class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-medium">2</span>
                            Log your meetings to build your contact history
                        </li>
                        <li class="flex items-center gap-3">
                            <span
                                class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-medium">3</span>
                            Use the AI assistant to get insights from your data
                        </li>
                    </ul>
                </div>

                <button wire:click="completeOnboarding" type="button"
                    class="px-8 py-4 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors text-lg">
                    Go to Dashboard →
                </button>
            </div>
        @endif
    </div>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out forwards;
        }
    </style>
</div>