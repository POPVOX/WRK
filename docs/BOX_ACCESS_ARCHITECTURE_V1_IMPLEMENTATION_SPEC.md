# WRK Box Access Architecture v1 — Implementation Spec

Date: February 23, 2026  
Status: Draft v1 (implementation-ready)  
Owner: WRK Platform Team

Related:
- `/Users/marcimac/PROJECTS/PVOXFDN MTGS TEST/docs/WRK_PLATFORM_SPEC.md`
- `/Users/marcimac/PROJECTS/PVOXFDN MTGS TEST/docs/BOX_TAXONOMY_AND_PERMISSION_MAPPING.md`
- `/Users/marcimac/PROJECTS/PVOXFDN MTGS TEST/docs/WRK_V2_SCHEMA_PROPOSAL.md`
- `/Users/marcimac/PROJECTS/PVOXFDN MTGS TEST/docs/WRK_V2_DATA_CONTRACT.md`

## 1) Objective

Implement a production-safe access model where:
- WRK is the permission control plane for shared/restricted organizational folders.
- Box is the storage and enforcement layer.
- Personal workspaces stay Box-native, with WRK registry + offboarding hooks.
- Agent access is explicit, scoped, auditable, and revocable.

Guiding rule: **WRK decides, Box enforces.**

## 2) Scope (v1)

### In Scope
- Tiered folder governance for shared and restricted WRK folders.
- Permission CRUD from WRK with Box API enforcement in same operation path.
- Drift detection and nightly reconciliation.
- Personal workspace registry.
- Document promotion workflow (move + reference).
- Agent Box scope grants for file access boundaries.
- Offboarding workflow tied to access revocation and promotion review.

### Out of Scope (v1)
- Full Box legal hold automation.
- AI autonomous policy edits without human role permissions.
- Full eDiscovery workflows.
- External identity provider group sync beyond WRK users/roles.

## 3) Tier Model and Governance

### Tier 1: WRK Shared Knowledge Base
- Folders: `PROJECTS`, `MEETINGS`, `CONTACTS_ORGS`, `KNOWLEDGE`, `TEAM`, `TRAVEL`.
- Default: broad staff read/write.
- Agent default: read, with scoped write grants.
- Governance: WRK-managed, Box-enforced.

### Tier 2: WRK Restricted Folders
- Folders: `ADMIN_RESTRICTED`, restricted parts of `FUNDERS`, restricted parts of `CONTRACTS`.
- Default: narrower role-based access.
- Split pattern:
  - Shared summary folder (staff-visible).
  - Restricted detail folder (leadership/admin).
- Governance: WRK-managed, Box-enforced.

### Tier 3: Personal Workspaces
- User-owned Box folders outside WRK canonical tree.
- Governance: Box-native (not managed by WRK ACL control plane).
- WRK responsibility: registry, promotion, offboarding checks.

## 4) Permission Consistency Contract

For WRK-managed tiers (1 and 2):
- A permission change request is successful only when Box update succeeds.
- If Box write fails, WRK marks operation failed and does not mark target permission as applied.
- Manual Box changes are treated as drift and surfaced in WRK.

Because DB + Box is not a true distributed transaction, v1 uses:
- `desired_state` tracking
- synchronous apply attempt
- compensation on partial failures
- reconciliation for eventual correctness

## 5) Data Model Additions (Postgres)

All IDs are bigint primary keys plus canonical prefixed IDs at API surface where applicable.

### 5.1 `box_access_policies`
Defines governance boundaries by folder/subtree.

Key columns:
- `id`
- `policy_key` (unique string, ex: `tier1.projects`)
- `tier` (`tier1`, `tier2`)
- `box_folder_id` (string)
- `entity_type` (nullable: `project`, `funder`, `contract`, `team`, `knowledge`, `travel`, etc.)
- `entity_id` (nullable bigint)
- `default_access` (`read_write`, `read_only`, `restricted`)
- `managed_by_wrk` (bool, default true)
- `active` (bool)
- `metadata` (jsonb)
- timestamps

Indexes:
- unique(`policy_key`)
- index(`box_folder_id`)
- index(`tier`, `active`)

### 5.2 `box_access_grants`
Desired/applied access grants per policy + subject.

