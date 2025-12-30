{{-- Publications Tab --}}
<div class="space-y-6">
    {{-- Status Pipeline --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        @foreach(\App\Models\ProjectPublication::STATUSES as $status => $label)
            @php
                $count = $project->publications->where('status', $status)->count();
                $colors = \App\Models\ProjectPublication::STATUS_COLORS[$status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $count }}</div>
                <div class="text-xs mt-1 {{ $colors }} px-2 py-0.5 rounded-full inline-block">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    {{-- Publications List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-5 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">All Publications</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $project->publications->count() }}
                    total</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            #</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Title</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Type</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Target Date</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($project->publications->sortBy('sort_order') as $pub)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $pub->sort_order }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $pub->title }}</div>
                                @if($pub->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ Str::limit($pub->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ \App\Models\ProjectPublication::TYPES[$pub->type] ?? $pub->type }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($pub->target_date)
                                    <span
                                        class="{{ $pub->isOverdue() ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ $pub->target_date->format('M j, Y') }}
                                        @if($pub->isOverdue())
                                            <span class="text-xs">(overdue)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select wire:change="updatePublicationStatus({{ $pub->id }}, $event.target.value)"
                                    class="text-xs rounded-full px-3 py-1 border-0 {{ \App\Models\ProjectPublication::STATUS_COLORS[$pub->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    @foreach(\App\Models\ProjectPublication::STATUSES as $status => $label)
                                        <option value="{{ $status }}" {{ $pub->status === $status ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No publications yet. Add publications to track your content pipeline.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>