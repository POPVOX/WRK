# WRK Agent Infrastructure Implementation Backlog

Date: March 1, 2026  
Status: Execution backlog (aligned to current WRK codebase)

## Execution Progress (Updated March 3, 2026)

- Epic A (P0): In progress, core prompt-layering backend and admin preview/editor shipped.
- Epic B (P0): In progress, goal scheduler loop + command + idempotency tests shipped.
- Epic C (P0): In progress, staffer identity + credential vault schema/services + scoping tests shipped.
- Epic D (P1): In progress, memory + thread visibility schema/services/UI + boundary tests shipped.
- Cross-cutting notification layer (new): Notifications V1 shipped.
  - In-app notifications panel for all staff.
  - Dedicated `/notifications` page for all users.
  - Management notifications center (`/notifications/admin`) with template library and manual send controls.
  - Auto-notifications wired for project/team/travel events.
  - Scheduled `notifications:trip-upcoming --days=7` reminder loop added.

## 1) Current Baseline (Already in Code)

These items are implemented or in progress in the current workspace:

- Agent control plane:
  - `agents`, `agent_threads`, `agent_messages`, `agent_runs`, `agent_suggestions`, `agent_permissions`.
  - Core orchestration in `app/Services/Agents/AgentOrchestratorService.php`.
- Universal approval gate foundation:
  - `approval_requests` table.
  - `approval_request_id` links on `trip_agent_actions` and `agent_suggestions`.
  - `app/Services/ApprovalGateService.php`.
  - Trip-agent and orchestrator now enforce pending approval for non-management.
- Slack ingress foundation:
  - `POST /webhooks/slack`.
  - `slack_webhook_events` table.
  - Slack webhook controller/job/services pipeline in `app/Http/Controllers/Webhooks/SlackWebhookController.php`, `app/Jobs/ProcessSlackWebhookEvent.php`, `app/Services/Slack/*`.

## 2) Architecture Decisions to Lock Now

1. Control plane stays centralized on WRK hosting (no per-staffer Mac Minis).
2. Per-staffer assistant is a logical identity (`agent` + scoped credentials), not a dedicated machine.
3. Universal approval gate remains mandatory for risky writes.
4. Privacy defaults should be conservative: private by default for direct chat until policy says otherwise.

## 3) Execution Order (Epics)

## Epic A (P0): Prompt Layering and Policy Constitution

### A1. Migrations

- `2026_03_02_090000_create_agent_prompt_layers_table.php`
  - Fields: `id`, `agent_id` nullable, `layer_type` (`org|role|personal`), `content`, `version`, `updated_by`, timestamps.
  - Indexes: `agent_id`, `layer_type`, (`agent_id`, `layer_type`, `version`).
- `2026_03_02_090100_create_agent_prompt_overrides_table.php`
  - Fields: `id`, `agent_id`, `override_key`, `override_value` (json/text), `source_layer`, timestamps.
  - Unique: (`agent_id`, `override_key`).

### A2. Services

- `app/Services/Agents/PromptAssemblyService.php`
  - Responsibilities:
    - Merge org + role + personal + goal context.
    - Enforce precedence (org > role > personal).
    - Emit conflict diagnostics.
- `app/Services/Agents/PolicyConflictService.php`
  - Detect prohibited overrides and return actionable conflict messages.

### A3. Integrations

- Update `AgentOrchestratorService` to call `PromptAssemblyService`.
- Add prompt preview endpoint/service method for admin audit.

### A4. UI

- Admin editor for org and role layers.
- Read-only visibility for personal layer + version history.

### A5. Tests

- `tests/Feature/AgentPromptLayersTest.php`
  - Org-level prohibition cannot be overridden.
  - Role override beats personal.
  - Prompt preview contains effective merged content.

Acceptance criteria:
- Effective prompt is deterministic and auditable for every run.

---

## Epic B (P0): Standing Goals + Scheduler Loop

### B1. Migrations

- `2026_03_02_100000_create_agent_goals_table.php`
  - Fields: `agent_id`, `title`, `description`, `goal_type` (`monitor|prepare|coordinate`), `status`, `trigger_type`, `trigger_config` json, `output_config` json, `priority`, timestamps.
- `2026_03_02_100100_create_agent_goal_runs_table.php`
  - Fields: `goal_id`, `agent_run_id` nullable, `triggered_at`, `trigger_reason`, `status`, `output_summary`, `completed_at`, timestamps.
