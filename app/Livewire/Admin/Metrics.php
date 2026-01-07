<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin Metrics')]
class Metrics extends Component
{
    public array $ai = [];

    public array $queues = [];

    public array $calendar = [];

    public array $documents = [];

    public function mount(): void
    {
        $this->ai = $this->loadAiMetrics();
        $this->queues = $this->loadQueueMetrics();
        $this->calendar = $this->loadCalendarMetrics();
        $this->documents = $this->loadDocumentMetrics();
    }

    protected function loadAiMetrics(): array
    {
        $success = (int) Cache::get('metrics:ai:success', 0);
        $error = (int) Cache::get('metrics:ai:error', 0);
        $count = (int) Cache::get('metrics:ai:count', 0);
        $totalLatency = (int) Cache::get('metrics:ai:latency_ms_total', 0);

        return [
            'success' => $success,
            'error' => $error,
            'count' => $count,
            'last_error_at' => Cache::get('metrics:ai:last_error_at'),
            'last_error_status' => Cache::get('metrics:ai:last_error_status'),
            'avg_latency_ms' => $count > 0 ? round($totalLatency / $count) : null,
        ];
    }

    protected function loadQueueMetrics(): array
    {
        $queueDepth = null;
        $failedCount = null;

        try {
            $queueDepth = DB::table('jobs')->count();
        } catch (\Throwable $e) {
            $queueDepth = null;
        }

        try {
            $failedCount = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            $failedCount = null;
        }

        return [
            'depth' => $queueDepth,
            'failed' => $failedCount,
        ];
    }

    protected function loadCalendarMetrics(): array
    {
        $connected = null;
        $stale = null;

        try {
            $connected = DB::table('users')->whereNotNull('google_refresh_token')->count();
            $stale = DB::table('users')
                ->whereNotNull('calendar_import_date')
                ->where('calendar_import_date', '<', now()->subDays(7))
                ->count();
        } catch (\Throwable $e) {
            $connected = null;
            $stale = null;
        }

        return [
            'connected' => $connected,
            'stale' => $stale,
        ];
    }

    protected function loadDocumentMetrics(): array
    {
        $failedIngestion = null;

        try {
            $failedIngestion = DB::table('failed_jobs')
                ->where(function ($q) {
                    $q->where('payload', 'like', '%FetchLinkContent%')
                        ->orWhere('payload', 'like', '%IndexDocumentContent%');
                })
                ->count();
        } catch (\Throwable $e) {
            $failedIngestion = null;
        }

        return [
            'failed_ingestion' => $failedIngestion,
        ];
    }

    public function render()
    {
        return view('livewire.admin.metrics');
    }
}
