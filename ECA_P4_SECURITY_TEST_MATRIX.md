# ECA P4 — Security Test Matrix

**Date:** 2026-10-08  

| ID | Control | Test | Expected | Result (local) |
| --- | --- | --- | --- | --- |
| T1 | AuthZ admin | GET `/admin/users.php` anon | 302 login / deny | PASS |
| T2 | AuthZ member | GET `/client/dashboard.php` anon | 302 client login | PASS |
| T3 | Documents | GET `/document-download.php?id=1` anon | 401/403 | PASS (401) |
| T4 | Headers | GET `/` | XCTO, XFO, CSP, Referrer-Policy | PASS |
| T5 | CSRF likes | POST `/like.php` without token/origin | 403 | PASS (P4 fix) |
| T6 | Verify abuse | Rate limit code path | `verify-lookup` present | PASS (P4 fix) |
| T7 | Advocacy content | `/advocacy-updates.php` | Empty state or real advocacy news; no fake campaigns | PASS |
| T8 | Wellness wording | `/wellness/support.php` | Non-clinical / referral | PASS |
| T9 | Demo news | `/news.php` | LOCAL DEMO label on seeds | PASS |
| T10 | Final-system | CLI suite | 67 PASS / 0 FAIL | See run |
| T11 | Access-control | CLI suite | 102 PASS / 0 FAIL | See run |
| T12 | P4 security regression | `v1/tools/p4-security-regression.php` | 0 FAIL | See run |

**Not performed (destructive / out of scope):** SQLi payload fuzzing, malware uploads, production SMTP sends, live DB connection.
