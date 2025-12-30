# WRK Knowledge Hub Specification

## Overview

The Knowledge Hub is the organizational intelligence layer of WRK—a unified interface for accessing, querying, and synthesizing everything the organization knows. It transforms scattered information across meetings, projects, documents, and relationships into queryable institutional memory.

**Core principle:** Capture once, access everywhere. Information entered anywhere in WRK flows into the Knowledge Hub and becomes searchable, connectable, and actionable.

---

## Scope

### What's IN the Knowledge Hub (All Team Members)

| Source | Content Indexed |
|--------|-----------------|
| **Meetings** | Notes, attendees, topics discussed, outcomes, AI summaries |
| **Projects** | Descriptions, documents, decisions, notes, milestones, status |
| **People** | Professional contacts, titles, bios, interaction history |
| **Organizations** | Partners, stakeholders, agencies, congressional offices, descriptions |
| **Issues/Topics** | Cross-cutting themes linking meetings, projects, orgs |
| **Actions/Commitments** | What we've promised, what's promised to us, status |
| **Team Resources** | SOPs, handbooks, style guides (respecting audience settings) |
| **Project Publications** | Drafts, final versions, related materials |
| **Project Events** | Event details, participants, outcomes |

### What's OUT of the Knowledge Hub (Access-Controlled Elsewhere)

| Source | Where It Lives | Access |
|--------|----------------|--------|
| Grants & funding amounts | Funders module | Management only |
| Reporting requirements | Funders module | Management only |
| Funder priorities/preferences | Funders module | Management only |
| Grant-project allocations | Funders module | Management only |
| Financial information | Funders module | Management only |

**Note:** Organizations marked as funders still appear in the Knowledge Hub as organizations (with contacts, meeting history). Only the financial/grant layer is excluded.

---

## User Stories

### As an Executive
- "What do we know about [topic]?" → Get synthesized answer from meetings, documents, projects
- "What have we committed to [organization]?" → See all commitments across all interactions
- "Prepare me for my meeting with [person/org]" → Get full context briefing (without grant details)
- "What's our history with [organization]?" → See relationship timeline, all touchpoints
- "What did we decide about [issue] and why?" → Find decision with rationale

### As a Project Lead
- "What have we learned about [topic] across all projects?" → Cross-project synthesis
- "Who have we talked to about [issue]?" → Find all relevant contacts
- "What documents do we have on [subject]?" → Unified document search
- "What are all the open commitments on my projects?" → Aggregated action items

### As a New Team Member
- "How does [process] work here?" → Find relevant SOPs, past decisions
- "Who should I talk to about [topic]?" → Find people with relevant expertise/history
- "What's the background on [project]?" → Get synthesized project history
- "What's our position on [issue]?" → Find relevant decisions, documents, talking points

---

## Interface Design

### Navigation

```
WRK Navigation:
[Dashboard] [Meetings] [Projects] [Contacts] [Team] [Knowledge Hub] [Funders*]

* Funders tab visible only to management
```

