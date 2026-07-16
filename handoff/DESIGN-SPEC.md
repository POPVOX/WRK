# WRKBench.ai — "Morning Desk" Redesign Spec

Developer handoff for the WRKBench internal dashboard redesign (POPVOX Foundation, ~7 staff).
Mockups are in `mockups/` at 1360×880. This document defines the design system, the new
information architecture, and per-screen behavior.

**Design intent in one line:** the app should read like a calm daily briefing — editorial
typography, hairline rules instead of card chrome, one accent color, and the AI companion
demoted to a single quiet command bar.

---

## 1. Design tokens

### Color

| Token | Value | Use |
|---|---|---|
| `bg` | `#FAF7F1` | App background (warm paper) |
| `bg-rail` | `#F5F1E8` | Right rails / secondary panels |
| `bg-inset` | `#F3EEE3` | Sub-nav boxes, pipeline footer, chips |
| `surface` | `#FFFFFF` | Inputs, cards that must lift (search, campaign cards) |
| `ink` | `#26221C` | Primary text, active nav pill, heavy rules |
| `ink-2` | `#4A453B` | Secondary text, nav items |
| `ink-3` | `#5C574D` | Body/muted copy |
| `ink-4` | `#8A8578` | Metadata |
| `ink-faint` | `#A39C8C` | Section labels, placeholders, counts |
| `line` | `#E4DDD0` | Hairline dividers |
| `line-strong` | `#D8D0BF` | Input/button borders |
| `accent` | `#8A4B2D` | Terracotta — links, primary CTAs, active markers |
| `accent-deep` | `#6E3A21` | Accent hover |
| `positive` | `#3B7A45` | "on track", fresh last-touch, campaign live |
| `warning` | `#8A6D1F` | "on hold", "planning", low confidence |
| `danger` | `#B33A2B` | overdue, "past due", going cold, needs reply |
| `alert-bg` | `#F8EFE6` + border `#E0C9B8` | Attention strips (notes debt, open follow-ups) |

Rules:
- ONE accent (terracotta). Green/amber/red are semantic status colors only, always as
  **colored text**, never chip backgrounds.
- Dark blocks (coverage card, active nav) use `ink` as background with `#F7F3EC` text
  and `#C9BFA9` for muted-on-dark.
- Avatar backgrounds rotate through muted hues at equal saturation/lightness:
  `#3B5A7A`, `#8A4B2D`, `#5A7A3B`, `#7A3B5A`, `#3B7A6E`, `#6E5A2E`.

### Typography

| Role | Font | Sizes |
|---|---|---|
| Display / headings / row titles | **Newsreader** (serif), weight 500–600 | Page title 38px/500, greeting 44px, section titles 34px, row titles 17–22px/600 |
| UI / body | **Public Sans**, 400–700 | Body 13–15px, meta 11–12px |
| Labels / data | **IBM Plex Mono**, 400–500 | Section labels 11px, letter-spacing 0.12–0.14em, UPPERCASE; times, counts, IDs 12–13px |

Both are open-source Google Fonts. Pattern: every section starts with a mono
UPPERCASE label in `ink-faint` (or `accent` when it's the page's key stat line),
followed by a rule.

### Rules & spacing
- Primary section rule: `2px solid ink` (top border). Secondary: `1px solid line`.
  Row separators: `1px solid line`; sub-rows `#F0EADD`.
- Radius: 6px buttons/inputs, 8–10px cards/strips, 999px filter pills.
- Page padding: 48–56px. Row padding: 13–18px vertical. Grid gaps: 16px.
- Shadows: almost none. Only white `surface` cards get `0 2px 10px rgba(40,34,24,0.04)`.

### Buttons & controls
- **Primary CTA:** accent bg, `#F7F3EC` text, 600, 13px, 9px×18px padding, radius 6.
  One per page max (New project / Log meeting / Add contact / New campaign).
- **Dark CTA:** ink bg — reserved for AI actions ("✦ Prep brief", "✦ Relationship brief").
- **Secondary:** white bg, `line-strong` border.
- **Tertiary:** plain accent-colored 600 text ("Review →", "Analytics →").
- **Filter pills:** 999px; active = ink bg / cream text; inactive = white bg + border.
- **Segmented toggle (Mine/Team):** joined pair, active segment ink bg.
- **AI affordances** always use the ✦ glyph.

---

## 2. Information architecture

Old sidebar (22 items, 3 groups, "Notifications" twice) collapses to **6 destinations**:

1. **Today** — the briefing home
2. **Inbox** — email needing action + mentions + system notices (merges old Inbox,
   Notifications ×2, Needs You)
3. **Meetings** — agenda + notes-debt queue (absorbs old Meetings)
4. **Projects** — status-grouped list (absorbs Projects, Funding can live here or under Admin)
5. **People** — sub-nav: Contacts / Organizations / Congress directory (merges Contacts,
   Organizations, Congress, Team)
