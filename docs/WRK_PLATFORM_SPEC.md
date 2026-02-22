---
title: WRK Platform — Document Management & Relationship Mapping Specification
version: 1.1
status: Draft
author: Marci Harris / Claude (AI-assisted)
stack: Laravel / PHP / Livewire / Forge / AWS / PostgreSQL
last_updated: 2026-02-22
---

# WRK Platform Specification
## Document Management, Structured Metadata, and Relationship Mapping

---

## 1. PURPOSE

The WRK platform is the front-end application for POPVOX Foundation's operational knowledge base. It serves two audiences:

1. **Human staff** — who need to create, edit, browse, and cross-reference organizational documents through an intuitive web interface.
2. **AI agents** — who need structured, consistent, machine-readable data to assist with tasks across the organization.

The platform manages structured markdown documents stored in Box (via Box API), with PostgreSQL as the relational backbone for metadata, cross-references, and search. The markdown files in Box remain the canonical content layer; the database is the canonical relationship and metadata layer. This is an incremental evolution of WRK's existing schema, not a greenfield rewrite.

This spec covers three phases:

- **Phase 1 (MVP):** Document CRUD with structured frontmatter, folder-aware UI, basic cross-referencing via dropdown selectors.
- **Phase 2:** Relationship graph, advanced cross-referencing, timeline views, and AI agent API.
- **Phase 3:** Full knowledge graph, automated relationship inference, activity feeds, and reporting dashboards.

---

## 2. ARCHITECTURE OVERVIEW

```
┌─────────────────────────────────────────────────┐
│                  WRK Platform                    │
│           (Laravel + Livewire + Forge)           │
├─────────────────────────────────────────────────┤
│                                                  │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐ │
│  │ Livewire │  │ Livewire │  │   Livewire    │ │
│  │  Doc     │  │ Relation │  │   Browse /    │ │
│  │  Editor  │  │ Manager  │  │   Search      │ │
│  └────┬─────┘  └────┬─────┘  └──────┬────────┘ │
│       │              │               │           │
│  ┌────┴──────────────┴───────────────┴────────┐ │
│  │          Laravel Service Layer              │ │
│  │  DocService / RelationService / AuthService │ │
│  └────┬──────────────┬───────────────┬────────┘ │
│       │              │               │           │
│  ┌────┴────┐   ┌─────┴─────┐  ┌─────┴────────┐ │
│  │ Box API │   │ PostgreSQL│  │ Search Index │ │
│  │ (files) │   │ (metadata │  │ (Meilisearch │ │
│  │         │   │  + rels)  │  │  or pg_trgm) │ │
│  └─────────┘   └───────────┘  └──────────────┘ │
│                                                  │
├─────────────────────────────────────────────────┤
│              AI Agent API (Phase 2)              │
│         REST + structured JSON responses         │
└─────────────────────────────────────────────────┘
```

### Key Principle: Dual Storage

| What | Where | Why |
|------|-------|-----|
| Document content (markdown body) | Box (files) | Human-readable, version-controlled, accessible outside the app |
| Structured metadata (frontmatter fields) | PostgreSQL | Queryable, indexable, enforceable schemas |
| Relationships (cross-references) | PostgreSQL (`relationships` table) | Typed, bidirectional, queryable graph |
| File reference | PostgreSQL (`documents.box_file_id`) | Links DB record to Box file |

When a document is saved through the platform, the system:
1. Upserts metadata and relationships in PostgreSQL
2. Enqueues an idempotent Box sync outbox job
3. Worker writes/updates markdown in Box (content + regenerated YAML frontmatter)
4. Re-indexes for search and records sync status

When a document is read, the system:
1. Loads metadata and relationships from PostgreSQL (fast)
2. Fetches markdown body from Box on demand (for the editor), or cached content when fresh

### 2.1 Compatibility With Current WRK Schema

WRK already has operational tables in production. This spec adds a document graph layer that coexists with them.

| Existing Table (Current WRK) | Role Today | v1.1 Handling |
|---|---|---|
| `projects`, `organizations`, `people`, `meetings`, `trips`, `actions`, `project_tasks` | Core operational records | Remain canonical for operational workflows |
| `project_documents`, `trip_documents` | Domain document records | Continue to run; bridge to new `documents` layer with mapping fields |
| `box_items`, `box_webhook_events`, `box_project_document_links` | Box metadata + webhook ingest | Reused directly; no replacement |
| `agents`, `agent_permissions`, `agent_suggestions` | Agent governance and execution | Reused; relationship graph is additive context |

Implementation rule:
- Do not break existing pages by swapping table dependencies all at once.
- Add adapters and backfills, then migrate read paths incrementally per module.

### 2.2 Entity Reference Standard

All cross-entity edges use a canonical tuple:
- `entity_type` (enum-like string)
- `entity_id` (BIGINT)

Allowed entity types:
- `document`, `folder`, `project`, `funder`, `contract`, `organization`, `person`, `meeting`, `trip`, `action`

---

## 3. DATA MODEL

### 3.1 Core Tables

#### `folders`

Represents the top-level WRK folder taxonomy. Seeded from the existing Box structure.

```sql
CREATE TABLE folders (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,          -- "PROJECTS", "FUNDERS", etc.
    box_folder_id   VARCHAR(64) NOT NULL UNIQUE,    -- Box folder ID
    parent_id       BIGINT REFERENCES folders(id),  -- for subfolder nesting
    folder_type     VARCHAR(64) NOT NULL,           -- enum: see below
    description     TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- folder_type enum values:
-- 'root'          (WRK itself)
-- 'category'      (PROJECTS, FUNDERS, CONTRACTS, etc.)
-- 'project'       (individual project folder under PROJECTS)
-- 'funder'        (individual funder folder under FUNDERS)
-- 'contract'      (individual contract folder under CONTRACTS)
-- 'organization'  (individual org folder under CONTACTS_ORGS)
-- 'team_member'   (individual person folder under TEAM)
-- 'meeting_series'(recurring meeting series under MEETINGS)
-- 'trip'          (individual trip folder under TRAVEL)
-- 'knowledge'     (subcategory under KNOWLEDGE)
-- 'admin'         (subcategory under ADMIN_RESTRICTED)
```

