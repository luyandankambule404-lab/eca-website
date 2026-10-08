# ECA P2 — Source-to-Feature Audit Report

**Date:** 2026-10-08  
**Environment:** Local only (`D:\Website`, `http://127.0.0.1:8765`)  
**Mode:** Audit & mapping only — **no** new systems, schema, auth, RBAC, or production changes  

**Inputs:**  
- `_private/source-verify/WHO_WE_ARE_ECA_BACKGROUND_AND_CURRENT_POSITION.pdf` (+ extract/PNGs)  
- `ECA_ORGANIZATIONAL_CONTENT_SOURCE_REGISTER.md`  
- `ECA_P1_CONTENT_SOURCE_VERIFICATION_REPORT.md`  
- Live codebase inventory (public, client, admin, CPD, wellness)

**Companion artefacts:**  
- `ECA_P2_SOURCE_TO_FEATURE_MATRIX.md`  
- `ECA_P2_ADVOCACY_GAP_ANALYSIS.md`  
- `ECA_P2_SMART_LOAN_REQUIREMENTS.md`  
- `ECA_P2_AUTISM_REQUIREMENTS.md`  
- `ECA_P2_SITE_RECORDS_REQUIREMENTS.md`  
- `ECA_P2_FEATURE_ROADMAP.md`  
- `ECA_P2_SOURCE_REQUEST_LIST.md`

---

## 1. Executive summary

The local ECA website already implements a **strong digital membership, directory, CPD, wellness, content, and contact-ticket stack**. P1 correctly surfaced the five departmental pillars on the public site.

P2 finds that **most “gaps” are documentation and content gaps**, not missing core IT platforms:

- **Verified mandates** exist for five departments (PDF pp. 2–4).  
- **Transactional systems already exist** for registration, membership lifecycle, CPD, wellness content/events, tenders, and support tickets.  
- **Smart Loan, Autism, Site Records, and live advocacy campaigns** are **named or mandated at theme level** but lack product/programme evidence → **must not be built yet**.  
- **Technical Support & Advisory** is a verified departmental mandate, but delivery today is **informational + Contact**, not a case-management CRM.

**Verdict:** Blueprint ready for a source-gated implementation phase. Do not overbuild.

---

## 2. Verified five-pillar structure

### Departmental model (authoritative for website) — PDF pp. 2–4

| # | Exact source name | Source page | Source description (condensed) | Website representation | Nav | Pages | Features | Missing | Confidence | Extra docs? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Advocacy Policy & Legal Affairs | p.2 | Heartbeat; policymakers; legislation (CIC, AESAP); fair procurement; business environment | Strong public hub | Top-level Advocacy | `/advocacy.php` | Presentation, contact CTA | Live campaigns/impact | High | Yes |
| 2 | Digital Systems & Data Intelligence | p.2–3 | Registration portal; contractor DB/insights; market research; intelligence for advocacy/CPD | Hub + real tools | Our work | `/digital-intelligence.php` + directory/verify/track/tenders/registration | Operational | Public insights product | High | Partial |
| 3 | Professionalization And Capacity Building | p.2–3 | Training; CPD points; technical excellence | Hub + full CPD/education | Our work | `/professionalization.php`, `/education*`, `/cpd/*` | Operational | — | High | No for core |
| 4 | Member Wellness, Inclusivity & CSR Operations | p.3 | Balingani; mental health; Smart Loan; social impact (disability, autism, CSR) | Hub + wellness system + themes | Our work | `/wellness-inclusivity.php`, `/wellness/*`, `/client/wellness/*` | Operational wellness; themes for loan/autism | Programme packs | High (dept); Low (loan/autism detail) | Yes |
| 5 | Technical support and advisory | p.3–4 | Business, contracts, tenders advisory units | Informational hub | Our work | `/technical-support.php` | Themes + contact + tenders link | Live advisory workflows | High (mandate); Low (delivery proof) | Yes |

### Evolutionary labels (PDF p.1) — preserve distinction

Advocacy = **Core Foundation**. Four expansion pillars use different names (Digital Transformation & Intelligence; Inclusivity & Empowerment; Capacity Building & Professionalization; Holistic Wellness & Human-Centric Support). **Technical Support is not listed** in the p.1 four-pillar expansion. Website correctly follows departmental five-function model.

Governance shown in PDF: Board of Directors / Executive Committee → Executive Secretariat → departments.

---

## 3. Department-by-department assessment

### Department: Advocacy Policy & Legal Affairs

**A. Source-backed mandate**  
Original heartbeat; engage policymakers; review legislation (e.g. CIC, AESAP); fair procurement; safeguard business environment; historically shape industry policy / level playing field.

