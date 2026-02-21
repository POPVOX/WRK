<?php

namespace App\Services\Box;

use App\Models\BoxItem;
use App\Models\BoxProjectDocumentLink;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BoxProjectDocumentService
{
    public function linkItemToProject(
        BoxItem $boxItem,
        Project $project,
        ?int $createdByUserId = null,
        string $visibility = 'all'
    ): BoxProjectDocumentLink {
        return BoxProjectDocumentLink::updateOrCreate(
            [
                'box_item_id' => $boxItem->id,
                'project_id' => $project->id,
            ],
            [
                'created_by' => $createdByUserId,
                'visibility' => $this->normalizeVisibility($visibility),
                'sync_status' => 'pending',
                'last_error' => null,
            ]
        );
    }

    /**
     * @param  Builder<BoxProjectDocumentLink>|null  $query
     * @return array{synced:int,failed:int}
     */
    public function syncLinks(?Builder $query = null): array
    {
        $query ??= BoxProjectDocumentLink::query();

        $synced = 0;
        $failed = 0;

        $query->with(['boxItem', 'project'])->chunkById(100, function ($links) use (&$synced, &$failed) {
            foreach ($links as $link) {
                try {
                    $this->syncLink($link);
                    $synced++;
                } catch (\Throwable) {
                    $failed++;
                }
            }
        });

        return ['synced' => $synced, 'failed' => $failed];
    }

    public function syncLink(BoxProjectDocumentLink $link): ProjectDocument
    {
        $link->loadMissing(['boxItem', 'project', 'projectDocument']);

        if (! $link->boxItem) {
            $this->markLinkFailed($link, 'Linked Box item not found.');
        }

        if (! $link->project) {
            $this->markLinkFailed($link, 'Linked project not found.');
        }

        if ($link->boxItem->box_item_type !== 'file') {
            $this->markLinkFailed($link, 'Only Box file items can sync to project documents.');
        }

        $boxItem = $link->boxItem;
        $attributes = [
            'project_id' => $link->project_id,
            'title' => $boxItem->name,
            'description' => $this->buildDescription($boxItem),
            'type' => 'link',
            'url' => $this->buildBoxItemUrl($boxItem),
            'mime_type' => (string) data_get($boxItem->raw_payload, 'mime_type', '') ?: null,
            'file_size' => $boxItem->size,
            'file_type' => $this->detectFileType($boxItem),
            'uploaded_by' => $link->created_by,
            'content_hash' => $boxItem->sha1 ?: null,
            'last_seen_at' => now(),
            'is_archived' => ! empty($boxItem->trashed_at),
            'missing_on_disk' => false,
            'visibility' => $this->normalizeVisibility($link->visibility),
            'ai_indexed' => false,
        ];

        $doc = null;
        if ($link->projectDocument && $link->projectDocument->project_id === $link->project_id) {
            $link->projectDocument->fill($attributes);
            $link->projectDocument->save();
            $doc = $link->projectDocument;
        } else {
            $existing = ProjectDocument::where('project_id', $link->project_id)
                ->where('type', 'link')
                ->where('url', $this->buildBoxItemUrl($boxItem))
                ->first();

            if ($existing) {
                $existing->fill($attributes);
                $existing->save();
                $doc = $existing;
            } else {
                $doc = ProjectDocument::create($attributes);
            }
        }

        $link->update([
            'project_document_id' => $doc->id,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'last_error' => null,
        ]);

        return $doc;
    }

    public function queueSyncForBoxExternalItemId(string $boxExternalItemId): int
    {
        $boxItem = BoxItem::where('box_item_id', $boxExternalItemId)->first();
        if (! $boxItem) {
            return 0;
        }

        $count = 0;
        BoxProjectDocumentLink::where('box_item_id', $boxItem->id)
            ->pluck('id')
            ->each(function ($linkId) use (&$count) {
                \App\Jobs\SyncBoxProjectDocumentLink::dispatch((int) $linkId);
                $count++;
            });

        return $count;
    }

    private function buildDescription(BoxItem $boxItem): ?string
    {
        $path = trim((string) ($boxItem->path_display ?? ''));
        if ($path === '') {
            return 'Synced from Box';
        }

        return 'Synced from Box: '.$path;
    }

    private function buildBoxItemUrl(BoxItem $boxItem): string
    {
        return "https://app.box.com/file/{$boxItem->box_item_id}";
    }

    private function detectFileType(BoxItem $boxItem): ?string
    {
        $ext = strtolower((string) pathinfo($boxItem->name ?? '', PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : null;
    }

    private function normalizeVisibility(string $visibility): string
    {
        $visibility = strtolower(trim($visibility));

        return in_array($visibility, ['all', 'management', 'admin'], true) ? $visibility : 'all';
    }

    private function markLinkFailed(BoxProjectDocumentLink $link, string $message): void
    {
        $link->update([
            'sync_status' => 'failed',
            'last_error' => Str::limit($message, 1000),
        ]);

        throw new \RuntimeException($message);
    }
}