- `2026_03_02_100200_create_agent_goal_context_table.php`
  - Fields: `goal_id`, `context_key`, `context_value` json, `updated_at`.
  - Unique: (`goal_id`, `context_key`).

### B2. Services + Commands

- `app/Services/Agents/GoalTriggerService.php`
  - Evaluate cron/deadline/event trigger match.
- `app/Services/Agents/GoalEvaluationService.php`
  - Load active goals, resolve due triggers, create `agent_runs`.
- `app/Services/Agents/GoalOutputRouterService.php`
  - Route output to Slack/WRK notifications/approval queue.
- `app/Console/Commands/EvaluateAgentGoals.php`
  - Command signature: `agents:evaluate-goals`.

### B3. Scheduling

- Add to `routes/console.php`:
  - `Schedule::command('agents:evaluate-goals')->everyFifteenMinutes()->withoutOverlapping(...)->onOneServer();`

### B4. Tests

- `tests/Feature/AgentGoalSchedulerTest.php`
  - Due goal creates run once per window.
  - Paused/completed goals skipped.
  - Duplicate triggers idempotent.

Acceptance criteria:
- Goal loop runs safely on schedule with idempotent execution.

---

## Epic C (P0): Per-Agent Identity + Credential Vault

### C1. Migrations

- `2026_03_02_110000_add_staffer_id_to_agents_table.php`
  - Add `staffer_id` FK to `users` (nullable during rollout, later constrained).
- `2026_03_02_110100_create_agent_credentials_table.php`
  - Fields: `agent_id`, `service` (`gmail|box|gcal|slack`), `token_data` encrypted json, `scopes`, `expires_at`, `refreshed_at`, timestamps.
  - Unique: (`agent_id`, `service`).
- `2026_03_02_110200_create_user_slack_identities_table.php`
  - Fields: `user_id`, `slack_user_id`, `workspace_id`, timestamps.
  - Unique: (`workspace_id`, `slack_user_id`).

### C2. Services

- `app/Services/Agents/AgentCredentialService.php`
  - Store/retrieve/refresh scoped OAuth tokens.
- `app/Services/Slack/SlackIdentityService.php`
  - Resolve Slack user to WRK user using identity map.

### C3. Integrations

- Update Slack command router to use identity map first, then fallback lookup.
- Update Gmail/Calendar/Box operations to fetch credentials per agent.

### C4. Tests

- `tests/Feature/AgentCredentialScopingTest.php`
  - Agent cannot use another agent’s credentials.
  - Missing credentials fail closed with explicit error.

Acceptance criteria:
- Every agent action is attributable to one staffer-scoped identity.

---

## Epic D (P1): Public/Private Boundary + Memory

### D1. Migrations

- `2026_03_03_090000_create_agent_memory_table.php`
  - Fields: `agent_id`, `memory_type`, `content`, `source_message_id`, `visibility` (`public|private`), `confidence`, timestamps.
- `2026_03_03_090100_add_visibility_to_agent_threads_and_messages.php`
  - Add `visibility` to `agent_threads` and optional `agent_messages`.
  - Default: `private` for direct/staffer conversations.

### D2. Services

- `app/Services/Agents/MemoryExtractionService.php`
  - Extract and persist structured memory entries.
- `app/Services/Agents/MemoryQueryService.php`
  - Retrieve memory by relevance + visibility.
- `app/Services/Agents/VisibilityPolicyService.php`
  - Enforce cross-user access rules.

### D3. UI

- Per-thread visibility toggle (private/public).
- Memory audit panel with visibility tags.

### D4. Tests

- `tests/Feature/AgentVisibilityBoundaryTest.php`
  - Private thread/memory not returned in cross-agent query.
  - Public memory queryable organization-wide per role.

Acceptance criteria:
- No private memory leakage across agents/users.

---

## Epic E (P1): Approval Queue Productization (Universal)

### E1. Migrations

- `2026_03_03_100000_add_workflow_fields_to_approval_requests_table.php`
  - Add `resource_type`, `resource_id`, `request_origin`, optional `expires_at`, `priority`.
  - Add indexes for review queue filtering.

### E2. Services

- `app/Services/Approvals/ApprovalQueueService.php`
  - Unified queue API: list pending, approve, reject, execute callbacks.
- `app/Services/Approvals/ApprovalExecutionService.php`
  - Execute approved action with idempotency key and audit stamp.

### E3. UI + Slack actions

- Central management approval queue page.
- Slack interactive approval endpoint:
  - `POST /webhooks/slack/actions` for approve/reject buttons.

### E4. Tests

