# Super Admin live vs local audit

**Date:** 2026-09-30  
**Local project:** `D:\Website`  
**Live site inspected (public only):** `https://www.eca.co.sz/`  
**Live after-login Super Admin:** **BLOCKED** — no live credentials were provided; passwords were not guessed.

This audit does not modify the live website, live database, cPanel, or DNS. Production secrets were not copied or printed.

---

## 1. Backup and environment

| Item | Value |
| --- | --- |
| Timestamped backup | `D:\Website\_backups\super-admin-parity-20260930-150943` |
| Zip | `D:\Website\_backups\super-admin-parity-20260930-150943.zip` |
| PHP | 8.2.12 (XAMPP `C:\xampp\php\php.exe`) |
| Local site | `http://127.0.0.1:8765/` (was already listening) |
| Local databases | `eca_local`, `eca_portal_local` |
| Git | Repository present; `safe.directory` warning at root. Status recorded in backup note. Config was not changed. |
| Secrets | `.env` excluded from backup. Schema dump only (no user password hashes). |

### Database ownership (local)

**`eca_local` (Hub / Command Centre / public CMS / wellness / tickets / RBAC)**  
`users`, `roles`, `user_roles`, `audit_logs`, `settings`, `companies`, `contact_messages`, `news`, `tenders`, `events`, `resources`, `downloads`, education_* tables, wellness_* tables.

**`eca_portal_local` (membership portal + CPD)**  
`tbl_client`, `tbl_client_documents`, `membership_years`, `membership_certificates`, `payments`, `userss`, `user` (CPD), `courses`, `cpd_applications`, `cpd_points_ledger`, `course_attendance`, `course_resources`, `feedback`, `support_tickets`, `notifications`, member project tables.

---

## 2. Live Super Admin (unauthenticated)

### Public pages fetched

| URL | Result | Notes |
| --- | --- | --- |
| `/` and `/index.php` | 200 | Public homepage. Login control goes to **member** `client/index.php`. |
| `/admin/`, `/admin/login.php`, `/admin/index.php` | **404** | Live does **not** expose the local Command Centre Admin Hub. |
| `/cpd/` | 200 | CPD landing. |
| `/cpd/login.php` | 200 | Public CPD login: “CPD POINT SYSTEM / Training Portal”, Email, Password, Login, Create account. |
| `/cpd/admin/` | 404 | Directory listing not public. |
| `/cpd/admin/dashboard.php` | **302 → `login.php`** | This is the only live Super Admin **entry** discovered. Interior HTML is login-walled. |
| `/cpd/officer/dashboard.php` | 302 → `login.php` | Officer CPD area also login-walled. |
| `/client/login.php` | 404 | Live member login is `/client/index.php`, not this local path. |
| `/client/dashboard.php` | 302 → `index.php` | Member hub login-walled. |
| `/login.php` | 404 | |
| `/wellness/` | 404 | Local Wellness Hub path is not on live. |
| `/directory.php` | 200 | Public contractor directory. |
| `/verify.php` | 404 | Local certificate verify path is not on live. |
| `/robots.txt` | 404 | |

### Live Super Admin inventory after login

**BLOCKED.** Without a live Super Admin session, the following cannot be inventoried from the live site:

Dashboard homepage, sidebar, top nav, header, KPI cards, charts, graphs, tables, filters, search, pagination, exports, notifications, recent activity, audit logs, user/role/permission screens, members, applications, contractors, certificates, payments, CPD interior, wellness interior, tickets, reports, settings, profile, system configuration, Super Admin-only modules.

Do not invent live widgets. Local implementation used **local code and public live clues only**.

### Public live clues that map to local Super Admin work

- Live Super Admin appears to be the **CPD staff portal** (`SUPPERADMIN` at `/cpd/admin/dashboard.php`), not `/admin/index.php`.
- Public login on the live homepage is **member** login, not officer/Super Admin.
- CPD login is a separate Training Portal.
- Local Command Centre at `/admin/index.php` is a **local Hub** that does not exist as a public live URL.

---

## 3. Local Super Admin inventory

There are two local Super Admin surfaces:

1. **Admin Hub Command Centre** — `/admin/login.php` → `/admin/index.php` (role `super_admin` in `eca_local.users`).
2. **CPD Super Admin** — same officer login or CPD staff session (`SUPPERADMIN`) → `/cpd/admin/dashboard.php`.

Chrome rules already in local code and re-tested this pass:

- Label is **Super Admin**, not “CPD Super Admin”.
- Command Centre title on Admin Hub dashboard and CPD Super Admin dashboard.
- CPD sidebar order: **Dashboard → Applications → Courses**.

### Module matrix

Status key: **MATCHED** = present locally and exercised in this pass. **PARTIAL** = present locally, gaps noted. **MISSING** = not in local code. **BLOCKED** = live interior unknown.