6. **Outreach** — sub-nav: Campaigns / Lists / Media clippings (merges Outreach, Media,
   Congress→Campaigns)
- **Travel** stays as a quiet 7th item (low frequency).
- Secondary links (Congress directory →, Media clippings →, Funding →) also appear as
  shortcuts under a divider.
- **Admin** (Staff, Box Access, Agent Policies, Integrations, Metrics, Feedback,
  Attention Pilot) moves entirely under the user block at the bottom — not in daily nav.

**Sidebar spec:** 216px; wordmark in Newsreader; items 14px Public Sans with mono count
badges right-aligned; active item = ink pill with cream text; sub-sections render as an
inset `bg-inset` box (see 2b/2c/4b). On detail pages the sidebar collapses to a 64px icon
rail (see 3b/4c/5a).

**AI companion:** one persistent ⌘K bar (bottom of Today, and available globally as a
command palette). Placeholder: "Log a meeting, prep a brief, capture a task…". No chat
card on the home screen. AI-generated content always appears as accent-colored italic
serif suggestions inline (see 1a prep cue) or behind ✦ buttons.

---

## 3. Screens

### 1a — Today (home) · `1a-home-morning-desk.png`
- Mono date/location line → serif greeting → one plain-language status sentence with an
  inline re-sync link (replaces the blue sync banner + three status buttons).
- Left column: **UP NEXT** (meeting w/ "Prep brief" CTA + italic AI cue) and **NEEDS YOU**
  (overdue task, notes debt) as ruled rows.
- Right column: dark **LATEST COVERAGE** card; **THIS WEEK** mini-stats (3 rows max).
- Bottom: the Ask WRK ⌘K bar.
- Cut entirely: Workspace Conversation card, brain-dump chips, Now/Next/Later empty
  states, Quick Access list (now nav), duplicate priority queue.

### 2a — Projects · `2a-projects.png`
- **Group by status** (ACTIVE / ON HOLD / PLANNING / COMPLETED) instead of a status chip
  column; completed+planning collapsed behind a "Show…" link.
- Columns: Name (serif) · Scope (plain text, not chips) · Lead · Timeline · Health.
- **Health** replaces the status dropdown: `on track` (green) / `past due` (red) /
  `on hold` (amber) as colored text. Computed from timeline + status.
- Sub-projects expand inline under the parent with a 2px left rule; count shown as
  "4 sub-projects ▾" in accent.
- Project IDs (P-096) removed from default view (keep in detail/URL).
- Header: search input, Mine/Team segmented toggle, one primary "＋ New project".

### 2b — Contacts · `2b-contacts.png`
- Table, not cards. Columns: Name+avatar+email · Organization · Owner · **Last touch** ·
  Meetings (mono, right-aligned).
- Last touch is the relationship signal: ≤1 week green, ≥3 months red.
- Saved views as pills: All / My contacts / **Going cold · 31** / Hill staff / Funders.
  "Going cold" = owned contacts with last touch > 90 days.
- Display names resolve to real names (title-case from email if needed); raw email is the
  secondary line. **No delete buttons in the list** — destructive actions live on the
  detail page rail.
- Sidebar shows the People sub-nav inset (Contacts / Organizations / Congress directory).

### 2c — Congress explorer · `2c-congress-explorer.png`
- Search reads back as a sentence: `title: "legislative director" · chamber: House ·
  status: current` with match count; active facets are removable ✕ chips; "+ committee ·
  office · state" adds facets.
- Primary CTA lives in the search block: **"Build list from 412 →"** — search→list is the
  core job.
- Results: Name + chamber (mono tag) · Role · Office · **Confidence** ("4 sources" green,
  "1 source" amber) replacing "source observations".
- Tabs (Directory / Staff changes / Lists / Campaigns) as understated text tabs with
  accent underline on active.
- **PIPELINE footer** shows Search → List → Campaign with live numbers, linking to 4b.

### 3a — Meetings agenda · `3a-meetings-agenda.png`
- One chronological agenda grouped by day (TODAY / TOMORROW / date headers); mono time
  column (88px), serif meeting titles, meta line with location/attendees/org.
- Filter pills: Upcoming / **Needs notes · 23** (red) / Past. Mine/Team toggle in header.
- **NOTES DEBT strip** (alert-bg): the 3 oldest unnoted meetings as mini-cards, each with
  "Add notes" and "✦ Draft from calendar" (AI pre-fills attendees/time/agenda from the
  calendar event). "Review all →" opens the full queue.
- Replaces the 4 view-toggles + month columns; a calendar view remains as a secondary link.

### 3b — Meeting detail · `3b-meeting-detail.png`
- **A document, not a form.** Reading column order: KEY ASK (serif pull-quote under 2px
  rule) → NOTES (with 🎤 Dictate) → FOLLOW-UPS (checkbox rows w/ due + owner, plus
  "＋ Add follow-up — or let WRK extract them from notes ✦").
