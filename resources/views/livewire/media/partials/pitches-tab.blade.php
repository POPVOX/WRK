{{-- Pitches Tab --}}
<div>
    {{-- Stats Bar --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $pitchStats['sent'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Pitches (90 days)</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-green-600">{{ $pitchStats['successful'] }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Successful</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="text-2xl font-bold text-indigo-600">{{ $pitchStats['success_rate'] }}%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Success Rate</div>
        </div>
    </div>

    {{-- View Toggle --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <button wire:click="$set('pitchView', 'kanban')" 
                    class="px-3 py-1.5 text-sm rounded-lg transition {{ $pitchView === 'kanban' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                Kanban
            </button>
            <button wire:click="$set('pitchView', 'list')" 
                    class="px-3 py-1.5 text-sm rounded-lg transition {{ $pitchView === 'list' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                List
            </button>
        </div>
        <button wire:click="openPitchModal" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            + New Pitch
        </button>
    </div>

    @if($pitchView === 'kanban')
        {{-- Kanban View --}}
        <div class="overflow-x-auto pb-4">
            <div class="flex gap-4 min-w-max">
                @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'following_up' => 'Following Up', 'accepted' => 'Accepted', 'published' => 'Published', 'closed' => 'Closed'] as $status => $label)
                    <div class="w-72 flex-shrink-0">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ $label }}</h3>
                            <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">
                                {{ $pitchesByStatus[$status]->count() }}
                            </span>
                        </div>
                        <div class="space-y-3 min-h-[200px] bg-gray-50 dark:bg-gray-900/50 rounded-lg p-2">
                            @forelse($pitchesByStatus[$status] as $pitch)
                                <div wire:click="openPitchModal({{ $pitch->id }})" 
                                     class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-pointer hover:border-indigo-300 dark:hover:border-indigo-600 transition">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2 mb-2">
                                        {{ $pitch->subject }}
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        @if($pitch->outlet_display_name !== 'TBD')
                                            <span>{{ $pitch->outlet_display_name }}</span>
                                        @endif
                                        @if($pitch->pitched_at)
                                            <span>{{ $pitch->pitched_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    @if($pitch->days_since_pitched && $pitch->days_since_pitched > 7 && in_array($pitch->status, ['sent', 'following_up']))
                                        <div class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                            ⏰ {{ $pitch->days_since_pitched }}d no response
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-400 dark:text-gray-500 text-sm">
                                    No pitches
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        {{-- List View --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Outlet</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Journalist</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pitched</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($pitches as $pitch)
                        <tr wire:click="openPitchModal({{ $pitch->id }})" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ Str::limit($pitch->subject, 40) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $pitch->outlet_display_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $pitch->journalist_display_name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                    @if($pitch->status === 'draft') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400
                                    @elseif($pitch->status === 'sent') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif($pitch->status === 'following_up') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                                    @elseif($pitch->status === 'accepted') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($pitch->status === 'published') bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 @endif">
                                    {{ $pitch->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $pitch->pitched_at?->format('M j, Y') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No pitches yet. <button wire:click="openPitchModal" class="text-indigo-600 hover:underline">Create your first pitch</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
