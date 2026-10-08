# ECA_ADMIN_FUNCTIONAL_TEST_REPORT.md

**Date:** 2026-10-06  
**Scope:** P0 + P1 + P2 (local)

---

## P2 Summary

Automated local HTTP tests: **34 PASS / 0 FAIL**

Coverage includes: dashboard domain sections, reports catalogue, users/roles/audit gates, unauth URL protection, XSS/SQLi, CSRF, module page loads, P1 companies/owners checks.

## P1 Regression

Core P1 suite re-run after P2: **26 PASS / 0 FAIL** (unauth, listing, search/filter/sort/pagination, detail, owners, CSV, print, XSS/SQLi, admin RBAC, dashboard company overview, CSRF).

Original P1 baseline **30 PASS / 0 FAIL** behaviours preserved (companies, owners, CSV, print, RBAC, security).

## P0

Prior Membership Intelligence suite remains in place (not regressively broken by P2 dashboard/report changes).

---

## Not covered / deferred

- Excel / PDF exports  
- Live UltraPro pixel parity  
- Communication Centre / Slides / Alerts (needs live inspection + likely new tables)  
- Load test with thousands of rows  

## Production safety

All tests hit `http://127.0.0.1:8765` only.
