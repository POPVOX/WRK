# WRK AI Architecture Brief v2 (Final Draft)

**Organization:** POPVOX Foundation  
**Date:** February 20, 2026  
**Document Type:** Strategy + architecture operating brief  
**Audience:** WRK core builders, team leads, and future implementing partners (nonprofits and legislative offices)

Related docs:
- `docs/WRK_AI_Architecture_Brief_BOARD_ONE_PAGER.md`
- `docs/WRK_V2_DATA_CONTRACT.md`
- `docs/WRK_V2_SCHEMA_PROPOSAL.md`
- `docs/BOX_TAXONOMY_AND_PERMISSION_MAPPING.md`

## 1. Decision Summary

WRK should continue with a **knowledge-first, progressively autonomous** architecture:

- Postgres is the system of record for operational entities and relationships.
- Box is the system of record for files, file metadata, and effective ACL semantics.
- Slack is the first conversational interface for agent workflows.
- AI autonomy expands only as quality, governance, and audit controls prove reliable.

This remains the right path for a small, high-accountability team and aligns with current industry/governance direction (see Sources `S1-S12`).

## 2. Current vs Planned in WRK v2 (As of February 20, 2026)

| Capability | Status | Notes |
|---|---|---|
| Postgres production baseline | Implemented | Migration completed; runbook in place. |
| Canonical data contract (`usr_`, `org_`, `prj_`, etc.) | Implemented | `docs/WRK_V2_DATA_CONTRACT.md` exists and is active. |
| Contracts canonical prefix (`ctr_`) | Implemented in contract/docs | Contracts now treated as first-class in v2 docs; DB tables are next. |
| Box webhook ingestion + event log | Implemented scaffold | `box_webhook_events` table + webhook controller + queued processor. |
| Box metadata mirror | Implemented scaffold | `box_items` table + folder sync command/job. |
| Box item -> WRK document canonical mapping | Planned | Next build slice required for full retrieval and citation pathing. |
| ACL projection into retrieval pipeline | Planned (partial policy defined) | Policy defined in docs; enforcement wiring in retrieval path is next. |
| Entity linking (`entity_links`) | Planned | Priority for GraphRAG-style retrieval quality. |
| Knowledge facts/insights (`knowledge_facts`, `knowledge_insights`) | Planned | Required for durable multi-agent shared memory. |
| Agent run tracing (`agent_runs`, `agent_steps`) | Planned | Needed before action-taking agents. |
| Approval workflow (`agent_approvals`) | Planned | Governance control point for sensitive writes. |
| Slack action workflow integration | Planned | MVP scoped in docs with 3 workflows. |

## 3. Updated Domain Model (WRK-First)

WRK v2 should operate with these first-class domains:

- `Contacts_Orgs`
- `Meetings`
- `Projects`
- `Contracts`
- `Funders`
- `Travel`
- `Team`
- `Knowledge`
- `Admin_Restricted`

### Contracts Domain Policy

Contracts should remain separate from projects at storage and policy layers:

- **Entity:** `ctr_*`
- **Required relationship:** `contract -> organization`
- **Optional relationships:** `contract -> project`, `contract -> grant`
- **Default visibility:** `management`
- **Escalated visibility:** `admin` for legal/compensation/vendor-sensitive records

## 4. Architecture Principles (Operational)

### 4.1 Progressive Autonomy

Start at read-only and draft-only behavior. Expand autonomy by policy, not by model capability.

### 4.2 Provenance-First Output

Every AI output must include: `prompt_version`, `model`, `source_refs[]`, `confidence`, and `review_state`.

### 4.3 Canonical Identity Everywhere

All services, logs, prompts, and event payloads must reference stable IDs (`usr_`, `org_`, `prj_`, `ctr_`, `gnt_`, `doc_`).

### 4.4 ACL-Aware Retrieval

Retrieval must enforce both WRK entity visibility and Box-derived document ACL visibility at query time.

### 4.5 Event-Driven + Idempotent Integrations

All connector events must be append-only and idempotent by unique delivery/event ID.

## 5. Target System Shape (2026)

### 5.1 Core Components

- **Postgres:** entities, links, policy flags, run logs, approvals, audit records.
- **Box:** file hierarchy, file/folder metadata, file ACL source of truth.
- **Queue workers:** ingestion, normalization, linking, indexing, notifications.
- **Retrieval layer:** hybrid keyword + structured link traversal; permission-filtered.
- **Slack interface:** command/event entrypoint, approval prompts, concise responses with citations.

### 5.2 Event Flow Baseline

1. External event received (Box/Slack/Calendar).
2. Event persisted (immutable envelope + payload).
3. Worker normalizes and upserts canonical entities.
4. Link resolver updates cross-domain relationship graph.
5. Indexer updates retrieval artifacts.
6. Agent response layer uses only permission-filtered sources.

## 6. Governance and Security Baseline

### 6.1 Data Classification and Handling

- **Class A (Admin Restricted):** HR, legal, compensation, privileged contract content.
- **Class B (Management):** funder strategy, contract billing/reporting details, sensitive negotiations.
- **Class C (Team):** standard project and meeting operations.

### 6.2 Controls Required Before Action-Taking Agents

