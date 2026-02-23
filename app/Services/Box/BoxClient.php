<?php

namespace App\Services\Box;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BoxClient
{
    public function isConfigured(): bool
    {
        $staticToken = trim((string) config('services.box.access_token', ''));
        if ($staticToken !== '') {
            return true;
        }

        $clientId = trim((string) config('services.box.client_id', ''));
        $clientSecret = trim((string) config('services.box.client_secret', ''));
        if ($clientId === '' || $clientSecret === '') {
            return false;
        }

        [, $subjectId] = $this->resolveSubjectReference();

        return $subjectId !== '';
    }

    public function createFolder(string $name, string $parentFolderId): array
    {
        return $this->postJson('folders', [
            'name' => $name,
            'parent' => [
                'id' => $parentFolderId,
            ],
        ]);
    }

    public function listFolderItems(string $folderId, int $offset = 0, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('services.box.sync_page_size', 100);

        return $this->getJson("folders/{$folderId}/items", [
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
        ]);
    }

    public function getFile(string $fileId): array
    {
        return $this->getJson("files/{$fileId}", [
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
        ]);
    }

    public function getFolder(string $folderId): array
    {
        return $this->getJson("folders/{$folderId}", [
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
        ]);
    }

    public function listFolderCollaborations(string $folderId, int $offset = 0, int $limit = 100): array
    {
        return $this->getJson("folders/{$folderId}/collaborations", [
            'offset' => max(0, $offset),
            'limit' => max(1, min($limit, 1000)),
            'fields' => implode(',', [
                'id',
                'type',
                'role',
                'status',
                'accessible_by',
                'item',
                'created_at',
                'modified_at',
                'expires_at',
            ]),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function createFolderCollaboration(string $folderId, string $userEmail, string $role): array
    {
        return $this->postJson('collaborations', [
            'item' => [
                'type' => 'folder',
                'id' => $folderId,
            ],
            'accessible_by' => [
                'type' => 'user',
                'login' => $userEmail,
            ],
            'role' => $role,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function updateCollaboration(string $collaborationId, string $role): array
    {
        return $this->putJson("collaborations/{$collaborationId}", [
            'role' => $role,
        ]);
    }

    public function deleteCollaboration(string $collaborationId): void
    {
        $this->sendWithRefreshRetry(
            fn (bool $refresh) => $this->request($refresh)->delete("collaborations/{$collaborationId}")
        )->throw();
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

    private function request(bool $forceRefreshToken = false): PendingRequest
    {
        $accessToken = $this->resolveAccessToken($forceRefreshToken);

        return Http::acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->baseUrl((string) config('services.box.base_uri', 'https://api.box.com/2.0'))
            ->timeout(20);
    }

    /**
     * @param  array<string,mixed>  $query
     * @return array<string,mixed>
     */
    private function getJson(string $uri, array $query = []): array
    {
        $response = $this->sendWithRefreshRetry(fn (bool $refresh) => $this->request($refresh)->get($uri, $query));
        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function postJson(string $uri, array $payload = []): array
    {
        $response = $this->sendWithRefreshRetry(fn (bool $refresh) => $this->request($refresh)->post($uri, $payload));
        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function putJson(string $uri, array $payload = []): array
    {
        $response = $this->sendWithRefreshRetry(fn (bool $refresh) => $this->request($refresh)->put($uri, $payload));
        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    private function sendWithRefreshRetry(\Closure $call): Response
    {
        try {
            /** @var Response $response */
            $response = $call(false);

            return $response->throw();
        } catch (RequestException $exception) {
            if (! $this->shouldRetryWithFreshToken($exception)) {
                throw $exception;
            }

            /** @var Response $response */
            $response = $call(true);

            return $response->throw();
        }
    }

    private function shouldRetryWithFreshToken(RequestException $exception): bool
    {
        if (trim((string) config('services.box.access_token', '')) !== '') {
            return false;
        }

        return $exception->response instanceof Response && $exception->response->status() === 401;
    }

    private function resolveAccessToken(bool $forceRefresh = false): string
    {
        $staticToken = trim((string) config('services.box.access_token', ''));
        if ($staticToken !== '') {
            return $staticToken;
        }

        $clientId = trim((string) config('services.box.client_id', ''));
        $clientSecret = trim((string) config('services.box.client_secret', ''));
        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Box auth is not configured. Set BOX_ACCESS_TOKEN or BOX_CLIENT_ID/BOX_CLIENT_SECRET + subject id.');
        }

        [$subjectType, $subjectId] = $this->resolveSubjectReference();
        if ($subjectId === '') {
            throw new RuntimeException('Box CCG subject is not configured. Set BOX_SUBJECT_TYPE and corresponding BOX_ENTERPRISE_ID or BOX_USER_ID.');
        }

        $cacheKey = $this->tokenCacheKey($subjectType, $subjectId);
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && trim($cached) !== '') {
                return trim($cached);
            }
        }

        $response = Http::acceptJson()
            ->asForm()
            ->timeout(20)
            ->post((string) config('services.box.token_url', 'https://api.box.com/oauth2/token'), [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'box_subject_type' => $subjectType,
                'box_subject_id' => $subjectId,
            ])
            ->throw();

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Box token endpoint returned an unexpected response.');
        }

        $token = trim((string) ($payload['access_token'] ?? ''));
        if ($token === '') {
            throw new RuntimeException('Box token endpoint response did not include access_token.');
        }

        $expiresIn = max(120, (int) ($payload['expires_in'] ?? 3600));
        $ttl = max(60, $expiresIn - 120);
        Cache::put($cacheKey, $token, now()->addSeconds($ttl));

        return $token;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveSubjectReference(): array
    {
        $subjectType = strtolower(trim((string) config('services.box.subject_type', 'enterprise')));
        if (! in_array($subjectType, ['enterprise', 'user'], true)) {
            throw new RuntimeException('BOX_SUBJECT_TYPE must be either "enterprise" or "user".');
        }

        $subjectId = $subjectType === 'user'
            ? trim((string) config('services.box.user_id', ''))
            : trim((string) config('services.box.enterprise_id', ''));

        if ($subjectId === '') {
            if ($subjectType === 'enterprise') {
                $fallback = trim((string) config('services.box.user_id', ''));
                if ($fallback !== '') {
                    return ['user', $fallback];
                }
            } else {
                $fallback = trim((string) config('services.box.enterprise_id', ''));
                if ($fallback !== '') {
                    return ['enterprise', $fallback];
                }
            }
        }

        return [$subjectType, $subjectId];
    }

    private function tokenCacheKey(string $subjectType, string $subjectId): string
    {
        $clientId = trim((string) config('services.box.client_id', ''));
        $suffix = md5($clientId.'|'.$subjectType.'|'.$subjectId);

        return 'box:ccg-token:'.$suffix;
    }
}
