# WRK Internal Product Refinement Plan

Status: Active internal plan
Audience: POPVOX Foundation staff and WRK maintainers
Scope: Optimize WRK for our own team before considering external productization

## Product Charter

WRK tells us what needs attention, preserves the context behind our work, and helps humans and agents produce the next useful artifact or action.

Every product change should do at least one of the following:

1. Reduce the effort required to understand what is happening.
2. Preserve useful institutional context.
3. Help a person or agent take the next appropriate action.

WRK is not intended to replace Box, Gmail, Google Calendar, or Slack. Those systems remain the working surfaces and source systems for files, email, schedules, and conversation. WRK connects their important activity to shared organizational context and durable outcomes.

## Product Kernel

The stable core of WRK is:

- People and organizations
- Projects and workstreams
- Meetings
- Actions and commitments
- Decisions and open questions
- Grants and funders
- Documents and Box links
- Cross-domain relationships
- Permissions and visibility
- Events, provenance, and agent runs

Travel, media, outreach, accomplishments, reporting, and future capabilities should be implemented as workflows over this kernel wherever possible, rather than as isolated applications with separate concepts and interaction patterns.

## Box-Native Operating Model

Box is the shared artifact plane for humans and agents:

- Staff work with normal Box folders and Box Drive.
- Codex, Cowork, and similar desktop tools can work with synchronized folders as local workspaces.
- WRK stores structured relationships, workflow state, permissions, and stable Box identifiers.
- Always-on agents use the Box API and the same workspace conventions.
- Important outputs remain inspectable, editable files rather than content trapped inside WRK.

The first implementation pilot will standardize five active project folders with:

- `WORKSPACE.md` for purpose, owners, status, constraints, and working conventions
- A machine-readable project manifest containing stable WRK and Box identifiers
- `Sources`, `Working`, `Deliverables`, `Decisions`, and `Agent Outputs` folders
- Clear draft, review, approved, and published states

Absolute Box Drive paths are not canonical. WRK and agents must use Box item IDs and resolve paths for the current environment.

## Flagship Workflows

### 1. Meeting lifecycle

Calendar event -> contextual preparation -> notes -> decisions and commitments -> assigned follow-up.

Initial success measures:

- Important meetings have a preparation brief before they begin.
- Notes are captured once and linked to relevant people, organizations, projects, and grants.
- Commitments have an owner and due date.
- Follow-up drafts are available within 24 hours.

### 2. Project and grant pulse

Project activity, files, meetings, deadlines, and commitments -> current status -> exceptions requiring attention -> weekly pulse.

Every active project should clearly answer:

- What is the objective?
- What changed recently?
- What is next?
- What is blocked?
- Which commitments and grant obligations are due?
- Where are the authoritative files?

### 3. Document production

Box sources -> research and synthesis -> working draft -> review -> approved deliverable -> filing and project linkage.

An agent entering a standardized project folder should be able to understand the assignment and produce a cited draft without a staff member manually reconstructing the context.

## First Product-Facing Slice: Needs You

The primary daily experience will become a unified attention queue. It will initially combine existing data rather than introduce a new task system.

Candidate items:

- Meetings needing preparation, notes, or follow-up
- Commitments and actions due or overdue
- Project blockers and stale status updates
- Grant deadlines and reporting obligations
- Documents awaiting review
- Agent outputs awaiting approval or correction
- Integration and indexing failures

Every item must explain why it surfaced, show its supporting context, and offer a direct next action such as complete, review, approve, delegate, snooze, or dismiss.

## Agent Operating Principles

- Agents receive service identities and explicit capability grants.
- Read-only and draft-only behavior is the default.
- Sending, publishing, deleting, spending, and other consequential actions require policy-based approval.
- Every run records its trigger, sources, model, outputs, actions, cost, status, and reviewer outcome.
- Retries are idempotent and changes are reversible wherever possible.
- Staff laptops are not the authoritative scheduler for organizational agents.
- Local desktop agents and managed always-on agents use the same Box workspace contract.

## Delivery Sequence

### Sprint 1: Stable foundation

- Close document visibility and nested-action authorization gaps.
- Make private-file relocation verifiable and non-destructive on failure.
- Restore a green PostgreSQL test baseline.
- Update dependency and deployment runbooks.
- Select five Box project-folder pilots.

### Sprint 2: Attention queue

- Inventory existing dashboard, notification, approval, and overdue-item queries.
- Define a common attention-item contract.
- Ship a read-only `Needs You` view using existing records.
- Add resolve, snooze, and deep-link behavior incrementally.

### Sprint 3: Flagship workflow

- Make the meeting lifecycle coherent end to end.
- Pilot meeting-prep and follow-up agents in draft-only mode.
- Measure preparation coverage, follow-up latency, and correction rate.

### Sprint 4: Box-native projects

- Create and validate the project-folder contract on five active projects.
- Generate workspace context and manifests from WRK.
- Test the same folders with WRK, Codex, Cowork, and a managed agent runner.

## Internal Product Cadence

- One accountable internal product owner
- A 30-minute weekly product review
- One observed real workflow each week
- In-app feedback reviewed before new feature work
- Monthly keep / merge / derive / retire review of screens and concepts
- Quarterly permission and agent-capability review

Feature requests should use this format:

> When this situation occurs, this person currently does X. It takes Y effort or creates Z risk. WRK should instead produce this outcome.

## Internal Success Measures

- Meeting preparation coverage
- Follow-up captured within 24 hours
- Commitments with an owner and due date
- Active projects with a current status and next action
- Grant reports delivered on time
- Search answers with useful cited sources
- Agent output acceptance, correction, and rejection rates
- Time from agent output to human decision
- Stale, duplicate, and orphaned record counts
- Weekly staff completing at least one meaningful workflow

## Explicit Non-Goals for Now

- External customer onboarding
- Billing, plans, or public packaging
- Broad tenant-level customization
- Replacing Box, Gmail, Calendar, or Slack
- Expanding agent autonomy before permissions, evidence, and recovery controls are reliable