**B. Existing website representation**  
`/advocacy.php` (flagship), subsection map, homepage advocacy blocks, About/History CIC narrative, `/education-policy.php`, Contact “Raise an Industry Issue”.

**C. Existing services/programmes**  
Mandate-level themes only on public site. Historical CIC advocacy (site-verified). No verified current campaign catalogue.

**D. Existing digital features**  
Public advocacy page; contact form → `contact_messages` tickets; news CMS (generic).

**E. Missing information**  
Current campaigns, meetings, submissions, outcomes, stats.

**F. Missing functionality**  
Optional public updates feed **after** source pack — not a fabricated dashboard.

**G. Data requirements**  
Optional: news/advocacy content rows (existing news tables may suffice). No new schema until content model approved.

**H. Documentation required**  
See Source Request A1–A6.

**I. Priority**  
P1 for content publishing; DEFER invented CRM/KPIs.

---

### Department: Digital Systems & Data Intelligence

**A. Mandate**  
Digital registration; contractor database & insights; market research; operational data; intelligence reports feeding advocacy and professionalization.

**B. Representation**  
`/digital-intelligence.php` linking live tools.

**C. Services**  
Registration, directory, Balingani directory, verify, track, tenders publication, admin company/member data.

**D. Digital features**  
Membership application/renewal pipelines; portal; admin companies/members/reports; tenders CMS.

**E. Missing information**  
Which intelligence reports may be published externally.

**F. Missing functionality**  
Public “insights” library only if ECA supplies publishable research.

**G. Data**  
Mostly existing. New insights product may need content tables later — **document only**.

**H. Docs**  
H2 insights pack.

**I. Priority**  
P0 maintain tools; P2 insights publishing.

---

### Department: Professionalization And Capacity Building

**A. Mandate**  
Training modules; CPD points system; technical excellence.

**B. Representation**  
`/professionalization.php` + education public pages.

**C/D. Services & features**  
Education CMS; CPD courses, applications, learners, attendance, transcripts, certificates, payments (CPD), feedback, support tickets.

**E/F.** No major unverified functional hole for core CPD.

**G.** Existing CPD/education schemas.

**H.** Optional curriculum catalogue for public copy.

**I.** P0 maintain.

---

### Department: Member Wellness, Inclusivity & CSR Operations

**A. Mandate**  
Care for person behind company; Balingani wing (participation, mentorship); mental health & wellness; Smart Loan financial services; social impact (environmental, disability, autism, CSR).

**B.** `/wellness-inclusivity.php`, `/wellness/*`, `/client/wellness/*`, `/admin/wellness/*`, `/balingani-directory.php`.

**C.** Wellness content, events, resources, check-in, announcements; Balingani directory. Smart Loan / Autism = named themes only.

**D.** Full wellness admin + member wellness portal.

**E.** Counseling path details; mentorship model; loan/autism programme packs; CSR programme detail.

**F.** No loan/autism systems until source. Mentorship signup only after brief.

**G.** Existing wellness tables. Loan/autism systems would need **new** data — deferred.

**H.** Requests B*, C*, F*.

**I.** P0 wellness platform; DEFER loan/autism systems; P1 counseling pathway clarity.

---

### Department: Technical support and advisory

**A. Mandate**  
Business management & growth (health checks, financial literacy, strategic planning, compliance); Contract management (FIDIC/JBCC, site records, variations, EOT, notices, subcontractors, early dispute resolution); Tender & procurement support (doc review, costing training, risk, market intelligence).

**B.** `/technical-support.php` informational; Contact; FAQ; Resources; Tenders list.

**C.** Published tenders (digital). Advisory services themselves not proven as digital workflows.

**D.** Contact tickets; tenders CMS; member project files (not verified as Site Records programme).

**E.** What is delivered today vs aspirational unit list.

**F.** Structured intake **only after** E1–E2. Site-record DMS deferred.

**G.** Possible ticket category field later — **do not alter schema now**.

**H.** Requests D*, E*.

**I.** P1 intake clarification; DEFER CRM/DMS.

---

## 4. Public website assessment

