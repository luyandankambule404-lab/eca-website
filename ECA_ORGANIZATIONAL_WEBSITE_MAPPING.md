# ECA Organizational Website Mapping

**Date:** 2026-10-08  
**Environment:** Local only (`D:\Website`, `http://127.0.0.1:8765`)  
**Purpose:** Map official organizational functions to existing website modules before additive public IA work.

---

## Absolute constraints

| Constraint | Status |
|------------|--------|
| No database CREATE/ALTER/DROP | Enforced |
| No Admin Hub / RBAC / auth changes | Enforced |
| No rebuild of CPD / Wellness / Directory | Enforced |
| No invented statistics or campaigns | Enforced |

---

## Function → existing module map

| ECA Function | Existing Website Module | Existing Route(s) | Action |
| --- | --- | --- | --- |
| Advocacy, Policy & Legal Affairs | Public Advocacy page + education policy content | `/advocacy.php`, `/education-policy.php` | Integrate / enhance presentation; reuse routes |
| Digital Systems & Data Intelligence | Member directory, tenders, verify, track | `/directory.php`, `/tenders.php`, `/verify.php`, `/track.php`, `/balingani-directory.php` | New public hub linking to existing tools |
| Professionalization & Capacity Building | Education hub + CPD learner/admin systems | `/education.php`, `/education-training.php`, `/education-knowledge.php`, `/education-learner.php`, `/cpd/login.php`, `/cpd/contractor/*` | New public hub; reuse CPD/education |
| Member Wellness, Inclusivity & CSR | Wellness Hub + Balingani | `/wellness/`, `/wellness/mental-health.php`, `/wellness/library.php`, `/client/wellness/`, `/balingani-directory.php` | New public hub; reuse wellness/Balingani |
| Technical Support & Advisory | Contact / support presentation (no advisory workflow DB) | `/contact.php`, `/faq.php`, `/resources.php` | Informational hub only |
| Membership | Membership registration / portals | `/membership-registration.php`, `/application.php`, `/client/`, `/checklist.php` | Preserve |
| Leadership | Executive committee page | `/about-bod.php` | Link from About IA |
| Governance | Bylaws / About | `/about.php`, `/about-by-laws.php` | Expand About nav with structure/mission pages |
| Administration | Admin Hub | `/admin/*` | **DO NOT redesign** |

---

## New additive public routes (presentation layer)

| Route | Role |
| --- | --- |
| `/about-mission.php` | Mission & purpose |
| `/about-structure.php` | Organizational structure + strategic pillars |
| `/digital-intelligence.php` | Digital Systems & Data Intelligence hub |
| `/professionalization.php` | Professionalization & Capacity Building hub |
| `/wellness-inclusivity.php` | Member Wellness, Inclusivity & CSR hub |
| `/technical-support.php` | Technical Support & Advisory hub |

Existing `/advocacy.php` remains the Advocacy pillar entry.

---

## Database changes required?

**NONE.**

If richer CMS-managed org content is needed later, document and seek approval before any schema work.

---

## Navigation / discovery (implemented)

| Area | Location |
| --- | --- |
| About ECA / Mission / Structure / Leadership | About mega-menu + footer |
| Advocacy | Top-level Advocacy + About footer |
| Digital / Professionalization / Technical / Wellness hubs | **Our work** mega-menu + footer “What ECA does” |
| Membership / Directory / News / Contact | Existing public nav preserved |

## Implementation status

See `ECA_ORGANIZATIONAL_WEBSITE_IMPLEMENTATION_REPORT.md`.

**Database changes required?** Still **NONE**.

## Security notes

- Public hubs link only to existing public (or login-gated) routes.
- No private member data, documents, payments, or audit content exposed.
- Admin / portal authz untouched.
