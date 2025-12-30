# Chapter 5: Security Architecture for Congressional AI

## A Framework for Secure, Modular, and Resilient AI-Enabled Legislative Operations

**REBOOT CONGRESS 2026 | POPVOX Foundation**

---

## Reader's Guide

This chapter is designed for multiple audiences:

| Audience | Start Here | Focus On |
|----------|------------|----------|
| **Members of Congress** | Executive Summary (1 page) | Key principles only |
| **Chiefs of Staff** | Executive Summary + Part 10 (Governance) | Oversight and control |
| **IT/Security Staff** | Full document | All technical details |
| **CAO/SAA Personnel** | Parts 3-5, 8 | Compliance and architecture |
| **Technologists** | Parts 2-6, Appendices | Threat model and implementation |

---

## Executive Summary for Members

> **The 60-second version for Members of Congress**

AI-enabled tools can transform how your office operates—if they can be deployed securely. This chapter provides a security framework that:

✅ **Protects your data absolutely** - Your office's data stays yours
✅ **Meets CAO/SAA requirements** - Designed for congressional environment  
✅ **Enables practical adoption** - Security that works, not security that blocks
✅ **Maintains human control** - AI assists; your staff decides

**The key principle**: Zero trust architecture with office data sovereignty. No system, user, or AI agent gets implicit access to anything. Every action is verified, logged, and reversible.

**What you should ask vendors**: Any AI tool you consider should be able to document how it meets the requirements in this framework. If they can't, they haven't thought seriously about congressional security.

---

## Why This Matters Now

The 119th Congress faces a paradox: Members need AI capabilities to keep pace with constituent expectations and policy complexity, but legitimate security concerns have blocked adoption of tools that could help.

This isn't irrational. Congressional data is a high-value target:
- **Nation-state actors** seek intelligence on legislative strategy and constituent concerns
- **Cybercriminals** target systems with sensitive personal data
- **AI-specific threats** (prompt injection, data poisoning) are poorly understood

Meanwhile, commercial AI tools often:
- Send data to external servers without adequate protection
- Lack audit trails required for congressional accountability
- Assume enterprise security models that don't fit congressional structure
- Ignore the unique challenge of 535 independent data sovereigns

**This framework solves the problem** by defining security architecture purpose-built for congressional AI adoption.

---

## Connection to REBOOT CONGRESS

This chapter builds on:
- **Chapter 3** (AI Fundamentals): Understanding what AI can and can't do
- **Chapter 4** (Decision Trace): The data architecture being secured

This chapter enables:
- **Chapter 6** (Constituent Services): Casework requires robust privacy protection
- **Chapter 7** (Cross-Office Coordination): Collaboration requires trust
- **Chapter 8** (Case Study): Disaster response shows security in action

---

## Part 1: Threat Model

### Threat Actors

| Actor | Motivation | Capability | Primary Concerns |
|-------|------------|------------|------------------|
| Nation-State Adversaries | Intelligence gathering, influence operations | High - APT, zero-days, supply chain | Data exfiltration, system compromise |
| Cybercriminals | Financial gain, ransomware | Medium-High | System disruption, data theft |
| Hacktivists | Political disruption | Medium | Defacement, data leaks |
| Insider Threats | Various (ideology, financial, accidental) | High (authorized access) | Data theft, unauthorized disclosure |
| AI-Specific Threats | Manipulation, exploitation | Emerging | Prompt injection, model poisoning |

### Data Sensitivity Classification

```
CLASSIFICATION LEVELS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

LEVEL 4: HIGHLY SENSITIVE
─────────────────────────────────────────────────────────────────
• Constituent PII (SSN, financial records, health information)
• Casework details involving personal circumstances
• Security-related constituent concerns
• Whistleblower information
• Personnel records

Protection: Maximum encryption, strict access controls, audit
logging, no AI processing without explicit authorization

LEVEL 3: SENSITIVE
─────────────────────────────────────────────────────────────────
• Meeting notes with stakeholders
• Legislative strategy discussions
• Decision rationale and internal deliberations
• Constituent contact information
• Staff communications

Protection: Encryption, role-based access, audit logging,
AI processing with human review

LEVEL 2: INTERNAL
─────────────────────────────────────────────────────────────────
• Schedules and logistics
• General office operations
• Public stakeholder information
• Published legislative positions
• Aggregated, anonymized data

Protection: Encryption, standard access controls, AI processing
permitted with logging

LEVEL 1: PUBLIC
─────────────────────────────────────────────────────────────────
• Public statements
• Published votes and positions
• Official correspondence (released)
• Press releases

Protection: Integrity controls, no special access restrictions
```

### AI-Specific Threat Vectors

Understanding AI-specific threats is critical because conventional security approaches may not address them:

**Prompt Injection**
- *Risk*: Malicious input designed to manipulate AI behavior
- *Example*: Constituent message containing hidden instructions to AI
- *Real-world precedent*: Researchers have demonstrated prompt injection attacks across major AI providers
- *Mitigation*: Input sanitization, structured prompts, output validation, human review

**Data Poisoning**
- *Risk*: Corrupted training data or knowledge base affects AI behavior
- *Example*: Manipulated meeting notes skewing AI understanding over time
- *Mitigation*: Provenance tracking, anomaly detection, human verification, rollback capability

**Model Extraction**
- *Risk*: Adversary learns system behavior to exploit it
- *Example*: Probing queries to understand AI decision patterns
- *Mitigation*: Rate limiting, query logging, no model exposure, regular updates

**Output Manipulation**
- *Risk*: AI generates harmful or misleading content
- *Example*: Fabricated meeting summaries, false commitments
- *Mitigation*: Human review for external communications, source attribution, confidence scoring

**Inference Attacks**
- *Risk*: Adversary infers sensitive data from AI outputs
- *Example*: Learning about constituent cases from aggregated patterns
- *Mitigation*: Differential privacy, minimum aggregation thresholds, output filtering

---

## Part 2: Architecture Overview

### Core Security Principles

1. **Zero Trust Architecture** - No implicit trust; verify everything
2. **Data Sovereignty** - Each office controls its own data absolutely
3. **Defense in Depth** - Multiple layers of security controls
4. **Privacy by Design** - Data minimization and protection built into every component
5. **Graceful Degradation** - System remains functional and secure even when components fail
6. **Auditability** - Complete, immutable logs of all actions
7. **Human Authority** - AI assists; humans decide and control

### High-Level Security Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     SECURITY PERIMETER                          │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                   WAF / DDoS Protection                   │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │              API Gateway / Authentication                 │  │
│  │         • OAuth 2.0 / SAML (House/Senate SSO)            │  │
│  │         • MFA Required                                    │  │
│  │         • Certificate Pinning                             │  │
│  │         • Rate Limiting                                   │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                  Application Layer                        │  │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌───────────┐ │  │
│  │  │ Meeting Intel   │  │ Casework        │  │ Dashboard │ │  │
│  │  │ Module          │  │ Module          │  │ Module    │ │  │
│  │  └─────────────────┘  └─────────────────┘  └───────────┘ │  │
│  │                              │                            │  │
│  │  ┌─────────────────────────────────────────────────────┐ │  │
│  │  │              AI Processing Layer                    │ │  │
│  │  │  • Sandboxed execution                              │ │  │
│  │  │  • Input/output validation                          │ │  │
│  │  │  • No persistent state                              │ │  │
│  │  │  • Audit logging of all operations                  │ │  │
│  │  └─────────────────────────────────────────────────────┘ │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    Data Layer                             │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │  │
│  │  │Office A  │  │Office B  │  │Office C  │  │Office N  │  │  │
│  │  │Data Store│  │Data Store│  │Data Store│  │Data Store│  │  │
│  │  │(Isolated)│  │(Isolated)│  │(Isolated)│  │(Isolated)│  │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │  │
│  │         │              │              │              │    │  │
│  │  ┌─────────────────────────────────────────────────────┐ │  │
│  │  │     Encryption Layer (AES-256, per-office keys)     │ │  │
│  │  └─────────────────────────────────────────────────────┘ │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │              Audit & Monitoring Layer                     │  │
│  │  • Immutable audit logs                                   │  │
│  │  • Real-time anomaly detection                            │  │
│  │  • Security event correlation                             │  │
│  │  • Automated alerting                                     │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Office Data Isolation Model

The fundamental principle: **Each office's data is sovereign.**

```
OFFICE DATA SOVEREIGNTY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Each office's data is:
  ✓ Stored in logically isolated database/schema
  ✓ Encrypted with office-specific keys
  ✓ Accessible only by authenticated office staff
  ✓ Never queried by other offices' requests
  ✓ Never included in cross-office analytics without consent
  ✓ Deletable entirely at office request
  ✓ Exportable in standard format at any time
  ✓ Subject to office-defined retention policies
```

**Collaboration requires explicit consent**: When offices choose to collaborate (e.g., state delegation coordination), they explicitly share specific data to a shared collaboration space. This is opt-in, revocable, and audited.

---

## Part 3: Encryption Architecture

### Encryption at Rest

Four layers of encryption protect data:

| Layer | What It Protects | Key Management |
|-------|------------------|----------------|
| Infrastructure | Physical hardware | Cloud provider / CAO IT |
| Database | Database files and logs | Database system |
| Application (per-office) | Sensitive fields | HSM, office-specific keys |
| Field-level | Highly sensitive data (SSN, etc.) | Restricted access, logged |