- `tests/Feature/ApprovalQueueTest.php`
  - Approval status transitions.
  - Only authorized users can approve/reject.
  - Approved actions execute once.

Acceptance criteria:
- Single queue covers all write-capable agent actions.

---

## Epic F (P1): Slack Assistant UX (Production Ready)

### F1. Migrations

- `2026_03_03_110000_add_response_and_route_fields_to_slack_webhook_events_table.php`
  - Add `route`, `handled_by_agent_id`, `response_status`, `response_error`.

### F2. Services

- `app/Services/Slack/SlackAssistantService.php`
  - Command intents:
    - `/wrk prep ...`
    - `/wrk followup ...`
    - `/wrk pulse`
  - Uses orchestrator + retrieval + approval gate.
- `app/Services/Slack/SlackCitationRendererService.php`
  - Attach source/citation snippets for factual claims.

### F3. Tests

- `tests/Feature/SlackAssistantFlowsTest.php`
  - Slash command routes to correct workflow.
  - Read-only flows return in-thread citations.
  - Write flows generate approval requests, no silent writes.

Acceptance criteria:
- Slack is usable as primary interface without bypassing safety controls.

---

## Epic G (P2): Inter-Agent Query Protocol

### G1. Migrations

- `2026_03_04_090000_create_agent_queries_table.php`
  - Fields: `from_agent_id`, `to_agent_id`, `query_type`, `content`, `priority`, `status`, `response`, `requested_at`, `responded_at`, `expires_at`, timestamps.
- `2026_03_04_090100_create_agent_authority_rules_table.php`
  - Fields: `role_pattern`, `target_pattern`, `allowed_query_types`, `max_priority`, `requires_approval`, timestamps.

### G2. Services

- `app/Services/Agents/AgentQueryRouterService.php`
  - Central request/response routing.
- `app/Services/Agents/AgentAuthorityService.php`
  - Authorize delegation and query types.

### G3. Tests

- `tests/Feature/InterAgentQueryTest.php`
  - Unauthorized request declined with reason.
  - Private information filtered before response.

Acceptance criteria:
- Inter-agent requests are auditable, authorized, and policy-filtered.

---

## Epic H (P2): Observability + Governance

### H1. Migrations

- `2026_03_04_100000_create_agent_metrics_daily_table.php`
  - Fields: `date`, `agent_id`, `runs_count`, `approval_block_count`, `avg_latency_ms`, `error_count`, `token_in`, `token_out`, `estimated_cost`.

### H2. Services/Jobs

- `app/Jobs/BuildAgentDailyMetrics.php`
- `app/Services/Agents/AgentHealthService.php`
  - Stale context detection.
  - Retrieval miss rate.
  - Approval bottleneck alerts.

### H3. UI

- Admin dashboard panel:
  - Throughput, blocked actions, errors, cost estimates, stale-context incidents.

### H4. Tests

- `tests/Feature/AgentMetricsAggregationTest.php`

Acceptance criteria:
- Leadership can see reliability, safety, and cost per agent.

---

## 4) Cross-Cutting Guardrails (Implement Throughout)

1. Fail closed: retrieval miss or stale context on write path -> propose-only + approval request.
2. Idempotency keys for all queued write actions.
3. Every outward action has audit metadata: actor, agent, source trigger, citations.
4. No external send without explicit approval policy outcome.

## 5) Suggested Sprint Plan (8 Weeks)

1. Sprint 1-2: Epic A + B (prompt layering + goal loop)
2. Sprint 3: Epic C (credentials + identity mapping)
3. Sprint 4: Epic D + E (memory visibility + centralized approval queue)
4. Sprint 5: Epic F (Slack workflows)
5. Sprint 6: Epic G + H (inter-agent + metrics)

## 6) Immediate Next 10 Tickets (Ready to Build)

1. Create `agent_prompt_layers` migration + model.
2. Create `PromptAssemblyService` and integrate into `AgentOrchestratorService`.
3. Create `agent_goals`/`agent_goal_runs`/`agent_goal_context` migrations.
4. Add `agents:evaluate-goals` command and schedule every 15 minutes.
5. Add `staffer_id` to `agents` and backfill from `owner_user_id`/`created_by`.
6. Create `agent_credentials` migration + encrypted token handling service.
7. Create `user_slack_identities` migration + Slack identity resolver.
8. Create `agent_memory` + visibility fields migration.
9. Build management approval queue UI over `approval_requests`.
10. Add Slack interactive approval endpoint and flow tests.
