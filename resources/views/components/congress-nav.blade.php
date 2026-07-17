<x-desk-tabs>
    @foreach([
        ['route' => 'congress.index', 'active' => 'congress.index', 'label' => 'Directory'],
        ['route' => 'congress.contact-data', 'active' => 'congress.contact-data', 'label' => 'Contact data'],
        ['route' => 'congress.changes', 'active' => 'congress.changes', 'label' => 'Staff changes'],
        ['route' => 'congress.lists', 'active' => 'congress.lists*', 'label' => 'Lists'],
        ['route' => 'congress.campaigns', 'active' => ['congress.campaigns*', 'congress.outreach*'], 'label' => 'Campaigns'],
    ] as $item)
        <a href="{{ route($item['route']) }}" wire:navigate aria-current="{{ request()->routeIs(...(array) $item['active']) ? 'page' : 'false' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</x-desk-tabs>
