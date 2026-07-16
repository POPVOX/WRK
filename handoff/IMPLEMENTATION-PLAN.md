# Morning Desk implementation plan

This plan turns the high-fidelity handoff into a sequence of deployable increments. The
goal is to keep WRK usable throughout the redesign: shared foundations land first, then
complete workflows move to the new system one at a time.

## Product principles

- Treat `DESIGN-SPEC.md` and `mockups/` as the visual source of truth.
- Preserve routes, permissions, and active background jobs while navigation changes.
- Prefer live product data over mock data, even when a first increment has fewer rows.
- Complete each workflow across desktop, mobile, empty, loading, and error states before
  moving to the next one.
- Keep the outreach loop visible: Congress search → list → campaign → reply → meeting →
  relationship history → follow-up list.

## Phase 0 — shared foundation and Today

Status: in progress

- Install the Newsreader, Public Sans, and IBM Plex Mono type system.
- Add the warm-paper palette, hairline rules, compact controls, status text, and focus
  treatment as reusable CSS primitives.
- Replace the legacy grouped sidebar with Today, Inbox, Meetings, Projects, People,
  Outreach, and Travel.
- Add contextual People and Outreach sub-navigation.
- Move administrative destinations into the user menu.
- Rebuild `/dashboard` as the Morning Desk using live meetings, tasks, coverage, and
  weekly activity.
- Preserve the existing Ask WRK command behavior in a quieter command-bar treatment.

## Phase 1 — daily records

- Rebuild `/projects` as status-grouped ruled rows with scope, lead, timeline, health,
  and inline sub-project expansion.
- Rebuild `/contacts` and `/organizations` as relationship-first tables.
- Rebuild `/meetings` as a chronological agenda with the notes-debt lifecycle.
- Add shared table, filter-pill, segmented-toggle, avatar, status-text, and empty-state
  components.

## Phase 2 — outreach loop

- Rebuild Congress Explorer search as readable facets and make “Build list” the primary
  result action.
- Rebuild list creation/review around one guided list-management flow.
- Rebuild Campaigns as state-first sending, draft, and completed rows/cards.
- Rebuild campaign analytics around sent → opened → replied → meetings booked.
- Add pipeline links and suggested follow-up-list creation.

## Phase 3 — relationship documents

- Rebuild meeting detail as a document with a contextual right rail.
- Rebuild contact and congressional staff detail around identity, freshness, open
  follow-ups, and a unified activity timeline.
- Add the compact 64px detail-page rail.
- Move destructive actions into detail-rail footers.

## Phase 4 — unified Inbox

- Merge actionable Gmail threads, campaign replies, mentions, and system notices into
  one triage contract.
- Add consistent inline actions and CRM context to every row.
- Route campaign replies into contact/staff timelines and meeting creation.
- Retire the separate Needs You and notification destinations after parity is verified.

## Phase 5 — completion and cleanup

- Migrate Travel, Funding, Media, Team, and Admin surfaces to the shared primitives.
- Audit responsive layouts, keyboard navigation, contrast, focus states, and reduced
  motion.
- Remove superseded legacy CSS and unused navigation code.
- Add visual regression snapshots for all ten handoff screens at desktop and mobile
  breakpoints.

## Known data work

- Add an explicit “no notes needed” state for meetings.
- Normalize relationship `last_touch_at` across meetings, Gmail, and campaigns.
- Record meetings booked as a first-class campaign outcome.
- Unify Inbox item types behind a common triage status and action interface.
- Add saved-view definitions for going-cold contacts and Congress facets.