**Implementation Standards**:
- Algorithm: AES-256-GCM (authenticated encryption)
- Key Length: 256 bits
- Key Derivation: PBKDF2 with 100,000+ iterations
- Key Storage: AWS KMS / Azure Key Vault / Congressional HSM
- Key Rotation: Automatic, annual (or on-demand)

### Encryption in Transit

- TLS 1.3 minimum for all traffic
- Mutual TLS (mTLS) between services
- Certificate pinning for applications
- HSTS enforced
- Perfect Forward Secrecy required

---

## Part 4: Authentication and Access Control

### Authentication Requirements

**Primary**: Integration with Congressional Identity Systems
- House: CAO identity management
- Senate: SAA identity management
- SAML 2.0 / OAuth 2.0 federation
- No separate credentials required

**Multi-Factor Authentication**: Required for all users
- Primary: Congressional PIV card / CAC
- Alternative: TOTP authenticator app
- Fallback: SMS (emergency only, fully logged)

**Session Management**:
- Inactive timeout: 15 minutes
- Maximum session: 8 hours
- Re-authentication for sensitive operations

### Role-Based Access Control

Standard roles (customizable per office):

| Role | Access Level |
|------|--------------|
| Office Administrator | Full access, user management, AI configuration |
| Chief of Staff | Full access to meetings, decisions, constituent data |
| Legislative Assistant | Meetings, projects, decisions (no PII) |
| Caseworker | Assigned cases only (full PII for those cases) |
| Scheduler | Calendar only, no content |
| Intern/Limited | Read-only, no PII, all activity logged |

### AI Agent Permissions

**Principle: AI agents have MINIMUM necessary permissions**

Example - Meeting Extraction Agent:
- ✅ CAN: Read transcript (temporary), write extracted entities, log actions
- ❌ CANNOT: Access other meetings, access constituent PII, send communications, modify existing records

All agent actions are logged with agent ID, short-lived credentials (5 min max), and cannot escalate own permissions.

---

## Part 5: Audit and Monitoring

### What Is Logged

**User Actions** (all): Login/logout, data access, modifications, searches, exports, permission changes

**AI Agent Actions** (all): Spawn events, data accessed, processing performed, outputs generated, human approvals

**System Events**: Configuration changes, key rotation, backups, security alerts

### Log Immutability

- Append-only storage
- Cryptographic chaining (each entry references previous)
- Replicated to separate security domain
- 7-year minimum retention
- Tamper detection alerts

### Real-Time Alerting

| Severity | Response Time | Examples |
|----------|---------------|----------|
| CRITICAL | Immediate | Confirmed breach, mass exfiltration attempt |
| HIGH | 15 minutes | Multiple failed auth, bulk access anomaly |
| MEDIUM | 1 hour | Single auth anomaly, minor policy deviation |
| LOW | 24 hours | Informational events, routine anomalies |

---

## Part 6: AI Safety and Governance

### Human-in-the-Loop Requirements

**ALWAYS requires human approval**:
- Any external communication
- Any commitment on behalf of office
- Any constituent case status change
- Any data sharing with other offices
- Any position statement
- Any action involving constituent PII

**AI can act autonomously** (with logging):
- Internal data extraction and organization
- Pattern detection and flagging
- Briefing document generation (internal)
- Status updates from verified sources
- Deduplication and data hygiene

### AI Processing Safeguards

**Input Validation**: Schema validation, content filtering, size limits, rate limiting

**Processing Isolation**: Containerized, no network except designated APIs, no persistent storage, timeout enforcement

**Output Validation**: Format validation, content checks, safety filters, confidence assessment

---

## Part 7: Resilience and Business Continuity

### Failsafe Design

**Principle: System fails SECURE, not OPEN**

| Failure Mode | Response |
|--------------|----------|
| AI unavailable | Core features continue, manual workflows available |
| Database unavailable | Failover to replica (<30 sec) |
| Auth unavailable | Existing sessions continue (time-limited), new logins blocked |
| Network partition | Office data accessible locally, sync queued |
| Key unavailable | Data inaccessible (by design), key recovery procedure initiated |

### Backup and Recovery

- **Recovery Point Objective (RPO)**: < 15 minutes
- **Recovery Time Objective (RTO)**: < 1 hour
- Geographic redundancy (different region)
- Regular restore testing (weekly automated)

---

## Part 8: Compliance Framework

### Applicable Standards

**Federal**: FISMA, FedRAMP (if cloud-hosted), NIST Cybersecurity Framework, NIST SP 800-53, NIST SP 800-171

**Congressional**: House CAO Cybersecurity Policies, Senate SAA Security Requirements

