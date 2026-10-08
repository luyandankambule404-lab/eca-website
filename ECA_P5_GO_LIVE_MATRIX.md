# ECA P5 — Go-Live Matrix

**Date:** 2026-10-08  
**Final decision rule:** GO-LIVE READY only if all mandatory blockers are closed or explicitly accepted by ECA management. Otherwise **NOT READY**.

| Gate | Status | Evidence | Owner | Required Action |
| --- | --- | --- | --- | --- |
| Security | PARTIAL / NOT READY | P4 audit + P5 gates; hosting/ECA items open | ECA IT + Security approver | Complete hosting ACL, secrets, HTTPS, approvals |
| HTTPS | REQUIRES HOSTING | `ECA_P5_HTTPS_CHECKLIST.md` | Hosting | Cert, redirect, secure cookies, mixed-content test |
| Secrets | REQUIRES HOSTING | `ECA_P5_PRODUCTION_SECRETS_CHECKLIST.md` | Hosting + ECA IT | Produce host env; never commit secrets |
| Authentication | READY (local) | Session harden, password hash, idle timeout | ECA IT | Confirm production cookie secure flag |
| Authorization | READY (local) | Access-control 102/0 | ECA IT | No RBAC redesign in P5 |
| CSRF | READY (code) | Production token-only; legacy reg token; P5 tests | ECA IT | Keep forms emitting `csrf_token` |
| XSS | READY (local pattern) | Escaping + CSP present; `unsafe-inline` remains | ECA IT | CSP hardening deferred where inline required |
| SQLi | READY (local pattern) | Prepared statements spot-check P4 | ECA IT | Maintain prepared statements |
| File uploads | PARTIAL | MIME checks; legacy path gated in prod | ECA IT | Confirm upload dirs non-executable on host |
| Private documents | READY (local) | AuthZ download; `_private` design | Hosting | Confirm web ACL denies `_private` |
| Sessions | READY (code) | HttpOnly, SameSite=Lax, idle | Hosting | `ECA_COOKIE_SECURE=1` on HTTPS |
| SSO | READY (fail-closed code) | Preview disabled when `APP_ENV=production` unless explicit allow | ECA IT | Do not set `ECA_ALLOW_PORTAL_PREVIEW=1` |
| MoMo | NOT READY | `ECA_P5_MOMO_READINESS.md` | Finance + IT | Sandbox test + amount/idempotency + ECA approval |
| SMTP | REQUIRES HOSTING | Host/TLS/From/SPF below | Hosting + ECA IT | Configure TLS SMTP; test internal only |
| Backups | NOT READY | `ECA_P5_BACKUP_RESTORE_TEST.md` | Hosting | Schedule DB + file backups |
| Restore | NOT VERIFIED | No restore drill performed | Hosting + ECA IT | Staging restore test (10 steps) |
| Error handling | READY (code) / REQUIRES HOSTING | `eca_apply_production_error_policy`; host `display_errors=Off` | Hosting | Confirm php.ini + app policy |
| Headers | READY (code) | `.htaccess` security headers + CSP | Hosting | Confirm `mod_headers` |
| Hosting ACL | REQUIRES HOSTING | `ECA_P5_CPANEL_GO_LIVE_CHECKLIST.md` | Hosting | Permissions + private paths |
| Monitoring | REQUIRES HOSTING | Error log path; uptime optional | Hosting | Wire log monitoring |
| Logging | PARTIAL | Audit helpers + mail-log; host log path TBD | Hosting + IT | Private writable logs |
| Content approval | REQUIRES ECA | Org/source content ownership | ECA content owner | Sign public content |
| Security approval | REQUIRES ECA | P4+P5 packages | ECA security approver | Written sign-off |
| Go-live approval | REQUIRES ECA | This matrix | ECA management | Written GO / NO-GO |

---

## SMTP go-live notes (no credentials)

| Item | Requirement |
| --- | --- |
| Host | `ECA_SMTP_HOST` (provider hostname) |
| Port | Typically `587` (STARTTLS) or `465` (SMTPS) |
| TLS | Required in production |
| From | Aligned to organisational domain (`ECA_SMTP_FROM` / info@) |
| Reply-To | Policy: no-reply vs monitored inbox — **REQUIRES ECA** |
| SPF/DKIM/DMARC | DNS records for sending domain — **REQUIRES HOSTING** |
| Credential storage | Host env only |
| Failure handling | Log; do not expose SMTP errors to public users |
| Logging | `_private/mail-log` locally; production path must be private |
| Testing | Internal mailboxes only — never real member blasts for test |

---

## CSP status

| Directive | Current | Notes |
| --- | --- | --- |
| `script-src` | `'self' 'unsafe-inline' https:` | **Exception retained** — many pages use inline scripts (forms, carousels, like.js CSRF wiring). Nonce/hash migration is a follow-on project. |
| `style-src` | `'self' 'unsafe-inline' https:` | Inline styles in legacy/admin pages |
| `unsafe-eval` | Not present | Good |
| Risk | Residual XSS impact reduction incomplete | Documented; do not break site for perfect CSP in P5 |

---

## Mandatory blockers (must be closed or formally accepted)

- Secrets  
- HTTPS  
- Backup/restore  
- Production error disclosure (host confirm)  
- SSO preview (code closed; host must not re-enable)  
- Critical security issue (none newly open after P5 gates)  
- Payment safety (MoMo **NOT READY**)  
- Authorization (local READY)  
- Private-document protection (host ACL confirm)

**Because secrets, HTTPS, backup/restore, hosting error display, MoMo, and ECA approvals remain open → FINAL GO-LIVE = NOT READY.**

---

## P5 code closures (local)

| Blocker | Action |
| --- | --- |
| Same-origin CSRF fallback | Disabled when `APP_ENV=production` |
| Legacy registration CSRF | Token + `eca_require_public_post`; prod disabled unless allow flag |
| SSO preview | `eca_portal_preview_allowed()` fail-closed in production |
| MoMo token mismatch | Callback uses `ECA_MOMO_API_TOKEN`; live opt-in required |
| Tool password fallback | Tools refuse production APP_ENV |
| `display_errors` | App forces Off when production |

---

## Regression baselines (expected)

| Suite | Expected |
| --- | --- |
| Final-system | 67 PASS / 0 FAIL |
| Access-control | 102 PASS / 0 FAIL |
| P4 security regression | 9 PASS / 0 FAIL |
| P5 security regression | All PASS / 0 FAIL |
