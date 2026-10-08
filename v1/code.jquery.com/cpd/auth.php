<?php
// auth.php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers.php";

function cpd_allowed_roles(): array
{
    return ['SUPPERADMIN', 'ADMIN', 'OFFICER', 'CONTRACTOR', 'LEARNER'];
}

function cpd_auth_db(): ?mysqli
{
    return function_exists('eca_local_portal_mysqli') ? eca_local_portal_mysqli(false) : eca_portal_mysqli(false);
}

function cpd_role_destination(string $role): string
{
    $role = strtoupper(trim($role));
    if (function_exists('eca_is_cpd_learner_role') && eca_is_cpd_learner_role($role)) {
        return '/cpd/contractor/dashboard.php';
    }
    if (function_exists('eca_officer_home') && eca_is_cpd_staff_role($role)) {
        return eca_officer_home($role);
    }
    if ($role === 'SUPPERADMIN') return '/cpd/admin/dashboard.php';
    if ($role === 'ADMIN') return '/cpd/admin/course_students.php';
    if ($role === 'OFFICER') return '/cpd/admin/applications.php';
    return '/cpd/contractor/dashboard.php';
}

function cpd_load_user_by_email(mysqli $conn, string $email): ?array
{
    $stmt = $conn->prepare(
        "SELECT id, role, email, full_name, password_hash, status
         FROM user WHERE LOWER(email) = LOWER(?) LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $user ?: null;
}

function cpd_member_row(mysqli $conn, string $identifier): ?array
{
    if (strpos($identifier, '@') !== false) {
        $stmt = $conn->prepare(
            'SELECT membership_number, full_name, email, password, role, status
             FROM userss WHERE LOWER(email) = LOWER(?) LIMIT 1'
        );
    } else {
        $stmt = $conn->prepare(
            'SELECT membership_number, full_name, email, password, role, status
             FROM userss WHERE membership_number = ? LIMIT 1'
        );
    }
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $identifier);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row ?: null;
}

function cpd_usable_password_hash(string $hash): string
{
    $hash = trim($hash);
    $info = password_get_info($hash);
    if ($hash !== '' && !empty($info['algo'])) {
        return $hash;
    }
    return password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
}