Key columns:
- `id`
- `policy_id` (fk -> `box_access_policies`)
- `subject_type` (`user`, `role`, `agent`)
- `subject_id` (bigint or role key mapped through metadata)
- `wrk_permission` (`read`, `write`, `manage`)
- `box_role` (`viewer`, `editor`, `co_owner`)
- `applies_to_subtree` (bool)
- `state` (`desired`, `applying`, `applied`, `failed`, `drift`)
- `box_collaboration_id` (nullable string)
- `last_synced_at` (nullable timestamp)
- `last_error` (nullable text)
- `source` (`manual`, `role_default`, `system`)
- timestamps

Indexes:
- unique(`policy_id`, `subject_type`, `subject_id`, `wrk_permission`)
- index(`state`)
- index(`box_collaboration_id`)

### 5.3 `box_access_operations`
Operation envelope for one user action and its Box side effects.

Key columns:
- `id`
- `operation_uuid` (uuid unique)
- `actor_user_id` (fk users)
- `operation_type` (`grant_create`, `grant_update`, `grant_revoke`, `bulk_apply`)
- `status` (`pending`, `applying`, `applied`, `failed`, `rolled_back`)
- `target_policy_id` (nullable fk)
- `payload` (jsonb)
- `started_at` / `completed_at`
- `error_summary` (nullable text)
- timestamps

### 5.4 `box_access_operation_items`
Per-Box-call result rows for observability and rollback.

Key columns:
- `id`
- `operation_id` (fk)
- `grant_id` (nullable fk)
- `box_item_type` (`folder`, `file`)
- `box_item_id` (string)
- `action` (`create_collab`, `update_collab`, `delete_collab`)
- `request_payload` (jsonb)
- `response_payload` (jsonb)
- `status` (`pending`, `applied`, `failed`, `rolled_back`)
- `error_message` (nullable text)
- timestamps

### 5.5 `box_access_drift_findings`
Tracks mismatch between WRK desired permissions and Box actual state.

Key columns:
- `id`
- `policy_id` (fk)
- `grant_id` (nullable fk)
- `finding_type` (`missing_collaboration`, `extra_collaboration`, `role_mismatch`, `manual_box_change`)
- `severity` (`low`, `medium`, `high`)
- `expected_state` (jsonb)
- `actual_state` (jsonb)
- `detected_at`
- `resolved_at` (nullable)
- `resolved_by` (nullable fk users)
- `resolution_note` (nullable text)
- timestamps

### 5.6 `personal_workspaces`
Registry only (Tier 3 governance stays Box-native).

Key columns:
- `id`
- `user_id` (unique fk users)
- `box_folder_id` (string)
- `box_private_folder_id` (nullable string)
- `default_visibility` (`org_read`, `private_default`)
- `status` (`active`, `disabled`, `offboarding_review`)
- `last_scanned_at` (nullable timestamp)
- `metadata` (jsonb)
- timestamps

### 5.7 `document_promotions`
Tracks move-from-personal-to-WRK canonical promotion actions.

Key columns:
- `id`
- `workspace_id` (fk `personal_workspaces`)
- `source_box_file_id` (string)
- `source_box_parent_folder_id` (string)
- `destination_policy_id` (fk `box_access_policies`)
- `destination_box_folder_id` (string)
- `initiated_by` (fk users)
- `status` (`requested`, `validating`, `moving`, `completed`, `failed`)
- `promoted_document_id` (nullable fk to app document table)
- `reference_note_box_file_id` (nullable string)
- `frontmatter` (jsonb)
- `failure_reason` (nullable text)
- timestamps

### 5.8 `agent_box_scopes`
Explicit allowlist for agent file access.

Key columns:
- `id`
- `agent_id` (fk agents)
- `scope_type` (`tier1`, `tier2`, `project`, `workspace`)
- `box_folder_id` (string)
- `access_level` (`read`, `write`)
- `approved_by` (fk users)
- `expires_at` (nullable timestamp)
- `status` (`active`, `revoked`, `expired`)
- `metadata` (jsonb)
- timestamps

Indexes:
- index(`agent_id`, `status`)
- index(`box_folder_id`, `status`)

### 5.9 `offboarding_cases` and `offboarding_case_items`
Offboarding orchestration and asset triage.

