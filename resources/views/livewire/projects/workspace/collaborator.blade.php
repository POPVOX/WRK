{{-- AI Collaborator Tab --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Chat Interface --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-[600px]">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-white flex items-center gap-2">
                        <span class="text-xl">🤖</span>
                        Project Collaborator
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-white/20 text-white">
                            AI
                        </span>
                    </h3>
                    @if(count($chatHistory) > 0)
                        <button wire:click="clearChat"
                            class="text-sm text-white/70 hover:text-white transition-colors">
                            Clear
                        </button>
                    @endif
                </div>
                <p class="text-xs text-white/70 mt-1">
                    I have access to all project documents. Ask me about planning, content, or next steps.
                </p>
                @if(!$aiEnabled)
                    <div class="mt-3 text-xs text-white bg-red-500/20 border border-red-400/40 rounded px-3 py-2">
                        AI features are disabled by the administrator.
                    </div>
                @elseif($aiNotice)
                    <div class="mt-3 text-xs text-white bg-amber-500/20 border border-amber-400/40 rounded px-3 py-2">
                        {{ $aiNotice }}
                    </div>
                @endif
            </div>

            {{-- Chat Messages --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-container">
                @forelse($chatHistory as $message)
                    <div class="{{ $message['role'] === 'user' ? 'text-right' : 'text-left' }}">
                        <div class="{{ $message['role'] === 'user'
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }} 
                            rounded-lg px-4 py-3 inline-block max-w-[85%] text-sm">
                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                {!! nl2br(e($message['content'])) !!}
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ $message['timestamp'] }}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="text-5xl mb-4">🤖</div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Hi! I'm your project collaborator.</h3>
                        <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">
                            I've loaded the project context including README, TIMELINE, and CHAPTERS. How can I help you today?
                        </p>
                    </div>
                @endforelse

                @if($isProcessing)
                    <div class="text-left">
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg px-4 py-3 inline-block">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-1">
                                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Thinking...</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Chat Input --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <form wire:submit="sendChat" class="flex gap-2">
                    <input type="text" wire:model="chatQuery" 
                        placeholder="Ask about the project..."
                        class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                        @disabled($isProcessing || !$aiEnabled)>
                    <button type="submit"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        @disabled($isProcessing || empty($chatQuery) || !$aiEnabled)>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Suggested Questions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">💡 Suggested Questions</h4>
            <div class="space-y-2">
                @foreach([
                    "What are the key deliverables for Q1?",
                    "Summarize Chapter 1's learning objectives",
                    "What should I focus on this week?",
                    "Draft an executive summary for the project",
                    "Who should I engage for the Q2 event?",
                    "What are the main success metrics?",
                ] as $question)
                    <button wire:click="$set('chatQuery', '{{ $question }}')"
                        class="w-full text-left px-3 py-2 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-sm">
                        "{{ $question }}"
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Context Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">📚 Loaded Context</h4>
            <ul class="space-y-2 text-sm">
                @if($project->project_path)
                    @php
                        $projectDir = base_path($project->project_path);
                        $files = ['README.md', 'TIMELINE.md', 'CHAPTERS.md'];
                    @endphp
                    @foreach($files as $file)
                        <li class="flex items-center gap-2">
                            @if(file_exists($projectDir . '/' . $file))
                                <span class="text-green-500">✓</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $file }}</span>
                            @else
                                <span class="text-gray-400">○</span>
                                <span class="text-gray-400">{{ $file }} (not found)</span>
                            @endif
                        </li>
                    @endforeach
                @else
                    <li class="text-gray-500 dark:text-gray-400">No project path configured</li>
                @endif
            </ul>
        </div>

        {{-- Tips --}}
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-5">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">💡 Tips</h4>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li>• Ask for help drafting content</li>
                <li>• Request summaries of chapters</li>
                <li>• Get suggestions for next steps</li>
                <li>• Ask about stakeholder engagement</li>
            </ul>
        </div>
    </div>
</div>
