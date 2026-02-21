<?php

namespace App\Observers;

use App\Jobs\EnsureBoxProjectFolder;
use App\Models\Project;
use App\Services\Box\BoxProjectFolderService;

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

        EnsureBoxProjectFolder::dispatch($project->id);
    }
}
