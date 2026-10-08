# COMPANIES_DATA_MAPPING.md

**Date:** 2026-10-06  
**Scope:** Local databases only (`eca_local`, `eca_portal_local`)  
**Rule:** No new tables invented for P1.

---

## Entities discovered

### 1. Public contractor directory — `eca_local.companies`

| Item | Value |
| --- | --- |
| Primary key | `id` |
| Key fields | `name`, `registration_number`, `email`, `phone`, `address`, `industry`, `status`, `website`, `description` |
| Purpose | Public directory / Admin Companies list |
| Seed rows (audit) | 6 |

`companies1` is a parallel/legacy directory table (Balingani path). Not required for P1 core listing when `companies` has rows.

### 2. Membership / contractor client — `eca_portal_local.tbl_client`

| Item | Value |
| --- | --- |
| Primary key | `client_id` |
| Company-like fields | `CompanyRegistrationName`, `TradingName`, address, Region, Clasification, Enterprise |
| Membership fields | `MembershipNumber`, `Status` (type), `active` (standing), application_*, CertificateNumber |
| Seed rows (audit) | 2 |

**Important:** Membership “company” data lives **on the client row**. There is no separate FK from `tbl_client` to `companies.id`.

### 3. Owners — `eca_portal_local.owners`

| Item | Value |
| --- | --- |
| Primary key | `id` |
| Link | `clientid` → `tbl_client.client_id` (soft FK; also `application_id` used by application flow) |
| Fields | `name`, `citizen`, `gender`, `shares`, `application_id` |
| Seed rows (audit) | **0** |

Owner rows are written by local application/renewal submit paths (`submit_membership.php`, `save_renewal.php`, etc.). Empty locally until applications/renewals create them.

### 4. Related tables

| Table | Relationship |
| --- | --- |
| `membership_years` | `client_id` → `tbl_client.client_id` |
| `membership_certificates` | `client_id` → `tbl_client.client_id` |
| `tbl_client_documents` | `client_id` → `tbl_client.client_id` |
| `payments` | via user / membership flows (not FK to `companies.id`) |

---

## Relationship conclusions (from schema + code — not assumed)

| Question | Answer from local evidence |
| --- | --- |
| ONE MEMBER → ONE COMPANY (directory)? | **Soft match only.** Code matches `companies.registration_number` ≈ `MembershipNumber`, or email/name equality. Not a hard FK. |
| ONE COMPANY → MANY MEMBERS? | **Possible via soft match** (multiple `tbl_client` rows could match one directory company by name/email). Currently uncommon in seed data. |
| ONE COMPANY → MANY OWNERS? | **No direct link.** Owners attach to **`tbl_client`**, not to `companies.id`. |
| ONE CLIENT → MANY OWNERS? | **Yes.** `owners.clientid` → `tbl_client.client_id` (1:N). |

```
eca_local.companies (directory)
        │  soft match: registration_number ↔ MembershipNumber
        │           or email/name
        ▼
eca_portal_local.tbl_client (membership + company fields on same row)
        │
        ├── membership_years (1:N)
        ├── membership_certificates (1:N)
        ├── tbl_client_documents (1:N)
        └── owners (1:N)  ← may be empty locally
```

---

## Live Owners Report mapping (from prior HTTP discovery)

Live `/demo/company_owners_report.php` columns (Trading Name, Membership, Owner, Gender, Citizen, Shares %, …) map locally to:

| Live-ish column | Local source |
| --- | --- |
| Client ID | `tbl_client.client_id` |
| Trading / Registered name | `TradingName` / `CompanyRegistrationName` |
| Membership | `MembershipNumber` |
| Region / Classification / Enterprise / Status | `Region`, `Clasification`, `Enterprise`, `Status`/`active` |
| Owner / Gender / Citizen / Shares | `owners.*` via `clientid` |
| Owners count | `COUNT(owners)` per client |

When `owners` has zero rows: report must show **DATA NOT AVAILABLE LOCALLY** for owner fields — never invent owners.

---

## Admin pages (P1)

| Capability | Local page |
| --- | --- |
| Companies list + intelligence | `/admin/companies.php` (enhanced) |
| Company details | `/admin/company-detail.php` |
| Company edit (existing) | `/admin/company-edit.php` |
| Owners report | `/admin/owners-report.php` |