### Knowledge Hub Main View

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ KNOWLEDGE HUB                                                    [Settings] │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 🔍 Ask anything... (or search across all content)                   │   │
│  │                                                          [Search]   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  Quick Actions:                                                             │
│  [📋 Prep for Meeting] [❓ What do we know about...] [✓ Find Commitments]  │
│  [🔗 Relationship History] [📌 Recent Decisions] [📂 Browse All]           │
│                                                                             │
├────────────────────────────────┬────────────────────────────────────────────┤
│                                │                                            │
│  FILTERS                       │  RESULTS                                   │
│                                │                                            │
│  Sources:                      │  ┌────────────────────────────────────┐   │
│  ☑ Meetings (234)              │  │                                    │   │
│  ☑ Documents (156)             │  │  [Results or AI answer appears     │   │
│  ☑ Decisions (45)              │  │   here based on query type]        │   │
│  ☑ Projects (23)               │  │                                    │   │
│  ☑ People (89)                 │  │                                    │   │
│  ☑ Organizations (67)          │  │                                    │   │
│  ☑ Commitments (34)            │  │                                    │   │
│  ☑ Team Resources (12)         │  │                                    │   │
│                                │  └────────────────────────────────────┘   │
│  Date Range:                   │                                            │
│  [All time ▼]                  │  Recent Activity:                          │
│                                │  • Meeting note added: Xcential call       │
│  Project:                      │  • Decision recorded: REBOOT timeline      │
│  [All projects ▼]              │  • Document updated: LRC white paper       │
│                                │  • New contact: Senate Finance staff       │
│  Issue/Topic:                  │                                            │
│  [All topics ▼]                │                                            │
│                                │                                            │
└────────────────────────────────┴────────────────────────────────────────────┘
```

---

## Query Modes

### Mode 1: Natural Language Query (AI-Powered)

User types a question in plain English. AI searches across all indexed content and synthesizes an answer with citations.

**Example queries:**
- "What do we know about OLRC modernization?"
- "Who have we talked to about AI in Congress?"
- "What's our position on positive law codification?"
- "Summarize our work with ParlAmericas"
- "What did we learn from the IPU conference?"

**Response format:**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Q: What do we know about OLRC modernization?                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ OLRC (Office of Law Revision Counsel) modernization has been discussed      │
│ across several projects and meetings:                                       │
│                                                                             │
│ **Summary:**                                                                │
│ • Current codification pace would take 80+ years to complete all titles     │
│ • Technical tools exist (Xcential's Comparative Prints Suite) but aren't    │
│   being applied to the codification backlog                                 │
│ • Leadership transition expected—younger staff interested in modern tools   │
│ • Connects to ParlLink work on international legislative data standards     │
│ • Strong relationship with Ari Hershowitz at Xcential                       │
│                                                                             │
│ **Key Documents:**                                                          │
│ 📄 The Hidden Foundation of American Law (white paper draft)                │
│ 📄 REBOOT CONGRESS outline - data infrastructure chapter                    │
│                                                                             │
│ **Relevant Meetings:**                                                      │
│ 💬 Call with Ari Hershowitz - Nov 15, 2025                                  │
│ 💬 Data Foundation discussion - Dec 10, 2025                                │
│                                                                             │
│ **Related Projects:**                                                       │
│ 📁 REBOOT CONGRESS (Chapter 1: Legislative Data Crisis)                     │
│ 📁 ParlLink                                                                 │
│                                                                             │
│ **Open Questions:**                                                         │
│ • Whether POPVOX should take on technical tool development                  │
│ • Timeline for OLRC leadership transition                                   │
│                                                                             │
│ ──────────────────────────────────────────────────────────────────────────  │
│ Sources: 7 items  [View all sources]  [Save answer]  [Share]                │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Mode 2: Keyword Search (Traditional Full-Text)

Standard search returning ranked results by relevance.

**Results display:**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Search: "positive law codification"                            42 results   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ 📄 DOCUMENT  •  High relevance                                              │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ The Hidden Foundation of American Law                                   │ │
│ │ "...completing positive law codification within a decade would          │ │
│ │ transform American statutory law into the most accessible..."          │ │
│ │                                                                         │ │
│ │ Project: REBOOT CONGRESS  •  Type: White Paper  •  Dec 2025            │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ 💬 MEETING  •  High relevance                                               │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Call with Data Foundation                                               │ │
│ │ "...discussed status of positive law titles and implications for        │ │
│ │ AI legal tools that treat the Code as authoritative..."                │ │
│ │                                                                         │ │
│ │ Dec 15, 2025  •  Data Foundation  •  Issues: Legislative Data          │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ 📋 PROJECT NOTE  •  Medium relevance                                        │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ REBOOT CONGRESS chapter outline                                         │ │
│ │ "...Chapter 1 should cover the positive law gap and its implications   │ │
│ │ for AI-powered legal research tools..."                                │ │
│ │                                                                         │ │
│ │ Project: REBOOT CONGRESS  •  Dec 20, 2025                              │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ [Load more results]                                                         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Mode 3: Structured Queries (Quick Actions)

Pre-built templates for common needs:

---

#### Quick Action: "Prep for Meeting"

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ PREP FOR MEETING                                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Select meeting:                                                            │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Upcoming meetings...                                          [▼]   │   │
│  │                                                                     │   │
│  │   Tomorrow: Hewlett Foundation check-in (Sarah Chen)               │   │
│  │   Jan 8: Senate Finance Committee staff (re: AI policy)            │   │
│  │   Jan 10: ParlAmericas planning call                               │   │
│  │   Jan 12: Xcential / Ari Hershowitz                                │   │
│  │                                                                     │   │
│  │ Or search: [org or person name]                                    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Generated prep document:**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ MEETING PREP                                                [Export] [Edit] │
│ Hewlett Foundation check-in                                                 │
│ Tomorrow, Jan 5, 2026 at 2:00 PM                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ ATTENDEES                                                                   │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ 👤 Sarah Chen                                                           │ │
│ │    Program Officer, Democracy & Governance                              │ │
│ │    12 previous meetings  •  Last: Oct 15, 2025                         │ │
│ │    [View full profile]                                                  │ │
│ │                                                                         │ │
│ │ 👤 Michael Torres (new contact)                                         │ │
│ │    Senior Program Associate                                             │ │
│ │    No previous interactions                                             │ │
│ │    [Add profile notes]                                                  │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ RELATIONSHIP HISTORY                                                        │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ • 12 meetings since January 2023                                        │ │
│ │ • Primary contacts: Sarah Chen, former contact David Park              │ │
│ │ • Connected projects: ParlLink, REBOOT CONGRESS, Digital Parliaments   │ │
│ │                                                                         │ │
│ │ Relationship timeline:                                                  │ │
│ │ ──●────────●────────●────────●────────●────────●──────────●────→       │ │
│ │  2023     Apr      Aug      Dec     2024     Jun      Oct   Now        │ │
│ │  First    Site     Progress Report  Renewal  Mid-yr   Last             │ │
│ │  mtg      visit    update   filed            check-in mtg              │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ LAST MEETING                                                                │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Oct 15, 2025 - Quarterly check-in                                       │ │
│ │                                                                         │ │
│ │ Topics discussed:                                                       │ │
│ │ • ParlLink expansion to Caribbean - they were enthusiastic              │ │
│ │ • REBOOT CONGRESS concept - interested in congressional modernization   │ │
│ │ • Potential for 2026 renewal                                            │ │
│ │                                                                         │ │
│ │ [View full meeting notes]                                               │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ OPEN COMMITMENTS                                                            │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ ⚠️ We committed to:                                                     │ │
│ │    • Share REBOOT CONGRESS outline when ready (Oct 15)                  │ │
│ │      Status: Not yet fulfilled                                          │ │
│ │    • Invite to Q1 staff event (Sep 20)                                  │ │
│ │      Status: Not yet fulfilled                                          │ │
│ │                                                                         │ │
│ │ ✓ They committed to:                                                    │ │
│ │    • Connect us with their intl democracy portfolio (Oct 15)           │ │
│ │      Status: Completed - intro made Nov 2                               │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ SUGGESTED TALKING POINTS                                                    │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Based on history and open items:                                        │ │
│ │                                                                         │ │
│ │ 1. REBOOT CONGRESS progress - January launch planned, can share         │ │
│ │    outline now (addresses open commitment)                              │ │
│ │                                                                         │ │
│ │ 2. ParlLink pilot results - Caribbean work progressing, initial        │ │
│ │    findings from Jamaica and Saint Lucia                                │ │
│ │                                                                         │ │
│ │ 3. Q1 staff event invitation (addresses open commitment)                │ │
│ │    Date: March 15, 2026 - should we invite them?                        │ │
│ │                                                                         │ │
│ │ 4. 2026 planning - may want to discuss renewal timeline                 │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ RELATED CONTEXT                                                             │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Recent activity on projects they're connected to:                       │ │
│ │ • ParlLink: Jamaica pilot completed, Saint Lucia in progress           │ │
│ │ • REBOOT CONGRESS: Chapter outline done, Rep Bodies v2 near final      │ │
│ │                                                                         │ │
│ │ [View project details]                                                  │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ [Add to meeting notes]  [Export PDF]  [Share with team]                     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Note:** Meeting prep for funders shows relationship/meeting history but does NOT include:
- Grant amounts
- Reporting deadlines
- Financial details
- Funder-specific priorities from the Funders module

Management can access that information separately in the Funders tab.

---

#### Quick Action: "Relationship History"

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ RELATIONSHIP HISTORY                                                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Search: [Organization or person name                              🔍]     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Generated view:**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ RELATIONSHIP HISTORY                                                        │
│ ParlAmericas                                                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│ OVERVIEW                                                                    │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Type: International Partner                                             │ │
│ │ First interaction: March 2023                                           │ │
│ │ Total meetings: 18                                                      │ │
│ │ Active projects together: 2                                             │ │
│ │ Key contacts: Maria Santos, James Wilson                                │ │
│ │ Primary relationship owner: Aubrey                                      │ │
│ │                                                                         │ │
│ │ [View organization profile]                                             │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ CONTACTS                                                                    │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ 👤 Maria Santos - Director of Programs                                  │ │
│ │    12 meetings  •  Last: Dec 10, 2025                                   │ │
│ │                                                                         │ │
│ │ 👤 James Wilson - Technology Advisor                                    │ │
│ │    8 meetings  •  Last: Nov 5, 2025                                     │ │
│ │                                                                         │ │
│ │ 👤 Ana Reyes - Parliamentary Liaison                                    │ │
│ │    3 meetings  •  Last: Sep 15, 2025                                    │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ TIMELINE                                                                    │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │                                                                         │ │
│ │ Dec 2025  💬 Planning call for 2026 initiatives                         │ │
│ │              Topics: ParlLink expansion, joint events                   │ │
│ │                                                                         │ │
│ │ Nov 2025  💬 Digital Parliaments working session                        │ │
│ │              Topics: Caribbean pilot design                             │ │
│ │           📄 Signed MOU for ParlLink partnership                        │ │
│ │                                                                         │ │
│ │ Oct 2025  💬 IPU Conference coordination                                │ │
│ │              Topics: Kuala Lumpur presentation prep                     │ │
│ │                                                                         │ │
│ │ Sep 2025  💬 Quarterly check-in                                         │ │
│ │              Topics: Project updates, new opportunities                 │ │
│ │                                                                         │ │
│ │ [Show earlier...]                                                       │ │
│ │                                                                         │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ CONNECTED PROJECTS                                                          │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ 📁 ParlLink / Digital Parliaments Project                               │ │
│ │    Role: Implementation partner                                         │ │
│ │    Status: Active                                                       │ │
│ │                                                                         │ │
│ │ 📁 Representative Bodies v2                                             │ │
│ │    Role: Contributing case studies                                      │ │
│ │    Status: Active                                                       │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ COMMITMENTS                                                                 │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Open commitments from us: 2                                             │ │
│ │ Open commitments from them: 1                                           │ │
│ │ [View all commitments]                                                  │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ KEY TOPICS DISCUSSED                                                        │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Parliamentary Technology (14 mentions)                                  │ │
│ │ Caribbean Legislatures (10 mentions)                                    │ │
│ │ Legislative Data (8 mentions)                                           │ │
│ │ AI Policy (5 mentions)                                                  │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

#### Quick Action: "Find Commitments"

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ FIND COMMITMENTS                                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Filters:                                                                   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Status: [Open ▼]  Direction: [All ▼]  Project: [All ▼]             │   │
│  │                                                                     │   │
│  │ Org/Person: [_______________]   Date range: [All time ▼]           │   │
│  │                                                                     │   │
│  │ Assigned to: [All team ▼]                                          │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  Results: 12 open commitments                                               │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ ⚠️ OVERDUE                                                          │   │
│  │                                                                     │   │
│  │ □ Share REBOOT CONGRESS outline with Hewlett                       │   │
│  │   Committed: Oct 15, 2025  •  To: Hewlett Foundation               │   │
│  │   Project: REBOOT CONGRESS  •  Owner: Marci                        │   │
│  │   [View meeting] [Mark complete] [Snooze]                          │   │
│  │                                                                     │   │
│  │ □ Send follow-up resources to Senate Finance staff                 │   │
│  │   Committed: Dec 1, 2025  •  To: Senate Finance Committee          │   │
│  │   Project: AI Policy  •  Owner: Aubrey                             │   │
│  │   [View meeting] [Mark complete] [Snooze]                          │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📅 DUE THIS WEEK                                                    │   │
│  │                                                                     │   │
│  │ □ Review ParlAmericas MOU draft                                    │   │
│  │   Committed: Dec 20, 2025  •  To: ParlAmericas                     │   │
│  │   Due: Jan 7, 2026  •  Owner: Marci                                │   │
│  │   [View meeting] [Mark complete] [Snooze]                          │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📅 UPCOMING                                                         │   │
│  │                                                                     │   │
│  │ □ Draft joint statement with WFD                                   │   │
│  │   Committed: Dec 15, 2025  •  To: Westminster Foundation           │   │
│  │   Due: Jan 15, 2026  •  Owner: Beatriz                             │   │
│  │   [View meeting] [Mark complete] [Snooze]                          │   │
│  │                                                                     │   │
│  │ [Show 8 more...]                                                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 🔄 WAITING ON OTHERS                                                │   │
│  │                                                                     │   │
│  │ □ IPU to share conference attendee list                            │   │
│  │   Committed: Nov 20, 2025  •  From: IPU                            │   │
│  │   [View meeting] [Send reminder] [Mark received]                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

#### Quick Action: "Recent Decisions"

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ RECENT DECISIONS                                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Filters:                                                                   │
│  [All projects ▼]  [Last 30 days ▼]  [All decision types ▼]                │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📌 Dec 28, 2025 - REBOOT CONGRESS                                   │   │
│  │                                                                     │   │
│  │ Decision: Confirmed January 2026 launch timeline                   │   │
│  │                                                                     │   │
│  │ Context: Discussed feasibility of completing Rep Bodies v2 and     │   │
│  │ first chapters by January. Agreed timeline is tight but doable.    │   │
│  │                                                                     │   │
│  │ Rationale: January launch aligns with 119th Congress start,        │   │
│  │ maximizes relevance. Delay would miss window.                      │   │
│  │                                                                     │   │
│  │ Made by: Marci  •  [View related discussion]                       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📌 Dec 26, 2025 - WRK Tool                                          │   │
│  │                                                                     │   │
│  │ Decision: Exclude constituent/casework functionality from scope    │   │
│  │                                                                     │   │
│  │ Context: Discussed whether tool should include CRM-style           │   │
│  │ constituent management or casework tracking.                       │   │
│  │                                                                     │   │
│  │ Rationale: CRM is established category with approval complexity.   │   │
│  │ Tool should be "intelligence layer" for professional relationships │   │
│  │ not volume contact management. Can revisit casework later.         │   │
│  │                                                                     │   │
│  │ Made by: Marci  •  [View related discussion]                       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📌 Dec 20, 2025 - ParlLink                                          │   │
│  │                                                                     │   │
│  │ Decision: Prioritize Jamaica and Saint Lucia for initial pilot     │   │
│  │                                                                     │   │
│  │ Context: Multiple Caribbean parliaments expressed interest.        │   │
│  │ Needed to select focus countries for limited resources.            │   │
│  │                                                                     │   │
│  │ Rationale: Jamaica has strongest technical capacity; Saint Lucia   │   │
│  │ has enthusiastic champion. Together they represent different       │   │
│  │ scales and can inform broader rollout.                             │   │
│  │                                                                     │   │
│  │ Made by: Aubrey  •  [View related discussion]                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  [Load more decisions]                                                      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Browse Mode

For exploration without a specific query:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ KNOWLEDGE HUB > Browse                                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  View by:                                                                   │
│  [Topics/Issues]  [Organizations]  [Projects]  [People]  [Timeline]        │
│       ↑ active                                                              │
│                                                                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  TOPICS                                                          [+ Add]   │
│                                                                             │
│  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐      │
│  │ Congressional     │  │ Legislative Data  │  │ AI Policy         │      │
│  │ Modernization     │  │                   │  │                   │      │
│  │ ━━━━━━━━━━━━━━━━━ │  │ ━━━━━━━━━━━━━━━━━ │  │ ━━━━━━━━━━━━━━━━━ │      │
│  │ 45 meetings       │  │ 23 meetings       │  │ 34 meetings       │      │
│  │ 12 documents      │  │ 8 documents       │  │ 15 documents      │      │
│  │ 3 projects        │  │ 2 projects        │  │ 2 projects        │      │
│  │ 15 organizations  │  │ 8 organizations   │  │ 12 organizations  │      │
│  │                   │  │                   │  │                   │      │
│  │ Recent: Dec 28    │  │ Recent: Dec 26    │  │ Recent: Dec 20    │      │
│  └───────────────────┘  └───────────────────┘  └───────────────────┘      │
│                                                                             │
│  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐      │
│  │ International     │  │ Constituent       │  │ Parliamentary     │      │
│  │ Parliaments       │  │ Engagement        │  │ Technology        │      │
│  │ ━━━━━━━━━━━━━━━━━ │  │ ━━━━━━━━━━━━━━━━━ │  │ ━━━━━━━━━━━━━━━━━ │      │
│  │ 28 meetings       │  │ 19 meetings       │  │ 31 meetings       │      │
│  │ 6 documents       │  │ 5 documents       │  │ 9 documents       │      │
│  │ 2 projects        │  │ 1 project         │  │ 3 projects        │      │
│  │ 22 organizations  │  │ 10 organizations  │  │ 14 organizations  │      │
│  │                   │  │                   │  │                   │      │
│  │ Recent: Dec 22    │  │ Recent: Dec 15    │  │ Recent: Dec 24    │      │
│  └───────────────────┘  └───────────────────┘  └───────────────────┘      │
│                                                                             │
│  [View all 18 topics]                                                       │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Clicking a topic** expands to show:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ TOPIC: Congressional Modernization                              [← Back]    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  45 meetings  •  12 documents  •  3 projects  •  15 organizations          │
│                                                                             │
│  ┌─────────────────────────────────┐  ┌─────────────────────────────────┐  │
│  │ RECENT MEETINGS                 │  │ KEY ORGANIZATIONS               │  │
│  │                                 │  │                                 │  │
│  │ Dec 28 - Internal discussion    │  │ House Modernization Subcmte     │  │
│  │ Dec 17 - Aubrey testimony       │  │   8 meetings                    │  │
│  │ Dec 10 - Data Foundation        │  │                                 │  │
│  │ Nov 15 - Xcential call          │  │ Congressional Research Service  │  │
│  │ Nov 8 - House Admin staff       │  │   5 meetings                    │  │
│  │                                 │  │                                 │  │
│  │ [View all 45]                   │  │ CAO Office                      │  │
│  │                                 │  │   4 meetings                    │  │
│  └─────────────────────────────────┘  │                                 │  │
│                                       │ [View all 15]                   │  │
│  ┌─────────────────────────────────┐  └─────────────────────────────────┘  │
│  │ KEY DOCUMENTS                   │                                       │
│  │                                 │  ┌─────────────────────────────────┐  │
│  │ 📄 Beyond the CRM (article)     │  │ CONNECTED PROJECTS              │  │
│  │ 📄 REBOOT CONGRESS outline      │  │                                 │  │
│  │ 📄 Aubrey testimony Dec 2025    │  │ 📁 REBOOT CONGRESS              │  │
│  │ 📄 LRC White Paper (draft)      │  │ 📁 WRK Tool                     │  │
│  │                                 │  │ 📁 Future of Constituent Engage │  │
│  │ [View all 12]                   │  │                                 │  │
│  └─────────────────────────────────┘  └─────────────────────────────────┘  │
│                                                                             │
│  ACTIVITY TIMELINE                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │     Jan    Feb    Mar    Apr    May    Jun    Jul    Aug    Sep    │   │
│  │      ▂      ▃      ▂      ▄      ▅      ▃      ▂      ▄      ▆     │   │
│  │                                                                     │   │
│  │     Oct    Nov    Dec                                               │   │
│  │      ▇      ▅      █                                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Data Architecture

### Knowledge Base Index

All searchable content flows into a unified index:

```
KB_ENTRY
├── id
├── source_type          // meeting, document, decision, note, person, org, etc.
├── source_id            // ID in source table
├── title                // Display title
├── content              // Full searchable text
├── summary              // AI-generated summary (optional)
├── metadata             // JSON: dates, relationships, tags, etc.
├── project_id           // If associated with a project
├── organization_id      // If associated with an org
├── person_id            // If associated with a person
├── indexed_at
└── updated_at
```

### Indexing Triggers

| Event | Action |
|-------|--------|
| Meeting saved | Index title, notes, AI summary; link to attendees, orgs, projects |
| Document uploaded/synced | Index title, content; link to project |
| Decision recorded | Index decision text, rationale; link to project |
| Note added | Index content; link to project |
| Person created/updated | Index name, title, bio, notes |
| Organization created/updated | Index name, description, notes |
| Action/Commitment created | Index description; link to meeting, project, assignee |
| Team resource saved | Index title, description, content |

### Excluded from Index

| Source | Reason |
|--------|--------|
| Grants | Management-only visibility |
| Reporting requirements | Management-only visibility |
| Funder priorities | Management-only visibility |
| Financial allocations | Management-only visibility |
| Team resources with `audience: management` | Respect access control |

---

## AI Integration

### Query Pipeline

```
┌──────────────────┐
│   User Query     │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Query Analysis   │  Determine: keyword search vs. natural language
└────────┬─────────┘  Identify: entities, topics, query type
         │
         ▼
