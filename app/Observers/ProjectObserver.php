<?php

namespace App\Observers;

use App\Jobs\EnsureBoxProjectFolder;
use App\Models\Project;
use App\Services\Box\BoxProjectFolderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectObserver
{
    public function __construct(
        protected BoxProjectFolderService $boxProjectFolderService
    ) {}

    public function created(Project $project): void
    {
        if (! $this->boxProjectFolderService->isConfigured()) {
            return;
        }

        $dispatch = function () use ($project): void {
            try {
                EnsureBoxProjectFolder::dispatch($project->id);
            } catch (\Throwable $exception) {
                Log::warning('Project created without queued Box folder provisioning.', [
                    'project_id' => $project->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        };

        if (app()->runningUnitTests()) {
            $dispatch();

            return;
        }

        DB::afterCommit($dispatch);
    }
}
