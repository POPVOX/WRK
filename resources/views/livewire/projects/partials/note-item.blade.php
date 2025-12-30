@php
    $colors = [
        'update' => 'blue',
        'decision' => 'green',
        'blocker' => 'red',
        'general' => 'gray',
    ];
    $color = $colors[$note->note_type] ?? 'gray';
@endphp

<div class="p-4 bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 rounded-lg border-l-4 border-{{ $color }}-500">
    <div class="flex justify-between items-start">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-medium text-{{ $color }}-700 dark:text-{{ $color }}-300 uppercase">
                    {{ App\Models\ProjectNote::NOTE_TYPES[$note->note_type] }}
                </span>
                @if($note->is_pinned)
                    <span class="text-xs">📌</span>
                @endif
            </div>
            <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ $note->content }}</p>
            <div class="flex items-center gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                @if($note->user)
                    <span>{{ $note->user->name }}</span>
                @endif
                <span>{{ $note->created_at->diffForHumans() }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2 ml-3">
            <button wire:click="togglePinNote({{ $note->id }})"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}">
                <svg class="w-4 h-4" fill="{{ $note->is_pinned ? 'currentColor' : 'none' }}" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </button>
            <button wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?"
                class="text-red-400 hover:text-red-600 dark:hover:text-red-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</div>