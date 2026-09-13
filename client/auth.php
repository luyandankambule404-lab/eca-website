<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

function eca_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function eca_db(): ?PDO
{
    static $conn = false;
    if ($conn === false) {
        $db = new Database();
        $conn = $db->getConnection(false);
    }
    return $conn instanceof PDO ? $conn : null;
}

function eca_current_member(): ?array
{
    return $_SESSION['eca_member'] ?? null;
}

function eca_preview_member(): array
{
    return [
        'name' => 'Sample Contractor',
        'registered_name' => 'Sample Construction (Pty) Ltd',
        'membership' => 'ECA0001',
        'email' => 'member@example.com',
        'phone' => '+268 2404 4987',
        'region' => 'Hhohho',
        'classification' => 'Building',
        'status' => 'Active',
        'address' => 'Suite 40, Cooper Centre, Mbabane',
        'expiry' => '31 Mar 2027',
        'joined' => '2018',
        'cpd_points' => '8.5',
        'cpd_target' => '12',
    ];
}

function eca_portal_member(): array
{
    $member = eca_current_member();
    if ($member) {
        return $member + [
            'expiry' => $member['expiry'] ?? '',
            'joined' => $member['joined'] ?? '',
            'cpd_points' => $member['cpd_points'] ?? '0',
            'cpd_target' => $member['cpd_target'] ?? '12',
        ];
    }

    if (php_sapi_name() === 'cli-server') {
        return eca_preview_member();
    }

    eca_require_member();
    return eca_current_member() ?? eca_preview_member();
}

function eca_require_member(): void
{
    if (!eca_current_member()) {
        header('Location: index.php');
        exit;
    }
}

function eca_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
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
        if (hash_equals($stored, $password)) {
            return true;
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
        'membership' => eca_pick($row, ['membership_number', 'MembershipNumber', 'membership'], $membership),
        'name' => eca_pick($row, ['TradingName', 'CompanyRegistrationName', 'company_name', 'full_name', 'fullName'], 'ECA Member'),
        'registered_name' => eca_pick($row, ['CompanyRegistrationName', 'company_name'], ''),
        'email' => eca_pick($row, ['EmailAddress', 'email', 'Email'], ''),
        'phone' => eca_pick($row, ['Cellphone', 'telephone', 'phone', 'Phone'], ''),
        'region' => eca_pick($row, ['Region', 'region'], ''),
        'classification' => eca_pick($row, ['Clasification', 'classification', 'discipline'], ''),
        'status' => eca_pick($row, ['Status', 'status', 'active'], ''),
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
    if (!$row) {
        $row = eca_query_row($conn, 'SELECT * FROM user WHERE membership_number = ? LIMIT 1', [$membership]);
    }
    if (!$row) {
        $row = eca_query_row($conn, 'SELECT * FROM tbl_client WHERE MembershipNumber = ? LIMIT 1', [$membership]);
    }
    if (!$row && strpos($membership, '@') !== false) {
        $row = eca_query_row($conn, 'SELECT * FROM userss WHERE email = ? LIMIT 1', [$membership]);
        if (!$row) {
            $row = eca_query_row($conn, 'SELECT * FROM user WHERE email = ? LIMIT 1', [$membership]);
        }
    }
    if (!$row) {
        return null;
    }

    $email = eca_pick($row, ['email', 'EmailAddress', 'Email']);
    if ($email !== '') {
        $loginRow = eca_query_row($conn, 'SELECT * FROM userss WHERE email = ? LIMIT 1', [$email]);
        if (!$loginRow) {
            $loginRow = eca_query_row($conn, 'SELECT * FROM user WHERE email = ? LIMIT 1', [$email]);
        }
        if ($loginRow) {
            $row = array_merge($row, $loginRow);
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
    return eca_normalize_member($row, $membership);
}
