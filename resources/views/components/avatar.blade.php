@props([
    'name' => 'Unknown',
    'photo' => null,
    'size' => 'md', // 'xs', 'sm', 'md', 'lg', 'xl'
    'showRing' => true,
])
@php
    // Size classes
    $sizes = [
        'xs' => ['container' => 'w-6 h-6', 'text' => 'text-[10px]', 'ring' => 'ring-1'],
        'sm' => ['container' => 'w-8 h-8', 'text' => 'text-xs', 'ring' => 'ring-1'],
        'md' => ['container' => 'w-10 h-10', 'text' => 'text-sm', 'ring' => 'ring-2'],
        'lg' => ['container' => 'w-12 h-12', 'text' => 'text-base', 'ring' => 'ring-2'],
        'xl' => ['container' => 'w-16 h-16', 'text' => 'text-xl', 'ring' => 'ring-2'],
        '2xl' => ['container' => 'w-24 h-24', 'text' => 'text-3xl', 'ring' => 'ring-4'],
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    // Beautiful gradient combinations - carefully curated pairs
    $gradients = [
        ['from-violet-500', 'to-indigo-600'],      // A-C
        ['from-blue-500', 'to-cyan-500'],          // D-F
        ['from-emerald-500', 'to-teal-600'],       // G-I
        ['from-amber-500', 'to-orange-600'],       // J-L
        ['from-rose-500', 'to-pink-600'],          // M-O
        ['from-fuchsia-500', 'to-purple-600'],     // P-R
        ['from-cyan-500', 'to-blue-600'],          // S-U
        ['from-red-500', 'to-orange-500'],         // V-X
        ['from-teal-500', 'to-green-600'],         // Y-Z
        ['from-indigo-500', 'to-violet-600'],      // fallback
    ];

    // Get initials
    $words = preg_split('/\s+/', trim($name));
    if (count($words) >= 2) {
        $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $initials = strtoupper(substr($name, 0, 2));
    }

    // Consistent color based on name (hash for better distribution)
    $hash = crc32($name);
    $gradientIndex = abs($hash) % count($gradients);
    $gradient = $gradients[$gradientIndex];

    $ringClass = $showRing ? "{$sizeClasses['ring']} ring-white dark:ring-gray-800 ring-offset-1 ring-offset-white dark:ring-offset-gray-900" : '';
@endphp
@if($photo)


       <img src="{{ $photo }}" alt="{{ $name }}"
        {{ $attributes->merge(['class' => "{$sizeClasses['container']} rounded-full object-cover flex-shrink-0 shadow-sm {$ringClass}"]) }}>
@else
    <div 
        {{ $attributes->merge(['class' => "{$sizeClasses['container']} rounded-full bg-gradient-to-br {$gradient[0]} {$gradient[1]} flex items-center justify-center text-white {$sizeClasses['text']} font-bold flex-shrink-0 shadow-md {$ringClass}"]) }}>
        {{ $initials }}
    </div>
@endif
