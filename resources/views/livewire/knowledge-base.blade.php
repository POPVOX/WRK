<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Knowledge Base
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Search Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <form wire:submit.prevent="search" class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                    <div class="lg:col-span-6">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" wire:model.defer="q" placeholder="Search the organization knowledge base..."
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Project</label>
                        <select wire:model="projectId" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                            <option value="">All projects</option>
                            @foreach($projects as $p)
                                <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Type</label>
                        <select wire:model="type" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                            <option value="">Any</option>
                            <option value="file">File</option>
                            <option value="link">Link</option>
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Ext</label>
                        <input type="text" wire:model="ext" placeholder="md"
                               class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tag</label>
                        <input type="text" wire:model="tag" placeholder="policy"
                               class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="lg:col-span-12 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                            Search
                        </button>
                    </div>
                </form>
                @if($isSearching)
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">Searching…</div>
                @endif
            </div>

            {{-- Saved Searches --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Saved Searches</h3>
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model.defer="newCollectionName" placeholder="Name this search"
                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-700 dark:text-white">
                        <button type="button" wire:click="saveCollection"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                            Save
                        </button>
                    </div>
                </div>
                <div class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($collections as $c)
                        <div class="py-2 flex items-center justify-between">
                            <button type="button" wire:click="loadCollection({{ $c['id'] }})"
                                    class="text-sm text-indigo-700 dark:text-indigo-400 hover:underline">
                                {{ $c['name'] }}
                            </button>
                            <button type="button" wire:click="deleteCollection({{ $c['id'] }})"
                                    class="text-xs text-gray-500 hover:text-red-600">Delete</button>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 dark:text-gray-400">No saved searches yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Results --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Results</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ count($results) }} items</div>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($results as $r)
                        <div class="p-5 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $r['title'] }}</div>
                                    @if($r['type'] === 'link')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Link</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ strtoupper($r['file_type'] ?? '') }}</span>
                                    @endif
                                    @if($r['archived'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Archived</span>
                                    @endif
                                    @if($r['missing'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">Missing</span>
                                    @endif
                                </div>
                                @if($r['project'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $r['project'] }}</div>
                                @endif
                                @if($r['snippet'])
                                    <div class="text-sm text-gray-700 dark:text-gray-300 mt-2 prose prose-sm dark:prose-invert max-w-none">
                                        {!! $r['snippet'] !!}
                                    </div>
                                @endif
                            </div>
                            <div class="shrink-0">
                                @if($r['type'] === 'link' && $r['url'])
                                    <a href="{{ $r['url'] }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-white text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors text-xs font-medium">
                                        Open
                                    </a>
                                @else
                                    <a href="{{ route('projects.workspace', ['project' => $r['project_id']]) . '?doc=' . $r['id'] }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-xs font-medium">
                                        View
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-10 text-center text-gray-500 dark:text-gray-400">
                            No results yet. Try a broader search.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
