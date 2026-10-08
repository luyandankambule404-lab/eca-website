# ECA P4 — Security Audit Report

**Date:** 2026-10-08  
**Scope:** Local ECA website (`D:\Website`, `http://127.0.0.1:8765`)  
**Mode:** Audit + low-risk local hardening only  
**Production:** Not accessed  

**Classification legend:** CONFIRMED VULNERABILITY · POTENTIAL RISK · HARDENING RECOMMENDATION  

---

## Executive security posture

The local application has a **solid baseline**: prepared statements widely used, Hub RBAC on admin routes, private document storage under `_private/`, CSRF on most mutating routes, session cookie hardening, security headers via `local-router.php` / `.htaccess`, and green access-control regressions.

Remaining issues are mostly **hardening and deployment hygiene**, plus a few **confirmed medium** CSRF/abuse gaps (one fixed in P4).

---

## Findings table

| ID | Severity | Area | Finding | Evidence | Risk | Recommendation | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| P4-001 | MEDIUM | CSRF | `like.php` accepted unauthenticated POST without CSRF | Confirmed: no token check | CSRF like inflation / abuse | Require public CSRF | **FIXED locally** |
| P4-002 | MEDIUM | Abuse | `verify.php` membership/cert lookup had no rate limit | Confirmed vs `track.php` limits | Enumeration / scraping | Rate-limit lookups | **FIXED locally** (30/15min) |
| P4-003 | MEDIUM | CSRF | `eca_require_public_post()` allows same-origin Origin/Referer without token | `session.php` confirmed | Weaker CSRF if XSS/same-host attack | Prefer token-only for high-value forms; keep fallback only if needed | HARDENING RECOMMENDATION |
| P4-004 | MEDIUM | CSRF / Upload | Legacy `registration/submit_registration.php` lacks Hub CSRF / hardened session | Confirmed | CSRF + weaker session | Migrate to modern registration or add CSRF | POTENTIAL RISK / DEFERRED |
| P4-005 | MEDIUM | Integration | MoMo callback may use different token env/constant and GET token | Confirmed code path | Callback spoofing if misconfigured | Align env keys; require POST + header secret | HARDENING RECOMMENDATION (prod) |
| P4-006 | MEDIUM | Auth | Hub SSO “portal preview” can bind member context for Super Admin | Confirmed in client auth | Dangerous if enabled on production without gates | Gate to APP_ENV=local only | HARDENING RECOMMENDATION |
| P4-007 | LOW | Secrets | Local tools/setup embed default local admin password fallbacks | Confirmed in tools/setup | Local-only credential patterns | Keep local-only; never deploy tools defaults | HARDENING RECOMMENDATION |
| P4-008 | LOW | Privacy | `_private/mail-log` stores email HTML | Confirmed mailer | Disk exposure if web-readable | Keep outside web root; restrict ACLs | HARDENING RECOMMENDATION |
| P4-009 | LOW | Upload | Legacy registration upload dir mode `0777` / portal path | Confirmed | Writable web path | Use `_private` + stricter perms | HARDENING RECOMMENDATION |
| P4-010 | LOW | CSP | CSP allows `'unsafe-inline'` scripts/styles | Confirmed headers | Limits XSS mitigation of CSP | Tighten after script inventory | HARDENING RECOMMENDATION |
| P4-011 | INFO | Abuse | `directory-suggest` / company JSON can return large sets | Confirmed limits up to high N | Scraping load | Keep clamps; monitor | HARDENING RECOMMENDATION |
| P4-012 | INFO | Env | `LIVE_DB_*` keys exist for read-only mode | Confirmed `db-mode.php` | Misuse if write credentials supplied | Production checklist: read-only DSN only | HARDENING RECOMMENDATION |

---

## Area assessments

### Environment / secrets
| Check | Status |
| --- | --- |
| `.env` present locally | PRESENT |
| `.env` in `.gitignore` / `v1/.gitignore` | SAFE |
| Secrets via `eca_env()` | SAFE pattern |
| Hardcoded production secrets in app code | ABSENT (local tool defaults PRESENT — local only) |
| Secrets in HTML | Not observed |
| APP_ENV / db-mode | PRESENT — NEEDS REVIEW for production |

### Authentication
| Flow | Status |
| --- | --- |
| Admin / Super Admin | Session + regenerate on login; login failure audited |
| Member portal | `eca_require_member`; redirects unauthenticated |
| CPD | Separate session/roles; CSRF helpers |
| Logout isolation | Documented prior fix (member vs admin/CPD) — retain |
| Brute force | Rate limits on login/contact/track/verify |

### Authorization / RBAC
| Check | Result |
| --- | --- |
| Unauthenticated `/admin/*` | Redirect to login (302) |
| Document download anon | **401** |
| Access-control suite | 102 PASS / 0 FAIL |
| Nav-only auth | Not sole control — `eca_admin_require` / `eca_can` on routes |

### CSRF
Most Hub/client/CPD/public forms protected. Public POST has token **or** same-origin fallback (P4-003). `like.php` fixed.

### XSS
Public pages reviewed escape output. No confirmed stored XSS in audit pass. CSP present but allows unsafe-inline.

### SQL injection
No confirmed injectable string-concat of user input in core routes. ORDER BY / LIMIT whitelisted or cast.

### File uploads / private documents
Modern membership docs: finfo + extension + random names under `_private/documents`. Downloads authorize owner/admin. Legacy registration path weaker.

### Certificates / QR
Public verify shows public standing only; revoked certs handled; rate-limited in P4.

### Sessions
HttpOnly, SameSite=Lax, optional Secure, idle timeout, regenerate on login.

### Headers
PRESENT on local-router and `.htaccess`: X-Content-Type-Options, X-Frame-Options, CSP, Referrer-Policy, Permissions-Policy. HSTS for HTTPS production via `.htaccess` when HTTPS.

### Error handling
No `display_errors` forced on in app includes searched. Production must set `display_errors=Off`.

### Audit logging
Failed login, CSRF reject, role/security events logged where implemented. Protect `audit_logs` via RBAC.

### SMTP
Credentials from env; TLS 587 pattern; contact sets From to user email (classic form pattern — review for spoofing on production).

### Content / source discipline (P1–P3)
Advocacy empty state / category filter; LOCAL DEMO labels; counselling non-clinical wording; SOURCE REQUIRED placeholders — intact.

---

## Hardening applied in P4 (local)

1. CSRF on `like.php` + token from `resources.php`  
2. Rate limit on `verify.php` lookups  
3. Added `v1/tools/p4-security-regression.php` (9 PASS / 0 FAIL)

## Not applied (document for review)

- Removing same-origin CSRF fallback  
- Legacy registration CSRF migration  
- MoMo callback hardening  
- Restricting Hub SSO preview to local only (code change needs careful test)  
- CSP nonce migration  

---

## Regression (P4)

| Suite | Result |
| --- | --- |
| Final-system | **67 PASS / 0 FAIL** |
| Access-control | **102 PASS / 0 FAIL** |
| P4 security regression | **9 PASS / 0 FAIL** |

---

## DATABASE CHANGES

**NONE**

## PRODUCTION CHANGES

**NONE**

## AUTH/RBAC CHANGES

**NONE** (behavior-preserving CSRF/rate-limit hardening only)
