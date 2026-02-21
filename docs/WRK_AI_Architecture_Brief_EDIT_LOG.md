# WRK AI Architecture Brief - Edit Log

**Source file reviewed:** `/tmp/wrk_ai_architecture_brief_raw.txt`  
**Derived files:**
- `docs/WRK_AI_Architecture_Brief.md` (clean conversion)
- `docs/WRK_AI_Architecture_Brief_FINAL.md` (tightened operating brief)
- `docs/WRK_AI_Architecture_Brief_BOARD_ONE_PAGER.md` (board-ready summary)

## Line-Referenced Change Summary

| Source line(s) | Original content (summary) | Final revision | Why |
|---|---|---|---|
| 2-8 | Title page block | Converted to Markdown title/subtitle + metadata block | Preserve original meaning while making repo-friendly Markdown |
| 10-14 | Executive summary + core thesis | Preserved; lightly tightened wording | Improve readability without changing thesis |
| 16-24 | Market/trend claims | Preserved and grouped into sectioned Markdown headers | Better scanability and citation maintenance |
| 18 | Market projections and adoption percentages | Kept claims but added source-validation note in final brief section 12 | Avoid treating unverified stats as final publication truth |
| 21-22 | MCP/ACP/A2A maturity narrative | Preserved with cleaner paragraph breaks | Improve readability and reduce run-on density |
| 23-24 | GraphRAG/knowledge graph emphasis | Preserved and elevated as architecture principle + roadmap driver | Align strategy with implementation sequencing |
| 26-27 | Five design principles intro | Preserved | Maintain conceptual framing |
| 29-33 | Progressive autonomy + WRK alignment | Preserved and converted into explicit policy requirement | Make it operational, not only descriptive |
| 35-39 | Provenance-first knowledge + WRK alignment | Preserved and expressed as mandatory output fields | Ensure implementation-level clarity |
| 41-42 | Canonical IDs (`usr_`, `org_`, `mtg_`, `prj_`) | Expanded to include `ctr_` in final brief | Align with current taxonomy decision on contracts |
| 43-44 | ACL-aware retrieval requirement | Preserved and translated into explicit retrieval control requirement | Prevent ambiguity around security policy |
| 45-46 | Event-driven idempotency | Preserved and mapped to system event-flow baseline | Tie principle to concrete architecture behavior |
| 51-59 | Strengths assessment | Preserved with condensed formatting | Keep assessment while reducing verbosity |
| 54-55 | Box taxonomy mentions numbered folders | Updated in final brief to WRK-first domains (`Projects`, `Funders`, `Contracts`, etc.) | Match latest team direction |
| 61-69 | Opportunities section | Preserved and converted into implementation priorities | Move from advisory language to execution language |
| 63 | `entity_links` as next step | Elevated to near-term must-have in phases and six-week plan | This is foundational for retrieval quality |
| 64-65 | Agent skill definitions | Preserved and operationalized in Phase 2 deliverables | Make "skills" actionable with permission boundaries |
| 66-67 | Feedback loops | Expanded to measurable metrics in section 7 | Turn qualitative recommendation into gating mechanism |
| 68-69 | Event bus recommendation | Preserved and reflected in target system shape | Improve cross-system integration coherence |
| 74-100 | Three-phase roadmap + autonomy levels | Preserved, tightened, and given phase exit gates | Add objective thresholds for autonomy expansion |
| 76-80 | Phase 1 bullets | Mapped to "knowledge-first" deliverables in sections 8 and 9 | Align roadmap with immediate sprint planning |
| 84-91 | Phase 2 bullets | Mapped to explicit tables (`agent_runs`, `agent_steps`, `agent_approvals`) | Clarify schema and governance dependencies |
| 93-100 | Phase 3 bullets | Kept with stronger dependency on reliability metrics | Prevent premature autonomy expansion |
| 103-112 | Legislative office implications | Preserved and tightened | Keep portability case while reducing repetition |
| 114-126 | Practical recommendations | Preserved and distributed across sections 6-11 | Integrate recommendations into concrete operating model |
| 117-122 | "Make AI quality measurable" | Converted into metric catalog + phase gates | Enable go/no-go decisions based on evidence |
| 123-124 | Portability recommendation | Converted into explicit portability requirements section | Make architecture substitution constraints explicit |
| 128-164 | Source list | Preserved, normalized, and indexed as `S1-S12` | Support cleaner references and future source validation |

## New Material Added in Final Draft (Not in Original)

- "Current vs Planned in WRK v2" implementation status table.
- Contracts as first-class domain with `ctr_*` policy and relationship rules.
- Security baseline with data classes and preconditions for action-taking agents.
- Retention/deletion baseline expectations.
- Quality metrics and explicit phase exit gates.
- Cost and operations assumptions (load, cost drivers, budget controls).
- Six-week execution alignment section.

## Notes

- No external facts were newly introduced as authoritative claims beyond the original source set.
- The final draft is positioned as an operating brief for internal execution; figures should be source-validated before public publication.
