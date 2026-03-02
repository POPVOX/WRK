<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'WRK') }} - {{ config('app.name', 'WRK') }}</title>
    <meta name="description" content="WRK workspace for POPVOX Foundation: clean execution, agent collaboration, and institutional memory.">
    <meta name="theme-color" content="#1d4f7d">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/wrk favicon.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/wrk favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/wrk favicon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="antialiased">
    @php
        $user = auth()->user();

        $coreNav = [
            ['label' => 'Assistant', 'route' => 'dashboard', 'active' => ['dashboard'], 'badge' => null],
            ['label' => 'Intelligence', 'route' => 'intelligence.index', 'active' => ['intelligence.*'], 'badge' => 'AI'],
            ['label' => 'Inbox', 'route' => 'communications.inbox', 'active' => ['communications.inbox'], 'badge' => null],
            ['label' => 'Notifications', 'route' => 'notifications.index', 'active' => ['notifications.index'], 'badge' => null],
            ['label' => 'Projects', 'route' => 'projects.index', 'active' => ['projects.*'], 'badge' => null],
            ['label' => 'Meetings', 'route' => 'meetings.index', 'active' => ['meetings.*'], 'badge' => null],
            ['label' => 'Travel', 'route' => 'travel.index', 'active' => ['travel.*'], 'badge' => null],
            ['label' => 'Funding', 'route' => 'funding.index', 'active' => ['funding.*'], 'badge' => null],
        ];

        $orgNav = [
            ['label' => 'Contacts', 'route' => 'contacts.index', 'active' => ['contacts.*', 'people.*'], 'badge' => null],
            ['label' => 'Organizations', 'route' => 'organizations.index', 'active' => ['organizations.*'], 'badge' => null],
            ['label' => 'Outreach', 'route' => 'communications.outreach', 'active' => ['communications.outreach'], 'badge' => null],
            ['label' => 'Media', 'route' => 'media.index', 'active' => ['media.*'], 'badge' => null],
            ['label' => 'Team', 'route' => 'team.hub', 'active' => ['team.*'], 'badge' => null],
        ];

        $adminNav = [];
        if ($user && $user->isAdmin()) {
            $adminNav = [
                ['label' => 'Permissions', 'route' => 'admin.permissions', 'active' => ['admin.permissions']],
                ['label' => 'Agent Policies', 'route' => 'admin.agent-policies', 'active' => ['admin.agent-policies', 'admin.agents.prompt-preview']],
                ['label' => 'Integrations', 'route' => 'admin.integrations', 'active' => ['admin.integrations']],
                ['label' => 'Metrics', 'route' => 'admin.metrics', 'active' => ['admin.metrics']],
                ['label' => 'Feedback', 'route' => 'admin.feedback', 'active' => ['admin.feedback']],
            ];
        }
        if ($user && $user->isManagement()) {
            $adminNav[] = ['label' => 'Notifications', 'route' => 'notifications.admin', 'active' => ['notifications.admin']];
        }

        $routeExists = static fn (array $item): bool => Route::has($item['route']);
        $coreNav = array_values(array_filter($coreNav, $routeExists));
        $orgNav = array_values(array_filter($orgNav, $routeExists));
        $adminNav = array_values(array_filter($adminNav, $routeExists));

        $isActive = static function (array $item): bool {
            foreach ($item['active'] as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        };

        $linkClasses = static fn (bool $active): string => 'app-nav-link '.($active ? 'app-nav-link-active' : '');
    @endphp

    <div
        x-data="{
            mobileNavOpen: false,
            mobileUserOpen: false,
            desktopNavCollapsed: (() => {
                try {
                    return JSON.parse(window.localStorage.getItem('wrk.desktop_nav_collapsed') ?? 'false') === true;
                } catch (error) {
                    return false;
                }
            })(),
        }"
        x-init="$watch('desktopNavCollapsed', value => window.localStorage.setItem('wrk.desktop_nav_collapsed', JSON.stringify(!!value)))"
        class="app-shell"
        :style="desktopNavCollapsed ? 'grid-template-columns: 0 minmax(0, 1fr);' : ''"
    >
        <aside class="app-sidebar hidden lg:flex lg:flex-col overflow-hidden transition-all duration-200"
               :class="desktopNavCollapsed ? 'lg:opacity-0 lg:pointer-events-none lg:-translate-x-2' : 'lg:opacity-100 lg:translate-x-0'">
            <div class="px-4 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between gap-2">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.png') }}" alt="WRK" class="h-8 w-auto">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">WRK</p>
                            <p class="text-xs app-muted">Agent Workspace</p>
                        </div>
                    </a>
                    <button
                        type="button"
                        @click="desktopNavCollapsed = true"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100"
                        aria-label="Collapse navigation"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="px-3 py-3 border-b border-gray-200">
                <a href="{{ route('dashboard') }}#workspace-assistant" wire:navigate class="app-surface block px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Open Assistant Console
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                <section>
                    <p class="px-2 pb-2 text-[11px] font-semibold tracking-[0.14em] uppercase app-muted">Core Work</p>
                    <div class="space-y-1.5">
                        @foreach($coreNav as $item)
                            @php($active = $isActive($item))
                            <a href="{{ route($item['route']) }}" wire:navigate class="{{ $linkClasses($active) }}">
                                <span>{{ $item['label'] }}</span>
                                @if(!empty($item['badge']))
                                    <span class="app-nav-pill">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>

                <section>
                    <p class="px-2 pb-2 text-[11px] font-semibold tracking-[0.14em] uppercase app-muted">Organization</p>
                    <div class="space-y-1.5">
                        @foreach($orgNav as $item)
                            @php($active = $isActive($item))
                            <a href="{{ route($item['route']) }}" wire:navigate class="{{ $linkClasses($active) }}">
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>

                @if($user && $user->isManagement() && !empty($adminNav))
                    <section>
                        <p class="px-2 pb-2 text-[11px] font-semibold tracking-[0.14em] uppercase app-muted">Admin</p>
                        <div class="space-y-1.5">
                            @foreach($adminNav as $item)
                                @php($active = $isActive($item))
                                <a href="{{ route($item['route']) }}" wire:navigate class="{{ $linkClasses($active) }}">
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </nav>

            @auth
                <div class="border-t border-gray-200 px-3 py-3">
                    <div class="app-surface px-3 py-3">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs app-muted truncate mt-0.5">{{ auth()->user()->email }}</p>
                        <div class="mt-3 flex items-center gap-2">
                            <a href="{{ route('profile') }}" wire:navigate
                               class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Profile
                            </a>
                            @if(Route::has('accomplishments.index'))
                                <a href="{{ route('accomplishments.index') }}" wire:navigate
                                   class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    Wins
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth
        </aside>

        <div class="app-main">
            <button
                type="button"
                x-cloak
                x-show="desktopNavCollapsed"
                @click="desktopNavCollapsed = false"
                class="fixed left-3 top-3 z-40 hidden h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-100 lg:inline-flex"
                aria-label="Expand navigation"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <header class="app-topbar lg:hidden sticky top-0 z-40">
                <div class="px-3 py-2.5 flex items-center justify-between gap-3">
                    <button type="button"
                            @click="mobileNavOpen = !mobileNavOpen"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                        <img src="{{ asset('images/logo.png') }}" alt="WRK" class="h-7 w-auto">
                        <span class="text-sm font-semibold text-gray-900">WRK</span>
                    </a>

                    <button type="button"
                            @click="mobileUserOpen = !mobileUserOpen"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0"/>
                        </svg>
                    </button>
                </div>
            </header>

            <div x-cloak x-show="mobileNavOpen" @click.self="mobileNavOpen = false"
                 class="lg:hidden fixed inset-0 z-30 bg-black/25">
                <div class="h-full w-80 max-w-[90vw] bg-white border-r border-gray-200 shadow-lg overflow-y-auto p-3">
                    <div class="pb-3 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-900">Navigation</p>
                    </div>
                    <div class="pt-3 space-y-6">
                        <section>
                            <p class="px-2 pb-2 text-[11px] font-semibold tracking-[0.14em] uppercase app-muted">Core Work</p>
                            <div class="space-y-1.5">
                                @foreach($coreNav as $item)
                                    @php($active = $isActive($item))
                                    <a href="{{ route($item['route']) }}" wire:navigate @click="mobileNavOpen = false"
                                       class="{{ $linkClasses($active) }}">
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                        <section>
                            <p class="px-2 pb-2 text-[11px] font-semibold tracking-[0.14em] uppercase app-muted">Organization</p>
                            <div class="space-y-1.5">
                                @foreach($orgNav as $item)
                                    @php($active = $isActive($item))
                                    <a href="{{ route($item['route']) }}" wire:navigate @click="mobileNavOpen = false"
                                       class="{{ $linkClasses($active) }}">
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                        @if($user && $user->isManagement() && !empty($adminNav))
                            <section>
                                <p class="px-2 pb-2 text-[11px] font-semibold tracking-[0.14em] uppercase app-muted">Admin</p>
                                <div class="space-y-1.5">
                                    @foreach($adminNav as $item)
                                        @php($active = $isActive($item))
                                        <a href="{{ route($item['route']) }}" wire:navigate @click="mobileNavOpen = false"
                                           class="{{ $linkClasses($active) }}">
                                            <span>{{ $item['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                </div>
            </div>

            @auth
                <div x-cloak x-show="mobileUserOpen" @click.self="mobileUserOpen = false"
                     class="lg:hidden fixed inset-0 z-40 bg-black/25">
                    <div class="absolute right-3 top-14 w-72 app-surface p-3">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs app-muted truncate mt-0.5">{{ auth()->user()->email }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('profile') }}" wire:navigate @click="mobileUserOpen = false"
                               class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth

            <main class="app-page">
                @if (isset($header))
                    <section class="app-surface p-4 mb-4">
                        {{ $header }}
                    </section>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @auth
        <livewire:notifications.notifications-panel />
        <livewire:feedback-widget />
    @endauth

    @stack('scripts')
</body>
</html>
