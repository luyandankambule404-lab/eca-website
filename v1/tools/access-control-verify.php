<?php
/**
 * LOCAL Super Admin vs Admin access-control checks. Does not touch production.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/includes/authz.php';
require_once dirname(__DIR__) . '/includes/audit.php';
require_once dirname(__DIR__) . '/includes/session.php';

$fail = 0;
$pass = 0;
function expect_true(bool $ok, string $label): void
{
    global $fail, $pass;
    if ($ok) {
        $pass++;
        echo "PASS {$label}\n";
        return;
    }
    $fail++;
    echo "FAIL {$label}\n";
}

if (!eca_rbac_is_local_database()) {
    fwrite(STDERR, "STOP: not local eca_local.\n");
    exit(2);
}

$conn = eca_rbac_pdo();
if (!$conn) {
    fwrite(STDERR, "No local database connection.\n");
    exit(1);
}

eca_revoke_super_admin_only_from_other_roles($conn);

$hasStatusCol = eca_hub_users_has_column($conn, 'status');
$user29Cols = $hasStatusCol ? 'id, email, role, status' : 'id, email, role';
$user29 = $conn->query('SELECT ' . $user29Cols . ' FROM users WHERE id = 29 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (empty($user29)) {
    echo "SKIP user 29 is not in this local database; Super Admin HTTP checks use a temporary Super Admin instead.\n";
} else {
    expect_true(true, 'user 29 exists');
    expect_true(eca_normalize_role((string) ($user29['role'] ?? '')) === 'super_admin', 'user 29 remains super_admin');
}

$adminRow = $conn->query("SELECT id, role FROM users WHERE LOWER(email) = 'admin@eca.co.sz' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
expect_true(eca_normalize_role((string) ($adminRow['role'] ?? '')) === 'admin', 'admin@eca.co.sz remains admin');

$matrix = [
    'admin' => [
        'hub.access' => true,
        'members.manage' => true,
        'applications.manage' => true,
        'companies.manage' => true,
        'certificates.manage' => true,
        'documents.manage' => true,
        'payments.manage' => true,
        'wellness.manage' => true,
        'content.manage' => true,
        'reports.view' => true,
        'audit.view' => true,
        'users.view' => false,
        'users.manage' => false,
        'users.promote_super_admin' => false,
        'roles.view' => false,
        'roles.manage' => false,
        'permissions.manage' => false,
        'security.view' => false,
        'security.manage' => false,
        'settings.view' => false,
        'settings.manage' => false,
        'hub.settings' => false,
    ],
    'super_admin' => [
        'hub.access' => true,
        'members.manage' => true,
        'users.view' => true,
        'users.manage' => true,
        'users.promote_super_admin' => true,
        'roles.manage' => true,
        'permissions.manage' => true,
        'security.view' => true,
        'settings.manage' => true,
        'audit.view' => true,
        'reports.view' => true,
    ],
    'membership_officer' => [
        'members.manage' => true,
        'users.view' => false,
        'payments.manage' => false,
    ],
    'finance_officer' => [
        'payments.manage' => true,
        'users.view' => false,
        'applications.manage' => false,
    ],
    'content_manager' => [
        'content.manage' => true,
        'users.view' => false,
        'members.manage' => false,
    ],
    'training_officer' => [
        'education.manage' => true,
        'users.view' => false,
        'payments.manage' => false,
    ],
];
foreach ($matrix as $role => $perms) {
    foreach ($perms as $permission => $allowed) {
        expect_true(eca_can($permission, $role) === $allowed, $role . ' ' . ($allowed ? 'has' : 'denied') . ' ' . $permission);
    }
}

expect_true(!eca_is_cpd_staff_role('super_admin'), 'Hub super_admin is not a CPD staff role');
expect_true(!in_array('super_admin', eca_cpd_staff_roles(), true), 'CPD staff list does not include Hub super_admin');
expect_true(eca_is_cpd_staff_role('SUPPERADMIN'), 'CPD SUPPERADMIN remains a CPD staff role');

$roleMap = [];
foreach ($conn->query('SELECT id, slug FROM roles')->fetchAll(PDO::FETCH_ASSOC) as $role) {
    $roleMap[eca_normalize_role((string) $role['slug'])] = (int) $role['id'];
}
expect_true(isset($roleMap['super_admin'], $roleMap['admin']), 'existing roles preserved');

$stamp = bin2hex(random_bytes(4));
$hash = password_hash('AccessCtrl!Local26', PASSWORD_DEFAULT);
$hasStatus = eca_hub_users_has_column($conn, 'status');
$created = [];

$insertUser = static function (string $email, string $role) use ($conn, $hash, $hasStatus, $roleMap, &$created): int {
    $sql = $hasStatus
        ? 'INSERT INTO users (name, email, password, role, status, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        : 'INSERT INTO users (name, email, password, role, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
    $params = $hasStatus
        ? ['Access Control Test', $email, $hash, $role, 'ACTIVE', in_array($role, ['admin', 'super_admin'], true) ? 1 : 0]
        : ['Access Control Test', $email, $hash, $role, in_array($role, ['admin', 'super_admin'], true) ? 1 : 0];
    $conn->prepare($sql)->execute($params);
    $id = (int) $conn->lastInsertId();
    $created[] = $id;
    if (!empty($roleMap[$role])) {
        $conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$id, $roleMap[$role]]);
    }
    return $id;
};

$adminId = $insertUser("acl.admin.{$stamp}@local.test", 'admin');
$saId = $insertUser("acl.sa.{$stamp}@local.test", 'super_admin');
$targetId = $insertUser("acl.target.{$stamp}@local.test", 'membership_officer');
$adminActor = eca_hub_reload_actor($conn, $adminId);
$saActor = eca_hub_reload_actor($conn, $saId);

$adminPromote = eca_assign_hub_roles($conn, $targetId, [$roleMap['super_admin']], $adminActor);
expect_true(empty($adminPromote['ok']), 'Admin privileged POST cannot promote Super Admin');

$adminPerms = eca_save_role_permissions($conn, $roleMap['admin'], ['users.manage', 'members.manage'], $adminActor);
expect_true(empty($adminPerms['ok']), 'Admin cannot change role permissions');

$adminCreate = eca_create_hub_user($conn, [
    'name' => 'Should Fail',
    'email' => "acl.fail.{$stamp}@local.test",
    'password' => 'AccessCtrl!Local26',
    'role' => 'admin',
], $adminActor);
expect_true(empty($adminCreate['ok']), 'Admin cannot create Hub users');

$adminSettings = eca_save_hub_settings($conn, ['organization_name' => 'Should Fail'], $adminActor);
expect_true(empty($adminSettings['ok']), 'Admin cannot change system settings');

$adminStatus = eca_set_hub_user_status($conn, $targetId, 'INACTIVE', $adminActor);
expect_true(empty($adminStatus['ok']), 'Admin cannot deactivate Hub users');

$adminEdit = eca_update_hub_user($conn, $targetId, ['name' => 'Should Fail'], $adminActor);
expect_true(empty($adminEdit['ok']), 'Admin cannot edit Hub users');

$saCreate = eca_create_hub_user($conn, [
    'name' => 'ACL Created',
    'email' => "acl.created.{$stamp}@local.test",
    'password' => 'AccessCtrl!Local26',
    'role' => 'content_manager',
], $saActor);
expect_true(!empty($saCreate['ok']), 'Super Admin can create Hub users');
if (!empty($saCreate['id'])) {
    $created[] = (int) $saCreate['id'];
}

$saPromote = eca_assign_hub_roles($conn, $targetId, [$roleMap['training_officer']], $saActor);
expect_true(!empty($saPromote['ok']), 'Super Admin can assign an operational role');
$restoreTarget = eca_assign_hub_roles($conn, $targetId, [$roleMap['membership_officer']], $saActor);
expect_true(!empty($restoreTarget['ok']), 'Super Admin can restore operational role');

$base = 'http://127.0.0.1:8765';
function eca_acl_http(string $method, string $url, array $opts = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $opts['follow'] ?? false,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_COOKIEJAR => $opts['jar'] ?? '',
        CURLOPT_COOKIEFILE => $opts['jar'] ?? '',
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['body'] ?? []);
    }
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $err = curl_error($ch);
    curl_close($ch);
    $header = is_string($raw) ? substr($raw, 0, $headerSize) : '';
    $body = is_string($raw) ? substr($raw, $headerSize) : '';
    return ['code' => $code, 'header' => $header, 'body' => $body, 'err' => $err];
}

function eca_acl_csrf(string $html): string
{
    if (preg_match('/name="csrf_token"[^>]*value="([^"]+)"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/value="([^"]+)"[^>]*name="csrf_token"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function eca_acl_login(string $base, string $dir, string $email, string $password): string
{
    $jar = $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_-]/i', '_', $email) . '.txt';
    $page = eca_acl_http('GET', $base . '/admin/login.php', ['jar' => $jar]);
    $csrf = eca_acl_csrf($page['body']);
    $res = eca_acl_http('POST', $base . '/admin/login.php', [
        'jar' => $jar,
        'follow' => false,
        'body' => [
            'email' => $email,
            'password' => $password,
            'csrf_token' => $csrf,
        ],
    ]);
    return ($res['code'] === 302 || $res['code'] === 200) ? $jar : '';
}

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eca-acl-' . bin2hex(random_bytes(3));
mkdir($tmpDir, 0700, true);
$adminEmail = "acl.admin.{$stamp}@local.test";
$saEmail = "acl.sa.{$stamp}@local.test";
$adminJar = eca_acl_login($base, $tmpDir, $adminEmail, 'AccessCtrl!Local26');
$saJar = eca_acl_login($base, $tmpDir, $saEmail, 'AccessCtrl!Local26');
expect_true($adminJar !== '', 'Admin HTTP login');
expect_true($saJar !== '', 'Super Admin HTTP login');

$adminAllowed = [
    '/admin/index.php',
    '/admin/members.php',
    '/admin/applications.php',
    '/admin/companies.php',
    '/admin/certificates.php',
    '/admin/documents.php',
    '/admin/payments.php',
    '/admin/reports.php',
    '/admin/audit.php',
];
$adminDenied = [
    '/admin/users.php',
    '/admin/roles.php',
    '/admin/security.php',
    '/admin/settings.php',
    '/admin/user-detail.php?id=29',
];
if ($adminJar) {
    foreach ($adminAllowed as $path) {
        $res = eca_acl_http('GET', $base . $path, ['jar' => $adminJar]);
        expect_true($res['code'] === 200, 'Admin allow ' . $path . ' => ' . $res['code']);
        if (in_array($path, ['/admin/index.php', '/admin/reports.php'], true)) {
            expect_true(!str_contains($res['body'], 'Super Admins'), 'Admin does not see Super Admins count on ' . $path);
        }
        if ($path === '/admin/index.php') {
            expect_true(!str_contains($res['body'], '/admin/users.php'), 'Admin dashboard has no Users tile');
            expect_true(!str_contains($res['body'], 'Security Centre'), 'Admin dashboard has no Security tile');
        }
    }
    foreach ($adminDenied as $path) {
        $res = eca_acl_http('GET', $base . $path, ['jar' => $adminJar]);
        expect_true(in_array($res['code'], [401, 403], true), 'Admin deny ' . $path . ' => ' . $res['code']);
    }
    $search = eca_acl_http('GET', $base . '/admin/search.php?q=Users', ['jar' => $adminJar]);
    expect_true($search['code'] === 200, 'Admin search page 200');
    expect_true(!str_contains($search['body'], '/admin/user-detail.php'), 'Admin search does not expose user-detail');
    $auditSecurity = eca_acl_http('GET', $base . '/admin/audit.php?action=user.promoted', ['jar' => $adminJar]);
    expect_true(in_array($auditSecurity['code'], [401, 403], true), 'Admin cannot filter security audit action => ' . $auditSecurity['code']);

    $page = eca_acl_http('GET', $base . '/admin/members.php', ['jar' => $adminJar]);
    $csrf = eca_acl_csrf($page['body']);
    $postUsers = eca_acl_http('POST', $base . '/admin/users.php', [
        'jar' => $adminJar,
        'body' => [
            'csrf_token' => $csrf,
            'action' => 'create_user',
            'name' => 'Should Fail',
            'email' => "acl.http.fail.{$stamp}@local.test",
            'password' => 'AccessCtrl!Local26',
            'role' => 'admin',
        ],
    ]);
    expect_true(in_array($postUsers['code'], [401, 403], true), 'Admin POST users.php denied => ' . $postUsers['code']);

    $postRoles = eca_acl_http('POST', $base . '/admin/roles.php', [
        'jar' => $adminJar,
        'body' => [
            'csrf_token' => $csrf,
            'role_id' => (string) $roleMap['admin'],
            'permissions' => ['users.manage', 'members.manage'],
        ],
    ]);
    expect_true(in_array($postRoles['code'], [401, 403], true), 'Admin POST roles.php denied => ' . $postRoles['code']);

    $postSettings = eca_acl_http('POST', $base . '/admin/settings.php', [
        'jar' => $adminJar,
        'body' => [
            'csrf_token' => $csrf,
            'organization_name' => 'Should Fail',
        ],
    ]);
    expect_true(in_array($postSettings['code'], [401, 403], true), 'Admin POST settings.php denied => ' . $postSettings['code']);

    $postUser29 = eca_acl_http('POST', $base . '/admin/user-detail.php?id=29', [
        'jar' => $adminJar,
        'body' => [
            'csrf_token' => $csrf,
            'action' => 'deactivate',
        ],
    ]);
    expect_true(in_array($postUser29['code'], [401, 403], true), 'Admin POST deactivate user 29 denied => ' . $postUser29['code']);
}

if ($saJar) {
    foreach (array_merge($adminAllowed, $adminDenied) as $path) {
        if (empty($user29) && str_contains($path, 'id=29')) {
            echo "SKIP Super Admin allow {$path} (no local user 29 fixture).\n";
            continue;
        }
        $res = eca_acl_http('GET', $base . $path, ['jar' => $saJar]);
        expect_true($res['code'] === 200, 'Super Admin allow ' . $path . ' => ' . $res['code']);
    }
    $saUsers = eca_acl_http('GET', $base . '/admin/users.php', ['jar' => $saJar]);
    expect_true(str_contains($saUsers['body'], 'Create administrative user'), 'Super Admin users page has create-user controls');
    $saDetail = eca_acl_http('GET', $base . '/admin/user-detail.php?id=29', ['jar' => $saJar]);
    if (empty($user29)) {
        echo "SKIP Super Admin user-detail.php?id=29 (no local user 29 fixture).\n";
    } else {
        expect_true($saDetail['code'] === 200 && (str_contains($saDetail['body'], 'Assign Hub roles') || str_contains($saDetail['body'], 'Reset password')), 'Super Admin can open user 29 controls');
    }
}

$still29Cols = $hasStatus ? 'id, role, status' : 'id, role';
$still29 = $conn->query('SELECT ' . $still29Cols . ' FROM users WHERE id = 29 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (empty($still29)) {
    echo "SKIP user 29 still absent after tests (no local fixture).\n";
} else {
    expect_true(eca_normalize_role((string) ($still29['role'] ?? '')) === 'super_admin', 'user 29 still super_admin after tests');
    $status29 = strtoupper((string) ($still29['status'] ?? 'ACTIVE'));
    expect_true($status29 === '' || $status29 === 'ACTIVE', 'user 29 still ACTIVE');
}

if ($created) {
    $ids = implode(',', array_map('intval', $created));
    $conn->prepare("DELETE FROM user_roles WHERE user_id IN ($ids)")->execute();
    $conn->prepare("DELETE FROM users WHERE id IN ($ids)")->execute();
}
$leftover = $conn->prepare("SELECT COUNT(*) FROM users WHERE email LIKE ?");
$leftover->execute(["acl.%{$stamp}@local.test"]);
expect_true((int) $leftover->fetchColumn() === 0, 'temporary test users removed');
$adminStill = $conn->prepare("SELECT role FROM users WHERE email = ? LIMIT 1");
$adminStill->execute(['admin@eca.co.sz']);
expect_true(eca_normalize_role((string) $adminStill->fetchColumn()) === 'admin', 'admin@eca.co.sz still admin after cleanup');

$actor29 = eca_hub_reload_actor($conn, 29);
if (empty($user29) || !$actor29) {
    echo "SKIP user 29 Super Admin actor checks (no local fixture).\n";
} else {
    expect_true(eca_actor_is_super_admin($actor29), 'user 29 actor is Super Admin');
    if (eca_is_final_active_super_admin($conn, 29)) {
        $denyDemote = eca_assign_hub_roles($conn, 29, [$roleMap['admin']], $actor29);
        expect_true(empty($denyDemote['ok']), 'final Super Admin cannot be demoted');
        if ($hasStatus) {
            $denyOff = eca_set_hub_user_status($conn, 29, 'INACTIVE', $actor29);
            expect_true(empty($denyOff['ok']), 'final Super Admin cannot be deactivated');
        }
    } else {
        expect_true(eca_count_active_super_admins($conn, 29) >= 1, 'another Super Admin exists besides user 29');
    }
}

echo "Passed={$pass} Failed={$fail}\n";
exit($fail > 0 ? 1 : 0);
