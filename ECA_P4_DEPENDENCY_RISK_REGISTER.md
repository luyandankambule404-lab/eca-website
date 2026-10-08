# ECA P4 — Dependency Risk Register

**Date:** 2026-10-08  
**Note:** No Composer root lockfile; many libraries are vendored/copied. Versions noted where visible.

| Dependency | Location | Risk notes | Action |
| --- | --- | --- | --- |
| PHP 8.x (XAMPP) | Host | Keep patched | Hosting patch policy |
| Bootstrap 5.x (CDN) | Public pages | CDN supply-chain | Pin version; SRI recommended |
| jQuery 3.6 (CDN) | Some pages | Legacy but common | Prefer reduce use over time |
| Font Awesome / Bootstrap Icons | CDN | Low | Pin versions |
| PHPMailer | `v1/PHPMailer-master`, CPD copies | Multiple copies; ensure not ancient | Inventory + keep one supported copy |
| Dompdf (CPD) | `v1/code.jquery.com/cpd/dompdf` | PDF gen; patch periodically | Review before prod |
| phpqrcode | CPD | QR generation | Low |
| Slick carousel | CDN | Low | Pin |
| Owl / animate libs | Local `lib/` | Low | — |
| MoMo API client | CPD pay scripts | Token handling sensitive | Align env secrets (see P4-005) |

**No automatic major upgrades in P4.**

**Recommendation:** Create a single approved PHPMailer version and remove duplicate trees in a future maintenance phase (after regression).
