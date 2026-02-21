# WRK Agent-First UX + Taxonomy Spec (Figma Handoff)

Date: 2026-02-21
Owner: WRK Product + Engineering
Status: Draft for design exploration and implementation planning

## 1) Purpose
This document is the UX and taxonomy handoff for a full WRK visual overhaul.

Goals:
- Make agents first-class across WRK (not hidden in a single tab).
- Keep structured operations usable (forms/tables/timelines) while chat drives actions.
- Keep Figma language aligned to canonical data keys already in WRK.

What this is:
- A product and interaction spec for design.
- A canonical naming reference for entities/fields/enums.

What this is not:
- Final visual design.
- Final API contract for future services (but it maps closely to current implementation).

## 2) Product Direction
### 2.1 Core principle
"Conversation is the control plane. Structured views are the verification plane."

### 2.2 Source of truth model
- Postgres: operational entities, links, permissions, workflow state, agent logs.
- Box: file/folder system of record and ACL baseline.
- WRK app: orchestration UI, conversation surfaces, retrieval, and action review.

### 2.3 UX outcomes to optimize
- Faster update workflows via natural language.
- Better trust through visible action logs and source links.
- Lower navigation load by reducing tab-hopping.

## 3) Global Information Architecture
### 3.1 Current live modules
- Dashboard
- Projects
- People
- Organizations
- Meetings
- Media & Press
- Travel
- Team Hub
- Knowledge Hub
- Knowledge Base
- Funders & Reporting
- Admin: Staff, Metrics, Permissions, Feedback

### 3.2 Proposed v2 navigation groups (agent-forward)
- Work: `Dashboard`, `Projects`, `Meetings`, `Travel`
- Relationships: `Contacts` (People + Organizations), `Funders`
- Intelligence: `Knowledge` (Hub + Base unified)
- Communications: `Media & Press`
- Team Ops: `Team Hub`
- Administration: `Permissions`, `Metrics`, `Feedback`, `Staff`

### 3.3 Route map (current canonical)
- `/dashboard` -> `dashboard`
- `/projects` -> `projects.index`
- `/projects/{project}` -> `projects.show`
- `/projects/{project}/workspace` -> `projects.workspace`
- `/people` -> `people.index`
- `/organizations` -> `organizations.index`
- `/meetings` -> `meetings.index`
- `/travel` -> `travel.index`
- `/travel/{trip}` -> `travel.show`
- `/funders` -> `grants.index`
- `/knowledge` -> `knowledge.hub`
- `/knowledge-base` -> `knowledge.base`
- `/team` -> `team.hub`
- `/media` -> `media.index`
- `/admin/staff` -> `admin.staff`
- `/admin/metrics` -> `admin.metrics`
- `/admin/permissions` -> `admin.permissions`
- `/admin/feedback` -> `admin.feedback`

## 4) Agent-First UX System
### 4.1 Page shell pattern (all major modules)
Desktop:
- Left rail: global navigation.
- Center: entity workspace (overview, lists, structured detail).
- Right rail: contextual agent drawer (persistent, collapsible).

Mobile:
- Main content full width.
- Agent opens as slide-over panel with conversation + quick actions.

### 4.2 Agent drawer anatomy
- Header: agent name + current entity context.
- Context bar: key facts (status, dates, owners, budget summary, last sync).
- Conversation stream: user + assistant messages with timestamps.
- Action log cards: per write operation with status and execution details.
- Composer: free text input with `Enter` to send, `Shift+Enter` newline.
- Quick prompts: context-aware action chips.

### 4.3 Message and action behavior
Message types:
- Informational: answer only, no mutation.
- Mutating: writes to records and logs execution.
- Clarification: asks for missing required fields.

Required post-action transparency:
- What changed.
- Which records changed.
- Status (`applied`, `no_change`, `failed`, etc.).
- Error message when failed.

### 4.4 Trust and audit UI requirements
- Every assistant write has an action card.
- Action card includes actor, timestamp, result, and touched entities.
- Inline "updated by agent" badges in affected data sections.
- User can always inspect source message and execution log.

