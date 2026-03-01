# POPVOX Foundation — Agent Infrastructure Technical Specification

**Per-Staffer Autonomous Agent System**

Version 1.0 — March 2026 | DRAFT — Internal Planning Document

---

## 1. Executive Summary

This specification defines the architecture for deploying per-staffer autonomous AI agents within POPVOX Foundation. Each agent operates as an organizational employee assigned to support a specific staff member, with the ability to monitor information sources, summarize developments, draft documents, and eventually coordinate across team members.

The system builds on WRK's existing agent control plane, Gmail integration, Box integration, and scheduler infrastructure. This spec covers the five components needed to extend the current platform into a full per-staffer autonomous agent system:

- **System Prompt Architecture** — Three-tier org/role/personal instruction hierarchy with inheritance and override protections.
- **Goal & Task Persistence** — Standing goals that the scheduler evaluates on recurring cycles, enabling autonomous background work.
- **Per-Agent Identity & Context** — Scoped credentials, memory, and tool access per agent instance.
- **Public/Private Boundary** — Conversation and memory visibility controls governing what other staff can query.
- **Inter-Agent Query Interface** — Protocol for agents to request information from and coordinate with other agents.

The implementation follows a phased approach: monitor and summarize (read-only, low risk), then draft and prepare (read-write with approval), then coordinate across agents (inter-agent communication).

---

## 2. System Prompt Architecture

Every agent's behavior is governed by a layered system prompt that ensures organizational consistency while allowing personalization. The key design principle is that the agent is an employee of POPVOX Foundation first, assigned to a staffer second. Organizational rules always take precedence.

### 2.1 Three-Tier Hierarchy

#### Tier 1: Organization Layer (Immutable)

Set by leadership. Applies to all agents. Cannot be overridden by role or personal layers. Updated centrally and propagated automatically to all agent instances.

Contents include:

- **Mission & values** — POPVOX Foundation's mission, approach to nonpartisan civic technology, and core principles.
- **Communication norms** — Tone, voice, and style guidelines. How the organization presents itself externally and communicates internally.
- **Confidentiality policy** — What can and cannot be shared externally, with partners, and between staff. Classification of sensitive information.
- **Behavioral boundaries** — Actions agents must never take (e.g., sending external communications without approval, making commitments on behalf of the organization, sharing donor information).
- **Escalation rules** — When to flag issues to leadership rather than attempting autonomous resolution.
- **Compliance requirements** — Nonprofit governance constraints, grant reporting obligations, data handling requirements.

#### Tier 2: Role Layer (Managed)

Set by management in coordination with the staffer. Defines the agent's understanding of its person's role within the organization.

Contents include:

- **Role description** — Job title, responsibilities, reporting relationships, and scope of authority.
- **Active projects** — Current project assignments with context, deadlines, and key stakeholders.
- **Key contacts** — Regular collaborators, partner contacts, and communication preferences for each.
- **Tool access scope** — Which Box folders, email accounts, calendar systems, and Slack channels the agent can access.
- **Decision authority** — What the agent can do autonomously vs. what requires staffer approval vs. what requires leadership approval.

#### Tier 3: Personal Layer (Organic)

Shaped by the staffer through interaction over time. Accumulated preferences, working patterns, and contextual knowledge.

Contents include:

- **Working style preferences** — Preferred summary format, communication style, level of detail, time-of-day preferences.
- **Accumulated context** — Ongoing projects, recent conversations, pending decisions, and relationship context built through daily interaction.
- **Personal templates** — Preferred formats for recurring outputs (weekly updates, partner emails, meeting notes).

### 2.2 Inheritance & Override Rules

The prompt assembly follows strict precedence rules to ensure organizational integrity:

| Rule | Behavior | Example |
|------|----------|---------|
| Org overrides all | If org layer prohibits an action, role and personal layers cannot enable it. | Org says "never share donor data" — personal preference cannot override this. |
| Role overrides personal | Role-layer boundaries constrain personal-layer flexibility. | Role says "draft communications require approval" — personal layer cannot auto-send. |
| Personal fills gaps | Where org and role layers are silent, personal preferences govern. | Summary format, notification timing, communication style within allowed bounds. |
| Conflicts escalate | If the agent detects a conflict between layers, it flags the conflict to the staffer and pauses. | Staffer asks agent to share a document the org layer classifies as confidential. |

