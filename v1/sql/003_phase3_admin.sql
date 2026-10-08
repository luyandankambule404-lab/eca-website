-- Phase 3 additive admin schema (eca_portal_local).
-- Do not drop existing tables. No fee amounts.
--
-- New columns on tbl_client_documents:
--   reviewed_at DATETIME NULL
--   reviewed_by VARCHAR(190) NULL

ALTER TABLE tbl_client_documents
  ADD COLUMN reviewed_at DATETIME NULL,
  ADD COLUMN reviewed_by VARCHAR(190) NULL;
