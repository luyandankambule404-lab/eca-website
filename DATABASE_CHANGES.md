# DATABASE_CHANGES.md

**Date:** 2026-10-06  

## Summary

**No database schema changes were made for P0, P1, or P2.**

P2 reuses existing local tables for dashboard KPIs, reports catalogue links, users/roles, and audit.

## Still not applied (would require approval)

| Candidate | Reason | Risk |
| --- | --- | --- |
| Slides / alerts / communications tables | Live Communication Centre — unverified locally | Medium |
| Hard company↔client FK | Would invent relationship | High — do not invent |

Any future change must ship a migration and never touch production.
