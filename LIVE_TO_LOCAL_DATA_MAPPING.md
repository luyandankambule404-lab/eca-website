# LIVE → LOCAL Data Mapping

**Date:** 2026-10-06  
**Rule:** Local databases only (`eca_local`, `eca_portal_local`). Production is read-only reference via HTTP, never written.

Format:

`Live functionality → Local page → Local table → Local fields → Permission → Test case`

---

## A. Membership core

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| Members list `/demo/members.php` | `/admin/members.php` | `eca_portal_local.tbl_client` (+ `membership_years`) | `client_id`, `TradingName`, `EmailAddress`, `Cellphone`, `Region`, `Clasification`, `DateOfRegistration`, `Status`, `active`, `MembershipNumber` | `members.manage` | List/search/filter/paginate members |
| Member detail `client.php` | `/admin/member-detail.php` | `tbl_client`, `tbl_client_documents`, `membership_years`, `payments`, `membership_certificates` | standing `active`, type `Status`, docs, years | `members.manage` | Open member; suspend/approve |
| Edit client `edit_client.php` | `/admin/member-detail.php` | `tbl_client` | contact + standing fields | `members.manage` | Update standing with audit |
| Delete client `delete_client.php` | **no hard-delete page** | — | — | — | Confirm local refuses destructive delete or soft-status only |
| Balingani `/demo/balingani.php` | public `/balingani-directory.php` + Admin gap | `tbl_client` filtered subset | classification/gender/wing fields if present | `members.manage` / public | Filter Balingani members |
| Companies & Owners report | **MISSING UI** | `owners`, `tbl_client` | owner name/gender/shares/citizen | `reports.view` / `companies.manage` | Build owners report from local `owners` |
| Membership years / renewals in reports | `/admin/reports.php`, member detail | `membership_years` | `type`, `status`, `year`, `expiry_date`, `client_id` | `reports.view` | Renewal pending count |
| Public renewal | `/renewal.php` | `membership_years`, `tbl_client`, `payments` | renewal type/status | public + later admin | Submit renewal locally |

## B. Applications

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| `application.php` (gated) | `/admin/applications.php`, `/admin/application-detail.php` | `tbl_client` | `application_reference`, `application_status` | `applications.manage` | Approve/reject/return |
| Pending applications report section | `/admin/reports.php` (+ gap for UltraPro) | `tbl_client` | status groups | `reports.view` | Pending KPI matches query |
| Application notes/docs | application detail | `membership_application_notes`, `tbl_client_documents`, `application_doc_requests` | notes, file paths/storage_key | `applications.manage` / `documents.manage` | Attach/review docs |

## C. Finance

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| **NOT VERIFIED FROM LIVE** payment UI | `/admin/payments.php`, `payment-detail.php` | `payments` | `status`, `proof_file`, `payment_date`, `payment_year`, `user_id` | `payments.manage` | Verify/reject proof |
| Balances | `/admin/balances.php` | optional balances table / derived | due counts | `payments.manage` | List outstanding |
| Receipts | `/admin/receipts.php` | receipt logs if present | — | `payments.manage` | Page does not 500 |

## D. Certificates / documents

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| **NOT VERIFIED FROM LIVE** | `/admin/certificates.php` | `membership_certificates` | `certificate_number`, `status`, `qr_token`, dates | `certificates.manage` | Issue/revoke/download |
| Verify | `/verify.php` | `membership_certificates` | `qr_token` / number | public | Verify active cert |
| Documents | `/admin/documents.php` | `tbl_client_documents` | `reviewed_at`, `storage_key` | `documents.manage` | Review document |

## E. CPD

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| `/cpd/admin/*` + `cpd_auto_login.php` | `/cpd/admin/*`, `/admin/cpd.php` | `user`, `courses`, `cpd_applications`, `course_attendance`, `cpd_points_ledger`, `course_resources` | role `SUPPERADMIN`, statuses | CPD staff / hub `cpd.view` | SSO from Super Admin without re-login |
| CPD reports | `/cpd/admin/cpd_reports.php` | CPD tables | date range | Super Admin CPD | CSV export |

