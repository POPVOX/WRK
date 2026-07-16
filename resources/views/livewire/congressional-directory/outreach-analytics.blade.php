<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('congress.outreach.show', $draft) }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Back to workbench</a>
            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Campaign analytics</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">{{ $draft->name }}</h1>
            <p class="mt-2 text-gray-600">Delivery and engagement across {{ number_format($campaigns->count()) }} controlled Gmail {{ Str::plural('batch', $campaigns->count()) }}.</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 lg:max-w-md">
            Opens are estimates: image blocking can undercount them, while privacy tools and security scanners can inflate opens and clicks.
        </div>
    </header>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6" aria-label="Campaign performance summary">
        @foreach([
            ['Sent', $metrics['sent'], null, 'text-gray-900'],
            ['Unique opens', $metrics['opened'], $metrics['open_rate'].'% open rate', 'text-indigo-700'],
            ['Unique clicks', $metrics['clicked'], $metrics['click_rate'].'% click rate', 'text-violet-700'],
            ['Click-through', $metrics['click_through_rate'].'%', 'of unique opens', 'text-violet-700'],
            ['Replies', $metrics['replied'], $metrics['reply_rate'].'% reply rate', 'text-emerald-700'],
            ['Bounces', $metrics['bounced'], $metrics['bounce_rate'].'% bounce rate', 'text-red-700'],
        ] as [$label, $value, $detail, $color])
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                @if($detail)<p class="mt-1 text-xs text-gray-500">{{ $detail }}</p>@endif
            </div>
        @endforeach
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="app-surface overflow-hidden">
            <header class="space-y-4 border-b border-gray-200 p-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recipients</h2>
                    <p class="mt-1 text-sm text-gray-500">Every recipient across every batch, with the latest known outcome.</p>
                </div>
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem]">
                    <input type="search" wire:model.live.debounce.250ms="recipientSearch" class="rounded-lg border-gray-300 text-sm" placeholder="Search name or email">
                    <select wire:model.live="outcomeFilter" class="rounded-lg border-gray-300 text-sm">
                        <option value="all">All recipients</option>
                        <option value="sent">Sent</option>
                        <option value="opened">Opened</option>
                        <option value="clicked">Clicked</option>
                        <option value="replied">Replied</option>
                        <option value="bounced">Bounced</option>
                        <option value="unsubscribed">Unsubscribed</option>
                        <option value="failed">Failed</option>
                        <option value="suppressed">Suppressed</option>
                        <option value="untracked">Sent before tracking</option>
                    </select>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr><th class="px-5 py-3">Recipient</th><th class="px-5 py-3">Batch</th><th class="px-5 py-3">Delivery</th><th class="px-5 py-3">Engagement</th><th class="px-5 py-3">Last activity</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($recipients as $recipient)
                            @php
                                $profile = $recipient->congressionalOutreachDraftRecipient?->profile;
                                $lastActivity = collect([$recipient->sent_at, $recipient->opened_at, $recipient->clicked_at, $recipient->replied_at, $recipient->bounced_at, $recipient->unsubscribed_at])->filter()->sortDesc()->first();
                            @endphp
                            <tr wire:key="analytics-recipient-{{ $recipient->id }}">
                                <td class="px-5 py-4">
                                    @if($profile)<a href="{{ route('congress.staff.show', $profile) }}" wire:navigate class="font-semibold text-gray-900 hover:text-indigo-700">{{ $recipient->name }}</a>@else<span class="font-semibold text-gray-900">{{ $recipient->name ?: 'Unknown' }}</span>@endif
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $recipient->email }}</p>
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ $recipient->campaign?->name }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $recipient->status === 'sent' ? 'bg-emerald-100 text-emerald-800' : ($recipient->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700') }}">{{ Str::headline($recipient->status) }}</span>
                                    @if($recipient->bounced_at)<span class="ml-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">Bounced</span>@endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @if(!$recipient->tracking_token && $recipient->sent_at)<span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">Pre-tracking</span>@endif
                                        @if($recipient->opened_at)<span class="rounded bg-indigo-50 px-2 py-1 text-xs text-indigo-700">Opened {{ number_format($recipient->open_count) }}×</span>@endif
                                        @if($recipient->clicked_at)<span class="rounded bg-violet-50 px-2 py-1 text-xs text-violet-700">Clicked {{ number_format($recipient->click_count) }}×</span>@endif
                                        @if($recipient->replied_at)<span class="rounded bg-emerald-50 px-2 py-1 text-xs text-emerald-700">Replied</span>@endif
                                        @if($recipient->unsubscribed_at)<span class="rounded bg-amber-50 px-2 py-1 text-xs text-amber-700">Unsubscribed</span>@endif
                                        @if(!$recipient->opened_at && !$recipient->clicked_at && !$recipient->replied_at)<span class="text-xs text-gray-400">No engagement recorded</span>@endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $lastActivity?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">No recipients match this view.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recipients->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $recipients->links() }}</div>@endif
        </section>

        <aside class="space-y-6">
            <section class="app-surface p-5">
                <h2 class="font-semibold text-gray-900">Delivery health</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">All recipients</dt><dd class="font-semibold">{{ number_format($metrics['total']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Failed</dt><dd class="font-semibold text-red-700">{{ number_format($metrics['failed']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Suppressed</dt><dd class="font-semibold text-amber-700">{{ number_format($metrics['suppressed']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Unsubscribed</dt><dd class="font-semibold text-amber-700">{{ number_format($metrics['unsubscribed']) }}</dd></div>
                </dl>
            </section>

            <section class="app-surface p-5">
                <h2 class="font-semibold text-gray-900">Top clicked links</h2>
                <div class="mt-4 space-y-3">
                    @forelse($topLinks as $link)
                        <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="line-clamp-2 break-all text-sm font-medium text-indigo-700 hover:underline">{{ $link->url }}</a>
                            <p class="mt-1 text-xs text-gray-500">{{ number_format($link->unique_recipients) }} unique · {{ number_format($link->clicks) }} total clicks</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No link clicks recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
