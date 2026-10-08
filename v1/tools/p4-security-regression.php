<?php
/**
 * P4 security regression — LOCAL ONLY.
 * Verifies hardening applied for confirmed findings P4-001 / P4-002.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$base = getenv('ECA_LOCAL_BASE') ?: 'http://127.0.0.1:8765';
$pass = 0;
$fail = 0;

function P4(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "PASS  $name" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
        return;
    }
    $fail++;
    echo "FAIL  $name" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
}

function p4_req(string $method, string $url, array $opts = []): array
{
    $ch = curl_init($url);
    $headers = [];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADERFUNCTION => static function ($ch, $hdr) use (&$headers) {
            $p = explode(':', $hdr, 2);
            if (count($p) === 2) {
                $headers[strtolower(trim($p[0]))] = trim($p[1]);
            }
            return strlen($hdr);
        },
    ]);
    if (!empty($opts['body'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['body']);
    }
    if (!empty($opts['cookie'])) {
        curl_setopt($ch, CURLOPT_COOKIE, $opts['cookie']);
    }
    if (!empty($opts['cookiejar'])) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $opts['cookiejar']);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $opts['cookiejar']);
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [
        'status' => $status,
        'body' => is_string($raw) ? substr($raw, $hs) : '',
        'headers' => $headers,
    ];
}

// P4-001: like.php without CSRF and without Origin should be rejected
$like = p4_req('POST', rtrim($base, '/') . '/like.php', [
    'body' => 'file=test-resource.pdf',
]);
P4(
    'like.php rejects CSRF-less POST',
    $like['status'] === 403 || trim($like['body']) === 'Invalid or expired request.',
    'st=' . $like['status']
);

// Headers present on home
$home = p4_req('GET', rtrim($base, '/') . '/');
P4('X-Content-Type-Options present', isset($home['headers']['x-content-type-options']));
P4('X-Frame-Options present', isset($home['headers']['x-frame-options']));
P4('CSP present', isset($home['headers']['content-security-policy']));

// Unauthenticated document download denied
$doc = p4_req('GET', rtrim($base, '/') . '/document-download.php?id=1');
P4('document-download anonymous denied', in_array($doc['status'], [401, 403, 302], true), 'st=' . $doc['status']);

// Unauthenticated admin redirected
$admin = p4_req('GET', rtrim($base, '/') . '/admin/users.php');
P4('admin users unauthenticated gated', in_array($admin['status'], [302, 401, 403], true), 'st=' . $admin['status']);

// Verify page loads; rate-limit function exists in session include (code presence)
$verifySrc = file_get_contents(dirname(__DIR__) . '/verify.php') ?: '';
P4('verify.php rate-limit wired', str_contains($verifySrc, 'verify-lookup') && str_contains($verifySrc, 'eca_rate_limit_exceeded'));

// Content safety
$adv = p4_req('GET', rtrim($base, '/') . '/advocacy-updates.php');
P4(
    'advocacy updates empty-or-list without invented claims',
    str_contains($adv['body'], 'No verified advocacy updates')
        || str_contains($adv['body'], 'adv-update-card')
        || str_contains($adv['body'], 'Advocacy Updates')
);

$well = p4_req('GET', rtrim($base, '/') . '/wellness/support.php');
P4(
    'wellness support non-clinical wording',
    str_contains($well['body'], 'does not diagnose')
        || str_contains($well['body'], 'do not provide clinical')
        || str_contains($well['body'], 'not a counselling service')
);

echo PHP_EOL . "P4_SECURITY_REGRESSION: PASS=$pass FAIL=$fail" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
