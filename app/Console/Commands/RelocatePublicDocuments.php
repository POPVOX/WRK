<?php

namespace App\Console\Commands;

use App\Support\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RelocatePublicDocuments extends Command
{
    protected $signature = 'files:relocate-private {--dry-run : List files without moving them}';

    protected $description = 'Move documents from the web-accessible public disk to the private disk';

    /**
     * Directories that hold documents (not public images/screenshots).
     */
    protected array $directories = [
        'project-documents',
        'project_uploads',
        'grant_documents',
        'attachments/organizations',
        'attachments/people',
        'meetings',
    ];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk(PrivateFiles::DISK);
        $moved = 0;
        $failed = 0;

        foreach ($this->directories as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                if ($this->option('dry-run')) {
                    $this->line("would move: {$path}");
                    $moved++;

                    continue;
                }

                $sourceSize = $public->size($path);

                if ($private->exists($path)) {
                    if ($private->size($path) !== $sourceSize) {
                        $this->error("not moved (destination differs): {$path}");
                        $failed++;

                        continue;
                    }
                } else {
                    $stream = $public->readStream($path);
                    if (! is_resource($stream)) {
                        $this->error("not moved (source could not be read): {$path}");
                        $failed++;

                        continue;
                    }

                    try {
                        $written = $private->writeStream($path, $stream);
                    } finally {
                        fclose($stream);
                    }

                    if (! $written || ! $private->exists($path) || $private->size($path) !== $sourceSize) {
                        $private->delete($path);
                        $this->error("not moved (destination verification failed): {$path}");
                        $failed++;

                        continue;
                    }
                }

                if (! $public->delete($path) || $public->exists($path)) {
                    $this->error("copied but source could not be removed: {$path}");
                    $failed++;

                    continue;
                }

                $moved++;
                $this->line("moved: {$path}");
            }
        }

        $this->info(($this->option('dry-run') ? 'Would move ' : 'Moved ')."{$moved} file(s); {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
