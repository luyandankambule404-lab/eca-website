-- ECA hub database (eca_local)
-- Admin users, public directory, homepage news, events, tenders, contact.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(64) NOT NULL DEFAULT 'admin',
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(64) NOT NULL,
  label VARCHAR(128) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  role_id TINYINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_roles (user_id, role_id),
  KEY idx_user_roles_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_type VARCHAR(32) NOT NULL DEFAULT 'system',
  actor_id VARCHAR(64) DEFAULT NULL,
  actor_email VARCHAR(190) DEFAULT NULL,
  action VARCHAR(128) NOT NULL,
  entity_type VARCHAR(64) DEFAULT NULL,
  entity_id VARCHAR(64) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  meta TEXT,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_action (action),
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS companies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  registration_number VARCHAR(64) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  industry VARCHAR(128) DEFAULT NULL,
  status VARCHAR(64) DEFAULT 'active',
  website VARCHAR(255) DEFAULT NULL,
  description TEXT,
  PRIMARY KEY (id),
  KEY idx_companies_registration (registration_number),
  KEY idx_companies_email (email),
  KEY idx_companies_industry_status (industry, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS companies1 (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  registration_number VARCHAR(64) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  phone VARCHAR(64) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  industry VARCHAR(128) DEFAULT NULL,
  status VARCHAR(64) DEFAULT 'active',
  website VARCHAR(255) DEFAULT NULL,
  description TEXT,
  PRIMARY KEY (id),
  KEY idx_companies1_registration (registration_number),
  KEY idx_companies1_email (email),
  KEY idx_companies1_industry_status (industry, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) DEFAULT NULL,
  summary TEXT,
  author VARCHAR(190) DEFAULT NULL,
  categories VARCHAR(190) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  video VARCHAR(255) DEFAULT NULL,
  link VARCHAR(255) DEFAULT NULL,
  date DATE DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'Active',
  `count` INT NOT NULL DEFAULT 0,
  created_at DATE DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  message TEXT,
  created_at DATETIME DEFAULT NULL,
  ticket_reference VARCHAR(32) NULL,
  status VARCHAR(32) NULL DEFAULT 'OPEN',
  assigned_to VARCHAR(190) NULL,
  admin_reply TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_contact_ticket_reference (ticket_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  file_path VARCHAR(255) DEFAULT NULL,
  file_type VARCHAR(32) DEFAULT NULL,
  category VARCHAR(128) DEFAULT NULL,
  status VARCHAR(32) DEFAULT 'Published',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS downloads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_name VARCHAR(255) NOT NULL,
  `count` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_downloads_file (file_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS likes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_name VARCHAR(255) NOT NULL,
  `count` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_likes_file (file_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wellness module (see also schema-wellness.sql for idempotent apply on existing DBs)
CREATE TABLE IF NOT EXISTS wellness_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  slug VARCHAR(128) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wellness_cat_slug (slug),
  KEY idx_wellness_cat_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wellness_resources (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  file_path VARCHAR(255) NULL,
  file_type VARCHAR(32) NULL,
  external_url VARCHAR(512) NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wellness_res_status (status),
  KEY idx_wellness_res_cat (category_id),
  KEY idx_wellness_res_pub (published_at),
  CONSTRAINT fk_wellness_res_cat FOREIGN KEY (category_id) REFERENCES wellness_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wellness_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  venue VARCHAR(255) NULL,
  location VARCHAR(255) NULL,
  organizer VARCHAR(190) NULL,
  contact_info VARCHAR(512) NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  capacity INT UNSIGNED NULL,
  banner_path VARCHAR(255) NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  registration_open TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wellness_evt_status (status),
  KEY idx_wellness_evt_starts (starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wellness_announcements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  published_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wellness_ann_status (status),
  KEY idx_wellness_ann_pub (published_at),
  KEY idx_wellness_ann_exp (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wellness_event_registrations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT UNSIGNED NOT NULL,
  client_id INT UNSIGNED NULL,
  membership_number VARCHAR(50) NOT NULL,
  member_name VARCHAR(190) NULL,
  member_email VARCHAR(190) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wellness_reg_event_member (event_id, membership_number),
  KEY idx_wellness_reg_event (event_id),
  CONSTRAINT fk_wellness_reg_event FOREIGN KEY (event_id) REFERENCES wellness_events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO wellness_categories (id, name, slug, sort_order, status) VALUES
  (1, 'Workplace Wellbeing', 'workplace-wellbeing', 10, 'ACTIVE'),
  (2, 'Occupational Health & Safety', 'ohs', 20, 'ACTIVE'),
  (3, 'Stress Management', 'stress-management', 30, 'ACTIVE'),
  (4, 'Healthy Workplace', 'healthy-workplace', 40, 'ACTIVE'),
  (5, 'Construction Worker Safety', 'construction-safety', 50, 'ACTIVE'),
  (6, 'Work-Life Balance', 'work-life-balance', 60, 'ACTIVE'),
  (7, 'Nutrition & Healthy Living', 'nutrition', 70, 'ACTIVE'),
  (8, 'General Wellness', 'general', 80, 'ACTIVE'),
  (9, 'Emergency & Support Contacts', 'support-contacts', 90, 'ACTIVE');

INSERT IGNORE INTO roles (slug, label) VALUES
  ('public', 'Public'),
  ('member', 'Member'),
  ('admin', 'Admin'),
  ('super_admin', 'Super Admin'),
  ('membership_officer', 'Membership Officer'),
  ('finance_officer', 'Finance Officer'),
  ('content_manager', 'Content Manager'),
  ('training_officer', 'Training Officer');

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('application_fee', ''),
  ('membership_fee', ''),
  ('renewal_fee', ''),
  ('training_fee', ''),
  ('event_fee', '');

SET FOREIGN_KEY_CHECKS = 1;