### 4.5 Permission behavior in UI
- Unauthorized users can read conversation history.
- Unauthorized users cannot submit mutating prompts.
- Show explicit read-only state with reason.

## 5) Canonical Taxonomy (Domain Labels)
Use these top-level labels in Figma so naming stays aligned with WRK:
- Projects
- Funders
- Team
- Contacts
- Meetings
- Travel
- Media & Press
- Knowledge
- Admin

### 5.1 Contracts in taxonomy
Recommendation for v2:
- Keep contract work under `Projects` as a project subtype (`project_type = contract`, proposed).
- Add contract-specific workspace sections inside projects:
  - Scope
  - Agreements
  - Billing
  - Reporting

Current implementation state:
- `project_type = contract` is not yet in current validation list.
- Design should include this as a proposed extension, not a current enum.

## 6) Canonical Field Dictionary
This section is the key handoff for design annotations.

Conventions:
- Use friendly UI labels.
- Keep canonical key names in annotations/spec notes.
- Do not rename enum keys without an explicit migration plan.

### 6.1 Identity and access
Entity: `users`
- `name`, `email`, `title`, `role`, `access_level`, `is_admin`
- `reports_to`, `start_date`, `end_date`, `responsibilities`, `bio`, `phone`, `linkedin`
- `location`, `timezone`, `is_visible`

Observed role/access values:
- `role`: `staff`, `contractor`, `intern`, `fellow` (team profile usage)
- `access_level`: `staff`, `management`, `admin` (plus legacy `team` in admin permissions UI)

Visibility model used across domains:
- `all`, `management`, `admin`

### 6.2 Projects
Entity: `projects`
- `name`, `scope`, `lead`, `description`, `status`
- `is_initiative`, `project_path`, `success_metrics`, `goals`, `url`, `tags`
- `start_date`, `target_end_date`, `actual_end_date`
- `created_by`, `parent_project_id`, `project_type`, `sort_order`
- `box_folder_id`, `box_folder_status`, `box_folder_error`, `box_folder_synced_at`
- `ai_status_summary`, `ai_status_generated_at`
- `grant_associations`, `metric_tags`

Project status enum:
- `planning`, `active`, `on_hold`, `completed`, `archived`

Project type enum (current):
- `initiative`, `publication`, `event`, `chapter`, `newsletter`, `tool`, `research`, `outreach`, `component`

Project type (proposed):
- `contract`

#### Project links and child objects
- `project_staff` pivot: `role`, `added_at`
- `project_person` pivot: `role`, `notes`
- `project_organization` pivot: `role`, `notes`
- `meetings` pivot field: `relevance_note`
- `grant_project` pivot fields: `allocated_amount`, `notes`

Child entities:
- `project_tasks`: `title`, `description`, `due_date`, `priority`, `status`, `assigned_to`, `created_by`, `completed_at`
- `project_milestones`: `title`, `description`, `status`, `due_date`, `completed_date`, `completed_by`, `sort_order`, `workstream_id`, `publication_id`, `event_id`
- `project_decisions`: `title`, `description`, `rationale`, `context`, `meeting_id`, `decision_date`, `decided_by`, `created_by`
- `project_questions`: `question`, `context`, `status`, `answer`, `answered_date`, `answered_in_meeting_id`, `raised_by`
- `project_workstreams`: `name`, `description`, `color`, `icon`, `status`, `sort_order`
- `project_publications`: `title`, `description`, `type`, `status`, `target_date`, `published_date`, `sort_order`, `metadata`, `content_path`
- `project_events`: `title`, `description`, `type`, `status`, `event_date`, `location`, `target_attendees`, `actual_attendees`, `deliverables`
- `project_notes`: `content`, `note_type`, `is_pinned`, `user_id`
- `project_chat_messages`: `project_id`, `user_id`, `role`, `content`

Project workspace enums:
- Task priority: `low`, `medium`, `high`
- Task status: `pending`, `in_progress`, `completed`, `cancelled`
- Milestone status: `pending`, `in_progress`, `completed`, `blocked`, `deferred`
- Workstream status: `planning`, `active`, `completed`, `paused`
- Publication type: `chapter`, `report`, `brief`, `appendix`, `case_study`, `other`
- Publication status: `idea`, `outlined`, `drafting`, `editing`, `review`, `ready`, `published`
- Project event type: `staff_event`, `demo`, `launch`, `briefing`, `workshop`, `other`
- Project event status: `planning`, `confirmed`, `completed`, `cancelled`
- Note type: `update`, `decision`, `blocker`, `general`

