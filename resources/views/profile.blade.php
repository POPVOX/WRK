<x-app-layout>
    <x-slot name="header">
        <div class="hidden">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Profile') }}
            </h2>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/50 rounded-lg hover:bg-red-200 dark:hover:bg-red-900 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Log Out
                </button>
            </form>
        </div>
    </x-slot>

    <div class="desk-page desk-page-narrow">
        <x-desk-page-header eyebrow="Account" title="Profile & settings" description="Keep your account, travel preferences, and security details current.">
            <x-slot:actions>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="desk-button-secondary text-[#b33a2b]">Log out</button>
                </form>
            </x-slot:actions>
        </x-desk-page-header>
        <div class="space-y-6">
            {{-- Travel Profile Link --}}
            <a href="{{ route('profile.travel') }}" wire:navigate
               class="app-surface block p-4 sm:p-6 transition-colors hover:bg-[#f5f1e8] group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-[#f3eee3] rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#8a4b2d]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-[#26221c]">Travel profile</h3>
                            <p class="text-sm text-[#5c574d]">Passport, loyalty programs, preferences, and emergency contact</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-[#8a8578] group-hover:text-[#8a4b2d] group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
