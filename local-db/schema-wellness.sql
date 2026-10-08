-- ECA Wellness module (hub database). Education, resources, events — no medical records.

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
  hub_section VARCHAR(64) NULL,
  kind VARCHAR(32) NULL,
  body_text TEXT NULL,
  topic_slug VARCHAR(128) NULL,
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

CREATE TABLE IF NOT EXISTS wellness_checkins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  scores_json VARCHAR(255) NOT NULL,
  band VARCHAR(32) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wellness_checkin_created (created_at)
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