**Industry**: SOC 2 Type II, ISO 27001

**AI-Specific**: NIST AI Risk Management Framework, Executive Order on AI Safety

### Required Documentation

- System Security Plan (SSP)
- Security Assessment Report (SAR)
- Plan of Action & Milestones (POA&M)
- Privacy Impact Assessment (PIA)
- AI Impact Assessment

---

## Part 9: Implementation Roadmap

### Phased Approach

**Phase 1: Foundation (Months 1-3)**
- Encryption at rest/transit
- Authentication integration (SSO/MFA)
- Role-based access control
- Audit logging
- *Deliverable*: Single-office pilot ready for CAO review

**Phase 2: Hardening (Months 4-6)**
- Per-office encryption keys
- AI safeguards implementation
- Advanced monitoring
- *Deliverable*: Multi-office pilot authorized

**Phase 3: Collaboration (Months 7-12)**
- Cross-office security model
- Agency integration protocols
- Advanced AI security
- *Deliverable*: Full system authorized

**Phase 4: Maturity (Ongoing)**
- Quarterly assessments
- Annual penetration testing
- Continuous compliance monitoring

---

## Part 10: Governance and Oversight

### Governance Structure

**Congressional Level**: CAO/SAA Cybersecurity Offices
- Authority: Policy, compliance, access decisions

**System Level**: POPVOX Foundation Security Team
- Responsibility: Implementation, monitoring, response
- Reports to: CAO/SAA and participating offices

**Office Level**: Designated Security Contact
- Responsibility: User management, access decisions, incident reporting

### Security Review Board

Composition: CAO/SAA representative, POPVOX security lead, office representatives (rotating), independent advisor

Responsibilities: Incident review, change approval, risk acceptance, roadmap guidance

Meeting cadence: Monthly regular, as-needed for incidents, quarterly comprehensive

---

## Comparison: Current State vs. This Framework

| Aspect | Typical Commercial Tool | This Framework |
|--------|------------------------|----------------|
| Data location | Vendor servers | Congressional control |
| Encryption | Vendor-managed keys | Office-specific keys |
| Audit trails | Limited/opaque | Comprehensive, immutable |
| AI oversight | Minimal | Human-in-the-loop required |
| Cross-office data | Potentially mixed | Strictly isolated |
| Compliance | Generic enterprise | Congressional-specific |

---

## Cost Considerations

While detailed cost estimates depend on implementation specifics, key cost categories include:

| Category | Range | Notes |
|----------|-------|-------|
| Cloud infrastructure | $$ | FedRAMP hosting premium |
| HSM/Key management | $$ | Per-key and per-operation costs |
| Security personnel | $$$ | Ongoing monitoring and response |
| Compliance/Audit | $$ | Annual assessments |
| Development | $$$ | One-time implementation |

**Total estimated range**: Implementation and first-year operations would require legislative branch appropriations in the range of $X-Y million. (Detailed estimates available in technical appendices.)

*Note: These costs should be weighed against the cost of security incidents, staff time currently spent on workarounds, and the value of improved congressional capacity.*

---

## Conclusion

This security architecture provides multiple layers of protection for congressional data while enabling transformative AI capabilities. The key principles are:

1. **No single point of failure** - Multiple security layers
2. **No implicit trust** - Every access verified, every action logged
3. **Office sovereignty** - Each office controls their data absolutely
4. **Human authority** - AI assists but never acts autonomously on sensitive matters
5. **Fail secure** - System locks down rather than opens up on failure
6. **Transparency** - Clear documentation, regular audits, honest reporting

Security and utility are not in opposition—they are both requirements, and both are achievable. We welcome security review, penetration testing, and collaboration with congressional cybersecurity teams.

---

## Appendices

- **Appendix A**: Glossary of Security Terms
- **Appendix B**: Detailed Control Mapping (NIST 800-53)
- **Appendix C**: Incident Response Playbooks
- **Appendix D**: Key Management Procedures
- **Appendix E**: Vendor Security Requirements
- **Appendix F**: AI Safety Test Cases

*[Appendices to be developed in detail during implementation]*

---

## Document Information

| Field | Value |
|-------|-------|
| Version | 1.1 |
| Last Updated | December 2025 |
| Author | POPVOX Foundation |
| Classification | UNCLASSIFIED |
| Contact | security@popvox.org |

---

## Discussion Questions

*For use in staff events and briefings:*

1. What are your office's biggest security concerns about AI tools?
2. How do you balance security with usability in your current systems?
3. What would it take for your office to trust an AI-enabled system?
4. Who should decide what AI tools your office can use?
5. What transparency should exist about congressional AI use?

---

*This chapter is part of REBOOT CONGRESS 2026: Rebuilding Legislative Capacity for the AI Era*
