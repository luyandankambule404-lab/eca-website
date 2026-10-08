# ECA P5 — cPanel Go-Live Checklist

**Date:** 2026-10-08  
**Rule:** DO NOT change cPanel in this phase. Checklist only for future controlled execution.  
**Status:** **REQUIRES HOSTING**

---

## A. Runtime

| Item | Target | Notes |
| --- | --- | --- |
| PHP version | Prefer 8.1+ (current `.htaccess` references ea-php74 — **upgrade before go-live if possible**) | Confirm with host |
| Required extensions | `mysqli`, `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`, `json`, `session` | Verify in MultiPHP INI |
| `display_errors` | **Off** | Production |
| `log_errors` | **On** | Private path |
| `error_log` | Outside public_html | Writable by PHP only |
| Document root | Point to application public root (current deploy root for `v1` as configured by ECA) | No `_private` web exposure |
| Memory / upload limits | Match max membership document sizes | Confirm |

---

## B. Environment variables

| Variable | Required | Notes |
| --- | --- | --- |
| `APP_ENV` | `production` | Enables fail-closed gates |
| `ECA_DB_*` | Yes | Production hub DB |
| `ECA_PORTAL_DB_*` | Yes | Production portal DB |
| `ECA_SMTP_*` | Yes for mail | TLS port 587 typical |
| `ECA_COOKIE_SECURE` | `1` | With HTTPS |
| `ECA_MOMO_*` | Only after payment approval | See MoMo readiness |
| `ECA_ALLOW_PORTAL_PREVIEW` | Unset / `0` | Fail closed |
| `ECA_ALLOW_LEGACY_TRAINING_REG` | Unset / `0` unless approved | Fail closed |

---

## C. Database

| Item | Requirement |
| --- | --- |
| Credentials | From env only |
| Privileges | App user: DML on needed tables; no FILE/SUPER |
| Remote access | Disabled unless required |
| Backup user | Separate account for dumps |

---

## D. Filesystem permissions (recommended)

| Path | Recommend |
| --- | --- |
| Application PHP/HTML | `644` files / `755` dirs; owner deploy user |
| `_private/` | Not web-reachable; `750` / `640` |
| `_private/documents` | Write for PHP user; no execute of uploads |
| `_private/legacy-registration` | Same |
| `uploads` / portal uploads | No PHP execution (handler deny) |
| Logs | Write-only for app; not public |
| Backups | Outside web root; restricted |
| `.env` | `600`; denied by `.htaccess` FilesMatch |

---

## E. HTTPS / headers

| Item | Action |
| --- | --- |
| SSL/TLS | AutoSSL or commercial cert |
| Force HTTPS | cPanel redirect or `.htaccess` |
| Security headers | Already in `.htaccess` — confirm `mod_headers` on |
| HSTS | Only after HTTPS stable |

---

## F. SMTP

| Item | Action |
| --- | --- |
| Host/port/TLS | Document in SMTP gate |
| From domain alignment | SPF/DKIM/DMARC |
| Test | Send to internal mailbox only |

---

## G. Cron / jobs

| Job | Purpose | Status |
| --- | --- | --- |
| Database backup | Daily dump + retention | NOT CONFIGURED (hosting) |
| File backup | App + `_private` | NOT CONFIGURED |
| Log rotation | Prevent disk fill | REQUIRES HOSTING |
| Certificate renewal monitor | AutoSSL health | REQUIRES HOSTING |

---

## H. Storage limits

| Item | Check |
| --- | --- |
| Disk quota | Headroom for uploads + backups |
| Inode limits | Many small upload files |
| Backup offsite | Not only same host |

---

## I. Pre-cutover confirmation (hosting owner signs)

- [ ] PHP version + extensions
- [ ] `display_errors=Off`
- [ ] Env vars set (no local secrets)
- [ ] Document root correct
- [ ] `_private` not HTTP-accessible
- [ ] HTTPS live + redirect
- [ ] Cron backups scheduled
- [ ] Error log path confirmed
- [ ] SMTP relay works
- [ ] MoMo env unset until approved

**Overall hosting status:** **REQUIRES HOSTING** — no cPanel changes performed in P5.