#### `documents`

Every managed markdown file in the system.

```sql
CREATE TABLE documents (
    id              BIGSERIAL PRIMARY KEY,
    folder_id       BIGINT NOT NULL REFERENCES folders(id),
    box_file_id     VARCHAR(64) NOT NULL UNIQUE,    -- Box file ID
    doc_type        VARCHAR(64) NOT NULL,           -- enum: see below
    title           VARCHAR(512) NOT NULL,
    slug            VARCHAR(512) NOT NULL,           -- URL-friendly identifier
    status          VARCHAR(32) DEFAULT 'active',    -- active/archived/draft
    content_body    TEXT,                            -- cached markdown body (optional)
    content_hash    VARCHAR(64),                     -- SHA-256 of Box content for cache invalidation
    metadata        JSONB NOT NULL DEFAULT '{}',     -- all frontmatter fields as JSON
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_documents_folder ON documents(folder_id);
CREATE INDEX idx_documents_doc_type ON documents(doc_type);
CREATE INDEX idx_documents_metadata ON documents USING GIN(metadata);

-- doc_type enum values:
-- 'org_info'       (ORG_INFO.md — singleton)
-- 'folder_readme'  (README.md files in each folder)
-- 'project_brief'  (BRIEF.md in project folders)
-- 'funder_profile' (PROFILE.md in funder folders)
-- 'contract_summary' (SUMMARY.md in contract folders)
-- 'org_profile'    (PROFILE.md in contacts_orgs folders)
-- 'team_profile'   (PROFILE.md in team folders)
-- 'meeting_note'   (individual meeting notes)
-- 'trip_record'    (individual trip records)
-- 'knowledge_doc'  (docs in KNOWLEDGE)
-- 'general'        (any other managed document)
```

#### `relationships`

The cross-referencing backbone. Every relationship is stored as a typed, directional edge.

```sql
CREATE TABLE relationships (
    id              BIGSERIAL PRIMARY KEY,
    source_type     VARCHAR(64) NOT NULL,   -- see entity_type standard in Section 2.2
    source_id       BIGINT NOT NULL,
    target_type     VARCHAR(64) NOT NULL,   -- see entity_type standard in Section 2.2
    target_id       BIGINT NOT NULL,
    relation_type   VARCHAR(64) NOT NULL,   -- enum: see below
    metadata        JSONB DEFAULT '{}',     -- extra context (role, notes, etc.)
    created_by      BIGINT REFERENCES users(id),
    updated_by      BIGINT REFERENCES users(id),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ,

    CHECK (NOT (source_type = target_type AND source_id = target_id))
);

CREATE UNIQUE INDEX uq_relationships_active
ON relationships(source_type, source_id, target_type, target_id, relation_type)
WHERE deleted_at IS NULL;

CREATE INDEX idx_rel_source ON relationships(source_type, source_id, deleted_at);
CREATE INDEX idx_rel_target ON relationships(target_type, target_id, deleted_at);
CREATE INDEX idx_rel_type ON relationships(relation_type);
CREATE INDEX idx_rel_metadata ON relationships USING GIN(metadata);

-- relation_type enum values:
-- 'funded_by'          project/document → funder
-- 'funds'              funder → project/document (inverse)
-- 'governed_by'        project/document → contract
-- 'governs'            contract → project/document (inverse)
-- 'partner_on'         organization/person → project
-- 'has_partner'        project → organization/person (inverse)
-- 'team_member_of'     person → project (with metadata.role)
-- 'has_team_member'    project → person (inverse)
-- 'related_to'         generic bidirectional
-- 'child_of'           hierarchical (e.g., subproject)
-- 'parent_of'          hierarchical inverse
-- 'references'         one doc references another
-- 'meeting_for'        meeting → project/funder/organization
-- 'trip_for'           trip → project/organization
-- 'deliverable_of'     document → contract/project
```

#### `users`

```sql
CREATE TABLE users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    role            VARCHAR(32) NOT NULL DEFAULT 'staff',  -- admin/staff/viewer/ai_agent
    password        VARCHAR(255),                          -- nullable for SSO users
    team_profile_id BIGINT REFERENCES documents(id),       -- link to their TEAM profile doc
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

#### `activity_log`

Tracks all changes for audit trail and AI context.

```sql
CREATE TABLE activity_log (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id),
    action          VARCHAR(64) NOT NULL,   -- 'created', 'updated', 'deleted', 'relationship_added', etc.
    entity_type     VARCHAR(64) NOT NULL,   -- any entity type from Section 2.2
    entity_id       BIGINT NOT NULL,
    changes         JSONB,                  -- diff of what changed
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_activity_entity ON activity_log(entity_type, entity_id);
CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_created ON activity_log(created_at);
```

#### `sync_outbox`

Tracks cross-system writes (especially Box sync) as retryable, idempotent jobs.

```sql
CREATE TABLE sync_outbox (
    id                  BIGSERIAL PRIMARY KEY,
    operation           VARCHAR(64) NOT NULL,      -- 'document_sync_to_box', etc.
    entity_type         VARCHAR(64) NOT NULL,      -- usually 'document'
    entity_id           BIGINT NOT NULL,
    dedupe_key          VARCHAR(255) NOT NULL,     -- stable idempotency key
    payload             JSONB NOT NULL DEFAULT '{}',
    status              VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending/running/failed/synced
    attempt_count       INTEGER NOT NULL DEFAULT 0,
    next_attempt_at     TIMESTAMPTZ DEFAULT NOW(),
    last_error          TEXT,
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW(),

    UNIQUE(dedupe_key)
);

