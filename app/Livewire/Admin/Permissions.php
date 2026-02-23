<?php

namespace App\Livewire\Admin;

use App\Jobs\ReconcileBoxAccessPolicy;
use App\Models\AgentPermission;
use App\Models\BoxAccessDriftFinding;
use App\Models\BoxAccessGrant;
use App\Models\BoxAccessOperation;
use App\Models\BoxAccessPolicy;
use App\Models\BoxItem;
use App\Models\Project;
use App\Models\User;
use App\Services\Box\BoxAccessService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Admin Permissions')]
class Permissions extends Component
{
    public array $rows = [];

    public array $projectOptions = [];

    public bool $agentPermissionsEnabled = false;

    public bool $boxPermissionControlsEnabled = false;

    public ?int $selectedBoxPolicyId = null;

    public array $boxPolicyForm = [
        'policy_key' => '',
        'tier' => 'tier1',
        'box_folder_id' => '',
        'default_access' => 'read_write',
        'active' => '1',
    ];

    public array $boxGrantForm = [
        'policy_id' => '',
        'user_id' => '',
        'wrk_permission' => 'read',
        'applies_to_subtree' => '0',
    ];

    public array $boxFolderOptions = [];

    public array $boxUserOptions = [];

    public array $boxPolicies = [];

    public array $boxGrants = [];

    public array $boxOperations = [];

    public array $boxOpenDriftFindings = [];

