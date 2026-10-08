# ECA P1 Content / Source-Verification Pass — Report

**Date:** 2026-10-08  
**Environment:** Local only  

---

## 1. Files changed

| File | Change |
| --- | --- |
| `v1/about-history.php` | **Created** — History gateway page from verified site content |
| `v1/about.php` | About gateway buttons (History / Mission / Structure / Leadership) |
| `v1/about-mission.php` | Link to History |
| `v1/about-structure.php` | Operating intro from source relationships; History link; CSR Operations naming |
| `v1/advocacy.php` | Source-backed subsection map; clarified advocacy workflow vs org model |
| `v1/wellness-inclusivity.php` | Smart Loan + Autism Awareness source-locked themes; CSR Operations title |
| `v1/technical-support.php` | Site Records + Subcontractor Management; available-now clarification |
| `v1/includes/organization.php` | Operating copy; CSR Operations title; CSS version |
| `v1/includes/demo-content.php` | **Created** — local demo news detection/badges |
| `v1/includes/public-header.php` | Our History nav item |
| `v1/includes/site-footer.php` | Our History footer link |
| `v1/index.php` | History CTA; operating-model explanations; demo news badges |
| `v1/news.php` | Demo news badges on featured/grid |
| `v1/css/organization.css` | History list, flow explain, demo badge styles |
| `local-router.php` | `about-history` preferred route |
| `v1/sitemap.php` | `/about-history.php` |
| `local-db/setup-local-db.php` | Future seed titles/summaries marked `[LOCAL DEMO]` / LOCAL DEMONSTRATION |
| `ECA_ORGANIZATIONAL_CONTENT_SOURCE_REGISTER.md` | **Created** |
| `_private/source-verify/*` | PDF copy, text extract, page PNGs |

---

## 2–3. Content & navigation changes

- About → History gateway (nav, footer, About cards, homepage History CTA)
- Advocacy subsection map (Legislation Review, Regulatory Environment, Industry Policy, Fair Procurement, Business Environment, Policymaker Engagement, intelligence-informed advocacy)
- Operating-model copy explains members → digital systems → advocacy / training / advisory / wellness
- Source-locked Smart Loan, Autism Awareness, Site Records cards with SOURCE REQUIRED notes for unconfirmed product/programme detail
- Local demo news labelled on homepage and news page

---

## 4–8. Source verification results

| Item | Status |
| --- | --- |
| Five-pillar PDF | **SOURCE VERIFIED** (departmental model pp. 2–4) |
| Smart Loan | **VERIFIED as named theme**; product details **SOURCE REQUIRED** |
| Autism | **VERIFIED as named theme** (awareness/advocacy); programme details **SOURCE REQUIRED** |
| Site Records | **VERIFIED as named theme** under Project Administration Guidance; templates **SOURCE REQUIRED** |
| Demo news | Labelled LOCAL DEMO / SAMPLE CONTENT (presentation + future seeds) |

**Naming note:** PDF p.1 uses evolutionary pillar labels that differ from departmental names on pp. 2–4. Website follows departmental five-function model including Technical Support.

---

## 9–12. Safety

| Item | Result |
| --- | --- |
| Database schema changes | **NONE** |
| Production changes | **NONE** |
| Auth/RBAC changes | **NONE** |

---

## 13–14. Regression

| Suite | Result |
| --- | --- |
| Final-system | **67 PASS / 0 FAIL** (1 BLOCKED payment E2E; 3 NOT_VERIFIABLE_LOCALLY) |
| Access-control | **102 PASS / 0 FAIL** |

Smoke: About History, Advocacy map, Smart Loan/Autism/Site Records, demo badges, operating-model copy — all HTTP 200 with expected markers.

---

## 15. Remaining SOURCE-REQUIRED items

- Current advocacy campaigns / meeting records / impact statistics  
- Smart Loan product terms, partners, eligibility, application path  
- Autism programme schedules, partners, outcomes  
- Site-record templates / advisory delivery workflows  
- Any claim that Technical Support themes are currently delivered as a live case-management service  
