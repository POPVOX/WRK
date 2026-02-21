<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="PVOXWRK" class="h-8 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Workspace') }}
                    </x-nav-link>
                    <x-nav-link :href="route('meetings.index')" :active="request()->routeIs('meetings.*')"
                        wire:navigate>
                        {{ __('Meetings') }}
                    </x-nav-link>
                    <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')"
                        wire:navigate>
                        {{ __('Projects') }}
                    </x-nav-link>
                    <x-nav-link :href="route('organizations.index')" :active="request()->routeIs('organizations.*')"
                        wire:navigate>
                        {{ __('Organizations') }}
                    </x-nav-link>
                    <x-nav-link :href="route('people.index')" :active="request()->routeIs('people.*')" wire:navigate>
                        {{ __('People') }}
                    </x-nav-link>
                    <x-nav-link :href="route('media.index')" :active="request()->routeIs('media.*')" wire:navigate>
                        {{ __('Media') }}
                    </x-nav-link>
                    <x-nav-link :href="route('team.hub')" :active="request()->routeIs('team.*')" wire:navigate>
                        {{ __('Team') }}
                    </x-nav-link>

                    {{-- Management Dropdown (Admin Only) --}}
                    @if(auth()->user()->isAdmin())
                        <div class="hidden sm:flex sm:items-center" x-data="{ open: false }">
                            <div class="relative">
                                <button @click="open = !open" @click.away="open = false"
                                    class="inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none {{ request()->routeIs('admin.*') || request()->routeIs('accomplishments.team') || request()->routeIs('grants.*') ? 'border-b-2 border-indigo-400 dark:border-indigo-600 text-gray-900 dark:text-gray-100' : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700' }}">
                                    {{ __('Management') }}
                                    <svg class="ms-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute left-0 z-50 mt-2 w-48 rounded-md shadow-lg origin-top-left"
                                    style="display: none;">
                                    <div class="rounded-md ring-1 ring-black ring-opacity-5 py-1 bg-white dark:bg-gray-700">
                                        <a href="{{ route('accomplishments.team') }}" wire:navigate
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('accomplishments.team') ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                            📊 Team Dashboard
                                        </a>
                                        <a href="{{ route('admin.staff') }}" wire:navigate
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('admin.staff') ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                            👥 Staff Management
                                        </a>
                                        <a href="{{ route('grants.index') }}" wire:navigate
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('grants.*') ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                            💰 Funders & Grants
                                        </a>
                                        <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                                        <a href="{{ route('admin.feedback') }}" wire:navigate
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('admin.feedback') ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                            💬 Feedback
                                        </a>
                                        <a href="{{ route('admin.metrics') }}" wire:navigate
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('admin.metrics') ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                            📈 Metrics
                                        </a>
                                        <a href="{{ route('admin.permissions') }}" wire:navigate
                                            class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 {{ request()->routeIs('admin.permissions') ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                            🔒 Permissions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                                x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('accomplishments.index')" wire:navigate>
                            🏆 {{ __('My Accomplishments') }}
                        </x-dropdown-link>

                        <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Workspace') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('meetings.index')" :active="request()->routeIs('meetings.*')"
                wire:navigate>
                {{ __('Meetings') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('organizations.index')" :active="request()->routeIs('organizations.*')"
                wire:navigate>
                {{ __('Organizations') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('people.index')" :active="request()->routeIs('people.*')" wire:navigate>
                {{ __('People') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('media.index')" :active="request()->routeIs('media.*')" wire:navigate>
                {{ __('Media') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('team.hub')" :active="request()->routeIs('team.*')" wire:navigate>
                {{ __('Team') }}
            </x-responsive-nav-link>

            {{-- Management Section (Admin Only) --}}
            @if(auth()->user()->isAdmin())
                <div class="pt-2 pb-1 border-t border-gray-200 dark:border-gray-600">
                    <div class="px-4 py-2">
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Management</span>
                    </div>
                    <x-responsive-nav-link :href="route('accomplishments.team')" :active="request()->routeIs('accomplishments.team')" wire:navigate>
                        📊 {{ __('Team Dashboard') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.staff')" :active="request()->routeIs('admin.staff')" wire:navigate>
                        👥 {{ __('Staff Management') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('grants.index')" :active="request()->routeIs('grants.*')" wire:navigate>
                        💰 {{ __('Funders & Grants') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.feedback')" :active="request()->routeIs('admin.feedback')" wire:navigate>
                        💬 {{ __('Feedback') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.metrics')" :active="request()->routeIs('admin.metrics')" wire:navigate>
                        📈 {{ __('Metrics') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.permissions')" :active="request()->routeIs('admin.permissions')" wire:navigate>
                        🔒 {{ __('Permissions') }}
                    </x-responsive-nav-link>
                </div>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200"
                    x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                    x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
