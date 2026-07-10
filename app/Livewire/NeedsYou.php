<?php

namespace App\Livewire;

use App\Services\Attention\AttentionItemService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class NeedsYou extends Component
{
    #[Url(except: 'all')]
    public string $filter = 'all';

    public function mount(): void
    {
        if (! in_array($this->filter, ['all', 'tasks', 'meetings', 'funding', 'approvals'], true)) {
            $this->filter = 'all';
        }
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'tasks', 'meetings', 'funding', 'approvals'], true)) {
            $this->filter = $filter;
        }
    }

    public function render(AttentionItemService $attention)
    {
        $allItems = $attention->forUser(Auth::user());
        $items = $this->filter === 'all'
            ? $allItems
            : $allItems->where('category', $this->filter)->values();

        $sections = collect([
            'now' => ['label' => 'Needs attention now', 'description' => 'Overdue, due today, or time-sensitive work.'],
            'review' => ['label' => 'Waiting for review', 'description' => 'Agent work that needs a human decision.'],
            'soon' => ['label' => 'Coming up', 'description' => 'Work worth preparing before it becomes urgent.'],
        ])->map(fn (array $section, string $bucket): array => $section + [
            'items' => $items->where('bucket', $bucket)->values(),
        ])->filter(fn (array $section): bool => $section['items']->isNotEmpty());

        $counts = collect(['all', 'tasks', 'meetings', 'funding', 'approvals'])
            ->mapWithKeys(fn (string $category): array => [
                $category => $category === 'all' ? $allItems->count() : $allItems->where('category', $category)->count(),
            ]);

        return view('livewire.needs-you', [
            'counts' => $counts,
            'sections' => $sections,
            'urgentCount' => $allItems->where('bucket', 'now')->count(),
            'reviewCount' => $allItems->where('bucket', 'review')->count(),
            'comingUpCount' => $allItems->where('bucket', 'soon')->count(),
        ])->title('Needs You');
    }
}
