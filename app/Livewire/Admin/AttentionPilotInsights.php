<?php

namespace App\Livewire\Admin;

use App\Models\AttentionFeedback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Attention Pilot Insights')]
class AttentionPilotInsights extends Component
{
    #[Url(except: '30')]
    public string $period = '30';

    public function mount(): void
    {
        abort_unless(Auth::user()?->isManagement(), 403);

        if (! in_array($this->period, ['7', '30', 'all'], true)) {
            $this->period = '30';
        }
    }

    public function setPeriod(string $period): void
    {
        if (in_array($period, ['7', '30', 'all'], true)) {
            $this->period = $period;
        }
    }

    protected function feedbackQuery(): Builder
    {
        return AttentionFeedback::query()
            ->when($this->period !== 'all', fn (Builder $query) => $query->where('created_at', '>=', now()->subDays((int) $this->period)));
    }

    public function render()
    {
        $feedback = $this->feedbackQuery()
            ->with('user:id,name,email')
            ->latest()
            ->get();

        $ratings = $feedback->whereIn('response', [
            AttentionFeedback::RESPONSE_USEFUL,
            AttentionFeedback::RESPONSE_NOT_RELEVANT,
        ]);
        $usefulCount = $ratings->where('response', AttentionFeedback::RESPONSE_USEFUL)->count();
        $notRelevantCount = $ratings->where('response', AttentionFeedback::RESPONSE_NOT_RELEVANT)->count();
        $missing = $feedback->where('response', AttentionFeedback::RESPONSE_MISSING)->values();

        $categoryStats = $ratings
            ->groupBy(fn (AttentionFeedback $item) => $item->category ?: 'uncategorized')
            ->map(function ($items, string $category): array {
                $useful = $items->where('response', AttentionFeedback::RESPONSE_USEFUL)->count();
                $total = $items->count();

                return [
                    'category' => $category,
                    'total' => $total,
                    'useful' => $useful,
                    'not_relevant' => $total - $useful,
                    'useful_rate' => $total > 0 ? (int) round(($useful / $total) * 100) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('livewire.admin.attention-pilot-insights', [
            'totalRatings' => $ratings->count(),
            'usefulCount' => $usefulCount,
            'notRelevantCount' => $notRelevantCount,
            'usefulRate' => $ratings->isNotEmpty() ? (int) round(($usefulCount / $ratings->count()) * 100) : 0,
            'participantCount' => $feedback->pluck('user_id')->unique()->count(),
            'missingCount' => $missing->count(),
            'missingSignals' => $missing->take(50),
            'categoryStats' => $categoryStats,
            'recentRatings' => $ratings->take(50),
        ]);
    }
}