┌──────────────────┐
│ Source Retrieval │  Full-text search across KB index
└────────┬─────────┘  Filter by user-selected sources/dates
         │
         ▼
┌──────────────────┐
│ Context Assembly │  Gather top N relevant snippets
└────────┬─────────┘  Include metadata (dates, relationships)
         │
         ▼
┌──────────────────┐
│ AI Synthesis     │  Generate answer with citations
└────────┬─────────┘  Identify gaps/uncertainties
         │
         ▼
┌──────────────────┐
│ Response Format  │  Structure for display
└──────────────────┘  Include source links
```

### Synthesis Prompt Template

```
You are helping query an organization's knowledge base.

USER QUESTION: {query}

RETRIEVED CONTEXT:
{snippets_with_metadata}

INSTRUCTIONS:
1. Synthesize an answer based ONLY on the provided context
2. Cite specific sources using [Source: title, date] format
3. If information is incomplete, say what's missing
4. If sources conflict, note the discrepancy
5. Suggest related queries if relevant

FORMAT:
- Lead with direct answer
- Provide supporting details with citations
- List all sources used
- Note any limitations or gaps
```

---

## Saved Queries

Users can save frequent queries for quick access:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ MY SAVED QUERIES                                              [+ New Query] │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  📌 Open commitments (all)                              [Run] [Edit] [×]   │
│     Last run: 2 hours ago  •  12 results                                    │
│                                                                             │
│  📌 Recent decisions on REBOOT CONGRESS                 [Run] [Edit] [×]   │
│     Last run: Yesterday  •  4 results                                       │
│                                                                             │
│  📌 What's new on AI policy                             [Run] [Edit] [×]   │
│     Last run: 3 days ago  •  8 results                                      │
│                                                                             │
│  📌 My open action items                                [Run] [Edit] [×]   │
│     Last run: Today  •  5 results                                           │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Access Control

### Visibility Rules

| Content Type | Default Visibility | Notes |
|--------------|-------------------|-------|
| Meetings | All team | All logged meetings are searchable |
| Documents | Per document setting | Respects `visibility` field |
| Decisions | All team | Organizational transparency |
| Notes | All team | Project notes are shared |
| People | All team | Contact information |
| Organizations | All team | Except funder-specific data |
| Commitments | All team | Accountability |
| Team Resources | Per resource setting | Respects `audience` field |
| Grants | Management only | **NOT in Knowledge Hub** |
| Reporting Req | Management only | **NOT in Knowledge Hub** |

### Query Filtering

```php
// When building KB queries, exclude management-only content for non-management users