### 6.3 Contacts
#### People
Entity: `people`
- `name`, `organization_id`, `title`, `email`, `phone`, `linkedin_url`, `photo_url`, `photo_path`, `bio`, `notes`
- CRM: `status`, `owner_id`, `source`, `tags`, `last_contacted_at`, `next_action_at`, `next_action_note`, `score`
- Media: `is_journalist`, `beat`, `media_notes`, `responsiveness`
- Grant reporting: `contact_type`, `political_affiliation`

People enums:
- CRM status: `lead`, `prospect`, `active`, `partner`, `inactive`
- Contact type: `government_official`, `funder`, `grantee`, `partner`, `media`, `academic`, `stakeholder`, `other`
- Political affiliation: `bipartisan`, `progressive`, `conservative`, `nonpartisan`, `democratic`, `republican`, `independent`

#### Organizations
Entity: `organizations`
- `name`, `suggested_name`, `abbreviation`, `type`, `website`, `email`, `phone`, `linkedin_url`, `logo_url`, `description`, `notes`
- Funder fields: `is_funder`, `funder_priorities`, `funder_preferences`
- Congressional fields: `is_congressional`, `bioguide_id`, `chamber`, `state`, `district`, `party`, `committees`, `leadership_positions`

Organization type values:
- `Advocacy`, `Trade Association`, `Government Agency`, `Nonprofit`, `Business`, `Labor`, `Constituent`, `Congressional Office`, `Funder`, `Media`, `Other`

### 6.4 Meetings and follow-through
Entity: `meetings`
- `title`, `meeting_date`, `meeting_time`, `meeting_end_time`, `location`
- `meeting_link`, `meeting_link_type`
- `user_id`, `lead_contact_id`
- Notes and prep: `prep_notes`, `agenda_notes`, `prep_analysis`, `raw_notes`, `transcript`, `audio_path`
- Summaries: `ai_summary`, `notes_summary`
- Follow-up: `key_ask`, `commitments_made`
- `status`, `google_event_id`, `grant_associations`, `metric_tags`, `external_organizations_count`

Meeting status enum:
- `new`, `action_needed`, `pending`, `complete`

Related entities:
- `meeting_agenda_items`: `title`, `description`, `order`, `duration_minutes`, `presenter_id`, `status`, `notes`, `decisions`
- `actions`: `title`, `description`, `notes`, `due_date`, `priority`, `status`, `source`, `assigned_to`, `meeting_id`, `project_id`, `completed_at`
- `commitments`: `description`, `direction`, `status`, `due_date`, `completed_at`, `meeting_id`, `project_id`, `person_id`, `organization_id`, `assigned_to`, `created_by`
- `decisions` (legacy/general): `decision`, `rationale`, `outcome`, `project_id`, `meeting_id`, `made_by`, `decided_at`

Action enums:
- Priority: `high`, `medium`, `low`
- Status: `pending`, `complete`
- Source: `manual`, `meeting`, `ai_suggested`, `calendar`

Commitment behavior values in current use:
- Direction: `from_us`, `to_us`
- Status: `open`, `completed`

### 6.5 Travel
#### Trip
Entity: `trips`
- `name`, `description`, `type`, `status`
- `start_date`, `end_date`
- `primary_destination_city`, `primary_destination_country`, `primary_destination_region`
- `project_id`, `partner_organization_id`, `partner_program_name`, `created_by`
- Compliance: `risk_level`, `step_registration_required`, `step_registration_completed`, `travel_insurance_required`, `travel_insurance_confirmed`, `approval_required`, `approved_by`, `approved_at`, `approval_notes`
- Debrief: `debrief_notes`, `outcomes`
- Template: `is_template`, `created_from_template_id`

