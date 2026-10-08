# ECA P2 — Source-to-Feature Matrix

**Date:** 2026-10-08  
**Environment:** Local only  
**Authoritative source:** `_private/source-verify/WHO_WE_ARE_ECA_BACKGROUND_AND_CURRENT_POSITION.pdf`  
**Basis:** Departmental model (PDF pp. 2–4). Page-1 evolutionary labels documented separately.

**Status legend:** EXISTING · CONTENT GAP · FEATURE GAP · SOURCE REQUIRED · DATA REQUIRED · VALIDATION REQUIRED · DEFERRED

---

## Naming note (p.1 vs pp. 2–4)

| Layer | Names |
| --- | --- |
| Core foundation (p.1) | Advocacy |
| Evolutionary pillars (p.1) | Digital Transformation & Intelligence; Inclusivity & Empowerment; Capacity Building & Professionalization; Holistic Wellness & Human-Centric Support |
| Departments (pp. 2–4) | Advocacy Policy & Legal Affairs; Digital Systems & Data Intelligence; Professionalization And Capacity Building; Member Wellness, Inclusivity & CSR Operations; Technical support and advisory |

Website IA follows **departmental** names. Technical Support is departmental (pp. 2–4) but **not** one of the four evolutionary pillars on p.1.

---

## Matrix

| Department/Pillar | Source-backed responsibility | Existing page | Existing feature | Missing feature | Data required? | Source required? | Priority | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Advocacy Policy & Legal Affairs | Heartbeat; policymakers; legislation review (CIC, AESAP); fair procurement; business environment | `/advocacy.php`, `/education-policy.php`, `/contact.php` | Public advocacy hub; subsection map; raise-issue → contact; contact tickets | Published campaigns, outcomes, impact metrics CMS | Optional CMS later | Yes — campaign/impact evidence | P1 | CONTENT GAP / SOURCE REQUIRED |
| Advocacy | Industry representation mandate | `/about-history.php`, `/about.php` | Historical CIC Act advocacy narrative (verified site content) | Dated campaign archive | Optional | Yes for new claims | P2 | EXISTING (history) |
| Digital Systems & Data Intelligence | Digital registration portal; contractor database & insights; market research; intelligence for advocacy/CPD | `/digital-intelligence.php`, `/membership-registration.php`, `/directory.php`, `/verify.php`, `/track.php`, `/tenders.php`, `/balingani-directory.php` | Registration, directory, verify, track, tenders, Balingani | Internal intelligence reports product; public “insights” publications | Reports may need tables | Yes — what may be published | P2 | EXISTING (tools) / SOURCE REQUIRED (insights product) |
| Digital Systems | Track member challenges; operational data | Admin companies/members; contact tickets | Operational member DB; tickets | Dedicated “member challenges” intelligence module | Possible | Yes | DEFERRED | SOURCE REQUIRED |
| Professionalization And Capacity Building | Training programmes; CPD points; technical excellence | `/professionalization.php`, `/education*`, `/cpd/*`, `/admin/education*`, `/admin/cpd.php` | Education CMS; CPD courses/applications/transcripts/points | — | Existing CPD/education tables | No for core CPD | P0 | EXISTING |
| Member Wellness, Inclusivity & CSR Operations | Balingani wing; mentorship theme | `/wellness-inclusivity.php`, `/balingani-directory.php` | Directory + inclusivity copy | Mentorship programme workflow | Possible | Yes — mentorship process | P2 | EXISTING (directory) / SOURCE REQUIRED (mentorship) |
| Wellness | Mental health & wellness support | `/wellness/*`, `/client/wellness/*`, `/admin/wellness/*` | Hub content, events, resources, check-in, announcements | Counseling booking system | Possible | Yes — counseling delivery model | P1 | EXISTING (hub) / SOURCE REQUIRED (counseling path) |
| Wellness | Smart Loan financial services | `/wellness-inclusivity.php` theme card | Informational only | Loan product / application / eligibility | Yes if built | **Yes — blocking** | DEFERRED | SOURCE REQUIRED |
| Wellness / Social Impact | Autism awareness / advocacy | `/wellness-inclusivity.php` theme card | Informational only | Programme pages/events/resources | Possible | **Yes — blocking** | DEFERRED | SOURCE REQUIRED |
| Wellness / Social Impact | Disability advocacy; environmental; CSR | `/wellness-inclusivity.php` | Theme cards | Programme detail | Possible | Yes | P2 | CONTENT GAP / SOURCE REQUIRED |
| Technical support and advisory | Business health checks; financial literacy; strategic planning; compliance support | `/technical-support.php`, `/contact.php`, `/faq.php`, `/resources.php` | Informational themes; contact | Structured advisory intake for business unit | Possible | Yes — delivery model | P1 | CONTENT GAP / FEATURE GAP (intake) / SOURCE REQUIRED |
| Technical Support | Contract clause interpretation; FIDIC/JBCC; site records; variations; EOT; notices; subcontractors; early dispute resolution | `/technical-support.php` | Informational themes | Templates, advisory cases, site-record system | Yes if built | **Yes — blocking for systems** | DEFERRED | SOURCE REQUIRED |
| Technical Support | Tender documentation review; bid costing training; tender risk; market intelligence | `/technical-support.php`, `/tenders.php` | Published tenders list; informational themes | Bid review workflow; pricing trend product | Possible | Yes | P2 | EXISTING (tenders) / SOURCE REQUIRED (advisory) |
| Governance (Board / Exec Committee / Secretariat) | Org chart in PDF | `/about-bod.php`, `/about-structure.php` | Leadership page; structure narrative | Full org chart CMS | Optional | Validate current BOD content | P2 | EXISTING / VALIDATION REQUIRED |
| Cross-cutting — Contact support | Not named as a department; operational need | `/contact.php`, `/admin/tickets.php` | `contact_messages` tickets | Department routing taxonomy | Optional field | Yes — SLA/routing | P2 | EXISTING |
| Cross-cutting — CPD support | CPD learner support | `/cpd/*/support.php` | `support_tickets` | — | Existing | No | P0 | EXISTING |
| Cross-cutting — Membership lifecycle | Implied by registration/directory mandate | Application/renewal/verify/client/admin | Full membership pipeline | — | Existing | No | P0 | EXISTING |

---

## Quick counts (approximate)

| Status | Count (rows above) |
| --- | --- |
| EXISTING (core digital services) | Strong for Membership, Directory, CPD, Wellness hub, Contact tickets, Tenders |
| SOURCE REQUIRED | Smart Loan, Autism programme, Site Records system, advocacy campaigns/impact, advisory delivery model, mentorship, counseling path, intelligence publications |
| FEATURE GAP (evidence-based, still need source for *how*) | Structured technical advisory intake; optional insights publishing |
| DEFERRED | Loan system, autism system, site-record DMS, CRM/case management, invented advocacy dashboard |
