<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-4 sm:p-6">
    <div class="max-w-7xl mx-auto space-y-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Outreach Suite</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Newsletters, bulk campaigns, automated workflows, and Substack planning in one workspace.
                </p>
            </div>
            <a href="{{ route('communications.inbox') }}" wire:navigate
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Open Inbox
            </a>
        </div>

        @if(!$migrationReady)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
                <h2 class="text-lg font-semibold">Outreach stack not initialized</h2>
                <p class="mt-2 text-sm">{{ $migrationMessage }}</p>
                <p class="mt-3 text-sm">Run: <span class="font-mono">php artisan migrate --force</span></p>
            </section>
        @endif

        @if($migrationReady)
            <nav class="flex flex-wrap items-center gap-2">
                @foreach(['newsletters' => 'Newsletters', 'campaigns' => 'Campaigns', 'automations' => 'Automations', 'substack' => 'Substack', 'activity' => 'Activity'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')"
                        class="rounded-lg px-3 py-2 text-sm font-medium {{ $tab === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        @endif

        @if($migrationReady && $tab === 'newsletters')
            <section class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <article class="xl:col-span-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Newsletter Planning</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create newsletter lanes with cadence, audience, and planning notes.</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</label>
                            <input type="text" wire:model.defer="newsletterForm.name"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Future-Proofing Congress">
                            @error('newsletterForm.name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project</label>
                                <select wire:model.defer="newsletterForm.project_id"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">No project</option>
                                    @foreach($projectOptions as $project)
                                        <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Channel</label>
                                <select wire:model.defer="newsletterForm.channel"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="hybrid">Hybrid</option>
                                    <option value="substack">Substack</option>
                                    <option value="gmail">Gmail</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cadence</label>
                                <select wire:model.defer="newsletterForm.cadence"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Biweekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="ad_hoc">Ad hoc</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Next Issue Date</label>
                            <input type="date" wire:model.defer="newsletterForm.next_issue_date"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Audience Statuses</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($contactStatusOptions as $status => $label)
                                    <label class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700">
                                        <input type="checkbox" wire:model.defer="newsletterForm.audience_statuses" value="{{ $status }}"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Planning Notes</label>
                            <textarea rows="5" wire:model.defer="newsletterForm.planning_notes"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Editorial priorities, sign-off rules, and owner workflow notes."></textarea>
                        </div>

                        <button wire:click="createNewsletter"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Create Newsletter Plan
                        </button>
                    </div>
                </article>

                <article class="xl:col-span-7 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Newsletter Portfolio</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            {{ $newsletters->count() }} newsletters
                        </span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($newsletters as $newsletter)
                            <article class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $newsletter->name }}</h3>
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ strtoupper($newsletter->status) }}
                                    </span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $newsletter->channel }}
                                    </span>
                                    @if($newsletter->cadence)
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $newsletter->cadence }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    @if($newsletter->project)
                                        Project: {{ $newsletter->project->name }} ·
                                    @endif
                                    Next issue:
                                    {{ $newsletter->next_issue_date ? $newsletter->next_issue_date->format('M j, Y') : 'Not set' }}
                                </div>
                                @if($newsletter->planning_notes)
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $newsletter->planning_notes }}</p>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                No newsletters yet. Create your first planning lane on the left.
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        @if($migrationReady && $tab === 'campaigns')
            <section class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <article class="xl:col-span-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Create Campaign</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Draft, schedule, or immediately send a bulk outreach campaign.</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Campaign Name</label>
                            <input type="text" wire:model.defer="campaignForm.name"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="WFD due diligence follow-up">
                            @error('campaignForm.name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Newsletter</label>
                                <select wire:model.defer="campaignForm.newsletter_id"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">No newsletter</option>
                                    @foreach($newsletterOptions as $newsletter)
                                        <option value="{{ $newsletter['id'] }}">{{ $newsletter['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project</label>
                                <select wire:model.defer="campaignForm.project_id"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">No project</option>
                                    @foreach($projectOptions as $project)
                                        <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</label>
                                <select wire:model.defer="campaignForm.campaign_type"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="bulk">Bulk</option>
                                    <option value="newsletter">Newsletter</option>
                                    <option value="automated">Automated</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Channel</label>
                                <select wire:model.defer="campaignForm.channel"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="gmail">Gmail</option>
                                    <option value="substack">Substack</option>
                                    <option value="hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Send Mode</label>
                                <select wire:model.live="campaignForm.send_mode"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="draft">Draft</option>
                                    <option value="immediate">Send now</option>
                                    <option value="scheduled">Scheduled</option>
                                </select>
                            </div>
                        </div>

                        @if($campaignForm['send_mode'] === 'scheduled')
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Scheduled For</label>
                                <input type="datetime-local" wire:model.defer="campaignForm.scheduled_for"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        @endif

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subject</label>
                            <input type="text" wire:model.defer="campaignForm.subject"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('campaignForm.subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Preheader</label>
                            <input type="text" wire:model.defer="campaignForm.preheader"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Body</label>
                            <textarea rows="6" wire:model.defer="campaignForm.body_text"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Write the campaign body. This sends as plain text through Gmail."></textarea>
                            @error('campaignForm.body_text') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Audience</h3>
                            <div class="mt-2 grid grid-cols-1 gap-2">
                                <select wire:model.live="campaignForm.audience_mode"
                                    class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="contacts">From contacts by status</option>
                                    <option value="manual">Manual recipient list</option>
                                </select>

                                @if($campaignForm['audience_mode'] === 'contacts')
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($contactStatusOptions as $status => $label)
                                            <label class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700">
                                                <input type="checkbox" wire:model.defer="campaignForm.contact_statuses" value="{{ $status }}"
                                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <textarea rows="4" wire:model.defer="campaignForm.manual_recipients"
                                        class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="One per line: name@email.com or Name <name@email.com>"></textarea>
                                @endif
                            </div>
                        </div>

                        <button wire:click="createCampaign"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Save Campaign
                        </button>
                    </div>
                </article>

                <article class="xl:col-span-7 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Campaign Queue</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            {{ $campaigns->count() }} campaigns
                        </span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($campaigns as $campaign)
                            <article class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $campaign->name }}</h3>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $campaign->subject ?: '(no subject)' }}
                                            @if($campaign->project)
                                                · {{ $campaign->project->name }}
                                            @endif
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ strtoupper($campaign->status) }}</span>
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $campaign->campaign_type }}</span>
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $campaign->channel }}</span>
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $campaign->sent_count }}/{{ $campaign->recipients_count }} sent</span>
                                            @if($campaign->failed_count > 0)
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ $campaign->failed_count }} failed</span>
                                            @endif
                                        </div>
                                        @if($campaign->scheduled_for)
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Scheduled for {{ $campaign->scheduled_for->format('M j, Y g:i A') }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(in_array($campaign->status, ['draft', 'scheduled', 'failed']))
                                            <button wire:click="queueCampaignNow({{ $campaign->id }})"
                                                class="inline-flex items-center rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                                                Queue Send
                                            </button>
                                        @endif
                                        @if(in_array($campaign->status, ['draft', 'scheduled', 'sending']))
                                            <button wire:click="cancelCampaign({{ $campaign->id }})"
                                                class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                                Cancel
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                No campaigns yet. Create one on the left.
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        @if($migrationReady && $tab === 'automations')
            <section class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <article class="xl:col-span-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Automation Recipe</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Create recurring outreach automations that draft, send, or queue tasks.</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</label>
                            <input type="text" wire:model.defer="automationForm.name"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Weekly policy digest draft">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Newsletter</label>
                                <select wire:model.defer="automationForm.newsletter_id"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">No newsletter</option>
                                    @foreach($newsletterOptions as $newsletter)
                                        <option value="{{ $newsletter['id'] }}">{{ $newsletter['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project</label>
                                <select wire:model.defer="automationForm.project_id"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">No project</option>
                                    @foreach($projectOptions as $project)
                                        <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Action</label>
                                <select wire:model.defer="automationForm.action_type"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="draft_campaign">Draft campaign</option>
                                    <option value="send_campaign">Send campaign</option>
                                    <option value="create_task">Create task</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Interval (hours)</label>
                                <input type="number" min="1" max="720" wire:model.defer="automationForm.interval_hours"
                                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompt</label>
                            <textarea rows="4" wire:model.defer="automationForm.prompt"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Context for the automation to use each run."></textarea>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Campaign Subject Template</label>
                            <input type="text" wire:model.defer="automationForm.subject"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Weekly update: policy + project momentum">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Campaign Body Template</label>
                            <textarea rows="4" wire:model.defer="automationForm.body_text"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Audience Source</label>
                            <select wire:model.live="automationForm.audience_mode"
                                class="mt-2 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="contacts">From contacts by status</option>
                                <option value="manual">Manual recipient list</option>
                            </select>
                            @if($automationForm['audience_mode'] === 'contacts')
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($contactStatusOptions as $status => $label)
                                        <label class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-2.5 py-1 text-xs dark:border-gray-600 dark:bg-gray-700">
                                            <input type="checkbox" wire:model.defer="automationForm.contact_statuses" value="{{ $status }}"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <textarea rows="3" wire:model.defer="automationForm.manual_recipients"
                                    class="mt-2 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="One recipient per line"></textarea>
                            @endif
                        </div>

                        <button wire:click="createAutomation"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Create Automation
                        </button>
                    </div>
                </article>

                <article class="xl:col-span-7 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Automation Recipes</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            {{ $automations->count() }} automations
                        </span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse($automations as $automation)
                            <article class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $automation->name }}</h3>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $automation->action_type }} · {{ $automation->trigger_type }}
                                            @if($automation->newsletter)
                                                · {{ $automation->newsletter->name }}
                                            @endif
                                            @if($automation->project)
                                                · {{ $automation->project->name }}
                                            @endif
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $automation->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                                {{ strtoupper($automation->status) }}
                                            </span>
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                Next run: {{ $automation->next_run_at ? $automation->next_run_at->format('M j, g:i A') : 'Not scheduled' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button wire:click="runAutomationNow({{ $automation->id }})"
                                            class="inline-flex items-center rounded-lg bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                                            Run now
                                        </button>
                                        <button wire:click="toggleAutomation({{ $automation->id }})"
                                            class="inline-flex items-center rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                            {{ $automation->status === 'active' ? 'Pause' : 'Activate' }}
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                No automations yet. Create your first recipe on the left.
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        @if($migrationReady && $tab === 'substack')
            <section class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <article class="xl:col-span-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Substack Integration</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Save publication settings and sync feed posts for editorial planning.</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Publication Name</label>
                            <input type="text" wire:model.defer="substackForm.publication_name"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Publication URL</label>
                            <input type="url" wire:model.defer="substackForm.publication_url"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="https://example.substack.com">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">RSS Feed URL (optional)</label>
                            <input type="url" wire:model.defer="substackForm.rss_feed_url"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="https://example.substack.com/feed">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">From Email (optional)</label>
                            <input type="email" wire:model.defer="substackForm.email_from"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">API Key (optional)</label>
                            <textarea rows="3" wire:model.defer="substackForm.api_key"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Store key for future API workflows (send/list sync)."></textarea>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button wire:click="saveSubstackConnection"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Save Settings
                            </button>
                            <button wire:click="syncSubstack"
                                class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                Sync Feed
                            </button>
                        </div>
                    </div>
                </article>

                <article class="xl:col-span-7 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Substack Posts</h2>
                        @if($substackConnection)
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $substackConnection->status === 'connected' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                                {{ strtoupper($substackConnection->status) }}
                            </span>
                        @endif
                    </div>

                    @if($substackConnection && $substackConnection->last_error)
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                            {{ $substackConnection->last_error }}
                        </div>
                    @endif

                    <div class="mt-4 space-y-3">
                        @forelse($substackPosts as $post)
                            <article class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $post['title'] }}</h3>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $post['published_at'] ?? 'Date unknown' }}
                                    @if(!empty($post['author']))
                                        · {{ $post['author'] }}
                                    @endif
                                </div>
                                @if(!empty($post['url']))
                                    <a href="{{ $post['url'] }}" target="_blank" rel="noopener noreferrer"
                                        class="mt-2 inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">
                                        Open post
                                    </a>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                No feed posts loaded yet. Save settings and run Sync Feed.
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif

        @if($migrationReady && $tab === 'activity')
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Outreach Activity Log</h2>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ $activityLogs->count() }} events
                    </span>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse($activityLogs as $log)
                        <article class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 dark:border-gray-700 dark:bg-gray-900/40">
                            <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $log->action }}</span>
                                <span>{{ $log->created_at?->format('M j, Y g:i A') }}</span>
                                @if($log->campaign)
                                    <span>Campaign: {{ $log->campaign->name }}</span>
                                @endif
                                @if($log->newsletter)
                                    <span>Newsletter: {{ $log->newsletter->name }}</span>
                                @endif
                                @if($log->automation)
                                    <span>Automation: {{ $log->automation->name }}</span>
                                @endif
                            </div>
                            @if($log->summary)
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $log->summary }}</p>
                            @endif
                            @if($log->details)
                                <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">{{ json_encode($log->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            No outreach activity yet.
                        </div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</div>
