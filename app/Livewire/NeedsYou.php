<?php

namespace App\Livewire;

use App\Models\AttentionFeedback;
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

    public string $missingSignal = '';

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

    public function recordFeedback(AttentionItemService $attention, string $itemId, string $response): void
    {
        abort_unless(in_array($response, [
            AttentionFeedback::RESPONSE_USEFUL,
            AttentionFeedback::RESPONSE_NOT_RELEVANT,
        ], true), 422);

        $item = $attention->forUser(Auth::user())->firstWhere('id', $itemId);

        abort_unless($item, 403);

        AttentionFeedback::query()->updateOrCreate(
            [
                'user_id' => Auth::id(),
                'item_key' => $item['id'],
            ],
            [
                'source_type' => $item['source_type'],
                'source_id' => $item['source_id'],
                'category' => $item['category'],
                'response' => $response,
            ],
        );

        $message = $response === AttentionFeedback::RESPONSE_USEFUL
            ? 'Marked as useful. Thank you.'
            : 'Marked as not relevant. We will use this to tune the queue.';

        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function submitMissingSignal(): void
    {
        $validated = $this->validate([
            'missingSignal' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        AttentionFeedback::query()->create([
            'user_id' => Auth::id(),
            'response' => AttentionFeedback::RESPONSE_MISSING,
            'note' => trim($validated['missingSignal']),
        ]);

        $this->reset('missingSignal');
        $this->dispatch('notify', type: 'success', message: 'Missing item recorded. Thank you.');
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

        $feedbackByItem = AttentionFeedback::query()
            ->where('user_id', Auth::id())
            ->whereIn('item_key', $allItems->pluck('id'))
            ->pluck('response', 'item_key');

        return view('livewire.needs-you', [
            'counts' => $counts,
            'sections' => $sections,
            'urgentCount' => $allItems->where('bucket', 'now')->count(),
            'reviewCount' => $allItems->where('bucket', 'review')->count(),
            'comingUpCount' => $allItems->where('bucket', 'soon')->count(),
            'feedbackByItem' => $feedbackByItem,
        ])->title('Needs You');
    }
}
