# MEMBERSHIP_INTELLIGENCE_KPI_SOURCES.md

**Date:** 2026-10-06  
**Database:** `eca_portal_local` only  
**Page:** `/admin/membership-report.php`

Every KPI is query-driven. No hard-coded totals.

## Business meaning

| Label | Meaning |
| --- | --- |
| Total clients | Every row in `tbl_client` (includes applicants without membership numbers) |
| With membership # | Clients that have a non-empty `MembershipNumber` (historical dashboard “Total members”) |
| Without membership # | Client rows still in the pipeline without a number |
| Active / Pending / Suspended / Declined / In progress | From `tbl_client.active` (standing). Declined also checks `Status` |
| Joining / Renewal / Type Active | From `tbl_client.Status` (membership type), **not** the same as standing |
| Year rows / current / expiring / expired / pending renewals | From `membership_years` |
| 2025/2026 clients | Distinct `client_id` with `membership_years.year` in 2025 or 2026 (or `2025/2026`) |
| Regions | Distinct non-empty `Region` values |

**Why live-style “Total Membership” ≠ local numbered members:** live UltraPro KPIs mixed Renewal/Joining language over the full client set. Local keeps both counts explicit so they are auditable.

## SQL (also shown on the report page)

See `eca_mi_intelligence()` in `v1/includes/membership-intelligence.php` and the on-page “KPI SQL sources” disclosure.
