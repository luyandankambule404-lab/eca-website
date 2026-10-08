# ECA P5 — Production Runbook (Future Deployment)

**Date:** 2026-10-08  
**Rule:** This documents the future process. **DO NOT DEPLOY** in P5.  
**Audience:** ECA-approved personnel only.

---

## Preconditions (all mandatory)

1. Go-live matrix shows no open **mandatory** blockers (or ECA signed acceptance of residual risk).  
2. Secrets checklist completed on host.  
3. HTTPS live and verified.  
4. Backup + restore drill **VERIFIED** on staging.  
5. `display_errors=Off` confirmed.  
6. MoMo left disabled unless payment gate READY.  
7. Content + security + management approvals signed.

If any mandatory gate is open → **STOP. NOT READY.**

---

## Deployment outline (controlled)

### 1. Freeze

- Announce maintenance window  
- Disable MoMo live flags if partially set  
- Snapshot current production (DB + files)

### 2. Staging validation

- Deploy candidate build to staging  
- Run Final-system / Access-control / P4 / P5 suites against staging URL  
- Smoke: public home, login, document deny anonymous, admin home

### 3. Hosting configuration

- Set `APP_ENV=production`  
- Set DB/SMTP/cookie env vars  
- Confirm `_private` not web-accessible  
- Confirm PHP version/extensions  
- Confirm cron backups

### 4. Cutover

- Deploy files  
- Run post-deploy smoke (below)  
- Monitor error log for 30–60 minutes  
- Enable MoMo **only** if MoMo gate READY and ECA approved

### 5. Post-deploy smoke

| Check | Expect |
| --- | --- |
| `https://` home | 200, no mixed content |
| HTTP request | Redirects to HTTPS |
| Anonymous `/document-download.php` | Denied |
| Admin without session | Redirect/login |
| Contact form with CSRF | Accepts |
| Contact without CSRF | 403 |
| Preview SSO | Disabled |
| PHP errors in page body | None |
| SMTP test to internal inbox | Optional, approved only |

### 6. Rollback

- Restore previous file tree from snapshot  
- Restore DB only if schema/data changed (P5 expects **no** schema change)  
- Re-verify smoke  
- See also `ECA_P4_DEPLOYMENT_ROLLBACK_PLAN.md`

---

## Absolute prohibitions

- Do not deploy from a laptop `.env` containing local passwords  
- Do not set `ECA_ALLOW_PORTAL_PREVIEW=1` without written approval  
- Do not set `ECA_MOMO_ALLOW_LIVE=1` without finance + IT approval  
- Do not send blast emails as a “test”  
- Do not disable CSRF or authz “temporarily”

---

## Ownership

| Role | Responsibility |
| --- | --- |
| ECA management | Go-live approval |
| ECA IT / technical | Deploy + env + verify |
| Hosting provider | SSL, PHP, cron, disk |
| Finance | MoMo live enablement |
| Content owner | Public content sign-off |
