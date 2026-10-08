-- Phase 4 additive content schema.
-- Apply to eca_local (tenders/events/tickets) AND eca_portal_local (notification columns).
-- Do not drop tables. Do not seed fake tenders/events/news.

-- eca_local:
CREATE TABLE IF NOT EXISTS tenders (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  summary TEXT NULL,
  body TEXT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  published_at DATETIME NULL,
  closes_at DATETIME NULL,
  document_name VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tenders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  summary TEXT NULL,
  venue VARCHAR(255) NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  capacity INT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event_registrations (
  id INT NOT NULL AUTO_INCREMENT,
  event_id INT NOT NULL,
  name VARCHAR(190) NULL,
  email VARCHAR(190) NULL,
  membership_number VARCHAR(50) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_event_reg_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE contact_messages
  ADD COLUMN ticket_reference VARCHAR(32) NULL,
  ADD COLUMN status VARCHAR(32) NULL DEFAULT 'OPEN',
  ADD COLUMN assigned_to VARCHAR(190) NULL,
  ADD COLUMN admin_reply TEXT NULL;

ALTER TABLE contact_messages
  ADD UNIQUE KEY uq_contact_ticket_reference (ticket_reference);
