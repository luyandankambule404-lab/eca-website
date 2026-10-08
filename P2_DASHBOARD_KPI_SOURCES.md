# P2_DASHBOARD_KPI_SOURCES.md

**Date:** 2026-10-06  
**Scope:** P2-A Super Admin Dashboard only  
**Rule:** Real local queries only. Missing source → **DATA NOT AVAILABLE LOCALLY** (not a fake zero).

Company match KPIs come from `eca_ci_company_intelligence()` in `v1/includes/companies-intelligence.php` (P1). Dashboard does **not** reimplement soft-match.

---

## MEMBERS (`eca_portal_local.tbl_client` / `membership_years`)

| KPI | Table | Column / filter | Calculation |
| --- | --- | --- | --- |
| All clients | `tbl_client` | — | `COUNT(*)` |
| Numbered members | `tbl_client` | `MembershipNumber` not empty | `COUNT(*)` |
| Without membership # | derived | — | all clients − numbered |
| Active | `tbl_client` | `active` (standing) | group `active` |
| Pending | `tbl_client` | `active` | group `pending` |
| Suspended | `tbl_client` | `active` | sum suspended/suspend |
| Declined | `tbl_client` | `active` / `Status` | LIKE `%declin%` |
| Joining | `tbl_client` | `Status` | group `joining` |
| Renewal type | `tbl_client` | `Status` | group `renewal` |
| Expiring (90 days) | `membership_years` | `status=Active`, expiry in 90 days | `COUNT(*)` |
| Recent members | `tbl_client` | — | `ORDER BY client_id DESC LIMIT 8` |

---

## COMPANIES (via P1 `eca_ci_company_intelligence`)

| KPI | Source |
| --- | --- |
| Total companies | `companies` `COUNT(*)` → CI `companies_total` |
| Active companies | CI `companies_active` (`status` = active) |
| Matched to membership | CI `matched_to_member` (reg. no. = `MembershipNumber`) |
| Without membership match | `companies_total − matched_to_member` |
| Owner rows | CI `owners_total` |
| Recent companies | `companies ORDER BY id DESC LIMIT 8` |

---

## APPLICATIONS (`tbl_client` with `application_reference`)

| KPI | Filter | Calculation |
| --- | --- | --- |
| Total | reference present | `SUM` of application_status groups |
| Pending | Submitted + Under review + Additional information required | sum of those groups |
| Approved | `application_status` Approved | group |
| Rejected | `application_status` Rejected | group |
| Recent | reference present | `ORDER BY client_id DESC LIMIT 8` |

---

## PAYMENTS (`payments` — no amount column)

| KPI | Filter |
| --- | --- |
| Pending proof | `status = pending` |
| Verified | `status IN (approved, verified)` |
| Rejected | `status = rejected` |
| Recent | `ORDER BY id DESC LIMIT 8` |

Balances due: `eca_local.balances` status due/outstanding/unpaid/pending.

---

## CERTIFICATES (`membership_certificates`)

| KPI | Filter |
| --- | --- |
| Total | all rows |
| Active | `status` Active |
| Revoked | `status` Revoked |
| Expiring soon | Active + expiry within 90 days |

---

## CPD

| KPI | Table | Filter |
| --- | --- | --- |
| Applications | `cpd_applications` | `COUNT(*)` / groups |
| Pending | `cpd_applications.status` | pending |
| Approved | status approved/completed/accepted | group sum |
| Rejected | rejected/declined | group sum |
| Courses | `courses` | all |
| Open courses | `courses.status` open | |
| Completed courses | completed/closed/finished | group sum (0 if none in seed) |
| Points | `cpd_points_ledger` | `SUM(points)` |

---

## WELLNESS (local wellness helpers)

| KPI | Source |
| --- | --- |
| Upcoming events | `wellness_events` PUBLISHED + starts_at ≥ NOW() |
| Published events | `wellness_events` status = PUBLISHED |
| Announcements | active announcement SQL |
| Resources | PUBLISHED resources |
| Registrations | `wellness_event_registrations COUNT(*)` |
| Check-ins / hub published | `eca_wellness_hub_stats()` |

---

## SUPPORT

| KPI | Source |
| --- | --- |
| Open tickets/messages | `contact_messages` open statuses + portal `support_tickets` open |
| Recent messages | `contact_messages ORDER BY id DESC LIMIT 8` |

---

## AUDIT

| KPI | Source |
| --- | --- |
| Recent activity | `audit_logs` last 8 (noisy download actions excluded; security actions gated) |
