@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 transition'
        : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-transparent transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
