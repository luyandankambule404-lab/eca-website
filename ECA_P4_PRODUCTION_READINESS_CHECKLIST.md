# ECA P4 — Production Readiness Checklist

**Date:** 2026-10-08  
**Rule:** Local green ≠ production ready.

| Code | Meaning |
| --- | --- |
| READY | Locally verified / design ready |
| NOT READY | Must complete before go-live |
| REQUIRES ECA ACTION | Business/content/approval |
| REQUIRES HOSTING ACTION | cPanel/DNS/server |
| REQUIRES SOURCE/APPROVAL | Content or legal |
| DEFERRED | Explicitly postponed |

---

## A. Environment

| Item | Status |
| --- | --- |
| APP_ENV=production set on host | REQUIRES HOSTING ACTION |
| `display_errors=Off` in production PHP | REQUIRES HOSTING ACTION |
| Error log path writable, not public | REQUIRES HOSTING ACTION |
| Local `.env` never deployed with local passwords | REQUIRES HOSTING ACTION |
| Debug tooling / verify scripts not web-exposed | REQUIRES HOSTING ACTION |

## B. Database

| Item | Status |
| --- | --- |
| Production DSN via env only | READY (pattern) |
| Least-privilege DB user | REQUIRES HOSTING ACTION |
| Live read-only mode credentials are read-only | REQUIRES HOSTING ACTION / REQUIRES ECA ACTION |
| Backups scheduled | NOT READY — see backup plan |
| Schema migrations reviewed | DEFERRED (no P4 schema) |

## C. Secrets

| Item | Status |
| --- | --- |
| `.env` gitignored | READY |
| SMTP/DB/MoMo secrets in host env | REQUIRES HOSTING ACTION |
| Rotate any credentials that ever appeared in local tools | REQUIRES ECA ACTION |
| No secrets in HTML/JS | READY (spot-check) |

## D. HTTPS

| Item | Status |
| --- | --- |
| Valid TLS certificate | REQUIRES HOSTING ACTION |
| Force HTTPS | REQUIRES HOSTING ACTION |
| `ECA_COOKIE_SECURE=1` | REQUIRES HOSTING ACTION |
| HSTS enabled | READY in `.htaccess` when HTTPS |

## E. Authentication

| Item | Status |
| --- | --- |
| Password hashing / regenerate session | READY |
| Idle timeout | READY |
| Failed login audit | READY |
| Hub SSO preview gated for production | NOT READY — confirm disabled/gated |
| Password reset paths reviewed | REQUIRES ECA ACTION |

## F. Authorization

| Item | Status |
| --- | --- |
| Access-control suite green | READY (102/0) |
| Admin vs Super Admin separation | READY |
| Document download authZ | READY |

## G. File storage

| Item | Status |
| --- | --- |
| `_private/documents` outside public URL | READY (design) |
| Host filesystem ACLs | REQUIRES HOSTING ACTION |
| Legacy `registration` upload path reviewed | NOT READY / DEFERRED |

## H. Email

| Item | Status |
| --- | --- |
| Production SMTP TLS | REQUIRES HOSTING ACTION |
| From/spoofing policy | REQUIRES ECA ACTION |
| Mail log not public | REQUIRES HOSTING ACTION |

## I. Backups

| Item | Status |
| --- | --- |
| Backup plan documented | READY (doc) |
| Backup job active | NOT READY |
| Restore test | NOT READY |

## J. Monitoring

| Item | Status |
| --- | --- |
| Uptime / error monitoring | REQUIRES HOSTING ACTION |
| Disk / `_private` growth | REQUIRES HOSTING ACTION |

## K. Logging

| Item | Status |
| --- | --- |
| Audit logs protected by RBAC | READY |
| PHP error logs not browsable | REQUIRES HOSTING ACTION |

## L. Security headers

| Item | Status |
| --- | --- |
| Local-router / `.htaccess` headers | READY |
| Confirm Apache/Nginx honors headers | REQUIRES HOSTING ACTION |
| CSP without unsafe-inline | DEFERRED |

## M. Error handling

| Item | Status |
| --- | --- |
| No stack traces to clients in prod | REQUIRES HOSTING ACTION |

## N. Data protection

| Item | Status |
| --- | --- |
| Sensitive data map | READY (doc) |
| Retention / deletion policy | REQUIRES ECA ACTION |

## O. Rollback

| Item | Status |
| --- | --- |
| Rollback plan documented | READY (doc) |
| Verified backup before deploy | REQUIRES HOSTING ACTION |

## P. DNS / cPanel

| Item | Status |
| --- | --- |
| DNS cutover plan | REQUIRES HOSTING ACTION / REQUIRES ECA ACTION |
| No accidental local config upload | REQUIRES HOSTING ACTION |

## Q. Final approval

| Item | Status |
| --- | --- |
| ECA content/source approvals (advocacy, wellness claims) | REQUIRES SOURCE/APPROVAL |
| Security sign-off | REQUIRES ECA ACTION |
| Go-live approval | REQUIRES ECA ACTION |

---

## Production-readiness estimate

| Category | Score (indicative) |
| --- | --- |
| Application security controls (local) | ~85% |
| Hosting / secrets / HTTPS / backups | ~35% |
| Content/source completeness | ~60% |
| **Overall go-live readiness** | **NOT READY** (~55%) until hosting + ECA actions close |

**Blockers:** production env/secrets/HTTPS, backups+restore test, SSO preview gate confirmation, SMTP policy, ECA go-live approval.
