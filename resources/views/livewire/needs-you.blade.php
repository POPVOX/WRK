<div class="space-y-6">
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Needs You</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The decisions, follow-up, and preparation where your attention matters most.</p>
            </div>
            <p class="text-xs font-medium uppercase tracking-[0.14em] text-gray-400">Read-only pilot</p>
        </div>
    </x-slot>

    <div class="app-page-frame space-y-6">
        <section class="grid gap-3 sm:grid-cols-3">
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-red-600">Now</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $urgentCount }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Time-sensitive items</p>
            </div>
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-violet-600">Review</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $reviewCount }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Agent decisions waiting</p>
            </div>
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-blue-600">Coming up</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $comingUpCount }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Work to prepare early</p>
            </div>
        </section>

        @php
            $filterLabels = [
                'all' => 'All',
                'tasks' => 'Tasks',
                'meetings' => 'Meetings',
                'funding' => 'Funding',
                'approvals' => 'Approvals',
            ];
        @endphp

        <nav class="flex flex-wrap gap-2" aria-label="Attention filters">
            @foreach($filterLabels as $value => $label)
                <button type="button" wire:click="setFilter('{{ $value }}')"
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition
                        {{ $filter === $value
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-300 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                    <span>{{ $label }}</span>
                    <span class="rounded-full px-1.5 py-0.5 text-xs {{ $filter === $value ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $counts[$value] }}
                    </span>
                </button>
            @endforeach
        </nav>

        @forelse($sections as $section)
            <section class="space-y-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $section['label'] }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $section['description'] }}</p>
                </div>

                <div class="space-y-2">
                    @foreach($section['items'] as $item)
                        @php
                            $priorityClasses = match($item['priority']) {
                                'critical' => 'border-l-red-600',
                                'high' => 'border-l-amber-500',
                                'medium' => 'border-l-blue-500',
                                default => 'border-l-gray-300 dark:border-l-gray-600',
                            };
                            $categoryLabels = [
                                'tasks' => 'Task',
                                'meetings' => 'Meeting',
                                'funding' => 'Funding',
                                'approvals' => 'Agent review',
                            ];
                        @endphp

                        <article wire:key="attention-{{ $item['id'] }}"
                            class="app-surface border-l-4 {{ $priorityClasses }} p-4 sm:p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $categoryLabels[$item['category']] ?? ucfirst($item['category']) }}
                                        </span>
                                        @if($item['due_label'])
                                            <span class="text-xs font-medium {{ $item['priority'] === 'critical' ? 'text-red-600' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $item['due_label'] }}
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $item['title'] }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $item['summary'] }}</p>

                                    @if($item['context'])
                                        <p class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $item['context'] }}</p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="text-xs text-gray-400">Does this belong here?</span>
                                        <button type="button"
                                            wire:click="recordFeedback('{{ $item['id'] }}', 'useful')"
                                            class="rounded-md px-2 py-1 text-xs font-medium transition {{ $feedbackByItem->get($item['id']) === 'useful' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'text-gray-500 hover:bg-gray-100 hover:text-emerald-700 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                            Useful
                                        </button>
                                        <button type="button"
                                            wire:click="recordFeedback('{{ $item['id'] }}', 'not_relevant')"
                                            class="rounded-md px-2 py-1 text-xs font-medium transition {{ $feedbackByItem->get($item['id']) === 'not_relevant' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'text-gray-500 hover:bg-gray-100 hover:text-amber-700 dark:text-gray-400 dark:hover:bg-gray-700' }}">
                                            Not relevant
                                        </button>
                                    </div>
                                </div>

                                <a href="{{ $item['url'] }}" wire:navigate
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                    {{ $item['action_label'] }}
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="app-surface px-6 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-xl text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">✓</div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Nothing needs your attention here</h3>
                <p class="mx-auto mt-2 max-w-lg text-sm text-gray-500 dark:text-gray-400">
                    WRK will surface assigned work, meeting preparation, reporting deadlines, and agent reviews as they become relevant.
                </p>
            </section>
        @endforelse

        <section class="app-surface p-5 sm:p-6">
            <div class="max-w-2xl">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Something missing?</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tell us what you expected WRK to surface. This only improves the pilot; it will not create or change a task.</p>

                <form wire:submit="submitMissingSignal" class="mt-4 space-y-3">
                    <textarea wire:model="missingSignal" rows="3"
                        placeholder="For example: remind me when a meeting has decisions but no follow-up owner…"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                    @error('missingSignal')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                        Record missing item
                    </button>
                </form>
            </div>
        </section>
    </div>
</div>
