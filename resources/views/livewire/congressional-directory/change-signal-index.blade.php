<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <x-congress-nav />
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('congress.index') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← Congress Explorer</a>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">Gmail staff-change review</h1>
            <p class="mt-2 max-w-3xl text-gray-600">
                Confirm departures, failed addresses, and replacement contacts observed in Gmail. Confirmation records evidence only; it does not automatically make an address campaign-eligible.
            </p>
        </div>
        <div>
            <label for="change-status" class="text-sm font-medium text-gray-700">Review status</label>
            <select id="change-status" wire:model.live="status" class="mt-1 block rounded-lg border-gray-300 text-sm">
                <option value="pending">Pending ({{ number_format($counts['pending'] ?? 0) }})</option>
                <option value="accepted">Confirmed ({{ number_format($counts['accepted'] ?? 0) }})</option>
                <option value="rejected">Dismissed ({{ number_format($counts['rejected'] ?? 0) }})</option>
                <option value="">All signals</option>
            </select>
        </div>
    </div>

    @forelse($signals as $signal)
        <article class="app-surface overflow-hidden">
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $signal->signal_type === 'delivery_failure' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ Str::headline($signal->signal_type) }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ Str::headline($signal->status) }}</span>
                    </div>
                    <h2 class="mt-3 font-semibold text-gray-900">{{ $signal->summary }}</h2>
                    <p class="mt-1 text-sm app-muted">
                        {{ $signal->source_email ?: 'Unknown sender' }} · {{ $signal->detected_at?->format('M j, Y g:i A') }}
                    </p>

                    @if($signal->replacement_contacts)
                        <div class="mt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Observed replacement contacts</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($signal->replacement_contacts as $contact)
                                    <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm text-emerald-900">
                                        {{ $contact['display_name'] ?? $contact['email'] }} · {{ $contact['email'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex shrink-0 gap-2">
                    @if($signal->status === 'pending')
                        <button type="button" wire:click="review({{ $signal->id }}, 'rejected')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Dismiss</button>
                        <button type="button" wire:click="review({{ $signal->id }}, 'accepted')" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Confirm evidence</button>
                    @else
                        <button type="button" wire:click="review({{ $signal->id }}, 'pending')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Return to review</button>
                    @endif
                </div>
            </div>
            <details class="border-t border-gray-200 bg-gray-50 px-5 py-3">
                <summary class="cursor-pointer text-sm font-medium text-gray-700">View message evidence</summary>
                <p class="mt-3 whitespace-pre-wrap text-sm text-gray-700">{{ $signal->evidence_excerpt }}</p>
            </details>
        </article>
    @empty
        <div class="app-surface px-6 py-14 text-center">
            <h2 class="font-semibold text-gray-900">No {{ $status ?: '' }} staff-change signals</h2>
            <p class="mt-1 text-sm app-muted">Signals will appear after Gmail sync or the historical scan command runs.</p>
        </div>
    @endforelse

    @if($signals->hasPages())
        <div class="app-surface px-5 py-4">{{ $signals->links() }}</div>
    @endif
</div>
