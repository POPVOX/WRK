# WRK v2 Data Contract

Related:
- `docs/WRK_AI_Architecture_Brief_FINAL.md`
- `docs/WRK_V2_SCHEMA_PROPOSAL.md`
- `docs/BOX_TAXONOMY_AND_PERMISSION_MAPPING.md`

## Goal
Define a stable, shared data model for WRK v2 so product, integrations, and agents all operate on the same canonical entities.

## Scope
This contract covers:
- canonical entities and IDs
- ownership/source of truth rules
- required relationships
- permission model
- event and provenance requirements

## Core Principles
- Postgres is the system of record for operational entities.
- Box is the system of record for file objects and file ACLs.
- Knowledge/AI artifacts are derived and reproducible.
- Every AI output must include source provenance.
- Every cross-entity link must use stable IDs, never free-text names.

## Canonical IDs
Use globally unique IDs with entity prefixes:
- `usr_` (staff user)
- `org_` (organization)
- `per_` (person/contact)
- `cal_` (calendar account)
- `evt_` (calendar event)
- `mtg_` (meeting)
- `prj_` (project)
- `ctr_` (contract)
- `trp_` (trip)
- `gnt_` (grant/funding)
- `doc_` (document record)
- `act_` (action/task)
- `dec_` (decision)
- `ins_` (insight)
- `fact_` (fact)

Use these IDs in APIs, prompts, logs, and automation payloads.

## Entity Contract

### Staff Users (`usr_`)
Required fields:
- `id`, `email`, `name`, `role`, `access_level`, `status`

Key relationships:
- owns meetings/actions
- participates in trips/projects
- has one or more calendar accounts

### Organizations (`org_`)
Required fields:
- `id`, `name`, `org_type`, `status`

Key relationships:
- has many people
- linked to meetings, projects, grants

### People / Contacts (`per_`)
Required fields:
- `id`, `name`, `organization_id` (nullable), `owner_user_id` (nullable), `status`

Key relationships:
- linked to meetings, projects, organizations, funders

### Calendar Accounts / Events (`cal_`, `evt_`)
Required fields:
- account: `id`, `user_id`, `provider`, `provider_account_id`, `status`
- event: `id`, `calendar_account_id`, `provider_event_id`, `start_at`, `end_at`, `title`

Key relationships:
- events map to meetings where applicable

### Meetings (`mtg_`)
Required fields:
- `id`, `title`, `scheduled_start_at`, `scheduled_end_at` (nullable), `status`, `source`

Key relationships:
- many-to-many: people, organizations, projects
- optional one-to-one: source calendar event
- has many: actions, decisions, meeting documents

### Projects (`prj_`)
Required fields:
- `id`, `name`, `category`, `status`, `owner_user_id` (nullable)

Categories:
- `top_level`, `event`, `product`, `campaign`, `other`

Key relationships:
- parent-child project hierarchy
- linked to meetings, documents, grants, travel

### Contracts (`ctr_`)
Required fields:
- `id`, `organization_id`, `name`, `status`, `effective_start_at` (nullable), `effective_end_at` (nullable), `visibility`

Key relationships:
- belongs to one organization (required)
- optional links to projects and grants
- has many supporting documents (agreements, billing, reporting, compliance)

### Travel (`trp_`)
Required fields:
- `id`, `name`, `status`, `start_at`, `end_at`, `created_by`

Key relationships:
- many travelers (users)
- optional links: projects, organizations, people
- has many segments, expenses, documents

### Funders / Grants (`gnt_`)
Required fields:
- `id`, `organization_id`, `name`, `status`, `visibility`, `amount` (nullable)

Key relationships:
- linked to projects, meetings, people, reporting requirements, documents

### Documents (`doc_`)
Required fields:
- `id`, `source` (`box` | `upload` | `link`), `external_id` (for Box file id), `title`, `mime_type`, `visibility`

Optional metadata:
- `project_id`, `meeting_id`, `organization_id`, `person_id`, `grant_id`, `contract_id`, `trip_id`, `doc_type`, `topics`

Rules:
- Box file metadata is mirrored into WRK document records.
- WRK document ACL must be a projection of Box ACL + WRK policy constraints.

### Actions / Decisions (`act_`, `dec_`)
Required fields:
- action: `id`, `title`, `status`, `assigned_user_id` (nullable), `due_at` (nullable)
- decision: `id`, `title`, `summary`, `decided_at` (nullable)

Key relationships:
- each may link to meetings/projects/organizations/people/grants/contracts

### Knowledge Artifacts (`fact_`, `ins_`)
Required fields:
- `id`, `type`, `content`, `source_refs[]`, `confidence`, `created_by` (`system` | `user`)

Rules:
- no artifact without at least one `source_ref`
- `source_ref` points to canonical IDs (`doc_`, `mtg_`, `prj_`, etc.)

## Source of Truth Matrix
- Operational records: Postgres
- Files/versions/shares: Box
- Search index/embeddings: derived from Postgres + Box content
- Slack threads/messages: external channel copy; important outcomes persist to Postgres

## Permission Contract
- AuthN: WRK user identity
- AuthZ: WRK RBAC + Box ACL mirror for docs
- Retrieval must enforce ACL at query time:
  - entity row-level visibility
  - document ACL visibility
  - contract visibility (`management`, `admin`)
  - grant visibility (`all`, `management`, `admin`)

## Event Contract
Every integration event must include:
- `event_id`
- `event_type`
- `occurred_at`
- `actor` (`user_id` or service identity)
- `entity_refs[]` (canonical IDs)
- `source_system`

Events are append-only and idempotent by `event_id`.

## Provenance Contract
AI outputs must store:
- `prompt_version`
- `model`
- `generated_at`
- `source_refs[]`
- `confidence`
- `review_state` (`draft`, `approved`, `rejected`)

## v2 MVP Acceptance Criteria
- All primary modules use canonical IDs.
- All documents have a WRK `doc_` record mapped to Box file IDs.
- KB search works on SQLite (dev) and Postgres (staging/prod).
- Slack actions create/update canonical records through WRK APIs.
- AI answers include citations to canonical source refs.
