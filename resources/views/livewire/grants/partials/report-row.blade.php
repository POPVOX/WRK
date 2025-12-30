@php
    $variantStyles = [
        'overdue' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'due_soon' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
        'in_progress' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'upcoming' => 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
        'submitted' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
    ];
    $style = $variantStyles[$variant] ?? $variantStyles['upcoming'];
@endphp

<div class="flex items-center gap-4 p-4 rounded-lg border {{ $style }}">
    {{-- Date --}}
    <div class="flex-shrink-0 w-16 text-center">
        <div
            class="text-xs font-medium {{ $variant === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} uppercase">
            {{ $report->due_date->format('M') }}
        </div>
        <div
            class="text-xl font-bold {{ $variant === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
            {{ $report->due_date->format('j') }}
        </div>
    </div>

    {{-- Details --}}
    <div class="flex-1 min-w-0">
        <a href="{{ route('grants.show', $report->grant) }}" wire:navigate
            class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
            {{ $report->name }}
        </a>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $report->grant->funder->name ?? 'Unknown Funder' }} • {{ $report->grant->name }}
        </p>
    </div>

    {{-- Status Badge --}}
    <div class="flex-shrink-0">
        @if($variant === 'overdue')
            <span
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                Overdue
            </span>
        @elseif($variant === 'due_soon')
            <span
                class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                Due {{ $report->due_date->diffForHumans() }}
            </span>
        @elseif($variant === 'in_progress')
            <span
                class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                In Progress
            </span>
        @elseif($variant === 'submitted')
            <span
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
                Submitted
            </span>
        @else
            <span
                class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                {{ $report->due_date->format('M j') }}
            </span>
        @endif
    </div>

    {{-- Action --}}
    <div class="flex-shrink-0">
        <a href="{{ route('grants.show', $report->grant) }}" wire:navigate
            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-sm">
            {{ $variant === 'submitted' ? 'View' : 'Work on it' }} →
        </a>
    </div>
</div>