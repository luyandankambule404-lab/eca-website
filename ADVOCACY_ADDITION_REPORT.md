# ECA Advocacy Addition Report

**Date:** 2026-10-07  
**Scope:** Additive public Advocacy feature only  
**Environment:** Local (`D:\Website`, `http://127.0.0.1:8765`)

---

## Summary

A new public **Advocacy** presence was added without redesigning the existing ECA website, portals, dashboards, authentication, database, RBAC, or admin systems.

Desired structure delivered:

- Existing ECA website preserved
- New Advocacy page
- Small homepage Advocacy section
- Advocacy navigation link

---

## Files created

| File | Purpose |
|------|---------|
| `v1/advocacy.php` | Standalone public Advocacy page |
| `v1/css/advocacy.css` | Scoped `.advocacy-page` styles only |
| `ADVOCACY_ADDITION_REPORT.md` | This report |

---

## Files modified (minimal / additive)

| File | Change |
|------|--------|
| `v1/includes/public-header.php` | Added top-level **Advocacy** nav link |
| `v1/index.php` | Added homepage Advocacy preview section; Industry advocacy card CTA now points to `/advocacy.php` |
| `v1/css/home.css` | Scoped `.eca-home-advocacy*` styles only |
| `v1/includes/site-footer.php` | Added Advocacy link under About |
| `v1/sitemap.php` | Added `/advocacy.php` |
| `v1/contact.php` | Optional subject prefill from `?subject=` only (existing form unchanged) |

---

## Homepage section

Added section **THE VOICE OF ESWATINI'S CONTRACTORS** with four focus areas:

- Fair Procurement
- Timely Payments
- Local Contractor Development
- Better Industry Regulation

CTA: **Explore Advocacy** → `/advocacy.php`

Existing homepage sections were not removed or rearranged beyond inserting this additive block before Newsroom.

---

## Navigation change

Added **Advocacy** as a top-level public nav item (after Membership, before Membership development).

No existing nav items were removed or reordered beyond this insertion.

---

## Advocacy page (`/advocacy.php`)

Sections included:

1. Hero / intro with **Explore Our Advocacy** and **Raise an Industry Issue**
2. Why Does Advocacy Matter?
3. How ECA Advocates (Listen → Impact process)
4. Our Advocacy Priorities (six cards)
5. ECA IN ACTION (activity types only — no invented dates/meetings)
6. What We're Working On (professional empty state)
7. YOUR VOICE MATTERS → existing Contact form
8. Our Advocacy Impact (no invented statistics)

Issue reporting reuses existing Contact:

`/contact.php?subject=Raise%20an%20Industry%20Issue`

---

## Database changes

**NONE**

- No tables created
- No columns altered
- No migrations
- No record changes

---

## Admin changes

**NONE**

- No Advocacy Admin module
- Super Admin Hub untouched
- Dashboards untouched

---

## RBAC changes

**NONE**

- No new permissions
- No role matrix changes

---

## Existing functionality preserved

Untouched by design:

- Members, Companies, Applications, Payments, Certificates
- CPD / Wellness / Support / Reports / Audit
- Authentication and session model
- Portal workflows
- Existing public pages (except additive homepage section + contact subject prefill)

---

## Tests performed

- HTTP smoke: `/`, `/advocacy.php`, `/about.php`, `/contact.php`, `/education-knowledge.php`, `/admin/login.php`
- Confirmed Advocacy nav + homepage section + Advocacy hero content
- `v1/tools/final-system-verify.php`
- `v1/tools/access-control-verify.php`
- `v1/tools/phase-a-auth-test.php` (partial — see note below)

---

## Regression results

Recorded after implementation (local, 2026-10-07):

| Suite | Result |
|-------|--------|
| Final system verify | **67 PASS / 0 FAIL** (1 BLOCKED, 3 NOT_VERIFIABLE_LOCALLY) |
| Access control verify | **102 PASS / 0 FAIL** |
| HTTP smoke (Advocacy + existing public pages) | **All 200** |

Protected baselines preserved via final suite coverage of companies/RBAC/dashboard/audit surfaces.

### Note on phase-a-auth-test.php

Script reached a pre-existing TypeError (`eca_assign_hub_roles(... $sessionActor)` null) after many PASS lines. This is unrelated to Advocacy files (no RBAC/admin/auth changes in this addition). Access-control and final-system suites covering the same privilege boundaries remained green.

---

## Known limitations

1. Advocacy is content/UI only — no CMS/admin editor yet
2. Current initiatives and impact metrics intentionally empty until verified data exists
3. No dedicated advocacy issue workflow beyond Contact form reuse

---

## Future recommendations

1. Design Advocacy Admin module separately (with explicit approval)
2. Only then consider storage for verified initiatives/metrics
3. Keep public empty states until data is formally documented
4. Do not invent campaign claims or statistics