Trip type enum:
- `conference_event`, `funder_meeting`, `site_visit`, `advocacy_hill_day`, `parliamentary_visit`, `training`, `partner_delegation`, `board_meeting`, `speaking_engagement`, `research`, `other`

Trip status enum:
- `planning`, `booked`, `in_progress`, `completed`, `cancelled`

#### Travelers
Pivot `trip_travelers`:
- `user_id`, `role`, `calendar_events_created`, `personal_notes`

Traveler role enum:
- `lead`, `participant`

#### Travel child entities
- `trip_guests`: `name`, `email`, `phone`, `organization`, `role`, `notes`, `home_airport_code`, `dietary_restrictions`, `emergency_contact_name`, `emergency_contact_phone`
- `trip_destinations`: `order`, `city`, `state_province`, `country`, `region`, `arrival_date`, `departure_date`, `state_dept_level`, `is_prohibited_destination`, `travel_advisory_notes`, `latitude`, `longitude`
- `trip_segments`: `user_id`, `trip_guest_id`, `trip_destination_id`, `type`, `carrier`, `carrier_code`, `segment_number`, `confirmation_number`, `departure_location`, `departure_city`, `departure_datetime`, `departure_terminal`, `departure_gate`, `arrival_location`, `arrival_city`, `arrival_datetime`, `arrival_terminal`, `aircraft_type`, `seat_assignment`, `cabin_class`, `distance_miles`, `cost`, `currency`, `status`, `booking_reference`, `ticket_number`, `notes`, `ai_extracted`, `ai_confidence`
- `trip_lodging`: `user_id`, `trip_destination_id`, `property_name`, `chain`, `address`, `city`, `country`, `confirmation_number`, `check_in_date`, `check_in_time`, `check_out_date`, `check_out_time`, `room_type`, `nights`, `nightly_rate`, `total_cost`, `currency`, `phone`, `email`, `latitude`, `longitude`, `notes`, `ai_extracted`
- `trip_ground_transport`: `user_id`, `trip_destination_id`, `type`, `provider`, `confirmation_number`, `pickup_datetime`, `pickup_location`, `return_datetime`, `return_location`, `vehicle_type`, `license_plate`, `cost`, `currency`, `notes`, `ai_extracted`
- `trip_expenses`: `user_id`, `category`, `description`, `expense_date`, `amount`, `currency`, `amount_usd`, `vendor`, `receipt_number`, `receipt_path`, `receipt_original_name`, `reimbursement_status`, `reimbursement_submitted_date`, `reimbursement_paid_date`, `approved_by`, `approved_at`, `trip_sponsorship_id`, `notes`, `ai_extracted`, `source_text`, `source_url`
- `trip_sponsorships`: `organization_id`, `type`, `description`, `amount`, `currency`, `covers_airfare`, `covers_lodging`, `covers_ground_transport`, `covers_meals`, `covers_registration`, `coverage_notes`, `billing_instructions`, `billing_contact_name`, `billing_contact_email`, `billing_contact_phone`, `billing_address`, `invoice_reference`, `purchase_order_number`, `payment_status`, `invoice_sent_date`, `payment_due_date`, `payment_received_date`, `amount_received`, `notes`, `agreement_text`, `agreement_file_path`, `agreement_file_name`, `extracted_terms`, `terms_extracted_at`, `line_items`, `total_consulting_fees`, `total_reimbursable`, `exchange_rate_note`, `payment_terms`, `invoice_deadline`, `deliverables`, `covered_travelers`
- `trip_events`: `meeting_id`, `project_event_id`, `title`, `description`, `start_datetime`, `end_datetime`, `location`, `address`, `type`, `notes`, `ai_extracted`
- `trip_documents`: `uploaded_by`, `filename`, `original_filename`, `mime_type`, `file_size`, `storage_path`, `type`, `description`, `ai_processed`, `ai_processed_at`
- `trip_checklists`: `user_id`, `item`, `category`, `is_completed`, `ai_suggested`
- `trip_sponsorship_documents`: `trip_sponsorship_id`, `file_path`, `file_name`, `file_type`, `extracted_text`, `file_size`

