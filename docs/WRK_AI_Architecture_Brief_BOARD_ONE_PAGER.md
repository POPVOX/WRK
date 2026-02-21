# WRK AI Architecture v2 - Board One-Pager

**Date:** February 20, 2026  
**Purpose:** Executive snapshot of architecture direction, risk posture, and 6-week delivery plan.

## 1) Strategic Decision

WRK v2 will be built as an **AI-ready operating platform** for logistics and institutional knowledge, with:

- **Postgres** as system of record for entities, relationships, workflows, and auditability.
- **Box** as system of record for files and file permissions.
- **Slack** as first conversational interface for agent workflows.
- **Progressive autonomy**: agents move from read-only to action-taking only after governance and quality gates are met.

## 2) Why This Approach

- Aligns with current organizational AI practice: knowledge quality and governance drive safe adoption.
- Fits POPVOX operating context: small team, high accountability, cross-domain coordination.
- Preserves portability for future nonprofit/legislative deployments.

## 3) Current State (As of February 20, 2026)

- Postgres migration complete and app access restored.
- v2 data contract and schema direction documented.
- Box integration scaffold implemented:
  - metadata mirror table (`box_items`)
  - webhook event log (`box_webhook_events`)
  - sync command/job + webhook processing pipeline
- Contracts now defined as a first-class domain (`ctr_*`) in architecture/data docs.

## 4) Target v2 Domains

- Contacts/Organizations
- Meetings/Calendar
- Projects
- Contracts
- Funders/Grants
- Travel
- Team/Policy
- Knowledge layer across all domains

## 5) Governance and Risk Controls

- Every AI output must include source references and review state.
- Retrieval must enforce WRK visibility and Box ACL-derived visibility.
- Sensitive writes require human approval and logged execution trace.
- Integration events must be idempotent and auditable.

## 6) Key Risks and Mitigations

- **RDS/network reliability risk**
  - Mitigation: cutover runbook, health checks, queue monitoring.
- **Box permission mapping complexity**
  - Mitigation: explicit taxonomy + ACL projection rules + domain defaults.
- **Search quality/citation trust risk**
  - Mitigation: entity linking (`entity_links`), source-ref requirements, quality metrics.
- **Agent trust/adoption risk**
  - Mitigation: phased autonomy, approvals, transparent run logs.

## 7) Six-Week Plan

### Weeks 1-2

- Freeze canonical schema and relationship map.
- Finalize Box taxonomy and permission mapping.
- Stabilize production ops baseline.

### Weeks 3-4

- Complete Box metadata sync and webhook ingestion path.
- Implement indexing/chunking/retrieval with permission filtering.

### Weeks 5-6

- Launch Slack agent MVP with 3 workflows:
  - Meeting prep brief (read-only)
  - Post-meeting follow-up extraction (draft + approval)
  - Weekly project/funder pulse (read-only digest)

## 8) Success Criteria for MVP

- Staff can locate key documents/decisions from chat in under 30 seconds.
- Meeting/project context is auto-linked across people, orgs, and files.
- Box file changes sync and reindex automatically.
- Slack agent executes top recurring workflows safely with approvals.
- Clear audit trail exists for all automated actions.

## 9) Board-Level Asks

- Confirm governance posture: progressive autonomy with mandatory approval gates for sensitive actions.
- Endorse v2 domain model including first-class `Contracts` domain.
- Approve focus on knowledge quality and auditability before broader autonomy expansion.
