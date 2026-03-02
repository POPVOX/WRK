<div class="space-y-6">
    <div class="app-page-frame">
        <div class="app-page-head">
            <div>
                <h1 class="app-page-title">Notifications Admin</h1>
                <p class="app-page-lead">Create calm, informative notifications and reusable templates for the team.</p>
            </div>
        </div>

        <div class="app-card p-4 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900">Send Notification</h2>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Template</label>
                    <select wire:model.live="selectedTemplateId" class="min-w-[220px] rounded-lg border-gray-300 text-sm">
                        <option value="">Custom</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                    @if($selectedTemplateId)
                        <button type="button" wire:click="applyTemplate({{ $selectedTemplateId }})" class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Apply
                        </button>
                    @endif
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Audience</span>
                    <select wire:model.live="audience" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach($audiences as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</span>
                    <select wire:model.live="sendCategory" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tone</span>
                    <select wire:model.live="sendLevel" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach($levels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @if($audience === 'specific_users')
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recipients</label>
                    <select wire:model.live="selectedUserIds" multiple size="6" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Hold Cmd/Ctrl to select multiple users.</p>
                    @error('selectedUserIds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kind Key</span>
                    <input wire:model="sendKind" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="project_added">
                </label>
                <label class="space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action Label (optional)</span>
                    <input wire:model="sendActionLabel" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Open Project">
                </label>
            </div>

            <label class="space-y-1 block">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Title</span>
                <input wire:model="sendTitle" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Marci added you to a new project">
                @error('sendTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-1 block">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Message</span>
                <textarea wire:model="sendBody" rows="3" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Project Name"></textarea>
                @error('sendBody') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>

            <label class="space-y-1 block">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action URL (optional)</span>
                <input wire:model="sendActionUrl" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="/projects/123">
            </label>

            <div class="flex items-center justify-end">
                <button type="button" wire:click="sendNow" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" wire:loading.attr="disabled" wire:target="sendNow">
                    <span wire:loading.remove wire:target="sendNow">Send Notification</span>
                    <span wire:loading wire:target="sendNow">Sending...</span>
                </button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="app-card p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-gray-900">Template Library</h2>
                    <input wire:model.live.debounce.250ms="templateSearch" type="text" placeholder="Search templates..." class="w-48 rounded-lg border-gray-300 text-sm">
                </div>

                <div class="space-y-2 max-h-[28rem] overflow-y-auto pr-1">
                    @forelse($templates as $template)
                        <article class="rounded-xl border border-gray-200 bg-white p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $template->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $template->kind }} · {{ ucfirst($template->default_level) }}</p>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $template->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $template->is_active ? 'Active' : 'Paused' }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-gray-700">{{ $template->title_template }}</p>
                            <p class="mt-1 text-xs text-gray-600">{{ $template->body_template }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="applyTemplate({{ $template->id }})" class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Use</button>
                                <button type="button" wire:click="toggleTemplateActive({{ $template->id }})" class="rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    {{ $template->is_active ? 'Pause' : 'Activate' }}
                                </button>
                                <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Delete this template?" class="rounded-lg border border-red-200 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Delete</button>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-gray-500">No templates yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="space-y-4">
                <div class="app-card p-4 space-y-3">
                    <h2 class="text-sm font-semibold text-gray-900">Create Template</h2>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Name</span>
                            <input wire:model="newTemplateName" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Added To Project">
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kind Key</span>
                            <input wire:model="newTemplateKind" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="project_added">
                        </label>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</span>
                            <select wire:model.live="newTemplateCategory" class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach($categories as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tone</span>
                            <select wire:model.live="newTemplateLevel" class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach($levels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="space-y-1 block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Title Template</span>
                        <input wire:model="newTemplateTitle" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="{actor_name} added you to a project">
                    </label>
                    <label class="space-y-1 block">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Body Template</span>
                        <textarea wire:model="newTemplateBody" rows="3" class="w-full rounded-lg border-gray-300 text-sm" placeholder="{project_name}"></textarea>
                    </label>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action Label</span>
                            <input wire:model="newTemplateActionLabel" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Open">
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action URL</span>
                            <input wire:model="newTemplateActionUrl" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="/projects/{project_id}">
                        </label>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="newTemplateIsActive" class="rounded border-gray-300 text-indigo-600">
                        Active
                    </label>
                    <div class="flex items-center justify-end">
                        <button type="button" wire:click="saveTemplate" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Save Template
                        </button>
                    </div>
                </div>

                <div class="app-card p-4 space-y-3">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Manual Notifications</h2>
                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                        @forelse($recentManualNotifications as $item)
                            <article class="rounded-lg border border-gray-200 bg-white px-3 py-2">
                                <p class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-600">{{ $item['body'] }}</p>
                                <p class="mt-1 text-[11px] text-gray-500">To {{ $item['recipient'] }} · {{ $item['time_label'] }}</p>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500">No manual notifications sent yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