## F. Wellness / education / content

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| Live wellness **NOT FOUND** publicly | `/admin/wellness/*` | `wellness_*` | events/resources/announcements | `wellness.manage` | CRUD event |
| News CMS `/demo/news.php` | `/admin/news.php` | `eca_local.news` | title/body/status | `content.manage` | Create/edit news |
| Slides `/demo/slides.php` | **MISSING** | — | — | — | Decide schema if required |
| Tenders/events/resources | `/admin/tenders.php` etc. | `tenders`, `events`, `resources` | — | `content.manage` | CRUD |

## G. Support / communications

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| `support_center.php` gated | `/admin/tickets.php` | `support_tickets`, `contact_messages` | status, reply | `tickets.manage` | Open/resolve ticket |
| `email_center.php` gated | mailer + logs | `system_email_logs` + `_private/mail-log` | — | settings/mail | Dev mode no prod SMTP |
| `communication_centre.php` | **MISSING** | — | — | — | Gap |

## H. Users / security

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| `/demo/users.php` | `/admin/users.php` | `eca_local.users`, `user_roles`, `roles` | email, role, **hashed** password | `users.view/manage` | Create user; never show password |
| Live roles Admin/Officer/SuperAdmin | `/admin/roles.php` | `roles` (+ optional `role_permissions`) | slug permissions | `roles.manage` Super Admin | Change non-floor perms |
| Audit (live unknown) | `/admin/audit.php` | `audit_logs` | action, actor, meta | `audit.view` | Login/logout/member update logged |
| Security centre | `/admin/security.php` | audit + users | — | `security.view` | Super Admin only |

## I. Reports (major gap)

| Live | Local page | Local table(s) | Key fields | Permission | Test case |
| --- | --- | --- | --- | --- | --- |
| UltraPro `/demo/reports.php` suite | **MISSING dedicated intelligence UI**; foundation `/admin/reports.php` | `tbl_client`, `membership_years` | Status/active/Region/Clasification/Enterprise/dates | `reports.view` / `reports.export` | Build dynamic local report from local DB only |
| PDF `export_report_pdf.php` | **MISSING** | same | date range | `reports.export` | Generate PDF from local data |
| Excel `export_report_excel.php` | **MISSING** | same | date range | `reports.export` | Generate XLSX/CSV from local data |
| `members25.php` (presumed year report) | **MISSING** | `membership_years.year` | year 2025/2026 | `reports.view` | Year filter report |

---

## Local schema inventory (audit snapshot)

### `eca_local` tables
`audit_logs`, `companies`, `companies1`, `contact_messages`, `downloads`, `education_*`, `events`, `event_registrations`, `likes`, `news`, `resources`, `roles`, `settings`, `tenders`, `users`, `user_roles`, `wellness_*`

### `eca_portal_local` tables
`announcements`, `application_doc_requests`, `course_*`, `courses`, `cpd_applications`, `cpd_points_ledger`, `downloads`, `feedback`, `likes`, `member_*`, `membership_application_notes`, `membership_certificates`, `membership_years`, `news`, `notifications`, `owners`, `payments`, `resources`, `support_tickets`, `system_email_logs`, `tbl_client`, `tbl_client_documents`, `user`, `userss`, `wallet_transactions`

Row counts at audit time were mostly seed/small (many zeros). Dashboard metrics must remain query-driven so they stay correct as data grows.

---

## Field spelling caveats (do not “fix” blindly)

- Live/local client table uses `Clasification` (missing ‘s’) — preserve for compatibility.
- Live membership KPIs use Renewal / Joining language; local standing uses `active` plus `Status` type (`Renewal`, etc.). Mapping must be explicit in report SQL.
