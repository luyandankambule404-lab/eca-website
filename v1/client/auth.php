<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/authz.php';

if (!function_exists('eca_h')) {
    function eca_h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function eca_db(): ?PDO
{
    static $conn = false;
    if ($conn === false) {
        $conn = eca_local_portal_pdo(false);
        if (!$conn) {
            $db = new Database();
            $conn = $db->getConnection(false);
        }
    }
    return $conn instanceof PDO ? $conn : null;
}

function eca_first_preview_member(?PDO $conn): ?array
{
    if (!$conn) {
        return null;
    }
    foreach (['ECA-1001'] as $identifier) {
        $row = eca_find_member($conn, $identifier);
        if (!$row) {
            continue;
        }
        $status = strtoupper(trim((string) eca_pick($row, ['status', 'Status'], '')));
        $role = strtoupper(trim((string) eca_pick($row, ['role'], '')));
        if ($status === 'ACTIVE' && $role === 'MEMBER') {
            return eca_normalize_member($row, $identifier);
        }
    }
    try {
        $stmt = $conn->query("SELECT * FROM userss WHERE UPPER(status) = 'ACTIVE' AND UPPER(role) = 'MEMBER' ORDER BY id ASC LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    } catch (Throwable $e) {
        $row = null;
    }
    if (!$row) {
        return null;
    }
    $membership = (string) eca_pick($row, ['membership_number', 'MembershipNumber', 'membership'], '');
    $full = $membership !== '' ? eca_find_member($conn, $membership) : $row;
    return eca_normalize_member($full ?: $row, $membership);
}

function eca_synthetic_preview_member(): array
{
    return [
        'id' => 0,
        'client_id' => 0,
        'membership' => 'ECA-PREVIEW',
        'name' => 'Portal Preview',
        'registered_name' => 'Portal Preview',
        'email' => 'preview.member@local.test',
        'phone' => '',
        'region' => '',
        'classification' => '',
        'status' => 'ACTIVE',
        'standing' => 'ACTIVE',
        'membership_type' => 'Preview',
        'application_status' => '',
        'application_reference' => '',
        'role' => 'MEMBER',
        'address' => '',
        'eca_synthetic' => true,
    ];
}

function eca_sso_member_from_hub(): ?array
{
    // Production fail-closed: no synthetic / preview member SSO unless explicitly allowed.
    if (function_exists('eca_portal_preview_allowed') && !eca_portal_preview_allowed()) {
        return null;
    }

    $canPreview = (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals())
        || (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals());
    if (!$canPreview) {
        return null;
    }

    // Keep Admin Hub session available so "Back to Admin Hub" / Leave portal always work.
    if (empty($_SESSION['eca_admin'])) {
        if (!function_exists('eca_sso_hub_from_cpd_super_admin')) {
            require_once __DIR__ . '/../admin/auth.php';
        }
        if (function_exists('eca_sso_hub_from_cpd_super_admin')) {
            eca_sso_hub_from_cpd_super_admin();
        }
    }

    $existing = eca_current_member(true);
    if ($existing) {
        $_SESSION['eca_hub_portal_preview'] = 'member';
        return $existing;
    }
    $member = eca_first_preview_member(eca_db()) ?: eca_synthetic_preview_member();
    $_SESSION['eca_member'] = $member;
    $_SESSION['eca_hub_portal_preview'] = 'member';
    return $member;
}

function eca_current_member(bool $refresh = false): ?array
{
    $member = $_SESSION['eca_member'] ?? null;
    if (!$member || !$refresh) {
        return $member;
    }

    if (!empty($member['eca_synthetic']) || (string) ($member['membership'] ?? '') === 'ECA-PREVIEW') {
        return $member;
    }

    $conn = eca_db();
    if (!$conn) {
        return null;
    }
    $identifier = (string) ($member['membership'] ?? '');
    $fresh = eca_find_member($conn, $identifier);
    if (!$fresh) {
        unset($_SESSION['eca_member']);
        return null;
    }

    $status = strtoupper(trim((string) eca_pick($fresh, ['status', 'Status'], '')));
    $role = strtoupper(trim((string) eca_pick($fresh, ['role'], '')));
    if ($status !== 'ACTIVE' || $role !== 'MEMBER') {
        unset($_SESSION['eca_member']);
        return null;
    }

    $_SESSION['eca_member'] = eca_normalize_member($fresh, $identifier);
    return $_SESSION['eca_member'];
}

function eca_portal_member(): array
{
    $member = eca_current_member(true);
    if ($member) {
        return $member + [
            'expiry' => $member['expiry'] ?? '',
            'joined' => $member['joined'] ?? '',
            'cpd_points' => $member['cpd_points'] ?? '0',
            'cpd_target' => $member['cpd_target'] ?? '12',
        ];
    }

    eca_require_member();
    return eca_current_member() ?? [];
}

function eca_require_member(): void
{
    eca_session_touch();
    eca_auth_no_store();
    if (!eca_db()) {
        eca_error_page(503, 'Service temporarily unavailable', 'The membership database is unavailable. Please try again shortly.');
    }
    if (empty($_SESSION['eca_admin']) && !function_exists('eca_sso_hub_from_cpd_super_admin')) {
        require_once __DIR__ . '/../admin/auth.php';
    }
    if (eca_current_member(true) || eca_sso_member_from_hub()) {
        return;
    }
    $next = eca_login_next($_SERVER['REQUEST_URI'] ?? '/client/dashboard.php', 'member');
    header('Location: ' . eca_login_url('member', $next));
    exit;
}

function eca_logout(): void
{
    eca_destroy_auth_session();
}

function eca_pick(array $row, array $keys, $default = '')
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return $default;
}

function eca_csrf_token(): string
{
    if (empty($_SESSION['eca_csrf'])) {
        $_SESSION['eca_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['eca_csrf'];
}

function eca_csrf_ok(?string $token): bool
{
    return is_string($token) && isset($_SESSION['eca_csrf']) && hash_equals($_SESSION['eca_csrf'], $token);
}

function eca_password_ok(array $row, string $password): bool
{
    $keys = ['password_hash', 'password', 'Password', 'pass', 'member_password'];
    foreach ($keys as $key) {
        if (empty($row[$key])) {
            continue;
        }
        $stored = (string) $row[$key];
        $info = password_get_info($stored);
        if (!empty($info['algo'])) {
            if (password_verify($password, $stored)) {
                return true;
            }
            continue;
        }
    }
    return false;
}

function eca_query_row(PDO $conn, string $sql, array $params): ?array
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function eca_normalize_member(array $row, string $membership): array
{
    return [
        'id' => eca_pick($row, ['id', 'user_id', 'client_id'], 0),
        'client_id' => eca_pick($row, ['client_id'], 0),
        'membership' => eca_pick($row, ['membership_number', 'MembershipNumber', 'membership'], $membership),
        'name' => eca_pick($row, ['TradingName', 'CompanyRegistrationName', 'company_name', 'full_name', 'fullName', 'name'], 'ECA Member'),
        'registered_name' => eca_pick($row, ['CompanyRegistrationName', 'company_name'], ''),
        'email' => eca_pick($row, ['EmailAddress', 'email', 'Email'], ''),
        'phone' => eca_pick($row, ['Cellphone', 'telephone', 'phone', 'Phone'], ''),
        'region' => eca_pick($row, ['Region', 'region'], ''),
        'classification' => eca_pick($row, ['Clasification', 'classification', 'discipline'], ''),
        'status' => eca_pick($row, ['Status', 'status', 'active'], ''),
        'standing' => eca_pick($row, ['active'], ''),
        'membership_type' => eca_pick($row, ['Status'], ''),
        'application_status' => eca_pick($row, ['application_status'], ''),
        'application_reference' => eca_pick($row, ['application_reference'], ''),
        'role' => strtoupper((string) eca_pick($row, ['role'], '')),
        'address' => eca_pick($row, ['address', 'Address'], ''),
    ];
}

function eca_find_member(PDO $conn, string $membership): ?array
{
    $membership = trim($membership);
    if ($membership === '') {
        return null;
    }

    $row = eca_query_row($conn, 'SELECT * FROM userss WHERE membership_number = ? LIMIT 1', [$membership]);
    if (!$row && strpos($membership, '@') !== false) {
        $row = eca_query_row($conn, 'SELECT * FROM userss WHERE LOWER(email) = LOWER(?) LIMIT 1', [$membership]);
    }
    if (!$row) {
        return null;
    }

    $email = eca_pick($row, ['email', 'EmailAddress', 'Email']);
    if ($email !== '') {
        $loginRow = eca_query_row($conn, 'SELECT * FROM userss WHERE email = ? LIMIT 1', [$email]);
        if ($loginRow) {
            $row = array_merge($row, $loginRow);
        }
    }

    $membershipNo = eca_pick($row, ['membership_number', 'MembershipNumber', 'membership'], $membership);
    if ($membershipNo !== '') {
        $client = eca_query_row($conn, 'SELECT * FROM tbl_client WHERE MembershipNumber = ? LIMIT 1', [$membershipNo]);
        if ($client) {
            if (empty($client['client_id']) && !empty($client['id'])) {
                $client['client_id'] = $client['id'];
            }
            $row = array_merge($client, $row);
        }
    }

    return $row;
}

function eca_attempt_login(PDO $conn, string $membership, string $password): ?array
{
    $row = eca_find_member($conn, $membership);
    if (!$row || !eca_password_ok($row, $password)) {
        return null;
    }

    $status = strtoupper(trim((string) eca_pick($row, ['status', 'Status'], '')));
    $role = strtoupper(trim((string) eca_pick($row, ['role'], '')));
    if ($status !== 'ACTIVE' || $role !== 'MEMBER') {
        return null;
    }

    return eca_normalize_member($row, $membership);
}
