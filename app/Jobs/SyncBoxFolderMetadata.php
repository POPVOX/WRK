<?php

namespace App\Jobs;

use App\Services\Box\BoxMetadataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBoxFolderMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $folderId;

    public bool $recursive;

    public int $depth;

    public $timeout = 120;

    public function __construct(string $folderId, bool $recursive = false, int $depth = 0)
    {
        $this->folderId = $folderId;
        $this->recursive = $recursive;
        $this->depth = $depth;
    }

    public function handle(BoxMetadataService $metadataService): void
    {
        $offset = 0;
        $limit = max(1, (int) config('services.box.sync_page_size', 100));
        $maxDepth = max(0, (int) config('services.box.sync_max_depth', 6));
        $queuedFolders = [];

        do {
            $result = $metadataService->syncFolderPage($this->folderId, $offset, $limit);
            $entriesCount = (int) ($result['entries'] ?? 0);
            $total = (int) ($result['total'] ?? 0);
            $offset += $entriesCount;

            Log::info('Box sync: folder page synced', [
                'folder_id' => $this->folderId,
                'depth' => $this->depth,
                'entries' => $entriesCount,
                'upserted' => (int) ($result['upserted'] ?? 0),
                'offset' => $offset,
                'total' => $total,
            ]);

            if ($this->recursive && $this->depth < $maxDepth) {
                foreach ($result['subfolders'] ?? [] as $subfolderId) {
                    if (isset($queuedFolders[$subfolderId])) {
                        continue;
                    }

                    $queuedFolders[$subfolderId] = true;
                    self::dispatch($subfolderId, true, $this->depth + 1);
                }
            }

            if ($entriesCount === 0 || $offset >= $total) {
                break;
            }
        } while (true);
    }
}
