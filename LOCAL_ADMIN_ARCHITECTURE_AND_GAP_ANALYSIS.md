# LOCAL_ADMIN_ARCHITECTURE_AND_GAP_ANALYSIS.md

**Date:** 2026-10-06  
**Purpose:** STEP 1–5 deliverable — show architecture + gaps **before** major modifications.

---

## 1. Local architecture (discovered)

```
D:\Website
├── start-local.bat / local-router.php     → http://127.0.0.1:8765/
├── .env                                   → APP_ENV=local, local DB names only
├── local-db/                              → schema + seed for eca_local / eca_portal_local
├── eca-pages/                             → static/public page sources
└── v1/                                    → runtime PHP application
    ├── admin/                             → Command Centre Hub (61 PHP pages)
    ├── client/                            → Member Hub
    ├── code.jquery.com/cpd/               → CPD portal (learner/officer/super admin)
    ├── wellness/                          → Wellness Hub
    ├── includes/                          → session, rbac, stats, audit, mailer, portal-db
    ├── tools/                             → local verify/bootstrap scripts
    └── verify.php, renewal.php, application.php, directory.php, …
```

### Dual Super Admin surfaces (local)

1. **Hub Command Centre** — `/admin/login.php` → `/admin/index.php` (`super_admin` / staff RBAC)  
2. **CPD Super Admin** — `/cpd/admin/dashboard.php` (`SUPPERADMIN`) with SSO into Hub portals  

### Local databases

| DB | Purpose |
| --- | --- |
| `eca_local` | Hub users/RBAC/audit/settings/CMS/education/wellness/companies/tickets contact |
| `eca_portal_local` | `tbl_client` membership, years, certificates, payments, CPD, member projects, owners |

---

## 2. Live Admin architecture (discovered)

Live Admin = **`https://www.eca.co.sz/demo/`** (UltraPro), **not** `/admin/`.

- Login gate: `/demo/dashboard.php`  
- Open modules observed without credentials in this environment: members, reports, users, balingani, owners report, communication, alerts, news/slides  
- Gated: post-login dashboard interior, application, members25, support, email  

Full screen map: `LIVE_ADMIN_FUNCTIONALITY_MAP.md`

---

## 3. Gap summary (honest)

### Already strong locally
Membership ops, applications workflow, payments, certificates, documents, CPD, wellness, education, RBAC, audit, tickets, content CMS, query-driven dashboard foundation.

### Missing / weak vs live (priority)

| Priority | Gap | Evidence |
| --- | --- | --- |
| P0 | Membership Intelligence reports + PDF/Excel | Live `/demo/reports.php` + export_* |
| P0 | 2025/2026 (members25) year report | Live link exists; interior gated |
| P1 | Companies & Owners report UI | Live page open; local `owners` table unused in Admin UI |
| P1 | Renewals first-class Admin nav | Live renewal KPIs/reports; local data only |
| P2 | Communication Centre / Slides / Alerts | Live modules open |
| P3 | AI Business Assistant | Live module; may be out of scope |
| Blocked | Post-login UltraPro dashboard + application/support/email interiors | **LIVE ACCESS REQUIRED** |

### Must not copy from live
- Password column on users UI  
- Unauthenticated admin data pages  
- Unpaginated full membership HTML dumps  
- Hard delete without audit/soft-status policy  

---

## 4. Deliverables written this pass

1. `LIVE_ADMIN_FUNCTIONALITY_MAP.md`  
2. `LIVE_ADMIN_FUNCTIONALITY_CHECKLIST.md`  
3. `LIVE_TO_LOCAL_DATA_MAPPING.md`  
4. `ECA_ADMIN_FUNCTIONAL_TEST_REPORT.md` (placeholder — tests after implementation)  
5. `ECA_ADMIN_DIFFERENCES.md`  
6. `DATABASE_CHANGES.md` (no changes yet)  
7. `PRODUCTION_SAFETY_REPORT.md`  
8. This file  

---

## 5. Stop point (per your instructions)

**No major Admin parity implementation has been started.**

Awaiting your go-ahead to proceed with STEP 6+ in this order:

1. Membership Intelligence reports from **local** data  
2. Year report (2025/2026) + exports  
3. Owners report  
4. Renewals admin module  
5. Dashboard KPI alignment (additive, query-driven)  
6. Optional live UltraPro chrome modules  
7. Full test + regression + security reports  

If you can provide **local-only** Super Admin access notes for live (secure channel), gated screens can be re-audited without guessing.
