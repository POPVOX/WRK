# Slack Agent MVP Scope (Week 5-6)

Related:
- `docs/WRK_AI_Architecture_Brief_FINAL.md`
- `docs/WRK_V2_DATA_CONTRACT.md`

Date: February 20, 2026
Status: Draft for pilot definition

## Objective

Ship a safe Slack-first agent that can complete 3 high-frequency workflows with auditability and approval gates.

## Workflow 1: Meeting Prep Brief on Demand

Trigger:

- User in Slack asks: "Prep me for [meeting/person/org]".

Behavior:

- Retrieve linked meetings, organizations, people, open actions, and relevant docs.
- Generate concise prep brief with:
  - attendee context
  - prior commitments
  - top 3 goals
  - suggested questions

Guardrails:

- Read-only workflow.
- Must include citations/source refs for each factual claim.

Success metric:

- Useful prep response in under 30 seconds.

## Workflow 2: Post-Meeting Follow-Up Capture

Trigger:

- User posts notes/transcript and asks for follow-ups.

Behavior:

- Extract actions, owners, due dates, and decisions.
- Present structured draft in Slack.
- On approval, write to WRK `actions`/`decisions`.

Guardrails:

- Write actions require explicit user confirmation in-thread.
- No automatic assignment to external people; internal users only for MVP.

Success metric:

- At least 80% of extracted follow-ups accepted with minor edits.

## Workflow 3: Weekly Project and Grant Pulse

Trigger:

- Scheduled weekly Slack digest to core team channel.

Behavior:

- Summarize:
  - overdue actions
  - grant reporting deadlines in next 30 days
  - meetings that still need notes
  - high-signal document changes in Box

Guardrails:

- Digest is read-only.
- Include source links for each section.

Success metric:

- Team reports reduced manual status-chasing effort week over week.

## Required Platform Features for MVP

- Slack command/event receiver
- Agent run logs (`who asked`, `what ran`, latency, result)
- Source/citation storage and rendering
- Approval checkpoint UI/action for write operations
- Fallback behavior when retrieval fails (no silent writes)

## Out of Scope for MVP

- Autonomous multi-step writes without approval
- Public channel-wide auto-responses without explicit invocation
- Cross-workspace Slack federation
