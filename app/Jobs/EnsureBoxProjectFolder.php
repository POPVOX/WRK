<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\Box\BoxProjectFolderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnsureBoxProjectFolder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;

    public int $tries = 3;

    public $timeout = 120;

    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
    }

    public function handle(BoxProjectFolderService $service): void
    {
        $project = Project::query()->find($this->projectId);
        if (! $project) {
            return;
        }

        if (! $service->isConfigured()) {
            return;
        }

        try {
            $service->ensureFolderForProject($project);
        } catch (\Throwable $exception) {
            Project::query()
                ->whereKey($this->projectId)
                ->update([
                    'box_folder_status' => 'failed',
                    'box_folder_error' => Str::limit($exception->getMessage(), 2000),
                ]);

            Log::warning('Box project folder provisioning failed', [
                'project_id' => $this->projectId,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