CREATE INDEX idx_sync_outbox_status ON sync_outbox(status, next_attempt_at);
```

### 3.2 Document Type Schemas

Each `doc_type` has a defined set of frontmatter fields. These are enforced by the platform when editing and stored in the `metadata` JSONB column.

The schemas below map directly to the YAML frontmatter templates already deployed in Box.

#### Project Brief (`project_brief`)

```json
{
    "project_name": { "type": "string", "required": true, "label": "Project Name" },
    "short_name": { "type": "string", "required": false, "label": "Abbreviation" },
    "tagline": { "type": "string", "required": true, "label": "One-line Description" },
    "status": {
        "type": "enum",
        "required": true,
        "options": ["Active", "Planning", "Paused", "Complete"],
        "label": "Status"
    },
    "start_date": { "type": "date", "required": false, "label": "Start Date" },
    "end_date": { "type": "date_or_string", "required": false, "label": "End Date", "allow_values": ["Ongoing"] },
    "lead": { "type": "relation", "relation_target": "team_profile", "required": false, "label": "Project Lead" },
    "team": {
        "type": "relation_array",
        "relation_target": "team_profile",
        "required": false,
        "label": "Team Members",
        "metadata_fields": { "role": { "type": "string", "label": "Role on Project" } }
    },
    "funders": { "type": "relation_array", "relation_target": "funder_profile", "required": false, "label": "Funders" },
    "contracts": { "type": "relation_array", "relation_target": "contract_summary", "required": false, "label": "Contracts" },
    "partners": { "type": "relation_array", "relation_target": "org_profile", "required": false, "label": "Partners" },
    "focus_area": {
        "type": "enum",
        "required": false,
        "options": ["Capacity", "Innovation", "Engagement"],
        "label": "Focus Area"
    },
    "website": { "type": "url", "required": false, "label": "Website" }
}
```

#### Funder Profile (`funder_profile`)

```json
{
    "funder_name": { "type": "string", "required": true, "label": "Funder Name" },
    "short_name": { "type": "string", "required": false, "label": "Abbreviation" },
    "type": {
        "type": "enum",
        "required": true,
        "options": ["Foundation", "Government", "Individual", "Corporate"],
        "label": "Funder Type"
    },
    "status": {
        "type": "enum",
        "required": true,
        "options": ["Active", "Prospective", "Past"],
        "label": "Relationship Status"
    },
    "primary_contact": { "type": "contact", "required": false, "label": "Primary Contact" },
    "website": { "type": "url", "required": false, "label": "Website" },
    "total_awarded": { "type": "currency", "required": false, "label": "Total Awarded" },
    "projects_funded": { "type": "relation_array", "relation_target": "project_brief", "required": false, "label": "Projects Funded" },
    "contracts": { "type": "relation_array", "relation_target": "contract_summary", "required": false, "label": "Contracts" }
}
```

#### Contract Summary (`contract_summary`)

```json
{
    "contract_name": { "type": "string", "required": true, "label": "Contract Name" },
    "type": {
        "type": "enum",
        "required": true,
        "options": ["MOU", "Grant Agreement", "SOW", "Services Agreement", "Subcontract", "Other"],
        "label": "Contract Type"
    },
    "status": {
        "type": "enum",
        "required": true,
        "options": ["Draft", "Active", "Completed", "Expired", "Terminated"],
        "label": "Status"
    },
    "parties": { "type": "string_array", "required": true, "label": "Parties Involved" },
    "effective_date": { "type": "date", "required": true, "label": "Effective Date" },
    "end_date": { "type": "date", "required": false, "label": "End Date" },
    "value": { "type": "currency_or_string", "required": false, "label": "Value", "allow_values": ["Non-monetary"] },
    "project": { "type": "relation", "relation_target": "project_brief", "required": false, "label": "Related Project" },
    "funder": { "type": "relation", "relation_target": "funder_profile", "required": false, "label": "Related Funder" },
    "key_deliverables": { "type": "string_array", "required": false, "label": "Key Deliverables" },
    "reporting_schedule": {
        "type": "enum",
        "required": false,
        "options": ["Monthly", "Quarterly", "Annual", "As-needed"],
        "label": "Reporting Schedule"
    },
    "popvox_lead": { "type": "relation", "relation_target": "team_profile", "required": false, "label": "POPVOX Lead" }
}
```

#### Organization Profile (`org_profile`)

```json
{
    "org_name": { "type": "string", "required": true, "label": "Organization Name" },
    "short_name": { "type": "string", "required": false, "label": "Abbreviation" },
    "type": {
        "type": "enum",
        "required": true,
        "options": ["Partner", "Government", "Civil Society", "Academic", "International Organization", "Media", "Vendor", "Other"],
        "label": "Organization Type"
    },
    "relationship": {
        "type": "enum",
        "required": true,
        "options": ["Strategic Partner", "Collaborator", "Stakeholder", "Vendor", "Other"],
        "label": "Relationship Type"
    },
    "status": {
        "type": "enum",
        "required": true,
        "options": ["Active", "Inactive"],
        "label": "Status"
    },
    "website": { "type": "url", "required": false, "label": "Website" },
    "primary_contact": { "type": "contact", "required": false, "label": "Primary Contact" },
    "related_projects": { "type": "relation_array", "relation_target": "project_brief", "required": false, "label": "Related Projects" },
    "related_funders": { "type": "relation_array", "relation_target": "funder_profile", "required": false, "label": "Related Funders" },
    "notes": { "type": "text", "required": false, "label": "Notes" }
}
```

#### Team Profile (`team_profile`)

```json
{
    "name": { "type": "string", "required": true, "label": "Full Name" },
    "role": { "type": "string", "required": true, "label": "Job Title" },
    "status": {
        "type": "enum",
        "required": true,
        "options": ["Active", "On Leave", "Former"],
        "label": "Status"
    },
    "email": { "type": "email", "required": true, "label": "Email" },
    "location": { "type": "string", "required": false, "label": "Location" },
    "start_date": { "type": "date", "required": false, "label": "Start Date" },
    "reports_to": { "type": "relation", "relation_target": "team_profile", "required": false, "label": "Reports To" },
    "current_projects": {
        "type": "relation_array",
        "relation_target": "project_brief",
        "required": false,
        "label": "Current Projects",
        "metadata_fields": { "role": { "type": "string", "label": "Role on Project" } }
    },
    "areas_of_expertise": { "type": "string_array", "required": false, "label": "Areas of Expertise" },
    "bio_summary": { "type": "text", "required": false, "label": "Bio Summary" }
}
```

#### Meeting Note (`meeting_note`)

```json
{
    "meeting_title": { "type": "string", "required": true, "label": "Meeting Title" },
    "date": { "type": "date", "required": true, "label": "Date" },
    "time": { "type": "string", "required": false, "label": "Time" },
    "type": {
        "type": "enum",
        "required": true,
        "options": ["Internal", "External", "Board", "Working Group", "Funder", "Partner", "Other"],
        "label": "Meeting Type"
    },
    "attendees": { "type": "string_array", "required": false, "label": "Attendees" },
    "organizer": { "type": "relation", "relation_target": "team_profile", "required": false, "label": "Organizer" },
    "related_project": { "type": "relation", "relation_target": "project_brief", "required": false, "label": "Related Project" },
    "related_funder": { "type": "relation", "relation_target": "funder_profile", "required": false, "label": "Related Funder" },
    "related_org": { "type": "relation", "relation_target": "org_profile", "required": false, "label": "Related Org" },
    "status": {
        "type": "enum",
        "required": true,
        "options": ["Scheduled", "Completed", "Cancelled"],
        "label": "Status"
    }
}
```

#### Trip Record (`trip_record`)

```json
{
    "trip_name": { "type": "string", "required": true, "label": "Trip Name" },
    "traveler": { "type": "relation_array", "relation_target": "team_profile", "required": true, "label": "Traveler(s)" },
    "destination": { "type": "string", "required": true, "label": "Destination" },
    "departure_date": { "type": "date", "required": true, "label": "Departure" },
    "return_date": { "type": "date", "required": true, "label": "Return" },
    "purpose": {
        "type": "enum",
        "required": true,
        "options": ["Conference", "Partner Meeting", "Training", "Site Visit", "Other"],
        "label": "Purpose"
    },
    "related_project": { "type": "relation", "relation_target": "project_brief", "required": false, "label": "Related Project" },
    "related_org": { "type": "relation", "relation_target": "org_profile", "required": false, "label": "Related Org" },
    "status": {
        "type": "enum",
        "required": true,
        "options": ["Planned", "Completed", "Cancelled"],
        "label": "Status"
    },
    "budget_source": { "type": "string", "required": false, "label": "Budget Source" }
}
```

---

## 4. FRONT-END: DOCUMENT EDITOR

### 4.1 Editor Layout

The document editor is a Livewire component with two zones:

```
┌─────────────────────────────────────────────────────┐
│  BREADCRUMB: WRK > PROJECTS > DIGITAL PARLIAMENTS   │
├────────────────────────┬────────────────────────────┤
│                        │                            │
│   METADATA PANEL       │   CONTENT EDITOR           │
│   (structured form)    │   (markdown)               │
│                        │                            │
│   Project Name: [___]  │   ## Overview              │
│   Status: [Active ▼]   │                            │
│   Lead: [Harris ▼]     │   The Digital Parliaments  │
│   Focus: [Innov. ▼]    │   Project (DPP) is...      │
│                        │                            │
│   ── Relations ──      │                            │
│   Funders: [+ Add]     │                            │
│     • FFDW    [×]      │                            │
│   Partners: [+ Add]    │                            │
│     • ParlAmericas [×] │                            │
│     • Mozilla    [×]   │                            │
│   Contracts: [+ Add]   │                            │
│                        │                            │
├────────────────────────┴────────────────────────────┤
│  [Save Draft]  [Save & Sync to Box]  [View History] │
└─────────────────────────────────────────────────────┘
```

### 4.2 Metadata Panel Behavior

The metadata panel is **dynamically generated from the doc_type schema** (Section 3.2). The rendering engine maps field types to Livewire components:

| Field Type | Component | Behavior |
|------------|-----------|----------|
| `string` | Text input | Simple text field |
| `text` | Textarea | Multi-line text |
| `enum` | Select dropdown | Options from schema |
| `date` | Date picker | Standard date input |
| `url` | URL input | With validation and "open" link |
| `email` | Email input | With validation |
| `currency` | Number input | With currency formatting |
| `string_array` | Tag input | Add/remove string items |
| `contact` | Composite | Name + title + email subfields |
| `relation` | **Searchable select** | Searches existing docs of `relation_target` type |
| `relation_array` | **Multi-select with search** | Add multiple related docs, each with optional metadata |

### 4.3 Relation Selector Component

This is the most important UI element. When a user clicks `[+ Add]` on a relation field:

1. A modal/dropdown opens with a **searchable list** of existing documents of the target type.
2. Results show: name, status badge, and a breadcrumb path (e.g., `FUNDERS > FFDW`).
3. If the relation has `metadata_fields` (e.g., "role on project"), an inline form appears after selection.
4. Selecting a relation creates entries in the `relationships` table for BOTH directions (e.g., adding a funder to a project creates `project → funded_by → funder` AND `funder → funds → project`).
5. The selector also offers a **"Create New"** option that opens a quick-create form for the target entity inline.

```php
// Livewire component pseudocode
class RelationSelector extends Component
{
    public string $targetDocType;
    public string $relationType;
    public array $selected = [];
    public string $search = '';

