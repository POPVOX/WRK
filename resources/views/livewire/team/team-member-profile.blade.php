<div>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('team.hub') }}" wire:navigate
                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Team Member Profile
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session()->has('message'))
                <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Profile Card --}}
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {{-- Header with photo --}}
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-24"></div>
                        <div class="px-6 pb-6">
                            <div class="-mt-12 mb-4">
                                @if($member->photo_url)
                                    <img src="{{ $member->photo_url }}" alt="{{ $member->name }}"
                                        class="w-24 h-24 rounded-full border-4 border-white dark:border-gray-800 object-cover shadow-lg">
                                @else
                                    <div class="w-24 h-24 rounded-full border-4 border-white dark:border-gray-800 bg-indigo-500 flex items-center justify-center shadow-lg">
                                        <span class="text-2xl font-bold text-white">{{ substr($member->name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>

                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $member->name }}</h3>
                            @if($member->title)
                                <p class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $member->title }}</p>
                            @endif

                            {{-- Location & Time --}}
                            <div class="mt-4 space-y-2">
                                @if($member->location)
                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>{{ $member->location }}</span>
                                    </div>
                                @endif
                                @if($member->timezone && $this->localTime)
                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $this->localTime }} ({{ str_replace('_', ' ', $member->timezone) }})</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Contact Info --}}
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                                @if($member->email)
                                    <a href="mailto:{{ $member->email }}" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ $member->email }}</span>
                                    </a>
                                @endif
                                @if($member->phone)
                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <span>{{ $member->phone }}</span>
                                    </div>
                                @endif
                                @if($member->linkedin)
                                    <a href="{{ $member->linkedin }}" target="_blank" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                        </svg>
                                        <span>LinkedIn</span>
                                    </a>
                                @endif
                            </div>

                            {{-- Edit Button for Admins --}}
                            @if(auth()->user()?->isAdmin())
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <button wire:click="startEditing"
                                        class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                                        Edit Profile
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Main Content Area --}}
                <div class="lg:col-span-2 space-y-6">
                    @if($editing)
                        {{-- Edit Form --}}
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Profile</h3>
                            <form wire:submit="save" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                        <input type="text" wire:model="name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                        <input type="text" wire:model="title" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div x-data="{
                                        open: false,
                                        search: @entangle('location'),
                                        cities: [
                                            // US Cities
                                            { name: 'Washington, DC', timezone: 'America/New_York' },
                                            { name: 'New York, NY', timezone: 'America/New_York' },
                                            { name: 'Boston, MA', timezone: 'America/New_York' },
                                            { name: 'Philadelphia, PA', timezone: 'America/New_York' },
                                            { name: 'Atlanta, GA', timezone: 'America/New_York' },
                                            { name: 'Miami, FL', timezone: 'America/New_York' },
                                            { name: 'Cleveland, OH', timezone: 'America/New_York' },
                                            { name: 'Detroit, MI', timezone: 'America/Detroit' },
                                            { name: 'Chicago, IL', timezone: 'America/Chicago' },
                                            { name: 'Dallas, TX', timezone: 'America/Chicago' },
                                            { name: 'Houston, TX', timezone: 'America/Chicago' },
                                            { name: 'Austin, TX', timezone: 'America/Chicago' },
                                            { name: 'San Antonio, TX', timezone: 'America/Chicago' },
                                            { name: 'Minneapolis, MN', timezone: 'America/Chicago' },
                                            { name: 'St. Louis, MO', timezone: 'America/Chicago' },
                                            { name: 'Kansas City, MO', timezone: 'America/Chicago' },
                                            { name: 'New Orleans, LA', timezone: 'America/Chicago' },
                                            { name: 'Nashville, TN', timezone: 'America/Chicago' },
                                            { name: 'Denver, CO', timezone: 'America/Denver' },
                                            { name: 'Boise, ID', timezone: 'America/Boise' },
                                            { name: 'Phoenix, AZ', timezone: 'America/Phoenix' },
                                            { name: 'Tucson, AZ', timezone: 'America/Phoenix' },
                                            { name: 'Albuquerque, NM', timezone: 'America/Denver' },
                                            { name: 'Salt Lake City, UT', timezone: 'America/Denver' },
                                            { name: 'Las Vegas, NV', timezone: 'America/Los_Angeles' },
                                            { name: 'Reno, NV', timezone: 'America/Los_Angeles' },
                                            { name: 'Los Angeles, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'San Francisco, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'San Jose, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'San Diego, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Sacramento, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Redwood City, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Palo Alto, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Oakland, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Berkeley, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Fresno, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Long Beach, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Irvine, CA', timezone: 'America/Los_Angeles' },
                                            { name: 'Seattle, WA', timezone: 'America/Los_Angeles' },
                                            { name: 'Tacoma, WA', timezone: 'America/Los_Angeles' },
                                            { name: 'Spokane, WA', timezone: 'America/Los_Angeles' },
                                            { name: 'Portland, OR', timezone: 'America/Los_Angeles' },
                                            { name: 'Eugene, OR', timezone: 'America/Los_Angeles' },
                                            { name: 'Anchorage, AK', timezone: 'America/Anchorage' },
                                            { name: 'Honolulu, HI', timezone: 'Pacific/Honolulu' },
                                            // Tennessee
                                            { name: 'Memphis, TN', timezone: 'America/Chicago' },
                                            { name: 'Jackson, TN', timezone: 'America/Chicago' },
                                            { name: 'Huron, TN', timezone: 'America/Chicago' },
                                            { name: 'Knoxville, TN', timezone: 'America/New_York' },
                                            { name: 'Chattanooga, TN', timezone: 'America/New_York' },
                                            // More US Cities
                                            { name: 'Charlotte, NC', timezone: 'America/New_York' },
                                            { name: 'Raleigh, NC', timezone: 'America/New_York' },
                                            { name: 'Durham, NC', timezone: 'America/New_York' },
                                            { name: 'Richmond, VA', timezone: 'America/New_York' },
                                            { name: 'Virginia Beach, VA', timezone: 'America/New_York' },
                                            { name: 'Baltimore, MD', timezone: 'America/New_York' },
                                            { name: 'Pittsburgh, PA', timezone: 'America/New_York' },
                                            { name: 'Columbus, OH', timezone: 'America/New_York' },
                                            { name: 'Cincinnati, OH', timezone: 'America/New_York' },
                                            { name: 'Indianapolis, IN', timezone: 'America/Indiana/Indianapolis' },
                                            { name: 'Louisville, KY', timezone: 'America/Kentucky/Louisville' },
                                            { name: 'Milwaukee, WI', timezone: 'America/Chicago' },
                                            { name: 'Madison, WI', timezone: 'America/Chicago' },
                                            { name: 'Omaha, NE', timezone: 'America/Chicago' },
                                            { name: 'Oklahoma City, OK', timezone: 'America/Chicago' },
                                            { name: 'Tulsa, OK', timezone: 'America/Chicago' },
                                            { name: 'Little Rock, AR', timezone: 'America/Chicago' },
                                            { name: 'Birmingham, AL', timezone: 'America/Chicago' },
                                            { name: 'Jacksonville, FL', timezone: 'America/New_York' },
                                            { name: 'Tampa, FL', timezone: 'America/New_York' },
                                            { name: 'Orlando, FL', timezone: 'America/New_York' },
                                            // Europe
                                            { name: 'London, UK', timezone: 'Europe/London' },
                                            { name: 'Paris, France', timezone: 'Europe/Paris' },
                                            { name: 'Berlin, Germany', timezone: 'Europe/Berlin' },
                                            { name: 'Amsterdam, Netherlands', timezone: 'Europe/Amsterdam' },
                                            { name: 'Brussels, Belgium', timezone: 'Europe/Brussels' },
                                            { name: 'Rome, Italy', timezone: 'Europe/Rome' },
                                            { name: 'Madrid, Spain', timezone: 'Europe/Madrid' },
                                            { name: 'Lisbon, Portugal', timezone: 'Europe/Lisbon' },
                                            { name: 'Dublin, Ireland', timezone: 'Europe/Dublin' },
                                            { name: 'Stockholm, Sweden', timezone: 'Europe/Stockholm' },
                                            { name: 'Oslo, Norway', timezone: 'Europe/Oslo' },
                                            { name: 'Copenhagen, Denmark', timezone: 'Europe/Copenhagen' },
                                            { name: 'Vienna, Austria', timezone: 'Europe/Vienna' },
                                            { name: 'Zurich, Switzerland', timezone: 'Europe/Zurich' },
                                            { name: 'Geneva, Switzerland', timezone: 'Europe/Zurich' },
                                            { name: 'Warsaw, Poland', timezone: 'Europe/Warsaw' },
                                            { name: 'Prague, Czech Republic', timezone: 'Europe/Prague' },
                                            { name: 'Athens, Greece', timezone: 'Europe/Athens' },
                                            // Americas
                                            { name: 'Toronto, Canada', timezone: 'America/Toronto' },
                                            { name: 'Vancouver, Canada', timezone: 'America/Vancouver' },
                                            { name: 'Montreal, Canada', timezone: 'America/Montreal' },
                                            { name: 'Mexico City, Mexico', timezone: 'America/Mexico_City' },
                                            { name: 'São Paulo, Brazil', timezone: 'America/Sao_Paulo' },
                                            { name: 'Sao Paulo, Brazil', timezone: 'America/Sao_Paulo' },
                                            { name: 'Sao Paolo, Brazil', timezone: 'America/Sao_Paulo' },
                                            { name: 'Rio de Janeiro, Brazil', timezone: 'America/Sao_Paulo' },
                                            { name: 'Buenos Aires, Argentina', timezone: 'America/Argentina/Buenos_Aires' },
                                            { name: 'Bogotá, Colombia', timezone: 'America/Bogota' },
                                            { name: 'Lima, Peru', timezone: 'America/Lima' },
                                            { name: 'Santiago, Chile', timezone: 'America/Santiago' },
                                            // Asia & Pacific
                                            { name: 'Tokyo, Japan', timezone: 'Asia/Tokyo' },
                                            { name: 'Seoul, South Korea', timezone: 'Asia/Seoul' },
                                            { name: 'Beijing, China', timezone: 'Asia/Shanghai' },
                                            { name: 'Shanghai, China', timezone: 'Asia/Shanghai' },
                                            { name: 'Hong Kong', timezone: 'Asia/Hong_Kong' },
                                            { name: 'Singapore', timezone: 'Asia/Singapore' },
                                            { name: 'Bangkok, Thailand', timezone: 'Asia/Bangkok' },
                                            { name: 'Mumbai, India', timezone: 'Asia/Kolkata' },
                                            { name: 'New Delhi, India', timezone: 'Asia/Kolkata' },
                                            { name: 'Dubai, UAE', timezone: 'Asia/Dubai' },
                                            { name: 'Tel Aviv, Israel', timezone: 'Asia/Jerusalem' },
                                            { name: 'Sydney, Australia', timezone: 'Australia/Sydney' },
                                            { name: 'Melbourne, Australia', timezone: 'Australia/Melbourne' },
                                            { name: 'Brisbane, Australia', timezone: 'Australia/Brisbane' },
                                            { name: 'Perth, Australia', timezone: 'Australia/Perth' },
                                            { name: 'Auckland, New Zealand', timezone: 'Pacific/Auckland' },
                                            // Africa & Middle East
                                            { name: 'Cairo, Egypt', timezone: 'Africa/Cairo' },
                                            { name: 'Johannesburg, South Africa', timezone: 'Africa/Johannesburg' },
                                            { name: 'Cape Town, South Africa', timezone: 'Africa/Johannesburg' },
                                            { name: 'Nairobi, Kenya', timezone: 'Africa/Nairobi' },
                                            { name: 'Lagos, Nigeria', timezone: 'Africa/Lagos' },
                                        ],
                                        get filtered() {
                                            if (!this.search || this.search.length < 2) return [];
                                            const s = this.search.toLowerCase();
                                            return this.cities.filter(c => c.name.toLowerCase().includes(s)).slice(0, 8);
                                        },
                                        selectCity(city) {
                                            this.search = city.name;
                                            $wire.set('location', city.name);
                                            $wire.set('timezone', city.timezone);
                                            this.open = false;
                                        }
                                    }" class="relative">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                                        <input type="text" x-model="search" @focus="open = true" @click.away="open = false"
                                            @input="open = true"
                                            placeholder="Start typing a city..."
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            autocomplete="off">
                                        <div x-show="open && filtered.length > 0" x-cloak
                                            class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <template x-for="city in filtered" :key="city.name">
                                                <button type="button" @click="selectCity(city)"
                                                    class="w-full px-4 py-2 text-left hover:bg-indigo-50 dark:hover:bg-indigo-900/30 flex items-center justify-between">
                                                    <span class="text-gray-900 dark:text-white" x-text="city.name"></span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="city.timezone.replace('_', ' ')"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                                        <select wire:model="timezone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <optgroup label="US & Canada">
                                                <option value="America/New_York">Eastern Time (ET)</option>
                                                <option value="America/Detroit">Detroit (ET)</option>
                                                <option value="America/Toronto">Toronto (ET)</option>
                                                <option value="America/Montreal">Montreal (ET)</option>
                                                <option value="America/Chicago">Central Time (CT)</option>
                                                <option value="America/Denver">Mountain Time (MT)</option>
                                                <option value="America/Boise">Boise (MT)</option>
                                                <option value="America/Phoenix">Arizona (no DST)</option>
                                                <option value="America/Los_Angeles">Pacific Time (PT)</option>
                                                <option value="America/Vancouver">Vancouver (PT)</option>
                                                <option value="America/Anchorage">Alaska Time</option>
                                                <option value="Pacific/Honolulu">Hawaii Time (no DST)</option>
                                            </optgroup>
                                            <optgroup label="Latin America">
                                                <option value="America/Mexico_City">Mexico City</option>
                                                <option value="America/Bogota">Bogotá</option>
                                                <option value="America/Lima">Lima</option>
                                                <option value="America/Santiago">Santiago</option>
                                                <option value="America/Sao_Paulo">São Paulo</option>
                                                <option value="America/Argentina/Buenos_Aires">Buenos Aires</option>
                                            </optgroup>
                                            <optgroup label="Europe">
                                                <option value="Europe/London">London (GMT/BST)</option>
                                                <option value="Europe/Dublin">Dublin</option>
                                                <option value="Europe/Lisbon">Lisbon</option>
                                                <option value="Europe/Paris">Paris (CET/CEST)</option>
                                                <option value="Europe/Berlin">Berlin</option>
                                                <option value="Europe/Amsterdam">Amsterdam</option>
                                                <option value="Europe/Brussels">Brussels</option>
                                                <option value="Europe/Rome">Rome</option>
                                                <option value="Europe/Madrid">Madrid</option>
                                                <option value="Europe/Zurich">Zurich</option>
                                                <option value="Europe/Vienna">Vienna</option>
                                                <option value="Europe/Stockholm">Stockholm</option>
                                                <option value="Europe/Oslo">Oslo</option>
                                                <option value="Europe/Copenhagen">Copenhagen</option>
                                                <option value="Europe/Warsaw">Warsaw</option>
                                                <option value="Europe/Prague">Prague</option>
                                                <option value="Europe/Athens">Athens (EET/EEST)</option>
                                                <option value="Europe/Helsinki">Helsinki</option>
                                                <option value="Europe/Moscow">Moscow</option>
                                            </optgroup>
                                            <optgroup label="Asia & Pacific">
                                                <option value="Asia/Tokyo">Tokyo (JST)</option>
                                                <option value="Asia/Seoul">Seoul (KST)</option>
                                                <option value="Asia/Shanghai">Beijing/Shanghai</option>
                                                <option value="Asia/Hong_Kong">Hong Kong</option>
                                                <option value="Asia/Taipei">Taipei</option>
                                                <option value="Asia/Singapore">Singapore</option>
                                                <option value="Asia/Bangkok">Bangkok (ICT)</option>
                                                <option value="Asia/Jakarta">Jakarta</option>
                                                <option value="Asia/Manila">Manila</option>
                                                <option value="Asia/Kolkata">India (IST)</option>
                                                <option value="Asia/Dubai">Dubai (GST)</option>
                                                <option value="Asia/Jerusalem">Israel (IST/IDT)</option>
                                                <option value="Australia/Sydney">Sydney (AEST/AEDT)</option>
                                                <option value="Australia/Melbourne">Melbourne</option>
                                                <option value="Australia/Brisbane">Brisbane</option>
                                                <option value="Australia/Perth">Perth (AWST)</option>
                                                <option value="Pacific/Auckland">Auckland (NZST/NZDT)</option>
                                            </optgroup>
                                            <optgroup label="Africa & Middle East">
                                                <option value="Africa/Cairo">Cairo (EET)</option>
                                                <option value="Africa/Johannesburg">Johannesburg (SAST)</option>
                                                <option value="Africa/Lagos">Lagos (WAT)</option>
                                                <option value="Africa/Nairobi">Nairobi (EAT)</option>
                                                <option value="Africa/Casablanca">Casablanca</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                                        <input type="text" wire:model="phone" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn URL</label>
                                        <input type="text" wire:model="linkedin" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Photo URL</label>
                                    <input type="url" wire:model="photo_url" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Short Bio (one-liner)</label>
                                    <input type="text" wire:model="bio_short" maxlength="255" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Medium Bio (paragraph)</label>
                                    <textarea wire:model="bio_medium" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Bio</label>
                                    <textarea wire:model="bio" rows="5" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                                </div>

                                {{-- Publications --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Publications</label>
                                    <div class="flex gap-2 mb-2">
                                        <input type="text" wire:model="newPublication" placeholder="Add publication title or URL"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            wire:keydown.enter.prevent="addPublication">
                                        <button type="button" wire:click="addPublication" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add</button>
                                    </div>
                                    @if(count($publications) > 0)
                                        <ul class="space-y-2">
                                            @foreach($publications as $index => $pub)
                                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                                    <span class="flex-1">{{ $pub }}</span>
                                                    <button type="button" wire:click="removePublication({{ $index }})" class="text-red-500 hover:text-red-700">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <div class="flex gap-3 pt-4">
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Changes</button>
                                    <button type="button" wire:click="cancelEditing" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">Cancel</button>
                                </div>
                            </form>
                        </div>
                    @else
                        {{-- Bio Section --}}
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">About</h3>
                            @if($member->bio_short)
                                <p class="text-indigo-600 dark:text-indigo-400 font-medium mb-3">{{ $member->bio_short }}</p>
                            @endif
                            @if($member->bio_medium)
                                <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $member->bio_medium }}</p>
                            @endif
                            @if($member->bio)
                                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ $member->bio }}</p>
                            @endif
                        </div>

                        {{-- Publications --}}
                        @if($member->publications && count($member->publications) > 0)
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Publications</h3>
                                <ul class="space-y-2">
                                    @foreach($member->publications as $pub)
                                        <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4 mt-0.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            @if(str_starts_with($pub, 'http'))
                                                <a href="{{ $pub }}" target="_blank" class="hover:text-indigo-600">{{ $pub }}</a>
                                            @else
                                                <span>{{ $pub }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Projects --}}
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Associated Projects</h3>
                            @php
                                $projects = \App\Models\Project::whereHas('staff', fn($q) => $q->where('users.id', $member->id))->get();
                            @endphp
                            @if($projects->count() > 0)
                                <div class="space-y-3">
                                    @foreach($projects as $project)
                                        <a href="{{ route('projects.show', $project) }}" wire:navigate
                                            class="block p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $project->name }}</div>
                                            @if($project->pivot?->role)
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Role: {{ $project->pivot->role }}</div>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm">No projects assigned yet.</p>
                            @endif
                        </div>

                        {{-- Recent Activity --}}
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h3>
                            @if($this->recentMeetings->count() > 0)
                                <div class="space-y-3">
                                    @foreach($this->recentMeetings as $meeting)
                                        <a href="{{ route('meetings.show', $meeting) }}" wire:navigate
                                            class="block p-3 rounded-lg bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $meeting->title }}</span>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $meeting->meeting_date?->format('M j, Y') }}
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400 text-sm">No recent meetings logged.</p>
                            @endif
                        </div>

                        {{-- Travel Profile (Management View) --}}
                        @if(auth()->user()?->isAdmin())
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    Travel Profile
                                </h3>

                                @if($this->travelProfile)
                                    @php $tp = $this->travelProfile; @endphp
                                    
                                    {{-- Passport Expiration Warning --}}
                                    @if($tp->isPassportExpired())
                                        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                                            <p class="text-sm text-red-700 dark:text-red-400 font-medium">⚠️ Passport is expired!</p>
                                        </div>
                                    @elseif($tp->isPassportExpiringSoon())
                                        <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg">
                                            <p class="text-sm text-amber-700 dark:text-amber-400 font-medium">⚠️ Passport expires {{ $tp->passport_expiration->diffForHumans() }}</p>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        {{-- Birthday --}}
                                        @if($tp->birthday)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Birthday</span>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $tp->birthday->format('F j') }}</p>
                                            </div>
                                        @endif

                                        {{-- Passport (Masked) --}}
                                        @if($tp->masked_passport)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Passport</span>
                                                <p class="font-medium text-gray-900 dark:text-white font-mono">{{ $tp->masked_passport }}</p>
                                            </div>
                                        @endif

                                        {{-- Passport Country --}}
                                        @if($tp->passport_country)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Passport Country</span>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $tp->passport_country }}</p>
                                            </div>
                                        @endif

                                        {{-- Passport Expiration --}}
                                        @if($tp->passport_expiration)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Passport Expires</span>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $tp->passport_expiration->format('M j, Y') }}</p>
                                            </div>
                                        @endif

                                        {{-- Known Traveler (Masked) --}}
                                        @if($tp->masked_tsa_precheck)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">TSA PreCheck</span>
                                                <p class="font-medium text-gray-900 dark:text-white font-mono">{{ $tp->masked_tsa_precheck }}</p>
                                            </div>
                                        @endif

                                        @if($tp->masked_global_entry)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Global Entry</span>
                                                <p class="font-medium text-gray-900 dark:text-white font-mono">{{ $tp->masked_global_entry }}</p>
                                            </div>
                                        @endif

                                        {{-- Seat Preference --}}
                                        @if($tp->seat_preference && $tp->seat_preference !== 'no_preference')
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Seat Preference</span>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($tp->seat_preference) }}</p>
                                            </div>
                                        @endif

                                        {{-- Dietary --}}
                                        @if($tp->dietary_restrictions)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Dietary</span>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ $tp->dietary_restrictions }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Frequent Flyer Programs --}}
                                    @if($tp->frequent_flyer_programs && count($tp->frequent_flyer_programs) > 0)
                                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Frequent Flyer Programs</span>
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                @foreach($tp->frequent_flyer_programs as $ff)
                                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs rounded-full">
                                                        {{ $ff['airline'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Emergency Contact --}}
                                    @if($tp->emergency_contact_name)
                                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Emergency Contact</span>
                                            <p class="font-medium text-gray-900 dark:text-white mt-1">
                                                {{ $tp->emergency_contact_name }}
                                                @if($tp->emergency_contact_relationship)
                                                    <span class="text-gray-500 dark:text-gray-400 font-normal">({{ $tp->emergency_contact_relationship }})</span>
                                                @endif
                                            </p>
                                            @if($tp->emergency_contact_phone)
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $tp->emergency_contact_phone }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-sm">No travel profile on file.</p>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
