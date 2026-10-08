# LIVE Admin Functionality Checklist

**Date:** 2026-10-06  
**Scope:** Discovery + local comparison only (no major implementation in this pass)  
**Legend:** `[x]` done in this audit pass · `[~]` partial · `[ ]` not done / blocked · `N/A` not applicable yet

Checklist columns (per feature):

1. Live functionality identified  
2. Local equivalent exists  
3. Local functionality works (smoke / prior tests)  
4. Database mapping completed  
5. Permissions implemented  
6. Validation implemented  
7. Audit logging implemented  
8. Tested (this programme)

---

## A. Dashboard

| Feature | 1 Live | 2 Local exists | 3 Works | 4 DB map | 5 Perms | 6 Valid | 7 Audit | 8 Tested | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Live UltraPro post-login dashboard | [~] gated | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | **LIVE ACCESS REQUIRED** |
| Local Command Centre `/admin/index.php` | N/A (local) | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local-only path; live `/admin` 404 |
| Live login gate `/demo/dashboard.php` | [x] | [~] | [x] | [x] | [x] | [x] | [x] | [~] | Local uses `/admin/login.php` |
| KPI: total / renewal / joining / regions | [x] | [~] | [~] | [~] | [x] | N/A | N/A | [ ] | Local KPIs differ (active/pending/suspended/apps/payments/CPD/wellness) |
| AI Assist chrome | [x] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** (product decision) |

## B. Membership / Members

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Members list | [x] | [x] | [x] | [x] | [x] | [~] | [~] | [~] | Local `/admin/members.php` |
| Search / filter / pagination | [~] | [x] | [x] | [x] | [x] | [x] | N/A | [~] | Live dumps huge table; local paginates |
| Member detail | [~] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Live `client.php` not fully verified |
| Edit member | [~] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Live `edit_client.php` |
| Delete member | [x] linked | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | Local: no hard-delete UI (safer) — **LOCAL DIFFERENCE** |
| Standing / status changes | [~] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local approve/suspend/reactivate |
| members25 / 2025-2026 report UI | [~] gated | [ ] | [ ] | [~] | [ ] | [ ] | [ ] | [ ] | **LIVE ACCESS REQUIRED** / **MISSING LOCALLY** as dedicated page |
| Balingani admin list | [x] | [~] | [~] | [~] | [~] | [~] | [ ] | [ ] | Local public directory exists; Admin mirror partial |

## C. Contractors / Companies / Owners

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Contractor directory admin | [~] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local `/admin/companies.php` |
| Companies & Owners report | [x] | [ ] | [ ] | [~] | [ ] | [ ] | [ ] | [ ] | Live `company_owners_report.php`; local `owners` table exists — **MISSING LOCALLY** UI |
| Public directory impact | [x] | [x] | [x] | [x] | [x] | [x] | N/A | [~] | Must preserve visibility rules |

## D. Applications

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Application queue | [~] gated | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Live `application.php` gated |
| Approve / reject / return | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local `application-detail.php` |
| ECA-APP-YYYY-NNNN refs | [ ] | [x] | [x] | [x] | N/A | [x] | [~] | [~] | Preserve format |
| Pending apps in reports | [x] | [~] | [~] | [x] | [x] | N/A | N/A | [ ] | Live reports include pending applications section |

## E. Renewals

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Renewal KPI / lists in reports | [x] | [~] | [~] | [x] | [x] | N/A | N/A | [ ] | Local `membership_years` + `renewals_pending` KPI; no dedicated Renewals nav page |
| Dedicated renewals module | [~] | [ ] | [ ] | [~] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** as first-class nav |
| Public renewal form | N/A | [x] | [x] | [x] | N/A | [x] | [~] | [~] | `/renewal.php` |

## F. Payments

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Payment proof admin | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | **NOT VERIFIED FROM LIVE** interior |
| Verify / reject payment | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local `/admin/payments.php` |
| Balances / receipts | [ ] | [~] | [~] | [~] | [x] | [~] | [~] | [~] | Partial local |