    public function getResults()
    {
        return Document::where('doc_type', $this->targetDocType)
            ->where(function ($query) {
                $query->where('title', 'ILIKE', "%{$this->search}%")
                    ->orWhereRaw("metadata->>'short_name' ILIKE ?", ["%{$this->search}%"]);
            })
            ->limit(20)
            ->get();
    }

    public function addRelation(int $targetId, array $meta = [])
    {
        // Create bidirectional relationship
        RelationService::createBidirectional(
            sourceType: 'document',
            sourceId: $this->documentId,
            targetType: 'document',
            targetId: $targetId,
            relationType: $this->relationType,
            metadata: $meta
        );
    }
}
```

### 4.4 Content Editor

The markdown body editor should use a live-preview markdown editor. Recommended: **EasyMDE** or **Toast UI Editor** (both work well with Livewire).

Requirements:
- Markdown editing with toolbar (headings, bold, links, lists, tables)
- Live preview pane
- Support for YAML frontmatter display (read-only — edited via the metadata panel)
- Auto-save drafts to `localStorage` to prevent data loss
- Image support via Box file upload

### 4.5 Save Flow

When the user clicks **"Save & Sync to Box"**:

```
1. Validate metadata against doc_type schema
2. In one DB transaction:
   - Upsert metadata + relationships locally
   - Write an outbox/sync job row (`pending_box_sync`)
   - Write activity log row (`save_requested`)