- Empty sections collapse to one line of add-links: "＋ Agenda · ＋ Commitments made ·
  ＋ Team thread". Never render empty boxes.
- **Parse Zoom/Teams boilerplate**: title area shows "Join Zoom ↗" button + one meta line
  (time · platform · meeting ID · logged by). Raw invite text never shows in Notes.
- Right rail (300px, `bg-rail`): ATTENDEES (with per-person meeting count → contact page),
  ORGANIZATION (with meeting count, last, owner), LINKED PROJECT, HISTORY with this org.
  Status dropdown, Edit, Delete demoted to rail footer.
- Sidebar = 64px icon rail on detail pages.

### 4a — Inbox · `4a-inbox.png`
- One triage queue merging synced Gmail threads, @mentions, campaign replies, and system
  notices. Filter pills: All / Email / Mentions / System.
- Every row: avatar, sender + **CRM context line** ("NDI · contact you own", "Staff
  Assistant · Rep. Houlahan · via H3-outreach campaign"), serif subject, snippet, and
  **inline actions**: Reply in Gmail ↗ / ✦ Draft reply / Make task / Schedule meeting /
  Add to contacts / Done.
- Right-edge status tag as colored text: `needs reply` red, `campaign reply` green,
  `mention` amber, `system` gray.
- System notices render muted (gray avatar, gray text) and sort to the bottom.

### 4b — Campaigns · `4b-campaigns.png`
- Outreach front page. One card per campaign, state-first:
  - **SENDING** (green ●): white card, progress bar (accent fill on `#F0EADD` track),
    `540 / 1,596 sent` mono counter, failed/opened/replied stats, Pause + Analytics.
  - **DRAFT** (amber ◐): what it's blocked on in plain words; Edit / Preview / Schedule.
  - **COMPLETED** (gray ✓): compressed to one line with topline stats + Duplicate.
- Footer: sending identity, daily cap, delivery settings.

### 4c — Campaign analytics · `4c-campaign-analytics.png`
- Funnel of 4 stats separated by hairlines: SENT → OPENED (with % + team-avg comparison)
  → REPLIED (with sentiment split) → **MEETINGS BOOKED** (accent — the metric that matters).
- Daily sends/opens as paired bars (ink = sent, `#C9A183` = opened), mono day labels.
- MOST ENGAGED list: name · office · outcome ("replied → meeting booked").
- **SUGGESTED NEXT STEP** card (`bg-inset`): "31 people opened 3+ but haven't replied" →
  "Build follow-up list · 31 →" (closes the loop back to Lists/2c).

### 5a — Contact detail · `5a-contact-detail.png`
- Header answers "who + are we current?": avatar, serif name, title · org-link · location,
  then one meta line (Last touch green/red · meetings · owner · since).
- Actions: ✉ Email / ＋ Log meeting / **✦ Relationship brief** (dark, AI summary).
- **OPEN FOLLOW-UPS** pinned in an alert strip before anything else.
- **TIMELINE** interleaves meetings (◷), emails (✉), campaign sends (➤, muted) newest
  first: mono date column, bold one-line summary, meta line, right link (Notes → /
  Open in Inbox →).
- Right rail: DETAILS (email/phone/timezone incl. "2:14 PM now"), TAGS, LINKED PROJECT,
  ALSO AT ORG. Footer: Synced from Gmail · Edit · Merge · Delete.

---

## 4. Cross-cutting behavior

- **Status = colored text, never chip soup.** Chips are reserved for removable filters
  and tags.
- **Counts in mono everywhere** (nav badges, table columns, stats).
- **Empty states never render as empty boxes** — collapse to a single "＋ Add x" line.
- **Destructive actions** (delete, disconnect) never appear in lists; detail-page rail
  footers only.
- **The outreach loop** is the product's spine — make each hop a link:
  Congress search (2c) → Build list → Campaign (4b) → reply lands in Inbox (4a) →
  Schedule meeting (3b) → contact timeline (5a) → analytics suggests follow-up list (4c).
- Meeting/notes text should support the "needs notes" lifecycle: a meeting is `needs
  notes` until Notes is non-empty or marked "no notes needed".
- Accessibility: body text ≥13px; `ink-faint` only for labels ≥11px uppercase mono;
  interactive rows get full-row hover (`#F5F1E8`) and visible focus rings in `accent`.

## 5. Assets & source

- `mockups/*.png` — 1360×880 hi-fi mockups (1a, 2a–2c, 3a–3b, 4a–4c, 5a).
- Live source of truth: `WRKBench Redesign.dc.html` in this project — all styles are
  inline and copy-paste-able; exact hex/px values in this doc match it.
- Fonts: Google Fonts — Newsreader (400–600 + italic), Public Sans (400–700),
  IBM Plex Mono (400–500).
