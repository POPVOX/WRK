@php
    $isCurrent = ($variant ?? 'current') === 'current';
    $borderClass = $isCurrent 
        ? 'border-gray-200 dark:border-gray-700' 
        : 'border-purple-200 dark:border-purple-800 border-dashed';
    $gradientClass = $isCurrent 
        ? 'from-green-400 to-green-600' 
        : 'from-purple-400 to-purple-600';
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border {{ $borderClass }} overflow-hidden hover:border-indigo-200 dark:hover:border-indigo-700 transition">
    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                @if($funder->logo_url)
                    <img src="{{ $funder->logo_url }}" alt="{{ $funder->name }}" class="w-10 h-10 rounded-lg object-cover">
                @else
                    <div class="w-10 h-10 bg-gradient-to-br {{ $gradientClass }} rounded-lg flex items-center justify-center text-white font-bold text-sm">
                        {{ strtoupper(substr($funder->name, 0, 2)) }}
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $funder->name }}</h4>
                        <button wire:click="openEditFunderModal({{ $funder->id }})" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $funder->grants_count }} grant{{ $funder->grants_count !== 1 ? 's' : '' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 space-y-2">
        @if($isCurrent)
            @foreach($funder->grants->take(3) as $grant)
                <a href="{{ route('grants.show', $grant) }}" wire:navigate
                   class="block p-2 rounded bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $grant->name }}</span>
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300',
                                'pending' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300',
                                'completed' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300',
                            ];
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded {{ $statusColors[$grant->status] ?? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-300' }}">
                            {{ ucfirst($grant->status) }}
                        </span>
                    </div>
                    @if($grant->amount)
                        <span class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($grant->amount, 0) }}</span>
                    @endif
                </a>
            @endforeach

            @if($funder->grants->count() > 3)
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center pt-1">
                    + {{ $funder->grants->count() - 3 }} more grant{{ $funder->grants->count() - 3 !== 1 ? 's' : '' }}
                </p>
            @endif
        @else
            @if($funder->funder_priorities)
                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $funder->funder_priorities }}</p>
            @endif
        @endif

        <button wire:click="openCreateGrantModal({{ $funder->id }})"
                class="w-full text-center text-sm py-2 {{ $isCurrent ? 'text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300' : 'text-purple-600 dark:text-purple-400 hover:text-purple-800 border border-purple-200 dark:border-purple-700 rounded' }}">
            + Add Grant
        </button>
    </div>
</div>
