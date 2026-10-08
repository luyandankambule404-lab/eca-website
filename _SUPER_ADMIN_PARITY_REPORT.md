# Super Admin parity report

**Date:** 2026-09-30  
**Local:** `D:\Website`  
**Live:** `https://www.eca.co.sz/` (public inspection only)  
**Live Super Admin after login:** **BLOCKED**

Nothing was changed on the live site, live database, cPanel, or DNS. Production passwords, SMTP, API secrets, cookies, and private keys were not copied into local config, git, or this report.

---

## 1. LIVE modules discovered (public)

| Discovery | Detail |
| --- | --- |
| Member login | Homepage Login → `client/index.php` |
| CPD Training Portal login | `/cpd/login.php` (Email, Password, Login, Create account) |
| Live Super Admin entry | `/cpd/admin/dashboard.php` redirects unauthenticated users to `login.php` |
| Live Officer CPD | `/cpd/officer/dashboard.php` also login-walled |
| Local Admin Hub paths | `/admin/login.php` and `/admin/index.php` are **404 on live** |
| Wellness Hub path | `/wellness/` **404 on live** |
| Certificate verify path | `/verify.php` **404 on live** |
| Public directory | `/directory.php` 200 |

Interior live Super Admin (sidebar, KPIs, charts, tables, exports, wellness admin, roles UI) was **not** seen.

---

## 2. LOCAL modules already matching (tested this pass)

Admin Hub Command Centre, Members, Applications, Contractors, Certificates, Documents, Payments, CPD bridge, Education, Wellness admin, News/Tenders/Events/Resources, Tickets, Search, Users, Roles, Reports, Audit, Security Centre, Settings, Officer vs Super Admin RBAC, CPD Super Admin dashboard/nav.

CPD hub identity: **Super Admin** (not “CPD Super Admin”). Nav: **Dashboard then Applications then Courses**. Command Centre title on Admin Hub and CPD Super Admin dashboard.

---

## 3. LOCAL modules partially matching

| Module | Gap |
| --- | --- |
| Live path parity | Live Super Admin is CPD-gated; local also has `/admin` Command Centre which live 404s |
| Outstanding balances | UI exists; local `balances` table may be empty |
| Receipt logs | UI exists; `receipt_logs` table is absent in `eca_portal_local` (page shows empty instead of 500) |
| Hub user `status` | Code supports ACTIVE/INACTIVE; local `users` has no `status` column |
| Hub profile page | No Super Admin self-profile in Admin Hub |
| Contractor CSV | Directory helper caps export at 500 rows |
| Live widgets | Cannot mark live KPI/chart parity MATCHED |

---

## 4. LOCAL modules newly implemented this pass

From **local evidence only** (permissions, dashboard alerts, existing CSV on reports, Command Centre chrome). No invented live widgets.

| Change | Why |
| --- | --- |
| Shared CSV export helper `v1/includes/admin-export.php` | `reports.export` existed but only reports.php exported |
| CSV on members, applications, payments, certificates, documents, tickets, audit, balances, receipts, users, companies | Same permission as reports; audit action `report.export`; no secrets in files |
| Reports CSV logs export; adds wellness check-ins and registrations | Local stats already on dashboard |
| Notifications alerts for members pending/near expiry, CPD pending, wellness events | Same alerts as Command Centre |
| CPD Super Admin Command Centre chrome (`admin-command.css`, Super Admin kicker, page titles) | Preserve Command Centre; Super Admin not CPD Super Admin |
| CPD inner page titles mapped (Applications, Courses, …) | Match Admin Hub inner-page titles |
| Removed duplicate Portals → Wellness nav (Wellness remains under Learning & wellness) | Duplicate link in `_hub.php` |
| Receipts page skips missing `receipt_logs` | Local schema evidence |
| `eca_is_final_active_super_admin` no longer requires `users.status` | Local schema evidence |
| ACL verify script skips missing user 29 fixture; does not select missing `status` | Local DB evidence |

---

## 5. Features still missing / BLOCKED

- **BLOCKED:** Any live Super Admin widget, chart, or menu item only visible after live login.  
- **MISSING locally:** Admin Hub profile/account page.  
- **NOT copied:** Production member/payment data, live secrets.  
- **NOT invented:** Live-only modules we could not see.

---

## 6. Database / schema changes

No production schema changes. No new local tables. No live dump.

Local code now tolerates:

- `users.status` absent  
- `receipt_logs` absent  

---

## 7. New files

| File | Role |
| --- | --- |
| `v1/includes/admin-export.php` | CSV export helper + audit |
| `_SUPER_ADMIN_LIVE_LOCAL_AUDIT.md` | This audit companion |
| `_SUPER_ADMIN_PARITY_REPORT.md` | This report |
| `_backups/super-admin-parity-20260930-150943/` | Timestamped Super Admin source + schema backup |

---

## 8. Modified files (this pass)

`v1/admin/_hub.php`, `members.php`, `applications.php`, `payments.php`, `certificates.php`, `documents.php`, `tickets.php`, `audit.php`, `balances.php`, `receipts.php`, `users.php`, `companies.php`, `reports.php`, `notifications.php`  
`v1/includes/audit.php`, `admin-stats.php`, `rbac.php`  
`v1/code.jquery.com/cpd/header.php`  
`v1/tools/access-control-verify.php`

