<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/company-data.php';
require_once __DIR__ . '/../includes/authz.php';
require_once __DIR__ . '/../includes/audit.php';

function eca_admin_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function eca_admin_db(): ?PDO
{
    static $conn = false;
    if ($conn === false) {
        $db = new Database();
        $conn = $db->getConnection(false);
    }
    return $conn instanceof PDO ? $conn : null;
}

function eca_admin_user(): ?array
{
    return $_SESSION['eca_admin'] ?? null;
}

function eca_admin_require(): void
{
    if (!eca_admin_user() || !eca_can('hub.access')) {
        header('Location: /admin/login.php');
        exit;
    }
}

function eca_admin_csrf(): string
{
    if (empty($_SESSION['eca_admin_csrf'])) {
        $_SESSION['eca_admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['eca_admin_csrf'];
}

function eca_admin_csrf_ok(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['eca_admin_csrf'])
        && hash_equals($_SESSION['eca_admin_csrf'], $token);
}

function eca_admin_is_admin_row(array $row): bool
{
    $role = eca_normalize_role((string) ($row['role'] ?? ''));
    $isAdmin = (string) ($row['is_admin'] ?? '0');
    return in_array($role, ['admin', 'super_admin'], true) || $isAdmin === '1';
}

function eca_admin_password_ok(array $row, string $password, string $email): bool
{
    $stored = (string) ($row['password'] ?? '');
    if ($stored !== '') {
        $info = password_get_info($stored);
        if (!empty($info['algo']) && password_verify($password, $stored)) {
            return true;
        }
    }

    $local = eca_env('ECA_LOCAL_ADMIN_PASSWORD', '');
    if ($local !== '' && strcasecmp($email, 'admin@eca.co.sz') === 0 && hash_equals($local, $password)) {
        return true;
    }

    return false;
}

function eca_admin_attempt(PDO $conn, string $email, string $password): ?array
{
    $email = trim($email);
    if ($email === '' || $password === '') {
        return null;
    }

    try {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return null;
    }

    if (!$row || !eca_admin_is_admin_row($row) || !eca_admin_password_ok($row, $password, $email)) {
        return null;
    }

    return [
        'id' => $row['id'] ?? 0,
        'name' => $row['name'] ?? 'Administrator',
        'email' => $row['email'] ?? $email,
        'role' => eca_normalize_role((string) ($row['role'] ?? 'admin')),
    ];
}

function eca_admin_logout(): void
{
    if (eca_admin_user()) {
        $admin = eca_admin_user();
        eca_audit('admin.logout', 'users', (string) ($admin['id'] ?? ''), ['email' => $admin['email'] ?? '']);
    }
    unset($_SESSION['eca_admin'], $_SESSION['eca_admin_csrf']);
}