| Module | Page / route | Purpose | Local widgets / actions | DB | Super Admin-only? | Live | Local | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Command Centre dashboard | `/admin/index.php` | Hub overview | KPI cards from local DB, portals, alerts, activity, trends, quick actions | `eca_local` + `eca_portal_local` | Super Admin sees extra portals + Super Admins count | BLOCKED | Full | MATCHED (local) / BLOCKED (live widgets) |
| Officer login | `/admin/login.php` | Staff sign-in | Email/password, CSRF, rate limit, audit | `users` / CPD `user` | No | Public live officer URL 404; CPD login 200 | Full | PARTIAL vs live paths |
| CPD login | `/cpd/login.php` | CPD Training Portal | Email/password | `eca_portal_local.user` | No | MATCHED (public form) | Full | MATCHED (public) |
| CPD Command Centre | `/cpd/admin/dashboard.php` | CPD Super Admin | KPIs, pie, upcoming courses, representatives, portal tiles | CPD tables | `SUPPERADMIN` | BLOCKED | Full | MATCHED (local) / BLOCKED (live) |
| Members | `/admin/members.php` | Portal members | Search, standing/type/year filters, pagination, Open, **CSV** | `tbl_client`, `membership_years` | No (`members.manage`) | BLOCKED | Full | MATCHED (local) |
| Member detail | `/admin/member-detail.php` | View/edit standing | Review, suspend, documents | `tbl_client` | No | BLOCKED | Full | MATCHED (local) |
| Applications | `/admin/applications.php` | Membership applications | Search, status filter, pagination, **CSV** | `tbl_client.application_*` | No | BLOCKED | Full | MATCHED (local) |
| Application detail | `/admin/application-detail.php` | Approve/reject/return | Status actions, notes, docs | portal | No | BLOCKED | Full | MATCHED (local) |
| Contractors | `/admin/companies.php` | Directory | Search, industry chips, suggest, edit, **CSV** (cap 500) | `eca_local.companies` | No | Public directory exists | Full | MATCHED (local) |
| Certificates | `/admin/certificates.php` | Issue/revoke | Search, generate, revoke, download, **CSV** | `membership_certificates` | No | Live `/verify.php` 404 | Full | MATCHED (local) |
| Documents | `/admin/documents.php` | Review uploads | Search, pending/reviewed, **CSV** (no storage paths) | `tbl_client_documents` | No | BLOCKED | Full | MATCHED (local) |
| Payments | `/admin/payments.php` | Proof of payment | Status filter, Open, **CSV** (counts, not SZL) | `payments` | No | BLOCKED | Full | MATCHED (local) |
| Balances | `/admin/balances.php` | Outstanding rows | Filter, **CSV** | `eca_local.balances` (optional) | No | BLOCKED | Table may be empty | PARTIAL |
| Receipts | `/admin/receipts.php` | CPD receipt logs | Search, **CSV** | `receipt_logs` if present | No | BLOCKED | Table missing locally; page no longer 500s | PARTIAL |
| CPD Hub bridge | `/admin/cpd.php` | Counts + portal links | Recent apps/courses | CPD tables | Portal open is Super Admin | BLOCKED | Full | MATCHED (local) |
| CPD Applications | `/cpd/admin/applications.php` | Course applications | Queue, approve/reject | `cpd_applications` | Staff | BLOCKED | Full | MATCHED (local) |
| CPD Courses | `/cpd/admin/courses.php` | Course admin | CRUD | `courses` | Staff | BLOCKED | Full | MATCHED (local) |
| CPD Payments | `/cpd/admin/payment.php` | CPD payments | List | portal | Staff | BLOCKED | Full | MATCHED (local) |
| Participants | `/cpd/admin/learners.php` | Learners | List | `user` | Staff | BLOCKED | Full | MATCHED (local) |
| Attendance | `/cpd/admin/course_students.php` | Attendance | List/export | attendance tables | Staff | BLOCKED | Full | MATCHED (local) |
| Resource library | `/cpd/admin/course_resources.php` | Files | Manage | `course_resources` | Staff | BLOCKED | Full | MATCHED (local) |
| Announcements | `/cpd/admin/announcement.php` | CPD announcements | Manage | announcements | Staff | BLOCKED | Full | MATCHED (local) |
| Feedback | `/cpd/admin/feedback.php` | Feedback | List | `feedback` | Staff | BLOCKED | Full | MATCHED (local) |
| Support | `/cpd/admin/support.php` | CPD support | Tickets | support | Staff | BLOCKED | Full | MATCHED (local) |
| CPD Users | `/cpd/admin/users.php` | CPD accounts | Add/update/roles | `user` | Super Admin | BLOCKED | Full | MATCHED (local) |
| CPD Reports | `/cpd/admin/cpd_reports.php` | CPD CSV reports | Date range, CSV | CPD tables | Super Admin | BLOCKED | Full | MATCHED (local) |
| Education | `/admin/education.php` | Public education CMS | Courses, programmes, articles, library | `education_*` | No (`education.manage`) | BLOCKED | Full | MATCHED (local) |
| Wellness | `/admin/wellness/` | Events, resources, announcements, reports | Stats, CRUD | `wellness_*` | Manage permission | Live `/wellness/` 404 | Full | MATCHED (local) / BLOCKED (live) |
| News / Tenders / Events / Resources | `/admin/news.php` etc. | Public content | CRUD | `eca_local` | `content.manage` | Public pages exist | Full | MATCHED (local) |
| Notifications | `/admin/notifications.php` | Admin alerts | Dismissible queue + member notes | stats + `notifications` | Super Admin sees security events | BLOCKED | Aligned with dashboard alerts | MATCHED (local) |
| Tickets | `/admin/tickets.php` | Contact form | Search, status, **CSV** | `contact_messages` | No | BLOCKED | Full | MATCHED (local) |
| Search | `/admin/search.php` | Hub search | Prepared queries, permission-scoped | both DBs | Super Admin-only entities hidden from Admin | BLOCKED | Full | MATCHED (local) |
| Users | `/admin/users.php` | Hub users | Create, filter, **CSV**, open detail | `users` | **Yes** (`users.*`) | BLOCKED | Full | MATCHED (local) |
| Roles | `/admin/roles.php` | Permissions | Floor + Super Admin-only locks | `roles` | **Yes** | BLOCKED | Full | MATCHED (local) |
| Reports | `/admin/reports.php` | Summaries | CSV summary + audit `report.export` | stats | Super Admins count is Super Admin | BLOCKED | Full | MATCHED (local) |
| Audit | `/admin/audit.php` | Logs | Filters, date range, **CSV**; security actions Super Admin-only | `audit_logs` | Security filter Super Admin | BLOCKED | Full | MATCHED (local) |
| Security Centre | `/admin/security.php` | Officer/security stats | Super Admins count | `users`, `audit_logs` | **Yes** | BLOCKED | Full | MATCHED (local) |
| Settings | `/admin/settings.php` | Org notes only | No SMTP/DB secrets | `settings` | **Yes** | BLOCKED | Full | MATCHED (local) |
| Profile | — | Account self-service | None in Admin Hub (CPD learner has account.php) | — | — | BLOCKED | No Hub profile page | MISSING locally / BLOCKED live |

