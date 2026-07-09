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

        foreach ($this->directories as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                if ($this->option('dry-run')) {
                    $this->line("would move: {$path}");
                    $moved++;

                    continue;
                }

                if (! $private->exists($path)) {
                    $private->writeStream($path, $public->readStream($path));
                }

                $public->delete($path);
                $moved++;
                $this->line("moved: {$path}");
            }
        }

        $this->info(($this->option('dry-run') ? 'Would move ' : 'Moved ')."{$moved} file(s).");

        return self::SUCCESS;
    }
}
