<?php

namespace App\Console\Commands;

use App\Jobs\SyncBoxFolderMetadata;
use Illuminate\Console\Command;

class SyncBoxMetadata extends Command
{
    protected $signature = 'box:sync-metadata
        {folderId? : Box folder ID to sync. Defaults to BOX_ROOT_FOLDER_ID}
        {--recursive : Also sync subfolders}
        {--now : Run synchronously instead of queueing}';

    protected $description = 'Sync Box folder/file metadata into WRK tables.';

    public function handle(): int
    {
        $folderId = trim((string) ($this->argument('folderId') ?: config('services.box.root_folder_id', '0')));
        if ($folderId === '') {
            $this->error('Folder ID is required (argument or BOX_ROOT_FOLDER_ID).');

            return self::FAILURE;
        }

        $recursive = (bool) $this->option('recursive');

        if ((bool) $this->option('now')) {
            SyncBoxFolderMetadata::dispatchSync($folderId, $recursive, 0);
            $this->info("Box metadata sync finished for folder {$folderId}.");

            return self::SUCCESS;
        }

        SyncBoxFolderMetadata::dispatch($folderId, $recursive, 0);
        $this->info("Queued Box metadata sync for folder {$folderId}.");

        return self::SUCCESS;
    }
}
