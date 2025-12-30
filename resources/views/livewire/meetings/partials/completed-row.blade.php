<div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4 flex-1 min-w-0">
            {{-- Org Avatar --}}
            <div class="flex-shrink-0">
                @if($meeting->organizations->first())
                    @php
                        $org = $meeting->organizations->first();
                        $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'];
                        $colorIndex = abs(crc32($org->name ?? 'X')) % count($colors);
                    @endphp
                    <div
                        class="w-10 h-10 rounded-lg {{ $colors[$colorIndex] }} flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr($org->name ?? 'O', 0, 2)) }}
                    </div>
                @else
                    <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Meeting Info --}}
            <div class="flex-1 min-w-0">
                <a href="{{ route('meetings.show', $meeting) }}" wire:navigate class="block">
                    <h4
                        class="font-medium text-gray-900 dark:text-white truncate hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        {{ $meeting->title ?: 'Untitled Meeting' }}
                    </h4>
                </a>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    @if($meeting->people->count() > 0)
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ $meeting->people->count() }}
                        </span>
                    @endif
                    @if($meeting->organizations->first())
                        <span class="text-gray-300 dark:text-gray-600">•</span>
                        <span>{{ $meeting->organizations->first()->name }}</span>
                    @endif
                    @if($meeting->issues->count() > 0)
                        <span class="text-gray-300 dark:text-gray-600">•</span>
                        @foreach($meeting->issues->take(2) as $issue)
                            <span
                                class="px-1.5 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                                {{ $issue->name }}
                            </span>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Date --}}
        <div class="flex-shrink-0 text-sm text-gray-500 dark:text-gray-400 mx-4">
            {{ $meeting->meeting_date->format('M j') }}
        </div>

        {{-- Has Notes Indicator + Actions --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            <span
                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 rounded-full">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
                Notes
            </span>

            {{-- Edit/Delete dropdown --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-20">
                    <a href="{{ route('meetings.edit', $meeting) }}" wire:navigate
                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </a>
                    <button wire:click="deleteMeeting({{ $meeting->id }})"
                        wire:confirm="Are you sure you want to delete this meeting? This action cannot be undone."
                        class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 w-full text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Notes Preview --}}
    @if($meeting->notes_preview)
        <div class="mt-2 ml-14 text-sm text-gray-600 dark:text-gray-400 italic">
            "{{ $meeting->notes_preview }}"
        </div>
    @endif
</div>