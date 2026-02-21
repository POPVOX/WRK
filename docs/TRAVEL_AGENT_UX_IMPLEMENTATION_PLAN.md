# Travel Agent UX Implementation Plan

## Goal
Make trip management conversational-first: users can ask questions, request updates, and manage logistics in one persistent travel-agent experience.

## Product Direction
- Chat is the primary control surface.
- Structured itinerary/expense views remain, but agent access is always present.
- Agent responses must separate:
  - informational answers (no data changes)
  - executed updates (with explicit audit details)

## Core UX Model
1. Global trip pages:
- Keep itinerary/expenses/data views.
- Add a persistent travel-agent composer on non-agent tabs.
- Keep a full conversation workspace for deep thread review.

2. Conversation behavior:
- One entry action: `Enter`.
- Natural language for both Q&A and updates.
- Execution logs attached only when data changed.

3. Expense flow:
- Receipt-first upload UI.
- Auto-extraction when possible (currently PDF text extraction).
- Manual fields always editable for correction.

## IA Changes
- Primary trip destinations:
  - `Workspace` (full thread + snapshot)
  - `Overview`
- Secondary data pills:
  - itinerary, expenses, documents, events, sponsorship, checklist, notes

## Interaction Requirements
- User can ask “What changed?” and receive factual summary.
- User can submit bulk updates (dates, flights, lodging).
- Mentioned travelers (e.g. Aubrey/Ben/Bryan) get split itinerary assignment.
- Unsupported requests are acknowledged without false “applied” claims.

## Agent Execution Rules
- Informational questions return assistant text with empty `changes`.
- Supported updates create actions + execution logs.
- Multi-lodging updates in one message default to additional records (avoid overwrite).
- Segment imports dedupe per traveler+route+departure time.

## Current Implementation Status
- [x] Conversational `Enter` action and travel-agent language.
- [x] Informational Q&A path without action creation.
- [x] Itinerary segment import from long message text.
- [x] Per-traveler segment splitting from mentioned names.
- [x] Persistent travel-agent composer on non-agent tabs.
- [x] Expense modal redesigned to receipt-first flow with optional text extraction.
- [x] Traveler selector in expense modal (manager-capable, self-default for non-management).
- [x] Receipt upload auto-parse wiring fixed for PDF temp uploads.

## Next Build Slices
1. Side thread drawer (always accessible, not just Workspace).
2. Inline “Agent changed this” markers inside itinerary and expense rows.
3. OCR support for image receipts (not just PDF text extraction).
4. Trip rebooking workflow scaffolding (future booking integration).

## Acceptance Criteria
- Users can stay on itinerary/expenses and still chat with the agent.
- Informational prompts never mutate data.
- Updates are visible in underlying tabs within the same interaction cycle.
- Agent action logs are auditable and specific.
