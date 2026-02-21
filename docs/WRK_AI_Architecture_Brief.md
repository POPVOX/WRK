# Designing the AI-Enabled Institution
## A Strategic Architecture Brief for WRK v2 and a Roadmap for Nonprofits and Legislative Offices

**POPVOX Foundation**  
**February 2026**  
Prepared with Claude AI research assistance

## Executive Summary

The landscape of organizational AI has shifted decisively in the past six months. What was experimental in 2025 is becoming operational in 2026. The emergence of multi-agent orchestration frameworks, standardized protocols like Anthropic's Model Context Protocol (MCP), and mature human-in-the-loop governance patterns means that small, mission-driven teams can now deploy AI capabilities that were recently exclusive to large enterprises.

This memo synthesizes cutting-edge research, industry frameworks, and practitioner discourse to assess where WRK's v2 architecture stands relative to the state of the art, and to chart a path for scaling these capabilities responsibly. It is written with two audiences in mind: the POPVOX team building WRK today, and the broader community of nonprofits and legislative offices that may follow this model tomorrow.

## Core Thesis

The organizations that thrive will be those that focus less on the technology itself and more on the human systems that surround it. WRK's architecture should grow the autonomy of its AI agents gradually, in lockstep with the organization's trust, governance maturity, and knowledge foundation.

## 1. Where the Field Is Now

### 1.1 From Experimentation to Production

Gartner projects that 40% of enterprise applications will embed AI agents by end of 2026, up from less than 5% in 2025. The agentic AI market is expected to grow from $7.8 billion to over $52 billion by 2030. But Deloitte's 2025 survey found that while 30% of organizations are exploring agentic AI and 38% are piloting, only 11% are in production. The gap between experimentation and deployment is where the real design work happens.

### 1.2 The Multi-Agent Paradigm

The field is undergoing what analysts call its "microservices revolution." Single all-purpose agents are giving way to orchestrated teams of specialized agents: one diagnoses, another remediates, a third validates, and a fourth documents. Gartner reported a 1,445% surge in multi-agent system inquiries from Q1 2024 to Q2 2025. The key insight from McKinsey's "agentic organization" framework is that competitive advantage comes not from deploying individual AI tools but from redesigning the institution around humans and AI agents working together.

### 1.3 The Protocol Layer Is Maturing

Anthropic's MCP, IBM's ACP, and Google's A2A all launched in 2025. In December 2025, Anthropic donated MCP to the Linux Foundation's newly formed Agentic AI Foundation. MCP has become the de facto standard for connecting AI systems to real-world data and tools, with adoption by OpenAI, Google DeepMind, and Microsoft.

Complementing MCP, Anthropic's "Agent Skills" concept provides the procedural knowledge layer: organized folders of instructions and resources that agents discover and load dynamically. As one analyst put it: MCP provides the plumbing for tool access; Agent Skills provide the brain.

### 1.4 Knowledge Graphs as the Foundation

The emerging consensus is that the difference between a useful agent and a hallucinating one depends on the quality of its knowledge foundation. In 2026, the concept of GraphRAG (retrieval-augmented generation powered by a semantic knowledge backbone) is moving from research to practice.

The knowledge graph serves as a shared memory and coordination hub: a digital nerve center connecting specialized agents across domains. Nearly half of organizations cite searchability (48%) and reusability (47%) of data as top challenges for AI automation.

## 2. Design Principles for the AI-Enabled Institution

Drawing from McKinsey, the World Economic Forum, AWS, and practitioner reports, five principles emerge as essential for organizations building AI into their operational DNA.

### 2.1 Progressive Autonomy

