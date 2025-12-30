{{-- Inquiries Tab --}}
<div>
    {{-- Filters --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <select wire:model.live="inquiryStatus"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="responding">In Progress</option>
                <option value="completed">Completed</option>
            </select>
            <select wire:model.live="inquiryUrgency"
                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">All Urgency</option>
                <option value="breaking">Breaking</option>
                <option value="urgent">Urgent</option>
                <option value="standard">Standard</option>
            </select>
        </div>
        <button wire:click="openInquiryModal" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            + Log Inquiry
        </button>
    </div>

    {{-- Grouped Inquiries --}}
    <div class="space-y-6">
        {{-- Urgent / Needs Attention --}}
        @if($groupedInquiries['urgent']->isNotEmpty())
            <div>
                <h3 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Needs Immediate Attention
                </h3>
                <div class="space-y-3">
                    @foreach($groupedInquiries['urgent'] as $inquiry)
                        @include('livewire.media.partials.inquiry-card', ['inquiry' => $inquiry, 'highlight' => true])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- New --}}
        @if($groupedInquiries['new']->isNotEmpty())
            <div>
                <h3 class="text-sm font-semibold text-blue-600 dark:text-blue-400 mb-3">New Inquiries</h3>
                <div class="space-y-3">
                    @foreach($groupedInquiries['new'] as $inquiry)
                        @include('livewire.media.partials.inquiry-card', ['inquiry' => $inquiry])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- In Progress --}}
        @if($groupedInquiries['responding']->isNotEmpty())
            <div>
                <h3 class="text-sm font-semibold text-amber-600 dark:text-amber-400 mb-3">In Progress</h3>
                <div class="space-y-3">
                    @foreach($groupedInquiries['responding'] as $inquiry)
                        @include('livewire.media.partials.inquiry-card', ['inquiry' => $inquiry])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Recently Completed --}}
        @if($groupedInquiries['completed']->isNotEmpty())
            <div>
                <h3 class="text-sm font-semibold text-green-600 dark:text-green-400 mb-3">Recently Completed</h3>
                <div class="space-y-3">
                    @foreach($groupedInquiries['completed'] as $inquiry)
                        @include('livewire.media.partials.inquiry-card', ['inquiry' => $inquiry])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty State --}}
        @if($inquiries->isEmpty())
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <p class="text-lg font-medium">No inquiries</p>
                <button wire:click="openInquiryModal" class="text-sm text-indigo-600 hover:underline mt-2">
                    Log your first media inquiry
                </button>
            </div>
        @endif
    </div>
</div>