# ECA P5 — Production Secrets Checklist

**Date:** 2026-10-08  
**Rule:** Never print, commit, or paste real secrets.  
**Status:** **REQUIRES HOSTING** / **REQUIRES ECA**

---

## Storage principles

| Principle | Requirement |
| --- | --- |
| Location | Host environment / cPanel env / outside web root `.env` (not in git) |
| Source control | `.env` gitignored; only `.env.example` with empty placeholders |
| Access | Hosting admin + designated ECA technical owner only |
| Rotation | Documented below; rotate after any exposure or staff change |
| Production-only | Production values must never be copied into local developer machines without approval |

---

## Required secrets

| Secret | Purpose | Storage | Owner | Rotation | Access | Prod-only |
| --- | --- | --- | --- | --- | --- | --- |
| DB host/name/user/password | Hub DB (`ECA_DB_*`) | Host env / private `.env` | Hosting + ECA IT | On staff change / suspected leak | Least privilege DB user | Yes for prod DSN |
| Portal DB credentials | CPD/membership (`ECA_PORTAL_DB_*`) | Host env | Hosting + ECA IT | Same | Separate user preferred | Yes |
| `ECA_SMTP_HOST/USER/PASS` | Outbound mail | Host env | Hosting + ECA IT | On provider reset | Mail admin only | Credentials yes |
| Application session entropy | PHP session / server | Host PHP / OS | Hosting | OS reinstall / compromise | Root/hosting | N/A |
| `ECA_MOMO_API_TOKEN` | MoMo initiate + callback | Host env | ECA finance/IT | On provider rotation | Payment operators + IT | Live token yes |
| MoMo provider portal passwords | Provider console | Password manager | ECA finance | Quarterly / leavers | Finance lead | Yes |
| SSO credentials (if approved later) | IdP client secret | Host env | ECA IT | On IdP policy | IT only | Yes |
| Backup encryption key (if used) | Offsite backup decrypt | Offline / vault | ECA IT | Annually / compromise | Backup owners | Yes |
| cPanel / SSH / FTP | Hosting access | Password manager | Hosting owner | Leavers / quarterly | Named admins | Yes |
| DNS / registrar | Domain control | Registrar vault | ECA management | Leavers | Named admins | Yes |

---

## Explicitly NOT for production

| Item | Rule |
| --- | --- |
| `ECA_LOCAL_ADMIN_PASSWORD` | Local tools only |
| Hardcoded `EcaLocal!2026` | Local QA fallback only; tools refuse `APP_ENV=production` |
| `ECA_ALLOW_PORTAL_PREVIEW=1` | Not recommended in production |
| Local `.env` from developer laptops | Do not upload to production |

---

## Pre-go-live checklist

- [ ] Production `.env` / env vars created on host (not from laptop copy of local secrets)
- [ ] DB users least-privilege (app write vs backup vs read-only)
- [ ] SMTP credentials tested with non-user mailbox
- [ ] MoMo token set only after ECA payment approval
- [ ] No secrets in HTML, JS, public logs, or git history
- [ ] Rotation owners named in ECA ops contact list

**Overall secrets status:** **NOT READY** until hosting confirmation.
