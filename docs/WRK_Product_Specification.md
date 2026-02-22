# WRK — Institutional Operating System

**Unified Product Description & Specification**

POPVOX Foundation | February 2026 | *Internal Working Document*

---

## 1. Vision & Purpose

WRK is an agent-powered institutional operating system designed to serve as a living organizational brain for POPVOX Foundation. It structures, interconnects, and activates the full breadth of institutional knowledge — documents, relationships, strategic insights, and organizational memory — making it accessible to both human team members and AI agents operating as persistent collaborators.

The platform addresses a fundamental challenge: POPVOX Foundation's most valuable asset is not any single report or policy analysis, but the accumulated institutional knowledge across years of work spanning congressional modernization, international parliamentary development, legislative technology governance, and civic innovation. WRK transforms this dispersed knowledge into a structured, compounding resource that grows more valuable with every project, every interaction, and every insight.

### 1.1 Design Philosophy

**Architecture is governance.** Every technical design decision in WRK embeds an accountability mechanism. The permission structure is the governance structure. The file system is the knowledge architecture. The agent framework is the delegation model. WRK is built on the principle that how a system is designed determines how an institution operates — and that trustworthy AI adoption requires governance built in from day one, not added as an afterthought.

### 1.2 Core Proposition

***Less Salesforce, more Jarvis.*** WRK is not a passive system of record that users interrogate. It is an active system of intelligence that collaborates with users — surfacing what matters, suggesting what's next, drafting what's needed, and learning from every interaction. The platform separates Strategy (what's changing, what needs attention) from Resources (what's established, what's documented), giving users clear mental models for navigating institutional complexity.

---

## 2. Knowledge Architecture

### 2.1 Organizational Knowledge Graph

Every document, meeting note, report draft, grant deliverable, congressional contact, and parliamentary jurisdiction feeds into a structured, interlinked knowledge base. This is not a file system in the traditional sense — it is an entity-relationship graph where documents, people, organizations, projects, and policy topics are connected with meaningful associations.

For example: the Jamaican Parliament connects to ParlLink, which connects to the IPU presentation, which connects to the AI Futures Toolkit, which connects to recommendations about legislative scrutiny capacity. When a team member or agent asks "what have we said about X," the system knows — across years of work.

### 2.2 Project-Based File System (Box Integration)

The foundational infrastructure is a project-based file system integrated with Box. Every project maintains a consistent folder structure: contracts and agreements, meeting notes and call summaries, deliverables and drafts, research and reference materials, communications and outreach, and the relationship map of involved parties. This consistency is what makes agent ingestion reliable and grounding verifiable. When an agent references a prior discussion, that reference traces back to an authoritative source document.

### 2.3 Compounding Intelligence

Every piece of work — human or agent-generated — feeds back into the knowledge base. The grants agent learns from every successful proposal. The policy agent gets sharper with every analysis. The international agent builds richer comparative frameworks with every new jurisdiction. WRK doesn't just help accomplish today's work faster; it makes next month's work better because institutional memory deepens continuously.

---

## 3. Agent Framework

### 3.1 Specialist Agents

WRK deploys persistent, role-based agents that operate across the organization with deep domain expertise. Each agent has a defined persona, domain, and capability set that makes its role immediately legible to team members.

| Agent | Domain | Key Capabilities |
|---|---|---|
| *The Sentinel* | Policy & Research | Monitors legislative developments, drafts policy memos, flags relevant news, prepares briefings grounded in POPVOX's positions and prior work |
| *The Weaver* | Relationships & Outreach | Maps contact networks, suggests outreach, drafts communications, tracks engagement patterns, identifies cross-project relationship opportunities |
| *The Scribe* | Grants & Reporting | Tracks deliverable schedules, drafts reports from logged project activities, cross-references against proposal commitments, manages funder relationships |
| *The Navigator* | International Parliaments | Tracks developments across jurisdictions, maintains comparative databases, synthesizes cross-jurisdictional patterns, supports ParlLink deployments |
| *The Herald* | Communications | Drafts newsletters, social media, and public content consistent with organizational voice, manages content calendar, tracks audience engagement |

### 3.2 Project-Scoped Agents

Any authorized team member can spin up a dedicated agent for a specific project. The agent ingests all project materials from the Box file system and operates as a full teammate for the duration of the initiative.

#### Onboarding Process

Spinning up a project agent is designed to feel like onboarding a new colleague. The agent is pointed at a project workspace and ingests all available materials: contracts, meeting notes, codebase, prior related work, and the relationship map. It then introduces itself to the project team with an initial status assessment and open questions, building trust and catching gaps from the start.

#### Ongoing Operation

Once running, a project-scoped agent operates as a persistent collaborator. It tracks decisions and action items across meetings, flags when new developments contradict or build on earlier decisions, maintains a running project memory more reliable than any individual's recollection, monitors technical artifacts like codebases for changes that have implications for stakeholders, proactively researches relevant developments and comparable work in other jurisdictions, and suggests outreach grounded in actual relationship context.

#### Template Reusability

Successful project agent configurations become reusable templates. When a new international engagement begins, the project agent starts from a template that already understands how these engagements typically work, what common milestones look like, and what research is usually relevant. Each new project agent starts smarter than the last.

#### Interface

The project agent has a visible presence on the project home within WRK — a panel showing its current understanding of project status, recent observations, and pending suggestions. This is not a chatbot window but a teammate who has been quietly paying attention and maintains a running list of "things I noticed" and "things I think we should discuss." Team members interact through quick questions, collaborative drafting sessions, or strategic prompts as needed.

---

## 4. Permissions & Governance

### 4.1 Management Controls

A dedicated Permissions tab in WRK's management interface allows designated administrators to control which team members can spin up agents, which projects are eligible for agent assignment, what autonomy level each agent type receives by default, and who can modify permission tiers. The system starts with a high approval threshold by design — agents suggest, humans approve — with the explicit expectation that autonomy expands as trust is established through demonstrated performance.

### 4.2 Tiered Approval Structure

Agent actions are classified into three tiers based on risk level and organizational impact. These tiers are configurable per agent type and per project.

| Tier | Actions | Approval Required |
|---|---|---|
| **Autonomous** | Research pulls, status summaries, meeting note organization, internal working docs, news flagging | None |
| **Team Approval** | Draft partner communications, task suggestions, timeline changes, deliverable first drafts, outreach suggestions | Any authorized team member |
| **Management Sign-off** | Public content (blogs, social, newsletters), new contact outreach, grant deliverables, budget/resource actions | Designated manager |

### 4.3 Approval Interface

The approval experience is designed to feel collaborative rather than bureaucratic. Agents surface suggestions inline with brief reasoning — typically one or two sentences of context — and team members can approve, modify, or dismiss with minimal friction. The interface presents the suggestion within the relevant project or communications context so reviewers never need to context-switch to make a decision.

Over time, approval and rejection patterns inform autonomy calibration. If a research agent's suggestions are approved 95% of the time, that's a signal to expand its autonomous range. If outreach suggestions are modified 60% of the time, that capability stays in approval mode. This creates an empirical, data-driven path from restrictive defaults to earned autonomy.

### 4.4 Audit & Traceability

Every agent action is traceable as a first-class element of the interface. When reviewing an agent's output, team members can expand any piece of work and see the reasoning chain, the sources consulted, the alternatives considered and rejected, and the approval history. This is accessible in context rather than buried in logs, supporting both trust-building and debugging when things go sideways.

---

## 5. Communications & Outreach Module

The Communications tab in WRK unifies newsletter operations (one-to-many via Substack) and direct advocacy outreach (one-to-one and segmented via Gmail) into a single intelligence-driven system. Every communication generates data, every interaction informs strategy, and agents continuously synthesize patterns to make each subsequent communication smarter.

### 5.1 Contact Intelligence Layer

The foundation is a relationship graph, not a static address book. Every congressional staffer, parliamentary contact, funder, and partner organization carries structured context: role and affiliation, topic affinities, engagement history across channels (email, events, newsletter), project associations, relationship health assessment, and interaction timeline. The system bootstraps this context by pulling existing communication history from Gmail into the WRK knowledge graph, providing years of relationship data from day one rather than a cold start.

### 5.2 Shared Visibility

When email flows into WRK's project-based structure, the system surfaces cross-team communication patterns. If one team member has been emailing a WFD contact about the PLS Bot while another is in touch about ParlLink, WRK makes that visible — enabling coordination that would otherwise depend on hallway conversations or lucky coincidence.

### 5.3 Dynamic Segmentation

Beyond static email lists, WRK supports dynamic segments defined by criteria rather than fixed names. Examples include all House personal office chiefs of staff, congressional staffers who have engaged with AI governance content in the last 90 days, contacts at parliaments currently piloting ParlLink, or people who attended events but haven't received follow-up. Agents can suggest segments proactively, such as identifying 47 staffers who engaged with the last congressional modernization content plus 12 new contacts from recent Hill meetings ahead of a REBOOT Congress report release.

### 5.4 Outreach Suggestion Engine

This is where project-scoped agents and the communications system converge. A project agent notices a milestone and suggests updating relevant stakeholders with tailored messaging per audience. At the organizational level, a communications agent watches for outreach opportunities across all projects: relevant news articles as natural re-engagement hooks, congressional hearings that connect to POPVOX's work, and new reports from peer organizations worth responding to or amplifying. Every suggestion includes draft language, recommended recipients, and reasoning.

### 5.5 The Magic Editor

The bridge between agent intelligence and human action. Rather than a full email client, the Magic Editor is a collaboration space where the agent's draft, the relevant context (contact history, project status, prior communications), and the human's editorial judgment come together in one view. The human polishes, approves, and the message flows out through Gmail. Recipients see a normal email from a real person — no platform branding, no "sent via" footer — which is critical for congressional outreach where trust and personal relationships matter.

### 5.6 Newsletter & Mass Email Intelligence

For Substack newsletters and mass congressional emails, WRK tracks engagement and surfaces patterns over time. This includes content affinity mapping (which topics drive engagement with which segments), timing optimization (when do congressional staffers actually read emails), content recycling intelligence (identifying when older content is newly relevant to current debates), and cross-channel synthesis (connecting Substack engagement with direct email engagement with event attendance to identify the most engaged contacts across all channels).

### 5.7 Workflow Automation

Routine outreach follows automated patterns, all flowing through the established approval architecture. New contacts added to a project trigger welcome/context emails queued for review. Grant deliverable submissions trigger funder acknowledgment drafts. Completed events trigger follow-up sequences. Published reports trigger distribution to relevant segments with tailored framing per audience. Dormant relationships surface re-engagement suggestions. The agent drafts and suggests; the human reviews and approves; management signs off on high-stakes communications.

### 5.8 Insights Dashboard

Over time, the system builds a strategic picture of POPVOX Foundation's communications ecosystem: influence mapping (most engaged contacts, amplifiers, network gaps), topic momentum (which issues are gaining traction, which are fading), relationship health (which key relationships are being nurtured, which are going cold), campaign effectiveness (not just opens but downstream outcomes like meetings, citations, and policy impact), and a congressional landscape view showing engagement patterns by chamber, committee, party, and staff level. The agent synthesizes periodic briefings highlighting key observations, opportunities to act on, and relationships that need attention.

### 5.9 Technical Foundation

The communications module launches on Google Workspace Gmail integration — zero adoption friction since the team already lives in Gmail. The architecture is designed to be sender-agnostic: all intelligence, contacts, lists, approvals, and agent suggestions live in WRK, with the sending backend as a swappable layer. Gmail handles initial volumes (up to 2,000 sends/day on Workspace accounts), with a path to dedicated sending infrastructure (SendGrid or similar) when volume demands it. Substack subscriber data and engagement metrics flow into WRK via API for cross-channel intelligence.

---

## 6. Smart Entity Profiles

When an agent suggests connecting with a contact, clicking that person's name should open a profile that is as intelligence-rich as the rest of the platform. Smart Entity Profiles synthesize data from across the knowledge graph to present a complete, contextualized view of every person, organization, or institution in WRK's network.

A congressional staffer's profile shows not just contact information but recent project interactions, full communication history from Gmail, newsletter engagement patterns, event attendance, policy areas of interest derived from all of those signals, the agent's assessment of relationship health and engagement trajectory, and suggested next steps. This ensures that the "deep dive" experience matches the intelligence of WRK's high-level dashboards and feeds.

---

## 7. Interface Architecture

### 7.1 Strategy vs. Resources

WRK's primary navigation separates Strategy (/insights) — what's changing, what needs attention, what agents are surfacing — from Resources (/knowledge) — what's established, what's documented, what's in the institutional record. This gives users a clear mental model: go to Strategy to see what's active and evolving; go to Resources to see what's settled and authoritative.

### 7.2 Consistent Action Pattern

Across the entire platform, the interaction pattern between agents and humans follows a single, reusable flow: the agent surfaces an insight with brief reasoning, the human reviews it in context, and the response is approve, modify, or dismiss. Whether the suggestion is an outreach opportunity, a project task, a research flag, or a grant reporting reminder, the pattern is identical. This consistency builds muscle memory and ensures that every new agent capability automatically has a polished action layer.

### 7.3 Agent Personas

Agent personas (The Sentinel, The Weaver, The Scribe, The Navigator, The Herald) add a layer of legibility that makes the AI feel like a team member rather than an algorithm. Each persona's name communicates its role instantly, and team members naturally develop differentiated trust levels with different personas based on experience — which is exactly the behavior the permission system is designed to support.

---

## 8. Productization Path

Everything built for internal use becomes a proof of concept for a productized legislative version. POPVOX Foundation is not just building its own operating system — it is living the demonstration of what institutional AI adoption looks like for democratic bodies.

### 8.1 The Dual Purpose

Every time WRK is shown to a parliamentary delegation, a congressional office, or an international partner, POPVOX is showing them their future. The platform answers the question every legislature is asking: "How do we adopt AI in a way that's trustworthy, accountable, and actually useful?" The answer is a working system, not a white paper.

### 8.2 Governance as Differentiator

The permission architecture, tiered approval structure, and audit trail system are not just internal controls — they are the most valuable components of the productized offering. Congressional offices and parliaments will have exactly the same questions about agent permissions, review requirements, and accountability. POPVOX can hand them a battle-tested framework with real usage data behind it.

### 8.3 Architecture for Adaptation

The productized version is built on a common core with nationally adaptable components — the same design philosophy driving the ParlLink platform for international parliamentary AI infrastructure. The knowledge graph structure, agent framework, permission architecture, and communications intelligence layer are universal; the specific content, workflows, and integrations adapt to each legislative context.

---

## 9. Development Roadmap

| Phase | Focus | Key Deliverables |
|---|---|---|
| **Phase 1** | Foundation | Project-based Box file system, Gmail integration, basic contact management, list creation, consistent folder structures per project |
| **Phase 2** | Agent Framework | Spin-up interface for project-scoped agents, agent ingestion of project materials, permission controls, inline approval workflow, specialist agent deployment |
| **Phase 3** | Communications Intelligence | Outreach suggestion engine, engagement tracking, Substack data integration, dynamic contact segments, Magic Editor for collaborative drafting |
| **Phase 4** | Strategic Intelligence | Cross-project knowledge graph, insights dashboard, relationship health scoring, automated workflows, compounding organizational memory |
| **Phase 5** | Productization | Legislative office version derived from internal architecture, governance model documentation, pilot deployment framework |

Each phase delivers standalone value while building toward the full vision. The progression is designed so that foundational decisions made in Phase 1 (file system structure, contact data model, knowledge graph schema) directly enable the intelligence capabilities in later phases. The key architectural principle throughout: build for agents from the start, even when agents aren't deployed yet.

---

## 10. Strategic Context

WRK is being built at a moment when the pacing problem — how technological acceleration outpaces democratic institutional adaptation — has intensified dramatically. The emergence of autonomous coding agents, recursive self-improvement capabilities, and ubiquitous AI-generated systems in late 2025 and early 2026 has compressed the timeline for institutional response.

Organizations that adapt will go vertical in capability; those that do not will be left behind and acted upon. Congress cannot afford to stand still, and neither can parliaments around the world. WRK exists at the intersection of this urgency: it is simultaneously the tool POPVOX Foundation uses to scale its own capacity for this moment, and the proof of concept it offers to the institutions it serves.

The platform embodies POPVOX Foundation's core conviction: it is no longer sufficient to make the case for modernization, lobby for appropriations, and point institutions in the right direction. The institutions are not moving fast enough. *We have to build the examples, show what is possible, and work with jurisdictions willing and able to deploy and show the way.*
