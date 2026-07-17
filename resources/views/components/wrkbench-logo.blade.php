@props([
    'variant' => 'light',
])

@php
    $isDark = $variant === 'dark';
    $wordmarkColor = $isDark ? '#F7F3EC' : '#26221C';
    $legColor = $isDark ? '#C97B4A' : '#8A4B2D';
@endphp

<svg
    {{ $attributes->class('block shrink-0') }}
    viewBox="150 55 340 125"
    role="img"
    aria-label="WRKBench"
    xmlns="http://www.w3.org/2000/svg"
>
    <title>WRKBench</title>
    <text x="160" y="128" font-family="Newsreader, Georgia, serif" font-size="72" font-weight="600" fill="{{ $wordmarkColor }}" letter-spacing="-1">WRK<tspan font-weight="400" font-style="italic">Bench</tspan></text>
    <g transform="translate(161,146)">
        <rect x="0" y="0" width="318" height="5" fill="{{ $wordmarkColor }}" />
        <rect x="22" y="5" width="5" height="17" fill="{{ $legColor }}" />
        <rect x="291" y="5" width="5" height="17" fill="{{ $legColor }}" />
    </g>
</svg>