Travel enums in current usage:
- Segment type: `flight`, `train`, `bus`, `rental_car`, `rideshare`, `ferry`, `other_transport`
- Segment status: `scheduled`, `confirmed`, `checked_in`, `completed`, `cancelled`, `delayed`
- Cabin class: `economy`, `premium_economy`, `business`, `first`
- Ground transport type: `rental_car`, `taxi`, `rideshare`, `public_transit`, `shuttle`, `parking`, `other`
- Expense category: `airfare`, `lodging`, `ground_transport`, `meals`, `registration_fees`, `baggage_fees`, `wifi_connectivity`, `tips_gratuities`, `visa_fees`, `travel_insurance`, `office_supplies`, `other`
- Reimbursement status: `not_applicable`, `pending`, `submitted`, `approved`, `paid`, `denied`
- Sponsorship type: `full_sponsorship`, `partial_sponsorship`, `travel_only`, `lodging_only`, `registration_only`, `honorarium`
- Sponsorship payment status: `pending`, `invoiced`, `partial_payment`, `paid`, `overdue`
- Trip event type: `conference_session`, `meeting`, `presentation`, `workshop`, `reception`, `site_visit`, `other`
- Trip document type: `itinerary`, `confirmation`, `receipt`, `invoice`, `boarding_pass`, `visa`, `insurance`, `agenda`, `presentation`, `other`
- Checklist category: `documents`, `electronics`, `clothing`, `presentation_materials`, `gifts_swag`, `health_safety`, `other`
- Sponsorship document file type: `contract`, `invoice`, `receipt`, `correspondence`, `other`
- Guest role: `speaker`, `partner`, `delegate`, `client`, `family`, `guest`, `other`

#### Travel agent entities
- `trip_agent_conversations`: `trip_id`, `user_id`, `title`
- `trip_agent_messages`: `conversation_id`, `user_id`, `role`, `content`, `meta`
- `trip_agent_actions`: `conversation_id`, `proposed_by_message_id`, `requested_by`, `status`, `summary`, `payload`, `execution_log`, `error_message`, `approved_by`, `approved_at`, `executed_by`, `executed_at`

Observed action statuses in runtime:
- `pending`, `approved`, `applied`, `rejected`, `failed`

Supported travel-agent mutation types (current):
- `update_trip_dates`
- `upsert_lodging`
- `import_itinerary_segments`

### 6.6 Funders and reporting
#### Grants
Entity: `grants`
- `organization_id`, `name`, `status`, `amount`, `start_date`, `end_date`, `description`, `deliverables`, `visibility`, `notes`, `scope`, `primary_project_id`

Grant enums:
- Status: `prospective`, `pending`, `active`, `completed`, `declined`
- Scope: `all`, `us`, `global`, `project`

#### Grant documents
Entity: `grant_documents`
- `grant_id`, `title`, `type`, `file_path`, `file_type`, `mime_type`, `file_size`, `ai_extracted_data`, `ai_processed`, `ai_summary`, `uploaded_by`

Grant document type enum:
- `application`, `agreement`, `report`, `amendment`, `other`

#### Reporting schemas and schema chat
- `grant_reporting_schemas`: `grant_id`, `version`, `status`, `schema_data`, `created_by`
- `schema_chatbot_conversations`: `grant_id`, `schema_id`, `conversation_type`, `messages`, `status`, `created_by`

Enums:
- Schema status: `draft`, `active`, `archived`
- Conversation type: `setup`, `refinement`, `question`
- Conversation status: `active`, `completed`, `abandoned`

### 6.7 Knowledge and Box
#### Project documents (search/index surface)
Entity: `project_documents`
- `project_id`, `title`, `description`, `type`, `document_type`, `file_path`, `file_type`, `url`, `mime_type`, `file_size`, `uploaded_by`
- `visibility`, `tags`, `suggested_tags`, `is_knowledge_base`, `ai_indexed`, `ai_summary`, `content_hash`, `cached_content`
- `last_seen_at`, `is_archived`, `missing_on_disk`, `google_doc_id`, `google_doc_type`, `last_synced_at`
- `grant_associations`, `metric_tags`

Document enums:
- `type`: `file`, `link`
- `document_type`: `policy_brief`, `testimony`, `model_legislation`, `report`, `analysis`, `factsheet`, `presentation`, `publication`, `blog_post`, `op_ed`, `other`

