# ECA P4 — Deployment & Rollback Plan

**Date:** 2026-10-08  
**Status:** Document only — **DO NOT DEPLOY** from this audit  

---

## Preconditions

- [ ] ECA go-live approval  
- [ ] Production readiness checklist blockers closed  
- [ ] Backup verified (checksum / restore sample)  
- [ ] Maintenance window communicated  

---

## Safe future deployment sequence

1. **Full backup** — databases + `_private/documents` + current code  
2. **Verify backup** — list files, checksum, optional staging restore  
3. **Set aside deployment package** — immutable release tarball/zip with version tag  
4. **Verify environment variables** — APP_ENV, DB, SMTP, cookie secure, public URL (no local passwords)  
5. **Verify database credentials** — least privilege; confirm not local defaults  
6. **Deploy** — upload/release package; do not overwrite secrets from local `.env`  
7. **Smoke tests** — home, login (admin/member/CPD), verify, track, contact, directory, advocacy updates, wellness support  
8. **Security tests** — unauthenticated admin deny, document download 401, CSRF on contact  
9. **Regression tests** — final-system + access-control against staging/production test host if available  
10. **Monitor** — errors, disk, mail failures, tickets for 24–72h  
11. **Roll back if required**  

---

## Rollback (explicit)

If smoke/security/regression fail or critical incident:

1. Stop accepting writes if possible (maintenance page)  
2. Restore previous code release  
3. Restore databases from pre-deploy backup **only if** data corruption occurred (coordinate carefully if new memberships arrived)  
4. Restore `_private/documents` from matching backup  
5. Re-verify env  
6. Re-run smoke  
7. Incident note to ECA  

**Prefer code-only rollback** when DB schema unchanged (current P1–P4 posture: no schema changes).

---

## Do not

- Deploy with local tool default passwords  
- Deploy with `display_errors=On`  
- Deploy SSO preview without production gate  
- Skip backup verification  

---

## Rollback readiness

| Item | Status |
| --- | --- |
| Plan documented | READY |
| Schema-free rollback simplicity | READY (current) |
| Production backup automation | NOT READY |
| Practiced rollback drill | NOT READY |
