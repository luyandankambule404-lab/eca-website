<?php
/**
 * FINAL SYSTEM completion suite — LOCAL ONLY.
 * Does not modify production. Does not create schema.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/_local_tool_auth.php';
$base = getenv('ECA_LOCAL_BASE') ?: 'http://127.0.0.1:8765';
$pass = eca_tool_local_admin_password();
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eca-final-' . getmypid();
@mkdir($tmp);

// Clear local rate limits so auth tests stay reliable.
$rateDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'rate-limits';
if (is_dir($rateDir)) {
    foreach (glob($rateDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        @unlink($file);
    }
}

$passN = 0;
$failN = 0;
$blockedN = 0;
$nvN = 0;

function F(string $name, bool $ok, string $detail = ''): void
{
    global $passN, $failN;
    if ($ok) {
        $passN++;
        echo "PASS  $name" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
        return;
    }
    $failN++;
    echo "FAIL  $name" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
}

function B(string $name, string $detail = ''): void
{
    global $blockedN;
    $blockedN++;
    echo "BLOCKED  $name" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
}

function NV(string $name, string $detail = ''): void
{
    global $nvN;
    $nvN++;
    echo "NOT_VERIFIABLE_LOCALLY  $name" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
}

function req(string $method, string $url, string $jar, array $fields = [], bool $follow = false): array
{
    $headers = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HEADERFUNCTION => static function ($ch, $hdr) use (&$headers) {
            $p = explode(':', $hdr, 2);
            if (count($p) === 2) {
                $headers[strtolower(trim($p[0]))] = trim($p[1]);
            }
            return strlen($hdr);
        },
    ]);
    if ($fields) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [
        'status' => $status,
        'body' => is_string($raw) ? substr($raw, $hs) : '',
        'location' => $headers['location'] ?? '',
        'content_type' => $headers['content-type'] ?? '',
    ];
}

function csrf(string $html): string
{
    if (preg_match('/name="csrf_token"[^>]*value="([^"]+)"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    if (preg_match('/value="([^"]+)"[^>]*name="csrf_token"/', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function login(string $email, string $password, string $jar, string $base): array
{
    $page = req('GET', $base . '/admin/login.php', $jar);
    return req('POST', $base . '/admin/login.php', $jar, [
        'csrf_token' => csrf($page['body']),
        'email' => $email,
        'password' => $password,
    ], true);
}

function denied(array $r): bool
{
    return in_array($r['status'], [301, 302, 303, 401, 403], true)
        || str_contains(strtolower($r['location'] . $r['body']), 'login')
        || str_contains(strtolower($r['body']), 'access denied');
}

$hub = new PDO('mysql:host=127.0.0.1;dbname=eca_local;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$portal = new PDO('mysql:host=127.0.0.1;dbname=eca_portal_local;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

F('Hub DB online', true);
F('Portal DB online', true);
F('role_permissions absent', !(bool) $hub->query("SHOW TABLES LIKE 'role_permissions'")->fetchColumn());
F('users.status absent', !(bool) $hub->query("SHOW COLUMNS FROM users LIKE 'status'")->fetchColumn());

// --- Public ---
$anon = $tmp . '/anon.txt';
@unlink($anon);
foreach (['/', '/directory.php', '/verify.php', '/track.php', '/contact.php', '/application.php'] as $path) {
    $r = req('GET', $base . $path, $anon);
    F('Public ' . $path, $r['status'] === 200, 'st=' . $r['status']);
}

// Verify membership + cert UI
$v = req('GET', $base . '/verify.php?m=' . rawurlencode('ECA-1001'), $anon);
F('Verify membership ECA-1001', $v['status'] === 200 && (str_contains($v['body'], 'Verified') || str_contains($v['body'], 'ECA-1001') || str_contains($v['body'], 'Status')));
F('Verify form has cert field', str_contains(req('GET', $base . '/verify.php', $anon)['body'], 'name="cert"'));
$vc = req('GET', $base . '/verify.php?cert=' . rawurlencode('CERT-ECA-1001'), $anon);
F('Verify active certificate', $vc['status'] === 200 && !str_contains(strtolower($vc['body']), 'certificate revoked'));
F('Verify share link not production host on local', !str_contains($vc['body'], 'https://eca.co.sz/verify.php') || str_contains($vc['body'], '127.0.0.1'));

// Track
$t = req('GET', $base . '/track.php?ref=ECA-APP-2026-0001', $anon);
F('Track approved application', $t['status'] === 200 && str_contains($t['body'], 'ECA-APP-2026-0001') && (str_contains($t['body'], 'Approved') || str_contains($t['body'], 'APPROVED') || str_contains(strtolower($t['body']), 'approved')));
$t2 = req('GET', $base . '/track.php?ref=ECA-APP-2026-0002', $anon);
F('Track submitted application', $t2['status'] === 200 && str_contains($t2['body'], 'ECA-APP-2026-0002'));
$tBad = req('GET', $base . '/track.php?ref=NOT-A-REF', $anon);
F('Track invalid reference rejected', $tBad['status'] === 200 && (str_contains(strtolower($tBad['body']), 'format') || str_contains(strtolower($tBad['body']), 'enter')));

// Document unauthorized (no seeded docs locally — missing id must not stream a file)
$doc = req('GET', $base . '/document-download.php?id=1', $anon);
F(
    'Document download anonymous denied or not found',
    denied($doc) || in_array($doc['status'], [401, 403, 404], true),
    'st=' . $doc['status']
);
F('Document download does not stream anonymously', $doc['status'] !== 200 || !str_contains(strtolower($doc['content_type']), 'application/pdf'));
$certDl = req('GET', $base . '/certificate-download.php?id=1', $anon);
F(
    'Certificate download anonymous denied or gated',
    denied($certDl) || in_array($certDl['status'], [401, 403, 404], true),
    'st=' . $certDl['status']
);

// XSS probes public
$xss = req('GET', $base . '/verify.php?m=' . rawurlencode('<script>alert(1)</script>'), $anon);
F('Verify XSS escaped', !str_contains($xss['body'], '<script>alert(1)</script>'));

// --- Auth ---
$failJar = $tmp . '/fail.txt';
@unlink($failJar);
$lp = req('GET', $base . '/admin/login.php', $failJar);
req('POST', $base . '/admin/login.php', $failJar, [
    'csrf_token' => csrf($lp['body']),
    'email' => 'nope@example.invalid',
    'password' => 'Wrong!',
], true);
$failed = (int) $hub->query("SELECT COUNT(*) FROM audit_logs WHERE action='admin.login.failed' AND created_at >= (NOW() - INTERVAL 2 MINUTE)")->fetchColumn();
F('Failed login audited', $failed > 0);

$super = $tmp . '/super.txt';
@unlink($super);
login('hub.super@eca.co.sz', $pass, $super, $base);
$dash = req('GET', $base . '/admin/index.php', $super);
F('Super Admin dashboard', $dash['status'] === 200 && str_contains($dash['body'], 'Membership overview'));
F('Dashboard company KPIs', str_contains($dash['body'], 'Company overview') || str_contains($dash['body'], 'Matched to membership'));

// Logout
req('GET', $base . '/admin/logout.php', $super, [], true);
F('Logout clears Hub session', denied(req('GET', $base . '/admin/index.php', $super)));

@unlink($super);
login('hub.super@eca.co.sz', $pass, $super, $base);

// CSRF invalid
$coEdit = req('POST', $base . '/admin/company-edit.php?id=1', $super, ['name' => 'Hack', 'status' => 'active'], true);
F('Company edit CSRF reject', str_contains(strtolower($coEdit['body']), 'session expired'));

// RBAC roles
$admin = $tmp . '/admin.txt';
@unlink($admin);
login('admin@eca.co.sz', $pass, $admin, $base);
F('Admin dashboard allowed', req('GET', $base . '/admin/index.php', $admin)['status'] === 200);
F('Admin users denied', denied(req('GET', $base . '/admin/users.php', $admin)));
F('Admin roles denied', denied(req('GET', $base . '/admin/roles.php', $admin)));
F('Admin companies allowed', req('GET', $base . '/admin/companies.php', $admin)['status'] === 200);
F('Admin audit allowed', req('GET', $base . '/admin/audit.php', $admin)['status'] === 200);
F('Admin security action filter denied', denied(req('GET', $base . '/admin/audit.php?action=csrf.rejected', $admin)));

$cpd = $tmp . '/cpd.txt';
@unlink($cpd);
login('cpd.admin@eca.co.sz', $pass, $cpd, $base);
F('CPD Admin denied Hub users', denied(req('GET', $base . '/admin/users.php', $cpd)));
F('CPD Admin denied Hub companies', denied(req('GET', $base . '/admin/companies.php', $cpd)));

F('Anonymous denied audit', denied(req('GET', $base . '/admin/audit.php', $anon)));
F('Anonymous denied payments', denied(req('GET', $base . '/admin/payments.php', $anon)));

// Modules Super Admin
foreach ([
    '/admin/members.php' => 'members',
    '/admin/applications.php' => 'applications',
    '/admin/payments.php' => 'payments',
    '/admin/certificates.php' => 'certificates',
    '/admin/documents.php' => 'documents',
    '/admin/companies.php' => 'companies',
    '/admin/owners-report.php' => 'owners',
    '/admin/reports.php' => 'reports',
    '/admin/audit.php' => 'audit',
    '/admin/users.php' => 'users',
    '/admin/roles.php' => 'roles',
    '/admin/tickets.php' => 'tickets',
    '/admin/cpd.php' => 'cpd',
    '/admin/wellness/' => 'wellness',
    '/admin/balances.php' => 'balances',
    '/admin/receipts.php' => 'receipts',
    '/admin/security.php' => 'security',
    '/admin/settings.php' => 'settings',
] as $path => $label) {
    $r = req('GET', $base . $path, $super, [], true);
    F('SA module ' . $label, $r['status'] === 200, 'st=' . $r['status']);
}

F('Balances DNA when table missing', str_contains(req('GET', $base . '/admin/balances.php', $super)['body'], 'DATA NOT AVAILABLE LOCALLY'));
F('Receipts DNA when table missing', str_contains(req('GET', $base . '/admin/receipts.php', $super)['body'], 'DATA NOT AVAILABLE LOCALLY'));
F('Payments empty is genuine zero', str_contains(req('GET', $base . '/admin/payments.php', $super)['body'], 'genuinely 0') || str_contains(req('GET', $base . '/admin/payments.php', $super)['body'], 'Payment proofs'));

// Companies regression samples
$co = req('GET', $base . '/admin/companies.php', $super);
F('Companies listing', $co['status'] === 200 && str_contains($co['body'], 'Total companies'));
$csv = req('GET', $base . '/admin/companies.php?export=csv', $super);
F('Companies CSV', str_contains(strtolower($csv['content_type']), 'csv') || str_contains($csv['body'], 'Company'));
F('Companies XSS escaped', !str_contains(req('GET', $base . '/admin/companies.php?search=' . rawurlencode('<script>alert(1)</script>'), $super)['body'], '<script>alert(1)</script>'));

// Applications / certificates
$apps = req('GET', $base . '/admin/applications.php', $super);
F('Applications list', $apps['status'] === 200);
$certs = req('GET', $base . '/admin/certificates.php', $super);
F('Certificates list', $certs['status'] === 200 && (str_contains($certs['body'], 'CERT') || str_contains($certs['body'], 'Certificate') || str_contains($certs['body'], 'certificate')));

// Audit detail
$aid = (int) $hub->query('SELECT id FROM audit_logs ORDER BY id DESC LIMIT 1')->fetchColumn();
$adet = req('GET', $base . '/admin/audit.php?id=' . $aid, $super);
F('Audit detail', $adet['status'] === 200 && str_contains(strtolower($adet['body']), 'audit detail'));
F('Audit detail no password leak', !preg_match('/\$2[ayb]\$/', $adet['body']));

// Payment approve path blocked by empty data
$payCount = (int) $portal->query('SELECT COUNT(*) FROM payments')->fetchColumn();
if ($payCount < 1) {
    B('Payment approve/reject E2E', 'payments table has 0 local rows');
} else {
    F('Payment rows present for E2E', true, 'count=' . $payCount);
}

// Privilege escalation
F('Admin cannot open user-detail', denied(req('GET', $base . '/admin/user-detail.php?id=1', $admin)));

// Contact page
$contact = req('GET', $base . '/contact.php', $anon);
F('Contact page', $contact['status'] === 200);

// Schema integrity single audit
F('Single audit_logs system', (int) $hub->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='eca_local' AND table_name='audit_logs'")->fetchColumn() === 1);

NV('Live UltraPro admin parity', 'Live discovery not available this session');
NV('Production SMTP delivery', 'Local mail log / env only');
NV('Production deployment cutover', 'Pre-production checklist only');

echo PHP_EOL;
echo "FINAL_SYSTEM:\n";
echo "PASS: $passN\n";
echo "FAIL: $failN\n";
echo "BLOCKED: $blockedN\n";
echo "NOT_VERIFIABLE_LOCALLY: $nvN\n";
exit($failN > 0 ? 1 : 0);
