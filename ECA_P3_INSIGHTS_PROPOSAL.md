# ECA P3 — Insights (Future) Proposal

**Status:** Assessment only — **not fully implemented**  
**Date:** 2026-10-08  

## Can existing architecture support “ECA Insights”?

**Yes, as a content channel**, without a new intelligence platform:

| Option | Fit | Notes |
| --- | --- | --- |
| News with category `Insights` / `Industry Intelligence` | High | Same pattern as Advocacy Updates |
| Education articles / Knowledge Centre | High | Already CMS-backed |
| Resources downloads | Medium | For PDF research packs |
| Dedicated `/insights.php` hub | Future | Could mirror advocacy-updates listing |

## Proposed information architecture

```
Digital Systems & Data Intelligence hub
  → ECA Insights (future)
       → Market / industry notes
       → Tender opportunity context (editorial, not scraping)
       → Contractor challenge themes (aggregated, anonymised — SOURCE REQUIRED)
       → Research summaries approved for public release
```

## Content types (future)

- Short insight notes (news-like)  
- Longer research summaries  
- Linked tenders/events (existing modules)  

## Potential sources

- Digital Systems & Data Intelligence department (PDF p.3)  
- Approved secretariat research  
- **Not:** invented analytics or scraped third-party data without approval  

## Future data requirements

- Prefer reuse of `news.categories` or education articles  
- New tables only if multi-file research packs + embargo workflows are required — **STOP and propose schema then**  

## Current action

No Insights platform shipped in P3. Advocacy Updates proves the category-filter pattern that Insights can reuse later.