### 2.3 Prompt Assembly

At runtime, the agent's system prompt is assembled by concatenating the three tiers in order. The orchestration layer constructs the final prompt before each API call:

```
[ORG_LAYER] + [ROLE_LAYER] + [PERSONAL_LAYER] + [GOAL_CONTEXT] + [CONVERSATION_HISTORY]
```

The org layer is stored as a single shared record and injected first in every agent's prompt. The role layer is stored per-agent and managed through an admin interface. The personal layer is stored per-agent and updated by the agent itself (with guardrails preventing it from modifying org or role content).

### 2.4 Data Model

| Table | Key Fields | Notes |
|-------|------------|-------|
| `agent_prompt_layers` | id, agent_id (nullable), layer_type (org\|role\|personal), content (text), version, updated_by, updated_at | agent_id is NULL for org layer (shared). Versioned for audit trail. |
| `agent_prompt_overrides` | id, agent_id, override_key, override_value, source_layer | Structured key-value overrides for specific settings (e.g., max_autonomy_level, can_send_email). |

### 2.5 Admin Interface Requirements

- Org layer editor accessible only to leadership/admin roles.
- Role layer editor accessible to management, with change history.
- Personal layer viewable (but not directly editable) by management for audit purposes.
- Version history and diff view for all layers.
- "Propagate" action that pushes org layer updates to all active agents.
- Preview mode: see the assembled prompt for any agent before deployment.

---

## 3. Goal & Task Persistence

The current WRK orchestrator handles one-shot agent runs: a trigger, a plan, approval, execution. Autonomous agents require standing goals that persist across runs and are evaluated on recurring schedules. This is the core difference between a chatbot and an agent.

### 3.1 Goal Types

| Type | Description | Schedule Pattern | Example |
|------|-------------|-----------------|---------|
| Monitor | Watch sources for changes or conditions. Read-only. Produces summaries or alerts. | Cron-based (hourly, daily, weekly) or event-triggered (webhook) | "Every morning, summarize new emails and Box activity related to ParlLink deployments." |
| Prepare | Assemble information and draft outputs. Read-write to documents. Requires approval before delivery. | Deadline-driven (X days before due date) or on-demand | "One week before the WFD course, compile case studies and draft a briefing doc." |
| Coordinate | Request information from or delegate tasks to other agents. Manages multi-person workflows. | Event-driven (triggered by status changes or requests from other agents) | "Collect status updates from all project leads and compile a weekly leadership summary." |

### 3.2 Goal Lifecycle

Goals move through a defined lifecycle:

- **Draft** — Goal is being defined. Not yet active.
- **Active** — Goal is live. The scheduler evaluates it on each cycle.
- **Paused** — Temporarily suspended (e.g., staffer is on leave, project is on hold).
- **Completed** — Goal has been fulfilled (for deadline-driven goals) or explicitly closed.
- **Archived** — Retained for institutional memory but no longer evaluated.

### 3.3 Scheduler Integration

The existing WRK scheduler (`routes/console.php`) already runs periodic reconciliation tasks. Goal evaluation extends this with a new scheduled job:

- **Goal Evaluation Cycle** — Runs every 15 minutes (configurable). For each active agent, loads active goals and checks whether any goal's trigger condition is met.
- **Trigger Conditions** — Cron expression matched, webhook event received, deadline threshold crossed, or manual invocation.
- **Run Creation** — When a goal triggers, the orchestrator creates an `agent_run` linked to the goal, assembles the prompt (including goal-specific context), calls the API model, and routes the output to the appropriate channel.
- **Output Routing** — Results are delivered based on goal configuration: Slack message, email draft, WRK notification, Box document, or held in the approval queue.

### 3.4 Data Model

| Table | Key Fields | Notes |
|-------|------------|-------|
| `agent_goals` | id, agent_id, title, description, goal_type (monitor\|prepare\|coordinate), status (draft\|active\|paused\|completed\|archived), trigger_type, trigger_config (JSON), output_config (JSON), priority, created_at, updated_at | Core goal definition. trigger_config holds cron expressions, webhook filters, or deadline rules. |
| `agent_goal_runs` | id, goal_id, agent_run_id, triggered_at, trigger_reason, status (pending\|running\|completed\|failed), output_summary, completed_at | Links goal evaluations to agent runs. Tracks history of all executions for a goal. |
| `agent_goal_context` | id, goal_id, context_key, context_value (JSON), updated_at | Persistent context that carries across runs for a goal. E.g., "last_checked_email_id", "previous_summary_date". |

