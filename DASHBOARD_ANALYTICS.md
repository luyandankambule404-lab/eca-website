# DASHBOARD_ANALYTICS.md

**Scope:** Local Super Admin / Officer Hub dashboard (`/admin/index.php`)  
**Date:** 2026-10-06  
**Environment:** `http://127.0.0.1:8765` only — not production.

---

## Overview

The dashboard presents:

1. **Executive KPIs** — compact cards (Members, Companies, Applications, Payments, Certificates, CPD, Wellness, Support)
2. **Executive analytics** — Chart.js charts fed from existing KPI aggregates
3. **Detailed module sections** — unchanged calculation logic (P2-A)
4. **Recent tables** — server-side pagination (`*_page` query keys)
5. **Recent audit activity** — paginated list (not a chart)

KPI **calculations** live in `v1/includes/admin-stats.php` (`eca_admin_dashboard_stats`). Charts are a presentation layer via `eca_admin_chart_payload()` — they do not invent or re-implement business rules.

---

## Chart library

- **Chart.js 4.4.1** (CDN), already used by CPD admin reports.
- No second charting framework was added.
- Without JavaScript: KPI cards, legends (numeric lists), CSS trend bars, and tables still work; canvases are empty.

---

## KPI sources (unchanged)

| Card | Primary source | Notes |
| --- | --- | --- |
| Members | `tbl_client` numbered members | Standing / type / year KPIs unchanged |
| Companies | P1 `eca_ci_company_intelligence` | Soft-match not duplicated |
| Applications | `tbl_client` with `application_reference` | Status groups unchanged |
| Payments | `payments` status groups | **Counts only** (no amount column) |
| Certificates | `membership_certificates` | Active / Revoked / Expiring |
| CPD | `cpd_applications`, courses, points ledger | Existing sums |
| Wellness | `eca_wellness_admin_stats` / hub stats | Existing |
| Support | `tickets_open` KPI | contact_messages + support_tickets open |

Missing local sources still show **DATA NOT AVAILABLE LOCALLY** (or chart empty states) — never fake zeros for unavailable systems.

---

## Charts

| Chart key | Type | Data | Empty state |
| --- | --- | --- | --- |
| `membership_trend` | Line | `trends_members` (monthly `created_at` last 8 months) | Historical membership trend not available locally |
| `applications` | Doughnut | Submitted / Under review / Returned / Approved / Rejected | No application data available locally. |
| `payments` | Doughnut | Pending / Verified / Rejected **counts** | No payment data available locally. |
| `certificates` | Bar | Active / Expiring (90d) / Revoked | No certificate data available locally. |
| `cpd` | Bar | Approved / Pending / Rejected | No CPD application data available locally. |
| `companies` | Doughnut | `company_statuses` GROUP BY, else CI active/matched/unmatched | Companies DNA / empty |
| `wellness` | Bar | Published / Upcoming / Registrations / Announcements / Resources | No wellness data available locally. |
| `support` | Bar | Open (combined KPI) / Closed messages / Closed tickets | No support data available locally. |

### Aggregation notes

- Membership trend uses existing `DATE_FORMAT(created_at,'%Y-%m')` counts — if every month is 0, the chart shows the historical empty message (no invented history).
- Certificate **Expiring** is a subset of **Active**; the bar chart shows both deliberately. Revoked are never included in Active.
- Payment chart never sums money — local schema has no reliable amount field for proofs.
- Support **Open** reuses `tickets_open` (does not double-count opens). Closed message/ticket counts come from separate status groups.

---

## Permissions (RBAC)

Chart series are only included in the page JSON when the viewer has the matching capability:

| Chart | Requires |
| --- | --- |
| membership_trend | `members.manage` |
| applications | `applications.manage` |
| payments | `payments.manage` |
| certificates | `certificates.manage` |
| cpd | `cpd.view` (or portal open elevation as today) |
| companies | `companies.manage` |
| wellness | `wellness.manage` or `wellness.view` |
| support | `tickets.manage` |

Unauthorized users still cannot open `/admin/index.php` without `hub.access`. Chart payloads are not emitted for modules the role cannot see.

---

## Pagination

Independent GET keys (10/page, server-side LIMIT/OFFSET):

- `members_page`, `companies_page`, `applications_page`, `payments_page`, `messages_page`, `audit_page`

Helpers: `v1/includes/pagination.php` (`eca_paged_query_named`, `eca_render_named_request_pager`).

---

## Responsive behaviour

Scoped in `v1/css/admin-command.css` (`body.hub-admin.is-admin-dash`):

| Width | KPI grid | Charts |
| --- | --- | --- |
| ≥1200px | 6 columns | 2-column rows |
| ~tablet | 3–4 columns | 2 → 1 column |
| ≤700px | 2 columns | 1 column |

Tables use horizontal scroll in `.eca-table-panel` when needed.

---

## Known local-data limitations

- Sparse `created_at` history → membership trend often empty locally.
- Payments table may be empty → payment chart empty state; KPIs remain genuine zeros when the table exists.
- Support closed counts depend on status vocabulary in local rows.
- Chart.js CDN requires network for interactive charts on first load.

---

## Files

- `v1/includes/admin-stats.php` — aggregates + `eca_admin_chart_payload`
- `v1/admin/index.php` — executive KPIs, charts UI, Chart.js init
- `v1/css/admin-command.css` — compact KPI + chart layout
- `v1/admin/_hub.php` — CSS cache bust
- `DASHBOARD_ANALYTICS.md` — this document
