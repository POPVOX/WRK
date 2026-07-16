<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <x-congress-nav />

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Congress · Campaigns</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">Campaigns</h1>
            <p class="mt-2 text-gray-600">Draft messages, control batch delivery, collaborate with your team, and follow results.</p>
        </div>
        <a href="{{ route('congress.campaigns.create') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">+ New campaign</a>
    </header>

    <section class="app-surface p-4">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem]">
            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Search campaigns, subjects, or lists" class="rounded-lg border-gray-300 text-sm">
            <select wire:model.live="status" class="rounded-lg border-gray-300 text-sm">
                <option value="all">All statuses</option>
                <option value="draft">Draft or paused</option>
                <option value="active">Active automation</option>
                <option value="completed">Completed</option>
            </select>
        </div>
    </section>

    <section class="app-surface overflow-hidden">
        <div class="hidden grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_8rem_8rem_10rem] gap-4 border-b border-gray-200 bg-gray-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 lg:grid">
            <span>Campaign</span><span>Audience</span><span>Delivery</span><span>Results</span><span class="text-right">Actions</span>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($campaigns as $campaign)
                @php
                    $isOwner = $campaign->user_id === auth()->id();
                    $deliveryLabel = $campaign->schedule_status === 'active'
                        ? $campaign->batch_size.' / '.$campaign->cadence_value.' '.Str::plural($campaign->cadence_unit, $campaign->cadence_value)
                        : ($campaign->schedule_status === 'completed' ? 'Completed' : Str::headline($campaign->schedule_status));
                @endphp
                <article class="grid gap-4 px-5 py-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_8rem_8rem_10rem] lg:items-center">
                    <div class="min-w-0">
                        <a href="{{ route('congress.outreach.show', $campaign) }}" wire:navigate class="font-semibold text-gray-900 hover:text-indigo-700">{{ $campaign->name }}</a>
                        <p class="mt-1 truncate text-sm text-gray-500">{{ $campaign->subject ?: 'No subject drafted yet' }}</p>
                        @unless($isOwner)<p class="mt-1 text-xs font-medium text-indigo-600">Shared by {{ $campaign->user->name }}</p>@endunless
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $campaign->staffList->name }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ number_format($campaign->recipients_count) }} snapshotted · {{ number_format($campaign->approved_recipients_count) }} approved</p>
                    </div>
                    <div><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $campaign->schedule_status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">{{ $deliveryLabel }}</span></div>
                    <div><p class="text-sm font-semibold text-gray-900">{{ number_format($campaign->sent_recipients_count) }} sent</p><p class="mt-1 text-xs text-gray-500">{{ number_format($campaign->failed_recipients_count) }} failed</p></div>
                    <div class="flex flex-wrap justify-end gap-2">
                        @if($campaign->sent_recipients_count > 0)<a href="{{ route('congress.outreach.analytics', $campaign) }}" wire:navigate class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Analytics</a>@endif
                        <a href="{{ route('congress.outreach.show', $campaign) }}" wire:navigate class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">{{ $isOwner ? 'Open' : 'View' }}</a>
                        @if($isOwner)<button type="button" wire:click="duplicateCampaign({{ $campaign->id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Duplicate</button>@endif
                    </div>
                </article>
            @empty
                <div class="px-6 py-16 text-center"><p class="text-sm text-gray-500">No campaigns match this view.</p><a href="{{ route('congress.campaigns.create') }}" wire:navigate class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Create a campaign</a></div>
            @endforelse
        </div>
        @if($campaigns->hasPages())<div class="border-t border-gray-200 px-5 py-4">{{ $campaigns->links() }}</div>@endif
    </section>
</div>
