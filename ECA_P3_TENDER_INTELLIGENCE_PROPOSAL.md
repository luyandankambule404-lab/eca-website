# ECA P3 — Tender Intelligence (Future) Proposal

**Status:** Assessment only — **no scraping / fake analytics**  
**Date:** 2026-10-08  

## Existing functionality (preserve)

| Feature | Route | Notes |
| --- | --- | --- |
| Published tenders list | `/tenders.php` | Hub `tenders` table |
| Tender detail | `/tender.php` | Existing |
| Admin tender CMS | `/admin/tenders.php` | `content.manage` |
| Technical Support link | `/technical-support.php` | Points to published tenders |

## Source mandate (PDF p.4)

Tender & Procurement Support unit includes market intelligence using data from Digital Systems (awards, pricing trends, upcoming projects).

## What is NOT implemented

- Automated tender scraping  
- Fabricated pricing trend charts  
- Award analytics without verified data  

## Future feature proposal (after source pack)

1. Editorial “Tender intelligence notes” using News category `Tender Intelligence` or Insights  
2. Optional fields on tenders for award outcome — **would need schema proposal first** (STOP before migrate)  
3. Summaries curated by Digital Systems / Technical Support staff  

## Current action

Preserve tenders module. Technical Support “Available now” links to published tenders only.