RBAC is enforced server-side (`eca_admin_require` / `eca_can` / `require_role`). Hiding a nav item is not the only control.

---

## 4. Local data mapping (Command Centre KPIs)

Live KPI mapping is **BLOCKED**. Local dashboard values come from local queries (not hard-coded):

| Local KPI | Database | Source |
| --- | --- | --- |
| Total members | `eca_portal_local.tbl_client` | Numbered membership rows |
| Standing Active/Pending/Expired/Suspended | `tbl_client.active` + `membership_years` | Standing vs year state |
| Near expiry | `membership_years` | Next 90 days |
| Applications pending/approved/rejected | `tbl_client.application_status` | Rows with `application_reference` |
| Contractors | `eca_local.companies` | Directory |
| Certificates issued/active/expiring | `membership_certificates` | Status + `expiry_date` |
| Payments pending/verified | `payments.status` | Proof counts, not SZL |
| CPD applications/pending/points/courses | `cpd_applications`, `cpd_points_ledger`, `courses` | Portal CPD |
| Wellness events/resources/check-ins | `wellness_*` | `eca_local` |
| Open tickets | `contact_messages` + `support_tickets` | Combined |
| Super Admins / officers | `eca_local.users` + roles | Super Admin-only on dashboard |
| Recent activity | `audit_logs` | No passwords in meta |

---

## 5. RBAC (local)

| Role | Hub access | Super Admin-only pages |
| --- | --- | --- |
| `super_admin` | Full Hub + CPD portal preview | Users, Roles, Settings, Security, promote Super Admin |
| `admin` | Operations (members, apps, finance, content, reports, audit operational) | Denied: users, roles, settings, security, Super Admins count |
| Officers (membership/finance/content/training) | Subsets | Denied users/settings |
| Member | Member hub only | Cannot open `/admin/*` |
| Anonymous | Login only | Redirect/401 |

`super_admin` Hub role is **not** the CPD staff role `SUPPERADMIN`. Both can be linked in session for portal preview. Final Super Admin demote/deactivate is protected when a `status` column exists; local `users` currently has no `status` column.

---

## 6. What still needs a live Super Admin session

Provide live Super Admin access through a secure channel (do not paste the password in chat) to inventory:

1. Exact live sidebar labels and order after login.  
2. Every live KPI/chart/widget.  
3. Live table columns, bulk actions, Excel/PDF if any.  
4. Live Wellness Super Admin screens.  
5. Live user/role UI if different from CPD `user` admin.  
6. Live settings/secrets screens (inspect only; do not copy secrets into local `.env`).

Until then, live-after-login remains **BLOCKED**.
