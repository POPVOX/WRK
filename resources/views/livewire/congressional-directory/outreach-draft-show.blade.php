<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('congress.lists') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Congressional staff lists</a>
            <p class="mt-4 text-sm font-semibold uppercase tracking-[0.14em] text-indigo-600">Outreach workbench</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">{{ $draft->name }}</h1>
            <p class="mt-2 text-gray-600">Review a fixed recipient snapshot and preview personalization before any sending capability is enabled.</p>
        </div>
        @if($canManage)
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="refreshSnapshot" wire:confirm="Refresh from the staff list? This resets all recipient approvals and exclusions." class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Refresh snapshot
                </button>
                <button type="button" wire:click="deleteDraft" wire:confirm="Delete this dry run? The staff list and profiles will not be changed." class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                    Delete dry run
                </button>
            </div>
        @endif
    </div>

    <section class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
        <p class="font-semibold">Dry run only — this workbench cannot send or schedule email.</p>
        <p class="mt-1">“Ready” means the recipient and message review is complete. It does not place anything in the outreach queue.</p>
    </section>

    @if($canManage)
        <section class="app-surface p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Campaign viewers</h2>
                    <p class="mt-1 text-sm text-gray-500">Added team members can inspect this dry run, but cannot edit the message, approve recipients, delete it, or send anything.</p>
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

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7" aria-label="Dry run summary">
        @foreach([
            ['Total', $summary['total'], 'text-gray-900'],
            ['Approved', $summary['approved'], 'text-emerald-700'],
            ['Pending', $summary['pending'], 'text-amber-700'],
            ['Excluded', $summary['excluded'], 'text-gray-600'],
            ['Eligible', $summary['eligible'], 'text-emerald-700'],
            ['Provisional', $summary['limited'], 'text-amber-700'],
            ['Blocked', $summary['blocked'], 'text-red-700'],
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
                    <p class="mt-1 text-sm text-gray-500">Available fields: <code>@{{first_name}}</code>, <code>@{{name}}</code>, <code>@{{title}}</code>, and <code>@{{office}}</code>.</p>
                </div>
                @if($draft->status === 'ready')
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Review ready</span>
                @else
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">Draft</span>
                @endif
            </div>

            <form wire:submit="saveMessage" class="mt-4 space-y-4">
                <div>
                    <label for="dry-run-subject" class="text-sm font-semibold text-gray-700">Subject</label>
                    <input id="dry-run-subject" type="text" wire:model.defer="subject" @disabled(!$canManage) class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50 disabled:text-gray-700" placeholder="A useful resource for @{{office}}">
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="dry-run-body" class="text-sm font-semibold text-gray-700">Plain-text message</label>
                    <textarea id="dry-run-body" wire:model.defer="bodyText" rows="10" @disabled(!$canManage) class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50 disabled:text-gray-700" placeholder="Hi @{{first_name}},&#10;&#10;I wanted to share..."></textarea>
                    @error('bodyText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @if($canManage)
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="submit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Save message</button>
                        <button type="button" wire:click="markReady" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Mark review ready</button>
                    </div>
                @endif
            </form>
        </section>

        <section class="app-surface p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Personalization preview</h2>
                    <p class="mt-1 text-sm text-gray-500">Preview uses the last saved message.</p>
                </div>
                @if($previewRecipient)
                    <span class="text-xs font-semibold text-gray-500">{{ $previewRecipient->name }}</span>
                @endif
            </div>
            @if($previewRecipient && $preview)
                <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <dl class="space-y-2 text-sm">
                        <div class="grid grid-cols-[4.5rem_1fr] gap-2"><dt class="font-semibold text-gray-500">To</dt><dd class="break-all text-gray-900">{{ $previewRecipient->email ?: 'No address selected' }}</dd></div>
                        <div class="grid grid-cols-[4.5rem_1fr] gap-2"><dt class="font-semibold text-gray-500">Subject</dt><dd class="text-gray-900">{{ $preview['subject'] ?: 'Save a subject to preview it' }}</dd></div>
                    </dl>
                    <div class="mt-4 whitespace-pre-wrap border-t border-gray-200 pt-4 text-sm leading-6 text-gray-800">{{ $preview['body'] ?: 'Save a message to preview it.' }}</div>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">No recipient is available for preview.</div>
            @endif
        </section>
    </div>

    <section class="app-surface overflow-hidden">
        <header class="border-b border-gray-200 p-5 space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recipient review</h2>
                    <p class="mt-1 text-sm text-gray-500">Bulk approval applies only to sourced, observed, or confirmed addresses. Guesses always require individual approval.</p>
                </div>
                @if($canManage)
                    <button type="button" wire:click="approveAllEligible" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve all eligible</button>
                @endif
            </div>
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem]">
                <input type="search" wire:model.live.debounce.250ms="recipientSearch" placeholder="Search name, address, title, or office" class="rounded-lg border-gray-300 text-sm">
                <select wire:model.live="statusFilter" class="rounded-lg border-gray-300 text-sm">
                    <option value="all">All recipients</option>
                    <option value="pending">Pending review</option>
                    <option value="approved">Approved</option>
                    <option value="excluded">Excluded</option>
                    <option value="eligible">Eligible addresses</option>
                    <option value="limited">Provisional addresses</option>
                    <option value="blocked">Blocked addresses</option>
                </select>
            </div>
        </header>

        <div class="divide-y divide-gray-200">
            @forelse($recipients as $recipient)
                @php
                    $tierClasses = match($recipient->eligibility_tier) {
                        'eligible' => 'bg-emerald-100 text-emerald-800',
                        'limited' => 'bg-amber-100 text-amber-800',
                        default => 'bg-red-100 text-red-800',
                    };
                    $statusClasses = match($recipient->review_status) {
                        'approved' => 'bg-indigo-100 text-indigo-800',
                        'pending' => 'bg-amber-100 text-amber-800',
                        default => 'bg-gray-100 text-gray-700',
                    };
                    $profileEmails = $recipient->profile?->emails ?? collect();
                @endphp
                <article wire:key="dry-run-recipient-{{ $recipient->id }}" class="p-5">
                    <div class="grid gap-4 xl:grid-cols-[minmax(13rem,0.9fr)_minmax(19rem,1.2fr)_minmax(12rem,0.8fr)_auto] xl:items-center">
                        <div class="min-w-0">
                            <button type="button" wire:click="showPreview({{ $recipient->id }})" class="text-left font-semibold text-gray-900 hover:text-indigo-700">{{ $recipient->name }}</button>
                            <p class="mt-0.5 text-sm text-gray-700">{{ $recipient->title ?: 'No current title' }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-500">{{ $recipient->office ?: 'No current office' }}</p>
                        </div>

                        <div>
                            @if($profileEmails->isNotEmpty())
                                <select wire:change="selectEmail({{ $recipient->id }}, $event.target.value)" @disabled(!$canManage) class="block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50 disabled:text-gray-700">
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
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tierClasses }}">{{ $recipient->eligibility_tier === 'limited' ? 'Provisional' : ucfirst($recipient->eligibility_tier) }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">{{ ucfirst($recipient->review_status) }}</span>
                            @if($recipient->exclusion_reason)
                                <span class="w-full text-xs text-gray-500">{{ $reasonLabels[$recipient->exclusion_reason] ?? str_replace('_', ' ', $recipient->exclusion_reason) }}</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap justify-start gap-2 xl:justify-end">
                            <button type="button" wire:click="showPreview({{ $recipient->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Preview</button>
                            @if($canManage)
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
                <div class="px-6 py-14 text-center text-sm text-gray-500">No recipients match these filters.</div>
            @endforelse
        </div>

        @if($recipients->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">{{ $recipients->links() }}</div>
        @endif
    </section>
</div>
