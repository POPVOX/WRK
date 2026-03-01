<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $project->name }}
            </h2>
            @if($project->is_initiative)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                    Initiative
                </span>
            @endif
        </div>
    </x-slot>

    <div class="app-page-frame">

            {{-- Project Header Card --}}
            <div class="app-card p-6">
                <div class="app-page-head">
                    <div>
                        <h1 class="app-page-title">{{ $project->name }}</h1>
                        @if($project->description)
                            <p class="app-page-lead">{{ Str::limit($project->description, 220) }}</p>
                        @endif
                    </div>
                    <div class="app-toolbar text-sm">
                        <span class="inline-flex items-center rounded-full border border-gray-300 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            {{ $stats['publications_published'] }}/{{ $stats['publications_total'] }} published
                        </span>
                        <span class="inline-flex items-center rounded-full border border-gray-300 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            {{ $stats['events_completed'] }}/{{ $stats['events_total'] }} events
                        </span>
                        <span class="inline-flex items-center rounded-full border border-gray-300 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            {{ $stats['milestones_completed'] }}/{{ $stats['milestones_total'] }} milestones
                        </span>
                        @if($stats['milestones_overdue'] > 0)
                            <span class="inline-flex items-center rounded-full border border-red-300 bg-red-50 px-3 py-1 text-xs font-medium text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">
                                {{ $stats['milestones_overdue'] }} overdue
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="app-card p-3 mb-6 space-y-3">
                <div class="app-tabset">
                    @foreach([
                        'overview' => 'Overview',
                        'timeline' => 'Timeline',
                        'collaborator' => 'Agent',
                    ] as $tab => $label)
                        <button wire:click="setTab('{{ $tab }}')"
                            class="app-tab {{ $activeTab === $tab ? 'app-tab-active' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <details class="group">
                    <summary class="list-none cursor-pointer rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        More Sections
                    </summary>
                    <div class="mt-2 app-tabset">
                        @foreach([
                            'publications' => ['label' => 'Publications', 'count' => $stats['publications_total']],
                            'events' => ['label' => 'Events', 'count' => $stats['events_total']],
                            'documents' => ['label' => 'Documents', 'count' => null],
                        ] as $tab => $info)
                            <button wire:click="setTab('{{ $tab }}')"
                                class="app-tab {{ $activeTab === $tab ? 'app-tab-active' : '' }}">
                                {{ $info['label'] }}
                                @if(!is_null($info['count']))
                                    <span class="ml-1 text-[11px] text-gray-500 dark:text-gray-300">{{ $info['count'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </details>
            </div>

            {{-- Tab Content --}}
            <div class="space-y-6">
                {{-- Overview Tab --}}
                @if($activeTab === 'overview')
                    @include('livewire.projects.workspace.overview')
                @endif

                {{-- Timeline Tab --}}
                @if($activeTab === 'timeline')
                    @include('livewire.projects.workspace.timeline')
                @endif

                {{-- Publications Tab --}}
                @if($activeTab === 'publications')
                    @include('livewire.projects.workspace.publications')
                @endif

                {{-- Events Tab --}}
                @if($activeTab === 'events')
                    @include('livewire.projects.workspace.events')
                @endif

                {{-- Documents Tab --}}
                @if($activeTab === 'documents')
                    @include('livewire.projects.workspace.documents')
                @endif

                {{-- AI Collaborator Tab --}}
                @if($activeTab === 'collaborator')
                    @include('livewire.projects.workspace.collaborator')
                @endif
            </div>
    </div>

    {{-- Modals --}}
    @include('livewire.projects.workspace.document-viewer')
</div>

@script
<script>
    $wire.on('chatUpdated', () => {
        const container = document.getElementById('chat-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });

    // When a chat message is queued, poll for the assistant reply for ~30s
    $wire.on('chatStarted', () => {
        let tries = 0;
        const maxTries = 15; // 15 * 2s = 30 seconds
        const timer = setInterval(async () => {
            tries++;
            try {
                await $wire.refreshChatHistory();
            } catch (e) {
                // ignore
            }
            if (tries >= maxTries) {
                clearInterval(timer);
            }
        }, 2000);
    });
</script>
@endscript