- Human approval gates on all external writes.
- Explicit run log for every model/tool invocation.
- Immutable audit trail for changes and approvals.
- Incident response ownership + on-call role for automation failures.
- Key rotation schedule and access-review cadence for integration tokens.

### 6.3 Retention and Deletion Policy (Baseline)

- Event envelopes and run logs retained long enough for audit and debugging (set policy by class).
- Source-linked artifacts must be rehydratable from canonical source records.
- Deletion workflows must support both business deletion and legal hold exceptions.

## 7. Quality Metrics and Phase Gates

Use measurable gates to control autonomy expansion.

### 7.1 Core Reliability Metrics

- Citation coverage (% AI outputs with at least one valid canonical source ref).
- Retrieval precision at top-k for internal benchmark queries.
- Permission leakage rate (target: zero confirmed leakage incidents).
- Human correction rate for extracted facts and drafted actions.
- Median and P95 response latency by workflow.

### 7.2 Suggested Exit Gates

**Phase 1 -> Phase 2 gate**

- Citation coverage >= 98%
- Permission leakage incidents = 0 over trailing 60 days
- Fact correction rate <= 20% on sampled outputs
- Median response time <= 30 seconds for top workflows

**Phase 2 -> Phase 3 gate**

- Approval override/rejection rate <= 10% for mature workflows
- Stable run success rate >= 99% over trailing 30 days
- Audit completeness = 100% for sampled runs

## 8. Phased Roadmap

### Phase 1: Knowledge Foundation (Current to Q2 2026)

Focus: Improve knowledge quality and retrieval trust before autonomy.

- Complete Box metadata sync + permission projection implementation.
- Ship `entity_links` for missing cross-domain relationships.
- Add `knowledge_facts` extraction with required source refs.
- Integrate structured links into meeting prep and knowledge hub retrieval.
- Build quality dashboard for usage/corrections/latency.

**Autonomy level:** 1-2 (operator/collaborator).

### Phase 2: Governed Agents (Q3 to Q4 2026)

Focus: Introduce write-capable workflows with approvals.

- Deploy `agent_runs`, `agent_steps`, `agent_approvals`.
- Publish explicit skill definitions with data/tool/action boundaries.
- Roll out draft-and-approve flows in Slack for top workflows.
- Capture edit deltas to improve prompts/skills.

**Autonomy level:** 2-3 (collaborator/consultant).

### Phase 3: Institutional Colleagues (2027+)

Focus: Increase autonomy for low-risk, high-confidence routine actions.

- Multi-agent coordination over shared event and knowledge layers.
- Policy-driven autonomy expansion for selected actions.
- Complete cross-system audit and compliance tooling.

**Autonomy level:** 3-4 (consultant/approver).

## 9. Six-Week Execution Alignment (Immediate)

### Weeks 1-2

- Freeze canonical schema for core + contracts + links.
- Finalize Box taxonomy and ACL mapping.
- Confirm operational runbooks and reliability checks.

### Weeks 3-4

- Implement Box metadata sync and webhook receiver fully.
- Build indexing/chunking/link extraction pipeline with permission-aware retrieval.

### Weeks 5-6

- Launch Slack agent MVP with 3 workflows:
  - Meeting prep brief (read-only)
  - Post-meeting follow-up extraction (draft + approval)
  - Weekly project/funder pulse (read-only digest)

## 10. Cost and Operations Assumptions (Initial)

### 10.1 Load and Throughput

- Queue workers run continuously in staging/production.
- Metadata sync volume and reindex cadence scale with Box change rate.
- Slack and retrieval latency targets require bounded indexing lag.

### 10.2 Cost Drivers

- Model inference (chat + extraction + summarization).
- Retrieval/storage growth (index + metadata + logs).
- Worker runtime and retry overhead.

### 10.3 Budget Controls

- Per-workflow token/cost budgets.
- Tiered model routing (cheaper model for low-risk transforms).
- Daily and monthly usage alerts with automated throttling rules.

## 11. Portability Requirements

To support nonprofit and legislative reuse, keep components replaceable:

- Storage abstraction: Box now, Drive/SharePoint later.
- Model abstraction: provider-swappable inference layer.
- Identity/policy abstraction: mapping to varied org role structures.

## 12. Source Notes and Validation

This brief preserves the source list from the original submission. Some market/adoption figures should be re-verified at publication time.

### Sources

- `S1` McKinsey: *The Agentic Organization* (2025)
- `S2` McKinsey: *Seizing the Agentic AI Advantage* (2025)
- `S3` Deloitte Insights: *Tech Trends 2026* (Agentic AI strategy section)
- `S4` MIT Sloan Management Review: *The Emerging Agentic Enterprise* (2025)
- `S5` World Economic Forum: *Using AI Agents in Organizations Today and in the Future* (Dec 2025)
- `S6` AWS: *The Agentic AI Security Scoping Matrix* (2025)
- `S7` Knight First Amendment Institute: *Levels of Autonomy for AI Agents*
- `S8` Anthropic: MCP and Agent Skills publications (2025)
- `S9` U.S. GAO: *Generative AI Use and Management at Federal Agencies* (GAO-25-107653)
- `S10` CDT / FPF state legislative AI analyses (2025)
- `S11` Government AI outlook analyses (SAS, Federal News Network, 2025)
- `S12` Implementation practitioner analyses listed in original brief
