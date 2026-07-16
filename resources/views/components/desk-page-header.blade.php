@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<header {{ $attributes->class(['desk-page-header']) }}>
    <div class="min-w-0">
        @if($eyebrow)
            <p class="desk-kicker">{{ $eyebrow }}</p>
        @endif
        <h1 class="desk-page-title {{ $eyebrow ? 'mt-2' : '' }}">{{ $title }}</h1>
        @if($description)
            <p class="desk-page-lead">{{ $description }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="desk-page-actions">
            {{ $actions }}
        </div>
    @endif
</header>