Not committed.

---

## 9. RBAC changes

No new roles. Super Admin-only permissions unchanged. Admin still cannot open Users/Roles/Settings/Security.  
`report.export` still required for CSV; page permission still required first.  
Final Super Admin protection no longer fatals when `status` is missing.

---

## 10. Security changes

- Exports reuse existing authz; Admin cannot hit Users CSV (403).  
- Document CSV omits storage paths.  
- Settings still do not store SMTP/DB secrets.  
- Audit export excludes secrets (logs never stored passwords).  
- Live session cookies from unauthenticated redirects were not saved into the project or this report.

---

## 11. Test results

Local PHP: 8.2.12. Syntax: all touched PHP files `php -l` clean.

**Unauthorized**

- Unauthenticated `/admin/index.php` and `/admin/users.php` redirected/blocked.  
- Unauthenticated member dashboard redirected.

**Super Admin login (local Hub account)**

- Dashboard 200, **Command Centre**, **Super Admin**, not “CPD Super Admin”, `admin-command.css`.  
- Members CSV link present.  
- Users create controls present. Roles 200.  
- Reports CSV 200.  
- CPD dashboard 200; nav Dashboard → Applications → Courses.

**Admin login**

- Dashboard 200, no Super Admins count.  
- Users/Roles/Settings 403.  
- Users CSV 403.

**ACL script** `v1/tools/access-control-verify.php`: **Passed=102 Failed=0** (user 29 fixture skipped; temporary Super Admin used for HTTP).

**Not MATCHED against live interior** because live after-login is BLOCKED.

---

## 12. Remaining manual tasks

1. Supply live Super Admin credentials via a secure channel if interior live parity is required.  
2. Optional: add `users.status` locally if inactivation should persist.  
3. Optional: create `receipt_logs` locally if CPD receipt history should store rows.  
4. Do not deploy this local Hub `/admin` tree to live until a live session confirms the live information architecture.  
5. Do not commit unless asked.

---

## Comparison table

| Module | Live | Local before | Local after | Status |
| --- | --- | --- | --- | --- |
| Public homepage | Present | Present | Unchanged | MATCHED (public) |
| Member login | `client/index.php` | `/client/` | Unchanged | PARTIAL (path differs) |
| Officer / Admin Hub login | **404** `/admin/login.php` | `/admin/login.php` | Unchanged | BLOCKED vs live path |
| CPD login | `/cpd/login.php` | `/cpd/login.php` | Unchanged | MATCHED (public) |
| Live Super Admin interior | Login-walled | n/a | n/a | **BLOCKED** |
| Command Centre (Hub) | Not on live public `/admin` | Present | Preserved + CSV/alerts | MATCHED (local) / BLOCKED (live) |
| CPD Super Admin dashboard | BLOCKED | Present | Command Centre chrome; Super Admin label; Dashboard/Applications/Courses | MATCHED (local) / BLOCKED (live) |
| Members | BLOCKED | Present | + CSV | MATCHED (local) |
| Applications | BLOCKED | Present | + CSV | MATCHED (local) |
| Contractors | Public directory | Present | + CSV (500 cap) | MATCHED (local) |
| Certificates | Live verify 404 | Present | + CSV | MATCHED (local) |
| Documents | BLOCKED | Present | + CSV | MATCHED (local) |
| Payments | BLOCKED | Present | + CSV | MATCHED (local) |
| Balances | BLOCKED | Present | + CSV | PARTIAL |
| Receipts | BLOCKED | Present (could 500) | Empty-safe + CSV | PARTIAL |
| CPD modules | BLOCKED | Present | Chrome/nav only | MATCHED (local) / BLOCKED (live) |
| Wellness | Live `/wellness/` 404 | Present | Notifications include events | MATCHED (local) / BLOCKED (live) |
| Tickets | BLOCKED | Present | + CSV | MATCHED (local) |
| Users / Roles / Settings / Security | BLOCKED | Present (SA-only) | + Users CSV | MATCHED (local) / BLOCKED (live) |
| Reports | BLOCKED | CSV summary | + audit log, wellness rows | MATCHED (local) |
| Audit | BLOCKED | Present | + CSV | MATCHED (local) |
| Notifications | BLOCKED | Partial alerts | Aligned with dashboard | MATCHED (local) |
| Hub profile | BLOCKED | Absent | Absent | MISSING / BLOCKED |
| Charts on live dashboard | BLOCKED | CPD pie/local meters | Unchanged (no invented live charts) | BLOCKED |

MATCHED in this table means **tested locally**, not “pixel-matched to live after login”.

---

## Safety confirmation

- No live files modified.  
- No live database modified.  
- No cPanel changes.  
- No DNS changes.  
- No production credentials copied into `.env` or reports.  
- No production passwords printed.  
- Existing local Hub, Applications, Members, Contractors, Certificates, Payments, CPD, Wellness, Tickets, Audit, and RBAC remain in place and were smoke-tested.  
- Git commit was **not** created.
