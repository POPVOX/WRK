@php
    $typeConfig = match ($result['source_type'] ?? 'item') {
        'meeting' => ['icon' => 'calendar', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/30', 'label' => 'Meeting'],
        'document' => ['icon' => 'document-text', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/30', 'label' => 'Document'],
        'decision' => ['icon' => 'flag', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/30', 'label' => 'Decision'],
        'note' => ['icon' => 'pencil-square', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50 dark:bg-gray-700', 'label' => 'Note'],
        'person' => ['icon' => 'user', 'color' => 'text-green-500', 'bg' => 'bg-green-50 dark:bg-green-900/30', 'label' => 'Person'],
        'organization' => ['icon' => 'building-office', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50 dark:bg-amber-900/30', 'label' => 'Organization'],
        'commitment' => ['icon' => 'check-circle', 'color' => 'text-rose-500', 'bg' => 'bg-rose-50 dark:bg-rose-900/30', 'label' => 'Commitment'],
        'project' => ['icon' => 'folder', 'color' => 'text-teal-500', 'bg' => 'bg-teal-50 dark:bg-teal-900/30', 'label' => 'Project'],
        default => ['icon' => 'document', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50 dark:bg-gray-700', 'label' => 'Item'],
    };

    $iconPaths = [
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'document-text' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'flag' => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
        'pencil-square' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'building-office' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'check-circle' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'folder' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
        'document' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    ];
@endphp

<a href="{{ $result['url'] ?? '#' }}"
    class="block p-3 rounded-lg border border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-700 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/20 transition">
    <div class="flex items-start gap-3">
        {{-- Type Icon --}}
        <div class="flex-shrink-0 p-2 rounded-lg {{ $typeConfig['bg'] }}">
            <svg class="w-4 h-4 {{ $typeConfig['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="{{ $iconPaths[$typeConfig['icon']] ?? $iconPaths['document'] }}" />
            </svg>
        </div>

        <div class="flex-1 min-w-0">
            {{-- Title --}}
            <div class="flex items-center gap-2">
                <span class="font-medium text-gray-900 dark:text-white truncate">{{ $result['title'] }}</span>
                <span class="text-xs px-1.5 py-0.5 rounded {{ $typeConfig['bg'] }} {{ $typeConfig['color'] }}">
                    {{ $typeConfig['label'] }}
                </span>
            </div>

            {{-- Snippet (if not compact) --}}
            @if(empty($compact) && isset($result['snippet']) && $result['snippet'])
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ $result['snippet'] }}</p>
            @endif

            {{-- Metadata --}}
            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                @if(isset($result['date']) && $result['date'])
                    <span>{{ \Carbon\Carbon::parse($result['date'])->format('M j, Y') }}</span>
                @endif
                @if(isset($result['project']) && $result['project'])
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <span>{{ $result['project'] }}</span>
                @endif
                @if(isset($result['organization']) && $result['organization'])
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <span>{{ $result['organization'] }}</span>
                @endif
            </div>
        </div>
    </div>
</a>