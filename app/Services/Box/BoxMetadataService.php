<?php

namespace App\Services\Box;

use App\Models\BoxItem;
use Carbon\Carbon;

class BoxMetadataService
{
    public function __construct(
        protected BoxClient $client
    ) {}

    /**
     * @return array{upserted:int,subfolders:array<int,string>,entries:int,total:int}
     */
    public function syncFolderPage(string $folderId, int $offset = 0, ?int $limit = null): array
    {
        $data = $this->client->listFolderItems($folderId, $offset, $limit);
        $entries = is_array($data['entries'] ?? null) ? $data['entries'] : [];
        $upserted = 0;
        $subfolders = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $item = $this->upsertItem($entry, $folderId);
            if ($item) {
                $upserted++;
            }

            if (($entry['type'] ?? null) === 'folder' && ! empty($entry['id'])) {
                $subfolders[] = (string) $entry['id'];
            }
        }

        return [
            'upserted' => $upserted,
            'subfolders' => array_values(array_unique($subfolders)),
            'entries' => count($entries),
            'total' => (int) ($data['total_count'] ?? count($entries)),
        ];
    }

    public function refreshItem(string $itemType, string $itemId): ?BoxItem
    {
        $normalizedType = strtolower(trim($itemType));
        if (! in_array($normalizedType, ['file', 'folder'], true)) {
            return null;
        }

        $payload = $normalizedType === 'file'
            ? $this->client->getFile($itemId)
            : $this->client->getFolder($itemId);

        return $this->upsertItem($payload);
    }

    public function upsertItem(array $item, ?string $fallbackParentFolderId = null): ?BoxItem
    {
        $itemId = (string) ($item['id'] ?? '');
        $itemType = (string) ($item['type'] ?? '');
        if ($itemId === '' || $itemType === '') {
            return null;
        }

        $pathEntries = is_array(data_get($item, 'path_collection.entries')) ? data_get($item, 'path_collection.entries') : [];
        $pathSegments = array_values(array_filter(array_map(function ($entry) {
            if (! is_array($entry)) {
                return null;
            }

            $name = trim((string) ($entry['name'] ?? ''));

            return $name === '' ? null : $name;
        }, $pathEntries)));

        $currentName = trim((string) ($item['name'] ?? ''));
        if ($currentName !== '') {
            $pathSegments[] = $currentName;
        }

        $payload = [
            'box_item_type' => $itemType,
            'name' => $currentName !== '' ? $currentName : '[unnamed]',
            'parent_box_folder_id' => (string) data_get($item, 'parent.id', $fallbackParentFolderId ?: '') ?: null,
            'path_display' => empty($pathSegments) ? null : '/'.implode('/', $pathSegments),
            'etag' => (string) ($item['etag'] ?? '') ?: null,
            'sha1' => (string) ($item['sha1'] ?? '') ?: null,
            'size' => isset($item['size']) ? (int) $item['size'] : null,
            'owned_by_login' => (string) data_get($item, 'owned_by.login', '') ?: null,
            'modified_at' => $this->parseTimestamp($item['modified_at'] ?? null),
            'trashed_at' => $this->parseTimestamp($item['trashed_at'] ?? null),
            'permissions' => is_array($item['permissions'] ?? null) ? $item['permissions'] : null,
            'raw_payload' => $item,
            'last_synced_at' => now(),
        ];

        return BoxItem::updateOrCreate(
            ['box_item_id' => $itemId],
            $payload
        );
    }

    public function markItemTrashed(string $itemId): void
    {
        BoxItem::where('box_item_id', $itemId)->update([
            'trashed_at' => now(),
            'last_synced_at' => now(),
        ]);
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
