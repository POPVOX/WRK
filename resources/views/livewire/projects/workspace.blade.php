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

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Project Header Card --}}
            <div class="rounded-xl shadow-lg p-6 mb-6 text-white" style="background: linear-gradient(to right, #4f46e5, #7c3aed, #db2777);">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🏛️</span>
                            <h1 class="text-2xl lg:text-3xl font-bold">{{ $project->name }}</h1>
                        </div>
                        @if($project->description)
                            <p class="mt-2 text-white/80 max-w-3xl">{{ Str::limit($project->description, 200) }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="bg-white/20 rounded-lg px-4 py-3 backdrop-blur-sm">
                            <div class="text-2xl font-bold">{{ $stats['publications_published'] }}/{{ $stats['publications_total'] }}</div>
                            <div class="text-white/80">Published</div>
                        </div>
                        <div class="bg-white/20 rounded-lg px-4 py-3 backdrop-blur-sm">
                            <div class="text-2xl font-bold">{{ $stats['events_completed'] }}/{{ $stats['events_total'] }}</div>
                            <div class="text-white/80">Events</div>
                        </div>
                        <div class="bg-white/20 rounded-lg px-4 py-3 backdrop-blur-sm">
                            <div class="text-2xl font-bold">{{ $stats['milestones_completed'] }}/{{ $stats['milestones_total'] }}</div>
                            <div class="text-white/80">Milestones</div>
                        </div>
                        @if($stats['milestones_overdue'] > 0)
                            <div class="bg-red-500/40 rounded-lg px-4 py-3 backdrop-blur-sm border border-red-300/50">
                                <div class="text-2xl font-bold">{{ $stats['milestones_overdue'] }}</div>
                                <div class="text-white/80">Overdue</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="flex overflow-x-auto">
                    @foreach([
                        'overview' => ['icon' => '📊', 'label' => 'Overview'],
                        'timeline' => ['icon' => '📅', 'label' => 'Timeline'],
                        'publications' => ['icon' => '📝', 'label' => 'Publications'],
                        'events' => ['icon' => '🎪', 'label' => 'Events'],
                        'documents' => ['icon' => '📁', 'label' => 'Documents'],
                        'collaborator' => ['icon' => '🤖', 'label' => 'AI Collaborator'],
                    ] as $tab => $info)
                        <button wire:click="setTab('{{ $tab }}')"
                            class="flex items-center gap-2 px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                            {{ $activeTab === $tab 
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' 
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <span>{{ $info['icon'] }}</span>
                            <span>{{ $info['label'] }}</span>
                            @if($tab === 'publications')
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    {{ $stats['publications_total'] }}
                                </span>
                            @elseif($tab === 'events')
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    {{ $stats['events_total'] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
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
