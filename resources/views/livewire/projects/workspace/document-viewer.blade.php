{{-- Document Viewer Modal --}}
@if($showDocumentViewer)
    <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="document-viewer-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-gray-900/75 transition-opacity" wire:click="closeDocumentViewer"></div>

            {{-- Modal Panel --}}
            <div
                class="relative flex-1 flex flex-col max-h-screen bg-white dark:bg-gray-800 ml-16 mr-16 my-8 rounded-xl shadow-2xl overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"
                    style="background: linear-gradient(to right, #4f46e5, #7c3aed);">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📄</span>
                        <div>
                            <h2 id="document-viewer-title" class="text-lg font-semibold text-white">{{ $documentTitle }}
                            </h2>
                            <p class="text-sm text-white/70">Document Viewer</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- Style Check Button --}}
                        <button wire:click="runStyleCheckQueued"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-colors text-sm font-medium backdrop-blur-sm"
                            @disabled($isStyleChecking)>
                            @if($isStyleChecking)
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>Analyzing...</span>
                            @else
                                <span>✨</span>
                                <span>Run Style Check</span>
                            @endif
                        </button>
                        {{-- Close Button --}}
                        <button wire:click="closeDocumentViewer"
                            class="p-2 text-white/70 hover:text-white transition-colors rounded-lg hover:bg-white/10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                @if($styleNotice)
                    <div class="px-6 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 text-xs border-t border-amber-200 dark:border-amber-800">
                        {{ $styleNotice }}
                    </div>
                @endif

                {{-- Loading Overlay - Uses wire:loading for immediate client-side feedback --}}
                <div wire:loading wire:target="runStyleCheckQueued"
                    class="absolute inset-0 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm z-10 flex items-center justify-center">
                    <div class="text-center">
                        <div class="relative">
                            <div class="w-16 h-16 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                            <div
                                class="absolute top-0 left-0 w-16 h-16 border-4 border-indigo-600 rounded-full border-t-transparent animate-spin">
                            </div>
                        </div>
                        <p class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Analyzing Document</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Checking against POPVOX Style Guide...</p>
                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">This may take 10-30 seconds</p>
                    </div>
                </div>

                {{-- Poll for style check completion while processing --}}
                @if($isStyleChecking)
                    <div wire:poll.2s="checkStyleCheckStatus"></div>
                @endif


                {{-- Content Area --}}
                <div class="flex-1 flex overflow-hidden">
                    {{-- Document Content --}}
                    <div class="flex-1 overflow-y-auto {{ count($styleCheckSuggestions) > 0 ? 'lg:w-2/3' : 'w-full' }}">
                        <div class="max-w-4xl mx-auto px-8 py-10">
                            <article
                                class="prose prose-lg prose-slate dark:prose-invert prose-headings:font-bold prose-headings:tracking-tight prose-h1:text-3xl prose-h2:text-2xl prose-h2:border-b prose-h2:border-gray-200 prose-h2:dark:border-gray-700 prose-h2:pb-3 prose-h2:mb-6 prose-h3:text-xl prose-p:leading-relaxed prose-p:text-gray-700 prose-p:dark:text-gray-300 prose-li:text-gray-700 prose-li:dark:text-gray-300 prose-strong:text-gray-900 prose-strong:dark:text-white prose-code:bg-gray-100 prose-code:dark:bg-gray-700 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm prose-code:before:content-none prose-code:after:content-none prose-pre:bg-gray-900 prose-pre:text-gray-100 prose-blockquote:border-l-indigo-500 prose-blockquote:bg-indigo-50/50 prose-blockquote:dark:bg-indigo-900/20 prose-blockquote:px-4 prose-blockquote:py-1 max-w-none">
                                {!! \Illuminate\Support\Str::markdown($documentContent, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                            </article>
                        </div>
                    </div>

                    {{-- Suggestions Panel --}}
                    @if($styleCheckComplete)
                        <div
                            class="w-full lg:w-1/3 border-l border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900">
                            {{-- Suggestions Header --}}
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        Style Check Results
                                    </h3>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ count($styleCheckSuggestions) > 0 ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                                        {{ count($styleCheckSuggestions) }}
                                        {{ Str::plural('suggestion', count($styleCheckSuggestions)) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Suggestions List --}}
                            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                                @forelse($styleCheckSuggestions as $index => $suggestion)
                                            <div
                                                class="bg-white dark:bg-gray-800 rounded-lg border {{ $suggestion['status'] === 'accepted' ? 'border-green-300 dark:border-green-700' : ($suggestion['status'] === 'rejected' ? 'border-red-300 dark:border-red-700 opacity-50' : 'border-gray-200 dark:border-gray-700') }} overflow-hidden">
                                                {{-- Importance Badge --}}
                                                <div
                                                    class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                                                                    {{ $suggestion['importance'] === 'high' ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' :
                                    ($suggestion['importance'] === 'medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' :
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300') }}">
                                                        {{ ucfirst($suggestion['importance']) }}
                                                    </span>
                                                    @if($suggestion['status'] !== 'pending')
                                                        <span
                                                            class="text-xs {{ $suggestion['status'] === 'accepted' ? 'text-green-600' : 'text-red-600' }}">
                                                            {{ $suggestion['status'] === 'accepted' ? '✓ Accepted' : '✗ Rejected' }}
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Suggestion Content --}}
                                                <div class="p-4 space-y-3">
                                                    <div>
                                                        <div
                                                            class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                            Original</div>
                                                        <div
                                                            class="text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded border-l-2 border-red-500">
                                                            {{ $suggestion['original'] }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div
                                                            class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                                            Suggested</div>
                                                        <div
                                                            class="text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2 py-1 rounded border-l-2 border-green-500">
                                                            {{ $suggestion['replacement'] }}
                                                        </div>
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 italic">
                                                        📖 {{ $suggestion['rule'] }}
                                                    </div>
                                                </div>

                                                {{-- Action Buttons --}}
                                                @if($suggestion['status'] === 'pending')
                                                    <div
                                                        class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                                                        <button wire:click="acceptSuggestion({{ $index }})"
                                                            class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            Accept
                                                        </button>
                                                        <button wire:click="rejectSuggestion({{ $index }})"
                                                            class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors text-sm font-medium">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            Reject
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                @empty
                                    <div class="text-center py-8">
                                        <div class="text-4xl mb-3">✨</div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No style issues found!</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">This document follows the POPVOX
                                            Foundation Style Guide.</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Apply Button --}}
                            @php
                                $acceptedCount = collect($styleCheckSuggestions)->where('status', 'accepted')->count();
                            @endphp
                            @if($acceptedCount > 0)
                                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                    <button wire:click="applyAcceptedSuggestions"
                                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Apply {{ $acceptedCount }} {{ Str::plural('Change', $acceptedCount) }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
