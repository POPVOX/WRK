<?php

namespace App\Console\Commands;

use App\Jobs\EnsureBoxProjectFolder;
use App\Models\Project;
use App\Services\Box\BoxProjectFolderService;
use Illuminate\Console\Command;

class ProvisionBoxProjectFolders extends Command
{
    protected $signature = 'box:provision-project-folders
        {--project-id= : Provision one project by ID}
        {--include-existing : Include projects that already have box_folder_id}
        {--now : Run synchronously instead of queueing}';

    protected $description = 'Provision Box folders for WRK projects, including nested sub-projects.';

    public function handle(BoxProjectFolderService $service): int
    {
        if (! $service->isConfigured()) {
            $this->error('Box project folder provisioning is not configured. Check BOX_ACCESS_TOKEN and BOX_PROJECTS_FOLDER_ID.');

            return self::FAILURE;
        }

        $projectId = $this->option('project-id');
        $includeExisting = (bool) $this->option('include-existing');
        $runNow = (bool) $this->option('now');

        $query = Project::query()
            ->orderByRaw('case when parent_project_id is null then 0 else 1 end')
            ->orderBy('parent_project_id')
            ->orderBy('id');

        if ($projectId !== null) {
            $query->whereKey((int) $projectId);
        } elseif (! $includeExisting) {
            $query->whereNull('box_folder_id');
        }

        $projectIds = $query->pluck('id');
        if ($projectIds->isEmpty()) {
            $this->info('No projects matched the provisioning criteria.');

            return self::SUCCESS;
        }

        if (! $runNow) {
            foreach ($projectIds as $id) {
                EnsureBoxProjectFolder::dispatch((int) $id);
            }

            $this->info("Queued {$projectIds->count()} project folder provisioning job(s).");

            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($projectIds as $id) {
            try {
                EnsureBoxProjectFolder::dispatchSync((int) $id);
                $success++;
            } catch (\Throwable $exception) {
                $failed++;
                $this->warn("Project {$id} failed: {$exception->getMessage()}");
            }
        }

        $this->info("Provisioning complete. Success: {$success}; Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
