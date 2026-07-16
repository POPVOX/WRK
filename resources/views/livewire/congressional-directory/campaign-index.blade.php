<div class="desk-page">
    <x-congress-nav />

    <x-desk-page-header eyebrow="Outreach" title="Campaigns" description="Draft, schedule, monitor, and learn from careful congressional outreach.">
        <x-slot:actions><a href="{{ route('congress.campaigns.create') }}" wire:navigate class="desk-button-primary">＋ New campaign</a></x-slot:actions>
    </x-desk-page-header>

    <section class="grid gap-3 md:grid-cols-[minmax(0,1fr)_13rem]">
        <label class="desk-search"><span class="text-[#8a8578]">⌕</span><input type="search" wire:model.live.debounce.250ms="search" placeholder="Search campaigns, subjects, or lists…" aria-label="Search campaigns"></label>
        <select wire:model.live="status" aria-label="Campaign status"><option value="all">All statuses</option><option value="active">Sending</option><option value="draft">Draft or paused</option><option value="completed">Completed</option></select>
    </section>

    <section class="space-y-3">
        @forelse($campaigns as $campaign)
            @php
                $isOwner = $campaign->user_id === auth()->id();
                $total = max(1, (int) $campaign->recipients_count);
                $progress = min(100, round(($campaign->sent_recipients_count / $total) * 100));
                $state = $campaign->schedule_status === 'active' ? 'sending' : ($campaign->schedule_status === 'completed' ? 'completed' : 'draft');
            @endphp

            @if($state === 'completed')
                <article class="desk-row grid gap-3 py-4 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center" wire:key="campaign-{{ $campaign->id }}">
                    <div><p class="desk-section-label">✓ Completed</p><a href="{{ route('congress.outreach.show', $campaign) }}" wire:navigate class="desk-display mt-1 block text-lg font-semibold hover:text-[#8a4b2d]">{{ $campaign->name }}</a></div>
                    <p class="desk-data text-xs text-[#5c574d]">{{ number_format($campaign->sent_recipients_count) }} sent · {{ number_format($campaign->failed_recipients_count) }} failed</p>
                    <div class="desk-toolbar justify-end">@if($campaign->sent_recipients_count > 0)<a href="{{ route('congress.outreach.analytics', $campaign) }}" wire:navigate class="desk-link">Analytics →</a>@endif @if($isOwner)<button type="button" wire:click="duplicateCampaign({{ $campaign->id }})" class="desk-button-secondary">Duplicate</button>@endif</div>
                </article>
            @else
                <article class="app-surface p-5" wire:key="campaign-{{ $campaign->id }}">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_15rem]">
                        <div class="min-w-0">
                            <p class="desk-section-label {{ $state === 'sending' ? '!text-[#3b7a45]' : '!text-[#8a6d1f]' }}">{{ $state === 'sending' ? '● Sending' : '◐ Draft' }}</p>
                            <a href="{{ route('congress.outreach.show', $campaign) }}" wire:navigate class="desk-display mt-2 block text-2xl font-semibold hover:text-[#8a4b2d]">{{ $campaign->name }}</a>
                            <p class="mt-1 text-sm text-[#5c574d]">{{ $campaign->subject ?: 'Subject and message still need drafting.' }}</p>
                            <p class="desk-meta mt-2">Audience: {{ $campaign->staffList->name }} · {{ number_format($campaign->approved_recipients_count) }} approved of {{ number_format($campaign->recipients_count) }} recipients</p>
                        </div>
                        <div class="flex flex-col items-start justify-between gap-4 lg:items-end">
                            <div class="text-left lg:text-right">
                                <p class="desk-data text-lg font-medium">{{ number_format($campaign->sent_recipients_count) }} / {{ number_format($campaign->recipients_count) }}</p>
                                <p class="desk-meta">{{ $state === 'sending' ? $campaign->batch_size.' every '.$campaign->cadence_value.' '.Str::plural($campaign->cadence_unit, $campaign->cadence_value) : 'Not scheduled' }}</p>
                            </div>
                            <div class="desk-toolbar justify-end">
                                @if($campaign->sent_recipients_count > 0)<a href="{{ route('congress.outreach.analytics', $campaign) }}" wire:navigate class="desk-button-secondary">Analytics</a>@endif
                                <a href="{{ route('congress.outreach.show', $campaign) }}" wire:navigate class="desk-button-primary">{{ $isOwner ? ($state === 'sending' ? 'Manage' : 'Edit') : 'View' }}</a>
                            </div>
                        </div>
                    </div>
                    @if($state === 'sending')
                        <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-[#f0eadd]" aria-label="{{ $progress }} percent sent"><div class="h-full bg-[#8a4b2d]" style="width: {{ $progress }}%"></div></div>
                    @else
                        <p class="mt-4 border-t border-[#e4ddd0] pt-3 text-xs text-[#8a6d1f]">{{ $campaign->approved_recipients_count === 0 ? 'Blocked: approve recipients before scheduling.' : 'Ready for message review and delivery settings.' }}</p>
                    @endif
                </article>
            @endif
        @empty
            <div class="desk-empty">No campaigns match this view. <a href="{{ route('congress.campaigns.create') }}" wire:navigate class="desk-link">＋ Create a campaign</a></div>
        @endforelse
    </section>

    @if($campaigns->hasPages())<div>{{ $campaigns->links() }}</div>@endif

    <footer class="desk-inset flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-xs text-[#5c574d]">
        <span>Sending identity and delivery caps are enforced per campaign.</span>
        <a href="{{ route('congress.contact-data') }}" wire:navigate class="desk-link">Manage congressional contact data →</a>
    </footer>
</div>
