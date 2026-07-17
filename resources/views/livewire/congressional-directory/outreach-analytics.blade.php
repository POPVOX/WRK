<div class="desk-page">
    <x-congress-nav />

    <x-desk-page-header eyebrow="Campaign analytics" :title="$draft->name" :description="'Delivery and engagement across '.number_format($campaigns->count()).' controlled Gmail '.Str::plural('batch', $campaigns->count()).'.'">
        <x-slot:actions><a href="{{ route('congress.outreach.show', $draft) }}" wire:navigate class="desk-button-secondary">← Campaign</a></x-slot:actions>
    </x-desk-page-header>

    <section class="grid border-y-2 border-[#26221c] sm:grid-cols-2 lg:grid-cols-4" aria-label="Campaign funnel">
        @foreach([
            ['Sent', number_format($metrics['sent']), null],
            ['Pixel opens', number_format($metrics['opened']), $metrics['open_rate'].'% estimated open rate'],
            ['Link activity', number_format($metrics['clicked']), $metrics['click_rate'].'% of recipients · unverified'],
            ['Human replies', number_format($metrics['human_replies']), $metrics['reply_rate'].'% reply rate'],
        ] as $index => [$label, $value, $detail])
            <div class="px-5 py-5 {{ $index > 0 ? 'border-t border-[#e4ddd0] sm:border-l sm:border-t-0' : '' }}">
                <p class="desk-section-label {{ $index === 3 ? '!text-[#8a4b2d]' : '' }}">{{ $label }}</p>
                <p class="desk-data mt-2 text-3xl font-medium {{ $index === 3 ? 'text-[#8a4b2d]' : 'text-[#26221c]' }}">{{ $value }}</p>
                @if($detail)<p class="desk-meta mt-1">{{ $detail }}</p>@endif
            </div>
        @endforeach
    </section>

    @if($metrics['auto_replies'] > 0 || $metrics['departure_notices'] > 0)
        <section class="desk-inset flex flex-wrap gap-x-8 gap-y-2 px-5 py-4" aria-label="Automated responses">
            <p class="desk-meta"><span class="font-semibold text-[#26221c]">{{ number_format($metrics['auto_replies']) }}</span> automated {{ Str::plural('response', $metrics['auto_replies']) }}</p>
            <p class="desk-meta"><span class="font-semibold text-[#8a4b2d]">{{ number_format($metrics['departure_notices']) }}</span> departure {{ Str::plural('notice', $metrics['departure_notices']) }}</p>
            <p class="desk-meta">These do not count as human replies.</p>
        </section>
    @endif

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
                    <div><p class="desk-section-label">Recipients</p><p class="desk-meta mt-1">Delivery, classified responses, and tracked link destinations.</p></div>
                    <div class="desk-toolbar"><input type="search" wire:model.live.debounce.250ms="recipientSearch" placeholder="Search name or email" class="!min-h-9 text-sm"><select wire:model.live="outcomeFilter" class="!min-h-9 text-sm"><option value="all">All outcomes</option><option value="opened">Pixel opened</option><option value="clicked">Link activity</option><option value="replied">Human reply</option><option value="auto_reply">Auto-reply</option><option value="departure">Departure notice</option><option value="bounced">Bounced</option><option value="failed">Failed</option></select></div>
                </div>
                <div class="desk-table-wrap">
                    <table class="desk-table">
                        <thead><tr><th>Recipient</th><th>Delivery</th><th>Engagement</th><th>Last activity</th></tr></thead>
                        <tbody>
                            @forelse($recipients as $recipient)
                                @php
                                    $profile = $recipient->congressionalOutreachDraftRecipient?->profile;
                                    $insight = $recipientInsights[$recipient->id];
                                    $lastActivity = $insight['last_activity_at'];
                                @endphp
                                <tr wire:key="analytics-recipient-{{ $recipient->id }}">
                                    <td>@if($profile)<a href="{{ route('congress.staff.show', $profile) }}" wire:navigate class="desk-table-title hover:text-[#8a4b2d]">{{ $recipient->name }}</a>@else<span class="desk-table-title">{{ $recipient->name ?: 'Unknown' }}</span>@endif<p class="desk-meta mt-1">{{ $recipient->email }}</p></td>
                                    <td><span class="font-semibold {{ $recipient->bounced_at || $recipient->status === 'failed' ? 'desk-status-danger' : 'desk-status-positive' }}">{{ $recipient->bounced_at ? 'Bounced' : Str::headline($recipient->status) }}</span></td>
                                    <td class="min-w-[18rem]">
                                        <div class="space-y-2">
                                            @if($insight['response_type'])
                                                <div>
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $insight['response_type'] === 'human_reply' ? 'bg-emerald-100 text-emerald-800' : ($insight['response_type'] === 'departure_auto_reply' ? 'bg-amber-100 text-amber-900' : 'bg-sky-100 text-sky-800') }}">{{ $insight['response_label'] }}</span>
                                                    @if($insight['gmail_url'])
                                                        <a href="{{ $insight['gmail_url'] }}" target="_blank" rel="noopener noreferrer" class="desk-link ml-2 text-xs">Open in Gmail ↗</a>
                                                    @endif
                                                </div>
                                                <details class="text-xs text-[#5c574d]">
                                                    <summary class="cursor-pointer font-semibold text-[#5c574d]">View response</summary>
                                                    @if($insight['response_subject'])<p class="mt-2 font-semibold text-[#26221c]">{{ $insight['response_subject'] }}</p>@endif
                                                    @if($insight['response_snippet'])<p class="mt-1 max-w-xl leading-relaxed">{{ $insight['response_snippet'] }}</p>@endif
                                                </details>
                                            @endif

                                            @if($insight['click_requests'] > 0)
                                                <details class="text-xs text-[#5c574d]">
                                                    <summary class="cursor-pointer">
                                                        <span class="font-semibold text-[#5c574d]">{{ number_format($insight['click_requests']) }} tracked {{ Str::plural('request', $insight['click_requests']) }}</span>
                                                        · {{ number_format($insight['unique_destinations']) }} unique {{ Str::plural('destination', $insight['unique_destinations']) }}
                                                        @if($insight['click_classification'] === 'likely_scanner')
                                                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-900">Likely security scan</span>
                                                        @else
                                                            <span class="ml-1 rounded-full bg-stone-100 px-2 py-0.5 font-semibold text-stone-700">Unverified</span>
                                                        @endif
                                                    </summary>
                                                    <ul class="mt-2 space-y-1 border-l border-[#d8d0c3] pl-3">
                                                        @foreach($insight['clicks'] as $click)
                                                            <li><a href="{{ $click['url'] }}" target="_blank" rel="noopener noreferrer" class="desk-link break-all">{{ $click['url'] }}</a> <span class="text-[#8a8578]">· {{ $click['requests'] }}×</span></li>
                                                        @endforeach
                                                    </ul>
                                                </details>
                                            @elseif(!$insight['response_type'] && $recipient->open_count > 0)
                                                <span>{{ number_format($recipient->open_count) }} pixel {{ Str::plural('open', $recipient->open_count) }}</span>
                                            @elseif(!$insight['response_type'])
                                                <span class="text-[#8a8578]">No engagement recorded</span>
                                            @endif
                                        </div>
                                    </td>
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
                <p class="desk-section-label pb-2">Human engagement</p>
                <div class="desk-hairline">
                    @forelse($mostEngaged as $recipient)
                        <div class="desk-row py-3"><p class="font-semibold text-[#26221c]">{{ $recipient->name ?: $recipient->email }}</p><p class="desk-meta mt-1">{{ $recipient->has_human_reply ? 'Human reply' : 'Pixel opened '.$recipient->open_count.'×' }}</p></div>
                    @empty
                        <p class="desk-empty">Engagement will appear after sends begin.</p>
                    @endforelse
                </div>
            </section>

            <section>
                <p class="desk-section-label pb-2">Tracked destinations</p>
                <div class="desk-hairline">
                    @forelse($topLinks as $link)
                        <div class="desk-row py-3">
                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="desk-link block break-all font-semibold">{{ $link->url }}</a>
                            <p class="desk-meta mt-1">{{ number_format($link->unique_recipients) }} recipients · {{ number_format($link->clicks) }} tracked requests</p>
                        </div>
                    @empty
                        <p class="desk-empty">No link activity recorded.</p>
                    @endforelse
                </div>
            </section>

            <section class="desk-inset p-5">
                <p class="desk-section-label !text-[#8a4b2d]">Suggested next step</p>
                <p class="desk-display mt-3 text-lg font-semibold">{{ number_format($followUpCandidates) }} people opened 3+ times but have not replied.</p>
                <a href="{{ route('congress.lists.create') }}" wire:navigate class="desk-link mt-4 inline-block">Build follow-up list · {{ number_format($followUpCandidates) }} →</a>
            </section>

            <section class="desk-alert p-4 text-xs leading-relaxed text-[#5c574d]">Opens are estimates. Link requests are not treated as confirmed human clicks: congressional email security systems may follow every link automatically. Repeated requests without a tracking-pixel open are labeled as likely security scans.</section>
        </aside>
    </div>
</div>
