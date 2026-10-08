# ECA P5 — HTTPS / Cookie Checklist

**Date:** 2026-10-08  
**Status:** **REQUIRES HOSTING**

Clearly distinguish **LOCAL** vs **PRODUCTION**.

---

## LOCAL (current)

| Item | Expectation | Status |
| --- | --- | --- |
| Protocol | `http://127.0.0.1:8765` | READY for local |
| SSL certificate | Not required on loopback | N/A |
| HTTP→HTTPS redirect | Must **not** force HTTPS locally | READY (do not enable) |
| Secure cookies | Off unless `ECA_COOKIE_SECURE=1` | READY (HTTPS/env driven in `session.php`) |
| HSTS | Do **not** activate in a way that breaks local HTTP | `.htaccess` HSTS only when `HTTPS=on` |
| Mixed content | Local assets mostly same-origin | OK for local |

---

## PRODUCTION (required before go-live)

| Item | Requirement | Status |
| --- | --- | --- |
| Valid SSL certificate | Trusted cert for `eca.co.sz` (and www if used) | REQUIRES HOSTING |
| HTTP → HTTPS redirect | All HTTP requests permanently redirect to HTTPS | REQUIRES HOSTING |
| Canonical HTTPS URLs | Sitemap, emails, MoMo callback URL use `https://` | REQUIRES ECA / HOSTING |
| Secure cookies | `ECA_COOKIE_SECURE=1` and/or HTTPS-detected `secure` flag | REQUIRES HOSTING |
| HttpOnly + SameSite | Already set in `eca_session_start()` (`HttpOnly`, `SameSite=Lax`) | READY (code) |
| HSTS | Enable only after HTTPS proven stable; `.htaccess` already has conditional HSTS | REQUIRES HOSTING verify |
| Mixed-content check | No `http://` active scripts/styles/images on HTTPS pages | REQUIRES TEST |
| Secure external resources | CDNs/APIs over HTTPS only | REQUIRES TEST |

---

## Cookie policy (code)

File: `v1/includes/session.php`

- `session.cookie_httponly = 1`
- `secure` when HTTPS **or** `ECA_COOKIE_SECURE=1`
- `SameSite=Lax`

**Do not** set `ECA_COOKIE_SECURE=1` on local HTTP — browsers will drop the session cookie.

---

## Go-live gate

| Gate | Status |
| --- | --- |
| Local HTTPS | N/A / not required |
| Production TLS | **NOT READY** — hosting |
| Production redirects + cookies | **NOT READY** — hosting |
| **Overall HTTPS** | **REQUIRES HOSTING** |
