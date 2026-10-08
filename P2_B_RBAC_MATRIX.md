# P2-B RBAC MATRIX (verified local)

**Date:** 2026-10-06  
**Source:** Code gates + HTTP tests. Legend: **Y** = allowed, **N** = denied/redirect, **—** = not applicable / out of Hub session.

Permissions are Hub RBAC keys. CPD uses a separate role space (`SUPPERADMIN` / `ADMIN` / `OFFICER`).

| Route | Perm gate | Super Admin | Admin | CPD Admin\* | Member | Anonymous |
| --- | --- | --- | --- | --- | --- | --- |
| `/admin/index.php` | `hub.access` | Y | Y | N† | N | N |
| `/admin/members.php` | `members.manage` | Y | Y | N | N | N |
| `/admin/companies.php` | `companies.manage` | Y | Y | N | N | N |
| `/admin/applications.php` | `applications.manage` | Y | Y | N | N | N |
| `/admin/payments.php` | `payments.manage` | Y | Y | N | N | N |
| `/admin/certificates.php` | `certificates.manage` | Y | Y | N | N | N |
| `/admin/cpd.php` | `cpd.view` | Y | Y | N† | N | N |
| `/admin/wellness/` | `wellness.manage` | Y | Y | N | N | N |
| `/admin/tickets.php` | `tickets.manage` | Y | Y | N | N | N |
| `/admin/audit.php` | `audit.view` | Y | Y | N | N | N |
| `/admin/reports.php` | `reports.view` | Y | Y | N | N | N |
| `/admin/users.php` | `users.view` (**SA-only**) | Y | N | N | N | N |
| `/admin/roles.php` | `roles.view` (**SA-only**) | Y | N | N | N | N |
| `/admin/settings.php` | `settings.view` (**SA-only**) | Y | N | N | N | N |
| `/admin/security.php` | `security.view` (**SA-only**) | Y | N | N | N | N |
| `/admin/db-mode.php` | `security.view` (**SA-only**, P2-B) | Y | N | N | N | N |
| `/cpd/admin/*` | CPD `require_role` | via portal SSO | N | Y (role-dependent) | N | N |

\* CPD Admin = `cpd.admin@eca.co.sz` (CPD `ADMIN` role) signing in via `/admin/login.php` → lands in CPD, no Hub `eca_admin` session.  
† Unless also a Hub user with `hub.access`, or CPD `SUPPERADMIN` SSO (local preview) synthesizes Hub super_admin.

## Super Admin–only permission keys

`users.*`, `roles.*`, `permissions.*`, `settings.*`, `security.*`, `hub.settings` — stripped for non–`super_admin` even if DB is wrong.

## P2-B fixes applied

1. Dashboard/search: `/admin/wellness/*` links require `wellness.manage` (not `wellness.view` alone).  
2. `db-mode.php`: gate raised from `hub.access` → `security.view`.  
3. `document-view.php`: route-level check for documents/applications/members manage|review before object ACL.
