{{-- Filters --}}
<div class="flex flex-wrap gap-4 mb-6">
    <select wire:model.live="reportStatusFilter"
        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="in_progress">In Progress</option>
        <option value="submitted">Submitted</option>
    </select>

    <select wire:model.live="reportFunderFilter"
        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        <option value="">All Funders</option>
        @foreach($funders as $funder)
            <option value="{{ $funder->id }}">{{ $funder->name }}</option>
        @endforeach
    </select>
</div>

{{-- Reports by Status --}}
<div class="space-y-6">
    {{-- Overdue --}}
    @if($reportsByStatus['overdue']->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                Overdue ({{ $reportsByStatus['overdue']->count() }})
            </h3>
            <div class="space-y-2">
                @foreach($reportsByStatus['overdue'] as $report)
                    @include('livewire.grants.partials.report-row', ['report' => $report, 'variant' => 'overdue'])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Due Soon --}}
    @if($reportsByStatus['due_soon']->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                Due Soon ({{ $reportsByStatus['due_soon']->count() }})
            </h3>
            <div class="space-y-2">
                @foreach($reportsByStatus['due_soon'] as $report)
                    @include('livewire.grants.partials.report-row', ['report' => $report, 'variant' => 'due_soon'])
                @endforeach
            </div>
        </div>
    @endif

    {{-- In Progress --}}
    @if($reportsByStatus['in_progress']->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                In Progress ({{ $reportsByStatus['in_progress']->count() }})
            </h3>
            <div class="space-y-2">
                @foreach($reportsByStatus['in_progress'] as $report)
                    @include('livewire.grants.partials.report-row', ['report' => $report, 'variant' => 'in_progress'])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Upcoming --}}
    @if($reportsByStatus['upcoming']->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-400 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                Upcoming ({{ $reportsByStatus['upcoming']->count() }})
            </h3>
            <div class="space-y-2">
                @foreach($reportsByStatus['upcoming'] as $report)
                    @include('livewire.grants.partials.report-row', ['report' => $report, 'variant' => 'upcoming'])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Submitted --}}
    @if($reportsByStatus['submitted']->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-green-700 dark:text-green-400 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                Submitted ({{ $reportsByStatus['submitted']->count() }})
            </h3>
            <div class="space-y-2">
                @foreach($reportsByStatus['submitted']->take(5) as $report)
                    @include('livewire.grants.partials.report-row', ['report' => $report, 'variant' => 'submitted'])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Empty State --}}
    @if($reportsByStatus['overdue']->isEmpty() && $reportsByStatus['due_soon']->isEmpty() && $reportsByStatus['in_progress']->isEmpty() && $reportsByStatus['upcoming']->isEmpty() && $reportsByStatus['submitted']->isEmpty())
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-8 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No reports found</p>
        </div>
    @endif
</div>