public function search(string $query, User $user)
{
    $results = KbEntry::search($query);
    
    // Always exclude grant-related content from KB
    $results->whereNotIn('source_type', ['grant', 'reporting_requirement']);
    
    // Filter documents by visibility
    if (!$user->isManagement()) {
        $results->where(function ($q) use ($user) {
            $q->where('source_type', '!=', 'document')
              ->orWhereHas('document', fn($d) => $d->where('visibility', 'all'));
        });
    }
    
    // Filter team resources by audience
    $results->where(function ($q) use ($user) {
        $q->where('source_type', '!=', 'team_resource')
          ->orWhereHas('teamResource', fn($r) => $r->forAudience($user));
    });
    
    return $results->get();
}
```

---

## Implementation Phases

### Phase 1: Foundation (Week 1-2)
- [ ] KB index table and model
- [ ] Indexing service for existing content types
- [ ] Basic full-text search
- [ ] Search results UI
- [ ] Source filtering

### Phase 2: Quick Actions (Week 3-4)
- [ ] Meeting prep generator
- [ ] Relationship history view
- [ ] Commitments finder
- [ ] Recent decisions view

### Phase 3: AI Integration (Week 5-6)
- [ ] Query analysis
- [ ] Context assembly
- [ ] AI synthesis with citations
- [ ] Natural language query mode

### Phase 4: Browse & Polish (Week 7-8)
- [ ] Browse by topic view
- [ ] Browse by organization view
- [ ] Saved queries
- [ ] Activity timeline visualizations

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Query response time | < 3 seconds (search), < 10 seconds (AI synthesis) |
| Search result relevance | > 80% of top 5 results rated relevant by users |
| Meeting prep usage | Used for > 50% of external meetings |
| Team adoption | All team members use KB weekly within 30 days |
| Knowledge capture | > 90% of meetings have searchable notes within 48 hours |

---

## Open Questions

1. **Semantic search**: Add vector embeddings for better natural language matching? (Phase 2+)

2. **Real-time indexing vs. batch**: Index on save vs. periodic reindex?

3. **External data enrichment**: Pull in public info about orgs/people from LinkedIn, websites?

4. **Mobile interface**: Simplified KB access for on-the-go meeting prep?

5. **Sharing/export**: How should synthesized answers be shareable outside WRK?
