<?php
/**
 * LOCAL ONLY. Apply 007_role_permissions to eca_local on this machine.
 * Refuses to run unless ECA_DB_HOST is loopback and ECA_DB_NAME is eca_local.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/authz.php';

if (!eca_rbac_is_local_database()) {
    fwrite(STDERR, "STOP: not the local eca_local database. Nothing was changed.\n");
    exit(2);
}

$conn = eca_rbac_pdo();
if (!$conn) {
    fwrite(STDERR, "Could not connect to the local database.\n");
    exit(1);
}

$schema = (string) $conn->query('SELECT DATABASE()')->fetchColumn();
$host = eca_env('ECA_DB_HOST', '');
echo "Applying 007 to {$schema} on {$host}\n";

$conn->exec(
    'CREATE TABLE IF NOT EXISTS role_permissions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

if (!eca_hub_users_has_column($conn, 'status')) {
    $conn->exec("ALTER TABLE users ADD COLUMN status VARCHAR(16) NOT NULL DEFAULT 'ACTIVE' AFTER role");
    try {
        $conn->exec('ALTER TABLE users ADD INDEX idx_users_status_role (status, role)');
    } catch (Throwable $e) {
        // Index may already exist under another name.
    }
    echo "Added users.status\n";
} else {
    echo "users.status already present\n";
}

eca_seed_default_role_permissions($conn);
$count = (int) $conn->query('SELECT COUNT(*) FROM role_permissions')->fetchColumn();
echo "role_permissions rows={$count}\n";
echo "OK\n";
