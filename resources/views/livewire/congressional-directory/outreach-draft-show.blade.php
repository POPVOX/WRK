<div @if($draft->status === 'building' || $sendingSummary['active'] > 0) wire:poll.3s @endif class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    @php
        $snapshotReviewable = in_array($draft->status, ['draft', 'ready'], true);
        $guessBatch = data_get($draft->metadata, 'email_guess_batch', []);
        $guessBatchRunning = ($guessBatch['status'] ?? null) === 'queued' && $draft->status === 'building';
    @endphp
    <x-congress-nav />
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('congress.campaigns') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Campaigns</a>
            <p class="mt-4 text-sm font-semibold uppercase tracking-[0.14em] text-indigo-600">Campaign builder</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">{{ $draft->name }}</h1>
            <p class="mt-2 text-gray-600">Audience: <a href="{{ route('congress.lists', ['list' => $draft->congressional_staff_list_id]) }}" wire:navigate class="font-semibold text-indigo-700 hover:underline">{{ $draft->staffList->name }}</a>. Review a fixed recipient snapshot, preview personalization, and deliver approved recipients in batches you control.</p>
        </div>
        @if($canManage)
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="refreshSnapshot" wire:confirm="Refresh from the staff list? This resets all recipient approvals and exclusions." @disabled($draft->status === 'building') class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-wait disabled:opacity-60">
                    {{ $draft->status === 'building' ? 'Building snapshot…' : 'Refresh snapshot' }}
                </button>
                <button type="button" wire:click="deleteDraft" wire:confirm="Delete this campaign? The staff list and profiles will not be changed." class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                    Delete campaign
                </button>
            </div>
        @endif
    </div>

    <section class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm text-indigo-950">
        <p class="font-semibold">Controlled Gmail delivery is enabled for the campaign owner.</p>
        <p class="mt-1">Only approved, unsent recipients are selected. The saved batch size applies to both manual and automated delivery, with suppression rechecked immediately before every send.</p>
    </section>

    @if($draft->status === 'building')
        <section class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm text-indigo-950" role="status">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <div>
                    <p class="font-semibold">{{ $guessBatchRunning ? 'Generating provisional email guesses…' : 'Building the recipient snapshot…' }}</p>
                    <p class="mt-1 text-indigo-800">{{ $guessBatchRunning ? 'The workbench is applying the saved House and Senate patterns, then it will rebuild the recipient snapshot.' : 'The workbench is resolving addresses and removing duplicates in the background.' }} You can safely leave this page; it will update automatically.</p>
                </div>
            </div>
        </section>
    @elseif($draft->status === 'failed')
        <section class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" role="alert">
            <p class="font-semibold">{{ ($guessBatch['status'] ?? null) === 'failed' ? 'The provisional email batch could not be completed.' : 'The recipient snapshot could not be built.' }}</p>
            <p class="mt-1">No email was sent. {{ ($guessBatch['status'] ?? null) === 'failed' ? 'Review the batch settings below and try it again.' : 'Use “Refresh snapshot” to try again.' }}</p>
        </section>
    @endif

    @if($canManage)
        <section class="app-surface p-5">
            <form wire:submit="saveCampaignName" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="min-w-0 flex-1 text-sm font-semibold text-gray-700">Campaign name<input type="text" wire:model.defer="campaignName" class="mt-1 block w-full rounded-lg border-gray-300"></label>
                <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Save name</button>
            </form>
        </section>

        <section class="app-surface p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Campaign viewers</h2>
                    <p class="mt-1 text-sm text-gray-500">Added team members can inspect this campaign, but cannot edit the message, approve recipients, delete it, or send anything.</p>
                </div>
                @if($availableViewers->isNotEmpty())
                    <form wire:submit="addViewer" class="flex w-full max-w-xl flex-col gap-2 sm:flex-row">
                        <div class="min-w-0 flex-1">
                            <select wire:model="selectedViewerId" class="block w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Select a team member...</option>
                                @foreach($availableViewers as $teamMember)
                                    <option value="{{ $teamMember->id }}">{{ $teamMember->name }} · {{ $teamMember->email }}</option>
                                @endforeach
                            </select>
                            @error('selectedViewerId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Add viewer</button>
                    </form>
                @endif
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse($viewers as $viewer)
                    <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700">
                        <span>{{ $viewer->name }}</span>
                        @if(!$viewer->is_active)<span class="text-xs text-gray-400">Former staff</span>@endif
                        <button type="button" wire:click="removeViewer({{ $viewer->id }})" wire:confirm="Remove {{ $viewer->name }}'s access to this campaign?" class="font-bold text-gray-400 hover:text-red-600" aria-label="Remove {{ $viewer->name }}">×</button>
                    </span>
                @empty
                    <p class="text-sm text-gray-500">Only you can currently view this campaign.</p>
                @endforelse
            </div>
        </section>
    @else
        <section class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm text-indigo-900">
            <p class="font-semibold">View-only campaign shared by {{ $draft->user?->name ?? 'a teammate' }}</p>
            <p class="mt-1">You can inspect recipients, evidence, and personalization previews. Ask the campaign owner to make changes.</p>
        </section>
    @endif

    @if($canManage)
        <section class="app-surface p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Batch enrichment</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">Generate provisional email guesses</h2>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500">Applies only to recipients with no known address in a member office. Committee and administrative offices are skipped. Every generated address remains provisional and requires individual approval.</p>
                </div>
                <div class="shrink-0 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">
                    <span class="font-semibold">{{ number_format($emailGuessEstimate['guessable']) }}</span> guessable
                    <span class="text-gray-400">·</span>
                    {{ number_format($emailGuessEstimate['house']) }} House
                    <span class="text-gray-400">·</span>
                    {{ number_format($emailGuessEstimate['senate']) }} Senate
                </div>
            </div>

            @if(($guessBatch['status'] ?? null) === 'completed')
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    Last batch generated <strong>{{ number_format($guessBatch['generated'] ?? 0) }}</strong> provisional addresses and skipped {{ number_format($guessBatch['skipped'] ?? 0) }} records. No email was sent.
                </div>
            @endif

            <form wire:submit="generateEmailGuesses" class="mt-4 space-y-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label for="batch-house-pattern" class="text-sm font-semibold text-gray-700">House pattern</label>
                        <input id="batch-house-pattern" type="text" wire:model.defer="batchHousePattern" @disabled($draft->status === 'building') class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm disabled:bg-gray-50" />
                        <p class="mt-1 text-xs text-gray-500">Available fields: <code>@{{first}}</code> and <code>@{{last}}</code>.</p>
                        @error('batchHousePattern') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="batch-senate-pattern" class="text-sm font-semibold text-gray-700">Senate pattern</label>
                        <input id="batch-senate-pattern" type="text" wire:model.defer="batchSenatePattern" @disabled($draft->status === 'building') class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm disabled:bg-gray-50" />
                        <p class="mt-1 text-xs text-gray-500">Available fields: <code>@{{first}}</code>, <code>@{{last}}</code>, and <code>@{{senator_last}}</code>.</p>
                        @error('batchSenatePattern') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="batch-instructions" class="text-sm font-semibold text-gray-700">Batch instructions / evidence note</label>
                    <textarea id="batch-instructions" wire:model.defer="batchInstructions" rows="3" @disabled($draft->status === 'building') class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50"></textarea>
                    <p class="mt-1 text-xs text-gray-500">These instructions are saved with each guess for traceability. Address generation follows the explicit patterns above.</p>
                    @error('batchInstructions') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4">
                    <p class="text-xs text-gray-500">Generating guesses resets current recipient approvals when the snapshot refreshes.</p>
                    <button type="submit" wire:loading.attr="disabled" wire:target="generateEmailGuesses" wire:confirm="Generate provisional guesses for up to {{ number_format($emailGuessEstimate['guessable']) }} recipients? Existing recipient approvals will be reset." @disabled($draft->status === 'building' || $emailGuessEstimate['guessable'] === 0) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="generateEmailGuesses">Generate provisional guesses</span>
                        <span wire:loading wire:target="generateEmailGuesses">Starting batch…</span>
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-8" aria-label="Campaign summary">
        @foreach([
            ['Total', $summary['total'], 'text-gray-900'],
            ['Approved', $summary['approved'], 'text-emerald-700'],
            ['Pending', $summary['pending'], 'text-amber-700'],
            ['Excluded', $summary['excluded'], 'text-gray-600'],
            ['Eligible', $summary['eligible'], 'text-emerald-700'],
            ['Provisional', $summary['limited'], 'text-amber-700'],
            ['Unavailable', $summary['unavailable'], 'text-gray-600'],
            ['Suppressed', $summary['suppressed'], 'text-red-700'],
        ] as [$label, $value, $color])
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(22rem,0.85fr)]">
        <section class="app-surface p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Message draft</h2>
                    <p class="mt-1 text-sm text-gray-500">Use <code>[Name]</code> or <code>@{{first_name}}</code> for the first name. Also available: <code>[Full Name]</code>, <code>[Title]</code>, <code>[Office]</code>, <code>@{{name}}</code>, <code>@{{title}}</code>, and <code>@{{office}}</code>.</p>
                    <p class="mt-1 text-xs text-gray-500">Use complete URLs such as <code>https://CongressH3.io</code>. Sent messages include both a plain-text version and a clickable HTML version.</p>
                </div>
                @if($draft->status === 'ready')
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Review ready</span>
                @elseif($draft->status === 'building')
                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">Building</span>
                @elseif($draft->status === 'failed')
                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">Build failed</span>
                @else
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">Draft</span>
                @endif
            </div>

            <form wire:submit="saveMessage" class="mt-4 space-y-4">
                <div>
                    <label for="dry-run-subject" class="text-sm font-semibold text-gray-700">Subject</label>
                    <input id="dry-run-subject" type="text" wire:model.defer="subject" @disabled(!$canManage || !$snapshotReviewable) class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50 disabled:text-gray-700" placeholder="A useful resource for @{{office}}">
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="dry-run-body" class="text-sm font-semibold text-gray-700">Plain-text message</label>
                    <textarea id="dry-run-body" wire:model.defer="bodyText" rows="10" @disabled(!$canManage || !$snapshotReviewable) class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50 disabled:text-gray-700" placeholder="Hi @{{first_name}},&#10;&#10;I wanted to share..."></textarea>
                    @error('bodyText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @if($canManage && $snapshotReviewable)
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" wire:click="refreshPreview" class="rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                            <span wire:loading.remove wire:target="refreshPreview">Refresh preview</span>
                            <span wire:loading wire:target="refreshPreview">Refreshing…</span>
                        </button>
                        <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Save message</button>
                        <button type="button" wire:click="markReady" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Mark review ready</button>
                    </div>
                @endif
            </form>
        </section>

        <section class="app-surface p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Personalization preview</h2>
                    @if($previewUsesUnsavedMessage)
                        <p class="mt-1 text-sm font-medium text-amber-700">Previewing current editor text. These changes are not saved yet.</p>
                    @else
                        <p class="mt-1 text-sm text-gray-500">Preview matches the saved message. Green highlights show inserted personalization.</p>
                    @endif
                </div>
                @if($previewRecipient)
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">{{ $previewRecipient->name }}</p>
                        @if($previewNavigation['count'] > 0)
                            <p class="mt-0.5 text-xs text-gray-500">Approved recipient {{ $previewNavigation['position'] ?? '—' }} of {{ $previewNavigation['count'] }}</p>
                        @endif
                    </div>
                @endif
            </div>
            @if($previewRecipient && $preview)
                @if($previewNavigation['count'] > 1)
                    <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-2">
                        <button type="button" wire:click="showPreview({{ $previewNavigation['previous_id'] ?? 0 }})" @disabled(!$previewNavigation['previous_id']) class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">← Previous</button>
                        <span class="text-xs font-medium text-gray-500">Review all {{ $previewNavigation['count'] }} approved messages</span>
                        <button type="button" wire:click="showPreview({{ $previewNavigation['next_id'] ?? 0 }})" @disabled(!$previewNavigation['next_id']) class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">Next →</button>
                    </div>
                @endif
                <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dl class="space-y-2 text-sm">
                        <div class="grid grid-cols-[4.5rem_1fr] gap-2"><dt class="font-semibold text-gray-500">To</dt><dd class="break-all text-gray-900">{{ $previewRecipient->email ?: 'No address selected' }}</dd></div>
                        <div class="grid grid-cols-[4.5rem_1fr] gap-2"><dt class="font-semibold text-gray-500">Subject</dt><dd class="text-gray-900">{!! $preview['subject_html'] ?: 'Save a subject to preview it' !!}</dd></div>
                    </dl>
                    <div class="mt-4 whitespace-pre-wrap border-t border-gray-200 pt-4 text-sm leading-6 text-gray-800">{!! $preview['body_html'] ?: 'Save a message to preview it.' !!}</div>
                    <div class="mt-4 grid gap-2 border-t border-gray-200 pt-4 text-xs sm:grid-cols-2">
                        <div class="rounded-lg bg-white px-3 py-2 text-gray-700">
                            <span class="font-semibold text-gray-500">[Name] becomes</span>
                            <span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 font-semibold text-emerald-900">{{ $preview['personalization']['first_name'] }}</span>
                            <span class="ml-1 text-gray-400">from {{ $preview['personalization']['name_source'] }}</span>
                        </div>
                        <div class="rounded-lg bg-white px-3 py-2 text-gray-700">
                            <span class="font-semibold text-gray-500">Links in this message</span>
                            @forelse($preview['links'] as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="ml-1 text-indigo-700 underline">{{ $link['display'] }}</a>
                            @empty
                                <span class="ml-1 text-amber-700">No web links detected</span>
                            @endforelse
                        </div>
                    </div>
                    @if($preview['unresolved'] !== [])
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                            <strong>Fix before sending:</strong> unresolved {{ implode(', ', $preview['unresolved']) }}. The send button will refuse to queue this message.
                        </div>
                    @else
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                            Personalization check passed for this recipient.
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">No recipient is available for preview.</div>
            @endif
        </section>
    </div>

    @if($canManage)
        @php
            $nextBatchCount = min($batchSize, $sendingSummary['approved_unsent']);
            $lastCampaign = $sendingSummary['last_campaign'];
        @endphp
        <section class="app-surface overflow-hidden">
            <div class="border-b border-gray-200 p-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Delivery controls</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">Choose the batch size and pace</h2>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500">For example, set 10 messages per batch and repeat every hour. Automation stops when the approved audience is exhausted and can be paused at any time.</p>
                </div>
                <form wire:submit="saveDeliverySettings" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                    <label class="text-sm font-semibold text-gray-700">Messages per batch<input type="number" min="1" max="5000" wire:model.defer="batchSize" @disabled($draft->schedule_status === 'active') class="mt-1 block w-full rounded-lg border-gray-300 disabled:bg-gray-50">@error('batchSize')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-semibold text-gray-700">Delivery mode<select wire:model.live="deliveryMode" @disabled($draft->schedule_status === 'active') class="mt-1 block w-full rounded-lg border-gray-300 disabled:bg-gray-50"><option value="manual">Manual batches</option><option value="scheduled">One scheduled batch</option><option value="recurring">Recurring batches</option></select></label>
                    @if($deliveryMode === 'recurring')
                        <label class="text-sm font-semibold text-gray-700">Repeat every<input type="number" min="1" max="1000" wire:model.defer="cadenceValue" @disabled($draft->schedule_status === 'active') class="mt-1 block w-full rounded-lg border-gray-300 disabled:bg-gray-50"></label>
                        <label class="text-sm font-semibold text-gray-700">Interval<select wire:model.defer="cadenceUnit" @disabled($draft->schedule_status === 'active') class="mt-1 block w-full rounded-lg border-gray-300 disabled:bg-gray-50"><option value="minute">Minutes</option><option value="hour">Hours</option><option value="day">Days</option><option value="week">Weeks</option></select></label>
                    @else
                        <div></div><div></div>
                    @endif
                    <button type="submit" @disabled($draft->schedule_status === 'active') class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">Save delivery settings</button>
                </form>
                @if($deliveryMode !== 'manual')
                    <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                        <label class="text-sm font-semibold text-gray-700">First batch<input type="datetime-local" wire:model.defer="nextSendAt" @disabled($draft->schedule_status === 'active') class="mt-1 block w-full rounded-lg border-gray-300 disabled:bg-gray-50"></label>
                        <label class="text-sm font-semibold text-gray-700">Timezone<input type="text" wire:model.defer="timezone" @disabled($draft->schedule_status === 'active') class="mt-1 block w-full rounded-lg border-gray-300 disabled:bg-gray-50" placeholder="America/New_York"></label>
                        <div class="flex flex-wrap gap-2">
                            @if($draft->schedule_status === 'active')
                                <button type="button" wire:click="pauseSchedule" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100">Pause automation</button>
                            @elseif($draft->schedule_status === 'paused')
                                <button type="button" wire:click="resumeSchedule" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Resume now</button>
                            @else
                                <button type="button" wire:click="activateSchedule" wire:confirm="Activate this campaign's delivery schedule? Approved recipients will begin sending at the selected time." @disabled(!$gmailConnected || !$snapshotReviewable || $sendingSummary['approved_unsent'] === 0) class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-40">Activate delivery</button>
                            @endif
                        </div>
                    </div>
                @endif
                @if($draft->schedule_status === 'active')
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><strong>Automation active.</strong> Next scheduler run: {{ $draft->next_send_at?->setTimezone($draft->timezone)->format('M j, Y g:i A T') }}. It will send up to {{ number_format($draft->batch_size) }} {{ $draft->delivery_mode === 'recurring' ? 'and repeat every '.$draft->cadence_value.' '.Str::plural($draft->cadence_unit, $draft->cadence_value) : 'once' }}.</div>
                @elseif($draft->schedule_status === 'paused')
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"><strong>Automation paused.</strong> No new batches will start until you resume it.</div>
                @endif
            </div>
            <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">Manual delivery</h3>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500">Personalization is rendered when a batch is created. Pending, excluded, previously sent, and centrally suppressed addresses are not included.</p>
                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-700">
                        <span><strong>{{ number_format($sendingSummary['approved_unsent']) }}</strong> approved and unsent</span>
                        <span><strong>{{ number_format($sendingSummary['sent']) }}</strong> sent</span>
                        <span><strong>{{ number_format($sendingSummary['failed']) }}</strong> failed</span>
                        <span><strong>{{ number_format($sendingSummary['suppressed']) }}</strong> suppressed at send time</span>
                    </div>
                    @if($lastCampaign)
                        <p class="mt-2 text-xs text-gray-500">Latest: {{ $lastCampaign->name }} · {{ ucfirst($lastCampaign->status) }} · {{ number_format($lastCampaign->sent_count) }}/{{ number_format($lastCampaign->recipients_count) }} sent</p>
                    @endif
                    @if(!$gmailConnected)
                        <p class="mt-3 text-sm font-semibold text-red-700">Gmail is not connected. <a href="{{ route('admin.integrations') }}" wire:navigate class="underline">Open Admin → Integrations</a>.</p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-col gap-2">
                    <button
                        type="button"
                        wire:click="sendNextBatch"
                        wire:loading.attr="disabled"
                        wire:target="sendNextBatch"
                        wire:confirm="Send a real Gmail message to the next {{ $nextBatchCount }} approved recipients? This cannot be undone."
                        @disabled(!$gmailConnected || !$snapshotReviewable || $nextBatchCount === 0 || $sendingSummary['active'] > 0 || $draft->schedule_status === 'active')
                        class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="sendNextBatch">{{ $sendingSummary['active'] > 0 ? 'Batch in progress…' : "Send next {$nextBatchCount}" }}</span>
                        <span wire:loading wire:target="sendNextBatch">Queuing batch…</span>
                    </button>
                    @if($sendingSummary['retryable'] > 0)
                        <button
                            type="button"
                            wire:click="retryFailedBatch"
                            wire:loading.attr="disabled"
                            wire:target="retryFailedBatch"
                            wire:confirm="Retry {{ $sendingSummary['retryable'] }} failed Gmail deliveries?"
                            @disabled(!$gmailConnected || $sendingSummary['active'] > 0)
                            class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
                        >Retry failed ({{ $sendingSummary['retryable'] }})</button>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="app-surface overflow-hidden">
        <header class="border-b border-gray-200 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Send analytics</p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">Campaign delivery and response dashboard</h2>
                </div>
                <a href="{{ route('congress.outreach.analytics', $draft) }}" wire:navigate class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">View all recipients and analytics →</a>
            </div>
            <p class="mt-1 text-sm text-gray-500">“Gmail accepted” means Gmail accepted the send request; it is not the same as a confirmed delivery. Bounce and reply signals appear after Gmail sync and evidence review.</p>
        </header>
        @if($analytics['campaigns']->isEmpty())
            <div class="p-8 text-center text-sm text-gray-500">No batch has been sent yet. Analytics will appear here after the first confirmed send.</div>
        @else
            @php
                $analyticsStatuses = $analytics['statuses'];
                $analyticsEvents = $analytics['events'];
                $queuedOrSending = ($analyticsStatuses['queued'] ?? 0) + ($analyticsStatuses['sending'] ?? 0);
            @endphp
            <div class="grid gap-3 border-b border-gray-200 p-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                @foreach([
                    ['Queued', $queuedOrSending, 'text-indigo-700'],
                    ['Gmail accepted', $analyticsStatuses['sent'] ?? 0, 'text-emerald-700'],
                    ['Failed', $analyticsStatuses['failed'] ?? 0, 'text-red-700'],
                    ['Suppressed', $analyticsStatuses['suppressed'] ?? 0, 'text-amber-700'],
                    ['Human replies', $analyticsEvents['human_reply'] ?? 0, 'text-emerald-700'],
                    ['Auto-replies', $analyticsEvents['auto_reply'] ?? 0, 'text-indigo-700'],
                    ['Bounce alerts', $analytics['bounce_signals'], 'text-red-700'],
                    ['Opened', $analytics['engagement']['opened'], 'text-indigo-700'],
                ] as [$label, $value, $color])
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                    </div>
                @endforeach
                <div class="rounded-lg bg-gray-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Link clicks</p>
                    @if($analytics['clicks_tracked'])
                        <p class="mt-1 text-2xl font-bold text-indigo-700">{{ number_format($analytics['engagement']['clicked']) }}</p>
                    @else
                        <p class="mt-1 text-xs font-semibold text-gray-500">Begins with the next send</p>
                    @endif
                </div>
            </div>

            <div class="grid divide-y divide-gray-200 xl:grid-cols-2 xl:divide-x xl:divide-y-0">
                <div class="p-5">
                    <h3 class="font-semibold text-gray-900">Batch history</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-wide text-gray-500"><tr><th class="pb-2 pr-3">Batch</th><th class="pb-2 pr-3">Status</th><th class="pb-2 pr-3">Accepted</th><th class="pb-2">Started</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($analytics['campaigns'] as $campaign)
                                    <tr>
                                        <td class="py-2 pr-3 font-medium text-gray-900">{{ $campaign->name }}</td>
                                        <td class="py-2 pr-3">{{ ucfirst($campaign->status) }}</td>
                                        <td class="py-2 pr-3">{{ number_format($campaign->sent_count) }}/{{ number_format($campaign->recipients_count) }}</td>
                                        <td class="py-2 text-gray-500">{{ $campaign->launched_at?->diffForHumans() ?? 'Not started' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="p-5">
                    <h3 class="font-semibold text-gray-900">Recent recipient outcomes</h3>
                    <div class="mt-3 max-h-80 overflow-auto">
                        <div class="divide-y divide-gray-100">
                            @foreach($analytics['recipients'] as $outcome)
                                <div class="flex items-start justify-between gap-4 py-2 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900">{{ $outcome->name }}</p>
                                        <p class="truncate text-xs text-gray-500">{{ $outcome->email }} · {{ $outcome->campaign?->name }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="font-semibold {{ $outcome->status === 'sent' ? 'text-emerald-700' : ($outcome->status === 'failed' ? 'text-red-700' : 'text-gray-700') }}">{{ ucfirst($outcome->status) }}</p>
                                        <p class="text-xs text-gray-500">{{ str_replace('_', ' ', $outcome->congressionalOutreachDraftRecipient?->staffEmail?->verification_status ?? 'unverified') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <section class="app-surface overflow-hidden">
        <header class="border-b border-gray-200 p-5 space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recipient review</h2>
                    <p class="mt-1 text-sm text-gray-500">Bulk approval applies only to sourced, observed, or confirmed addresses. Guesses always require individual approval.</p>
                </div>
                @if($canManage && $snapshotReviewable)
                    <button type="button" wire:click="approveAllEligible" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve all eligible</button>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-4" role="tablist" aria-label="Recipient views">
                @foreach([
                    'pending' => ['Needs review', $summary['pending']],
                    'limited' => ['Provisional', $summary['limited']],
                    'approved' => ['Approved', $summary['approved']],
                    'excluded' => ['Excluded', $summary['excluded']],
                    'all' => ['All', $summary['total']],
                ] as $viewKey => [$viewLabel, $viewCount])
                    <button type="button" wire:click="$set('statusFilter', '{{ $viewKey }}')" class="rounded-full px-3 py-1.5 text-sm font-semibold {{ $statusFilter === $viewKey ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $viewLabel }} <span class="ml-1 opacity-75">{{ number_format($viewCount) }}</span>
                    </button>
                @endforeach
            </div>
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center">
                <input type="search" wire:model.live.debounce.250ms="recipientSearch" placeholder="Search name, address, title, or office" class="rounded-lg border-gray-300 text-sm">
                <button type="button" wire:click="selectVisibleRecipients({{ Illuminate\Support\Js::from($recipients->pluck('id')->all()) }})" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Select this page</button>
                <button type="button" wire:click="selectAllMatchingRecipients" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Select all matching</button>
            </div>
        </header>

        @if(count($selectedRecipientIds) > 0)
            <div class="sticky top-0 z-10 flex flex-col gap-3 border-b border-indigo-200 bg-indigo-50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-indigo-950">{{ number_format(count($selectedRecipientIds)) }} selected</p>
                <div class="flex flex-wrap gap-2">
                    @if($canManage && $snapshotReviewable)
                        <button type="button" wire:click="approveSelectedRecipients" wire:confirm="Approve all selected available recipients, including selected provisional addresses?" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve selected</button>
                        <button type="button" wire:click="excludeSelectedRecipients" wire:confirm="Exclude all selected recipients from this campaign?" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Exclude selected</button>
                    @endif
                    <button type="button" wire:click="clearRecipientSelection" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear</button>
                </div>
            </div>
        @endif

        <div class="divide-y divide-gray-200">
            @forelse($recipients as $recipient)
                @php
                    $tierClasses = match(true) {
                        $recipient->eligibility_tier === 'eligible' => 'bg-emerald-100 text-emerald-800',
                        $recipient->eligibility_tier === 'limited' => 'bg-amber-100 text-amber-800',
                        in_array($recipient->exclusion_reason, ['no_address', 'inactive_profile'], true) => 'bg-gray-100 text-gray-700',
                        default => 'bg-red-100 text-red-800',
                    };
                    $tierLabel = match(true) {
                        $recipient->eligibility_tier === 'eligible' => 'Eligible',
                        $recipient->eligibility_tier === 'limited' => 'Provisional',
                        in_array($recipient->exclusion_reason, ['no_address', 'inactive_profile'], true) => 'Unavailable',
                        $recipient->exclusion_reason === 'blocked_address' => 'Suppressed',
                        default => 'Blocked',
                    };
                    $statusClasses = match($recipient->review_status) {
                        'approved' => 'bg-indigo-100 text-indigo-800',
                        'pending' => 'bg-amber-100 text-amber-800',
                        default => 'bg-gray-100 text-gray-700',
                    };
                    $profileEmails = $recipient->profile?->emails ?? collect();
                @endphp
                <article wire:key="dry-run-recipient-{{ $recipient->id }}" class="p-5 {{ in_array($recipient->id, array_map('intval', $selectedRecipientIds), true) ? 'bg-indigo-50/40' : '' }}">
                    <div class="grid gap-4 xl:grid-cols-[2rem_minmax(13rem,0.9fr)_minmax(19rem,1.2fr)_minmax(12rem,0.8fr)_auto] xl:items-center">
                        <div class="pt-1 xl:pt-0">
                            <input type="checkbox" wire:model.live="selectedRecipientIds" value="{{ $recipient->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" aria-label="Select {{ $recipient->name }}">
                        </div>
                        <div class="min-w-0">
                            <button type="button" wire:click="showPreview({{ $recipient->id }})" class="text-left font-semibold text-gray-900 hover:text-indigo-700">{{ $recipient->name }}</button>
                            <p class="mt-0.5 text-sm text-gray-700">{{ $recipient->title ?: 'No current title' }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-500">{{ $recipient->office ?: 'No current office' }}</p>
                        </div>

                        <div>
                            @if($profileEmails->isNotEmpty())
                                <select wire:change="selectEmail({{ $recipient->id }}, $event.target.value)" @disabled(!$canManage || !$snapshotReviewable) class="block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50 disabled:text-gray-700">
                                    @foreach($profileEmails as $emailOption)
                                        <option value="{{ $emailOption->id }}" @selected($recipient->staff_email_id === $emailOption->id)>{{ $emailOption->email }} · {{ str_replace('_', ' ', $emailOption->verification_status) }}</option>
                                    @endforeach
                                </select>
                            @else
                                <p class="rounded-lg border border-dashed border-gray-300 px-3 py-2 text-sm text-gray-500">No address available</p>
                            @endif
                            <p class="mt-1 text-xs text-gray-500">{{ $recipient->selection_reason }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tierClasses }}">{{ $tierLabel }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ ucfirst($recipient->review_status) }}</span>
                            @if($recipient->exclusion_reason)
                                <span class="w-full text-xs text-gray-500">{{ $reasonLabels[$recipient->exclusion_reason] ?? str_replace('_', ' ', $recipient->exclusion_reason) }}</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap justify-start gap-2 xl:justify-end">
                            <button type="button" wire:click="showPreview({{ $recipient->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Preview</button>
                            @if($canManage && $snapshotReviewable)
                                @if($recipient->review_status === 'pending' && !$recipient->exclusion_reason)
                                    <button type="button" wire:click="approveRecipient({{ $recipient->id }})" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">{{ $recipient->eligibility_tier === 'limited' ? 'Approve provisional' : 'Approve' }}</button>
                                    <button type="button" wire:click="excludeRecipient({{ $recipient->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Exclude</button>
                                @elseif($recipient->review_status === 'approved')
                                    <button type="button" wire:click="excludeRecipient({{ $recipient->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Exclude</button>
                                @elseif($recipient->exclusion_reason === 'manual_exclusion')
                                    <button type="button" wire:click="restoreRecipient({{ $recipient->id }})" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Restore</button>
                                @endif
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center text-sm text-gray-500">{{ $draft->status === 'building' ? 'Recipients will appear here as soon as the snapshot is ready.' : 'No recipients match these filters.' }}</div>
            @endforelse
        </div>

        @if($recipients->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">{{ $recipients->links() }}</div>
        @endif
    </section>
</div>
