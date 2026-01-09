<?php

namespace App\Livewire\Accomplishments;

use App\Jobs\CalculateUserAccomplishments;
use App\Models\Accomplishment;
use App\Models\UserActivityStats;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class MyWinsWidget extends Component
{
    public ?UserActivityStats $weekStats = null;

    public array $recentWins = [];

    public int $recognitionCount = 0;

    public bool $hasError = false;

    public function mount(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        try {
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

            // If no stats, create empty ones (don't run the full calculation synchronously)
            if (! $this->weekStats) {
                // Dispatch job to calculate in background
                CalculateUserAccomplishments::dispatch($user->id, $startOfWeek, $endOfWeek);

                // Create placeholder stats for now
                $this->weekStats = new UserActivityStats([
                    'user_id' => $user->id,
                    'period_start' => $startOfWeek,
                    'period_end' => $endOfWeek,
                    'meetings_attended' => 0,
                    'meetings_organized' => 0,
                    'documents_authored' => 0,
                    'projects_owned' => 0,
                    'projects_contributed' => 0,
                    'recognition_received' => 0,
                    'total_impact_score' => 0,
                ]);
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
        } catch (\Exception $e) {
            Log::error('MyWinsWidget error: '.$e->getMessage());
            $this->hasError = true;
        }
    }

    public function render()
    {
        return view('livewire.accomplishments.my-wins-widget');
    }
}