3. Queue worker consumes outbox row
4. Generate YAML frontmatter from metadata
   - Relation fields render as stable IDs plus readable paths
5. Upload/update file in Box via Box API (idempotent)
6. Mark sync row `synced` (or `failed` with retry/backoff)
7. Update search index
8. Log `save_completed` or `save_failed`
```

Why this pattern:
- Cross-system writes are not truly atomic. The outbox/saga pattern avoids split-brain state and provides retries + auditability.

The reverse sync (Box → Platform) should also be supported for initial import and for changes made directly in Box:

```
1. Webhook or polling detects Box file change
2. Verify webhook signature and dedupe by delivery ID
3. Fetch updated content from Box
4. Parse YAML frontmatter
5. Upsert metadata into PostgreSQL
6. Resolve relation IDs first, then fallback to path/name resolution
7. Flag unresolvable relations for manual review
8. Log provenance (`source=box_webhook`, `delivery_id`, `box_event_id`)
```

---

## 5. RELATIONSHIP MAPPING (Phase 2)

### 5.1 Relationship Graph Service

```php
class RelationService
{
    /**
     * Create a bidirectional relationship.
     * Automatically creates the inverse relation.
     */
    public static function createBidirectional(
        string $sourceType,
        int $sourceId,
        string $targetType,
        int $targetId,
        string $relationType,
        array $metadata = []
    ): void {
        if ($sourceType === $targetType && $sourceId === $targetId) {
            throw new InvalidArgumentException('Self-referential edge is not allowed.');
        }

        $inverse = self::getInverseType($relationType);

        Relationship::withTrashed()->updateOrCreate(
            ['source_type' => $sourceType, 'source_id' => $sourceId,
             'target_type' => $targetType, 'target_id' => $targetId,
             'relation_type' => $relationType],
            ['metadata' => $metadata, 'deleted_at' => null, 'updated_at' => now()]
        );

        if ($inverse) {
            Relationship::withTrashed()->updateOrCreate(
                ['source_type' => $targetType, 'source_id' => $targetId,
                 'target_type' => $sourceType, 'target_id' => $sourceId,
                 'relation_type' => $inverse],
                ['metadata' => $metadata, 'deleted_at' => null, 'updated_at' => now()]
            );
        }
    }

    private static function getInverseType(string $type): ?string
    {
        return match($type) {
            'funded_by'       => 'funds',
            'funds'           => 'funded_by',
            'governed_by'     => 'governs',
            'governs'         => 'governed_by',
            'partner_on'      => 'has_partner',
            'has_partner'     => 'partner_on',
            'team_member_of'  => 'has_team_member',
            'has_team_member' => 'team_member_of',
            'child_of'        => 'parent_of',
            'parent_of'       => 'child_of',
            'meeting_for'     => null, // one-directional
            'trip_for'        => null,
            'references'      => 'references', // symmetric
            'related_to'      => 'related_to', // symmetric
            default           => null,
        };
    }

    /**
     * Get all relationships for an entity, grouped by type.
     */
    public static function getRelationsFor(string $entityType, int $entityId): Collection
    {
        return Relationship::query()
            ->whereNull('deleted_at')
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->get()
            ->groupBy('relation_type');
    }

