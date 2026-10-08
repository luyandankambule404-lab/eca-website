# ECA Admin Differences (LIVE vs LOCAL)

**Date:** 2026-10-06  
**Updated after:** P2 Super Admin / live functionality parity (local)

---

## Status legend

| Tag | Meaning |
| --- | --- |
| VERIFIED LIVE → IMPLEMENTED LOCALLY | Seen on live and built locally |
| NOT VERIFIED LIVE → IMPLEMENTED FROM LOCAL DATA MODEL | Built from local schema; live UI not fully inspected |
| NOT VERIFIED LIVE → REQUIRES MANUAL LIVE ACCESS | Needs future read-only live inspection |
| DEFERRED | Explicitly out of current phase |

---

## P2 Super Admin parity

| Feature | Status |
| --- | --- |
| Dashboard sections (Members, Companies, Applications, Payments, Certificates, CPD, Wellness, Support, Audit feed) | **NOT VERIFIED LIVE → IMPLEMENTED FROM LOCAL DATA MODEL** |
| Recent feeds (members/apps/payments/companies) | **NOT VERIFIED LIVE → IMPLEMENTED FROM LOCAL DATA MODEL** |
| Reports catalogue linking domain reports | **NOT VERIFIED LIVE → IMPLEMENTED FROM LOCAL DATA MODEL** |
| Users / Roles (existing Super Admin RBAC) | **NOT VERIFIED LIVE → IMPLEMENTED FROM LOCAL DATA MODEL** (reused; no new permission system) |
| Audit known-action labels expanded | Local strengthening |
| Excel / PDF exports | **DEFERRED** |
| Communication Centre / Slides / Alerts | **NOT VERIFIED LIVE → REQUIRES MANUAL LIVE ACCESS** (+ likely DB change — not started) |
| Exact UltraPro layout | **UNVERIFIED LIVE** |

---

## P1 Companies & Owners

| Feature | Status |
| --- | --- |
| Companies listing + intelligence | **IMPLEMENTED FROM LOCAL DATA MODEL** |
| Owners Report | **VERIFIED LIVE (URL discovery) → IMPLEMENTED LOCALLY** |
| Soft match only (no hard FK) | Documented |

---

## P0 Membership Intelligence

Accepted — preserved in P2.

---

## Production safety

No production DB writes, deploys, or SMTP use during P0–P2.