#### Knowledge collections
Entity: `kb_collections`
- `user_id`, `name`, `query`, `filters`

#### Box mirror
- `box_items`: `box_item_id`, `box_item_type`, `name`, `parent_box_folder_id`, `path_display`, `etag`, `sha1`, `size`, `owned_by_login`, `modified_at`, `trashed_at`, `permissions`, `raw_payload`, `last_synced_at`
- `box_project_document_links`: `box_item_id`, `project_id`, `project_document_id`, `visibility`, `sync_status`, `last_synced_at`, `last_error`, `created_by`
- `box_webhook_events`: `delivery_id`, `trigger`, `source_type`, `source_id`, `headers`, `payload`, `status`, `error_message`, `processed_at`

Webhook statuses in current flow:
- `received`, `processed`, `failed`

### 6.8 Team and HR
- `team_resources`: `title`, `description`, `type`, `category`, `audience`, `url`, `file_path`, `icon`, `sort_order`, `is_featured`, `created_by`, `last_reviewed`, `review_frequency_days`
- `team_messages`: `user_id`, `parent_id`, `content`, `screenshot_path`, `is_pinned`, `is_announcement`
- `accomplishments`: `user_id`, `title`, `description`, `type`, `visibility`, `date`, `source`, `attachment_path`, `added_by`, `is_recognition`, `contributors`, `project_id`, `grant_id`

Team enums:
- Resource category: `onboarding`, `hr`, `operations`, `style`, `tools`, `policy`, `resource`, `howto`, `template`, `general`
- Resource audience: `all`, `staff`, `management`, `admin`
- Accomplishment type: `recognition`, `award`, `feedback`, `milestone`, `speaking`, `media`, `learning`, `other`
- Accomplishment visibility: `personal`, `team`, `organizational`

### 6.9 Media and press
- `press_clips`: `title`, `url`, `outlet_name`, `outlet_id`, `journalist_id`, `journalist_name`, `published_at`, `clip_type`, `sentiment`, `status`, `reach`, `summary`, `quotes`, `notes`, `source`, `created_by`, `image_url`
- `inquiries`: `subject`, `description`, `status`, `urgency`, `received_at`, `deadline`, `journalist_id`, `journalist_name`, `journalist_email`, `outlet_id`, `outlet_name`, `project_id`, `handled_by`, `response_notes`, `ai_insights`, `resulting_clip_id`, `created_by`
- `pitches`: `subject`, `description`, `status`, `pitched_at`, `journalist_id`, `journalist_name`, `journalist_email`, `outlet_id`, `outlet_name`, `project_id`, `pitched_by`, `follow_ups`, `resulting_clip_id`, `notes`

Observed media statuses in use:
- Inquiry status: `new`, `responding`, `completed`, `declined`, `no_response`
- Inquiry urgency: `normal`, `urgent`, `breaking`
- Pitch status: `draft`, `sent`, `following_up`, `accepted`, `published`, `declined`, `no_response`
- Press clip sentiment: `positive`, `negative`, `mixed`, `neutral`

## 7) Relationship Map (Design-Level)
- `Project` <-> `Meeting`, `Person`, `Organization`, `Issue`, `Grant`
- `Project` -> `Document`, `Task`, `Decision`, `Milestone`, `Question`, `Note`, `Workstream`, `Publication`, `Event`, `ChatMessage`
- `Meeting` -> `AgendaItem`, `Action`, `Commitment`
- `Trip` -> `Destination`, `Segment`, `Lodging`, `GroundTransport`, `Expense`, `Sponsorship`, `Event`, `Document`, `Checklist`, `Guest`, `AgentConversation`
- `Grant` -> `GrantDocument`, `GrantReportingSchema`, `SchemaChatbotConversation`
- `BoxItem` -> `BoxProjectDocumentLink` -> `ProjectDocument` -> `Project`

## 8) Box Folder Taxonomy for UX and Ops
Root: `WRK` (configured by `BOX_ROOT_FOLDER_ID`)

