# COMPANIES_KPI_SOURCES.md

**Date:** 2026-10-06  
**Scope:** Local Companies & Owners intelligence only  
**Page:** `/admin/companies.php`, `/admin/owners-report.php`, Super Admin dashboard Company Overview

Every KPI below is backed by a real local query. Soft match means `companies.registration_number` equals `tbl_client.MembershipNumber` (case/trim normalized). There is no hard FK.

---

## Directory companies (`eca_local.companies`)

| KPI | Query / method |
| --- | --- |
| Total Companies | `SELECT COUNT(*) FROM companies` |
| Active Companies | `SELECT COUNT(*) FROM companies WHERE LOWER(TRIM(COALESCE(status,''))) = 'active'` |
| Inactive Companies | `SELECT COUNT(*) FROM companies WHERE LOWER(TRIM(COALESCE(status,''))) IN ('inactive','disabled')` |
| Suspended Companies | `SELECT COUNT(*) FROM companies WHERE LOWER(TRIM(COALESCE(status,''))) LIKE '%suspend%'` |
| With registration | `SELECT COUNT(*) FROM companies WHERE registration_number IS NOT NULL AND TRIM(registration_number) <> ''` |
| Missing registration | `SELECT COUNT(*) FROM companies WHERE registration_number IS NULL OR TRIM(registration_number) = ''` |
| By industry | `SELECT COALESCE(NULLIF(TRIM(industry),''),'(blank)'), COUNT(*) FROM companies GROUP BY …` |
| By status | `SELECT COALESCE(NULLIF(TRIM(status),''),'(blank)'), COUNT(*) FROM companies GROUP BY …` |

---

## Soft membership / certificate KPIs

| KPI | Query / method |
| --- | --- |
| Matched to member | Count of directory rows whose `registration_number` appears as a `tbl_client.MembershipNumber` |
| Companies with valid certificates | Distinct matched `MembershipNumber` with `membership_certificates.status = 'ACTIVE'` and expiry null/≥ today |
| Companies with expired certificates | Distinct matched memberships with certificate expiry `< CURDATE()` and not active |

---

## Owners (`eca_portal_local`)

| KPI | Query / method |
| --- | --- |
| Membership clients | `SELECT COUNT(*) FROM tbl_client` |
| Owner rows | `SELECT COUNT(*) FROM owners` |
| Clients with owners | `SELECT COUNT(DISTINCT clientid) FROM owners WHERE clientid IS NOT NULL AND clientid > 0` |

When owner rows = 0, UI shows **DATA NOT AVAILABLE LOCALLY** (not invented values).

---

## Dashboard Company Overview

| Dashboard label | Source field |
| --- | --- |
| Directory companies | `eca_admin_dashboard_stats().companies` |
| Active companies | `….companies_active` |
| Matched members | `….companies_matched_members` |
| Owner rows | `….owners_total` |