### 3.5 Goal Definition Interface

Staff should be able to define goals through natural language conversation with their agent, which the agent translates into structured goal records:

> *Staffer: "Every Monday morning, give me a summary of all ParlLink-related emails and any new files in the Caribbean deployments folder."*
>
> **Agent creates:** Monitor goal with weekly cron trigger (Monday 7:00 AM), scoped to Gmail search "ParlLink" + Box folder /Caribbean-Deployments, output via Slack DM.

Management should also be able to assign goals to agents through an admin interface (e.g., org-wide reporting goals pushed to all agents).

---

## 4. Per-Agent Identity & Context

Each agent instance represents a distinct organizational entity with its own credentials, memory, and tool access. This section defines how agents are provisioned and how their context is managed.

### 4.1 Agent Provisioning

When a new staffer joins (or when the system is first deployed), an agent is created with the following:

- **Identity** — Name (typically the staffer's name + "Assistant", e.g., "Marci's Assistant"), unique agent ID, assigned staffer.
- **Credentials** — OAuth tokens scoped to the staffer's accounts: Gmail (read + draft), Box (read + write to designated folders), Google Calendar (read + create events), Slack (post to designated channels + DM the staffer).
- **Prompt Layers** — Org layer (inherited), role layer (configured by management), personal layer (initialized empty, grows over time).
- **Goals** — Initial set of goals configured by management (e.g., standard monitoring goals all agents share) plus any staffer-defined goals.

### 4.2 Credential Scoping

The current WRK integration architecture connects to Gmail and Box at an organizational level. Per-agent deployment requires scoping credentials to individual users:

| Integration | Current State | Required Change |
|-------------|--------------|-----------------|
| Gmail (GoogleGmailService) | Org-level OAuth connection | Per-user OAuth tokens stored in `agent_credentials` table. Each agent authenticates as its staffer's Gmail account. |
| Box (BoxAccessService) | Org-level with ACL tracking | Per-user tokens OR org-level token with agent-specific folder scope rules. Box ACL tracking already supports per-user permissions — leverage this. |
| Google Calendar | Synced via scheduler | Per-user OAuth tokens. Agent reads staffer's calendar and can create events (with approval). |
| Slack | Not yet integrated | Bot token with per-channel permissions. Each agent posts as itself (not as the staffer) to maintain accountability. |

### 4.3 Agent Memory

Each agent maintains persistent memory that accumulates over time. This is distinct from goal-specific context (which tracks operational state) — agent memory captures relationship context, learned preferences, and institutional knowledge.

- **Short-term memory** — Recent conversation history, currently active tasks, pending approvals. Stored in `agent_conversations` and loaded into prompt context for each interaction.
- **Long-term memory** — Extracted facts, learned preferences, key decisions, relationship context. Stored as structured records in `agent_memory` and selectively loaded based on relevance to current task.
- **Institutional memory** — When a staffer departs, their agent's non-private long-term memory is preserved and made available to the successor's agent. This is the knowledge continuity mechanism.

### 4.4 Data Model

| Table | Key Fields | Notes |
|-------|------------|-------|
| `agents` | id, staffer_id, name, status (active\|suspended\|archived), model_provider, model_id, created_at | Core agent record. Extends existing agent model. |
| `agent_credentials` | id, agent_id, service (gmail\|box\|gcal\|slack), token_data (encrypted JSON), scopes, expires_at, refreshed_at | Per-agent OAuth tokens. Encrypted at rest. |
| `agent_memory` | id, agent_id, memory_type (fact\|preference\|decision\|relationship), content, source_conversation_id, visibility (public\|private), confidence, created_at, updated_at | Structured memory records. Visibility flag controls cross-agent access. |
| `agent_conversations` | id, agent_id, conversation_type (chat\|goal_run\|inter_agent), visibility (public\|private), started_at, last_message_at | Conversation containers. Visibility set at conversation level. |
| `agent_messages` | id, conversation_id, role (user\|assistant\|system), content, created_at | Individual messages within conversations. |

---

## 5. Public/Private Boundary

Agents operate in a shared organizational context where other staff members can query any agent for work-related information. However, staff also need a space for private brainstorming, early-stage thinking, and sensitive conversations with their agent. The public/private boundary manages this tension.

### 5.1 Visibility Model

| Mode | Who Can Access | What's Included | Use Cases |
|------|---------------|-----------------|-----------|
| Public (default) | Any staff member via their own agent or direct query | Work status, project updates, schedule availability, document locations, task progress, institutional knowledge | "When is Marci free Tuesday?" "What's the status of the NDI Albania project?" "Where is the latest ParlLink deployment report?" |
| Private | Only the assigned staffer and the agent | Brainstorming sessions, draft thinking, personal reflections, sensitive personnel discussions, early-stage ideas | "Let me think through whether we should restructure the team." "Help me brainstorm the Google.org proposal framing." "Draft some feedback for a direct report." |

### 5.2 Privacy Rules

- **Default is public** — All conversations and memory are public unless explicitly flagged private. This aligns with the organizational principle that work is shared.
- **Private is explicit** — The staffer must actively enter private mode (via command, toggle, or keyword). The agent confirms the switch.
- **Memory respects boundaries** — Facts extracted from private conversations are tagged private in `agent_memory` and excluded from cross-agent queries.
- **Private is reversible** — A staffer can reclassify a private conversation or memory as public (but not the reverse — once public, it stays public to maintain trust).
- **Management has audit access** — Leadership can access private conversations for legitimate organizational purposes (compliance, investigation) with appropriate notice. This mirrors standard employer policies for workplace communications.
- **Departure protocol** — When a staffer leaves, private conversations and memory are handled per organizational policy: options include purge, archive with restricted access, or transfer to leadership review. This should be defined in the org prompt layer and communicated to staff during onboarding.

### 5.3 Cross-Agent Query Handling

When staff or agents query another agent, the responding agent applies these rules:

- **Check visibility** — Only surface information from public conversations and public memory.
- **Check role authorization** — Some information may be public but role-restricted (e.g., budget details accessible to leadership agents only). The role layer defines these restrictions.
- **Provide helpful responses** — For schedule queries, project status, and document locations, the agent should be forthcoming. The goal is to be as helpful as a well-informed human assistant.
- **Decline gracefully** — If a query touches private information, the agent responds with "I don't have public information about that" without revealing that private information exists.

### 5.4 Implementation Approach

The visibility flag is applied at two levels:

- **Conversation level** — The `agent_conversations.visibility` field controls whether the conversation's content is accessible to cross-agent queries. Set when the conversation begins or when the staffer toggles privacy.
- **Memory level** — The `agent_memory.visibility` field controls whether extracted facts are accessible. Inherited from the source conversation by default, but can be independently managed.

The cross-agent query interface (Section 6) filters results based on these flags before returning information.

---

## 6. Inter-Agent Query Interface

This component enables agents to communicate with each other for information gathering and task coordination. While full coordination (Tier 3) is a later phase, the data model and protocol should be established now to avoid architectural rework.

### 6.1 Communication Model

Inter-agent communication follows a request/response pattern routed through the orchestration layer. Agents never communicate directly — all messages pass through the orchestrator, which enforces authorization, visibility, and logging.

**Flow:** Requesting Agent → Orchestrator → Target Agent → Orchestrator → Requesting Agent

### 6.2 Query Types

| Query Type | Description | Authorization | Phase |
|-----------|-------------|---------------|-------|
| `info_request` | Ask another agent for factual information (schedule, project status, document location). | Any agent can query any other agent. Visibility rules apply. | Phase 1 (monitor) |
| `action_request` | Ask another agent to perform an action (draft a document section, schedule a meeting, update a status). | Requires requester's role to have delegation authority over the target's role. | Phase 2 (prepare) |
| `coordination_request` | Multi-step workflow involving multiple agents (compile a cross-team report, schedule a group meeting). | Requires leadership-level or project-lead authority. | Phase 3 (coordinate) |

### 6.3 Authority & Priority

Inter-agent requests carry an implicit priority based on the organizational hierarchy encoded in the role layers:

- **Leadership agents** — Requests from the ED's agent (or equivalent) carry highest priority. Target agents should prioritize these and can act on them with less approval friction.
- **Peer agents** — Requests between agents at the same level are treated as informational. The target agent provides information but does not rearrange its staffer's priorities.
- **Cross-functional** — For project-based authority (e.g., a project lead requesting updates from team members' agents), the role layer should encode project-specific delegation authority.

Critically, no agent can override its own staffer's explicit instructions based on another agent's request. If there's a conflict, the agent flags it to its staffer: "Marci's assistant is asking for the deployment report by tomorrow, but you've marked that as low priority. How should I respond?"

### 6.4 Data Model

| Table | Key Fields | Notes |
|-------|------------|-------|
| `agent_queries` | id, from_agent_id, to_agent_id, query_type (info\|action\|coordination), content, priority, status (pending\|processing\|completed\|declined\|expired), response, requested_at, responded_at, expires_at | All inter-agent communications. Logged for audit and institutional memory. |
| `agent_authority_rules` | id, role_pattern (e.g., "leadership"), target_pattern, allowed_query_types, max_priority, requires_approval | Defines what query types each role can make to other roles. Evaluated by the orchestrator before routing. |

### 6.5 Protocol Design

Each inter-agent query follows a standardized protocol:

1. **Request:** Requesting agent formulates query with type, content, and desired response format.
2. **Authorization:** Orchestrator checks `agent_authority_rules`. If unauthorized, returns declined with reason.
3. **Routing:** Orchestrator assembles target agent's prompt with the query as context and calls the API model.
4. **Filtering:** Target agent processes the query, applying visibility rules to exclude private information.
5. **Response:** Orchestrator returns the response to the requesting agent, logging the full exchange.

---

## 7. Implementation Phases

### Phase 1: Monitor & Summarize (Weeks 1–4)

Read-only autonomous agents. Validates the core loop of goal evaluation, API calls, and output delivery.

**Deliverables:**

- `agent_prompt_layers` table and org layer editor.
- `agent_goals` and `agent_goal_runs` tables with scheduler integration.
- Per-agent Gmail and Box credential scoping.
- 2–3 monitor goals running for a pilot agent (e.g., daily inbox summary, weekly Box activity report, congressional hearing monitor).
- Output delivered via Slack DM or WRK notification.

**Success Criteria:**

- Pilot agent produces useful daily summaries without manual intervention.
- Org layer prompt changes propagate to agent behavior within one scheduler cycle.
- Goal evaluation runs reliably on schedule with no missed cycles.

### Phase 2: Draft & Prepare (Weeks 5–8)

Agents gain write access and produce draft outputs for staffer review.

**Deliverables:**

- Approval queue in WRK: pending drafts surface for staffer review with approve/edit/reject actions.
- Box write access: agents can create and update documents in designated folders.
- Gmail draft creation: agents can draft emails parked in the staffer's drafts folder.
- "Prepare" goal type with deadline-driven triggers.
- Agent memory system (`agent_memory` table) with public/private tagging.

**Success Criteria:**

- Agent drafts a grant report section that requires only moderate editing.
- Approval queue processes at least 10 drafts per week across pilot agents.
- Private conversations are correctly excluded from cross-agent queries.

### Phase 3: Coordination (Weeks 9–12)

Agents communicate with each other for information gathering and workflow coordination.

**Deliverables:**

- Inter-agent query protocol and `agent_queries` table.
- Authority rules engine based on role layers.
- Cross-agent status report: leadership agent queries all agents and compiles organizational summary.
- Schedule coordination: agents negotiate meeting times across calendars.
- "Coordinate" goal type with event-driven triggers.

**Success Criteria:**

- Leadership agent produces a weekly cross-team status report from inter-agent queries.
- Meeting scheduling via agent coordination succeeds in 80%+ of attempts.
- Authority rules correctly prevent unauthorized delegation.

### Phase 4: Org-Wide Rollout (Weeks 13–16)

Deploy agents for all staff, refine based on Phase 1–3 learnings, and establish ongoing governance.

**Deliverables:**

- Agent provisioning workflow for new staff.
- Departure/transition protocol for agent knowledge transfer.
- Staff training materials and onboarding guide.
- Governance review: org layer prompt review cycle, audit procedures, feedback mechanisms.
- Monitoring dashboard: agent activity, goal execution rates, API costs, error rates.

---

## 8. Cost & Infrastructure

### 8.1 Compute Infrastructure

The orchestration layer (scheduler, goal evaluator, prompt assembler, inter-agent router) runs on a single server. All AI inference happens via API, so the server's role is coordination, not computation.

| Option | Estimated Cost | Pros | Cons |
|--------|---------------|------|------|
| Mac Mini (M-series) in office | $600–$800 one-time + electricity | Physical control, no ongoing hosting fees, sufficient for orchestration | Requires network stability, physical access for maintenance, power/cooling |
| Cloud VM (AWS/GCP/DO) | $20–$80/month | No physical maintenance, easy scaling, reliable uptime | Ongoing cost, data leaves premises |
| Existing WRK hosting | Incremental | No new infrastructure, integrated with existing deployment | May need compute headroom for scheduler load |

**Recommendation:** Start with existing WRK hosting infrastructure if it has headroom. The scheduler is already running there, and adding goal evaluation jobs is incremental. Move to dedicated infrastructure only if performance requires it.

### 8.2 API Costs

Estimated monthly API costs per agent, based on typical usage patterns:

| Activity | Estimated Calls/Month | Avg Tokens/Call | Est. Monthly Cost (Claude Sonnet) |
|----------|----------------------|-----------------|----------------------------------|
| Daily summaries (monitor) | 30 | ~5,000 in / ~2,000 out | $2–$4 |
| Weekly reports (monitor) | 4 | ~15,000 in / ~4,000 out | $1–$2 |
| Draft preparation (prepare) | 10–20 | ~10,000 in / ~5,000 out | $3–$8 |
| Ad-hoc queries (chat) | 50–100 | ~3,000 in / ~1,000 out | $3–$6 |
| Inter-agent queries | 20–40 | ~2,000 in / ~500 out | $1–$2 |

**Estimated total:** $10–$22 per agent per month, or roughly $60–$130/month for a 6-person team. This is well within reasonable operating costs for the productivity gains expected. Costs will vary based on model choice (Sonnet vs. Opus vs. GPT-4o) and usage intensity.

---

## 9. Security & Governance

### 9.1 Data Security

- **Credential encryption** — All OAuth tokens stored in `agent_credentials` are encrypted at rest using AES-256 or equivalent.
- **Principle of least privilege** — Each agent's token scopes are limited to what its goals require. A monitor-only agent doesn't need Gmail send permissions.
- **Token rotation** — OAuth refresh tokens are rotated on the standard provider schedule. Expired tokens trigger alerts, not silent failures.
- **Audit logging** — All agent actions (API calls, file access, email reads, inter-agent queries) are logged with timestamps for review.

### 9.2 Organizational Governance

- **Org layer review cycle** — The org-level system prompt is reviewed quarterly by leadership to ensure it reflects current organizational values and policies.
- **Agent activity review** — Monthly review of agent activity logs by management to identify patterns, issues, or misuse.
- **Staff feedback loop** — Regular check-ins with staff on agent usefulness, accuracy, and any concerns about the system.
- **Incident response** — If an agent takes an incorrect or harmful action, there is a defined process: pause the agent, review the run logs, identify the root cause (bad goal definition, prompt issue, model error), and remediate.

### 9.3 Staff Policies

- **Transparency** — Staff are informed about what their agent records, what is public vs. private, and management's audit access.
- **Onboarding** — New staff receive training on how to interact with their agent, set goals, manage privacy, and escalate issues.
- **Departure** — Offboarding includes agent transition: private data handling per policy, knowledge transfer to successor agent, credential revocation.

---

## 10. Open Questions

The following decisions should be resolved before or during Phase 1 implementation:

1. **Model selection strategy:** Should all agents use the same model, or should different goal types use different models (e.g., Haiku for simple summaries, Sonnet for drafting, Opus for complex coordination)?

2. **Slack integration architecture:** One Slack bot per agent (clear identity) or one organizational bot that routes to the appropriate agent (simpler setup)?

3. **Memory extraction approach:** How should the agent extract structured memory from conversations? Options include post-conversation summarization, real-time extraction, or staffer-directed ("remember this").

4. **Box folder scoping:** Should agent access mirror the staffer's Box permissions exactly, or should it be more restrictive (e.g., only folders listed in the role layer)?

5. **External communication policy:** Should agents ever send emails or Slack messages to external parties (partners, funders), or should all external communication require human sending?

6. **Agent identity in communications:** When an agent sends a Slack message or email draft, should it be clear that an AI agent composed it? If so, what's the disclosure language?

7. **Practice Intelligence integration:** Should the agent system generate structured research data as a byproduct of operational work? If so, what data schema should be captured, and how does this feed into the broader Practice Intelligence framework?