function cpd_ensure_contractor_from_member(mysqli $conn, array $member): ?array
{
    $email = trim((string) ($member['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $existing = cpd_load_user_by_email($conn, $email);
    if ($existing) {
        $role = strtoupper((string) ($existing['role'] ?? ''));
        if (function_exists('eca_is_cpd_staff_role') && eca_is_cpd_staff_role($role)) {
            return $existing;
        }
        return $existing;
    }
    $hash = cpd_usable_password_hash((string) ($member['password'] ?? $member['password_hash'] ?? ''));
    $name = trim((string) ($member['full_name'] ?? $member['name'] ?? '')) ?: 'ECA Member';
    $stmt = $conn->prepare(
        "INSERT INTO `user` (role, company_name, full_name, email, phone, password_hash, status, created_at)
         VALUES ('CONTRACTOR', ?, ?, ?, '', ?, 'ACTIVE', NOW())"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ssss', $name, $name, $email, $hash);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        return cpd_load_user_by_email($conn, $email);
    }
    return cpd_load_user_by_email($conn, $email);
}

function cpd_attach_session_keep_member(array $user): void
{
    if ((int) ($_SESSION['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = strtoupper((string) ($user['role'] ?? 'CONTRACTOR'));
    $_SESSION['full_name'] = (string) ($user['full_name'] ?? '');
    $_SESSION['email'] = (string) ($user['email'] ?? '');
}

function cpd_sso_attach_existing(?array $user): ?array
{
    if (!$user) {
        return null;
    }
    $status = strtoupper((string) ($user['status'] ?? ''));
    $role = strtoupper((string) ($user['role'] ?? ''));
    if (!in_array($status, ['ACTIVE', 'PENDING'], true) || !in_array($role, cpd_allowed_roles(), true)) {
        return null;
    }
    if ($status === 'PENDING') {
        $conn = cpd_auth_db();
        $id = (int) ($user['id'] ?? 0);
        if ($conn instanceof mysqli && $id > 0) {
            $stmt = $conn->prepare("UPDATE `user` SET status = 'ACTIVE' WHERE id = ? AND UPPER(status) = 'PENDING' LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        $user['status'] = 'ACTIVE';
        $status = 'ACTIVE';
    }
    $user['role'] = $role;
    unset($user['password_hash']);
    cpd_attach_session_keep_member($user);
    return $user;
}

function cpd_sso_from_member(array $member): ?array
{
    $conn = cpd_auth_db();
    if (!($conn instanceof mysqli)) {
        return null;
    }
    $email = trim((string) ($member['email'] ?? ''));
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $attached = cpd_sso_attach_existing(cpd_load_user_by_email($conn, $email));
        if ($attached) {
            return $attached;
        }
    }
    $identifier = $email !== '' ? $email : $membership;
    $row = $identifier !== '' ? cpd_member_row($conn, $identifier) : null;
    if ($row) {
        $user = cpd_ensure_contractor_from_member($conn, $row);
        return cpd_sso_attach_existing($user);
    }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $user = cpd_ensure_contractor_from_member($conn, [
            'email' => $email,
            'full_name' => (string) ($member['name'] ?? $member['full_name'] ?? 'ECA Member'),
            'password' => '',
        ]);
        return cpd_sso_attach_existing($user);
    }
    return null;
}

function cpd_load_staff_fallback(mysqli $conn): ?array
{
    foreach (['cpd.super@eca.co.sz', 'cpd.admin@eca.co.sz'] as $email) {
        $user = cpd_load_user_by_email($conn, $email);
        if ($user) {
            return $user;
        }
    }
    $result = $conn->query(
        "SELECT id, role, email, full_name, password_hash, status
         FROM `user`
         WHERE UPPER(role) = 'SUPPERADMIN' AND UPPER(status) IN ('ACTIVE', 'PENDING')
         ORDER BY id ASC
         LIMIT 1"
    );
    $row = $result ? $result->fetch_assoc() : null;
    return $row ?: null;
}

function cpd_sso_from_hub_user(array $admin): ?array
{
    $conn = cpd_auth_db();
    if (!($conn instanceof mysqli)) {
        return null;
    }

    $email = trim((string) ($admin['email'] ?? ''));
    $name = trim((string) ($admin['name'] ?? '')) ?: 'ECA user';
    $canOpen = function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals($admin);

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $attached = cpd_sso_attach_existing(cpd_load_user_by_email($conn, $email));
        if ($attached) {
            return $attached;
        }
    }

    if ($canOpen) {
        $attached = cpd_sso_attach_existing(cpd_load_staff_fallback($conn));
        if ($attached) {
            return $attached;
        }
    }

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return cpd_sso_attach_existing(cpd_ensure_contractor_from_member($conn, [
            'email' => $email,
            'full_name' => $name,
            'password' => '',
        ]));
    }

    if ($canOpen) {
        $previewEmail = 'hub.preview+' . max(1, (int) ($admin['id'] ?? 0)) . '@local.test';
        return cpd_sso_attach_existing(cpd_ensure_contractor_from_member($conn, [
            'email' => $previewEmail,
            'full_name' => $name,
            'password' => '',
        ]));
    }

    return null;
}

function cpd_sso_from_signed_in(): ?array
{
    $current = cpd_current_user(true);
    if ($current) {
        return $current;
    }

    // Production fail-closed: no hub/member/staff preview SSO unless explicitly allowed.
    if (function_exists('eca_portal_preview_allowed') && !eca_portal_preview_allowed()) {
        return null;
    }

    $canOpen = (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals())
        || (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals());

    // Prefer hub Super Admin identity when browsing portals from Admin Hub.
    if ($canOpen && !empty($_SESSION['eca_admin']) && is_array($_SESSION['eca_admin'])) {
        $user = cpd_sso_from_hub_user($_SESSION['eca_admin']);
        if ($user) {
            return $user;
        }
    }

    if (!empty($_SESSION['eca_member']) && is_array($_SESSION['eca_member'])) {
        $user = cpd_sso_from_member($_SESSION['eca_member']);
        if ($user) {
            return $user;
        }
    }

    if (!empty($_SESSION['eca_admin']) && is_array($_SESSION['eca_admin'])) {
        return cpd_sso_from_hub_user($_SESSION['eca_admin']);
    }

    if ($canOpen) {
        $conn = cpd_auth_db();
        if ($conn instanceof mysqli) {
            return cpd_sso_attach_existing(cpd_load_staff_fallback($conn));
        }
    }

    return null;
}

function cpd_user_ok(?array $user, string $password): bool
{
    return $user
        && strtoupper((string) ($user['status'] ?? '')) === 'ACTIVE'
        && in_array(strtoupper((string) ($user['role'] ?? '')), cpd_allowed_roles(), true)
        && password_verify($password, (string) ($user['password_hash'] ?? ''));
}

function cpd_authenticate(string $email, string $password, bool $allowMemberFallback = true): ?array
{
    $conn = cpd_auth_db();
    $identifier = trim($email);
    if (!($conn instanceof mysqli) || $identifier === '' || $password === '') {
        return null;
    }

    $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
        ? cpd_load_user_by_email($conn, $identifier)
        : null;

    if (!cpd_user_ok($user, $password)) {
        if (!$allowMemberFallback) {
            return null;
        }
        $member = cpd_member_row($conn, $identifier);
        $memberHash = (string) ($member['password'] ?? '');
        $memberOk = $member
            && strtoupper((string) ($member['status'] ?? '')) === 'ACTIVE'
            && strtoupper((string) ($member['role'] ?? '')) === 'MEMBER'
            && $memberHash !== ''
            && password_verify($password, $memberHash);
        $user = $memberOk ? cpd_ensure_contractor_from_member($conn, $member) : null;
        if (!cpd_user_ok($user, $password)) {
            return null;
        }
    }

    $user['role'] = strtoupper((string) $user['role']);
    unset($user['password_hash']);
    return $user;
}

function cpd_start_authenticated_session(array $user): void
{
    session_regenerate_id(true);
    unset($_SESSION['eca_admin'], $_SESSION['eca_member']);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = strtoupper((string) $user['role']);
    $_SESSION['full_name'] = (string) ($user['full_name'] ?? '');
    $_SESSION['email'] = (string) ($user['email'] ?? '');
}

function cpd_current_user(bool $refresh = true): ?array
{
    $conn = cpd_auth_db();
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }
    if (!$refresh) {
        return [
            'id' => $userId,
            'role' => strtoupper((string) ($_SESSION['role'] ?? '')),
            'full_name' => (string) ($_SESSION['full_name'] ?? ''),
            'email' => (string) ($_SESSION['email'] ?? ''),
        ];
    }
    if (!($conn instanceof mysqli)) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT id, role, email, full_name, status FROM user WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    $role = strtoupper((string) ($user['role'] ?? ''));
    if (
        !$user
        || strtoupper((string) ($user['status'] ?? '')) !== 'ACTIVE'
        || !in_array($role, cpd_allowed_roles(), true)
    ) {
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['full_name'], $_SESSION['email']);
        return null;
    }

    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = (string) ($user['full_name'] ?? '');
    $_SESSION['email'] = (string) ($user['email'] ?? '');
    $user['role'] = $role;
    return $user;
}

function require_login(): array
{
    if (function_exists('eca_session_touch')) {
        eca_session_touch();
    }
    eca_auth_no_store();
    if (!(cpd_auth_db() instanceof mysqli)) {
        eca_error_page(503, 'Service temporarily unavailable', 'The authentication database is unavailable. Please try again shortly.');
    }
    $user = cpd_current_user(true);
    $canOpenPortals = (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals())
        || (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals());
    if (!$user && $canOpenPortals) {
        if (!function_exists('eca_sso_hub_from_cpd_super_admin')) {
            require_once dirname(__DIR__, 2) . '/admin/auth.php';
        }
        if (function_exists('eca_sso_hub_from_cpd_super_admin')) {
            eca_sso_hub_from_cpd_super_admin();
        }
        $user = cpd_sso_from_signed_in();
    }
    if (!$user) {
        $next = (string) ($_SERVER['REQUEST_URI'] ?? '/cpd/login.php');
        $path = strtolower((string) (parse_url($next, PHP_URL_PATH) ?? $next));
        if (
            !empty($_SESSION['eca_admin'])
            && function_exists('eca_can')
            && (str_starts_with($path, '/cpd/admin') || str_starts_with($path, '/cpd/officer'))
            && eca_can('cpd.view', (string) ($_SESSION['eca_admin']['role'] ?? ''))
            && !(function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals())
        ) {
            header('Location: /admin/cpd.php');
            exit;
        }
        $portal = function_exists('eca_login_portal_for_path')
            ? (eca_login_portal_for_path($next) ?: 'learner')
            : 'learner';
        header('Location: ' . (function_exists('eca_login_url') ? eca_login_url($portal, $next) : '/cpd/login.php'));
        exit;
    }
    return $user;
}

function require_role($roles)
{
    $allowedList = is_array($roles)
        ? array_map(static fn ($role): string => strtoupper(trim((string) $role)), $roles)
        : [strtoupper(trim((string) $roles))];
    $wantsLearnerPortal = (bool) array_intersect($allowedList, ['CONTRACTOR', 'LEARNER']);
    $wantsStaffOnly = (bool) array_intersect($allowedList, ['SUPPERADMIN', 'ADMIN', 'OFFICER']);
    if ($wantsLearnerPortal && !$wantsStaffOnly) {
        cpd_sso_from_signed_in();
    } elseif (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals()) {
        cpd_sso_from_signed_in();
    }

    $user = require_login();
    if (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals()) {
        return;
    }
    $userRole = strtoupper((string) $user['role']);
    if (
        function_exists('eca_is_cpd_learner_role')
        && eca_is_cpd_learner_role($userRole)
        && !array_intersect($allowedList, eca_cpd_learner_roles())
    ) {
        header('Location: /cpd/contractor/dashboard.php');
        exit;
    }

    if (is_array($roles)) {
        $allowed = array_map(function ($r) {
            return strtoupper(trim($r));
        }, $roles);

        if ($userRole === 'SUPPERADMIN' && array_intersect($allowed, ['SUPPERADMIN', 'ADMIN', 'OFFICER'])) {
            return;
        }
        if ($userRole === 'LEARNER' && in_array('CONTRACTOR', $allowed, true)) {
            return;
        }
        if (
            in_array('CONTRACTOR', $allowed, true)
            && function_exists('eca_is_cpd_staff_role')
            && eca_is_cpd_staff_role($userRole)
        ) {
            return;
        }
        if (!in_array($userRole, $allowed, true)) {
            eca_forbid();
        }
    } else {
        $required = strtoupper(trim($roles));
        if ($userRole === 'SUPPERADMIN' && in_array($required, ['SUPPERADMIN', 'ADMIN', 'OFFICER'], true)) {
            return;
        }
        if ($userRole === 'LEARNER' && $required === 'CONTRACTOR') {
            return;
        }
        if (
            $required === 'CONTRACTOR'
            && function_exists('eca_is_cpd_staff_role')
            && eca_is_cpd_staff_role($userRole)
        ) {
            return;
        }
        if ($userRole !== $required) {
            eca_forbid();
        }
    }
}