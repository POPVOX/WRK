<div @if(in_array($operation['status'] ?? null, ['queued', 'running'], true)) wire:poll.3s="refreshOperation" @endif class="desk-page">
    <x-congress-nav />

    <x-desk-page-header eyebrow="Congress · Data quality" title="Contact data" description="Maintain directory-wide email evidence and provisional addresses independently from lists and campaigns." />

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Congressional contact data summary">
        @foreach([
            ['Profiles', $estimate['total'], 'text-gray-900'],
            ['With an address', $estimate['already_addressed'], 'text-emerald-700'],
            ['Missing an address', $estimate['candidates'], 'text-amber-700'],
            ['Ready to generate', $estimate['guessable'], 'text-indigo-700'],
            ['Needs research', $estimate['unresolved'], 'text-gray-600'],
        ] as [$label, $value, $color])
            <div class="app-surface p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm text-indigo-950">
        <p class="font-semibold">This changes contact records, not campaign recipients.</p>
        <p class="mt-1">New guesses become unverified evidence on staff profiles. Existing campaigns keep their fixed audience snapshot until an owner explicitly chooses “Refresh snapshot.” No email is sent from this page.</p>
    </section>

    <section class="app-surface p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Directory enrichment</p>
                <h2 class="mt-1 text-xl font-semibold text-gray-900">Generate provisional email addresses</h2>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">Applies the known institutional conventions to every congressional profile that lacks an address. Every generated address remains provisional until individually verified.</p>
            </div>
            <div class="shrink-0 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700">
                <span class="font-semibold">{{ number_format($estimate['guessable']) }}</span> ready
                <span class="text-gray-400">·</span>
                {{ number_format($estimate['house']) }} House
                <span class="text-gray-400">·</span>
                {{ number_format($estimate['senate']) }} Senate
                @if($correctable > 0)
                    <span class="text-gray-400">·</span>
                    {{ number_format($correctable) }} correctable
                @endif
            </div>
        </div>

        @if(in_array($operation['status'] ?? null, ['queued', 'running'], true))
            <div class="mt-5 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900" role="status">
                <p class="font-semibold">{{ ($operation['status'] ?? null) === 'queued' ? 'Enrichment queued…' : 'Enriching congressional contact data…' }}</p>
                <p class="mt-1">You can safely leave this page. No campaign approvals are changed and no email is sent.</p>
            </div>
        @elseif(($operation['status'] ?? null) === 'completed')
            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                Last run generated <strong>{{ number_format(data_get($operation, 'result.generated', 0)) }}</strong> provisional addresses, corrected {{ number_format(data_get($operation, 'result.corrected', 0)) }}, and left {{ number_format(data_get($operation, 'result.unresolved', 0)) }} profiles for research. No email was sent.
            </div>
        @elseif(($operation['status'] ?? null) === 'failed')
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                {{ $operation['error'] ?? 'The contact-data enrichment could not be completed. Please retry.' }} No email was sent.
            </div>
        @endif

        <form wire:submit="generateEmailGuesses" class="mt-5 space-y-4">
            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label for="directory-house-pattern" class="text-sm font-semibold text-gray-700">House pattern</label>
                    <input id="directory-house-pattern" type="text" wire:model.defer="housePattern" @disabled(in_array($operation['status'] ?? null, ['queued', 'running'], true)) class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm disabled:bg-gray-50" />
                    <p class="mt-1 text-xs text-gray-500">Available fields: <code>@{{first}}</code> and <code>@{{last}}</code>. CBO and other known institutions keep their specialized formulas.</p>
                    @error('housePattern') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="directory-senate-pattern" class="text-sm font-semibold text-gray-700">Senate member-office pattern</label>
                    <input id="directory-senate-pattern" type="text" wire:model.defer="senatePattern" @disabled(in_array($operation['status'] ?? null, ['queued', 'running'], true)) class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm disabled:bg-gray-50" />
                    <p class="mt-1 text-xs text-gray-500">Available fields: <code>@{{first}}</code>, <code>@{{last}}</code>, and <code>@{{senator_last}}</code>. Known committee domains are applied separately.</p>
                    @error('senatePattern') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="directory-enrichment-note" class="text-sm font-semibold text-gray-700">Evidence note</label>
                <textarea id="directory-enrichment-note" wire:model.defer="instructions" rows="3" @disabled(in_array($operation['status'] ?? null, ['queued', 'running'], true)) class="mt-1 block w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-50"></textarea>
                <p class="mt-1 text-xs text-gray-500">Saved with every new or corrected guess for audit history.</p>
                @error('instructions') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4">
                <p class="text-xs text-gray-500">This only fills missing provisional addresses and repairs untouched formula-generated guesses.</p>
                <button type="submit" wire:loading.attr="disabled" wire:target="generateEmailGuesses" wire:confirm="Apply the reviewed formulas across the congressional directory? No email will be sent." @disabled(in_array($operation['status'] ?? null, ['queued', 'running'], true) || ($estimate['guessable'] === 0 && $correctable === 0)) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="generateEmailGuesses">Run directory enrichment</span>
                    <span wire:loading wire:target="generateEmailGuesses">Starting…</span>
                </button>
            </div>
        </form>
    </section>
</div>