Recommended top-level folders:
- `Projects`
- `Funders`
- `Team`
- `Meetings`
- `Travel`
- `Media-Press`
- `Knowledge`
- `Admin`

Project folder policy (current+planned):
- Each WRK project auto-provisions a Box folder.
- Child projects become subfolders under parent project.
- Contract work remains under project scope, not a disconnected top-level app domain.

Suggested project internal structure:
- `01_Operations`
- `02_Contracts`
- `03_Billing`
- `04_Reporting`
- `05_Deliverables`

Permission projection guidance:
- Box ACL projection feeds WRK `visibility` (`all` | `management` | `admin`).
- WRK must never broaden beyond Box effective access.

## 9) UX Specs by Domain (What Figma Should Design)
### 9.1 Global shell
Required frames:
- App shell with left nav + contextual right agent drawer (open and closed)
- Mobile shell with agent slide-over
- Entity switcher / global "Ask WRK" entry

### 9.2 Dashboard
Required blocks:
- Priority queue (tasks, commitments, approvals)
- Upcoming work cards (projects, meetings, trips)
- Agent summary card ("what changed since yesterday")
- Quick prompt launcher

### 9.3 Projects
Required flows:
- Project detail with persistent agent drawer
- Conversational updates for milestones, notes, docs, and team links
- Contract-work variant (proposed type) with billing/reporting cards

### 9.4 Contacts
Required flows:
- Person profile with timeline, org context, and next action
- Organization profile with linked people/projects/grants
- Agent prompts for relationship updates and follow-up planning

### 9.5 Meetings
Required flows:
- Prep -> capture -> summarize -> commitments pipeline
- Agent help for drafting notes/actions and extracting decisions
- Linked entity chips (people/org/project/funder)

### 9.6 Travel
Required flows:
- Itinerary and expenses with persistent travel agent drawer
- Conversational updates for dates/flights/lodging/expenses
- Action log visibility for each mutation
- Receipt-first expense capture pattern

### 9.7 Funders
Required flows:
- Grant pipeline and detail
- Reporting schema builder view
- Agent support for drafting reporting structures and summaries

### 9.8 Knowledge
Required flows:
- Unified search and saved collections
- Retrieval answer view with explicit sources
- Agent query panel that can pivot from "answer" to "action"

### 9.9 Media and Team
Required flows:
- Media pipeline with agent support for triage and drafting
- Team resources and communications with assistant support

### 9.10 Admin
Required flows:
- Permission editing
- Feedback triage
- Metrics monitoring
- Clear visualization of audit trails and recent automation events

## 10) Figma Component Requirements
Build reusable components with desktop and mobile variants:
- App shell and nav
- Entity header (status, owner, timeline)
- Section tabs/chips
- Conversation bubble (user/assistant/system)
- Action log card (status variants)
- Source/citation chip
- Audit timeline row
- Empty/loading/error/success states
- Modal patterns for create/edit/import
- Receipt uploader pattern

Required states for each core screen:
- Empty
- Loading
- Success
- Error
- Read-only permissions
- Agent-applied-changes

## 11) Annotation Rules for Figma
Every input and data card should include:
- UI label
- Canonical field key
- Data type
- Enum values (if any)
- Required/optional

Naming convention for handoff layers:
- `domain.entity.field_key`
- Example: `travel.trip.start_date`, `projects.project.status`, `contacts.person.next_action_at`

## 12) Gaps and Proposed Next Schema Support
Not yet implemented globally but recommended for v2:
- `agent_runs` (cross-domain run log)
- `agent_steps` (tool-level trace)
- `agent_approvals` (optional human gate model)
- source citation structure for all AI responses (not only travel actions)
- `project_type = contract`
- cleanup of access-level enum drift (`team` vs `staff`)

## 13) Success Criteria for UX redesign
- User can complete top updates through conversation with fewer clicks than form-only flow.
- Core trip/project updates reflect immediately in structured views.
- Every mutation is auditable in UI.
- Users can locate key docs/decisions from conversational entry points quickly.

## 14) Implementation Note for Design Handoff
If design proposes renamed tabs, labels, or categories, include a mapping table back to current canonical keys and route/module names before engineering handoff.