    /**
     * Get the full relationship graph for visualization.
     * Returns nodes and edges suitable for a graph library.
     */
    public static function getGraph(array $filters = []): array
    {
        $query = Relationship::query()->whereNull('deleted_at');

        if (isset($filters['relation_types'])) {
            $query->whereIn('relation_type', $filters['relation_types']);
        }

        $edges = $query->get();
        $nodeRefs = $edges
            ->flatMap(fn ($rel) => [
                ['type' => $rel->source_type, 'id' => (int) $rel->source_id],
                ['type' => $rel->target_type, 'id' => (int) $rel->target_id],
            ])
            ->unique(fn ($ref) => $ref['type'].':'.$ref['id'])
            ->values();

        $nodes = $nodeRefs->map(function (array $ref) {
            return self::resolveGraphNode($ref['type'], $ref['id']);
        })->filter()->values();

        return [
            'nodes' => $nodes,
            'edges' => $edges->map(fn ($rel) => [
                'source' => $rel->source_type.':'.$rel->source_id,
                'target' => $rel->target_type.':'.$rel->target_id,
                'type' => $rel->relation_type,
                'metadata' => $rel->metadata,
            ]),
        ];
    }

    private static function resolveGraphNode(string $type, int $id): ?array
    {
        return match ($type) {
            'document' => self::mapDocumentNode($id),
            'project' => self::mapProjectNode($id),
            'organization' => self::mapOrganizationNode($id),
            'person' => self::mapPersonNode($id),
            'meeting' => self::mapMeetingNode($id),
            'trip' => self::mapTripNode($id),
            default => ['id' => $type.':'.$id, 'label' => strtoupper($type).' #'.$id, 'type' => $type],
        };
    }
}
```

### 5.2 Graph Visualization (Phase 2)

A visual relationship map on the front end using **D3.js** or **Cytoscape.js** (both render well in Livewire via Alpine.js bridges).

Features:
- Force-directed graph showing entities as nodes, relationships as edges
- Color-coded by entity type (projects = blue, funders = green, contracts = orange, etc.)
- Click a node to open its document
- Filter by relationship type, entity type, status
- Zoom to a single entity and see its neighborhood
- Export as SVG/PNG for reports

### 5.3 Relationship Query API (Phase 2)

For AI agents and internal tooling:

```
GET /api/v1/relationships?entity_type=document&entity_id=42
GET /api/v1/relationships?relation_type=funded_by
GET /api/v1/graph?center_type=project&center_id=42&depth=2
GET /api/v1/documents?doc_type=project_brief&status=Active
GET /api/v1/documents/42/related?relation_type=funded_by
```

---

## 6. AUTHENTICATION & AUTHORIZATION

### 6.1 Roles

| Role | Description | Access |
|------|-------------|--------|
| `admin` | Organization leadership | Full access to all folders including ADMIN_RESTRICTED |
| `staff` | General team members | All folders except ADMIN_RESTRICTED; full CRUD on assigned projects |
| `viewer` | External collaborators, board members | Read-only access to specified folders |
| `ai_agent` | AI systems (Claude, custom bots) | API access with scoped permissions per agent |

### 6.2 Folder-Level Permissions

```sql
CREATE TABLE folder_permissions (
    id          BIGSERIAL PRIMARY KEY,
    folder_id   BIGINT NOT NULL REFERENCES folders(id),
    user_id     BIGINT REFERENCES users(id),         -- NULL = applies to role
    role        VARCHAR(32),                          -- NULL = applies to specific user
    permission  VARCHAR(32) NOT NULL,                 -- 'read', 'write', 'admin'
    created_at  TIMESTAMPTZ DEFAULT NOW(),

    -- Either user_id or role must be set
    CHECK (user_id IS NOT NULL OR role IS NOT NULL)
);
```

Default permission matrix:

| Folder | admin | staff | viewer | ai_agent |
|--------|-------|-------|--------|----------|
| ADMIN_RESTRICTED | admin | — | — | — |
| PROJECTS | admin | write | read | read |
| FUNDERS | admin | write | — | read |
| CONTRACTS | admin | write | — | read |
| CONTACTS_ORGS | admin | write | read | read |
| MEETINGS | admin | write | read | read |
| TEAM | admin | write | read | read |
| KNOWLEDGE | admin | write | read | read |
| TRAVEL | admin | write | — | read |

### 6.3 AI Agent Authentication

AI agents authenticate via API keys with scoped permissions:

```sql
CREATE TABLE api_keys (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id),  -- the ai_agent user
    key_hash        VARCHAR(255) NOT NULL,                  -- bcrypt hash of API key
    name            VARCHAR(255) NOT NULL,                  -- "Claude Cowork Agent", etc.
    permissions     JSONB NOT NULL DEFAULT '{}',            -- granular permission overrides
    rate_limit      INTEGER DEFAULT 100,                    -- requests per minute
    last_used_at    TIMESTAMPTZ,
    expires_at      TIMESTAMPTZ,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 6.4 Middleware

```php
// Laravel middleware stack for document routes
Route::middleware(['auth', 'folder.permission:read'])->group(function () {
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
});

Route::middleware(['auth', 'folder.permission:write'])->group(function () {
    Route::put('/documents/{document}', [DocumentController::class, 'update']);
    Route::post('/documents', [DocumentController::class, 'store']);
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('folder-permissions', FolderPermissionController::class);
});

// API routes for AI agents
Route::middleware(['auth:api', 'throttle:api'])->prefix('api/v1')->group(function () {
    Route::get('/documents', [ApiDocumentController::class, 'index']);
    Route::get('/documents/{document}', [ApiDocumentController::class, 'show']);
    Route::get('/relationships', [ApiRelationshipController::class, 'index']);
    Route::get('/graph', [ApiGraphController::class, 'show']);
});
```

---

## 7. BOX API INTEGRATION

### 7.1 Service Layer

