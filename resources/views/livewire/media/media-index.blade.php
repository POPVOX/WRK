<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Media & Press</h1>
            <p class="text-gray-600 dark:text-gray-400">Track coverage, manage journalist relationships, and coordinate
                outreach</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openClipModal"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                Log Clip
            </button>
            <button wire:click="openPitchModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Pitch
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex gap-6 overflow-x-auto">
            <button wire:click="setTab('dashboard')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'dashboard' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Dashboard
            </button>
            <button wire:click="setTab('coverage')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'coverage' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Coverage
                @if($stats['pending_review'] > 0)
                    <span
                        class="ml-1.5 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $stats['pending_review'] }}</span>
                @endif
            </button>
            <button wire:click="setTab('pitches')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'pitches' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Pitches
            </button>
            <button wire:click="setTab('inquiries')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'inquiries' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Inquiries
                @if($needsAttention['inquiries_urgent']->count() > 0)
                    <span
                        class="ml-1.5 px-1.5 py-0.5 text-xs bg-red-100 text-red-700 rounded-full">{{ $needsAttention['inquiries_urgent']->count() }}</span>
                @endif
            </button>
            <button wire:click="setTab('contacts')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'contacts' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Press Contacts
            </button>
            <button wire:click="setTab('outlets')"
                class="pb-3 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $activeTab === 'outlets' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                Outlets
                <span
                    class="ml-1.5 px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">{{ $outlets->count() }}</span>
            </button>
        </nav>
    </div>

    {{-- Dashboard Tab --}}
    @if($activeTab === 'dashboard')
        @include('livewire.media.partials.dashboard-tab')
    @endif



    {{-- Coverage Tab --}}
    @if($activeTab === 'coverage')
        @include('livewire.media.partials.coverage-tab')
    @endif

    {{-- Pitches Tab --}}
    @if($activeTab === 'pitches')
        @include('livewire.media.partials.pitches-tab')
    @endif

    {{-- Inquiries Tab --}}
    @if($activeTab === 'inquiries')
        @include('livewire.media.partials.inquiries-tab')
    @endif

    {{-- Contacts Tab --}}
    @if($activeTab === 'contacts')
        @include('livewire.media.partials.contacts-tab')
    @endif

    {{-- Outlets Tab --}}
    @if($activeTab === 'outlets')
        @include('livewire.media.partials.outlets-tab')
    @endif

    {{-- Clip Modal --}}
    @if($showClipModal)
        @include('livewire.media.partials.clip-modal')
    @endif

    {{-- Pitch Modal --}}
    @if($showPitchModal)
        @include('livewire.media.partials.pitch-modal')
    @endif

    {{-- Inquiry Modal --}}
    @if($showInquiryModal)
        @include('livewire.media.partials.inquiry-modal')
    @endif
</div>