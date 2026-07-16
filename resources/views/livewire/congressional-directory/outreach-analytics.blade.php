<div class="desk-page">
    <x-congress-nav />

    <x-desk-page-header eyebrow="Campaign analytics" :title="$draft->name" :description="'Delivery and engagement across '.number_format($campaigns->count()).' controlled Gmail '.Str::plural('batch', $campaigns->count()).'.'">
        <x-slot:actions><a href="{{ route('congress.outreach.show', $draft) }}" wire:navigate class="desk-button-secondary">← Campaign</a></x-slot:actions>
    </x-desk-page-header>

    <section class="grid border-y-2 border-[#26221c] sm:grid-cols-2 lg:grid-cols-4" aria-label="Campaign funnel">
        @foreach([
            ['Sent', number_format($metrics['sent']), null],
            ['Opened', number_format($metrics['opened']), $metrics['open_rate'].'% open rate · '.$metrics['click_rate'].'% click rate'],
            ['Replied', number_format($metrics['replied']), $metrics['reply_rate'].'%'],
            ['Meetings booked', '—', 'Connect meeting outcomes'],
        ] as $index => [$label, $value, $detail])
            <div class="px-5 py-5 {{ $index > 0 ? 'border-t border-[#e4ddd0] sm:border-l sm:border-t-0' : '' }}">
                <p class="desk-section-label {{ $index === 3 ? '!text-[#8a4b2d]' : '' }}">{{ $label }}</p>
                <p class="desk-data mt-2 text-3xl font-medium {{ $index === 3 ? 'text-[#8a4b2d]' : 'text-[#26221c]' }}">{{ $value }}</p>
                @if($detail)<p class="desk-meta mt-1">{{ $detail }}</p>@endif
            </div>
        @endforeach
    </section>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_21rem]">
        <div class="space-y-8">
            <section>
                <div class="flex items-end justify-between pb-2"><p class="desk-section-label">Last seven days</p><p class="desk-meta">Sent vs. opened</p></div>
                @php
                    $chartMax = max(1, $dailyActivity->max('sent'));
                @endphp
                <div class="desk-rule grid grid-cols-7 gap-3 pt-5">
                    @foreach($dailyActivity as $day)
                        <div class="flex min-w-0 flex-col items-center gap-2">
                            <div class="flex h-32 w-full items-end justify-center gap-1">
                                <span class="w-3 bg-[#26221c]" style="height: {{ max(3, ($day['sent'] / $chartMax) * 100) }}%" title="{{ $day['sent'] }} sent"></span>
                                <span class="w-3 bg-[#c9a183]" style="height: {{ max(3, ($day['opened'] / $chartMax) * 100) }}%" title="{{ $day['opened'] }} opened"></span>
                            </div>
                            <span class="desk-data text-[10px] text-[#8a8578]">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section>
                <div class="flex flex-wrap items-end justify-between gap-3 pb-2">
                    <div><p class="desk-section-label">Recipients</p><p class="desk-meta mt-1">Every recipient with the latest known outcome.</p></div>
                    <div class="desk-toolbar"><input type="search" wire:model.live.debounce.250ms="recipientSearch" placeholder="Search name or email" class="!min-h-9 text-sm"><select wire:model.live="outcomeFilter" class="!min-h-9 text-sm"><option value="all">All outcomes</option><option value="opened">Opened</option><option value="clicked">Clicked</option><option value="replied">Replied</option><option value="bounced">Bounced</option><option value="failed">Failed</option></select></div>
                </div>
                <div class="desk-table-wrap">
                    <table class="desk-table">
                        <thead><tr><th>Recipient</th><th>Delivery</th><th>Engagement</th><th>Last activity</th></tr></thead>
                        <tbody>
                            @forelse($recipients as $recipient)
                                @php
                                    $profile = $recipient->congressionalOutreachDraftRecipient?->profile;
                                    $lastActivity = collect([$recipient->sent_at, $recipient->opened_at, $recipient->clicked_at, $recipient->replied_at, $recipient->bounced_at])->filter()->sortDesc()->first();
                                @endphp
                                <tr wire:key="analytics-recipient-{{ $recipient->id }}">
                                    <td>@if($profile)<a href="{{ route('congress.staff.show', $profile) }}" wire:navigate class="desk-table-title hover:text-[#8a4b2d]">{{ $recipient->name }}</a>@else<span class="desk-table-title">{{ $recipient->name ?: 'Unknown' }}</span>@endif<p class="desk-meta mt-1">{{ $recipient->email }}</p></td>
                                    <td><span class="font-semibold {{ $recipient->bounced_at || $recipient->status === 'failed' ? 'desk-status-danger' : 'desk-status-positive' }}">{{ $recipient->bounced_at ? 'Bounced' : Str::headline($recipient->status) }}</span></td>
                                    <td>{{ $recipient->replied_at ? 'Replied' : ($recipient->clicked_at ? 'Clicked' : ($recipient->opened_at ? 'Opened '.$recipient->open_count.'×' : 'No engagement')) }}</td>
                                    <td class="whitespace-nowrap">{{ $lastActivity?->diffForHumans() ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="!py-10 text-center text-[#8a8578]">No recipients match this view.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recipients->hasPages())<div class="mt-4">{{ $recipients->links() }}</div>@endif
            </section>
        </div>

        <aside class="space-y-7">
            <section>
                <p class="desk-section-label pb-2">Most engaged</p>
                <div class="desk-hairline">
                    @forelse($mostEngaged as $recipient)
                        <div class="desk-row py-3"><p class="font-semibold text-[#26221c]">{{ $recipient->name ?: $recipient->email }}</p><p class="desk-meta mt-1">{{ $recipient->replied_at ? 'Replied' : ($recipient->click_count ? 'Clicked '.$recipient->click_count.'×' : 'Opened '.$recipient->open_count.'×') }}</p></div>
                    @empty
                        <p class="desk-empty">Engagement will appear after sends begin.</p>
                    @endforelse
                </div>
            </section>

            <section class="desk-inset p-5">
                <p class="desk-section-label !text-[#8a4b2d]">Suggested next step</p>
                <p class="desk-display mt-3 text-lg font-semibold">{{ number_format($followUpCandidates) }} people opened 3+ times but have not replied.</p>
                <a href="{{ route('congress.lists.create') }}" wire:navigate class="desk-link mt-4 inline-block">Build follow-up list · {{ number_format($followUpCandidates) }} →</a>
            </section>

            <section class="desk-alert p-4 text-xs leading-relaxed text-[#5c574d]">Opens are estimates: image blocking can undercount them, while privacy tools can inflate them.</section>
        </aside>
    </div>
</div>
