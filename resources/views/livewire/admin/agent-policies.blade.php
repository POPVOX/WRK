<div class="app-page-frame space-y-6">
    <div class="app-page-head">
        <div>
            <h1 class="app-page-title">Agent Policy Layers</h1>
            <p class="app-page-lead">Manage organization and role prompt constitution, and audit personal layers per agent.</p>
        </div>
        <div class="inline-flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <span>Last refreshed {{ $generatedAt }}</span>
            <button wire:click="refreshData"
                class="rounded-lg bg-gray-900 px-3 py-1.5 font-medium text-white transition hover:bg-black dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                Refresh
            </button>
        </div>
    </div>

    @if(!$migrationReady)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
            <h2 class="text-lg font-semibold">Prompt layering not initialized</h2>
            <p class="mt-2 text-sm">{{ $migrationMessage }}</p>
            <p class="mt-3 text-sm">Run: <span class="font-mono">php artisan migrate --force</span></p>
        </section>
    @else
        <section class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <article class="app-card p-5 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Organization Layer</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Highest-precedence policy and safety constitution.</p>
                </div>

                <form wire:submit="saveOrgLayer" class="space-y-3">
                    <textarea
                        wire:model.defer="orgLayerContent"
                        rows="12"
                        class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="policy.no_external_send: required_approval&#10;policy.no_pii_export: true&#10;tone.default: concise"
                    ></textarea>
                    @error('orgLayerContent')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Save Organization Layer
                    </button>
                </form>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Recent Versions</p>
                    <div class="mt-2 space-y-2">
                        @forelse($orgHistory as $entry)
                            <article class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">v{{ $entry['version'] }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $entry['updated_at'] ?: '—' }}</p>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Updated by {{ $entry['updated_by'] ?: 'Unknown' }}</p>
                                <pre class="mt-2 whitespace-pre-wrap rounded bg-gray-900 px-2 py-1.5 text-[11px] text-gray-100">{{ $entry['content'] }}</pre>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No organization layer versions yet.</p>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="app-card p-5 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Role Layer</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Applied after org rules and before personal layer.</p>
                </div>

                <form wire:submit="saveRoleLayer" class="space-y-3">
                    <textarea
                        wire:model.defer="roleLayerContent"
                        rows="12"
                        class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="strategy.default_horizon: 6_weeks&#10;output.include_citations: true"
                    ></textarea>
                    @error('roleLayerContent')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Save Role Layer
                    </button>
                </form>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Recent Versions</p>
                    <div class="mt-2 space-y-2">
                        @forelse($roleHistory as $entry)
                            <article class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">v{{ $entry['version'] }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $entry['updated_at'] ?: '—' }}</p>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Updated by {{ $entry['updated_by'] ?: 'Unknown' }}</p>
                                <pre class="mt-2 whitespace-pre-wrap rounded bg-gray-900 px-2 py-1.5 text-[11px] text-gray-100">{{ $entry['content'] }}</pre>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No role layer versions yet.</p>
                        @endforelse
                    </div>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <article class="xl:col-span-5 app-card p-5 space-y-3">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Personal Layer (Read-only)</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Review per-agent personal prompt history.</p>
                    </div>
                    <div class="w-72 max-w-full">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Agent</label>
                        <select wire:model.live="selectedAgentId"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @foreach($agentOptions as $agent)
                                <option value="{{ $agent['id'] }}">
                                    {{ $agent['name'] }} ({{ $agent['scope'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    @forelse($personalHistory as $entry)
                        <article class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    @if($entry['version'] > 0)
                                        v{{ $entry['version'] }}
                                    @else
                                        Agent Instructions (fallback)
                                    @endif
                                </p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $entry['updated_at'] ?: '—' }}</p>
                            </div>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Updated by {{ $entry['updated_by'] ?: 'Unknown' }}</p>
                            <pre class="mt-2 whitespace-pre-wrap rounded bg-gray-900 px-2 py-1.5 text-[11px] text-gray-100">{{ $entry['content'] }}</pre>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            No personal prompt layer found for this agent.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="xl:col-span-7 app-card p-5 space-y-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Effective Prompt Preview</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Deterministic merged prompt used for runs and audit.</p>
                </div>

                @if(!empty($promptPreview['diagnostics']))
                    <div class="space-y-2">
                        @foreach($promptPreview['diagnostics'] as $diagnostic)
                            @php
                                $isError = ($diagnostic['severity'] ?? '') === 'error';
                            @endphp
                            <div class="rounded-lg border px-3 py-2 text-sm {{ $isError ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200' : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200' }}">
                                {{ $diagnostic['message'] ?? 'Prompt diagnostic' }}
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($promptPreview))
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-700 dark:text-gray-300">
                            Hash: {{ $promptPreview['effective_prompt_hash'] ?? 'n/a' }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-700 dark:text-gray-300">
                            Org v{{ $promptPreview['layer_versions']['org'] ?? 0 }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-700 dark:text-gray-300">
                            Role v{{ $promptPreview['layer_versions']['role'] ?? 0 }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 dark:bg-gray-700 dark:text-gray-300">
                            Personal v{{ $promptPreview['layer_versions']['personal'] ?? 0 }}
                        </span>
                    </div>
                    <pre class="max-h-[40rem] overflow-auto whitespace-pre-wrap rounded-xl bg-gray-900 px-4 py-3 text-[12px] text-gray-100">{{ $promptPreview['effective_prompt'] ?? '' }}</pre>
                @else
                    <div class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        Select an agent to preview effective prompt output.
                    </div>
                @endif
            </article>
        </section>
    @endif
</div>
