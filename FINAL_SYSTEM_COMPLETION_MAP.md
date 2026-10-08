# FINAL SYSTEM COMPLETION MAP

**Date:** 2026-10-06  
**Environment:** LOCAL ONLY (`http://127.0.0.1:8765`)  
**Databases:** `eca_local` (Hub) + `eca_portal_local` (membership/apps/payments/certs)  
**Schema flags:** `role_permissions` = NO · `users.status` = NO · **No schema changes applied in this sprint unless explicitly approved**

**Protected baselines:** P1 30 · P2-A 18 · P2-B 46 · P2-C 23 (must remain PASS)

---

## Legend

| State | Meaning |
| --- | --- |
| COMPLETE | Locally implemented and verified |
| PARTIAL | Works with known gaps |
| BROKEN | Fails locally |
| MISSING | Not present in local codebase |
| DEFERRED | Intentionally not done (schema/Excel/PDF/production) |
| NOT VERIFIABLE LOCALLY | Needs production data, SMTP, or live systems |

---

## Master feature table

| Area | Feature | Current State | Missing | Priority | Action | Database Change? | Test |
| ---- | ------- | ------------- | ------- | -------- | ------ | ---------------- | ---- |
| Auth | Officer login/logout/CSRF | COMPLETE | — | P1 | Preserve | No | Auth suite |
| Auth | Failed login audit | COMPLETE | — | P1 | Preserve | No | P2-D |
| Auth | Member portal login | COMPLETE | — | P1 | Preserve | No | Auth suite |
| Auth | CPD staff login (SSO bridge) | COMPLETE | — | P1 | Preserve | No | P2-B |
| Auth | Session isolation Hub↔Member↔CPD | COMPLETE | — | P1 | Preserve | No | P2-B |
| Auth | Rate limiting on logins | COMPLETE | Local rate files fill under heavy tests | P2 | Clear `_private/rate-limits` before suites | No | Final suite |
| RBAC | Hub role matrix | COMPLETE | — | P1 | Preserve; no redesign | No | P2-B |
| RBAC | Super Admin–only Users/Roles/Security | COMPLETE | — | P1 | Preserve | No | P2-C |
| RBAC | Persistent role_permissions | DEFERRED | Table absent; Save disabled | P2 | Do **not** apply 007 without approval | **Would need `role_permissions`** | Document |
| RBAC | users.status activate/deactivate | DEFERRED | Column absent | P2 | Do **not** add column | **Would need `users.status`** | Document |
| Dashboard | Membership/Company KPIs | COMPLETE | — | P1 | Preserve P2-A | No | P2-A |
| Dashboard | Apps/Payments/Certs/CPD/Wellness/Support | COMPLETE | Sparse portal seed data | P1 | Preserve; empty ≠ fake | No | P2-A |
| Dashboard | DATA NOT AVAILABLE LOCALLY | COMPLETE | — | P1 | Preserve | No | P2-A |
| Nav | Hub navigation groups | COMPLETE | — | P2 | Preserve P2-B | No | P2-B |
| Users | List/search/filter/pagination/create/edit | COMPLETE | — | P2 | Preserve P2-C | No | P2-C |
| Users | Password reset (no hash display) | COMPLETE | — | P2 | Preserve | No | P2-C |
| Roles | Role catalogue + defaults note | COMPLETE | Persistence deferred | P2 | Keep Save disabled without table | No | P2-C |
| Audit | audit_logs + eca_audit() | COMPLETE | — | P1 | Reuse only; no second system | No | P2-D |
| Audit | Login/logout/fail/CSRF/access.denied | COMPLETE | Strengthened in P2-D | P1 | Preserve | No | P2-D 43 |
| Audit | UI search/filter/module/detail/pagination | COMPLETE | — | P2 | Preserve | No | P2-D |
| Audit | Admin vs Super Admin security events | COMPLETE | — | P1 | Preserve | No | P2-D |
| Companies | List/detail/edit/owners/CSV/print | COMPLETE | — | P1 | **Do not alter soft-match** | No | P1 30 |
| Companies | Soft-match intelligence | COMPLETE | — | P1 | Preserve `companies-intelligence.php` | No | P1 |
| Members | List/detail/status/notes | COMPLETE | Thin seed (2 portal clients) | P1 | Preserve workflows | No | Final |
| Applications | Admin review/approve/reject/notes/docs | COMPLETE | Thin seed | P1 | Preserve + E2E test | No | Final |
| Applications | Public submit (application.php etc.) | COMPLETE | — | P1 | Preserve CSRF/rate limits | No | Final |
| Applications | Tracking `/track.php` | COMPLETE | Polish only if gaps | P1 | Verify timeline/copy | No | Final |
| Applications | application-update resubmit | COMPLETE | — | P1 | Preserve | No | Final |
| Payments | Admin list/detail approve/reject | COMPLETE | **0 payment rows** locally | P1 | Workflow code OK; seed optional | No | Final (BLOCKED data) |
| Payments | Member proof upload | PARTIAL | Needs member session + empty table | P1 | Verify client payments page | No | Final |
| Payments | Receipts (CPD receipt_logs) | PARTIAL | Depends on `receipt_logs` presence | P3 | Graceful empty state | No | Final |
| Payments | Balances | PARTIAL | Depends on `balances` table | P3 | Graceful empty / DNA label | No | Final |
| Certificates | Issue/revoke/list | COMPLETE | 1 local cert | P1 | Preserve | No | Final |
| Certificates | Public verify membership | COMPLETE | — | P1 | Preserve | No | Final |
| Certificates | Public verify by cert number | PARTIAL | Form UI lacks cert field (GET `cert` works) | P1 | **Add cert field to verify.php** | No | Final |
| Certificates | Revoked certs not valid | COMPLETE | Lookup returns null if REVOKED | P1 | Preserve | No | Final |
| Certificates | Download authZ | COMPLETE | — | P1 | Preserve | No | Final |
| Documents | Admin review/view | COMPLETE | — | P1 | Preserve | No | Final |
| Documents | Protected download | COMPLETE | Auth + ownership checks | P1 | Preserve | No | Final |
| Documents | Access denial audit | COMPLETE | via `access.denied` | P1 | Preserve | No | P2-D |
| Verification | `/verify.php` shareable link | PARTIAL | Hardcodes `https://eca.co.sz` | P5 | **Use request host / env base locally** | No | Final |
| Reports | Catalogue + CSV snapshot | COMPLETE | Excel/PDF deferred | P2 | Preserve | No | Final |
| Reports | Excel/PDF | DEFERRED | Not in local codebase | P5 | Do not invent | No | Document |
| Security | Hub security page | COMPLETE | Super Admin only | P2 | Preserve | No | P2-B |
| Settings | System settings | COMPLETE | Super Admin | P2 | Preserve | No | P2-B |
| Search | Hub global search | COMPLETE | — | P2 | Preserve | No | Final |
| Notifications | Hub notifications | COMPLETE | — | P4 | Preserve | No | Final |
| Support | Contact form → messages | COMPLETE | — | P4 | Verify CSRF/store | No | Final |
| Support | Tickets admin | COMPLETE | — | P4 | Preserve | No | Final |
| CPD | Hub monitor + deep links | COMPLETE | Full CPD in `/cpd/` | P3 | Preserve bridge | No | Final |
| CPD | Courses/apps/points/admin | COMPLETE | Separate CPD schema | P3 | Preserve; no Hub rewrite | No | Final |
| Wellness | CMS events/announcements/resources | COMPLETE | — | P3 | Preserve | No | Final |
| Wellness | Public check-in | COMPLETE | — | P3 | Preserve CSRF | No | Final |
| Education | Hub CMS | COMPLETE | — | P5 | Preserve | No | Smoke |
| Content | News/tenders/events/resources | COMPLETE | — | P5 | Preserve | No | Smoke |
| Public | Homepage/directory/about/membership | COMPLETE | — | P5 | Preserve responsive | No | Smoke |
| Public | Live UltraPro parity | NOT VERIFIABLE LOCALLY | Live discovery stalled | — | Do not claim parity | No | — |
| Email | SMTP notifications | PARTIAL | Local SMTP may be unset | P4 | Safe local fail; no prod creds | No | Document |
| Email | Production mail delivery | NOT VERIFIABLE LOCALLY | — | — | Pre-prod checklist | No | — |
| Data | Portal seed volume | NOT VERIFIABLE LOCALLY | Only 2 clients, 0 payments | — | Do not fabricate metrics | No | — |
| Deploy | Production cutover | NOT VERIFIABLE LOCALLY | — | — | Checklist only | Maybe | — |

