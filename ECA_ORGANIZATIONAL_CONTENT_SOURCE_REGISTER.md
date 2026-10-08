# ECA Organizational Content Source Register

**Date:** 2026-10-08  
**Environment:** Local only (`D:\Website`)  
**Authoritative source file (project copy):** `_private/source-verify/WHO_WE_ARE_ECA_BACKGROUND_AND_CURRENT_POSITION.pdf`  
**Extract:** `_private/source-verify/who-we-are-extract.txt`  
**Original Downloads path:** `C:\Users\mthok\Downloads\WHO WE ARE ECA BACKGROUND AND CURRENT POSITION.pdf`

---

## Five-pillar verification status

**STATUS: SOURCE VERIFIED** (via PyMuPDF text extraction + visual page PNG inspection)

### Departmental / operational structure (PDF pp. 2–4) — website mapping basis

| # | Exact source name (departmental) | Source page | Website location |
| --- | --- | --- | --- |
| 1 | Advocacy Policy & Legal Affairs | p.2 | `/advocacy.php` (title: Advocacy, Policy & Legal Affairs) |
| 2 | Digital Systems & Data Intelligence | p.2–3 | `/digital-intelligence.php` |
| 3 | Professionalization And Capacity Building | p.2–3 | `/professionalization.php` |
| 4 | Member Wellness, Inclusivity & CSR Operations | p.3 | `/wellness-inclusivity.php` |
| 5 | Technical support and advisory | p.3–4 | `/technical-support.php` |

### Evolutionary “pillar expansion” wording (PDF p.1) — naming discrepancy

Page 1 describes Advocacy as **Core Foundation**, then “four strategic pillars” with different labels:

1. Digital Transformation & Intelligence  
2. Inclusivity & Empowerment  
3. Capacity Building & Professionalization  
4. Holistic Wellness & Human-Centric Support  

**Note:** Technical Support & Advisory appears in the departmental structure (pp. 2–4) but is **not** listed among the four evolutionary pillars on p.1. The public website follows the **departmental** five-function model (pp. 2–4), which includes Technical Support.

---

## Source register table

| Theme/Pillar | Source | Page/Location | Verified? | Website Location | Notes |
| --- | --- | --- | --- | --- | --- |
| History | Existing About + homepage history; PDF background (CIC Act / industry policy evolution) | about.php; index history; PDF p.1 | Yes (site history); PDF supports advocacy evolution framing | `/about-history.php`, `/about.php`, `/` | New History page reuses verified site timeline only |
| Advocacy | WHO WE ARE PDF | p.1–2 | Yes | `/advocacy.php` | Heartbeat; legislation review; regulatory environment; industry policy; fair procurement; business environment; policymaker engagement |
| Digital Systems | WHO WE ARE PDF | p.2–3 | Yes | `/digital-intelligence.php` | Portal, contractor database/insights, market research; feeds advocacy & professionalization |
| Professionalization | WHO WE ARE PDF | p.2–3 | Yes | `/professionalization.php` | Training programmes; CPD points; technical excellence |
| Wellness / Inclusivity / CSR Ops | WHO WE ARE PDF | p.1, p.3 | Yes | `/wellness-inclusivity.php` | Balingani; mental health; smart loan; environmental; disability; autism; CSR |
| Technical Support & Advisory | WHO WE ARE PDF | p.3–4 | Yes | `/technical-support.php` | Business / contracts / tenders units |
| Smart Loan | WHO WE ARE PDF | p.1, p.2 diagram, p.3 | **Yes — named only** | Wellness hub financial theme | Product terms, partners, application path **SOURCE REQUIRED** |
| Autism | WHO WE ARE PDF | p.1 (“autism awareness”), p.3 (“autism advocacy”) | **Yes — named only** | Wellness hub social impact | Campaigns/dates/partners **SOURCE REQUIRED** |
| Site Records | WHO WE ARE PDF | p.4 | **Yes — named** | Technical Support contract section | Best practices for maintaining site records; templates **SOURCE REQUIRED** |
| Operating Model | Website presentation + PDF departmental relationships | Structure page; homepage; PDF p.3 feeds/receives | Presentation verified against PDF relationships | `/about-structure.php`, `/` | LISTEN→…→IMPACT is website narrative; Advocacy page has a separate issue-handling workflow |
| News / demo content | `local-db/setup-local-db.php` seed rows | Local DB `news` | Local demo only | `/`, `/news.php` | Presentation badges + seed labels; not official announcements |
| Current advocacy campaigns / impact stats | — | — | **SOURCE REQUIRED** | Advocacy empty-states | Intentionally unpublished |
| Smart Loan product details | — | — | **SOURCE REQUIRED** | Wellness note | Named in org PDF only |
| Autism programme details | — | — | **SOURCE REQUIRED** | Wellness note | Named in org PDF only |

---

## Source-locked theme detail

### Smart Loan

| Field | Value |
| --- | --- |
| Source file | `WHO_WE_ARE_ECA_BACKGROUND_AND_CURRENT_POSITION.pdf` |
| Exact source wording | “smart loan financial services”; “SMART LAON FINANCIAL SERVICES” (PDF typo); “smart loan financial services system to ease cash-flow and personal stressors”; Technical Support links working capital guidance to Smart Loan services in Wellness |
| Supported claims | Theme exists within Wellness & Financial Services / Inclusivity & Wellness Operations |
| Unsupported claims | Rates, partners, eligibility, application process, outcomes, beneficiary counts |
| Current website | `/wellness-inclusivity.php` (informational card + SOURCE LOCK note) |
| Proposed | Keep informational until product documentation is supplied |

### Autism

| Field | Value |
| --- | --- |
| Source file | Same PDF |
| Exact source wording | “autism awareness” (p.1); “autism advocacy” (p.3) |
| Supported claims | Theme exists under social inclusion / CSR-related engagement |
| Unsupported claims | Specific campaigns, dates, partners, outcomes |
| Current website | `/wellness-inclusivity.php` |
| Proposed | Keep informational until programme detail is supplied |

### Site Records

| Field | Value |
| --- | --- |
| Source file | Same PDF |
| Exact source wording | “Best practices for maintaining site records, managing variations (Change Orders), handling extension of time (EOT) claims, and issuing formal notices.” |
| Supported claims | Site records are an identified Technical Support / Project Administration Guidance theme |
| Unsupported claims | Downloadable templates, live advisory workflow, delivery statistics |
| Current website | `/technical-support.php` (#contracts) |
| Proposed | Informational theme card only |

---

## Production / schema safety

| Item | Status |
| --- | --- |
| Database schema changes | NONE |
| Production access | NONE |
| Auth / RBAC changes | NONE |
| Demo news labelling | Presentation-layer detection + future seed title prefix `[LOCAL DEMO]` |

---

## P2 follow-on mapping (audit only)

See:

- `ECA_P2_SOURCE_TO_FEATURE_MATRIX.md`
- `ECA_P2_SOURCE_TO_FEATURE_AUDIT_REPORT.md`
- `ECA_P2_FEATURE_ROADMAP.md`
- `ECA_P2_SOURCE_REQUEST_LIST.md`
- `ECA_P2_ADVOCACY_GAP_ANALYSIS.md`
- `ECA_P2_SMART_LOAN_REQUIREMENTS.md`
- `ECA_P2_AUTISM_REQUIREMENTS.md`
- `ECA_P2_SITE_RECORDS_REQUIREMENTS.md`