    public function mount(): void
    {
        $this->agentPermissionsEnabled = Schema::hasTable('agent_permissions');
        $this->boxPermissionControlsEnabled = $this->hasBoxAccessSchema();

        $this->projectOptions = Project::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
            ])->values()->all();

        $users = User::orderBy('name')
            ->get(['id', 'name', 'email', 'access_level', 'is_admin']);

        $permissionMap = $this->agentPermissionsEnabled
            ? AgentPermission::query()
                ->whereIn('user_id', $users->pluck('id'))
                ->get()
                ->keyBy('user_id')
            : collect();

        $this->rows = $users
            ->map(function (User $user) use ($permissionMap): array {
                /** @var AgentPermission|null $permission */
                $permission = $permissionMap->get($user->id);
                $defaultSpecialist = $user->isManagement();
                $defaultApprove = $user->isManagement();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'access_level' => $user->access_level ?? 'team',
                    'is_admin' => $user->is_admin ? '1' : '0',
                    'can_create_specialist' => (($permission?->can_create_specialist ?? $defaultSpecialist) ? '1' : '0'),
                    'can_create_project' => (($permission?->can_create_project ?? true) ? '1' : '0'),
                    'project_scope' => (string) ($permission?->project_scope ?? 'all'),
                    'allowed_project_ids_text' => collect($permission?->allowed_project_ids ?? [])->implode(', '),
                    'can_approve_medium_risk' => (($permission?->can_approve_medium_risk ?? $defaultApprove) ? '1' : '0'),
                    'can_approve_high_risk' => (($permission?->can_approve_high_risk ?? $defaultApprove) ? '1' : '0'),
                ];
            })
            ->toArray();

        if ($this->boxPermissionControlsEnabled) {
            $this->loadBoxAccessControls();
        }
    }

    public function save(): void
    {
        $this->validate([
            'rows' => 'required|array',
            'rows.*.id' => ['required', 'integer', Rule::exists('users', 'id')],
            'rows.*.access_level' => ['required', Rule::in(['team', 'management', 'admin'])],
            'rows.*.is_admin' => ['required', Rule::in(['0', '1'])],
        ]);

        if ($this->agentPermissionsEnabled) {
            $this->validate([
                'rows.*.can_create_specialist' => ['required', Rule::in(['0', '1'])],
                'rows.*.can_create_project' => ['required', Rule::in(['0', '1'])],
                'rows.*.project_scope' => ['required', Rule::in(['none', 'assigned', 'all', 'custom'])],
                'rows.*.allowed_project_ids_text' => ['nullable', 'string', 'max:2000'],
                'rows.*.can_approve_medium_risk' => ['required', Rule::in(['0', '1'])],
                'rows.*.can_approve_high_risk' => ['required', Rule::in(['0', '1'])],
            ]);
        }

        $knownProjectIds = collect($this->projectOptions)->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($this->rows as $index => $row) {
            if (! $this->agentPermissionsEnabled) {
                break;
            }

            $allowedProjectIds = $this->parseProjectIds((string) Arr::get($row, 'allowed_project_ids_text'));

            $invalid = collect($allowedProjectIds)
                ->filter(fn ($id) => ! in_array((int) $id, $knownProjectIds, true))
                ->values()
                ->all();

            if (! empty($invalid)) {
                $this->addError('rows.'.$index.'.allowed_project_ids_text', 'Unknown project ID(s): '.implode(', ', $invalid));

                return;
            }

            if (($row['project_scope'] ?? 'all') === 'custom' && empty($allowedProjectIds)) {
                $this->addError('rows.'.$index.'.allowed_project_ids_text', 'Custom scope requires at least one project ID.');

                return;
            }
        }

        foreach ($this->rows as $row) {
            $allowedProjectIds = $this->parseProjectIds((string) Arr::get($row, 'allowed_project_ids_text'));

            User::where('id', $row['id'])->update([
                'access_level' => $row['access_level'],
                'is_admin' => $row['is_admin'] === '1',
            ]);

            if ($this->agentPermissionsEnabled) {
                AgentPermission::query()->updateOrCreate(
                    ['user_id' => $row['id']],
                    [
                        'can_create_specialist' => $row['can_create_specialist'] === '1',
                        'can_create_project' => $row['can_create_project'] === '1',
                        'project_scope' => $row['project_scope'],
                        'allowed_project_ids' => $row['project_scope'] === 'custom' ? $allowedProjectIds : null,
                        'can_approve_medium_risk' => $row['can_approve_medium_risk'] === '1',
                        'can_approve_high_risk' => $row['can_approve_high_risk'] === '1',
                    ]
                );
            }
        }

        session()->flash('status', 'Permissions updated.');
        $this->mount();
    }

    public function selectBoxPolicy(int $policyId): void
    {
        if (! $this->boxPermissionControlsEnabled) {
            return;
        }

        $this->selectedBoxPolicyId = $policyId;
        $this->boxGrantForm['policy_id'] = (string) $policyId;
        $this->loadSelectedBoxPolicyData();
    }

    public function createBoxPolicy(): void
    {
        if (! $this->boxPermissionControlsEnabled) {
            return;
        }

        $validated = $this->validate([
            'boxPolicyForm.policy_key' => ['required', 'string', 'max:120', Rule::unique('box_access_policies', 'policy_key')],
            'boxPolicyForm.tier' => ['required', Rule::in(['tier1', 'tier2'])],
            'boxPolicyForm.box_folder_id' => ['required', 'string', 'max:64'],
            'boxPolicyForm.default_access' => ['required', Rule::in(['read_write', 'read_only', 'restricted'])],
            'boxPolicyForm.active' => ['required', Rule::in(['0', '1'])],
        ]);

        $policy = BoxAccessPolicy::query()->create([
            'policy_key' => trim((string) $validated['boxPolicyForm']['policy_key']),
            'tier' => $validated['boxPolicyForm']['tier'],
            'box_folder_id' => trim((string) $validated['boxPolicyForm']['box_folder_id']),
            'default_access' => $validated['boxPolicyForm']['default_access'],
            'managed_by_wrk' => true,
            'active' => $validated['boxPolicyForm']['active'] === '1',
            'metadata' => null,
        ]);

        $this->selectedBoxPolicyId = (int) $policy->id;
        $this->boxGrantForm['policy_id'] = (string) $policy->id;
        $this->boxPolicyForm['policy_key'] = '';
        $this->boxPolicyForm['box_folder_id'] = '';

        session()->flash('box_status', 'Box access policy created.');
        $this->loadBoxAccessControls();
    }

    public function saveBoxGrant(BoxAccessService $boxAccessService): void
    {
        if (! $this->boxPermissionControlsEnabled) {
            return;
        }

        $validated = $this->validate([
            'boxGrantForm.policy_id' => ['required', 'integer', Rule::exists('box_access_policies', 'id')],
            'boxGrantForm.user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'boxGrantForm.wrk_permission' => ['required', Rule::in(['read', 'write', 'manage'])],
            'boxGrantForm.applies_to_subtree' => ['required', Rule::in(['0', '1'])],
        ]);

        $policyId = (int) $validated['boxGrantForm']['policy_id'];
        $userId = (int) $validated['boxGrantForm']['user_id'];
        $wrkPermission = (string) $validated['boxGrantForm']['wrk_permission'];

        BoxAccessGrant::query()->updateOrCreate(
            [
                'policy_id' => $policyId,
                'subject_type' => 'user',
                'subject_id' => $userId,
            ],
            [
                'wrk_permission' => $wrkPermission,
                'box_role' => $boxAccessService->mapWrkPermissionToBoxRole($wrkPermission),
                'applies_to_subtree' => $validated['boxGrantForm']['applies_to_subtree'] === '1',
                'state' => 'desired',
                'last_error' => null,
                'source' => 'manual',
            ]
        );

        $this->selectedBoxPolicyId = $policyId;
        $this->boxGrantForm['policy_id'] = (string) $policyId;

        session()->flash('box_status', 'Box access grant saved. Apply policy to push changes to Box.');
        $this->loadBoxAccessControls();
    }

    public function applySelectedBoxPolicy(BoxAccessService $boxAccessService): void
    {
        if (! $this->boxPermissionControlsEnabled || ! $this->selectedBoxPolicyId) {
            return;
        }

        $operation = $boxAccessService->createApplyOperationForPolicy(
            (int) $this->selectedBoxPolicyId,
            auth()->id()
        );

        if (! $operation) {
            session()->flash('box_status', 'No pending or drifted grants to apply for this policy.');
            $this->loadSelectedBoxPolicyData();

            return;
        }

        session()->flash('box_status', "Queued apply operation #{$operation->id}.");
        $this->loadSelectedBoxPolicyData();
    }

    public function reconcileSelectedBoxPolicy(): void
    {
        if (! $this->boxPermissionControlsEnabled || ! $this->selectedBoxPolicyId) {
            return;
        }

        ReconcileBoxAccessPolicy::dispatch((int) $this->selectedBoxPolicyId);

        session()->flash('box_status', 'Queued policy reconciliation job.');
        $this->loadSelectedBoxPolicyData();
    }

    public function resolveBoxDriftFinding(int $findingId): void
    {
        if (! $this->boxPermissionControlsEnabled) {
            return;
        }

        $finding = BoxAccessDriftFinding::query()
            ->whereNull('resolved_at')
            ->find($findingId);

        if (! $finding) {
            return;
        }

        $finding->update([
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
            'resolution_note' => 'Manually resolved from Admin Permissions.',
        ]);

        session()->flash('box_status', 'Drift finding marked resolved.');
        $this->loadSelectedBoxPolicyData();
    }

    protected function loadBoxAccessControls(): void
    {
        $this->boxFolderOptions = BoxItem::query()
            ->folders()
            ->whereNull('trashed_at')
            ->orderByRaw("coalesce(path_display, name)")
            ->limit(2000)
            ->get(['box_item_id', 'name', 'path_display'])
            ->map(fn (BoxItem $folder) => [
                'id' => (string) $folder->box_item_id,
                'name' => (string) $folder->name,
                'path' => (string) ($folder->path_display ?: $folder->name),
            ])->values()->all();

        $this->boxUserOptions = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
            ])->values()->all();

        $this->boxPolicies = BoxAccessPolicy::query()
            ->withCount('grants')
            ->withCount([
                'driftFindings as open_drift_count' => fn ($query) => $query->whereNull('resolved_at'),
            ])
            ->orderBy('tier')
            ->orderBy('policy_key')
            ->get()
            ->map(fn (BoxAccessPolicy $policy) => [
                'id' => (int) $policy->id,
                'policy_key' => (string) $policy->policy_key,
                'tier' => (string) $policy->tier,
                'box_folder_id' => (string) $policy->box_folder_id,
                'default_access' => (string) $policy->default_access,
                'active' => (bool) $policy->active,
                'grants_count' => (int) $policy->grants_count,
                'open_drift_count' => (int) $policy->open_drift_count,
            ])
            ->values()
            ->all();

        $availablePolicyIds = collect($this->boxPolicies)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($this->selectedBoxPolicyId === null || ! in_array((int) $this->selectedBoxPolicyId, $availablePolicyIds, true)) {
            $this->selectedBoxPolicyId = $availablePolicyIds[0] ?? null;
        }

        $this->boxGrantForm['policy_id'] = $this->selectedBoxPolicyId ? (string) $this->selectedBoxPolicyId : '';
        $this->loadSelectedBoxPolicyData();
    }

    protected function loadSelectedBoxPolicyData(): void
    {
        if (! $this->selectedBoxPolicyId) {
            $this->boxGrants = [];
            $this->boxOperations = [];
            $this->boxOpenDriftFindings = [];

            return;
        }

        $policyId = (int) $this->selectedBoxPolicyId;

        $this->boxGrants = BoxAccessGrant::query()
            ->where('policy_id', $policyId)
            ->with('subjectUser:id,name,email')
            ->orderBy('subject_type')
            ->orderBy('subject_id')
            ->get()
            ->map(fn (BoxAccessGrant $grant) => [
                'id' => (int) $grant->id,
                'subject_type' => (string) $grant->subject_type,
                'subject_id' => (int) $grant->subject_id,
                'subject_name' => (string) ($grant->subjectUser?->name ?? 'Unknown user'),
                'subject_email' => (string) ($grant->subjectUser?->email ?? ''),
                'wrk_permission' => (string) $grant->wrk_permission,
                'box_role' => (string) ($grant->box_role ?? ''),
                'state' => (string) $grant->state,
                'applies_to_subtree' => (bool) $grant->applies_to_subtree,
                'last_synced_at' => $grant->last_synced_at?->format('M j, Y g:i A'),
                'last_error' => (string) ($grant->last_error ?? ''),
            ])->values()->all();

        $this->boxOperations = BoxAccessOperation::query()
            ->where('target_policy_id', $policyId)
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (BoxAccessOperation $operation) => [
                'id' => (int) $operation->id,
                'operation_type' => (string) $operation->operation_type,
                'status' => (string) $operation->status,
                'created_at' => $operation->created_at?->format('M j, g:i A'),
                'completed_at' => $operation->completed_at?->format('M j, g:i A'),
                'error_summary' => (string) ($operation->error_summary ?? ''),
            ])->values()->all();

        $this->boxOpenDriftFindings = BoxAccessDriftFinding::query()
            ->where('policy_id', $policyId)
            ->whereNull('resolved_at')
            ->with(['grant.subjectUser:id,name,email'])
            ->latest('detected_at')
            ->limit(20)
            ->get()
            ->map(function (BoxAccessDriftFinding $finding): array {
                $grant = $finding->grant;

                return [
                    'id' => (int) $finding->id,
                    'finding_type' => (string) $finding->finding_type,
                    'severity' => (string) $finding->severity,
                    'detected_at' => $finding->detected_at?->format('M j, g:i A'),
                    'subject_name' => (string) ($grant?->subjectUser?->name ?? ''),
                    'subject_email' => (string) ($grant?->subjectUser?->email ?? ''),
                    'expected_state' => $finding->expected_state,
                    'actual_state' => $finding->actual_state,
                ];
            })->values()->all();
    }

    protected function hasBoxAccessSchema(): bool
    {
        return Schema::hasTable('box_access_policies')
            && Schema::hasTable('box_access_grants')
            && Schema::hasTable('box_access_operations')
            && Schema::hasTable('box_access_operation_items')
            && Schema::hasTable('box_access_drift_findings');
    }

    protected function parseProjectIds(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn ($item) => trim($item))
            ->filter(fn ($item) => $item !== '' && ctype_digit($item))
            ->map(fn ($item) => (int) $item)
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.permissions', [
            'projectCount' => count($this->projectOptions),
            'boxFolderCount' => count($this->boxFolderOptions),
        ]);
    }
}
