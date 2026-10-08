# PRODUCTION_SAFETY_REPORT.md

**Date:** 2026-10-06  
**Project:** `D:\Website` local ECA

## Confirmation

| Control | Status |
| --- | --- |
| Production website files modified | **No** |
| Production database INSERT/UPDATE/DELETE/DDL | **No** |
| Production SMTP / SMS used | **No** |
| Production passwords / API keys printed or committed | **No** |
| Auth bypass attempted on live | **No** |
| Live credentials submitted | **No** |

## What was done against live

Read-only HTTPS GETs to public/demo URLs for functionality discovery, e.g.:

- `https://www.eca.co.sz/`
- `https://www.eca.co.sz/demo/dashboard.php` (login form)
- `https://www.eca.co.sz/demo/members.php`
- `https://www.eca.co.sz/demo/reports.php`
- other `/demo/*.php` module probes listed in `LIVE_ADMIN_FUNCTIONALITY_MAP.md`

## Local-only operations

- Listed local MySQL schemas `eca_local` / `eca_portal_local` (SELECT/SHOW only)
- Wrote audit markdown files in `D:\Website\`
- Saved HTML snapshots under `D:\Website\_private\live-demo-probe\` (may contain live PII — do not deploy/commit)

## Environment posture

- Local app expected: `APP_ENV=local` → `eca_local` + `eca_portal_local`
- `live_readonly` mode must remain SELECT-only when intentionally enabled
- Local mail must use local/dev logging only

## Residual risks

1. `_private/live-demo-probe/members.html` contains live member contact data — treat as confidential; delete after mapping if not needed.  
2. Live demo pages that returned 200 without login indicate **production security issues** on live — out of scope to fix from local, but local must not copy open-by-default behaviour.
