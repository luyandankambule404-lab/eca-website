# P2 IMPLEMENTATION MAP

**Date:** 2026-10-06  
**Scope:** LOCAL ONLY (`http://127.0.0.1:8765`)  
**Baseline:** P0 + P1 accepted (30 PASS / 0 FAIL)  
**Live discovery:** NOT AVAILABLE this session — treat UltraPro interiors as **UNVERIFIED LIVE**

---

## 1. Audit summary

| Area | Status | Notes |
| --- | --- | --- |
| Auth / CSRF / sessions | **DO NOT TOUCH** | `v1/admin/auth.php`, login, CSRF |
| RBAC | **DO NOT TOUCH design** | `rbac.php` / `authz.php` — reuse only |
| P0 Membership Intelligence | **COMPLETE** | Preserve |
| P1 Companies / Owners | **COMPLETE** | Preserve |
| Dashboard | **PARTIAL** | Membership + Companies strong; Finance/Certs/CPD/Wellness/Support/Users need clearer sections |
| Navigation | **MOSTLY COMPLETE** | All required links exist; verify permission gating |
| Users / Roles | **COMPLETE core** | Super Admin only for users; polish/search/filter as needed |
| Audit | **COMPLETE core** | Strengthen known-action labels; ensure sensitive actions covered |
| Reports | **PARTIAL** | Foundation + MI + Owners; need structured report hub by domain |
| Members / Apps / Payments / Certs | **COMPLETE** | Review search/filter/pagination; no rewrite |
| CPD | **PARTIAL in Hub** | Monitor + deep links to `/cpd/admin/` |
| Wellness | **COMPLETE CMS** | Ensure dashboard KPIs clear |
| Excel / PDF | **DEFERRED** | CSV + print only |
| Comms / Slides / Alerts | **OUT OF P2 unless tables exist** | Would need **DATABASE CHANGE** → STOP |

---

## 2. Must NOT touch

- `v1/admin/auth.php`, login/logout CSRF
- `v1/includes/rbac.php` / `authz.php` permission model redesign
- P0: `membership-report.php`, `membership-intelligence.php`
- P1: `companies.php`, `company-detail.php`, `company-edit.php`, `owners-report.php`, `companies-intelligence.php`
- Production DB / SMTP / deploy

---

## 3. P2 work packages (exact order)

### P2-A — Dashboard parity (additive)
Split/extend `index.php` + `admin-stats.php` only:
- Dedicated Finance, Certificates, CPD, Wellness, Support sections
- Recent members / applications / payments / companies (LIMIT)
- Keep existing Membership + Company Overview
- Every KPI from real queries; unavailable ≠ fake zero when table missing

### P2-B — Navigation
Confirm `_hub.php` groups match required IA; add missing keys only if needed; no redesign.

### P2-C — Users / Roles
Review `users.php` / `user-detail.php` / `roles.php`: search, filter, pagination, RBAC boundaries. No new permission system.

### P2-D — Audit
Ensure login/logout/failed login, user/role/member/app/payment/cert/company/settings actions are labeled and filterable. No password logging.

### P2-E — Reports hub
Expand `reports.php` into domain report cards linking to existing filtered pages + CSV. No Excel/PDF.

### P2-F — Search/filter/pagination
Spot-fix large tables; fix gaps without rewriting working lists.

### P2-G…L — Module review
Members, Applications, Payments, Certificates, CPD, Wellness — strengthen only where gaps found; preserve public cert verify.

---

## 4. Database

**Preferred:** `DATABASE CHANGES: NONE`

STOP and report if slides/alerts/comms require new tables.

---

## 5. Testing gates

1. After each module: smoke test + security spot-check  
2. End: full P2 suite + **P1 regression must remain 30 PASS / 0 FAIL**

---

## 6. Known UNVERIFIED LIVE

- Exact UltraPro dashboard layout/columns  
- Live Excel/PDF export behaviour  
- Communication Centre / Slides / Alerts interiors  
- Exact live Owners Report column set (local uses real `owners` schema)

---

## Next action

1. Timestamped backup → `D:\Website\_backups\`  
2. Implement P2-A  
3. Continue P2-B…L incrementally  
