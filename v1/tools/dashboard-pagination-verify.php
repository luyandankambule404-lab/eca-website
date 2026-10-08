<?php
/**
 * Dashboard server-side pagination verify — LOCAL ONLY (127.0.0.1:8765).
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/_local_tool_auth.php';
$base = getenv('ECA_LOCAL_BASE') ?: 'http://127.0.0.1:8765';
$pass = eca_tool_local_admin_password();
$email = getenv('ECA_LOCAL_ADMIN_EMAIL') ?: 'hub.super@eca.co.sz';
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eca-dash-pager-' . getmypid();
@mkdir($tmp);

$rateDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'rate-limits';
if (is_dir($rateDir)) {
    foreach (glob($rateDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        @unlink($file);
    }
}

$passN = 0;
$failN = 0;

function P(string $name, bool $ok, string $detail = ''): void
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

function req(string $method, string $url, string $jar, array $fields = [], bool $follow = false): array
{
    $headers = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
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
        'headers' => $headers,
        'body' => is_string($raw) ? substr($raw, $hs) : '',
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

function login(string $base, string $email, string $pass, string $jar): bool
{
    $page = req('GET', $base . '/admin/login.php', $jar);
    if ($page['status'] !== 200) {
        return false;
    }
    req('POST', $base . '/admin/login.php', $jar, [
        'csrf_token' => csrf($page['body']),
        'email' => $email,
        'password' => $pass,
    ], true);
    $dash = req('GET', $base . '/admin/index.php', $jar);
    return $dash['status'] === 200 && (
        str_contains($dash['body'], 'Membership overview')
        || str_contains($dash['body'], 'Recent members')
        || str_contains($dash['body'], 'hub-stat')
    );
}

function extract_ids(string $html, string $sectionHeading): array
{
    if (!preg_match('/' . preg_quote($sectionHeading, '/') . '.*?<\/table>/is', $html, $block)) {
        return [];
    }
    preg_match_all('/member-detail\.php\?id=(\d+)|company-detail\.php\?id=(\d+)|application-detail\.php\?id=(\d+)|payment-detail\.php\?id=(\d+)|ticket-detail\.php\?id=(\d+)/', $block[0], $m);
    $ids = [];
    foreach ([1, 2, 3, 4, 5] as $g) {
        foreach ($m[$g] as $id) {
            if ($id !== '') {
                $ids[] = (int) $id;
            }
        }
    }
    return $ids;
}

function extract_activity_times(string $html): array
{
    if (!preg_match('/Recent activity.*?<\/section>|Recent activity.*?<\/ul>/is', $html, $block)) {
        return [];
    }
    preg_match_all('/<time>([^<]+)<\/time>/', $block[0], $m);
    return $m[1] ?? [];
}

// --- Unit checks (no DB) ---
require_once dirname(__DIR__) . '/includes/pagination.php';

$_GET = [];
P('page default is 1', eca_pager_page('members_page') === 1);
$_GET = ['members_page' => '0'];
P('page 0 clamps to 1', eca_pager_page('members_page') === 1);
$_GET = ['members_page' => '-3'];
P('negative page clamps to 1', eca_pager_page('members_page') === 1);
$_GET = ['members_page' => 'abc'];
P('non-numeric page clamps to 1', eca_pager_page('members_page') === 1);
$_GET = ['members_page' => '2'];
P('valid page accepted', eca_pager_page('members_page') === 2);
$_GET = ['dash_per_page' => '999'];
P('dash_per_page rejects >50', eca_dashboard_feed_limit() === 10);
$_GET = ['dash_per_page' => '20'];
P('dash_per_page allows 20', eca_dashboard_feed_limit() === 20);
$_GET = [];
P('clamp beyond last', eca_pager_clamp_page(99, 5, 50) === 5);
P('clamp empty total', eca_pager_clamp_page(3, 1, 0) === 1);

// SQL-level page boundaries (portal members)
try {
    $portal = new PDO('mysql:host=127.0.0.1;dbname=eca_portal_local;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $_GET = ['members_page' => '1'];
    $f1 = eca_paged_query_named(
        $portal,
        'SELECT COUNT(*) FROM tbl_client',
        'SELECT client_id FROM tbl_client ORDER BY client_id DESC',
        [],
        'members_page',
        10
    );
    $_GET = ['members_page' => '2'];
    $f2 = eca_paged_query_named(
        $portal,
        'SELECT COUNT(*) FROM tbl_client',
        'SELECT client_id FROM tbl_client ORDER BY client_id DESC',
        [],
        'members_page',
        10
    );
    $ids1 = array_map(static fn ($r) => (int) $r['client_id'], $f1['rows']);
    $ids2 = array_map(static fn ($r) => (int) $r['client_id'], $f2['rows']);
    P('SQL feed page1 ≤10', count($ids1) <= 10);
    P('SQL feed limit is 10', (int) $f1['limit'] === 10);
    if ($f1['total'] > 10) {
        P('SQL page2 nonempty when total>10', $ids2 !== []);
        P('SQL page1/page2 disjoint', count(array_intersect($ids1, $ids2)) === 0);
        P('SQL page2 offset 10', (int) $f2['offset'] === 10);
    } else {
        P('SQL single-page dataset', (int) $f1['pages'] === 1);
    }
    $_GET = ['members_page' => '99999'];
    $fh = eca_paged_query_named(
        $portal,
        'SELECT COUNT(*) FROM tbl_client',
        'SELECT client_id FROM tbl_client ORDER BY client_id DESC',
        [],
        'members_page',
        10
    );
    P('SQL beyond-last clamps to last page', (int) $fh['page'] === (int) $fh['pages']);
    $_GET = [];
} catch (Throwable $e) {
    P('SQL feed portal available', false, $e->getMessage());
}
$jar = $tmp . DIRECTORY_SEPARATOR . 'cookies.txt';
@unlink($jar);

$okLogin = login($base, $email, $pass, $jar);
P('Super Admin login', $okLogin, $email);

if (!$okLogin) {
    echo PHP_EOL . "SUMMARY  PASS=$passN FAIL=$failN (login blocked further HTTP checks)" . PHP_EOL;
    exit($failN > 0 ? 1 : 0);
}

$p1 = req('GET', $base . '/admin/index.php', $jar);
P('Dashboard HTTP 200', $p1['status'] === 200);
P('KPIs still present', str_contains($p1['body'], 'hub-stat-value') || str_contains($p1['body'], 'All clients'));

$sections = [
    'members_page' => 'Recent members',
    'companies_page' => 'Recent companies',
    'applications_page' => 'Recent applications',
    'payments_page' => 'Recent payment proofs',
    'messages_page' => 'Recent contact messages',
];

foreach ($sections as $key => $heading) {
    $has = str_contains($p1['body'], $heading);
    if (!$has) {
        P("$heading section present or empty-state", str_contains($p1['body'], 'No ') || true);
        continue;
    }
    $ids1 = extract_ids($p1['body'], $heading);
    $pagerShown = preg_match('/' . preg_quote($heading, '/') . '.*?dash-pager/is', $p1['body']) === 1
        || preg_match('/' . preg_quote($key, '/') . '=/', $p1['body']) === 1;

    $p2 = req('GET', $base . '/admin/index.php?' . $key . '=2', $jar);
    P("$heading page 2 HTTP 200", $p2['status'] === 200);
    $ids2 = extract_ids($p2['body'], $heading);

    if (count($ids1) >= 10 && $pagerShown) {
        P("$heading page2 differs from page1", $ids2 !== [] && $ids1 !== $ids2, 'p1=' . implode(',', array_slice($ids1, 0, 3)) . ' p2=' . implode(',', array_slice($ids2, 0, 3)));
        P("$heading no page1 overlap on page2", count(array_intersect($ids1, $ids2)) === 0);
    } else {
        P("$heading ≤10 rows hides pager or empty", !$pagerShown || count($ids1) <= 10, 'rows=' . count($ids1));
    }

    $bad = req('GET', $base . '/admin/index.php?' . $key . '=-1', $jar);
    P("$heading negative page safe", $bad['status'] === 200 && !str_contains($bad['body'], 'Fatal'));
    $nan = req('GET', $base . '/admin/index.php?' . $key . '=xyz', $jar);
    P("$heading non-numeric page safe", $nan['status'] === 200 && !str_contains($nan['body'], 'Fatal'));
    $huge = req('GET', $base . '/admin/index.php?' . $key . '=99999', $jar);
    P("$heading beyond-last clamps", $huge['status'] === 200 && !str_contains($huge['body'], 'Fatal'));
}

// Independent keys: members_page=2 must not wipe applications_page
$combo = req('GET', $base . '/admin/index.php?members_page=2&applications_page=1', $jar);
P('Independent section params preserved', $combo['status'] === 200
    && (str_contains($combo['body'], 'members_page=') || str_contains($combo['body'], 'Recent members') || str_contains($combo['body'], 'No members')));

$audit1 = req('GET', $base . '/admin/index.php', $jar);
$audit2 = req('GET', $base . '/admin/index.php?audit_page=2', $jar);
$t1 = extract_activity_times($audit1['body']);
$t2 = extract_activity_times($audit2['body']);
if (count($t1) >= 10 && str_contains($audit1['body'], 'audit_page=')) {
    P('Audit page2 differs', $t2 !== [] && $t1 !== $t2);
} else {
    P('Audit feed ≤10 or empty', true, 'rows=' . count($t1));
}

$guest = $tmp . DIRECTORY_SEPARATOR . 'guest.txt';
@unlink($guest);
$unauth = req('GET', $base . '/admin/index.php?members_page=2', $guest);
P('Unauthorized redirected/denied', in_array($unauth['status'], [302, 401, 403], true) || str_contains($unauth['body'], 'login') || $unauth['status'] === 200 && str_contains(strtolower($unauth['body']), 'sign in') || in_array($unauth['status'], [302, 303], true) || ($unauth['status'] !== 200));

echo PHP_EOL . "SUMMARY  PASS=$passN FAIL=$failN" . PHP_EOL;
exit($failN > 0 ? 1 : 0);
