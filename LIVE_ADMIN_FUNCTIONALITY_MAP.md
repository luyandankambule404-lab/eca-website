# LIVE Admin Functionality Map

**Date:** 2026-10-06  
**Local project:** `D:\Website`  
**Live reference base:** `https://www.eca.co.sz/demo/`  
**Primary URL requested:** `https://www.eca.co.sz/demo/dashboard.php`  
**Production writes:** none (read-only HTTP fetches only)  
**Credentials:** none submitted; no auth bypass attempted

---

## 1. Live Admin architecture (discovered)

The live Admin is **not** at `/admin/`. It is a separate **UltraPro / Contractor Development Portal** under:

| Path | Role |
| --- | --- |
| `/demo/` | Live Admin portal root |
| `/demo/dashboard.php` | Admin login gate (“Secure Login / Admin Portal”) |
| `/demo/index.php` | Same login gate |
| `/cpd/` | Separate CPD Training Portal |
| `/admin/` on live | **404** (local Command Centre path does not exist on live) |

Live CSS identity: `eca-ultrapro.css` (versions observed `20260919v3`, `20260921v11`, `20260921report12`).

---

## 2. Live navigation inventory (from accessible pages)

Unique module links discovered from live HTML:

| Module URL | Title / purpose | Access without credentials |
| --- | --- | --- |
| `dashboard.php` | Secure Login | Login gate (public form) |
| `index.php` | Secure Login | Login gate |
| `members.php` | Members \| ECA | **OPEN (200)** — full member list HTML |
| `members25.php` | Login gate | **LIVE ACCESS REQUIRED** |
| `reports.php` | ECA Membership Reports | **OPEN (200)** |
| `application.php` | Login gate | **LIVE ACCESS REQUIRED** |
| `balingani.php` | Balingani Members \| ECA | **OPEN (200)** |
| `users.php` | ECA - Manage Users | **OPEN (200)** |
| `support_center.php` | Login gate | **LIVE ACCESS REQUIRED** |
| `email_center.php` | Login gate | **LIVE ACCESS REQUIRED** |
| `communication_centre.php` | Communication Centre \| ECA | **OPEN (200)** |
| `company_owners_report.php` | ECA Companies & Owners Report | **OPEN (200)** |
| `business_assistant.php` | AI Business Assistant \| ECA | **OPEN (200)** |
| `economic_alerts.php` | Economic Alerts \| ECA | **OPEN (200)** |
| `industry_news.php` | Industry News \| ECA | **OPEN (200)** |
| `regulation_alerts.php` | Standards & Regulation Alerts \| ECA | **OPEN (200)** |
| `news.php` | ECA Dashboard — Manage News | **OPEN (200)** |
| `slides.php` | ECA Dashboard — Slides Management | **OPEN (200)** |
| `cpd_auto_login.php` | ECA CPD System bridge | Redirect/login behaviour |
| `client.php?client_id=` | Member/client detail | Needs id; empty without |
| `edit_client.php?id=` | Edit client | Needs id; empty without |
| `delete_client.php?id=` | Delete client | Linked from members (dangerous if open) |
| `export_clients.php` | Client export | Linked; not fully verified |
| `export_report_excel.php` | Excel membership export | Linked; probe returned error without params |
| `export_report_pdf.php` | ECA Membership Intelligence Report | **OPEN (200)** with date params |
| `logout.php` | Logging Out… | **OPEN (200)** |
| `add_news.php` / `edit_news.php` / `delete_news.php` / `toggle_news_status.php` | News CMS | Linked from news.php |
| `add_slides.php` / `edit_slides.php` / `delete_slides.php` / `toggle_slide_status.php` | Slides CMS | Linked from slides.php |

**LIVE SCREEN NOT ACCESSIBLE — NEEDS MANUAL VERIFICATION** (login-walled in this pass):

- Post-login `dashboard.php` interior (KPIs, charts, pending queues after auth)
- `application.php` interior workflow
- `members25.php` (likely 2025/2026 membership report UI)
- `support_center.php`
- `email_center.php`
- Any authenticated-only actions behind CSRF/session

---

## 3. Screen records (accessible)

### 3.1 Login — `/demo/dashboard.php` & `/demo/index.php`

1. **URL:** `/demo/dashboard.php`, `/demo/index.php`  
2. **Title:** ECA Login \| Contractor Development Portal  
3. **Menu:** none (login shell)  
4. **Role:** Authorized ECA users only (claimed)  
5. **KPIs:** none on login  
6–12. **Tables/filters/search/pagination/sort:** n/a  
13. **Forms:** Username, Password, Login  
14. **Validation:** not verified server-side from outside  
15–28. **Actions/audit/exports:** **LIVE ACCESS REQUIRED** after login  

Also shows public chrome: “ECA AI Assist”, Information & Alerts (“Training activity”).

### 3.2 Members — `/demo/members.php`

