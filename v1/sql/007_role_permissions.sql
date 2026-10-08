-- Phase A additive RBAC (LOCAL eca_local ONLY).
-- Do not run against production. Do not drop existing roles or user_roles.

CREATE TABLE IF NOT EXISTS role_permissions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id TINYINT UNSIGNED NOT NULL,
  permission VARCHAR(128) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_role_permissions (role_id, permission),
  KEY idx_role_permissions_permission (permission),
  CONSTRAINT fk_role_permissions_role
    FOREIGN KEY (role_id) REFERENCES roles (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Applied by v1/tools/apply-007-local.php when the column is missing:
-- ALTER TABLE users
--   ADD COLUMN status VARCHAR(16) NOT NULL DEFAULT 'ACTIVE' AFTER role,
--   ADD INDEX idx_users_status_role (status, role);
