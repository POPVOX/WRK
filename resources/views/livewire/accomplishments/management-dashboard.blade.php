<div class="min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Migration Error Alert --}}
        @if($hasMigrationError)
            <div class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-amber-800 dark:text-amber-200">Database Setup Required</h3>
                        <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">{{ $migrationErrorMessage }}</p>
                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            Run <code class="bg-amber-100 dark:bg-amber-900/40 px-1 py-0.5 rounded">php artisan migrate</code> on the server to fix this issue.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    👥 Team Overview
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Monitor team activities and celebrate wins
                </p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Period Filter --}}
                <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                    @foreach(['week' => 'Week', 'month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Year'] as $key => $label)
                        <button wire:click="setPeriod('{{ $key }}')"
                            class="px-3 py-1.5 text-sm font-medium rounded-md transition {{ $period === $key ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            {{-- Left Column: Team Members --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Team Members</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Click to view detailed activity</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($teamStats['member_stats'] ?? [] as $member)
                            <a href="{{ route('accomplishments.user', ['userId' => $member['user']->id]) }}" wire:navigate
                                class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition group">
                                {{-- Avatar --}}
                                <div class="flex-shrink-0">
                                    @if($member['user']->photo_url)
                                        <img src="{{ $member['user']->photo_url }}" alt="{{ $member['user']->name }}"
                                            class="w-12 h-12 rounded-full object-cover">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-semibold text-lg">
                                            {{ substr($member['user']->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                            {{ $member['user']->name }}
                                        </p>
                                        @if($member['user']->title)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $member['user']->title }}</span>
                                        @endif
                                    </div>
                                    {{-- Recent wins preview --}}
                                    @if($member['recent_wins']->count() > 0)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">
                                            <span class="text-green-600 dark:text-green-400">✓</span>
                                            {{ $member['recent_wins']->first()->title }}
                                            @if($member['recent_wins']->count() > 1)
                                                <span class="text-gray-400">+{{ $member['recent_wins']->count() - 1 }} more</span>
                                            @endif
                                        </p>
                                    @else
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No recent wins recorded</p>
                                    @endif
                                </div>

                                {{-- Quick Stats --}}
                                <div class="flex items-center gap-4 text-center">
                                    <div>
                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $member['stats']->meetings_attended ?? 0 }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Meetings</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $member['stats']->documents_authored ?? 0 }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Docs</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">{{ $member['stats']->recognition_received ?? 0 }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Recognition</div>
                                    </div>
                                </div>

                                {{-- Arrow --}}
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @empty
                            <div class="p-8 text-center">
                                <div class="text-4xl mb-2">👥</div>
                                <p class="text-gray-500 dark:text-gray-400">No team member data available</p>
                                @if($hasMigrationError)
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Run database migrations to enable this feature</p>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right Column: Recent Wins Feed --}}
            <div class="space-y-6">
                {{-- Recent Team Wins --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            ✨ Recent Team Wins
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
                        @forelse($recentAccomplishments as $accomplishment)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <div class="flex items-start gap-3">
                                    <div class="text-xl flex-shrink-0">{{ $accomplishment->type_emoji }}</div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $accomplishment->title }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $accomplishment->user->name }}</span>
                                            @if($accomplishment->is_recognition && $accomplishment->addedBy)
                                                <span class="text-xs px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 rounded-full">
                                                    🌟 by {{ $accomplishment->addedBy->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $accomplishment->date->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="text-4xl mb-2">🎯</div>
                                <p class="text-gray-500 dark:text-gray-400">No recent wins yet</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Team members can add wins from their profile</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800 p-4">
                    <h3 class="font-medium text-gray-900 dark:text-white mb-3">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('admin.staff') }}" wire:navigate
                            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            Manage Staff
                        </a>
                        <a href="{{ route('team.hub') }}" wire:navigate
                            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                            Team Hub & Messages
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