`offboarding_cases`:
- `id`, `user_id`, `initiated_by`, `status` (`open`, `in_progress`, `blocked`, `completed`)
- `wrk_access_revoked_at`, `box_access_revoked_at` (nullable)
- `notes`, timestamps

`offboarding_case_items`:
- `id`, `offboarding_case_id`
- `box_item_type`, `box_item_id`, `box_path`
- `classification` (`project_related`, `personal`, `unknown`)
- `recommended_action` (`promote`, `archive`, `discard`, `review`)
- `action_status` (`pending`, `done`, `skipped`)
- `linked_project_id` (nullable)
- timestamps

## 6) Job and Command Architecture

### 6.1 Queue Jobs

1. `ApplyBoxAccessOperation`
- Input: `operation_id`
- Behavior: apply each grant delta to Box, update item statuses, finalize operation.
- Failure: mark failed, attempt rollback for already-applied items.

2. `ReconcileBoxAccessPolicy`
- Input: `policy_id`
- Behavior: compare WRK desired grants vs Box collaborations; write drift findings.

3. `IngestBoxCollaborationEvent`
- Input: webhook event row id
- Behavior: process collaboration changes, update grant state or create drift finding.

4. `PromoteWorkspaceDocument`
- Input: `promotion_id`
- Behavior:
  - validate source belongs to workspace owner
  - move file into destination WRK folder
  - write source reference note/metadata
  - trigger metadata re-sync/index

5. `RunOffboardingCase`
- Input: `offboarding_case_id`
- Behavior:
  - revoke WRK grants
  - apply Box revokes for managed tiers
  - scan workspace and create triage items
  - notify reviewers

6. `ExpireAgentBoxScopes`
- Behavior: revoke expired scopes and enforce in runtime cache.

### 6.2 Artisan Commands

- `php artisan box:access-apply --operation={id}`
- `php artisan box:reconcile-permissions {--policy=} {--full}`
- `php artisan box:reconcile-drift --resolve={finding_id}` (optional manual assist)
- `php artisan workspaces:scan-personal {--user_id=}`
- `php artisan workspaces:promote --promotion={id}`
- `php artisan offboarding:run {--case=}`
- `php artisan agents:expire-box-scopes`

### 6.3 Scheduler

- Every 5 minutes: `box:reconcile-permissions --policy=hot` (recently changed policies)
- Nightly: `box:reconcile-permissions --full`
- Daily: `workspaces:scan-personal`
- Hourly: `agents:expire-box-scopes`
- Every 10 minutes: `offboarding:run` for open/in-progress cases

## 7) API Surface (v1)

All endpoints authenticated; admin/manager authorization where needed.

### 7.1 Policies & Grants

`GET /api/v1/box/access/policies`
- Returns list with policy metadata, effective grants, drift count.

`POST /api/v1/box/access/policies/{policy}/grants`
- Request: `{subject_type, subject_id, wrk_permission, applies_to_subtree}`
- Response: `{operation_id, status}`

`PATCH /api/v1/box/access/grants/{grant}`
- Request: `{wrk_permission|box_role|state}`
- Response: `{operation_id, status}`

`DELETE /api/v1/box/access/grants/{grant}`
- Response: `{operation_id, status}`

### 7.2 Drift & Reconciliation

`GET /api/v1/box/access/drift?status=open`
- Returns unresolved findings with expected vs actual.

`POST /api/v1/box/access/reconcile`
- Request: `{policy_id? , full?}`
- Response: `{queued: true, run_id}`

### 7.3 Personal Workspaces

`GET /api/v1/workspaces/personal`
- Registry list for admins/managers.

`POST /api/v1/workspaces/personal/{user}/register`
- Request: `{box_folder_id, box_private_folder_id?, default_visibility}`

### 7.4 Document Promotion

`POST /api/v1/workspaces/promotions`
- Request:
  - `workspace_id`
  - `source_box_file_id`
  - `destination_policy_id` or `destination_box_folder_id`
  - `frontmatter` (`project_id`, `doc_type`, `sensitivity`, `owner_user_id`, etc.)
- Response: `{promotion_id, status: "requested"}`

`POST /api/v1/workspaces/promotions/{promotion}/execute`
- Response: `{queued: true}`

### 7.5 Agent Box Scopes

`POST /api/v1/agents/{agent}/box-scopes`
- Request: `{scope_type, box_folder_id, access_level, expires_at?}`
- Response: `{scope_id, status}`

