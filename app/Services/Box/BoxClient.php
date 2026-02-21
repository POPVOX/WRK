<?php

namespace App\Services\Box;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BoxClient
{
    public function createFolder(string $name, string $parentFolderId): array
    {
        return $this->request()
            ->post('folders', [
                'name' => $name,
                'parent' => [
                    'id' => $parentFolderId,
                ],
            ])
            ->throw()
            ->json();
    }

    public function listFolderItems(string $folderId, int $offset = 0, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('services.box.sync_page_size', 100);

        return $this->request()
            ->get("folders/{$folderId}/items", [
                'offset' => $offset,
                'limit' => max(1, min($limit, 1000)),
                'fields' => implode(',', [
                    'id',
                    'type',
                    'name',
                    'etag',
                    'sha1',
                    'size',
                    'modified_at',
                    'trashed_at',
                    'permissions',
                    'owned_by',
                    'parent',
                    'path_collection',
                ]),
            ])
            ->throw()
            ->json();
    }

    public function getFile(string $fileId): array
    {
        return $this->request()
            ->get("files/{$fileId}", [
                'fields' => implode(',', [
                    'id',
                    'type',
                    'name',
                    'etag',
                    'sha1',
                    'size',
                    'modified_at',
                    'trashed_at',
                    'permissions',
                    'owned_by',
                    'parent',
                    'path_collection',
                ]),
            ])
            ->throw()
            ->json();
    }

    public function getFolder(string $folderId): array
    {
        return $this->request()
            ->get("folders/{$folderId}", [
                'fields' => implode(',', [
                    'id',
                    'type',
                    'name',
                    'etag',
                    'modified_at',
                    'trashed_at',
                    'permissions',
                    'owned_by',
                    'parent',
                    'path_collection',
                ]),
            ])
            ->throw()
            ->json();
    }

    public function itemNotFound(RequestException $exception): bool
    {
        return $exception->response instanceof Response && $exception->response->status() === 404;
    }

    public function verifyWebhookSignature(
        string $body,
        ?string $deliveryTimestamp,
        ?string $primarySignature,
        ?string $secondarySignature
    ): bool {
        $enforceSignature = (bool) config('services.box.webhook.enforce_signature', true);
        if (! $enforceSignature) {
            return true;
        }

        $primaryKey = trim((string) config('services.box.webhook.primary_signature_key', ''));
        $secondaryKey = trim((string) config('services.box.webhook.secondary_signature_key', ''));
        if ($primaryKey === '' && $secondaryKey === '') {
            return false;
        }

        if (! $deliveryTimestamp) {
            return false;
        }

        $payload = $deliveryTimestamp.$body;

        if ($this->signatureMatches($payload, $primarySignature, $primaryKey)) {
            return true;
        }

        if ($this->signatureMatches($payload, $secondarySignature, $secondaryKey)) {
            return true;
        }

        return false;
    }

    private function signatureMatches(string $payload, ?string $providedSignature, string $secret): bool
    {
        $providedSignature = trim((string) $providedSignature);
        if ($providedSignature === '' || $secret === '') {
            return false;
        }

        $rawHash = hash_hmac('sha256', $payload, $secret, true);
        $expectedBase64 = base64_encode($rawHash);
        $expectedHex = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedBase64, $providedSignature)
            || hash_equals($expectedHex, strtolower($providedSignature));
    }

    private function request(): PendingRequest
    {
        $accessToken = trim((string) config('services.box.access_token', ''));
        if ($accessToken === '') {
            throw new RuntimeException('Box access token is not configured.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->baseUrl((string) config('services.box.base_uri', 'https://api.box.com/2.0'))
            ->timeout(20);
    }
}
