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
        $failed = null;
        $active = null;
        $neverSynced = null;
        $queuedJobs = null;
        $failedJobs = null;
        $latestSuccess = null;
        $problemUsers = [];

        try {
            $connected = DB::table('users')->whereNotNull('google_access_token')->count();
            $stale = DB::table('users')
                ->whereNotNull('google_access_token')
                ->where(function ($query): void {
                    $query->whereNull('calendar_import_date')
                        ->orWhere('calendar_import_date', '<', now()->subMinutes(30));
                })
                ->count();
            $failed = DB::table('users')->whereNotNull('google_access_token')->where('calendar_sync_status', 'failed')->count();
            $active = DB::table('users')->whereNotNull('google_access_token')->whereIn('calendar_sync_status', ['queued', 'running'])->count();
            $neverSynced = DB::table('users')->whereNotNull('google_access_token')->whereNull('calendar_import_date')->count();
            $latestSuccess = DB::table('users')->whereNotNull('google_access_token')->max('calendar_import_date');
            $queuedJobs = DB::table('jobs')->where('payload', 'like', '%SyncCalendarEvents%')->count();
            $failedJobs = DB::table('failed_jobs')->where('payload', 'like', '%SyncCalendarEvents%')->count();
            $problemUsers = DB::table('users')
                ->select(['id', 'name', 'calendar_sync_status', 'calendar_import_date', 'calendar_sync_failed_at'])
                ->whereNotNull('google_access_token')
                ->where(function ($query): void {
                    $query->whereIn('calendar_sync_status', ['failed', 'queued', 'running'])
                        ->orWhereNull('calendar_import_date')
                        ->orWhere('calendar_import_date', '<', now()->subMinutes(30));
                })
                ->orderByRaw("CASE WHEN calendar_sync_status = 'failed' THEN 0 WHEN calendar_sync_status = 'running' THEN 1 WHEN calendar_sync_status = 'queued' THEN 2 ELSE 3 END")
                ->limit(10)
                ->get()
                ->map(fn ($user): array => (array) $user)
                ->all();
        } catch (\Throwable $e) {
            $connected = null;
            $stale = null;
        }

        return [
            'connected' => $connected,
            'stale' => $stale,
            'failed' => $failed,
            'active' => $active,
            'never_synced' => $neverSynced,
            'queued_jobs' => $queuedJobs,
            'failed_jobs' => $failedJobs,
            'latest_success' => $latestSuccess,
            'problem_users' => $problemUsers,
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
