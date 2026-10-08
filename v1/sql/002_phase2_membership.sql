-- Phase 2 additive membership schema (eca_portal_local only).
-- Do not drop existing tables. No fee amounts.
--
-- New columns on tbl_client:
--   application_reference VARCHAR(32) NULL  -- ECA-APP-YYYY-NNNN
--   application_status    VARCHAR(64) NULL  -- SUBMITTED | UNDER REVIEW | ADDITIONAL INFORMATION REQUIRED | APPROVED | REJECTED
--
-- New columns on tbl_client_documents:
--   storage_key   VARCHAR(64) NULL   -- opaque key; not a filesystem path
--   original_name VARCHAR(255) NULL  -- original upload name for admin display only
--
-- New table membership_application_notes:
--   id, client_id, admin_email, note, created_at
--
-- New table membership_certificates:
--   id, client_id, membership_number, certificate_number, classification,
--   company_name, issued_at, expiry_date, status (ACTIVE|REVOKED), qr_token, created_at

ALTER TABLE tbl_client
  ADD COLUMN application_reference VARCHAR(32) NULL,
  ADD COLUMN application_status VARCHAR(64) NULL;

ALTER TABLE tbl_client
  ADD UNIQUE KEY uq_tbl_client_app_ref (application_reference);

ALTER TABLE tbl_client_documents
  ADD COLUMN storage_key VARCHAR(64) NULL,
  ADD COLUMN original_name VARCHAR(255) NULL;

ALTER TABLE tbl_client_documents
  ADD UNIQUE KEY uq_tbl_client_documents_storage_key (storage_key);

CREATE TABLE IF NOT EXISTS membership_application_notes (
  id INT NOT NULL AUTO_INCREMENT,
  client_id INT NOT NULL,
  admin_email VARCHAR(190) NULL,
  note TEXT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_app_notes_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS membership_certificates (
  id INT NOT NULL AUTO_INCREMENT,
  client_id INT NOT NULL,
  membership_number VARCHAR(50) NULL,
  certificate_number VARCHAR(64) NOT NULL,
  classification VARCHAR(150) NULL,
  company_name VARCHAR(255) NULL,
  issued_at DATE NULL,
  expiry_date DATE NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'ACTIVE',
  qr_token VARCHAR(64) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_membership_certificates_number (certificate_number),
  KEY idx_membership_certificates_client (client_id),
  KEY idx_membership_certificates_member (membership_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
