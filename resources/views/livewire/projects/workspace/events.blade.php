{{-- Events Tab --}}
<div class="space-y-6">
    {{-- Header with Add Button --}}
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project Events</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Staff events, demos, launches, and workshops</p>
        </div>
        <button wire:click="openEventModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Event
        </button>
    </div>

    {{-- Status Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach(\App\Models\ProjectEvent::STATUSES as $status => $label)
            @php
                $count = $project->events->where('status', $status)->count();
                $colors = \App\Models\ProjectEvent::STATUS_COLORS[$status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $count }}</div>
                <div class="text-xs mt-1 {{ $colors }} px-2 py-0.5 rounded-full inline-block">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    {{-- Events Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($project->events->sortBy('event_date') as $event)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Event Header --}}
                <div class="p-5 {{ $event->status === 'completed' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-purple-50 dark:bg-purple-900/20' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">
                                    @switch($event->type)
                                        @case('staff_event') 👥 @break
                                        @case('demo') 🔧 @break
                                        @case('launch') 🚀 @break
                                        @case('briefing') 📋 @break
                                        @case('workshop') 🎓 @break
                                        @default 🎪
                                    @endswitch
                                </span>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $event->title }}</h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ \App\Models\ProjectEvent::TYPES[$event->type] ?? $event->type }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <select wire:change="updateEventStatus({{ $event->id }}, $event.target.value)"
                            class="text-xs rounded-full px-3 py-1 border-0 {{ \App\Models\ProjectEvent::STATUS_COLORS[$event->status] ?? 'bg-gray-100 text-gray-700' }}">
                            @foreach(\App\Models\ProjectEvent::STATUSES as $status => $label)
                                <option value="{{ $status }}" {{ $event->status === $status ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Event Details --}}
                <div class="p-5 space-y-3">
                    @if($event->event_date)
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $event->event_date->format('l, F j, Y \a\t g:i A') }}
                            </span>
                        </div>
                    @endif

                    @if($event->location)
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">{{ $event->location }}</span>
                        </div>
                    @endif

                    @if($event->target_attendees || $event->actual_attendees)
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">
                                @if($event->actual_attendees)
                                    {{ $event->actual_attendees }} attended
                                    @if($event->target_attendees)
                                        (target: {{ $event->target_attendees }})
                                    @endif
                                @elseif($event->target_attendees)
                                    Target: {{ $event->target_attendees }} attendees
                                @endif
                            </span>
                        </div>
                    @endif

                    @if($event->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                            {{ $event->description }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-2 bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="text-4xl mb-4">🎪</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">No events yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Click "Add Event" to create your first event.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Add Event Modal --}}
@if($showEventModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEventModal"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form wire:submit="createEvent" class="space-y-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add New Event</h3>
                        <button type="button" wire:click="closeEventModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Title *</label>
                        <input type="text" wire:model="newEventTitle"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Q1 Staff Event">
                        @error('newEventTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Type</label>
                            <select wire:model="newEventType"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach(\App\Models\ProjectEvent::TYPES as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date & Time</label>
                            <input type="datetime-local" wire:model="newEventDate"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                            <input type="text" wire:model="newEventLocation"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Capitol Hill / Virtual">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Attendees</label>
                            <input type="number" wire:model="newEventTargetAttendees" min="1"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="25">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="newEventDescription" rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Brief description of the event..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="closeEventModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                            Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
