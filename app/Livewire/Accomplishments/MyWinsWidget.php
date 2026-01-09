<?php

namespace App\Livewire\Accomplishments;

use App\Jobs\CalculateUserAccomplishments;
use App\Models\Accomplishment;
use App\Models\UserActivityStats;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MyWinsWidget extends Component
{
    public ?UserActivityStats $weekStats = null;

    public array $recentWins = [];

    public int $recognitionCount = 0;

    public function mount(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Get or calculate this week's stats
        $startOfWeek = now()->startOfWeek()->toDateString();
        $endOfWeek = now()->endOfWeek()->toDateString();

        $this->weekStats = UserActivityStats::where('user_id', $user->id)
            ->where('period_start', $startOfWeek)
            ->where('period_end', $endOfWeek)
            ->first();

        // If no stats or stale, calculate in background
        if (! $this->weekStats || ! $this->weekStats->last_calculated_at || $this->weekStats->last_calculated_at->diffInHours(now()) > 1) {
            $job = new CalculateUserAccomplishments($user->id, $startOfWeek, $endOfWeek);
            $this->weekStats = $job->handle();
        }

        // Get recent accomplishments (last 14 days)
        $this->recentWins = Accomplishment::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(14))
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'type' => $a->type,
                'type_emoji' => $a->type_emoji,
                'date' => $a->date->diffForHumans(),
                'is_recognition' => $a->is_recognition,
            ])
            ->toArray();

        // Get recognition received this week
        $this->recognitionCount = Accomplishment::where('user_id', $user->id)
            ->where('is_recognition', true)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->count();
    }

    public function render()
    {
        return view('livewire.accomplishments.my-wins-widget');
    }
}