| Area | Purpose | Owning dept (best fit) | Source | Info vs transactional | Portal link? | Auth? | DB? | Implemented? | Content verified? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Home | Brand + discovery | Cross-cutting | Org PDF + site | Info + CTAs | Partial | No | News etc. | Yes | Mixed (demo news labelled) |
| About | Who we are | Governance | Site + PDF | Info | No | No | No | Yes | Yes (existing) |
| Our History | Timeline | Governance / Advocacy history | Site history | Info | No | No | No | Yes (`/about-history.php`) | Yes |
| Mission / Structure | Org model | Governance | PDF | Info | Pillar links | No | No | Yes | Yes |
| Leadership | BOD | Governance | Site | Info | No | No | No | Yes | VALIDATION REQUIRED currency |
| Advocacy | Mandate | Advocacy | PDF | Info + contact | Contact | No | Tickets | Yes | Mandate yes; campaigns no |
| Digital Intelligence hub | Dept gateway | Digital | PDF | Info → tools | Tools | Mixed | Yes | Yes | Yes |
| Professionalization hub | Dept gateway | Professionalization | PDF | Info → CPD | CPD/education | Mixed | Yes | Yes | Yes |
| Wellness & Inclusivity hub | Dept gateway | Wellness | PDF | Info → wellness | Wellness | Mixed | Yes | Yes | Themes locked |
| Technical Support hub | Dept gateway | Technical | PDF | Info | Contact/tenders | No | Partial | Yes | Themes locked |
| Membership | Join/renew | Digital / Membership ops | Registration mandate | Transactional | Client after approval | Forms public | Yes | Yes | Yes |
| Directory / Balingani | Find contractors | Digital / Inclusivity | PDF | Transactional search | Profiles | No | Yes | Yes | Yes |
| Verify / Track | Trust / status | Digital | Ops | Transactional | No | No | Yes | Yes | Yes |
| Tenders | Opportunities | Digital + Technical market intel | PDF | Info list | Detail | No | Yes | Yes | Content CMS |
| News / Events / Gallery | Communications | Content | Ops | Info / registration | Events POST | No | Yes | Yes | Demo news labelled |
| Resources / Documents / FAQ | Knowledge | Cross / Professionalization | Ops | Info | Downloads | Mixed | Mixed | Yes | Mixed |
| Wellness Hub | Care | Wellness | PDF | Info + tools | Member wellness | Public hub | Yes | Yes | Yes |
| Contact | Enquiries | Cross | Ops | Transactional | Ticket admin | No | Yes | Yes | Yes |
| Education pages | Learning | Professionalization | PDF | Info | Learner | Mixed | Yes | Yes | Yes |

**Do not create pages only to fill nav.** Gaps are mostly **content packs**, not missing IA shells.

---

## 5. Member journey assessment (verified stages only)

```
PUBLIC WEBSITE
  → MEMBERSHIP INFORMATION (/membership-registration, checklist)
  → APPLICATION / ARTISAN / RENEWAL (public forms)
  → REVIEW (admin applications/documents/payments)
  → APPROVAL
  → MEMBER PORTAL (/client/*)
  → MEMBER SERVICES (profile, documents, certificate, payments, projects, notifications)
  → CPD (/cpd/*) / WELLNESS (/client/wellness/*) / RESOURCES
  → SUPPORT (contact tickets; CPD support tickets)
  → RENEWAL
```

| Stage | Page/portal | Role | DB | Implemented | Missing | Source dependency |
| --- | --- | --- | --- | --- | --- | --- |
| Discover | Home/About/Directory | Public | Mixed | Yes | — | — |
| Apply | application / artisan | Public | Portal | Yes | — | — |
| Track | `/track.php` | Public | Portal | Yes | — | — |
| Admin review | `/admin/applications*` | Staff | Hub/portal | Yes | — | — |
| Member hub | `/client/*` | Member | Portal | Yes | — | — |
| CPD | `/cpd/*` | Learner/Contractor | CPD | Yes | — | PDF CPD mandate |
| Wellness | `/client/wellness/*` | Member | Wellness | Yes | Counseling clarity | PDF |
| Advisory deep service | — | — | — | **No workflow** | Intake | SOURCE REQUIRED |
| Smart Loan | — | — | — | **No** | Product | SOURCE REQUIRED |

---

## 6. Advocacy assessment

See `ECA_P2_ADVOCACY_GAP_ANALYSIS.md`.  
**Summary:** Mandate **VERIFIED**. Campaigns/impact **SOURCE REQUIRED**. Contact pathway **EXISTING**.

---

## 7. Smart Loan assessment

See `ECA_P2_SMART_LOAN_REQUIREMENTS.md`.  
**Summary:** Named under Wellness financial services; linked from Technical Support financial literacy. **No product system.** **DEFERRED.**

---

## 8. Autism assessment

See `ECA_P2_AUTISM_REQUIREMENTS.md`.  
**Summary:** “Awareness” / “advocacy” named only. **DEFERRED.**

---

## 9. Site Records assessment

See `ECA_P2_SITE_RECORDS_REQUIREMENTS.md`.  
**Summary:** Guidance theme under contract administration. **Not** proof of DMS. **DEFERRED.**

---

## 10. Wellness assessment