```php
class BoxService
{
    private BoxClient $client;

    /**
     * Sync a document to Box (Platform → Box).
     */
    public function syncToBox(Document $document): void
    {
        $markdown = $this->renderMarkdown($document);

        if ($document->box_file_id) {
            $this->client->uploadNewVersion($document->box_file_id, $markdown);
        } else {
            $file = $this->client->uploadFile(
                $document->folder->box_folder_id,
                $document->slug . '.md',
                $markdown
            );
            $document->update(['box_file_id' => $file->id]);
        }
    }

    /**
     * Import a document from Box (Box → Platform).
     */
    public function importFromBox(string $boxFileId, Folder $folder): Document
    {
        $content = $this->client->getFileContent($boxFileId);
        $parsed = YamlFrontMatter::parse($content);

        return Document::updateOrCreate(
            ['box_file_id' => $boxFileId],
            [
                'folder_id' => $folder->id,
                'title' => $parsed->matter('project_name')
                    ?? $parsed->matter('funder_name')
                    ?? $parsed->matter('title')
                    ?? basename($boxFileId),
                'doc_type' => $this->inferDocType($folder, $parsed),
                'metadata' => $parsed->matter(),
                'content_body' => $parsed->body(),
                'content_hash' => hash('sha256', $content),
            ]
        );
    }

    /**
     * Render a document as markdown with YAML frontmatter.
     * Relation fields are rendered as human-readable folder paths.
     */
    private function renderMarkdown(Document $document): string
    {
        $frontmatter = $document->metadata;

        // Convert relation IDs to folder paths for Box readability
        $relations = RelationService::getRelationsFor('document', $document->id);
        foreach ($this->getRelationFields($document->doc_type) as $field => $config) {
            $relType = $this->fieldToRelationType($field);
            $related = $relations->get($relType, collect());
            $paths = $related->map(fn ($r) => $this->entityToPath($r));

            $frontmatter[$field] = $config['type'] === 'relation'
                ? $paths->first()
                : $paths->toArray();
        }

        $yaml = Yaml::dump($frontmatter, 4, 2);
        return "---\n{$yaml}---\n\n{$document->content_body}";
    }

    /**
     * Convert a related entity to a Box folder path.
     * e.g., Document(funder_profile, "FFDW") → "FUNDERS/FFDW"
     */
    private function entityToPath(Relationship $rel): string
    {
        $doc = Document::find($rel->target_id);
        $folder = $doc?->folder;
        return $folder ? "{$folder->parent->name}/{$folder->name}" : "UNKNOWN";
    }
}
```

Notes:
- `syncToBox()` is invoked by a queue worker processing `sync_outbox` rows, not directly in a user request transaction.
- Box writes should include idempotency guards (`dedupe_key`, `etag`/version checks) to prevent duplicate updates.

### 7.2 Webhook Handler (Box → Platform Sync)

```php
// Register Box webhook for file changes
// POST /webhooks/box
class BoxWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // 1) Verify Box signature (primary/secondary key) before processing
        if (! $this->signatureVerifier->isValid($request)) {
            return response('Invalid signature', 401);
        }

        // 2) Idempotency: skip if delivery already processed
        $deliveryId = (string) $request->header('BOX-DELIVERY-ID');
        if (BoxWebhookEvent::where('delivery_id', $deliveryId)->exists()) {
            return response('Already processed', 200);
        }

        $event = $request->input('trigger');
        $fileId = $request->input('source.id');
        $eventId = $request->input('id');

        BoxWebhookEvent::create([
            'delivery_id' => $deliveryId,
            'trigger' => $event,
            'source_type' => $request->input('source.type'),
            'source_id' => $fileId,
            'payload' => $request->all(),
            'status' => 'received',
        ]);

        if (in_array($event, ['FILE.UPLOADED', 'FILE.MODIFIED'])) {
            ImportFromBoxJob::dispatch($fileId, $deliveryId, $eventId);
        }

        return response('OK', 200);
    }
}
```

---

## 8. SEARCH

### 8.1 Strategy

For Phase 1, use PostgreSQL's built-in full-text search with `pg_trgm` for fuzzy matching. This avoids an external dependency and is sufficient for the document volume.

```sql
-- Add search columns
ALTER TABLE documents ADD COLUMN search_vector tsvector;

CREATE INDEX idx_documents_search ON documents USING GIN(search_vector);

-- Trigger to auto-update search vector
CREATE OR REPLACE FUNCTION update_search_vector() RETURNS trigger AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('english', COALESCE(NEW.title, '')), 'A') ||
        setweight(to_tsvector('english', COALESCE(NEW.metadata::text, '')), 'B') ||
        setweight(to_tsvector('english', COALESCE(NEW.content_body, '')), 'C');
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE TRIGGER documents_search_update
    BEFORE INSERT OR UPDATE ON documents
    FOR EACH ROW EXECUTE FUNCTION update_search_vector();
```

### 8.2 Search UI

A global search bar (Livewire component) that:
- Searches across all accessible documents
- Filters by doc_type, folder, status
- Shows results with title, doc_type badge, status, folder breadcrumb, and a snippet
- Keyboard navigable (↑↓ to select, Enter to open)

---

## 9. AI AGENT API (Phase 2)

### 9.1 Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/org` | Returns ORG_INFO content and metadata |
| GET | `/api/v1/documents` | List/search documents (filterable) |
| GET | `/api/v1/documents/{id}` | Full document with metadata + content + relations |
| GET | `/api/v1/documents/{id}/related` | Related entities with relationship types |
| PUT | `/api/v1/documents/{id}` | Update document (metadata and/or content) |
| POST | `/api/v1/documents` | Create new document |
| GET | `/api/v1/relationships` | Query relationships |
| GET | `/api/v1/graph` | Graph data for visualization or traversal |
| GET | `/api/v1/folders` | Folder tree with document counts |
| GET | `/api/v1/activity` | Recent activity log |

### 9.2 Response Format

All API responses include a `_context` block to help AI agents orient themselves:

```json
{
    "data": {
        "id": 42,
        "doc_type": "project_brief",
        "title": "DIGITAL PARLIAMENTS PROJECT",
        "metadata": { "status": "Active", "focus_area": "Innovation", ... },
        "content_body": "## Overview\n\nThe Digital Parliaments Project...",
        "relationships": [
            { "type": "has_partner", "target": { "id": 15, "title": "ParlAmericas", "doc_type": "org_profile" } },
            { "type": "has_partner", "target": { "id": 16, "title": "Mozilla Foundation", "doc_type": "org_profile" } }
        ]
    },
    "_context": {
        "org": "POPVOX Foundation",
        "folder_path": "WRK > PROJECTS > DIGITAL PARLIAMENTS PROJECT",
        "doc_type_schema_url": "/api/v1/schemas/project_brief",
        "last_updated": "2026-02-22T15:34:50Z",
        "permissions": { "can_read": true, "can_write": false }
    }
}
```

### 9.3 Agent Instructions Endpoint

A special endpoint that returns a system-prompt-friendly summary for AI agents:

```
GET /api/v1/agent-context?scope=all
```

Returns a structured summary including: org info, folder structure, active projects with statuses, available doc types and their schemas, and recent activity. Designed to be injected into an AI agent's system prompt or context window.

---

## 10. INITIAL DATA IMPORT

### 10.1 Seeder

A Laravel seeder/command to import the existing Box structure:

```bash
php artisan wrk:import-from-box
```

This command:
1. Walks the WRK folder tree via Box API
2. Creates `folders` records for each Box folder
3. Finds all `.md` files
4. Parses YAML frontmatter
5. Creates `documents` records with metadata
6. Attempts to resolve cross-references in frontmatter to create `relationships`
7. Reports unresolvable references for manual review

### 10.2 Frontmatter Path Resolution

When importing, the system resolves relationship references in this order:

1. Stable entity reference (preferred), e.g. `funder:15` or `document:88`
2. Box IDs in metadata (if present)
3. Human-readable path fallback like `FUNDERS/FFDW`

```php
private function resolveReference(string $value): ?array
{
    // 1) Stable ref: "entity_type:entity_id"
    if (preg_match('/^(document|project|funder|contract|organization|person|meeting|trip):(\d+)$/', $value, $m)) {
        return ['entity_type' => $m[1], 'entity_id' => (int) $m[2]];
    }

    // 2) Optional Box ID metadata mapping
    $boxMatch = $this->resolveByBoxId($value);
    if ($boxMatch) {
        return $boxMatch;
    }

    // 3) Legacy fallback: "FUNDERS/FFDW"
    return $this->resolveByPath($value);
}
```

---

## 11. MIGRATION ROADMAP

### Phase 0: Compatibility Bridge (Week 0-1)

- [ ] Add document-graph tables without changing existing WRK page behavior
- [ ] Add adapters between existing domain entities and new `documents` / `relationships`
- [ ] Backfill stable entity references (`entity_type`, `entity_id`) for graph edges
- [ ] Add sync outbox table and worker for Box write retries
- [ ] Validate Box webhook signature + replay protection in production

### Phase 1: MVP (Weeks 1-6)

- [ ] Database schema and migrations
- [ ] Box API service layer (read/write/sync)
- [ ] Import existing Box documents
- [ ] Document editor (metadata panel + markdown editor)
- [ ] Relation selector component (searchable dropdowns)
- [ ] Bidirectional relationship creation on save
- [ ] Folder-level auth (admin vs staff vs viewer)
- [ ] Basic search (PostgreSQL full-text)
- [ ] Activity logging
- [ ] Production readiness checks (idempotency, retry policies, dead-letter queue)

### Phase 2: Relationships & API (Weeks 7-12)

- [ ] Relationship graph visualization (D3/Cytoscape)
- [ ] AI Agent REST API with auth
- [ ] Agent context endpoint
- [ ] Box webhook integration (two-way sync)
- [ ] Advanced search (filters, facets)
- [ ] Timeline views (project milestones, contract dates)
- [ ] Dashboard: org-wide status overview

### Phase 3: Intelligence (Weeks 13+)

- [ ] Automated relationship inference (suggest connections based on content overlap)
- [ ] Stale document detection (flag docs not updated in X days)
- [ ] Reporting dashboards (project status, funder pipeline, contract expirations)
- [ ] Bulk import/export tools
- [ ] API rate limiting and usage analytics
- [ ] Full knowledge graph traversal queries

---

## 12. CONVENTIONS & STANDARDS

### Naming

- Database tables: `snake_case` plural (`documents`, `relationships`)
- Laravel models: `PascalCase` singular (`Document`, `Relationship`)
- API endpoints: `kebab-case` (`/api/v1/agent-context`)
- Livewire components: `PascalCase` (`RelationSelector`, `DocumentEditor`)
- Box folder names: `ALL CAPS` with underscores
- Markdown files: `ALL CAPS` for structural docs (`BRIEF.md`, `README.md`, `PROFILE.md`)

### Frontmatter ↔ Database Sync Rules

1. The **database** is authoritative for metadata and relationships.
2. The **Box markdown file** is authoritative for content body.
3. On save from the platform, metadata writes are local-transactional and Box writes are asynchronous via outbox.
4. On import from Box, the database is updated and flagged for review if relationships can't be resolved.
5. Runtime entities (`projects`, `people`, `organizations`, `meetings`, `trips`, `actions`) remain canonical for operational workflows.
6. `last_updated` in frontmatter is always set to the current timestamp on successful Box sync.

### Relationship Integrity

- Deleting a document should soft-delete its relationships (`relationships.deleted_at`) rather than hard-delete.
- The UI should warn when creating a relationship to a "Complete" or "Inactive" entity.
- Orphaned relationships (where one side is deleted) should be surfaced in an admin review queue.