---

## Priority implementation queue (this sprint)

### Must implement (code-only)

1. **Certificate verification UI** — accept cert number on `/verify.php` form; keep revocation rules.
2. **Local-safe verification share link** — no hard-coded production host when `APP_ENV=local`.
3. **Payments/balances/receipts empty-state clarity** — distinguish true zero vs missing table.
4. **End-to-end workflow smoke** — application track + cert verify + payment page auth using existing seed.
5. **Complete audit coverage review** — confirm P2-D hooks remain; fill any remaining operational gaps without new tables.
6. **Final comprehensive test suite** + full P1–P2-C regression.
7. **FINAL_SYSTEM_COMPLETION_REPORT.md**

### Must NOT implement without explicit schema approval

- `apply-007-local.php` / `role_permissions`
- `users.status`
- New audit tables
- Excel/PDF exporters
- Production SMTP wiring

---

## Local data reality (truth source)

| Store | Relevant content |
| --- | --- |
| `eca_local` | users, roles, companies, audit_logs, contact_messages, wellness_*, balances? |
| `eca_portal_local` | tbl_client (2), payments (0), membership_certificates (1), documents, userss |

---

## Regression gate

After every major change block:

```text
P1:   30 PASS / 0 FAIL
P2-A: 18 PASS / 0 FAIL
P2-B: 46 PASS / 0 FAIL
P2-C: 23 PASS / 0 FAIL
```

Then run new final suite. Do not delete or weaken tests.