`DELETE /api/v1/agents/{agent}/box-scopes/{scope}`
- Response: `{status: "revoked"}`

### 7.6 Offboarding

`POST /api/v1/offboarding/cases`
- Request: `{user_id, notes?}`
- Response: `{case_id, status: "open"}`

`POST /api/v1/offboarding/cases/{case}/start`
- Response: `{status: "in_progress"}`

`POST /api/v1/offboarding/cases/{case}/complete`
- Guard: all required items resolved.

## 8) UI States and Screens

### 8.1 Admin > Permissions (Control Plane)

Views:
- Policy list (tier, folder, active grants, drift status)
- Grant editor (subject, level, subtree)
- Operation log (last 50 operations)

States:
- `idle`
- `applying` (show spinner + operation id)
- `applied` (green confirmation)
- `failed` (error + retry)
- `drift_detected` (badge + diff modal)

### 8.2 Admin > Drift Review

Views:
- Drift queue table
- Diff inspector (`expected_state` vs `actual_state`)
- Actions: `apply WRK state to Box`, `accept Box change and update WRK`, `dismiss`

### 8.3 Workspace > Promote Document

Wizard:
1. Select source file
2. Select destination WRK folder
3. Add required frontmatter
4. Confirm move + reference note

States:
- `validating`
- `moving`
- `completed`
- `failed`

### 8.4 Intelligence > Agent Scope Manager

Views:
- Current scopes for selected agent
- Add scope modal (folder picker + read/write + expiry)
- Scope history/audit

States:
- `active`
- `expiring_soon`
- `revoked`
- `expired`

### 8.5 Admin > Offboarding

Views:
- Open cases
- Case timeline
- Asset triage (project-related files requiring promotion decision)

States:
- `open`
- `in_progress`
- `blocked`
- `completed`

## 9) Runtime Enforcement Rules

### 9.1 WRK-Managed Folder Access
- All WRK file operations check `box_access_grants` effective state.
- Runtime denies writes if grant state is not `applied`.

### 9.2 Agent Access Boundary
- Agent file operations require a matching active `agent_box_scopes` row.
- Tier 2 access requires explicit scope and valid approval trail.
- Workspace scope requires invoking user = workspace owner (or admin override).

### 9.3 Personal Workspace Isolation
- Agents cannot read another user workspace without explicit admin-approved scope.
- Default behavior: no cross-workspace traversal.

## 10) Failure Handling and Alerts

### 10.1 Permission Apply Failures
- Mark operation `failed`.
- Store per-item errors.
- Attempt rollback for any partial applies.
- Alert in admin UI and Slack ops channel.

### 10.2 Drift Events
- Create/open drift finding.
- Increment drift counters on policy.
- Alert if severity is `high` (restricted folder mismatch).

### 10.3 Promotion Failures
- Preserve source file unchanged.
- Mark promotion failed with reason.
- Keep retry action available.

## 11) Audit and Traceability

Every operation stores:
- actor
- requested change
- Box API call payload/response (sanitized)
- final state
- source docs/entities/agent where relevant

Audit targets:
- Permission changes
- Agent scope grants/revocations
- Promotions
- Offboarding actions

## 12) Rollout Plan (v1)

### Phase A: Schema + Read Path
- Add new tables.
- Build policy/grant read APIs.
- Add UI read-only views.

### Phase B: Write Path + Operations
- Implement grant create/update/revoke operation flow.
- Add synchronous apply + rollback behavior.
- Add operation logs.

### Phase C: Drift + Promotion + Offboarding
- Nightly reconciliation.
- Promotion wizard and job.
- Offboarding case workflow.

### Phase D: Agent Scopes
- Agent file scope table and runtime checks.
- Intelligence panel integration.

## 13) Acceptance Criteria

1. Permission changes for managed tiers either:
- complete and show `applied`, or
- fail with explicit reason; no silent partial success.

2. Nightly reconciliation surfaces all unmanaged Box-side ACL edits as drift.

3. Promotion flow produces:
- canonical moved file in WRK folder,
- source reference note/metadata pointer,
- promotion audit row.

4. Agent file calls are denied outside explicit scopes.

5. Offboarding case can revoke WRK-managed access and produce a promotion triage list before closure.

