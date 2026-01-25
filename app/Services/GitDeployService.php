<?php

namespace App\Services;

use App\Models\AiFixProposal;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GitDeployService
{
    protected string $projectPath;

    public function __construct()
    {
        $this->projectPath = base_path();
    }

    /**
     * Apply file patches from an AI fix proposal.
     */
    public function applyPatches(AiFixProposal $proposal): bool
    {
        $patches = $proposal->file_patches ?? [];

        if (empty($patches)) {
            Log::warning('No patches to apply', ['proposal_id' => $proposal->id]);
            return false;
        }

        $appliedFiles = [];

        try {
            foreach ($patches as $patch) {
                $file = $patch['file'] ?? null;
                $original = $patch['original'] ?? '';
                $replacement = $patch['replacement'] ?? '';

                if (!$file || !$original) {
                    continue;
                }

                $fullPath = $this->projectPath . '/' . $file;

                if (!File::exists($fullPath)) {
                    Log::warning('File not found for patching', ['file' => $file]);
                    continue;
                }

                $content = File::get($fullPath);

                // Check if original content exists in file
                if (strpos($content, $original) === false) {
                    Log::warning('Original content not found in file', [
                        'file' => $file,
                        'original_snippet' => Str::limit($original, 100),
                    ]);
                    continue;
                }

                // Apply the patch
                $newContent = str_replace($original, $replacement, $content);

                // Only replace the first occurrence
                $newContent = preg_replace(
                    '/' . preg_quote($original, '/') . '/',
                    $replacement,
                    $content,
                    1
                );

                File::put($fullPath, $newContent);
                $appliedFiles[] = $file;

                Log::info('Applied patch to file', ['file' => $file]);
            }

            return count($appliedFiles) > 0;
        } catch (\Exception $e) {
            Log::error('Failed to apply patches', [
                'proposal_id' => $proposal->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Commit and push changes to the repository.
     */
    public function commitAndPush(array $files, string $message, ?string $branch = null): ?string
    {
        try {
            // Add files
            foreach ($files as $file) {
                $this->runGitCommand(['add', $file]);
            }

            // Create commit
            $this->runGitCommand(['commit', '-m', $message]);

            // Get commit SHA
            $sha = trim($this->runGitCommand(['rev-parse', 'HEAD']));

            // Push (optional, might be handled by deploy hook)
            if ($branch) {
                $this->runGitCommand(['push', 'origin', $branch]);
            } else {
                $this->runGitCommand(['push']);
            }

            Log::info('Git commit and push successful', [
                'sha' => $sha,
                'files' => $files,
            ]);

            return $sha;
        } catch (\Exception $e) {
            Log::error('Git operations failed', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a feature branch for safer deploys.
     */
    public function createFeatureBranch(string $name): bool
    {
        try {
            // Sanitize branch name
            $branchName = 'ai-fix/' . Str::slug($name);

            // Create and checkout branch
            $this->runGitCommand(['checkout', '-b', $branchName]);

            Log::info('Created feature branch', ['branch' => $branchName]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create feature branch', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Switch back to main branch.
     */
    public function checkoutMain(): bool
    {
        try {
            $this->runGitCommand(['checkout', 'main']);
            return true;
        } catch (\Exception $e) {
            try {
                $this->runGitCommand(['checkout', 'master']);
                return true;
            } catch (\Exception $e2) {
                Log::error('Failed to checkout main branch', [
                    'error' => $e2->getMessage(),
                ]);
                return false;
            }
        }
    }

    /**
     * Deploy a fix proposal.
     */
    public function deployProposal(AiFixProposal $proposal): array
    {
        $feedback = $proposal->feedback;

        // Generate commit message
        $commitMessage = sprintf(
            "fix: %s\n\nFixes feedback #%d: %s\n\nGenerated by AI Fix System",
            Str::limit($proposal->problem_analysis ?? $feedback->message, 50),
            $feedback->id,
            Str::limit($feedback->message, 100)
        );

        // Apply patches
        $applied = $this->applyPatches($proposal);

        if (!$applied) {
            return [
                'success' => false,
                'error' => 'Failed to apply patches to files',
            ];
        }

        // Commit and push
        $files = $proposal->affected_files ?? [];
        $sha = $this->commitAndPush($files, $commitMessage);

        if (!$sha) {
            return [
                'success' => false,
                'error' => 'Failed to commit and push changes',
            ];
        }

        return [
            'success' => true,
            'commit_sha' => $sha,
            'files_modified' => count($files),
        ];
    }

    /**
     * Run a git command.
     */
    protected function runGitCommand(array $args): string
    {
        $command = 'git ' . implode(' ', array_map('escapeshellarg', $args));

        $output = [];
        $returnCode = 0;

        exec("cd {$this->projectPath} && {$command} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Git command failed: ' . implode("\n", $output));
        }

        return implode("\n", $output);
    }

    /**
     * Check if git is available and repository is clean.
     */
    public function checkStatus(): array
    {
        try {
            $status = $this->runGitCommand(['status', '--porcelain']);
            $branch = trim($this->runGitCommand(['branch', '--show-current']));

            return [
                'available' => true,
                'clean' => empty(trim($status)),
                'branch' => $branch,
                'uncommitted_changes' => !empty(trim($status)),
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
