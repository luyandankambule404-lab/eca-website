# ECA P3 — Evidence-Backed Service Improvements Report

**Date:** 2026-10-08  
**Environment:** Local only  

---

## 1. Features implemented

| Feature | Approach |
| --- | --- |
| Advocacy Updates | Reuses `news` rows with category containing “Advocacy”; dedicated page + section on Advocacy hub; empty state when none |
| Counselling pathway clarity | Documented referral model on Wellness Inclusivity + Support & Referrals; Contact enquiry type |
| Advisory intake routing | Contact form enquiry type prefixes subject (`[Technical Advisory]` etc.); no CRM |
| Content gap structures | SOURCE REQUIRED placeholders for impact, mentorship, CSR, Board currency, advisory delivery |
| Insights | Proposal doc only |
| Tender intelligence | Proposal doc only; preserve existing tenders |

## 2. Files changed / created

**Created:**  
`v1/includes/advocacy-content.php`, `v1/includes/enquiry-routing.php`, `v1/advocacy-updates.php`, `ECA_P3_INSIGHTS_PROPOSAL.md`, `ECA_P3_TENDER_INTELLIGENCE_PROPOSAL.md`, `ECA_P3_IMPLEMENTATION_REPORT.md`

**Updated:**  
`v1/advocacy.php`, `v1/contact.php`, `v1/contact_process.php`, `v1/wellness-inclusivity.php`, `v1/wellness/support.php`, `v1/technical-support.php`, `v1/about-structure.php`, `v1/admin/news-edit.php`, `v1/css/advocacy.css`, `v1/css/organization.css`, `v1/includes/organization.php`, `v1/index.php`, `local-router.php`, `v1/sitemap.php`

## 3. Existing functionality reused

- `news` table + Admin News CMS (`categories`)  
- Contact form + `contact_messages` tickets (status/assignment/reply unchanged)  
- Wellness Support pathways / referrals / check-in  
- Tenders module  
- Org / advocacy presentation CSS  

## 4. New functionality

- Advocacy Updates listing (filter + empty state)  
- Enquiry type select → subject prefix routing  
- Counselling pathway narrative (no clinical system)  
- Advisory intake explanation + CTA  

## 5. Content structures created

SOURCE REQUIRED blocks for: advocacy impact, mentorship, CSR pack, Board currency, advisory live-delivery scope.

## 6. Source-backed claims used

- Advocacy mandate + publishing via Communications  
- Wellness counselling = referral via office (existing Hub copy)  
- Technical Support themes + contact as intake  
- Smart Loan / Autism remain named-only (unchanged locks)

## 7. Source-required items remaining

Campaigns/impact stats; Smart Loan product; Autism programme; Site Records DMS; advisory SLA/eligibility; mentorship programme; CSR pack; Board term confirmation; Insights publishable research.

## 8. Database changes

**NONE**

## 9. Production changes

**NONE**

## 10. Auth/RBAC changes

**NONE**

## 11. Security changes

**NONE** (CSRF/rate-limit/ticket flow preserved; subject prefix only)

## 12. Regression results

| Suite | Result |
| --- | --- |
| Final-system | **67 PASS / 0 FAIL** (1 BLOCKED payment E2E; 3 NOT_VERIFIABLE_LOCALLY) |
| Access-control | **102 PASS / 0 FAIL** |

Smoke: Advocacy Updates empty state, counselling pathway, advisory intake + contact enquiry type, Board SOURCE REQUIRED, demo news labels — HTTP 200.

## 13. Known limitations

- Advocacy Updates only appear when staff set category to Advocacy  
- Enquiry routing is subject-text based (staff search), not a DB enum  
- No Insights / tender analytics platform  

## 14. Recommended next phase

1. ECA supplies Advocacy news items + impact pack  
2. Confirm Board listing currency  
3. Mentorship / CSR briefs → content pages  
4. Insights launch using same category pattern  
5. Only after packs: consider schema for true advisory categories if subject-prefix proves insufficient  
