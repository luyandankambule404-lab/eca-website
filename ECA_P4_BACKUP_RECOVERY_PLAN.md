# ECA P4 — Backup & Recovery Plan

**Date:** 2026-10-08  
**Scope:** Recommended production/local operational plan — **no production backups modified**

---

## What must be backed up

| Asset | Priority | Notes |
| --- | --- | --- |
| Hub database (`eca_local` / production hub name) | P0 | Members ops, tickets, content, admin users |
| Portal database | P0 | Membership, CPD, applications, payments |
| `_private/documents/` | P0 | Application / member documents |
| `_private/mail-log/` (if retained) | P2 | Privacy-sensitive; encrypt if kept |
| Application code release package | P0 | Versioned deploy artifact |
| `.env` / host environment config | P0 | Store in secrets vault — never in public backup share |
| Uploaded education/CPD/resources files | P1 | Per deployment layout |
| Wellness media if stored on disk | P1 | |

---

## Frequency & retention (recommended)

| Asset | Frequency | Retention |
| --- | --- | --- |
| Databases | Daily automated + pre-deploy manual | 30 daily / 12 weekly / 12 monthly |
| `_private/documents` | Daily or continuous sync | Align with DB retention |
| Code releases | Each deploy | Last 10 releases minimum |
| Config/secrets | On change | Prior 5 versions in vault |

---

## Storage

- Off-server (not only the web host)  
- Access-controlled; encrypted at rest  
- Separate credentials from web app DB user  

---

## Restore procedure (high level)

1. Declare incident / maintenance window  
2. Identify backup set (timestamp, checksum)  
3. Restore DBs to staging first when possible  
4. Restore `_private/documents` to matching paths  
5. Deploy matching code release  
6. Inject env secrets  
7. Smoke: login, verify, track, document download, CPD login, admin hub  
8. Run access-control / critical paths  
9. Return to service / communicate  

---

## Recovery priorities

1. Authentication + membership data  
2. Application documents  
3. Payments/certificates integrity  
4. Public content (news/tenders)  
5. Mail logs (optional)

---

## Responsible roles

| Role | Responsibility |
| --- | --- |
| Hosting / IT | Job scheduling, storage, encryption |
| Super Admin / designated officer | Pre-deploy backup verification |
| ECA management | RTO/RPO acceptance |

**Suggested RPO:** ≤ 24 hours · **Suggested RTO:** ≤ 8 hours (confirm with ECA)

---

## Restore testing

- Quarterly restore drill to non-production  
- Document last successful restore date  

**Current status:** Plan documented; production job **NOT READY** until hosting implements.
