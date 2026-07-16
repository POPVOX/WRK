<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'WRK') }} - {{ config('app.name', 'WRK') }}</title>
    <meta name="description" content="WRKBench workspace for POPVOX Foundation.">
    <meta name="theme-color" content="#faf7f1">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/wrk favicon.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/wrk favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/wrk favicon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=newsreader:400,400i,500,500i,600|public-sans:400,500,600,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="antialiased">
    @php
        $user = auth()->user();
        $congressEnabled = (bool) config('features.congressional_directory_ui');
        $dailyNav = [
            ['label' => 'Today', 'route' => 'dashboard', 'active' => ['dashboard'], 'badge' => null],
            ['label' => 'Inbox', 'route' => 'communications.inbox', 'active' => ['communications.inbox', 'needs-you.*', 'notifications.*'], 'badge' => null],
            ['label' => 'Meetings', 'route' => 'meetings.index', 'active' => ['meetings.*'], 'badge' => null],
            ['label' => 'Projects', 'route' => 'projects.index', 'active' => ['projects.*', 'funding.*', 'grants.*'], 'badge' => null],
            ['label' => 'People', 'route' => 'contacts.index', 'active' => ['contacts.*', 'people.*', 'organizations.*', 'team.*', 'congress.index', 'congress.staff.*', 'congress.changes', 'congress.contact-data'], 'badge' => null],
            ['label' => 'Outreach', 'route' => $congressEnabled ? 'congress.campaigns' : 'media.index', 'active' => ['congress.campaigns', 'congress.campaigns.*', 'congress.lists', 'congress.lists.*', 'congress.outreach.*', 'media.*', 'communications.outreach'], 'badge' => null],
            ['label' => 'Travel', 'route' => 'travel.index', 'active' => ['travel.*'], 'badge' => null],
        ];

        $peopleSubnav = [
            ['label' => 'Contacts', 'route' => 'contacts.index', 'active' => ['contacts.*', 'people.*']],
            ['label' => 'Organizations', 'route' => 'organizations.index', 'active' => ['organizations.*']],
        ];
        if ($congressEnabled) {
            $peopleSubnav[] = ['label' => 'Congress directory', 'route' => 'congress.index', 'active' => ['congress.index', 'congress.staff.*', 'congress.changes', 'congress.contact-data']];
        }

        $outreachSubnav = $congressEnabled ? [
            ['label' => 'Campaigns', 'route' => 'congress.campaigns', 'active' => ['congress.campaigns', 'congress.campaigns.*', 'congress.outreach.*']],
            ['label' => 'Lists', 'route' => 'congress.lists', 'active' => ['congress.lists', 'congress.lists.*']],
            ['label' => 'Media clippings', 'route' => 'media.index', 'active' => ['media.*']],
        ] : [
            ['label' => 'Media clippings', 'route' => 'media.index', 'active' => ['media.*']],
        ];

        $shortcuts = [
            ['label' => 'Media clippings', 'route' => 'media.index'],
            ['label' => 'Funding', 'route' => 'funding.index'],
        ];
        if ($congressEnabled) {
            array_unshift($shortcuts, ['label' => 'Congress directory', 'route' => 'congress.index']);
        }

        $adminNav = [];
        if ($user && $user->isAdmin()) {
            $adminNav = [
                ['label' => 'Staff', 'route' => 'admin.staff'],
                ['label' => 'Box Access', 'route' => 'admin.permissions'],
                ['label' => 'Agent Policies', 'route' => 'admin.agent-policies'],
                ['label' => 'Integrations', 'route' => 'admin.integrations'],
                ['label' => 'Metrics', 'route' => 'admin.metrics'],
                ['label' => 'Feedback', 'route' => 'admin.feedback'],
            ];
        }
        if ($user && $user->isManagement()) {
            $adminNav[] = ['label' => 'Attention Pilot', 'route' => 'attention.insights'];
        }

        $routeExists = static fn (array $item): bool => Route::has($item['route']);
        $dailyNav = array_values(array_filter($dailyNav, $routeExists));
        $peopleSubnav = array_values(array_filter($peopleSubnav, $routeExists));
        $outreachSubnav = array_values(array_filter($outreachSubnav, $routeExists));
        $shortcuts = array_values(array_filter($shortcuts, $routeExists));
        $adminNav = array_values(array_filter($adminNav, $routeExists));

        $isActive = static function (array $item): bool {
            foreach ($item['active'] ?? [] as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        };
        $peopleActive = collect($dailyNav)->contains(fn (array $item): bool => $item['label'] === 'People' && $isActive($item));
        $outreachActive = collect($dailyNav)->contains(fn (array $item): bool => $item['label'] === 'Outreach' && $isActive($item));
        $initials = collect(explode(' ', trim((string) ($user?->name ?? 'WRK'))))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    <div x-data="{ mobileNavOpen: false, mobileUserOpen: false }" class="app-shell">
        <aside class="app-sidebar hidden lg:flex lg:flex-col overflow-hidden">
            <div class="px-6 pt-5 pb-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="desk-wordmark text-[#26221c]">WRKBench</a>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-2">
                <div class="space-y-1">
                    @foreach($dailyNav as $item)
                        @php($active = $isActive($item))
                        <a href="{{ route($item['route']) }}" wire:navigate class="app-nav-link {{ $active ? 'app-nav-link-active' : '' }}">
                            <span>{{ $item['label'] }}</span>
                            @if(!empty($item['badge']))
                                <span class="app-nav-pill">{{ $item['badge'] }}</span>
                            @endif
                        </a>

                        @if($item['label'] === 'People' && $peopleActive)
                            <div class="desk-subnav">
                                <p class="mb-1 text-xs font-semibold text-[#26221c]">People</p>
                                @foreach($peopleSubnav as $subitem)
                                    <a href="{{ route($subitem['route']) }}" wire:navigate aria-current="{{ $isActive($subitem) ? 'page' : 'false' }}">
                                        {{ $subitem['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($item['label'] === 'Outreach' && $outreachActive)
                            <div class="desk-subnav">
                                <p class="mb-1 text-xs font-semibold text-[#26221c]">Outreach</p>
                                @foreach($outreachSubnav as $subitem)
                                    <a href="{{ route($subitem['route']) }}" wire:navigate aria-current="{{ $isActive($subitem) ? 'page' : 'false' }}">
                                        {{ $subitem['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="mt-5 border-t border-[#e4ddd0] pt-3 px-3 space-y-1.5">
                    @foreach($shortcuts as $shortcut)
                        <a href="{{ route($shortcut['route']) }}" wire:navigate class="block text-xs text-[#8a8578] hover:text-[#8a4b2d]">
                            {{ $shortcut['label'] }} →
                        </a>
                    @endforeach
                </div>
            </nav>

            @auth
                <div class="border-t border-[#e4ddd0] px-4 py-3">
                    <details class="group relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md px-2 py-2 hover:bg-[#f3eee3]">
                            <span class="desk-avatar h-7 w-7">{{ $initials }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-xs font-semibold text-[#26221c]">{{ $user->name }}</span>
                                <span class="block text-[10px] text-[#8a8578]">{{ $user->isAdmin() ? 'Admin' : 'Settings' }}</span>
                            </span>
                            <span class="text-[10px] text-[#8a8578] group-open:rotate-180">⌃</span>
                        </summary>

                        <div class="absolute bottom-full left-0 right-0 z-30 mb-2 max-h-[70vh] overflow-y-auto rounded-lg border border-[#d8d0bf] bg-white p-2 shadow-lg">
                            <a href="{{ route('profile') }}" wire:navigate class="block rounded px-2 py-1.5 text-xs text-[#4a453b] hover:bg-[#f3eee3]">Profile & settings</a>
                            @if(Route::has('accomplishments.index'))
                                <a href="{{ route('accomplishments.index') }}" wire:navigate class="block rounded px-2 py-1.5 text-xs text-[#4a453b] hover:bg-[#f3eee3]">Wins</a>
                            @endif

                            @if(!empty($adminNav))
                                <p class="desk-section-label mt-2 border-t border-[#e4ddd0] px-2 pt-2">Admin</p>
                                @foreach($adminNav as $item)
                                    <a href="{{ route($item['route']) }}" wire:navigate class="block rounded px-2 py-1.5 text-xs text-[#4a453b] hover:bg-[#f3eee3]">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            @endif

                            <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-[#e4ddd0] pt-2">
                                @csrf
                                <button type="submit" class="w-full rounded px-2 py-1.5 text-left text-xs font-medium text-[#b33a2b] hover:bg-[#f8efe6]">Log out</button>
                            </form>
                        </div>
                    </details>
                </div>
            @endauth
        </aside>

        <div class="app-main">
            <header class="app-topbar lg:hidden sticky top-0 z-40">
                <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                    <button type="button" @click="mobileNavOpen = !mobileNavOpen" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-[#d8d0bf] text-[#4a453b]" aria-label="Open navigation">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('dashboard') }}" wire:navigate class="desk-wordmark text-lg">WRKBench</a>
                    <button type="button" @click="mobileUserOpen = !mobileUserOpen" class="desk-avatar h-9 w-9" aria-label="Open account menu">{{ $initials }}</button>
                </div>
            </header>

            <div x-cloak x-show="mobileNavOpen" @click.self="mobileNavOpen = false" class="fixed inset-0 z-30 bg-black/25 lg:hidden">
                <div class="h-full w-72 max-w-[86vw] overflow-y-auto border-r border-[#e4ddd0] bg-[#faf7f1] p-3 shadow-lg">
                    <div class="flex items-center justify-between border-b border-[#e4ddd0] px-2 pb-3">
                        <span class="desk-wordmark">WRKBench</span>
                        <button type="button" @click="mobileNavOpen = false" class="text-xl text-[#5c574d]" aria-label="Close navigation">×</button>
                    </div>
                    <nav class="space-y-1 pt-3">
                        @foreach($dailyNav as $item)
                            @php($active = $isActive($item))
                            <a href="{{ route($item['route']) }}" wire:navigate @click="mobileNavOpen = false" class="app-nav-link {{ $active ? 'app-nav-link-active' : '' }}">
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                    <div class="mt-5 border-t border-[#e4ddd0] px-3 pt-3 space-y-2">
                        @foreach($shortcuts as $shortcut)
                            <a href="{{ route($shortcut['route']) }}" wire:navigate @click="mobileNavOpen = false" class="block text-xs text-[#8a8578]">{{ $shortcut['label'] }} →</a>
                        @endforeach
                    </div>
                </div>
            </div>

            @auth
                <div x-cloak x-show="mobileUserOpen" @click.self="mobileUserOpen = false" class="fixed inset-0 z-40 bg-black/25 lg:hidden">
                    <div class="absolute right-3 top-14 w-72 rounded-lg border border-[#d8d0bf] bg-white p-3 shadow-lg">
                        <p class="text-sm font-semibold text-[#26221c]">{{ $user->name }}</p>
                        <p class="mt-0.5 truncate text-xs text-[#8a8578]">{{ $user->email }}</p>
                        <div class="mt-3 space-y-1 border-t border-[#e4ddd0] pt-2">
                            <a href="{{ route('profile') }}" wire:navigate @click="mobileUserOpen = false" class="block rounded px-2 py-1.5 text-xs text-[#4a453b] hover:bg-[#f3eee3]">Profile & settings</a>
                            @foreach($adminNav as $item)
                                <a href="{{ route($item['route']) }}" wire:navigate @click="mobileUserOpen = false" class="block rounded px-2 py-1.5 text-xs text-[#4a453b] hover:bg-[#f3eee3]">{{ $item['label'] }}</a>
                            @endforeach
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-[#e4ddd0] pt-2">
                                @csrf
                                <button type="submit" class="w-full rounded px-2 py-1.5 text-left text-xs font-medium text-[#b33a2b]">Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth

            <main class="app-page">
                @if (isset($header))
                    <section class="app-surface mb-4 p-4">{{ $header }}</section>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
