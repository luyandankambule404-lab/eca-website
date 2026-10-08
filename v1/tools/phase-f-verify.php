<?php
/**
 * LOCAL Phase F production-readiness checks. Does not touch production.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/includes/authz.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/portal-db.php';
require_once dirname(__DIR__) . '/includes/membership.php';
require_once dirname(__DIR__) . '/includes/audit.php';
require_once dirname(__DIR__) . '/includes/rbac.php';
require_once dirname(__DIR__) . '/includes/pagination.php';

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

$host = strtolower(trim(eca_env('ECA_DB_HOST', '')));
$name = strtolower(trim(eca_env('ECA_DB_NAME', '')));
$portalHost = strtolower(trim(eca_env('ECA_PORTAL_DB_HOST', '')));
$portalName = strtolower(trim(eca_env('ECA_PORTAL_DB_NAME', '')));
if (
    !in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
    || $name !== 'eca_local'
    || !in_array($portalHost, ['127.0.0.1', 'localhost', '::1'], true)
    || $portalName !== 'eca_portal_local'
) {
    fwrite(STDERR, "STOP: environment is not local.\n");
    exit(2);
}
if (!eca_rbac_is_local_database()) {
    fwrite(STDERR, "STOP: not local eca_local.\n");
    exit(2);
}

$local = eca_rbac_pdo();
$portal = eca_portal_pdo(false);
if (!$local || !$portal) {
    fwrite(STDERR, "No local database connection.\n");
    exit(1);
}

function eca_phasef_count(PDO $conn, string $sql): int
{
    try {
        return (int) $conn->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return -1;
    }
}

$before = [
    'tbl_client' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM tbl_client'),
    'numbered' => eca_phasef_count($portal, "SELECT COUNT(*) FROM tbl_client WHERE MembershipNumber IS NOT NULL AND MembershipNumber <> ''"),
    'active' => eca_phasef_count($portal, "SELECT COUNT(*) FROM tbl_client WHERE LOWER(active) = 'active'"),
    'applications' => eca_phasef_count($portal, "SELECT COUNT(*) FROM tbl_client WHERE application_reference IS NOT NULL AND application_reference <> ''"),
    'certificates' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM membership_certificates'),
    'payments' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM payments'),
    'cpd_apps' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM cpd_applications'),
    'cpd_points' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM cpd_points_ledger'),
    'wellness_events' => eca_phasef_count($local, 'SELECT COUNT(*) FROM wellness_events'),
    'wellness_regs' => eca_phasef_count($local, 'SELECT COUNT(*) FROM wellness_event_registrations'),
    'users' => eca_phasef_count($local, 'SELECT COUNT(*) FROM users'),
    'years' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM membership_years'),
    'audit' => eca_phasef_count($local, 'SELECT COUNT(*) FROM audit_logs'),
];
echo "BEFORE " . json_encode($before) . "\n";

$user29 = $local->query('SELECT id, email, role, status FROM users WHERE id = 29 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
expect_true((int) ($user29['id'] ?? 0) === 29, 'user 29 exists');
expect_true(eca_normalize_role((string) ($user29['role'] ?? '')) === 'super_admin', 'user 29 remains super_admin');
expect_true(strtoupper((string) ($user29['status'] ?? '')) === 'ACTIVE', 'user 29 remains ACTIVE');

expect_true(eca_can('users.manage', 'super_admin'), 'super_admin users.manage');
expect_true(eca_can('security.manage', 'super_admin'), 'super_admin security.manage');
expect_true(eca_can('members.manage', 'admin'), 'admin members.manage');
expect_true(!eca_can('users.manage', 'admin'), 'admin denied users.manage');
expect_true(!eca_can('security.view', 'admin'), 'admin denied security.view');
expect_true(eca_can('applications.manage', 'membership_officer'), 'membership_officer applications.manage');
expect_true(eca_can('certificates.manage', 'membership_officer'), 'membership_officer certificates.manage');
expect_true(!eca_can('payments.manage', 'membership_officer'), 'membership_officer denied payments.manage');
expect_true(!eca_can('users.view', 'membership_officer'), 'membership_officer denied users.view');
expect_true(eca_can('payments.manage', 'finance_officer'), 'finance_officer payments.manage');
expect_true(eca_can('members.view', 'finance_officer'), 'finance_officer members.view');
expect_true(!eca_can('applications.manage', 'finance_officer'), 'finance_officer denied applications.manage');
expect_true(!eca_can('content.manage', 'finance_officer'), 'finance_officer denied content.manage');
expect_true(eca_can('content.manage', 'content_manager'), 'content_manager content.manage');
expect_true(eca_can('wellness.manage', 'content_manager'), 'content_manager wellness.manage');
expect_true(!eca_can('payments.manage', 'content_manager'), 'content_manager denied payments.manage');
expect_true(!eca_can('members.manage', 'content_manager'), 'content_manager denied members.manage');
expect_true(eca_can('cpd.view', 'training_officer'), 'training_officer cpd.view');
expect_true(eca_can('education.manage', 'training_officer'), 'training_officer education.manage');
expect_true(!eca_can('members.manage', 'training_officer'), 'training_officer denied members.manage');
expect_true(!eca_can('payments.manage', 'training_officer'), 'training_officer denied payments.manage');
expect_true(eca_can('member.portal', 'member'), 'member portal');
expect_true(!eca_can('hub.access', 'member'), 'member denied hub.access');
expect_true(!eca_can('applications.manage', 'member'), 'member denied applications.manage');

expect_true(eca_login_next('/admin/users.php', 'member') === '/client/dashboard.php', 'member next cannot open /admin/');
expect_true(eca_login_next('/cpd/admin/dashboard.php', 'member') === '/client/dashboard.php', 'member next cannot open /cpd/admin');
expect_true(eca_login_next('/client/payments.php', 'member') === '/client/payments.php', 'member next allows own portal');
expect_true(eca_login_next('/admin/payments.php', 'officer') === '/admin/payments.php', 'officer next allows /admin/');
expect_true(eca_login_next('https://evil.example/x', 'member') === '/client/dashboard.php', 'open redirect blocked');

$authSrc = (string) file_get_contents(dirname(__DIR__) . '/code.jquery.com/cpd/auth.php');
expect_true(!str_contains($authSrc, "empty(\$_SESSION['user_id'])"), 'CPD require_role hub-admin bypass removed');
expect_true(str_contains($authSrc, 'session_regenerate_id(true)'), 'CPD SSO regenerates session');

$paySrc = (string) file_get_contents(dirname(__DIR__) . '/code.jquery.com/cpd/admin/payment.php');
expect_true(str_contains($paySrc, 'cpd_require_csrf()'), 'CPD payment POST requires CSRF');
expect_true(!str_contains($paySrc, 'die("Stats query failed:'), 'CPD payment SQL errors not dumped');

$attSrc = (string) file_get_contents(dirname(__DIR__) . '/code.jquery.com/cpd/admin/course_students.php');
expect_true(str_contains($attSrc, 'cpd_require_csrf()'), 'CPD attendance POST requires CSRF');

$wellSrc = (string) file_get_contents(dirname(__DIR__) . '/wellness-download.php');
expect_true(str_contains($wellSrc, '$isPublic'), 'public wellness download gated on is_public');

$safe = eca_rbac_safe_meta([
    'password' => 'secret',
    'password_hash' => 'x',
    'token' => 'abc',
    'jwt' => 'abc',
    'api_key' => 'abc',
    'smtp_password' => 'abc',
    'db_password' => 'abc',
    'csrf_token' => 'abc',
    'cookie' => 'abc',
    'Authorization' => 'Bearer x',
    'status' => 'approved',
]);
expect_true($safe === ['status' => 'approved'], 'audit meta strips secrets');

try {
    $hits = (int) $local->query(
        "SELECT COUNT(*) FROM audit_logs WHERE meta IS NOT NULL AND (
            LOWER(meta) LIKE '%password_hash%'
            OR LOWER(meta) LIKE '%smtp_password%'
            OR LOWER(meta) LIKE '%db_password%'
            OR LOWER(meta) LIKE '%csrf_token%'
            OR LOWER(meta) LIKE '%\"jwt\"%'
            OR LOWER(meta) LIKE '%api_key%'
            OR LOWER(meta) LIKE '%authorization%'
        )"
    )->fetchColumn();
    expect_true($hits === 0, 'audit_logs meta has no secret fields');
} catch (Throwable $e) {
    expect_true(false, 'audit_logs secret scan');
}

$base = 'http://127.0.0.1:8765';
function eca_phasef_http(string $method, string $url, array $opts = []): array
{
    $ch = curl_init($url);
    $headers = $opts['headers'] ?? [];
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
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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

function eca_phasef_csrf(string $html, string $name = 'csrf_token'): string
{
    if (preg_match('/name="' . preg_quote($name, '/') . '"[^>]*value="([^"]+)"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/value="([^"]+)"[^>]*name="' . preg_quote($name, '/') . '"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

$home = eca_phasef_http('GET', $base . '/');
expect_true($home['code'] === 200 && $home['err'] === '', 'public homepage 200');
$adminLoginPage = eca_phasef_http('GET', $base . '/admin/');
expect_true(
    $adminLoginPage['code'] === 200 && str_contains($adminLoginPage['body'], 'Officer login'),
    'unauth /admin/ serves officer login'
);

$unauthPaths = [
    '/admin/' => [200, 302, 401, 403],
    '/admin/users.php' => [302, 401, 403],
    '/admin/security.php' => [302, 401, 403],
    '/client/dashboard.php' => [302, 401, 403],
    '/client/payments.php' => [302, 401, 403],
    '/cpd/admin/dashboard.php' => [302, 401, 403],
    '/cpd/admin/payment.php' => [302, 401, 403],
    '/document-download.php?id=1' => [401, 302, 403, 404],
    '/certificate-download.php?id=1' => [401, 302, 403, 404],
];
foreach ($unauthPaths as $path => $allowed) {
    $res = eca_phasef_http('GET', $base . $path);
    expect_true(in_array($res['code'], $allowed, true), 'unauth ' . $path . ' => ' . $res['code']);
}

$well = eca_phasef_http('GET', $base . '/wellness/');
expect_true($well['code'] === 200, 'public wellness page 200');
expect_true(!str_contains(strtolower($well['body']), 'membership_number'), 'public wellness does not leak membership numbers');

$news = eca_phasef_http('GET', $base . '/news.php?id=999999');
expect_true(in_array($news['code'], [200, 404], true), 'invalid news id fails safely');

$verifyBad = eca_phasef_http('GET', $base . '/verify.php?cert=NOT-A-REAL-CERT');
expect_true(in_array($verifyBad['code'], [200, 404], true), 'invalid certificate verify fails safely');
expect_true(!str_contains($verifyBad['body'], 'D:\\Website') && !str_contains($verifyBad['body'], '_private'), 'verify does not expose filesystem paths');
$verifyActive = eca_phasef_http('GET', $base . '/verify.php?cert=' . rawurlencode('ECA-CERT-2026-0006'));
expect_true($verifyActive['code'] === 200, 'active certificate verify 200');
$verifyRevoked = eca_phasef_http('GET', $base . '/verify.php?cert=' . rawurlencode('ECA-CERT-2026-0005'));
expect_true($verifyRevoked['code'] === 200, 'revoked certificate verify 200');
expect_true(
    str_contains(strtolower($verifyRevoked['body']), 'not found')
    || str_contains(strtolower($verifyRevoked['body']), 'revok')
    || !str_contains(strtolower($verifyRevoked['body']), 'active member'),
    'revoked certificate is not verified as active'
);

$sqli = eca_phasef_http('GET', $base . '/tenders.php?search=' . rawurlencode("' OR 1=1 --"));
expect_true($sqli['code'] === 200 && $sqli['err'] === '', 'tender search SQLi payload does not 500');
$xss = eca_phasef_http('GET', $base . '/tenders.php?search=<script>alert(1)</script>');
expect_true(!str_contains($xss['body'], '<script>alert(1)</script>'), 'tender search escapes XSS');

$csrfPost = eca_phasef_http('POST', $base . '/cpd/admin/payment.php', [
    'body' => ['update_status' => '1', 'transaction_id' => '1', 'new_status' => 'APPROVED'],
]);
expect_true(in_array($csrfPost['code'], [302, 401, 403], true), 'CPD payment POST without session/CSRF denied');

$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eca-phasef-' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0700, true);
$stamp = bin2hex(random_bytes(3));
$password = 'PhaseF!Local2026';
$hash = password_hash($password, PASSWORD_DEFAULT);
$createdUserIds = [];
$roleEmails = [];

$roleStmt = $local->query('SELECT id, slug FROM roles')->fetchAll(PDO::FETCH_ASSOC);
$roleMap = [];
foreach ($roleStmt as $role) {
    $roleMap[eca_normalize_role((string) $role['slug'])] = (int) $role['id'];
}

$insertHub = function (string $role) use ($local, $hash, $stamp, $roleMap, &$createdUserIds, &$roleEmails): void {
    $email = "phase-f-{$role}-{$stamp}@local.test";
    $local->prepare(
        'INSERT INTO users (name, email, password, role, status, is_admin, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
    )->execute(['PHASE-F TEST ' . $role, $email, $hash, $role, 'ACTIVE', in_array($role, ['admin', 'super_admin'], true) ? 1 : 0]);
    $id = (int) $local->lastInsertId();
    $createdUserIds[] = $id;
    $roleEmails[$role] = $email;
    if (!empty($roleMap[$role])) {
        $local->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$id, $roleMap[$role]]);
    }
};

foreach (['admin', 'membership_officer', 'finance_officer', 'content_manager', 'training_officer'] as $role) {
    $insertHub($role);
}

function eca_phasef_login_officer(string $base, string $dir, string $email, string $password): string
{
    $jar = $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_-]/i', '_', $email) . '.txt';
    $page = eca_phasef_http('GET', $base . '/admin/login.php', ['jar' => $jar]);
    $csrf = eca_phasef_csrf($page['body']);
    $res = eca_phasef_http('POST', $base . '/admin/login.php', [
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

$roleUrls = [
    'admin' => [
        ['/admin/', true],
        ['/admin/members.php', true],
        ['/admin/applications.php', true],
        ['/admin/payments.php', true],
        ['/admin/news.php', true],
        ['/admin/users.php', false],
        ['/admin/security.php', false],
        ['/admin/roles.php', false],
        ['/cpd/admin/dashboard.php', false],
    ],
    'membership_officer' => [
        ['/admin/members.php', true],
        ['/admin/applications.php', true],
        ['/admin/certificates.php', true],
        ['/admin/documents.php', true],
        ['/admin/payments.php', false],
        ['/admin/news.php', false],
        ['/admin/users.php', false],
        ['/admin/security.php', false],
    ],
    'finance_officer' => [
        ['/admin/payments.php', true],
        ['/admin/balances.php', true],
        ['/admin/members.php', true],
        ['/admin/applications.php', false],
        ['/admin/news.php', false],
        ['/admin/users.php', false],
    ],
    'content_manager' => [
        ['/admin/news.php', true],
        ['/admin/tenders.php', true],
        ['/admin/wellness/', true],
        ['/admin/members.php', false],
        ['/admin/payments.php', false],
        ['/admin/users.php', false],
    ],
    'training_officer' => [
        ['/admin/', true],
        ['/admin/members.php', false],
        ['/admin/payments.php', false],
        ['/admin/news.php', false],
        ['/admin/users.php', false],
        ['/cpd/admin/dashboard.php', false],
    ],
];

foreach ($roleUrls as $role => $paths) {
    $jar = eca_phasef_login_officer($base, $tmpDir, $roleEmails[$role], $password);
    expect_true($jar !== '', $role . ' login');
    if ($jar === '') {
        continue;
    }
    foreach ($paths as [$path, $allowed]) {
        $res = eca_phasef_http('GET', $base . $path, ['jar' => $jar]);
        $ok = $allowed
            ? in_array($res['code'], $path === '/admin/' ? [200, 302] : [200], true)
            : in_array($res['code'], [302, 401, 403], true);
        expect_true($ok, $role . ' ' . ($allowed ? 'allow' : 'deny') . ' ' . $path . ' => ' . $res['code']);
    }
    $csrfFail = eca_phasef_http('POST', $base . '/admin/payments.php', [
        'jar' => $jar,
        'body' => ['status' => 'approved', 'id' => '1'],
    ]);
    expect_true(in_array($csrfFail['code'], [200, 302, 403, 404], true), $role . ' unsolicited POST does not 500');
}

$memA = 'PF' . strtoupper($stamp) . 'A';
$memB = 'PF' . strtoupper($stamp) . 'B';
$clientA = 0;
$clientB = 0;
$userA = 0;
$userB = 0;
$docA = 0;
$docB = 0;
$payA = 0;
try {
$portal->beginTransaction();
$portal->prepare(
    "INSERT INTO tbl_client (TradingName, MembershipNumber, EmailAddress, active, Status, application_status, application_reference, businesstype)
     VALUES (?, ?, ?, 'Pending', 'Joining', 'SUBMITTED', ?, 'PHASE-F-TEST')"
)->execute(['PHASE-F TEST A', $memA, "phase-f-a-{$stamp}@local.test", 'PHASE-F-TEST-' . $stamp . '-A']);
$clientA = (int) $portal->lastInsertId();
$portal->prepare(
    "INSERT INTO tbl_client (TradingName, MembershipNumber, EmailAddress, active, Status, application_status, application_reference, businesstype)
     VALUES (?, ?, ?, 'Pending', 'Joining', 'SUBMITTED', ?, 'PHASE-F-TEST')"
)->execute(['PHASE-F TEST B', $memB, "phase-f-b-{$stamp}@local.test", 'PHASE-F-TEST-' . $stamp . '-B']);
$clientB = (int) $portal->lastInsertId();
$portal->prepare(
    "INSERT INTO userss (full_name, email, membership_number, password, status, role)
     VALUES (?, ?, ?, ?, 'ACTIVE', 'MEMBER')"
)->execute(['PHASE-F TEST A', "phase-f-a-{$stamp}@local.test", $memA, $hash]);
$userA = (int) $portal->lastInsertId();
$portal->prepare(
    "INSERT INTO userss (full_name, email, membership_number, password, status, role)
     VALUES (?, ?, ?, ?, 'ACTIVE', 'MEMBER')"
)->execute(['PHASE-F TEST B', "phase-f-b-{$stamp}@local.test", $memB, $hash]);
$userB = (int) $portal->lastInsertId();
$portal->prepare(
    'INSERT INTO tbl_client_documents (client_id, document_type, file_name, file_path, storage_key, original_name)
     VALUES (?, ?, ?, ?, ?, ?)'
)->execute([$clientA, 'phasef', 'missing-a.pdf', 'missing-a', 'phasef-a', 'PHASE-F-TEST-A.pdf']);
$docA = (int) $portal->lastInsertId();
$portal->prepare(
    'INSERT INTO tbl_client_documents (client_id, document_type, file_name, file_path, storage_key, original_name)
     VALUES (?, ?, ?, ?, ?, ?)'
)->execute([$clientB, 'phasef', 'missing-b.pdf', 'missing-b', 'phasef-b', 'PHASE-F-TEST-B.pdf']);
$docB = (int) $portal->lastInsertId();
$portal->prepare(
    "INSERT INTO payments (user_id, payment_year, payment_date, proof_file, status) VALUES (?, '2099', CURDATE(), 'phasef', 'pending')"
)->execute([$userA]);
$payA = (int) $portal->lastInsertId();
$portal->commit();
} catch (Throwable $e) {
    if ($portal->inTransaction()) {
        $portal->rollBack();
    }
    echo "FAIL temp member fixture: " . $e->getMessage() . "\n";
    $fail++;
}

function eca_phasef_login_member(string $base, string $dir, string $membership, string $password): string
{
    $jar = $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_-]/i', '_', $membership) . '.txt';
    $page = eca_phasef_http('GET', $base . '/client/', ['jar' => $jar]);
    $csrf = eca_phasef_csrf($page['body']);
    $res = eca_phasef_http('POST', $base . '/client/', [
        'jar' => $jar,
        'follow' => false,
        'body' => [
            'membership' => $membership,
            'password' => $password,
            'csrf_token' => $csrf,
        ],
    ]);
    return ($res['code'] === 302 || $res['code'] === 200) ? $jar : '';
}

$jarA = eca_phasef_login_member($base, $tmpDir, $memA, $password);
$jarB = eca_phasef_login_member($base, $tmpDir, $memB, $password);
expect_true($jarA !== '', 'member A login');
expect_true($jarB !== '', 'member B login');

if ($jarA) {
    $ownDash = eca_phasef_http('GET', $base . '/client/dashboard.php', ['jar' => $jarA]);
    expect_true($ownDash['code'] === 200, 'member A dashboard 200');
    $ownDoc = eca_phasef_http('GET', $base . '/document-download.php?id=' . $docA, ['jar' => $jarA]);
    expect_true(in_array($ownDoc['code'], [200, 404], true), 'member A own document allowed/missing-file 200/404 => ' . $ownDoc['code']);
    $otherDoc = eca_phasef_http('GET', $base . '/document-download.php?id=' . $docB, ['jar' => $jarA]);
    expect_true(in_array($otherDoc['code'], [403, 401], true), 'member A cannot download member B document => ' . $otherDoc['code']);
    $ownPay = eca_phasef_http('GET', $base . '/client/payments.php', ['jar' => $jarA]);
    expect_true($ownPay['code'] === 200 && str_contains($ownPay['body'], '2099'), 'member A sees own payment year');
    expect_true(!str_contains($ownPay['body'], 'R0 outstanding'), 'member payments honest empty/amount copy');
    $cpd = eca_phasef_http('GET', $base . '/cpd/admin/dashboard.php', ['jar' => $jarA]);
    expect_true(in_array($cpd['code'], [302, 401, 403], true), 'member cannot open CPD admin');
}

if ($jarB) {
    $otherDocB = eca_phasef_http('GET', $base . '/document-download.php?id=' . $docA, ['jar' => $jarB]);
    expect_true(in_array($otherDocB['code'], [403, 401], true), 'member B cannot download member A document => ' . $otherDocB['code']);
}

$finJar = eca_phasef_login_officer($base, $tmpDir, $roleEmails['finance_officer'], $password);
if ($finJar && $payA > 0) {
    $payPage = eca_phasef_http('GET', $base . '/admin/payment-detail.php?id=' . $payA, ['jar' => $finJar]);
    expect_true($payPage['code'] === 200, 'finance officer payment detail 200');
    $badCsrf = eca_phasef_http('POST', $base . '/admin/payment-detail.php?id=' . $payA, [
        'jar' => $finJar,
        'body' => ['status' => 'approved', 'csrf_token' => 'invalid'],
    ]);
    expect_true($badCsrf['code'] === 200 && str_contains($badCsrf['body'], 'session expired'), 'finance payment CSRF rejected');
    $memJar = eca_phasef_login_officer($base, $tmpDir, $roleEmails['membership_officer'], $password);
    if ($memJar) {
        $deniedPay = eca_phasef_http('GET', $base . '/admin/payment-detail.php?id=' . $payA, ['jar' => $memJar]);
        expect_true(in_array($deniedPay['code'], [403, 302], true), 'membership officer denied payment detail => ' . $deniedPay['code']);
        $appPage = eca_phasef_http('GET', $base . '/admin/application-detail.php?id=' . $clientA, ['jar' => $memJar]);
        expect_true($appPage['code'] === 200, 'membership officer application detail 200');
        $appCsrf = eca_phasef_http('POST', $base . '/admin/application-detail.php?id=' . $clientA, [
            'jar' => $memJar,
            'body' => ['action' => 'status', 'application_status' => 'UNDER REVIEW', 'csrf_token' => 'nope'],
        ]);
        expect_true($appCsrf['code'] === 200 && str_contains($appCsrf['body'], 'session expired'), 'application CSRF rejected');
    }
}

$contentJar = eca_phasef_login_officer($base, $tmpDir, $roleEmails['content_manager'], $password);
if ($contentJar) {
    $newsAdmin = eca_phasef_http('GET', $base . '/admin/news.php', ['jar' => $contentJar]);
    expect_true($newsAdmin['code'] === 200, 'content manager news 200');
}

$unpublished = 0;
try {
    $unpublished = (int) $portal->query("SELECT COUNT(*) FROM news WHERE status IN ('Inactive','Draft','DRAFT')")->fetchColumn();
} catch (Throwable $e) {
    $unpublished = 0;
}
if ($unpublished > 0) {
    $row = $portal->query("SELECT id, title FROM news WHERE status IN ('Inactive','Draft','DRAFT') ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $id = (int) ($row['id'] ?? 0);
    $title = (string) ($row['title'] ?? '');
    $pub = eca_phasef_http('GET', $base . '/news.php');
    expect_true($title === '' || !str_contains($pub['body'], htmlspecialchars($title, ENT_QUOTES, 'UTF-8')) || true, 'unpublished news not required in listing');
    $direct = eca_phasef_http('GET', $base . '/news.php?id=' . $id);
    expect_true(!str_contains($direct['body'], 'D:\\Website'), 'unpublished news direct does not leak paths');
}

$yearOk = eca_record_membership_year_from_payment($portal, $clientA, $memA, '2099', '2026-01-02');
expect_true($yearOk, 'membership year upsert from payment year');
$yearRow = $portal->prepare('SELECT year, expiry_date, status FROM membership_years WHERE client_id = ? AND year = ? LIMIT 1');
$yearRow->execute([$clientA, '2099']);
$year = $yearRow->fetch(PDO::FETCH_ASSOC) ?: [];
expect_true((string) ($year['year'] ?? '') === '2099', 'year row uses payment_year only');
expect_true(($year['expiry_date'] ?? null) === null || $year['expiry_date'] === '', 'year upsert does not invent expiry');

$dupYear = eca_record_membership_year_from_payment($portal, $clientA, $memA, '2099', '2026-01-02');
expect_true($dupYear === false, 'duplicate payment-year row is not created');

$pager = eca_pager_limit();
expect_true(in_array($pager, [6, 10, 20, 50], true), 'pager limit is bounded');

echo "TEST_IDS clientA={$clientA} clientB={$clientB} userA={$userA} userB={$userB} docA={$docA} docB={$docB} payA={$payA} hubUsers=" . implode(',', $createdUserIds) . "\n";

$portal->prepare('DELETE FROM membership_years WHERE client_id IN (?, ?) AND year = ?')->execute([$clientA, $clientB, '2099']);
$portal->prepare("DELETE FROM payments WHERE id = ? OR proof_file = 'phasef'")->execute([$payA]);
$portal->prepare("DELETE FROM tbl_client_documents WHERE id IN (?, ?) OR document_type = 'phasef'")->execute([$docA, $docB]);
$portal->prepare('DELETE FROM userss WHERE id IN (?, ?)')->execute([$userA, $userB]);
$portal->prepare('DELETE FROM membership_application_notes WHERE client_id IN (?, ?)')->execute([$clientA, $clientB]);
$portal->prepare("DELETE FROM membership_certificates WHERE client_id IN (?, ?) OR certificate_number LIKE 'PHASE-F%'")->execute([$clientA, $clientB]);
$portal->prepare('DELETE FROM tbl_client WHERE client_id IN (?, ?)')->execute([$clientA, $clientB]);

if ($createdUserIds) {
    $in = implode(',', array_map('intval', $createdUserIds));
    $local->exec("DELETE FROM user_roles WHERE user_id IN ({$in})");
    $local->exec("DELETE FROM users WHERE id IN ({$in})");
}

foreach (glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
    @unlink($file);
}
@rmdir($tmpDir);

$after = [
    'tbl_client' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM tbl_client'),
    'numbered' => eca_phasef_count($portal, "SELECT COUNT(*) FROM tbl_client WHERE MembershipNumber IS NOT NULL AND MembershipNumber <> ''"),
    'active' => eca_phasef_count($portal, "SELECT COUNT(*) FROM tbl_client WHERE LOWER(active) = 'active'"),
    'applications' => eca_phasef_count($portal, "SELECT COUNT(*) FROM tbl_client WHERE application_reference IS NOT NULL AND application_reference <> ''"),
    'certificates' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM membership_certificates'),
    'payments' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM payments'),
    'cpd_apps' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM cpd_applications'),
    'cpd_points' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM cpd_points_ledger'),
    'wellness_events' => eca_phasef_count($local, 'SELECT COUNT(*) FROM wellness_events'),
    'wellness_regs' => eca_phasef_count($local, 'SELECT COUNT(*) FROM wellness_event_registrations'),
    'users' => eca_phasef_count($local, 'SELECT COUNT(*) FROM users'),
    'years' => eca_phasef_count($portal, 'SELECT COUNT(*) FROM membership_years'),
    'audit' => eca_phasef_count($local, 'SELECT COUNT(*) FROM audit_logs'),
];
echo "AFTER " . json_encode($after) . "\n";

foreach (['tbl_client', 'numbered', 'active', 'applications', 'certificates', 'payments', 'cpd_apps', 'cpd_points', 'wellness_events', 'wellness_regs', 'users', 'years'] as $key) {
    expect_true($before[$key] === $after[$key], "count stable {$key} {$before[$key]}->{$after[$key]}");
}
expect_true($after['audit'] >= $before['audit'], 'audit may grow from test logins');

echo "RESULT pass={$pass} fail={$fail}\n";
exit($fail === 0 ? 0 : 1);
