<?php

namespace App\Services\Box;

use App\Jobs\ApplyBoxAccessOperation;
use App\Models\BoxAccessDriftFinding;
use App\Models\BoxAccessGrant;
use App\Models\BoxAccessOperation;
use App\Models\BoxAccessOperationItem;
use App\Models\BoxAccessPolicy;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class BoxAccessService
{
    public function __construct(
        protected BoxClient $boxClient
    ) {}

    public function createApplyOperationForPolicy(int $policyId, ?int $actorUserId = null, bool $dispatch = true): ?BoxAccessOperation
    {
        $policy = BoxAccessPolicy::query()->find($policyId);
        if (! $policy || ! $policy->active) {
            return null;
        }

        $grantIds = BoxAccessGrant::query()
            ->where('policy_id', $policy->id)
            ->whereIn('state', ['desired', 'failed', 'drift'])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($grantIds === []) {
            return null;
        }

        $operation = BoxAccessOperation::query()->create([
            'operation_uuid' => (string) Str::uuid(),
            'actor_user_id' => $actorUserId,
            'operation_type' => 'grant_apply',
            'status' => 'pending',
            'target_policy_id' => $policy->id,
            'payload' => [
                'grant_ids' => $grantIds,
            ],
        ]);

        if ($dispatch) {
            ApplyBoxAccessOperation::dispatch($operation->id);
        }

        return $operation;
    }

    public function applyOperation(int $operationId): BoxAccessOperation
    {
        $operation = BoxAccessOperation::query()->with('policy')->findOrFail($operationId);
        $policy = $operation->policy;

        if (! $policy || ! $policy->active) {
            $operation->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_summary' => 'Target policy is missing or inactive.',
            ]);

            return $operation->fresh(['policy', 'items']);
        }

        $operation->update([
            'status' => 'applying',
            'started_at' => $operation->started_at ?? now(),
            'completed_at' => null,
            'error_summary' => null,
        ]);

        $grantIds = collect(Arr::get($operation->payload ?? [], 'grant_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $grants = BoxAccessGrant::query()
            ->where('policy_id', $policy->id)
            ->when($grantIds !== [], fn ($query) => $query->whereIn('id', $grantIds))
            ->orderBy('id')
            ->get();

        $collaborations = $this->listCollaborationsByLogin((string) $policy->box_folder_id);
        $failed = 0;
        $firstError = null;

        foreach ($grants as $grant) {
            $item = BoxAccessOperationItem::query()->create([
                'operation_id' => $operation->id,
                'grant_id' => $grant->id,
                'box_item_type' => 'folder',
                'box_item_id' => (string) $policy->box_folder_id,
                'action' => 'upsert_collaboration',
                'status' => 'pending',
            ]);

            try {
                $result = $this->applyGrantToPolicy($grant, $policy, $collaborations);

                $item->update([
                    'action' => (string) ($result['action'] ?? 'upsert_collaboration'),
                    'request_payload' => $result['request_payload'] ?? null,
                    'response_payload' => $result['response_payload'] ?? null,
                    'status' => 'applied',
                    'error_message' => null,
                ]);
            } catch (\Throwable $exception) {
                $failed++;
                $firstError ??= $exception->getMessage();

                $grant->update([
                    'state' => 'failed',
                    'last_error' => Str::limit($exception->getMessage(), 4000),
                ]);

                $item->update([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 4000),
                ]);
            }
        }

        $operation->update([
            'status' => $failed > 0 ? 'failed' : 'applied',
            'completed_at' => now(),
            'error_summary' => $failed > 0
                ? Str::limit("{$failed} grant(s) failed. {$firstError}", 4000)
                : null,
        ]);

        return $operation->fresh(['policy', 'items']);
    }

    /**
     * @return array{checked:int,matched:int,drifted:int,missing:int,mismatched:int}
     */
    public function reconcilePolicy(int $policyId): array
    {
        $policy = BoxAccessPolicy::query()->findOrFail($policyId);
        $grants = BoxAccessGrant::query()
            ->where('policy_id', $policy->id)
            ->orderBy('id')
            ->get();

        $collaborations = $this->listCollaborationsByLogin((string) $policy->box_folder_id);

        $counts = [
            'checked' => 0,
            'matched' => 0,
            'drifted' => 0,
            'missing' => 0,
            'mismatched' => 0,
        ];

        foreach ($grants as $grant) {
            $counts['checked']++;

            try {
                $user = $this->resolveGrantUser($grant);
                $login = Str::lower(trim((string) $user->email));
                $expectedRole = $this->resolveDesiredBoxRole($grant);
                $existing = $collaborations[$login] ?? null;

                if (! $existing) {
                    $counts['drifted']++;
                    $counts['missing']++;

                    $grant->update([
                        'state' => 'drift',
                        'last_error' => 'Missing Box collaboration.',
                    ]);

                    $this->createOrUpdateOpenDriftFinding(
                        $policy,
                        $grant,
                        'missing_collaboration',
                        $policy->tier === 'tier2' ? 'high' : 'medium',
                        [
                            'subject_type' => $grant->subject_type,
                            'subject_id' => $grant->subject_id,
                            'login' => $login,
                            'expected_role' => $expectedRole,
                        ],
                        null
                    );

                    continue;
                }

                $actualRole = (string) ($existing['role'] ?? '');
                if ($actualRole !== $expectedRole) {
                    $counts['drifted']++;
                    $counts['mismatched']++;

                    $grant->update([
                        'state' => 'drift',
                        'box_collaboration_id' => (string) ($existing['id'] ?? ''),
                        'last_error' => "Role mismatch. Expected {$expectedRole}, found {$actualRole}.",
                    ]);

                    $this->createOrUpdateOpenDriftFinding(
                        $policy,
                        $grant,
                        'role_mismatch',
                        $policy->tier === 'tier2' ? 'high' : 'medium',
                        [
                            'login' => $login,
                            'expected_role' => $expectedRole,
                        ],
                        [
                            'collaboration_id' => (string) ($existing['id'] ?? ''),
                            'actual_role' => $actualRole,
                        ]
                    );

                    continue;
                }

                $counts['matched']++;

                $grant->update([
                    'state' => 'applied',
                    'box_collaboration_id' => (string) ($existing['id'] ?? ''),
                    'last_synced_at' => now(),
                    'last_error' => null,
                ]);

                $this->resolveOpenDriftFindingsForGrant($policy->id, $grant->id, 'Resolved by reconcile.');
            } catch (\Throwable $exception) {
                $counts['drifted']++;

                $grant->update([
                    'state' => 'drift',
                    'last_error' => Str::limit($exception->getMessage(), 4000),
                ]);

                $this->createOrUpdateOpenDriftFinding(
                    $policy,
                    $grant,
                    'manual_box_change',
                    'medium',
                    ['error' => 'Unable to reconcile grant state.'],
                    ['error' => Str::limit($exception->getMessage(), 2000)]
                );
            }
        }

        return $counts;
    }

    public function mapWrkPermissionToBoxRole(string $wrkPermission): string
    {
        return match (Str::lower(trim($wrkPermission))) {
            'manage' => 'co-owner',
            'write' => 'editor',
            default => 'viewer',
        };
    }

    /**
     * @param  array<string,array{id:string,role:string,raw:array<string,mixed>}>  $collaborations
     * @return array{action:string,request_payload:array<string,mixed>|null,response_payload:array<string,mixed>|null}
     */
    protected function applyGrantToPolicy(BoxAccessGrant $grant, BoxAccessPolicy $policy, array &$collaborations): array
    {
        $user = $this->resolveGrantUser($grant);
        $login = Str::lower(trim((string) $user->email));
        $desiredRole = $this->resolveDesiredBoxRole($grant);

        $existing = $collaborations[$login] ?? null;
        $requestPayload = [
            'folder_id' => (string) $policy->box_folder_id,
            'login' => $login,
            'role' => $desiredRole,
        ];

        if ($existing) {
            $existingRole = (string) ($existing['role'] ?? '');
            $collabId = (string) ($existing['id'] ?? '');

            if ($collabId === '') {
                throw new RuntimeException("Box collaboration exists for {$login} but no collaboration ID was returned.");
            }

            if ($existingRole !== $desiredRole) {
                $response = $this->boxClient->updateCollaboration($collabId, $desiredRole);

                $collaborations[$login]['role'] = $desiredRole;
                $collaborations[$login]['raw'] = $response;

                $grant->update([
                    'box_role' => $desiredRole,
                    'box_collaboration_id' => $collabId,
                    'state' => 'applied',
                    'last_synced_at' => now(),
                    'last_error' => null,
                ]);

                return [
                    'action' => 'update_collaboration',
                    'request_payload' => $requestPayload,
                    'response_payload' => $response,
                ];
            }

            $grant->update([
                'box_role' => $desiredRole,
                'box_collaboration_id' => $collabId,
                'state' => 'applied',
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            return [
                'action' => 'noop',
                'request_payload' => $requestPayload,
                'response_payload' => is_array($existing['raw'] ?? null) ? $existing['raw'] : null,
            ];
        }

        $response = $this->boxClient->createFolderCollaboration((string) $policy->box_folder_id, $login, $desiredRole);
        $collabId = trim((string) ($response['id'] ?? ''));
        if ($collabId === '') {
            throw new RuntimeException("Box create collaboration did not return an ID for {$login}.");
        }

        $collaborations[$login] = [
            'id' => $collabId,
            'role' => $desiredRole,
            'raw' => $response,
        ];

        $grant->update([
            'box_role' => $desiredRole,
            'box_collaboration_id' => $collabId,
            'state' => 'applied',
            'last_synced_at' => now(),
            'last_error' => null,
        ]);

        return [
            'action' => 'create_collaboration',
            'request_payload' => $requestPayload,
            'response_payload' => $response,
        ];
    }

    protected function resolveDesiredBoxRole(BoxAccessGrant $grant): string
    {
        $boxRole = Str::lower(trim((string) ($grant->box_role ?? '')));
        if ($boxRole !== '') {
            return $boxRole;
        }

        return $this->mapWrkPermissionToBoxRole((string) $grant->wrk_permission);
    }

    protected function resolveGrantUser(BoxAccessGrant $grant): User
    {
        if (Str::lower((string) $grant->subject_type) !== 'user') {
            throw new RuntimeException("Unsupported subject type: {$grant->subject_type}");
        }

        $user = $grant->subjectUser;
        if (! $user) {
            throw new RuntimeException("User not found for grant {$grant->id}.");
        }

        $email = trim((string) $user->email);
        if ($email === '') {
            throw new RuntimeException("Grant user {$user->id} has no email address.");
        }

        return $user;
    }

    /**
     * @return array<string,array{id:string,role:string,raw:array<string,mixed>}>
     */
    protected function listCollaborationsByLogin(string $folderId): array
    {
        $offset = 0;
        $limit = 200;
        $result = [];

        do {
            $payload = $this->boxClient->listFolderCollaborations($folderId, $offset, $limit);
            $entries = collect($payload['entries'] ?? [])->filter(fn ($entry) => is_array($entry));

            foreach ($entries as $entry) {
                $login = Str::lower(trim((string) data_get($entry, 'accessible_by.login', '')));
                if ($login === '') {
                    continue;
                }

                $collaborationId = trim((string) ($entry['id'] ?? ''));
                if ($collaborationId === '') {
                    continue;
                }

                $result[$login] = [
                    'id' => $collaborationId,
                    'role' => Str::lower(trim((string) ($entry['role'] ?? ''))),
                    'raw' => $entry,
                ];
            }

            $fetched = $entries->count();
            $offset += $fetched;
            $total = (int) ($payload['total_count'] ?? $offset);
        } while ($offset < $total && $fetched > 0);

        return $result;
    }

    /**
     * @param  array<string,mixed>|null  $expectedState
     * @param  array<string,mixed>|null  $actualState
     */
    protected function createOrUpdateOpenDriftFinding(
        BoxAccessPolicy $policy,
        BoxAccessGrant $grant,
        string $findingType,
        string $severity,
        ?array $expectedState,
        ?array $actualState
    ): void {
        $finding = BoxAccessDriftFinding::query()
            ->where('policy_id', $policy->id)
            ->where('grant_id', $grant->id)
            ->where('finding_type', $findingType)
            ->whereNull('resolved_at')
            ->first();

        if ($finding) {
            $finding->update([
                'severity' => $severity,
                'expected_state' => $expectedState,
                'actual_state' => $actualState,
                'detected_at' => now(),
            ]);

            return;
        }

        BoxAccessDriftFinding::query()->create([
            'policy_id' => $policy->id,
            'grant_id' => $grant->id,
            'finding_type' => $findingType,
            'severity' => $severity,
            'expected_state' => $expectedState,
            'actual_state' => $actualState,
            'detected_at' => now(),
        ]);
    }

    protected function resolveOpenDriftFindingsForGrant(int $policyId, int $grantId, string $note): void
    {
        BoxAccessDriftFinding::query()
            ->where('policy_id', $policyId)
            ->where('grant_id', $grantId)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'resolution_note' => Str::limit($note, 2000),
            ]);
    }
}
