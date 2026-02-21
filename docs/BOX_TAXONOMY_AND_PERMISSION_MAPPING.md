# Box Taxonomy and Permission Mapping (v2 Draft)

Related:
- `docs/WRK_AI_Architecture_Brief_FINAL.md`
- `docs/WRK_V2_DATA_CONTRACT.md`
- `docs/WRK_V2_SCHEMA_PROPOSAL.md`

Date: February 20, 2026
Status: Kickoff baseline for Week 1-2

## 1) Folder Taxonomy

Recommended root structure under `WRK`:

- `Projects`
- `Funders`
- `Contracts`
- `Team`
- `Meetings`
- `Travel`
- `Contacts_Orgs`
- `Knowledge`
- `Admin_Restricted`

For project-specific material:

- `Projects/{project_slug}/01_Operations`
- `Projects/{project_slug}/02_Deliverables`
- `Projects/{project_slug}/03_Meetings`
- `Projects/{project_slug}/04_External`

For grants:

- `Funders/{funder_slug}/{grant_slug}/01_Application`
- `Funders/{funder_slug}/{grant_slug}/02_Agreement`
- `Funders/{funder_slug}/{grant_slug}/03_Reporting`

For contracts (new first-class WRK domain):

- `Contracts/{org_slug}/{ctr_id}_{short_name}/01_Agreements`
- `Contracts/{org_slug}/{ctr_id}_{short_name}/02_Billing`
- `Contracts/{org_slug}/{ctr_id}_{short_name}/03_Reporting`
- `Contracts/{org_slug}/{ctr_id}_{short_name}/04_Compliance`

For travel:

- `Travel/{trip_slug}/01_Logistics`
- `Travel/{trip_slug}/02_Agendas`
- `Travel/{trip_slug}/03_Receipts`

## 2) Metadata and Linking Rules

- Every top-level folder relevant to WRK entities should include one canonical mapping key in name or metadata (for example, `prj_xxx`, `gnt_xxx`, `ctr_xxx`, `trp_xxx`).
- Prefer Box metadata templates for durable mapping once enabled:
  - `wrk_entity_type`
  - `wrk_entity_id`
  - `wrk_visibility`
  - `wrk_org_id` (required for contracts/funders)
  - `wrk_project_id` (required when a contract is tied to a project)
- If metadata templates are unavailable, use naming convention fallback and maintain mapping table in WRK.
- Every `Contracts/{org_slug}/{ctr_id}_{short_name}` folder must map to:
  - one `organization` (`org_*`) [required]
  - zero or one `project` (`prj_*`) [optional, but preferred]
  - zero or more grants/funders (`gnt_*`) [optional]

## 3) Permission Mapping Policy

WRK visibility policy from Box collaboration:

- Box open team access -> WRK `all`
- Box restricted management group -> WRK `management`
- Box restricted admins/legal/exec only -> WRK `admin`

Rules:

- Effective WRK document visibility is `min(Box ACL projection, WRK explicit policy)`.
- Never broaden access in WRK beyond effective Box access.
- Permission projection should be recalculated on file/folder collaboration events.
- Default visibility by top-level domain:
  - `Projects` -> `all`
  - `Funders` -> `management`
  - `Contracts` -> `management` (upgrade to `admin` for legal/compensation/vendor-sensitive records)
  - `Team` -> `management`
  - `Admin_Restricted` -> `admin`
- Contract-specific override rule:
  - if any contract node is `admin`, inherited children remain `admin` unless explicitly tightened in Box.

## 4) Event Triggers to Handle

- `FILE.UPLOADED`, `FILE.COPIED`, `FILE.MOVED`, `FILE.RENAMED`, `FILE.TRASHED`
- `FOLDER.CREATED`, `FOLDER.MOVED`, `FOLDER.RENAMED`, `FOLDER.TRASHED`
- Collaboration/permission changes affecting inherited ACLs

## 5) Contract Linking Rules (Required)

When WRK ingests metadata for a `Contracts` path:

1. Resolve `org_id` from folder metadata or naming convention.
2. Resolve `contract_id` (canonical `ctr_*`) from folder metadata or naming convention.
3. Optionally resolve linked `project_id` (`prj_*`).
4. Persist explicit links:
   - `contract -> organization` (required)
   - `contract -> project` (optional)
   - `contract -> grant` (optional)
5. Reject or flag orphan contract folders that do not resolve to an organization.

## 6) Sync Pipeline Baseline

1. Webhook receives event and stores immutable payload in `box_webhook_events`.
2. Worker fetches latest Box item metadata and upserts into `box_items`.
3. For file events, worker dispatches linked document sync jobs so connected WRK project documents stay current.
4. Permission projection layer derives WRK visibility.
5. Downstream indexing pipeline decides if content re-ingest/reindex is needed.

## 7) Operational Baseline

- Command: `php artisan box:sync-metadata {folderId?} --recursive`
- Command: `php artisan box:link-to-project {boxItemId} {projectId} [--visibility=all|management|admin] [--created-by={userId}] [--queue]`
- Command: `php artisan box:sync-documents [--link-id={id}] [--project-id={id}] [--box-item-id={externalId}] [--queue]`
- Webhook endpoint: `POST /webhooks/box`
- Queue workers must run continuously in staging/production for near-real-time sync.

## 8) Link Table Baseline

- New table: `box_project_document_links` records explicit Box file -> WRK project links.
- A link can maintain (or re-attach to) one `project_documents` row with `type=link`.
- Sync status tracking lives on the link row:
  - `pending` -> waiting for sync
  - `synced` -> current
  - `failed` -> last sync failed (with `last_error`)
