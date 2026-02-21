# WRK v2 Schema Proposal (Frozen Draft)

Related:
- `docs/WRK_AI_Architecture_Brief_FINAL.md`
- `docs/WRK_V2_DATA_CONTRACT.md`
- `docs/BOX_TAXONOMY_AND_PERMISSION_MAPPING.md`

Date: February 20, 2026
Status: Draft frozen for Week 1-2 implementation
Owner: WRK v2 rollout

## 1) Scope and Constraints

- Postgres is the operational source of truth.
- Box is the source of truth for file metadata and ACL semantics.
- Existing WRK module tables remain active; v2 adds cross-domain linking and auditability layers.
- All new agent-facing writes require explicit provenance and actor attribution.

## 2) Canonical Table Groups

### Identity and Access

- `users` (existing): staff identity, role/access level, auth state.
- `teams` (future): optional grouping for delegated permissions.
- `entity_permissions` (new): row-level overrides for sensitive entities.
  - key fields: `entity_type`, `entity_id`, `subject_type`, `subject_id`, `permission`, `granted_by`.

### Core Domain Entities

- `people` (existing): contacts.
- `organizations` (existing): institutions/funders/partners.
- `meetings` (existing): internal/external meeting records.
- `projects` (existing): top-level + nested workstreams/events/products.
- `contracts` (new): service/client contract records (agreement, billing, reporting, compliance).
- `trips` (existing): travel plans and logistics.
- `grants` (existing): funding commitments, reporting.
- `actions` (existing): tasks/follow-ups.
- `decisions` (existing): durable decision records.

### Cross-Domain Linking

- Existing pivots remain in use (for example: `meeting_person`, `meeting_organization`, `project_person`, `project_organization`).
- `entity_links` (new): generic typed links for relationships not covered by module-specific pivot tables.
  - key fields: `from_type`, `from_id`, `to_type`, `to_id`, `link_type`, `created_by`, `source`.

### Files and Knowledge

- `box_items` (new, implemented): mirrored Box file/folder metadata.
  - key fields: `box_item_id`, `box_item_type`, `name`, `parent_box_folder_id`, `permissions`, `modified_at`.
- `project_documents` (existing): app-facing document records used by current project workflows.
- `kb_index` (existing): searchable text index for retrieval.
- `knowledge_facts` (future): extracted structured facts with source refs and confidence.
- `knowledge_insights` (future): synthesized insights with review state.

### Agent and Workflow Control

- `box_webhook_events` (new, implemented): append-only ingestion log for Box webhook deliveries.
- `agent_runs` (future): one row per run with prompt/model/runtime metadata.
- `agent_steps` (future): internal execution trace per tool/action.
- `agent_approvals` (future): human gate decisions for sensitive actions.

### Audit and Eventing

- `audit_logs` (future): normalized cross-system change log.
- `integration_events` (future): idempotent event inbox/outbox pattern.

## 3) Relationship Map (Logical)

```
users
  -> meetings (owner/participant via meeting_user)
  -> projects (owner/contributor)
  -> trips (traveler/creator)
  -> actions (assignee/creator)

organizations <-> people
organizations <-> meetings
organizations <-> projects
organizations <-> grants
organizations <-> contracts

meetings <-> people
meetings <-> organizations
meetings <-> projects
meetings -> actions
meetings -> decisions

projects <-> people
projects <-> organizations
projects <-> grants
projects <-> trips
projects <-> contracts

contracts -> organizations (required)
contracts -> projects (optional)
contracts -> grants (optional)

box_items -> (mapped to) project_documents
project_documents -> kb_index
```

## 4) Canonical ID Policy

- Keep integer primary keys in DB for compatibility/performance.
- Add stable external IDs at API layer using prefixes from `docs/WRK_V2_DATA_CONTRACT.md` (`usr_`, `org_`, `per_`, `mtg_`, etc.).
- Add `ctr_` prefix for contracts.
- Store external IDs in dedicated columns as each API surface is upgraded.

## 5) Required v2 Invariants

- Every AI/agent output must store `source_refs`.
- Every automated write must include `actor`, `source_system`, and timestamp.
- Document retrieval must enforce both WRK visibility and projected Box ACL visibility.
- All webhook/event ingestion must be idempotent by unique delivery/event ID.

## 6) Week 1-2 Build Slice

- Implemented now:
  - `box_items`
  - `box_webhook_events`
  - Box metadata sync command/job/webhook scaffold
- Next schema slice:
  - `contracts`
  - `entity_links`
  - `agent_runs`
  - `agent_approvals`
  - `knowledge_facts` / `knowledge_insights`
