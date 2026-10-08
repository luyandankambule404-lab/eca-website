# P2-C Users & Roles — notes

**Date:** 2026-10-06  
**DATABASE CHANGES:** None applied

## Already working (preserved)

- Super Admin–only `users.view` / `roles.view` gates + strip list
- Create user (`eca_create_hub_user`) with CSRF, hash, role validation, audit
- User detail: name edit, role assign, password reset, final Super Admin protection
- Role permission UI with floor / Super Admin–only strip
- Search / role filter / pagination on users

## Local schema gaps (not invented)

| Gap | Impact | Resolution |
| --- | --- | --- |
| No `users.status` column | Activate/deactivate UI unavailable | Deferred — would need schema change |
| No `role_permissions` table | Permission edits cannot persist; defaults from code | Existing local migration `v1/sql/007_role_permissions.sql` / `apply-007-local.php` — **not applied** (needs approval) |

## P2-C polish applied

- Status/role badges, sort whitelist, created column, clear counts
- Logical permission groups (actual catalog keys)
- Clear message when `role_permissions` missing; Save disabled
- Self-deactivate blocked server-side (+ UI when status exists)
- Invalid user ID → 404
