{{-- Documents Tab --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-5 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project Documents</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if($project->project_path)
                        Synced from: <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $project->project_path }}</code>
                    @else
                        Uploaded files and linked documents
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $project->documents->count() }} documents</span>
                @if($project->project_path)
                    <button wire:click="previewSyncDocumentsFromFolder"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors text-sm font-medium shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4h10M6 8h12M6 12h10M6 16h8" />
                        </svg>
                        Preview Sync
                    </button>
                    <button wire:click="previewSyncDocumentsFromFolder"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors text-sm font-medium shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4h10M6 8h12M6 12h10M6 16h8" />
                        </svg>
                        Preview Sync
                    </button>
                    <button wire:click="syncDocumentsFromFolder"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sync Documents
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Upload & Link Forms --}}
    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Upload File --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Upload File</h4>
            <form wire:submit.prevent="uploadDocument" class="space-y-3">
                <div>
                    <input type="text" wire:model="uploadTitle" placeholder="Optional title"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <input type="file" wire:model="uploadFile"
                           class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Allowed: md, txt, pdf, docx, xlsx • Max 20MB</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                        Upload
                    </button>
                </div>
            </form>
        </div>

        {{-- Add Link --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">Add Link</h4>
            <form wire:submit.prevent="addDocumentLink" class="space-y-3">
                <div>
                    <input type="text" wire:model="linkTitle" placeholder="Document title"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <input type="url" wire:model="linkUrl" placeholder="https://docs.google.com/document/d/..."
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tip: Paste a Google Doc link to auto-cache a text version for the knowledge base.</p>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                        Add Link
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Knowledge Base Search --}}
    <div class="px-5 pb-5">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900 dark:text-white">Knowledge Base Search</h4>
                @if($kbIsSearching)
                    <span class="text-xs text-gray-500 dark:text-gray-400">Searching...</span>
                @endif
            </div>
            <form wire:submit.prevent="searchKnowledgeBase" class="flex gap-2">
                <input type="text" wire:model.defer="kbQuery" placeholder="Find across this project's knowledge base..."
                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                    Search
                </button>
            </form>

            @if(!empty($kbResults))
                <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($kbResults as $hit)
                        <div class="py-3 flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $hit['title'] }}</div>
                                @if($hit['snippet'])
                                    <div class="text-xs text-gray-600 dark:text-gray-300 mt-1">…{{ $hit['snippet'] }}…</div>
                                @endif
                                <div class="text-[11px] text-gray-400 mt-1 uppercase">
                                    {{ strtoupper($hit['hit']) }}
                                </div>
                            </div>
                            <div class="shrink-0">
                                @if($hit['type'] === 'link')
                                    <a href="{{ optional($project->documents->firstWhere('id',$hit['id']))?->url }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-white text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors text-xs font-medium">
                                        Open
                                    </a>
                                @else
                                    <button type="button" wire:click="viewDocument({{ $hit['id'] }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-xs font-medium">
                                        View
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($project->documents->count() > 0)
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($project->documents as $doc)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-xl">
                                @php
                                    $ext = strtolower($doc->file_type ?? pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION) ?? '');
                                @endphp
                                @switch($ext)
                                    @case('md') 📝 @break
                                    @case('pdf') 📄 @break
                                    @case('docx')
                                    @case('doc') 📃 @break
                                    @case('xlsx')
                                    @case('xls') 📊 @break
                                    @case('txt') 📋 @break
                                    @default 📁
                                @endswitch
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $doc->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-2">
                                    @if($doc->file_path)
                                        <span>{{ $doc->file_path }}</span>
                                    @endif
                                    @if($doc->file_size)
                                        <span>•</span>
                                        <span>{{ $doc->formatted_size }}</span>
                                    @endif
                                    @if($doc->updated_at)
                                        <span>•</span>
                                        <span>Updated {{ $doc->updated_at->diffForHumans() }}</span>
                                    @endif
                                </div>

                                {{-- Tags --}}
                                @if(!empty($doc->tags))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($doc->tags as $t)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">#{{ str_replace(' ', '_', $t) }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Edit Tags --}}
                                <div class="mt-2 flex items-center gap-2">
                                    <input type="text" wire:model.defer="tagsEdit.{{ $doc->id }}" placeholder="Add tags (comma separated)"
                                           class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-xs dark:bg-gray-700 dark:text-white w-64">
                                    <button wire:click="saveDocumentTags({{ $doc->id }})"
                                            class="px-2.5 py-1 bg-white text-indigo-700 border border-indigo-200 rounded hover:bg-indigo-50 text-xs font-medium">
                                        Save Tags
                                    </button>
                                </div>

                                {{-- Common Tags --}}
                                @if(!empty($commonTags))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($commonTags as $t)
                                            <button type="button"
                                                    wire:click="$set('tagsEdit.{{ $doc->id }}', (tagsEdit[{{ $doc->id }}] ?? '') ? (tagsEdit[{{ $doc->id }}] + ', {{ $t }}') : '{{ $t }}')"
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                                                #{{ str_replace(' ', '_', $t) }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Suggested Tags --}}
                                @if(!empty($doc->suggested_tags))
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($doc->suggested_tags as $t)
                                            <button type="button" wire:click="addSuggestedTag({{ $doc->id }}, '{{ $t }}')"
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800">
                                                + {{ str_replace(' ', '_', $t) }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($doc->ai_indexed)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    AI Indexed
                                </span>
                            @endif
                            @if($doc->missing_on_disk)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                                    Missing
                                </span>
                            @endif
                            @if($doc->is_archived)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    Archived
                                </span>
                            @endif
                            @if($doc->url)
                                <a href="{{ $doc->url }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <span>Open</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @elseif($doc->file_path)
                                @php
                                    $fullPath = base_path($doc->file_path);
                                    $canView = in_array($ext, ['md', 'txt']);
                                    $isUploaded = \Illuminate\Support\Str::startsWith($doc->file_path, 'project_uploads/');
                                @endphp
                                @if($canView && file_exists($fullPath))
                                    <button type="button"
                                        wire:click="viewDocument({{ $doc->id }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>View</span>
                                    </button>
                                @endif
                                @if($isUploaded)
                                    <a href="{{ $doc->getAccessUrl() }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-white text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                        </svg>
                                        <span>Download</span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-12 text-center">
            <div class="text-4xl mb-4">📁</div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">No documents yet</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-2 mb-4">
                @if($project->project_path)
                    Click "Sync Documents" to import files from your project folder.
                @else
                    Upload documents or configure a project folder path.
                @endif
            </p>
            @if($project->project_path)
                <button wire:click="syncDocumentsFromFolder"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync Documents Now
                </button>
            @endif
        </div>
    @endif
</div>

{{-- Sync Preview Modal --}}
@if($showSyncPreviewModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-gray-900/70" wire:click="$set('showSyncPreviewModal', false)"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Preview Document Sync</h3>
                <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" wire:click="$set('showSyncPreviewModal', false)">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="font-medium text-green-800 dark:text-green-300">To Add</div>
                    <div class="text-xs text-green-700 dark:text-green-400 mb-2">{{ count($syncPreview['add']) }} file(s)</div>
                    <ul class="space-y-1 max-h-48 overflow-y-auto text-xs">
                        @forelse($syncPreview['add'] as $i)
                            <li class="text-gray-700 dark:text-gray-300">{{ $i['file_path'] }}</li>
                        @empty
                            <li class="text-gray-400">None</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                    <div class="font-medium text-amber-800 dark:text-amber-300">To Update</div>
                    <div class="text-xs text-amber-700 dark:text-amber-400 mb-2">{{ count($syncPreview['update']) }} file(s)</div>
                    <ul class="space-y-1 max-h-48 overflow-y-auto text-xs">
                        @forelse($syncPreview['update'] as $i)
                            <li class="text-gray-700 dark:text-gray-300">{{ $i['file_path'] }}</li>
                        @empty
                            <li class="text-gray-400">None</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="font-medium text-gray-800 dark:text-gray-200">Missing on Disk</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ count($syncPreview['missing']) }} file(s)</div>
                    <ul class="space-y-1 max-h-48 overflow-y-auto text-xs">
                        @forelse($syncPreview['missing'] as $i)
                            <li class="text-gray-700 dark:text-gray-300">{{ $i['file_path'] }}</li>
                        @empty
                            <li class="text-gray-400">None</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    @if(count($syncPreview['missing']) > 0)
                        <button class="px-3 py-2 rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-300"
                                wire:click="archiveMissingDocumentsFromPreview">
                            Archive Missing
                        </button>
                        <button class="px-3 py-2 rounded-lg bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300"
                                wire:click="removeMissingDocumentsFromPreview">
                            Remove Missing from DB
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
                            wire:click="$set('showSyncPreviewModal', false)">Cancel</button>
                    <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"
                            wire:click="applySyncDocumentsFromFolder">Apply Sync</button>
                </div>
            </div>
        </div>
    </div>
@endif