Every framework surveyed (AWS's Security Scoping Matrix, the Knight First Amendment Institute's five-level autonomy model, and McKinsey's "governed autonomy" concept) converges on the same idea: start with tightly constrained agents and expand autonomy as trust, governance, and security capabilities mature.

For a small team, this means your first agents should be read-only assistants that surface information and draft recommendations. Only after establishing reliability and trust should they gain the ability to take actions, and even then, with explicit human approval gates.

**WRK alignment:** WRK's v2 schema already includes `agent_approvals` as a planned table, and the data contract requires every automated write to include actor, source system, and timestamp.

### 2.2 Provenance-First Knowledge

Every piece of AI-generated content must carry its lineage: what sources informed it, what model produced it, what confidence level it carries, and whether a human has reviewed it. This is becoming a regulatory and operational requirement.

More practically, provenance is what makes institutional knowledge trustworthy. When a meeting prep brief cites specific past meetings, documents, and contacts, staff can verify and build on it.

**WRK alignment:** The v2 data contract's provenance requirements (`prompt_version`, `model`, `source_refs[]`, `confidence`, `review_state`) are ahead of what most organizations have implemented.

### 2.3 Canonical Identity Everywhere

Stable, prefixed entity IDs (`usr_`, `org_`, `mtg_`, `prj_`) that work across APIs, prompts, logs, and automation payloads are essential for multi-agent coordination. Free-text names break down quickly in multi-agent systems.

### 2.4 ACL-Aware Retrieval

AI retrieval must enforce the same access controls as human access. When an agent answers a question, it must respect both WRK visibility policy and projected Box ACL. Without this, AI becomes an accidental information leak.

### 2.5 Event-Driven, Idempotent Architecture

Every integration event should include an `event_id`, actor, timestamps, and entity references, and should be append-only and idempotent. This pattern makes the system auditable, recoverable, and safer for concurrent agent operations.

## 3. WRK v2 Architecture Assessment

### 3.1 Strengths: What WRK Gets Right

#### Unified Data Contract

The `WRK_V2_DATA_CONTRACT` is mature for a small-team project. Canonical IDs, explicit source-of-truth designations (Postgres for operations, Box for files), and a clear permission contract are strong foundations.

#### Box Integration as Knowledge Backbone

The Box taxonomy, webhook-driven metadata sync, and permission projection layer are a strong implementation of single source of truth for files.

#### Agent Governance Scaffolding

Planning for `agent_runs`, `agent_steps`, and `agent_approvals` early is aligned with governance best practices.

#### Existing AI Features as Trust-Builders

Meeting prep, grant document analysis, and knowledge hub search are Level 1-2 autonomy features. They surface information and draft recommendations without autonomous external actions.

### 3.2 Opportunities: Where to Invest Next

#### Knowledge Graph / Entity Linking

The planned `entity_links` table is the right next step. A lightweight relational knowledge graph with typed links can materially improve retrieval quality over flat vector-only search.

#### Agent Skill Definitions

Define explicit skills for each agent role: what data it can access, what tools it can use, and what actions require approval.

#### Feedback Loops That Improve the System

Move from unstructured feedback to structured AI quality signals: missing context, confidence errors, and edit deltas.

#### Cross-System Event Bus

As WRK connects to Box, Google Calendar, Slack, and email, an event bus pattern (`integration_events`) will avoid tightly coupled integrations.

## 4. A Phased Roadmap for Scaling

### Phase 1: Knowledge Foundation (Current - Q2 2026)

Focus: Make the AI deeply knowledgeable before making it autonomous.

- Complete Box sync pipeline: full metadata mirror with webhook-driven updates and permission projection.
- Deploy `entity_links`: capture cross-domain relationships not covered by existing pivots.
- Build `knowledge_facts` extraction: generate structured facts with source refs from meetings, grants, and contact updates.
- Enrich meeting prep and knowledge hub outputs with richer cross-domain context.
- Instrument AI quality (used, edited, ignored).

**Autonomy level:** 1-2 (operator/collaborator).

### Phase 2: Governed Agents (Q3 - Q4 2026)

Focus: Introduce agents that can take actions with explicit approval workflows.

- Deploy `agent_runs` and `agent_approvals`.
- Define skill-level permission boundaries.
- Introduce draft-and-approve flows for follow-ups and updates.
- Capture and use human edits as training and prompt feedback.

**Autonomy level:** 2-3 (collaborator/consultant).

### Phase 3: Institutional Colleagues (2027+)

Focus: Agents operate as trusted team members within defined boundaries.

- Multi-agent coordination through shared knowledge graph and event bus.
- Progressive autonomy expansion for low-risk, high-confidence tasks.
- Full `audit_logs` coverage across systems.
- External workflow integrations (Slack, email, calendar) under policy controls.

**Autonomy level:** 3-4 (consultant/approver).

## 5. Implications for Legislative Offices

### 5.1 The Current State of Government AI

Federal AI adoption is accelerating, with increased agency AI use and stronger policy attention to risk management and governance.

### 5.2 Why This Model Translates

Legislative offices share WRK-like constraints: small teams, high accountability, relationship-heavy work, and strong need for institutional memory.

### 5.3 The Trust Imperative

Progressive autonomy is mandatory in legislative contexts. Unauthorized actions can create severe operational and political risk. WRK's governance scaffolding (approvals, provenance, ACL-aware retrieval, and auditability) is core to safe adoption.

## 6. Practical Recommendations

- Invest in knowledge quality before expanding autonomy.
- Design governance fields early (don't backfill later).
- Measure AI quality explicitly and continuously.
- Keep architecture portable across storage, model providers, and org structures.
- Document implementation decisions for reuse by mission-driven teams.

## Sources and Further Reading

### Industry Frameworks and Analysis

- McKinsey, *The Agentic Organization: Contours of the Next Paradigm for the AI Era* (2025)
- McKinsey, *Seizing the Agentic AI Advantage* (2025)
- Deloitte Insights, *Agentic AI Strategy* - Tech Trends 2026
- MIT Sloan Management Review, *The Emerging Agentic Enterprise* (2025)
- World Economic Forum, *Using AI Agents in Organizations Today and in the Future* (December 2025)
- IBM, *The Trends That Will Shape AI and Tech in 2026*
- Cloudera, *2026 Predictions: Architecture, Governance, and AI Trends*

### Governance and Security

- AWS, *The Agentic AI Security Scoping Matrix* (2025)
- Palo Alto Networks, *A Complete Guide to Agentic AI Governance* (2025)
- Knight First Amendment Institute, *Levels of Autonomy for AI Agents*
- Google Cloud, *Lessons from 2025 on Agents and Trust*
- Permit.io, *Human-in-the-Loop for AI Agents: Best Practices*
- Parseur, *Future of Human-in-the-Loop AI (2026)*

### Technical Architecture

- Anthropic, *Equipping Agents for the Real World with Agent Skills* (2025)
- Anthropic, *Code Execution with MCP* (2025)
- Pento, *A Year of MCP: From Internal Experiment to Industry Standard* (2025)
- The New Stack, *Agent Skills: Anthropic's Next Bid to Define AI Standards*
- AgileSoftLabs, *How to Build Enterprise AI Agents in 2026*
- Machine Learning Mastery, *7 Agentic AI Trends to Watch in 2026*

### Government and Public Sector

- U.S. GAO, *Artificial Intelligence: Generative AI Use and Management at Federal Agencies* (GAO-25-107653)
- Center for Democracy and Technology, *State Legislatures' Focus on Public Sector AI Use* (2025)
- Future of Privacy Forum, *The State of State AI: Legislative Approaches in 2025*
- SAS, *Government AI Predictions for 2026*
- Federal News Network, *Why AI Agents Won't Replace Government Workers Anytime Soon* (December 2025)
- AI Center for Government, Partnership for Public Service
