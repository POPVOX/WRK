<?php

namespace App\Services\Box;

use App\Models\Project;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class BoxProjectFolderService
{
    public function __construct(
        protected BoxClient $client,
        protected BoxMetadataService $metadataService
    ) {}

    public function isConfigured(): bool
    {
        $projectsFolderId = trim((string) config('services.box.projects_folder_id', config('services.box.root_folder_id', '')));
        $autoProvisionEnabled = (bool) config('services.box.auto_provision_project_folders', true);

        return $autoProvisionEnabled && $this->client->isConfigured() && $projectsFolderId !== '';
    }

    public function ensureFolderForProjectById(int $projectId): ?Project
    {
        $project = Project::query()->find($projectId);
        if (! $project) {
            return null;
        }

        return $this->ensureFolderForProject($project);
    }

    /**
     * @param  array<int,int>  $lineage
     */
    public function ensureFolderForProject(Project $project, array $lineage = []): Project
    {
        $project = Project::query()->findOrFail($project->id);
        if (! empty($project->box_folder_id)) {
            return $project;
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('Box project folder provisioning is not configured.');
        }

        if (in_array($project->id, $lineage, true)) {
            throw new RuntimeException('Detected a circular parent_project_id relationship while provisioning Box folders.');
        }
        $lineage[] = $project->id;

        $parentFolderId = $this->resolveParentFolderId($project, $lineage);
        $folderName = $this->normalizeFolderName((string) $project->name, (int) $project->id);

        Project::query()
            ->whereKey($project->id)
            ->update([
                'box_folder_status' => 'provisioning',
                'box_folder_error' => null,
            ]);

        $folder = $this->createOrReuseFolder($folderName, $parentFolderId);
        $folderId = trim((string) ($folder['id'] ?? ''));
        if ($folderId === '') {
            throw new RuntimeException('Box folder provisioning succeeded without returning a folder id.');
        }

        $this->metadataService->upsertItem($folder, $parentFolderId);

        Project::query()
            ->whereKey($project->id)
            ->update([
                'box_folder_id' => $folderId,
                'box_folder_status' => 'ready',
                'box_folder_error' => null,
                'box_folder_synced_at' => now(),
            ]);

        return Project::query()->findOrFail($project->id);
    }

    /**
     * @param  array<int,int>  $lineage
     */
    protected function resolveParentFolderId(Project $project, array $lineage): string
    {
        if ($project->parent_project_id) {
            $parentProject = Project::query()->find($project->parent_project_id);
            if (! $parentProject) {
                throw new RuntimeException("Parent project {$project->parent_project_id} was not found.");
            }

            $parentProject = $this->ensureFolderForProject($parentProject, $lineage);
            $parentFolderId = trim((string) $parentProject->box_folder_id);
            if ($parentFolderId === '') {
                throw new RuntimeException("Parent project {$parentProject->id} has no Box folder id.");
            }

            return $parentFolderId;
        }

        $projectsFolderId = trim((string) config('services.box.projects_folder_id', config('services.box.root_folder_id', '')));
        if ($projectsFolderId === '') {
            throw new RuntimeException('BOX_PROJECTS_FOLDER_ID (or BOX_ROOT_FOLDER_ID fallback) is required.');
        }

        return $projectsFolderId;
    }

    /**
     * @return array<string,mixed>
     */
    protected function createOrReuseFolder(string $folderName, string $parentFolderId): array
    {
        try {
            return $this->client->createFolder($folderName, $parentFolderId);
        } catch (RequestException $exception) {
            $conflictFolderId = $this->extractConflictFolderId($exception);
            if ($conflictFolderId === null) {
                throw $exception;
            }

            return $this->client->getFolder($conflictFolderId);
        }
    }

    protected function extractConflictFolderId(RequestException $exception): ?string
    {
        $response = $exception->response;
        if (! $response || $response->status() !== 409) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload) || (string) ($payload['code'] ?? '') !== 'item_name_in_use') {
            return null;
        }

        $conflicts = data_get($payload, 'context_info.conflicts');
        if (! is_array($conflicts)) {
            return null;
        }

        if (isset($conflicts['id'])) {
            $type = strtolower((string) ($conflicts['type'] ?? 'folder'));
            if ($type === 'folder') {
                $conflictId = trim((string) $conflicts['id']);

                return $conflictId !== '' ? $conflictId : null;
            }
        }

        foreach ($conflicts as $conflict) {
            if (! is_array($conflict)) {
                continue;
            }

            $type = strtolower((string) ($conflict['type'] ?? 'folder'));
            if ($type !== 'folder') {
                continue;
            }

            $conflictId = trim((string) ($conflict['id'] ?? ''));
            if ($conflictId !== '') {
                return $conflictId;
            }
        }

        return null;
    }

    protected function normalizeFolderName(string $name, int $projectId): string
    {
        $name = preg_replace('/[\/\\\\]+/', '-', trim($name)) ?? '';
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name) ?? '';
        $name = trim($name);

        if ($name === '') {
            $name = "Project {$projectId}";
        }

        return mb_substr($name, 0, 255);
    }
}
