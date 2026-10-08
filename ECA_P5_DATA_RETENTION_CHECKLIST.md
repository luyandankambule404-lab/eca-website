# ECA P5 — Data Retention Checklist

**Date:** 2026-10-08  
**Rule:** Do not invent legal policy. ECA must decide or verify existing policy.  
**Status:** **REQUIRES ECA**

---

## Decision table

| Data class | Examples | Status | Notes for ECA |
| --- | --- | --- | --- |
| Member / contractor profile | Names, IDs, contacts, membership numbers | **POLICY REQUIRED** | Retention after resignation/expiry |
| Membership applications | Application forms, refs, status history | **POLICY REQUIRED** | Keep period for incomplete/rejected |
| Documents | Uploaded certificates, POP, company docs | **POLICY REQUIRED** | Storage location `_private/documents` |
| Payment records | Proofs, wallet_transactions, MoMo refs | **POLICY REQUIRED** | Often longer for finance/audit |
| Tickets / support | Admin tickets, CPD support | **POLICY REQUIRED** | Close vs delete vs anonymise |
| CPD / training | Course applications, attendance, certificates | **POLICY REQUIRED** | Professional record obligations |
| Wellness information | Referrals, toolbox interactions | **POLICY REQUIRED** | Higher sensitivity — minimise + access control |
| Enquiry / contact mail | Contact form, advisory prefixes | **POLICY REQUIRED** | Mailbox + app logs |
| Audit logs | Login failures, CSRF rejects, admin actions | **POLICY REQUIRED** | Security retention vs privacy |
| Backups | DB + file archives | **POLICY REQUIRED** | Retention days/weeks + offsite |
| Marketing / news content | Public articles | EXISTING / ops practice | Confirm ownership |
| Session / rate-limit files | `_private/rate-limits` | Ops — short-lived OK | Confirm cleanup job |

---

## For each POLICY REQUIRED item, ECA should record

1. Retention period  
2. Legal/business basis  
3. Deletion / anonymisation method  
4. Who can approve exceptions  
5. Whether backups inherit the same period  

Until recorded: treat as **REQUIRES ECA** — not a blocker for *technical* deploy alone, but mandatory for responsible go-live approval.