1. **URL:** `/demo/members.php`  
2. **Title:** Members \| ECA  
3. **Menu:** UltraPro sidebar (same family as reports)  
4. **Role:** **NOT VERIFIED FROM LIVE** (page returned 200 without login in this environment)  
5. **KPIs observed:**  
   - Total Membership ≈ **1,082**  
   - Renewal Members ≈ **743**  
   - Joining Members ≈ **339**  
   - Regions Represented ≈ **4**  
6. **Tables:** members grid  
7. **Columns:** #, Trading Name, Email, Cellphone, Region, Classification, Date Registered, Status, Actions  
8–11. Search control present; full filter/sort/pagination behaviour **NOT VERIFIED FROM LIVE** at interaction level (page dumps a very large HTML table ~4.4MB)  
13–15. **Actions linked:** `client.php?client_id=…`, `edit_client.php?id=…`, `delete_client.php?id=…`  
16–18. Status values appear as membership standing/type mix (Active / Renewal / Joining etc.) — exact enum **LOCAL DIFFERENCE** vs local `active` + `Status` fields  
19–20. Export link family includes `export_clients.php`  
24. **Likely DB:** live equivalent of `tbl_client` (+ related)  
25. **Permissions:** **NOT VERIFIED FROM LIVE**  
28. **Audit:** **NOT VERIFIED FROM LIVE**

### 3.3 Membership Reports — `/demo/reports.php`

1. **URL:** `/demo/reports.php`  
2. **Title:** ECA Membership Reports  
3. **Menu:** dashboard, members, members25, application, balingani, users, reports, communication, AI/alerts, news/slides, CPD auto-login, support/email (some gated)  
5. **Report suite titles observed:**  
   - Advanced Membership Analysis  
   - Executive Summary Report  
   - Membership Status Composition  
   - Active vs Inactive Members Report  
   - Registration Report Filtered by Date Range  
   - Renewal Report Filtered by Date Range  
   - Registration and Renewal Statement  
   - Pending Applications Report for Contractors Awaiting Approval  
   - Members by Region / Classification / Enterprise  
   - Regional Performance Report  
   - Contractor Classification Performance Report  
   - Monthly Membership Trend Report with Charts  
   - Predicted Next Month Renewals  
   - Renewal Ratio  
7. **Tables:** Trading Name, Email, Cellphone, Region, Classification, Date Registered / Renewal Date, Status  
8. **Filters:** date range (`start_date`, `end_date`)  
19–22. **Exports:** `export_report_excel.php`, `export_report_pdf.php` (“ECA Membership Intelligence Report”)  
24. **Likely DB:** membership / `tbl_client` analytics  
**LIVE ACCESS REQUIRED** for authenticated-only report controls if any exist beyond the public HTML.

### 3.4 Balingani — `/demo/balingani.php`

2. **Title:** Balingani Members \| ECA  
5. **Heading:** Balingani Member Overview  
7. **Columns:** #, Member, Cellphone, Region, Classification, Date registered, Actions  
**Maps locally** to public `balingani-directory` / women-in-construction subset — Admin CRUD parity **NOT VERIFIED FROM LIVE**.

### 3.5 Users — `/demo/users.php`

2. **Title:** ECA - Manage Users  
5. Counts shown for user totals (3 / 2 / 1 style KPI cards)  
7. **Columns:** #, Username, **Password**, Role, Created At, Actions  
13. **Actions:** Add User, Edit User  
**Roles observed in markup:** `Admin`, `Officer`, `SuperAdmin`  
**SECURITY NOTE (live):** password column label is present in the users table UI. Local reproduction must **never** display password hashes/plaintext. Use hashed storage only.

### 3.6 Companies & Owners — `/demo/company_owners_report.php`

2. **Title:** ECA Companies & Owners Report  
5. **Heading:** Full Companies with Owners Report  
7. **Columns include:** Client ID, Trading Name, Registered Company, Membership, Region, Classification, Enterprise, Status, Owner, Gender, Citizen, Shares %, Owner Status, Owners Count, Data Quality, Detail  

### 3.7 Communication / AI / Alerts / CMS

| Page | Observed purpose |
| --- | --- |
| `communication_centre.php` | Add Communication + Published Entries |
| `business_assistant.php` | AI Business Assistant |
| `economic_alerts.php` | Economic Alerts |
| `industry_news.php` | Industry News |
| `regulation_alerts.php` | Standards & Regulation Alerts |
| `news.php` | Manage News (CMS) |
| `slides.php` | Slides Management (CMS) |
| `cpd_auto_login.php` | Bridge into CPD system |

Interiors of AI/alerts are mostly chrome + content lists; deep workflow **NOT VERIFIED FROM LIVE**.

---

## 4. What was NOT invented

Interior post-login dashboard widgets, approval buttons, email senders, payment verification screens, certificate revoke flows, and ticket assignment UIs behind login were **not** fabricated.

Where a screen was gated: **LIVE ACCESS REQUIRED**.

---

## 5. Probe artefacts (local only)

Read-only HTML snapshots saved under:

`D:\Website\_private\live-demo-probe\`

These may contain live member PII. They must not be committed or deployed. Production DB was not queried.