## G. Certificates

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Certificate admin | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | **NOT VERIFIED FROM LIVE** |
| Issue / revoke / download | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local certificates module |
| QR / verify.php | [ ] | [x] | [x] | [x] | N/A | [x] | N/A | [~] | Live `/verify.php` previously 404 — **LOCAL DIFFERENCE** |

## H. Documents

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Document review | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | **NOT VERIFIED FROM LIVE** |

## I. CPD

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| CPD Super Admin portal | [~] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Live via `/cpd/` + `cpd_auto_login.php` |
| Courses / apps / attendance / reports | [~] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local CPD admin suite |
| Auto-login bridge from demo | [x] | [~] | [~] | [~] | [~] | [~] | [~] | [~] | Local SSO from Super Admin (recent) |

## J. Wellness

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Wellness admin | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Live `/wellness/` previously 404 — local extension |

## K. Support / Contact / Comms

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Support centre | [~] gated | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local `/admin/tickets.php` |
| Email centre | [~] gated | [~] | [~] | [~] | [~] | [~] | [~] | [ ] | Local mailer + `_private/mail-log` |
| Communication centre | [x] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** |
| Contact messages | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Hub `contact_messages` |

## L. Reports / Exports

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| UltraPro membership intelligence reports | [x] | [ ] | [ ] | [~] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** (major gap) |
| PDF export | [x] | [ ] | [ ] | [~] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** |
| Excel export | [x] | [ ] | [ ] | [~] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** |
| Local CSV summary reports | N/A | [x] | [x] | [x] | [x] | N/A | [~] | [~] | `/admin/reports.php` |
| Module CSV exports (members/apps/etc.) | [~] | [x] | [x] | [x] | [x] | N/A | [~] | [~] | Local export helper |

## M. Users / Roles / Permissions

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Manage users | [x] | [x] | [x] | [x] | [x] | [x] | [x] | [~] | Live shows Password column — **do not copy** |
| Roles Admin/Officer/SuperAdmin | [x] | [x] | [x] | [x] | [x] | [x] | [x] | [~] | Local RBAC richer |
| Permissions matrix UI | [ ] | [x] | [x] | [x] | [x] | [x] | [x] | [~] | Local `/admin/roles.php` |

## N. Audit / Security / Settings

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Audit logs | [ ] | [x] | [x] | [x] | [x] | N/A | [x] | [~] | **NOT VERIFIED FROM LIVE** |
| Security centre | [ ] | [x] | [x] | [x] | [x] | N/A | [x] | [~] | Local-only |
| Settings | [ ] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | |

## O. Content / Alerts / AI

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| News CMS | [x] | [x] | [x] | [x] | [x] | [x] | [~] | [~] | Local `/admin/news.php` |
| Slides CMS | [x] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** |
| Economic / industry / regulation alerts | [x] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** |
| AI Business Assistant | [x] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | [ ] | **MISSING LOCALLY** (optional) |

## P. AuthN / AuthZ / Search / Pagination

| Feature | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Staff authentication | [x] | [x] | [x] | [x] | [x] | [x] | [x] | [~] | |
| Server-side RBAC | [~] | [x] | [x] | [x] | [x] | [x] | [x] | [~] | Live open pages suggest weak gating — local must stay strict |
| Hub search | [ ] | [x] | [x] | [x] | [x] | [x] | N/A | [~] | |
| Table pagination | [~] | [x] | [x] | [x] | N/A | N/A | N/A | [~] | Live members page appears unpaginated dump |

---

## Immediate blockers for “complete live parity”

1. **LIVE ACCESS REQUIRED** for post-login dashboard, `application.php`, `members25.php`, support/email interiors.  
2. UltraPro **Membership Intelligence** report suite + PDF/Excel — largest functional gap.  
3. Companies & Owners report UI.  
4. Dedicated Renewals admin nav.  
5. Communication centre / slides / alerts / AI — product decisions needed (reproduce vs defer).
