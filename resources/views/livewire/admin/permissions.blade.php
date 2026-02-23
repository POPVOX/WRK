<div class="max-w-[120rem] mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Permissions</h1>
            <p class="text-gray-500 dark:text-gray-400">Manage platform access and agent governance rights.</p>
        </div>
        <a href="{{ route('dashboard') }}" wire:navigate
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">← Back to Dashboard</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if(!$agentPermissionsEnabled)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
            Agent permissions table not found yet. Base user access can still be edited; run migrations to enable agent governance controls.
        </div>
    @endif

    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
        Use project IDs only when <strong>Project Scope = custom</strong>. Known projects: {{ $projectCount }}.
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Access</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Admin</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Create Specialist</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Create Project Agent</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Project Scope</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Custom Project IDs</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Approve Medium</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Approve High</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($rows as $index => $row)
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white whitespace-nowrap">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $row['email'] }}</td>
                            <td class="px-4 py-3 min-w-[10rem]">
                                <select wire:model="rows.{{ $index }}.access_level"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="team">Team</option>
                                    <option value="management">Management</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[7rem]">
                                <select wire:model="rows.{{ $index }}.is_admin"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[9rem]">
                                <select wire:model="rows.{{ $index }}.can_create_specialist"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[10rem]">
                                <select wire:model="rows.{{ $index }}.can_create_project"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[10rem]">
                                <select wire:model="rows.{{ $index }}.project_scope"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="none">None</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="all">All</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[14rem]">
                                <input type="text"
                                    wire:model.defer="rows.{{ $index }}.allowed_project_ids_text"
                                    placeholder="1, 12, 48"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @error('rows.'.$index.'.allowed_project_ids_text')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-3 min-w-[9rem]">
                                <select wire:model="rows.{{ $index }}.can_approve_medium_risk"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[9rem]">
                                <select wire:model="rows.{{ $index }}.can_approve_high_risk"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button wire:click="save"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                Save Changes
            </button>
        </div>
    </div>

    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Box Permission Control Plane</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manage folder policies, user grants, and apply/reconcile jobs.</p>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Synced folder options: {{ $boxFolderCount }}
            </div>
        </div>

        @if (session('box_status'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
                {{ session('box_status') }}
            </div>
        @endif

        @if(!$boxPermissionControlsEnabled)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-100">
                Box access control tables not found yet. Run migrations to enable policy/grant apply/reconcile controls.
            </div>
        @else
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
                <section class="xl:col-span-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Create Policy</h3>
                    <div class="mt-3 space-y-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Policy Key</label>
                            <input type="text"
                                wire:model.defer="boxPolicyForm.policy_key"
                                placeholder="tier1.projects"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('boxPolicyForm.policy_key')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tier</label>
                                <select wire:model.defer="boxPolicyForm.tier"
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="tier1">Tier 1</option>
                                    <option value="tier2">Tier 2</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Default Access</label>
                                <select wire:model.defer="boxPolicyForm.default_access"
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="read_write">Read/Write</option>
                                    <option value="read_only">Read-only</option>
                                    <option value="restricted">Restricted</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Box Folder</label>
                            <select wire:model.defer="boxPolicyForm.box_folder_id"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Select folder</option>
                                @foreach($boxFolderOptions as $folder)
                                    <option value="{{ $folder['id'] }}">{{ $folder['path'] }}</option>
                                @endforeach
                            </select>
                            @error('boxPolicyForm.box_folder_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Active</label>
                            <select wire:model.defer="boxPolicyForm.active"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <button wire:click="createBoxPolicy"
                            class="w-full px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Create Policy
                        </button>
                    </div>

                    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Policies</h4>
                        <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                            @forelse($boxPolicies as $policy)
                                <button wire:click="selectBoxPolicy({{ $policy['id'] }})"
                                    class="w-full text-left rounded-md border px-3 py-2 text-xs {{ $selectedBoxPolicyId === $policy['id'] ? 'border-indigo-300 bg-indigo-50 text-indigo-900 dark:border-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200' : 'border-gray-200 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold">{{ $policy['policy_key'] }}</span>
                                        <span class="rounded-full px-2 py-0.5 {{ $policy['open_drift_count'] > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                            drift {{ $policy['open_drift_count'] }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ strtoupper($policy['tier']) }} · folder {{ $policy['box_folder_id'] }} · grants {{ $policy['grants_count'] }}
                                    </div>
                                </button>
                            @empty
                                <div class="rounded-md border border-dashed border-gray-300 px-3 py-2 text-xs text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                    No policies yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="xl:col-span-8 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @if($selectedBoxPolicyId)
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Policy #{{ $selectedBoxPolicyId }} Grants</h3>
                            <div class="flex items-center gap-2">
                                <button wire:click="applySelectedBoxPolicy"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                    Apply Pending
                                </button>
                                <button wire:click="reconcileSelectedBoxPolicy"
                                    class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                    Reconcile
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                                <div>
                                    <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">User</label>
                                    <select wire:model.defer="boxGrantForm.user_id"
                                        class="mt-1 w-full rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">Select user</option>
                                        @foreach($boxUserOptions as $user)
                                            <option value="{{ $user['id'] }}">{{ $user['name'] }} ({{ $user['email'] }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Permission</label>
                                    <select wire:model.defer="boxGrantForm.wrk_permission"
                                        class="mt-1 w-full rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="read">Read</option>
                                        <option value="write">Write</option>
                                        <option value="manage">Manage</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subtree</label>
                                    <select wire:model.defer="boxGrantForm.applies_to_subtree"
                                        class="mt-1 w-full rounded-md border-gray-300 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button wire:click="saveBoxGrant"
                                        class="w-full px-3 py-2 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                        Save Grant
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" wire:model.defer="boxGrantForm.policy_id">
                            @error('boxGrantForm.policy_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('boxGrantForm.user_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">User</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Permission</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Box Role</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">State</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Last Sync</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-300">Error</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($boxGrants as $grant)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $grant['subject_name'] }}</div>
                                                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $grant['subject_email'] }}</div>
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $grant['wrk_permission'] }}</td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $grant['box_role'] ?: 'n/a' }}</td>
                                            <td class="px-3 py-2">
                                                <span class="rounded-full px-2 py-0.5 {{ in_array($grant['state'], ['failed', 'drift']) ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : ($grant['state'] === 'applied' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200') }}">
                                                    {{ strtoupper($grant['state']) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $grant['last_synced_at'] ?: '—' }}</td>
                                            <td class="px-3 py-2 text-red-600 dark:text-red-300">{{ $grant['last_error'] ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No grants for this policy.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Recent Operations</h4>
                                <div class="mt-2 space-y-2 max-h-52 overflow-y-auto">
                                    @forelse($boxOperations as $operation)
                                        <div class="rounded-md border border-gray-200 px-2.5 py-2 text-xs dark:border-gray-700">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-medium text-gray-900 dark:text-white">#{{ $operation['id'] }} {{ $operation['operation_type'] }}</span>
                                                <span class="rounded-full px-2 py-0.5 {{ $operation['status'] === 'applied' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : ($operation['status'] === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200') }}">
                                                    {{ strtoupper($operation['status']) }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ $operation['created_at'] }} @if($operation['completed_at']) · done {{ $operation['completed_at'] }} @endif
                                            </div>
                                            @if($operation['error_summary'])
                                                <div class="mt-1 text-[11px] text-red-600 dark:text-red-300">{{ $operation['error_summary'] }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-xs text-gray-500 dark:text-gray-400">No operations yet.</div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Open Drift Findings</h4>
                                <div class="mt-2 space-y-2 max-h-52 overflow-y-auto">
                                    @forelse($boxOpenDriftFindings as $finding)
                                        <div class="rounded-md border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs dark:border-amber-800 dark:bg-amber-900/20">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-medium text-amber-900 dark:text-amber-200">{{ $finding['finding_type'] }}</span>
                                                <button wire:click="resolveBoxDriftFinding({{ $finding['id'] }})"
                                                    class="rounded-md border border-amber-300 px-2 py-0.5 text-[11px] text-amber-800 hover:bg-amber-100 dark:border-amber-700 dark:text-amber-200 dark:hover:bg-amber-900/40">
                                                    Resolve
                                                </button>
                                            </div>
                                            <div class="mt-1 text-[11px] text-amber-800 dark:text-amber-200">
                                                {{ strtoupper($finding['severity']) }} · {{ $finding['detected_at'] }}
                                            </div>
                                            @if($finding['subject_name'])
                                                <div class="mt-1 text-[11px] text-amber-900 dark:text-amber-100">
                                                    {{ $finding['subject_name'] }} ({{ $finding['subject_email'] }})
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-xs text-gray-500 dark:text-gray-400">No open drift findings.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            Create or select a policy to manage grants.
                        </div>
                    @endif
                </section>
            </div>
        @endif
    </div>
</div>
