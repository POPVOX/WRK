@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'block w-full rounded-lg px-3 py-2.5 text-sm font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 transition'
        : 'block w-full rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 border border-transparent transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
