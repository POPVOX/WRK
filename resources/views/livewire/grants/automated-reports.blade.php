<div class="space-y-6">
    {{-- Error State - if database tables don't exist --}}
    @if($hasError)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-center">
            <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200 mb-2">Automated Reports Unavailable</h3>
            <p class="text-amber-600 dark:text-amber-400 max-w-md mx-auto">
                {{ $errorMessage ?: 'This feature is currently unavailable. Please contact support if this persists.' }}
            </p>
        </div>
    @else
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Automated Reports</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if($this->schemaStatus === 'active')
                    AI-powered metrics tracking and report generation
                @elseif($this->schemaStatus === 'draft')
                    Schema setup in progress
                @else
                    Set up automated reporting for this grant
                @endif
            </p>
        </div>

        @if($this->hasSchema && $this->schemaStatus === 'active')
            <div class="flex items-center gap-3">
                {{-- Period Selector --}}
                <select wire:model.live="periodLabel" wire:change="setPeriod($event.target.value)"
                    class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="this_quarter">This Quarter</option>
                    <option value="last_quarter">Last Quarter</option>
                    <option value="this_year">This Year</option>
                </select>

                <button wire:click="recalculateMetrics"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        @endif
    </div>

    {{-- No Schema State (only show if not in chatbot mode) --}}
    @if(!$this->hasSchema && !$showChatbot)
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800 p-8 text-center">
            <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Set Up Automated Reporting</h3>
            <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-6">
                I'll analyze your grant documents and help you create a custom reporting schema. 
                Track metrics automatically and generate reports with ease.
            </p>
            <button wire:click="startSetup" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-wait"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                <svg wire:loading.remove wire:target="startSetup" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <svg wire:loading wire:target="startSetup" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span wire:loading.remove wire:target="startSetup">Start AI Setup</span>
                <span wire:loading wire:target="startSetup">Starting...</span>
            </button>
        </div>
    @endif

    {{-- Schema Draft State / Chatbot --}}
    @if($showChatbot || $this->schemaStatus === 'draft')
        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Chat Panel --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col h-[600px]">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Schema Builder
                    </h3>
                    @if($showChatbot)
                        <button wire:click="closeChatbot" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Chat Messages --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                    @foreach($chatHistory as $message)
                        <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] {{ $message['role'] === 'user' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' }} rounded-lg px-4 py-2">
                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($message['content']) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($isChatProcessing)
                        <div class="flex justify-start">
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg px-4 py-2">
                                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Thinking...
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Chat Input --}}
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <form wire:submit="sendChatMessage" class="flex gap-2">
                        <input type="text" wire:model="chatMessage"
                            placeholder="Type your message..."
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            @disabled($isChatProcessing)>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition"
                            @disabled($isChatProcessing || empty($chatMessage))>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Schema Preview Panel --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col h-[600px]">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-medium text-gray-900 dark:text-white">Schema Preview</h3>
                    @if($this->schemaStatus === 'draft' && !empty($pathways))
                        <button wire:click="activateSchema"
                            class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Activate Schema
                        </button>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto p-4">
                    @if(empty($pathways))
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <p>Schema will appear here as you configure it</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($pathways as $pathway)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $pathway['name'] ?? 'Pathway' }}</h4>
                                    </div>
                                    <div class="p-3 space-y-2">
                                        @foreach($pathway['outcomes'] ?? [] as $outcome)
                                            <div class="pl-3 border-l-2 border-indigo-200 dark:border-indigo-700">
                                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $outcome['name'] ?? 'Outcome' }}</p>
                                                <ul class="mt-1 space-y-1">
                                                    @foreach($outcome['metrics'] ?? [] as $metric)
                                                        <li class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                            @if(($metric['calculation'] ?? 'manual') === 'auto')
                                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                                            @else
                                                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                                            @endif
                                                            {{ $metric['name'] ?? 'Metric' }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($tagsConfig))
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Required Tags</h4>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($availableTags as $tag)
                                        <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Active Schema: Sub-tabs --}}
    @if($this->hasSchema && $this->schemaStatus === 'active' && !$showChatbot)
        {{-- Sub-tab Navigation --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-6">
                <button wire:click="setSubTab('dashboard')"
                    class="pb-3 text-sm font-medium border-b-2 transition {{ $activeSubTab === 'dashboard' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    📊 Metrics Dashboard
                </button>
                <button wire:click="setSubTab('tag')"
                    class="pb-3 text-sm font-medium border-b-2 transition {{ $activeSubTab === 'tag' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    🏷️ Tag Items
                    @if($untaggedCount > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full">{{ $untaggedCount }}</span>
                    @endif
                </button>
                <button wire:click="setSubTab('generate')"
                    class="pb-3 text-sm font-medium border-b-2 transition {{ $activeSubTab === 'generate' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    📄 Generate Report
                </button>
                <button wire:click="openRefineChat"
                    class="pb-3 text-sm font-medium border-b-2 transition {{ $activeSubTab === 'refine' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    💬 Refine Schema
                </button>
            </nav>
        </div>

        {{-- Metrics Dashboard --}}
        @if($activeSubTab === 'dashboard')
            <div class="space-y-6">
                {{-- Period Info --}}
                <div class="flex items-center justify-between bg-indigo-50 dark:bg-indigo-900/20 rounded-lg px-4 py-3">
                    <div>
                        <span class="text-sm text-indigo-700 dark:text-indigo-300">Reporting Period:</span>
                        <span class="font-medium text-indigo-900 dark:text-indigo-100 ml-2">{{ $periodLabel }}</span>
                        <span class="text-sm text-indigo-600 dark:text-indigo-400 ml-2">({{ $periodStart }} to {{ $periodEnd }})</span>
                    </div>
                    @if($isCalculating)
                        <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Calculating...
                        </div>
                    @endif
                </div>

                {{-- Pathways Grid --}}
                @foreach($pathways as $pathway)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $pathway['name'] ?? 'Pathway' }}</h3>
                        </div>
                        <div class="p-5 space-y-6">
                            @foreach($pathway['outcomes'] ?? [] as $outcome)
                                <div>
                                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                                        <span class="text-xs px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 rounded">
                                            {{ $outcome['timeframe'] ?? 'MT' }}
                                        </span>
                                        {{ $outcome['name'] ?? 'Outcome' }}
                                    </h4>
                                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($outcome['metrics'] ?? [] as $metric)
                                            @php
                                                $metricId = $metric['id'] ?? '';
                                                $metricData = $this->getMetricValue($metricId);
                                                $isAuto = ($metric['calculation'] ?? 'manual') === 'auto';
                                            @endphp
                                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {{ $metric['name'] ?? 'Metric' }}
                                                    </h5>
                                                    @if($isAuto)
                                                        <span class="text-xs px-1.5 py-0.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded">
                                                            Auto
                                                        </span>
                                                    @else
                                                        <span class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded">
                                                            Manual
                                                        </span>
                                                    @endif
                                                </div>

                                                @if($isAuto && $metricData)
                                                    <div class="space-y-2">
                                                        <div class="flex items-end gap-2">
                                                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                                                {{ $metricData['value'] ?? 0 }}
                                                            </span>
                                                            @if(isset($metricData['target']))
                                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                                    / {{ $metricData['target'] }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        @if(isset($metricData['target']))
                                                            @php
                                                                $percentage = $metricData['target'] > 0 ? min(100, ($metricData['value'] / $metricData['target']) * 100) : 0;
                                                                $statusColor = match($metricData['status'] ?? '') {
                                                                    'above_target', 'on_track' => 'bg-green-500',
                                                                    'below_target' => 'bg-amber-500',
                                                                    default => 'bg-gray-400'
                                                                };
                                                            @endphp
                                                            <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                                <div class="h-full {{ $statusColor }} rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                                                            </div>
                                                            <div class="flex items-center gap-1 text-xs {{ str_contains($statusColor, 'green') ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                                @if($metricData['status'] === 'above_target' || $metricData['status'] === 'on_track')
                                                                    ✓ On track
                                                                @else
                                                                    ⚠ Below target
                                                                @endif
                                                            </div>
                                                        @endif

                                                        @if(!empty($metricData['items']))
                                                            <button class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                                                View {{ count($metricData['items']) }} items
                                                            </button>
                                                        @endif
                                                    </div>
                                                @elseif(!$isAuto)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                                                        {{ $metric['prompt'] ?? 'Manual entry required' }}
                                                    </p>
                                                @else
                                                    <p class="text-sm text-gray-400 dark:text-gray-500">No data yet</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if($untaggedCount > 0)
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🏷️</span>
                            <div>
                                <p class="font-medium text-amber-800 dark:text-amber-200">{{ $untaggedCount }} items need tagging</p>
                                <p class="text-sm text-amber-600 dark:text-amber-400">Tag items to track them in your metrics</p>
                            </div>
                        </div>
                        <button wire:click="setSubTab('tag')"
                            class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-medium">
                            Review Items
                        </button>
                    </div>
                @endif
            </div>
        @endif

        {{-- Tagging Interface --}}
        @if($activeSubTab === 'tag')
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Review & Tag Items</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Associate items with this grant and add metric tags</p>
                </div>

                @if(empty($untaggedItems))
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">All caught up!</h4>
                        <p class="text-gray-500 dark:text-gray-400">No items need tagging right now.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($untaggedItems as $item)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0">
                                        @if($item['type'] === 'meeting')
                                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ $item['title'] }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ ucfirst($item['type']) }} • {{ $item['date'] }}
                                        </p>

                                        {{-- Tag Suggestions --}}
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($availableTags as $tag)
                                                <label class="inline-flex items-center gap-1.5 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                                    <input type="checkbox" class="rounded text-indigo-600 border-gray-300">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">{{ $tag }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-2">
                                        <button wire:click="tagItem('{{ $item['type'] }}', {{ $item['id'] }}, [])"
                                            class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                            ✓ Add to Grant
                                        </button>
                                        <button wire:click="skipItem('{{ $item['type'] }}', {{ $item['id'] }})"
                                            class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition">
                                            Skip
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Report Generation --}}
        @if($activeSubTab === 'generate')
            <div class="space-y-6">
                {{-- Generation Panel --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Generate Progress Report</h3>

                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reporting Period</label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $periodLabel }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ $periodStart }} to {{ $periodEnd }})</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Metrics Status</label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-green-600 dark:text-green-400">✓ {{ count($metricsData) }} auto metrics calculated</span>
                            </div>
                        </div>
                    </div>

                    @php
                        $manualMetrics = $schema?->getManualMetrics() ?? [];
                    @endphp

                    @if(count($manualMetrics) > 0)
                        <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-amber-800 dark:text-amber-200">{{ count($manualMetrics) }} manual entries needed</p>
                                    <p class="text-sm text-amber-600 dark:text-amber-400">Add qualitative assessments before generating the report</p>
                                </div>
                                <button wire:click="openManualEntryModal"
                                    class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-medium">
                                    Enter Manual Data
                                </button>
                            </div>
                        </div>
                    @endif

                    <button wire:click="generateReport"
                        class="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium flex items-center justify-center gap-2"
                        @disabled($isGeneratingReport)>
                        @if($isGeneratingReport)
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Generating Report...
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Generate Draft Report
                        @endif
                    </button>
                </div>

                {{-- Generated Report --}}
                @if($generatedReport)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Generated Report Draft</h3>
                            <div class="flex items-center gap-2">
                                <button wire:click="clearReport"
                                    class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">
                                    Clear
                                </button>
                                <button class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                    Copy to Clipboard
                                </button>
                            </div>
                        </div>
                        <div class="p-6 prose prose-sm dark:prose-invert max-w-none">
                            {!! \Illuminate\Support\Str::markdown($generatedReport) !!}
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Manual Entry Modal --}}
    @if($showManualEntryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeManualEntryModal"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Manual Metric Entries</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Enter qualitative assessments for metrics that can't be auto-calculated</p>
                    </div>
                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto space-y-4">
                        @foreach($manualEntries as $metricId => $entry)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $entry['name'] }}
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $entry['prompt'] }}</p>
                                <textarea wire:model="manualEntries.{{ $metricId }}.value" rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Enter your response..."></textarea>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeManualEntryModal"
                            class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                            Cancel
                        </button>
                        <button wire:click="saveManualEntries"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            Save Entries
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @endif {{-- End hasError else block --}}
</div>

