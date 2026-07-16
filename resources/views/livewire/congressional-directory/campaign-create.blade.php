<div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <x-congress-nav />

    <header>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Congress · Campaigns</p>
        <h1 class="mt-1 text-3xl font-bold text-gray-900">Create a campaign</h1>
        <p class="mt-2 text-gray-600">Choose a saved audience and establish the message and delivery rules. Nothing sends until you review recipients and activate delivery.</p>
    </header>

    @if($lists->isEmpty())
        <section class="app-surface p-8 text-center"><h2 class="text-lg font-semibold text-gray-900">Create a staff list first</h2><p class="mt-2 text-sm text-gray-500">Campaigns use saved lists so the audience remains understandable and reusable.</p><a href="{{ route('congress.lists.create') }}" wire:navigate class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Create a list</a></section>
    @else
        <form wire:submit="createCampaign" class="space-y-6">
            <section class="app-surface p-5">
                <h2 class="text-lg font-semibold text-gray-900">1. Campaign and audience</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-semibold text-gray-700">Campaign name<input type="text" wire:model.defer="name" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="Congress H3 staff outreach">@error('name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-semibold text-gray-700">Staff list<select wire:model.defer="staffListId" class="mt-1 block w-full rounded-lg border-gray-300"><option value="">Choose a list</option>@foreach($lists as $list)<option value="{{ $list->id }}">{{ $list->name }} ({{ number_format($list->profiles_count) }})</option>@endforeach</select>@error('staffListId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                </div>
            </section>

            <section class="app-surface p-5">
                <h2 class="text-lg font-semibold text-gray-900">2. Draft the message</h2>
                <p class="mt-1 text-sm text-gray-500">You can finish or revise this after the recipient snapshot is built. Use <code class="rounded bg-gray-100 px-1">@{{first_name}}</code>, <code class="rounded bg-gray-100 px-1">@{{name}}</code>, <code class="rounded bg-gray-100 px-1">@{{title}}</code>, or <code class="rounded bg-gray-100 px-1">@{{office}}</code>.</p>
                <label class="mt-4 block text-sm font-semibold text-gray-700">Subject<input type="text" wire:model.defer="subject" class="mt-1 block w-full rounded-lg border-gray-300"></label>
                <label class="mt-4 block text-sm font-semibold text-gray-700">Plain-text message<textarea wire:model.defer="bodyText" rows="10" class="mt-1 block w-full rounded-lg border-gray-300"></textarea></label>
            </section>

            <section class="app-surface p-5">
                <h2 class="text-lg font-semibold text-gray-900">3. Delivery rules</h2>
                <p class="mt-1 text-sm text-gray-500">Set your own batch size. Recurring delivery will send that many at each interval until the approved audience is exhausted or you pause it.</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-semibold text-gray-700">Messages per batch<input type="number" min="1" max="5000" wire:model.defer="batchSize" class="mt-1 block w-full rounded-lg border-gray-300">@error('batchSize')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-semibold text-gray-700">Delivery mode<select wire:model.live="deliveryMode" class="mt-1 block w-full rounded-lg border-gray-300"><option value="manual">Manual — I send each batch</option><option value="scheduled">Scheduled — send one batch at a chosen time</option><option value="recurring">Recurring — send batches automatically</option></select></label>
                </div>
                @if($deliveryMode === 'recurring')
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <label class="text-sm font-semibold text-gray-700">Repeat every<input type="number" min="1" max="1000" wire:model.defer="cadenceValue" class="mt-1 block w-full rounded-lg border-gray-300"></label>
                        <label class="text-sm font-semibold text-gray-700">Interval<select wire:model.defer="cadenceUnit" class="mt-1 block w-full rounded-lg border-gray-300"><option value="minute">Minutes</option><option value="hour">Hours</option><option value="day">Days</option><option value="week">Weeks</option></select></label>
                        <label class="text-sm font-semibold text-gray-700">Timezone<input type="text" wire:model.defer="timezone" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="America/New_York"></label>
                    </div>
                @endif
            </section>

            <div class="flex justify-end gap-3"><a href="{{ route('congress.campaigns') }}" wire:navigate class="rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700">Cancel</a><button type="submit" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Build campaign</button></div>
        </form>
    @endif
</div>
