<nav class="flex flex-wrap gap-1 rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm" aria-label="Congress workspace">
    @foreach([
        ['route' => 'congress.index', 'active' => 'congress.index', 'label' => 'Staff Directory'],
        ['route' => 'congress.lists', 'active' => 'congress.lists*', 'label' => 'Lists'],
        ['route' => 'congress.campaigns', 'active' => ['congress.campaigns*', 'congress.outreach*'], 'label' => 'Campaigns'],
        ['route' => 'congress.changes', 'active' => 'congress.changes', 'label' => 'Staff Changes'],
    ] as $item)
        <a href="{{ route($item['route']) }}" wire:navigate class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs(...(array) $item['active']) ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
