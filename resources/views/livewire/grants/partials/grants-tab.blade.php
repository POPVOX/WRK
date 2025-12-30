{{-- Filters --}}
<div class="flex flex-wrap gap-4 mb-6">
    <input type="text"
           wire:model.live.debounce.300ms="grantSearch"
           placeholder="Search grants..."
           class="flex-1 min-w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
    
    <select wire:model.live="grantStatusFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        <option value="">All Statuses</option>
        @foreach($statuses as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    <select wire:model.live="grantFunderFilter" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        <option value="">All Funders</option>
        @foreach($funders as $funder)
            <option value="{{ $funder->id }}">{{ $funder->name }}</option>
        @endforeach
    </select>
</div>

{{-- Grants List --}}
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Grant</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Funder</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Amount</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Period</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Reports</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($grants as $grant)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-4">
                        <a href="{{ route('grants.show', $grant) }}" wire:navigate
                           class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                            {{ $grant->name }}
                        </a>
                        @if($grant->projects->isNotEmpty())
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $grant->projects->pluck('name')->join(', ') }}
                            </p>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            @if($grant->funder->logo_url)
                                <img src="{{ $grant->funder->logo_url }}" class="w-6 h-6 rounded" alt="">
                            @else
                                <div class="w-6 h-6 rounded bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr($grant->funder->name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $grant->funder->name ?? 'Unknown' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900 dark:text-white font-medium">
                        @if($grant->amount)
                            ${{ number_format($grant->amount) }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                        @if($grant->start_date && $grant->end_date)
                            {{ $grant->start_date->format('M Y') }} – {{ $grant->end_date->format('M Y') }}
                        @elseif($grant->start_date)
                            {{ $grant->start_date->format('M Y') }} –
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
                                'pending' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
                                'prospective' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
                                'completed' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
                                'declined' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
                            ];
                        @endphp
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$grant->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($grant->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-sm">
                        @if($grant->reports_due_count > 0)
                            <span class="text-amber-600 dark:text-amber-400">{{ $grant->reports_due_count }} due</span>
                        @else
                            <span class="text-gray-400">{{ $grant->reporting_requirements_count }} total</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-right">
                        <a href="{{ route('grants.show', $grant) }}" wire:navigate
                           class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-sm">
                            View →
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        No grants found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($grants->hasPages())
    <div class="mt-4">
        {{ $grants->links() }}
    </div>
@endif
