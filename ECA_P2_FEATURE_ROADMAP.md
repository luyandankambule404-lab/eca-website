# ECA P2 — Feature Roadmap (Post Source-to-Feature Audit)

**Date:** 2026-10-08  
**Rule:** Source first. Feature second. No schema/auth/RBAC changes in this audit phase.

---

### P0 — Required for correctness/security

| Feature | Reason | Source | Department | Users | Dependency | Complexity | Source/docs |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Preserve membership / application / payment / certificate pipelines | Core association operations already live | Operational system + Digital registration mandate | Digital Systems + Membership ops | Public, members, staff | Existing DB | — | Maintain only |
| Preserve CPD learner/admin flows | Source mandates CPD points & training | PDF p.2–3 | Professionalization | Learners, CPD staff | Existing CPD DB | — | Maintain only |
| Preserve Wellness Hub + admin content | Source mandates wellness support | PDF p.1, p.3 | Wellness | Public, members, wellness admin | Existing wellness tables | — | Maintain only |
| Preserve contact + CPD support tickets | Existing support channels; do not replace with invented CRM | Operational | Cross-cutting | Public, members, staff | `contact_messages`, `support_tickets` | — | Maintain only |
| Keep regression suites green | Safety baseline | QA | All | Staff | Tools | — | — |
| Keep demo news labelled | Prevent false official announcements | Local seed policy | Content | Public | Presentation helpers | Low | Done in P1 |

---

### P1 — Required for core organizational service delivery (when source allows)

| Feature | Reason | Source | Department | Users | Dependency | Complexity | Source/docs |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Publish verified advocacy updates (CMS or static pack) | Mandate exists; public currently empty | PDF advocacy mandate | Advocacy | Public, stakeholders | Content workflow; optional news | Medium | Campaign briefs **blocking** |
| Clarify counseling / wellness support pathways | Source mentions counseling access | PDF p.3 | Wellness | Members | Existing wellness support pages | Low–Med | Pathway doc **blocking** for claims |
| Structured technical advisory enquiry routing | Source describes advisory units; only generic contact exists | PDF p.3–4 | Technical Support | Members, staff | Contact tickets taxonomy | Medium | Intake SLA + categories **blocking** |
| Keep org hubs accurate as source packs arrive | Presentation layer already exists | PDF departments | All five | Public | Content only | Low | Ongoing |

---

### P2 — Valuable enhancement

| Feature | Reason | Source | Department | Users | Dependency | Complexity | Source/docs |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Public industry insights / research summaries | Digital Systems generates intelligence | PDF p.3 | Digital Systems | Public, members | Editorial + optional tables | Medium | What may be published |
| Mentorship programme pages (Balingani) | Mentorship named under Balingani Wing | PDF p.3 | Wellness / Inclusivity | Women contractors | Content / optional signup | Medium | Mentorship model |
| Disability / environmental / CSR programme pages | Named themes | PDF p.1, p.3 | Wellness / CSR | Public | Content | Low–Med | Programme briefs |
| Tender market intelligence summaries | Named under Tender unit + Digital Systems | PDF p.3–4 | Technical + Digital | Members | Editorial / data rules | Medium | Publishable data rules |
| About org-chart refresh | PDF shows Board / Secretariat / departments | PDF p.2 | Governance | Public | BOD validation | Low | Confirm leadership content |
| Education-policy deeper link from Advocacy | Related content exists | Site + mandate | Advocacy | Stakeholders | Content | Low | Editorial |

---

### DEFERRED — Requires source/documentation first

| Feature | Reason deferred | Source status |
| --- | --- | --- |
| Smart Loan application / underwriting system | Product details unknown | SOURCE REQUIRED |
| Autism programme/case system | Only awareness/advocacy named | SOURCE REQUIRED |
| Site-record document management / approvals | Guidance theme only | SOURCE REQUIRED |
| Full advisory CRM / case management | Not evidenced as platform mandate | SOURCE REQUIRED |
| Invented advocacy impact dashboard | Stats unpublished | SOURCE REQUIRED |
| New RBAC roles for loan/autism/site-records | No verified workflows | DEFERRED |
| New database tables for above | Schema freeze until verified | DEFERRED |

---

## Implementation order (recommended for next build phase)

1. Receive ECA source packs (see `ECA_P2_SOURCE_REQUEST_LIST.md`)  
2. Content-only updates for verified packs (no schema)  
3. Optional contact-ticket category routing for Technical Support / Advocacy (if approved — may need field; document before alter)  
4. Only then consider transactional systems with dedicated design + security review  
