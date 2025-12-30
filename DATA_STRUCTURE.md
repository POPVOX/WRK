# PVOXWRK Data Structure

## Models (25 total)

| Model | Description |
|-------|-------------|
| **User** | Team members with profile, access levels, manager hierarchy |
| **Organization** | Companies, agencies, nonprofits, funders, congressional offices |
| **Person** | Contacts/individuals with CRM fields |
| **PersonInteraction** | Interaction history for people |
| **ContactView** | Contact view tracking |
| **Issue** | Policy issues/topics |
| **Meeting** | Meetings with calendar integration |
| **MeetingAttachment** | Files attached to meetings |
| **Action** | Action items from meetings |
| **Project** | Projects with nesting, status, scope, lead, tags |
| **ProjectNote** | Notes on projects |
| **ProjectDocument** | Documents with AI/KB support, Google Docs |
| **ProjectDecision** | Decisions made on projects |
| **ProjectMilestone** | Project milestones |
| **ProjectEvent** | Project-related events |
| **ProjectPublication** | Publications linked to projects |
| **ProjectQuestion** | Questions/Q&A for projects |
| **ProjectWorkstream** | Workstreams within projects |
| **ProjectChatMessage** | AI collaborator chat messages |
| **ProfileAttachment** | Profile attachments |
| **KbCollection** | Knowledge base collections |
| **TeamMessage** | Team hub messages |
| **TeamResource** | Team hub resources with audience/category |
| **Grant** | Funder grants with visibility |
| **ReportingRequirement** | Grant reporting deadlines |

---

## Database Tables

### Core Entities
- `users` – Team profiles, access levels, manager relationships
- `organizations` – With funder/congressional fields, committees
- `people` – With LinkedIn, photo URL, CRM fields
- `issues` – Policy topics
- `projects` – With parent/child nesting, project_type, sort_order

### Grants & Funders
- `grants` – Funding with visibility levels
- `grant_project` – Pivot: grants ↔ projects with allocated amounts
- `reporting_requirements` – Grant report deadlines

### Meetings
- `meetings` – With Google Calendar fields, title, lead contact
- `meeting_attachments` – Files for meetings
- `meeting_organization` – Pivot: meetings ↔ organizations
- `meeting_person` – Pivot: meetings ↔ people
- `meeting_issue` – Pivot: meetings ↔ issues
- `meeting_user` – Pivot: meetings ↔ users
- `meeting_project` – Pivot: meetings ↔ projects
- `actions` – Action items from meetings

### Projects
- `project_organization` – Pivot: projects ↔ organizations
- `project_person` – Pivot: projects ↔ people
- `project_issue` – Pivot: projects ↔ issues
- `project_staff` – Pivot: projects ↔ staff/users
- `project_decisions` – Decisions on projects
- `project_milestones` – Project milestones
- `project_questions` – Q&A for projects
- `project_notes` – Notes on projects
- `project_documents` – Documents with Google Docs, visibility

### Workspace
- `project_publications` – Publications
- `project_events` – Events
- `project_workstreams` – Workstreams
- `project_chat_messages` – AI collaborator messages

### Team Hub
- `team_messages` – Team chat messages
- `team_resources` – Resources with audience, review tracking

### Knowledge Base
- `kb_collections` – Document collections
- `kb_index_fts5` – Full-text search index

### Supporting
- `profile_attachments` – Profile file attachments
- `people_interactions` – CRM interaction tracking
- `contact_views` – Contact view history
- `cache`, `jobs`, `job_batches`, `failed_jobs` – Laravel system tables
