# ECA P5 — MoMo Readiness

**Date:** 2026-10-08  
**Rule:** Do not connect to live MoMo. Do not send live transactions.  
**Status:** **NOT READY**

---

## Summary

MoMo initiation (`pay_momo.php`) and callback (`momo_callback.php`) were misaligned on secret source and lacked an explicit live/sandbox gate. P5 aligned auth to `ECA_MOMO_API_TOKEN` and added fail-closed live opt-in. End-to-end production payment readiness is still **NOT READY**.

---

## Integration map

| Item | Detail |
| --- | --- |
| Initiate route | `/cpd/api/pay_momo.php` |
| Callback route | `/cpd/api/momo_callback.php` |
| Remote API (default) | `https://c4.technosol.co.sz/ussd/smart/momo_api_receive.php` (overridable via `ECA_MOMO_REMOTE_URL`) |
| Shared secret | `ECA_MOMO_API_TOKEN` (env only) |
| Mode | `ECA_MOMO_MODE=sandbox` (default) / `live` |
| Live opt-in | `ECA_MOMO_ALLOW_LIVE=1` required for live in production |
| CSRF on initiate | Yes (`cpd_require_csrf`) |
| Rate limit on initiate | Yes (5 / 5 minutes session) |

---

## P4 mismatch (confirmed)

| Side | Previous auth | Problem |
| --- | --- | --- |
| `pay_momo.php` | `eca_env('ECA_MOMO_API_TOKEN')` | Correct pattern |
| `momo_callback.php` | `defined('MOMO_API_TOKEN')` constant | Constant not set in `config.php` → callbacks unauthorized **or** divergent secret if someone defined the constant separately |

**P5 fix:** Callback authenticates with `ECA_MOMO_API_TOKEN` via `hash_equals`. Legacy `MOMO_API_TOKEN` constant accepted only outside production.

---

## Validation coverage

| Check | Status |
| --- | --- |
| Shared-secret callback auth | READY (code) |
| Environment live/sandbox separation | READY (code gate) |
| Amount verification on callback | **NOT READY** — callback does not re-verify amount |
| Transaction reference binding | PARTIAL — updates by `request_id` / `reference_id` |
| Duplicate callback idempotency | **NOT READY** — re-APPROVE possible; no explicit idempotent guard |
| Signature beyond shared token | **NOT READY** — no HMAC/provider signature documented |
| Sandbox credentials verified with provider | **REQUIRES TEST** / **REQUIRES ECA** |
| Production credentials staged | **REQUIRES ECA** / **REQUIRES HOSTING** |
| Audit logging of payment events | PARTIAL — `remote_response` column; no central audit event |

---

## Production rule (required)

```
APP_ENV=production
ECA_MOMO_MODE=live
ECA_MOMO_ALLOW_LIVE=1
ECA_MOMO_API_TOKEN=<production secret from provider>
```

Without all three mode/opt-in/token conditions, live initiate and live callbacks **fail closed**.

Local/default:

```
APP_ENV=local
ECA_MOMO_MODE=sandbox
ECA_MOMO_ALLOW_LIVE=0
```

---

## Go-live decision for MoMo

| Gate | Status |
| --- | --- |
| Code gate for live | READY |
| Secret alignment initiate↔callback | READY |
| Provider sandbox test | NOT VERIFIED |
| Provider production approval | REQUIRES ECA |
| Amount + duplicate handling | NOT READY |
| **Overall MoMo** | **NOT READY** |

Do not enable live MoMo until ECA approves credentials, hosting env vars are set, and a controlled sandbox→live checklist is signed off.
