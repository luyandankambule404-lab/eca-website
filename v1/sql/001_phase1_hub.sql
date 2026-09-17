-- Phase 1 additive Hub tables. Do not drop existing membership/directory tables.
-- Applied to eca_local only.

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

INSERT IGNORE INTO roles (slug, label) VALUES
  ('public', 'Public'),
  ('member', 'Member'),
  ('admin', 'Admin'),
  ('super_admin', 'Super Admin'),
  ('membership_officer', 'Membership Officer'),
  ('finance_officer', 'Finance Officer'),
  ('content_manager', 'Content Manager'),
  ('training_officer', 'Training Officer');

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
INNER JOIN roles r ON r.slug = 'admin'
WHERE u.email = 'admin@eca.co.sz'
LIMIT 1;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('application_fee', ''),
  ('membership_fee', ''),
  ('renewal_fee', ''),
  ('training_fee', ''),
  ('event_fee', '');
