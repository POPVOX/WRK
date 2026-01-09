<div class="min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    📊 Team Dashboard
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Overview of team activities and accomplishments
                </p>
            </div>
            <div>
                <a href="{{ route('accomplishments.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    ← My Accomplishments
                </a>
            </div>
        </div>

        {{-- Period Filter --}}
        <div class="flex items-center gap-2 mb-6">
            <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                @foreach(['week' => 'This Week', 'month' => 'This Month', 'quarter' => 'This Quarter', 'year' => 'This Year'] as $key => $label)
                    <button wire:click="setPeriod('{{ $key }}')"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition {{ $period === $key ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Team Overview Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $teamStats['total_team_members'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Team Members</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $teamStats['total_meetings'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Meetings</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $teamStats['total_documents'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Documents</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $teamStats['total_projects'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Projects</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $teamStats['total_recognition'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recognition</div>
            </div>
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-4 text-center text-white">
                <div class="text-3xl font-bold">{{ $teamStats['average_impact_score'] }}</div>
                <div class="text-xs opacity-90 mt-1">Avg Impact</div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Left Column: Top Performers + Team Members --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Top Performers --}}
                @if(count($topPerformers) > 0)
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            🏆 Top Contributors
                        </h2>
                        <div class="grid sm:grid-cols-3 gap-4">
                            @foreach($topPerformers as $index => $performer)
                                <div class="bg-gradient-to-br {{ $index === 0 ? 'from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-yellow-200 dark:border-yellow-700' : ($index === 1 ? 'from-gray-50 to-slate-50 dark:from-gray-800 dark:to-slate-800 border-gray-200 dark:border-gray-600' : 'from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border-orange-200 dark:border-orange-700') }} rounded-xl border p-4">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="text-2xl">
                                            @if($index === 0) 🥇 @elseif($index === 1) 🥈 @else 🥉 @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $performer['user']->name }}</div>
                                            <div class="text-sm text-indigo-600 dark:text-indigo-400 font-bold">
                                                {{ number_format($performer['stats']->total_impact_score, 1) }} pts
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                        <div class="bg-white/50 dark:bg-gray-900/30 rounded p-1.5">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $performer['stats']->meetings_attended }}</div>
                                            <div class="text-gray-500">Meetings</div>
                                        </div>
                                        <div class="bg-white/50 dark:bg-gray-900/30 rounded p-1.5">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $performer['stats']->documents_authored }}</div>
                                            <div class="text-gray-500">Docs</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- All Team Members --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        👥 Team Members
                    </h2>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Team Member</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Meetings</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Docs</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Projects</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Recognition</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Impact</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($teamStats['member_stats'] as $member)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-sm font-medium text-indigo-700 dark:text-indigo-300">
                                                    {{ substr($member['user']->name, 0, 1) }}
                                                </div>
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $member['user']->name }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{ $member['stats']->meetings_attended }}
                                            @if($member['stats']->meetings_organized > 0)
                                                <span class="text-xs text-gray-400">({{ $member['stats']->meetings_organized }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ $member['stats']->documents_authored }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{ $member['stats']->projects_owned + $member['stats']->projects_contributed }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                                            {{ $member['stats']->recognition_received }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-full">
                                                {{ number_format($member['stats']->total_impact_score, 1) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('accomplishments.user', ['userId' => $member['user']->id]) }}" wire:navigate
                                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                                                View →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right Column: Recent Accomplishments Feed --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    ✨ Recent Team Wins
                </h2>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($recentAccomplishments as $accomplishment)
                        <div class="p-4">
                            <div class="flex items-start gap-3">
                                <div class="text-xl">{{ $accomplishment->type_emoji }}</div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $accomplishment->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $accomplishment->user->name }}
                                        @if($accomplishment->is_recognition && $accomplishment->addedBy)
                                            <span class="text-yellow-600 dark:text-yellow-400">
                                                • Recognized by {{ $accomplishment->addedBy->name }}
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $accomplishment->date->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            <p>No recent accomplishments</p>
                        </div>
                    @endforelse
                </div>

                {{-- Quick Recognition --}}
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Quick Recognize</h3>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Select a team member to recognize:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(collect($teamStats['member_stats'])->take(6) as $member)
                                <a href="{{ route('accomplishments.user', ['userId' => $member['user']->id]) }}?recognize=true" wire:navigate
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 rounded-full hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition">
                                    🌟 {{ $member['user']->first_name ?? explode(' ', $member['user']->name)[0] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

