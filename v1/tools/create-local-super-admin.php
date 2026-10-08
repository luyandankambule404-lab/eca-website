<?php
/**
 * LOCAL ONLY bootstrap for a Hub Super Admin account.
 * Does not convert admin@eca.co.sz.
 * Does not invent a password. You must pass --email and --password.
 *
 * php v1/tools/create-local-super-admin.php --email=you@local.test --password=your-local-password
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/authz.php';
require_once dirname(__DIR__) . '/includes/audit.php';

if (!eca_rbac_is_local_database()) {
    fwrite(STDERR, "STOP: not the local eca_local database. Nothing was changed.\n");
    exit(2);
}

$email = '';
$password = '';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--email=')) {
        $email = trim(substr($arg, 8));
    } elseif (str_starts_with($arg, '--password=')) {
        $password = substr($arg, 11);
    }
}

if ($email === '' || $password === '') {
    fwrite(STDERR, "Usage: php v1/tools/create-local-super-admin.php --email=... --password=...\n");
    fwrite(STDERR, "Pass a local-only password. Do not use a production password.\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Enter a valid email address.\n");
    exit(1);
}

$protected = strtolower($email);
if (in_array($protected, ['admin@eca.co.sz', 'officer@eca.co.sz'], true)) {
    fwrite(STDERR, "Refusing to change the existing ADMIN/officer test accounts.\n");
    exit(1);
}

if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

$conn = eca_rbac_pdo();
if (!$conn) {
    fwrite(STDERR, "Could not connect to the local database.\n");
    exit(1);
}

$roleStmt = $conn->prepare("SELECT id FROM roles WHERE slug = 'super_admin' LIMIT 1");
$roleStmt->execute();
$roleId = (int) $roleStmt->fetchColumn();
if ($roleId <= 0) {
    fwrite(STDERR, "The super_admin role is missing from eca_local.roles.\n");
    exit(1);
}

$existing = $conn->prepare('SELECT id, email, role FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
$existing->execute([$email]);
$row = $existing->fetch(PDO::FETCH_ASSOC);
if ($row) {
    fwrite(STDERR, "A user with that email already exists (id {$row['id']}, role {$row['role']}). Nothing was changed.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$name = 'Local Super Admin';
$statusSql = eca_hub_users_has_column($conn, 'status') ? ', status' : '';
$statusVal = eca_hub_users_has_column($conn, 'status') ? ', ?' : '';
$params = [$name, $email, $hash, 'super_admin'];
if (eca_hub_users_has_column($conn, 'status')) {
    $params[] = 'ACTIVE';
}

$conn->beginTransaction();
try {
    $sql = "INSERT INTO users (name, email, password, role{$statusSql}, is_admin, created_at, updated_at)
            VALUES (?, ?, ?, ?{$statusVal}, 1, NOW(), NOW())";
    $conn->prepare($sql)->execute($params);
    $userId = (int) $conn->lastInsertId();
    $conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollBack();
    fwrite(STDERR, "Could not create the local Super Admin.\n");
    exit(1);
}

eca_audit('user.created', 'users', (string) $userId, [
    'email' => $email,
    'role' => 'super_admin',
    'source' => 'local_bootstrap',
    'result' => 'ok',
]);
eca_audit('user.promoted', 'users', (string) $userId, [
    'email' => $email,
    'role' => 'super_admin',
    'source' => 'local_bootstrap',
    'result' => 'ok',
]);

echo "Created local Super Admin {$email} (id {$userId})\n";
echo "Sign in at /admin/login.php\n";