| Question | Answer |
| --- | --- |
| Owning department | Member Wellness, Inclusivity & CSR Operations |
| Source support | PDF p.1 Holistic Wellness; p.3 department functions |
| Existing events/announcements/resources | Yes — admin wellness + member portal |
| Registrations | Wellness event registrations exist |
| Public pages | `/wellness/*` extensive |
| Authenticated | `/client/wellness/*` (`wellness.view`) |
| Missing verified functionality | Counseling pathway detail; mentorship programme; Smart Loan/Autism programmes |
| Separate from proposals | Keep existing hub; add content only when sourced |

---

## 11. Advisory / case-management assessment

| Item | Classification |
| --- | --- |
| Technical Support departmental mandate | VERIFIED |
| Business / contract / tender unit theme lists | VERIFIED (as org functions in PDF) |
| Public informational presentation | EXISTING |
| Contact tickets (`contact_messages`) | EXISTING (general support — not advisory CRM) |
| CPD `support_tickets` | EXISTING (CPD support only) |
| Dedicated advisory case management / CRM | SOURCE REQUIRED / POTENTIAL FUTURE FEATURE |
| Complaints workflow table | Not found — SOURCE REQUIRED if needed |
| Early dispute resolution digital workflow | SOURCE REQUIRED |

**Do not build CRM** until ECA confirms delivery model.

---

## 12. User / journey types (verified roles only)

| Actor | Existing support |
| --- | --- |
| Public visitor | Public site, directories, verify, track, contact, news, education, wellness hub |
| Prospective member | Registration/application/renewal |
| Existing member (`userss` MEMBER) | `/client/*`, wellness view, CPD contractor/learner paths |
| ECA Hub staff | Roles: admin, membership_officer, finance_officer, content_manager, training_officer |
| Administrator (Hub `admin`) | Ops modules; not users/roles/settings |
| Super Administrator | Full Hub including users/roles/security/settings |
| CPD staff | SUPPERADMIN / ADMIN / OFFICER (CPD system) |
| CPD learner/contractor | CPD app |

**Do not invent** loan officer / autism caseworker roles without source + RBAC design phase.

---

## 13. Data requirements (documentation only)

| Proposed area | Existing tables usable? | New table? | New field? | Docs storage? | Audit? | Notify/email? | Permissions? | Workflow status? |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Advocacy updates | Likely `news` | No unless separate | Optional category | Optional | Existing news admin | Optional | `content.manage` | Publish status exists |
| Smart Loan | No | **Would need** | **Would need** | Yes | Yes | Yes | New | Yes |
| Autism programme | Wellness events/resources may suffice for simple content | Maybe later | Optional tags | Optional | Existing | Optional | `wellness.manage` | Event status |
| Site Records DMS | No | **Would need** | **Would need** | Yes | Yes | Yes | New | Yes |
| Advisory intake categories | `contact_messages` | Prefer avoid | **Maybe category field** | No | Existing tickets | Existing | `tickets.manage` | Existing ticket status |
| Insights publications | Education articles / news / resources | Optional | Optional | Optional | Existing | Optional | content/education | Publish |

**No schema changes in P2.**

---

## 14. Feature gaps (evidence-based only)

1. Live advocacy updates/impact (content + source)  
2. Publishable industry intelligence products  
3. Confirmed counseling pathway messaging  
4. Structured technical advisory intake (after SLA/categories approved)  
5. Mentorship programme presence (after brief)  
6. Smart Loan / Autism / Site Records **systems** — gaps only if ECA confirms they operate digitally; otherwise remain themes  

---

## 15. Source gaps

See `ECA_P2_SOURCE_REQUEST_LIST.md` (A–H). Hard blockers: Smart Loan pack, Autism pack, Site Records delivery model, Advisory live-vs-theme list, Advocacy campaign/impact pack.

---

## 16. Recommended roadmap

See `ECA_P2_FEATURE_ROADMAP.md` (P0 / P1 / P2 / DEFERRED).

---

## 17. Items explicitly deferred

- Smart Loan transactional system  
- Autism programme/case system  
- Site-record DMS / approvals  
- Advisory CRM / case management  
- Invented advocacy KPI dashboard  
- New RBAC roles / schema for the above  
- Production / auth changes  

---

## Regression safety (this audit)

Documentation-only phase (no application code changes in P2).

| Suite | Result |
| --- | --- |
| Final-system | **67 PASS / 0 FAIL** (1 BLOCKED payment E2E; 3 NOT_VERIFIABLE_LOCALLY) |
| Access-control | **102 PASS / 0 FAIL** (confirmed on re-run) |

Note: One ACL run during this session briefly reported `Failed=1` with a user-29 ACTIVE line inconsistent with the usual SKIP fixture path; immediate re-runs returned **102 PASS / 0 FAIL**. Treated as transient local fixture noise, not an audit-induced regression (P2 wrote docs only).
