# ECA P5 — Backup / Restore Test Gate

**Date:** 2026-10-08  
**Rule:** DO NOT perform against production in this phase.  
**Status:** **NOT VERIFIED**

---

## Scope

### DATABASE

| Step | Detail | Status |
| --- | --- | --- |
| Full backup | `mysqldump` (or host backup) of hub + portal DBs | NOT VERIFIED |
| Verification | File non-empty; optional checksum; spot-check table list | NOT VERIFIED |
| Restore | Restore into isolated staging DB (not production) | NOT VERIFIED |

### FILES

| Asset | Include | Status |
| --- | --- | --- |
| Application files | Deployed PHP/assets | NOT VERIFIED |
| Uploads | Membership/payment proofs | NOT VERIFIED |
| Private documents | `_private/documents` | NOT VERIFIED |
| Configuration | Host env template (no secret values in ticket) | NOT VERIFIED |
| Legacy registration uploads | `_private/legacy-registration` / portal uploads | NOT VERIFIED |

---

## Formal restore test procedure (future staging)

1. **Backup creation** — timestamped DB dump + file archive  
2. **Backup integrity verification** — checksum / archive test / dump head validation  
3. **Clean restoration** — empty staging DB + extract files to staging tree  
4. **Application startup** — PHP pages load without fatal errors  
5. **Database connectivity** — hub + portal connections succeed  
6. **Login test** — admin + member (staging credentials)  
7. **Public site test** — home, news, contact  
8. **Admin test** — dashboard, one write path in staging only  
9. **Member test** — portal home  
10. **Document access test** — authorized download works; anonymous denied  

Each step: record date, operator, pass/fail, evidence path.

---

## Acceptance

| Outcome | Meaning |
| --- | --- |
| All 10 steps pass on staging | Restore gate **READY** |
| Any step skipped or failed | **NOT VERIFIED** / **NOT READY** |
| Production-only backup without restore drill | **NOT VERIFIED** |

**Current decision:** No production restore test has been performed → **STATUS = NOT VERIFIED**. Do not claim READY.
