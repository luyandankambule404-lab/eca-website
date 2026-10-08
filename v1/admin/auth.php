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

function eca_first_hub_super_admin(?PDO $conn): ?array
{
    if (!$conn) {
        return null;
    }
    try {
        $rows = $conn->query('SELECT id, name, email, role FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return null;
    }
    foreach ($rows as $row) {
        if (function_exists('eca_hub_user_is_active') && !eca_hub_user_is_active($row)) {
            continue;
        }
        $role = function_exists('eca_hub_resolve_user_role')
            ? eca_hub_resolve_user_role($conn, (int) $row['id'], (string) ($row['role'] ?? ''))
            : eca_normalize_role((string) ($row['role'] ?? ''));
        if ($role !== 'super_admin') {
            continue;
        }
        return [
            'id' => (int) $row['id'],
            'name' => (string) ($row['name'] ?? 'Super Admin'),
            'email' => (string) ($row['email'] ?? ''),
            'role' => 'super_admin',
            'status' => 'ACTIVE',
            'eca_cpd_hub_preview' => true,
        ];
    }
    return null;
}

function eca_synthetic_hub_super_admin_from_cpd(): array
{
    $name = trim((string) ($_SESSION['full_name'] ?? ''));
    if ($name === '' || strcasecmp($name, 'SuperAdmin') === 0) {
        $name = 'Super Admin';
    }
    $email = trim((string) ($_SESSION['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = 'cpd.super@eca.co.sz';
    }
    return [
        'id' => 0,
        'name' => $name,
        'email' => $email,
        'role' => 'super_admin',
        'status' => 'ACTIVE',
        'eca_cpd_hub_preview' => true,
        'eca_synthetic' => true,
    ];
}

function eca_sso_hub_from_cpd_super_admin(): ?array
{
    // Production fail-closed: CPD→Hub preview SSO disabled unless explicitly allowed.
    if (function_exists('eca_portal_preview_allowed') && !eca_portal_preview_allowed()) {
        return null;
    }

    $existing = $_SESSION['eca_admin'] ?? null;
    if (is_array($existing) && $existing !== []) {
        return $existing;
    }
    if (strtoupper(trim((string) ($_SESSION['role'] ?? ''))) !== 'SUPPERADMIN') {
        return null;
    }
    $admin = eca_first_hub_super_admin(eca_admin_db()) ?: eca_synthetic_hub_super_admin_from_cpd();
    $_SESSION['eca_admin'] = $admin;
    $_SESSION['eca_hub_portal_preview'] = 'hub';
    return $admin;
}

function eca_admin_user(bool $refresh = false): ?array
{
    $sessionUser = $_SESSION['eca_admin'] ?? null;
    $isPreview = is_array($sessionUser) && !empty($sessionUser['eca_cpd_hub_preview']);
    $isSynthetic = is_array($sessionUser) && !empty($sessionUser['eca_synthetic']);
    if (!$sessionUser || !$refresh) {
        return $sessionUser;
    }

    // Synthetic CPD→Hub preview has no hub users row; keep the session as-is.
    if ($isSynthetic || ((int) ($sessionUser['id'] ?? 0) <= 0 && $isPreview)) {
        return $sessionUser;
    }

    $userId = (int) ($sessionUser['id'] ?? 0);
    $conn = eca_admin_db();
    if ($userId <= 0 || !$conn) {
        return null;
    }

    $fresh = eca_hub_reload_actor($conn, $userId);
    if (!$fresh) {
        unset($_SESSION['eca_admin']);
        return null;
    }

    $role = eca_normalize_role((string) ($fresh['role'] ?? ''));
    if (!eca_can('hub.access', $role)) {
        unset($_SESSION['eca_admin']);
        return null;
    }

    $_SESSION['eca_admin'] = [
        'id' => (int) $fresh['id'],
        'name' => (string) ($fresh['name'] ?? 'ECA user'),
        'email' => (string) ($fresh['email'] ?? ''),
        'role' => $role,
        'status' => strtoupper((string) ($fresh['status'] ?? 'ACTIVE')),
    ];
    if ($isPreview) {
        $_SESSION['eca_admin']['eca_cpd_hub_preview'] = true;
    }
    return $_SESSION['eca_admin'];
}

function eca_admin_require(string $permission = 'hub.access'): void
{
    eca_session_touch();
    eca_auth_no_store();
    if (!eca_admin_db()) {
        eca_error_page(503, 'Service temporarily unavailable', 'The administration database is unavailable. Please try again shortly.');
    }
    $user = eca_admin_user(true);
    if (!$user) {
        $user = eca_sso_hub_from_cpd_super_admin();
    }
    if (!$user) {
        header('Location: ' . eca_login_url('officer', (string) ($_SERVER['REQUEST_URI'] ?? '/admin/')));
        exit;
    }
    $role = eca_normalize_role((string) ($user['role'] ?? ''));
    if (!eca_can($permission, $role)) {
        eca_forbid();
    }
    if (
        function_exists('eca_super_admin_only_permissions')
        && in_array($permission, eca_super_admin_only_permissions(), true)
        && $role !== 'super_admin'
    ) {
        eca_forbid('Only a Super Admin can open this page.');
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
    $ok = is_string($token)
        && isset($_SESSION['eca_admin_csrf'])
        && hash_equals($_SESSION['eca_admin_csrf'], $token);
    if (
        !$ok
        && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST'
        && function_exists('eca_audit_security_event')
    ) {
        static $csrfAudited = false;
        if (!$csrfAudited) {
            $csrfAudited = true;
            // Never log the submitted token value.
            eca_audit_security_event('csrf.rejected', 'request', eca_audit_request_path(), [
                'has_token' => is_string($token) && $token !== '',
            ]);
        }
    }
    return $ok;
}

function eca_admin_has_hub_access(array $row): bool
{
    $role = eca_normalize_role((string) ($row['role'] ?? ''));
    return eca_can('hub.access', $role);
}

function eca_admin_password_ok(array $row, string $password): bool
{
    $stored = (string) ($row['password'] ?? '');
    if ($stored !== '') {
        $info = password_get_info($stored);
        if (!empty($info['algo']) && password_verify($password, $stored)) {
            return true;
        }
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
        $columns = 'id, name, email, password, role';
        if (eca_hub_users_has_column($conn, 'status')) {
            $columns .= ', status';
        }
        $stmt = $conn->prepare("SELECT $columns FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return null;
    }

    if (!$row || !eca_hub_user_is_active($row)) {
        return null;
    }

    $row['role'] = eca_hub_resolve_user_role(
        $conn,
        (int) ($row['id'] ?? 0),
        (string) ($row['role'] ?? 'admin')
    );

    if (!eca_admin_has_hub_access($row) || !eca_admin_password_ok($row, $password)) {
        return null;
    }

    return [
        'id' => $row['id'] ?? 0,
        'name' => $row['name'] ?? 'Administrator',
        'email' => $row['email'] ?? $email,
        'role' => eca_normalize_role((string) ($row['role'] ?? 'admin')),
        'status' => strtoupper((string) ($row['status'] ?? 'ACTIVE')),
    ];
}

function eca_admin_logout(): void
{
    $admin = eca_admin_user();
    if ($admin) {
        eca_audit('admin.logout', 'users', (string) ($admin['id'] ?? ''), ['email' => $admin['email'] ?? '']);
    }
    if (
        is_array($admin)
        && !empty($admin['eca_cpd_hub_preview'])
        && strtoupper(trim((string) ($_SESSION['role'] ?? ''))) === 'SUPPERADMIN'
    ) {
        unset($_SESSION['eca_admin'], $_SESSION['eca_hub_portal_preview']);
        return;
    }
    eca_destroy_auth_session();
}
