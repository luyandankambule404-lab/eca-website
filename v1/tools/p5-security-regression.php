<?php
/**
 * P5 security regression — LOCAL ONLY.
 * CSRF / SSO / MoMo / error policy / tool password fail-closed gates.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$base = getenv('ECA_LOCAL_BASE') ?: 'http://127.0.0.1:8765';
$root = dirname(__DIR__);
$pass = 0;
$fail = 0;

function P5(string $name, bool $ok, string $detail = ''): void
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

function p5_req(string $method, string $url, array $opts = []): array
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
    if (!empty($opts['headers']) && is_array($opts['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
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

$sessionSrc = file_get_contents($root . '/includes/session.php') ?: '';
$dbModeSrc = file_get_contents($root . '/includes/db-mode.php') ?: '';
$clientAuth = file_get_contents($root . '/client/auth.php') ?: '';
$adminAuth = file_get_contents($root . '/admin/auth.php') ?: '';
$cpdAuth = file_get_contents($root . '/code.jquery.com/cpd/auth.php') ?: '';
$momoCb = file_get_contents($root . '/code.jquery.com/cpd/api/momo_callback.php') ?: '';
$payMomo = file_get_contents($root . '/code.jquery.com/cpd/api/pay_momo.php') ?: '';
$legacyReg = file_get_contents($root . '/registration/submit_registration.php') ?: '';
$toolAuth = file_get_contents($root . '/tools/_local_tool_auth.php') ?: '';
$envPhp = file_get_contents($root . '/includes/env.php') ?: '';

// --- CSRF closure ---
P5(
    'public POST requires CSRF token in production',
    str_contains($sessionSrc, 'eca_is_production')
        && str_contains($sessionSrc, 'Same-origin Origin/Referer fallback')
        && str_contains($sessionSrc, 'csrf_token'),
    'session.php production fail-closed'
);

$likeNoCsrf = p5_req('POST', rtrim($base, '/') . '/like.php', [
    'body' => 'file=test-resource.pdf',
]);
P5(
    'like.php still rejects CSRF-less POST locally',
    $likeNoCsrf['status'] === 403 || trim($likeNoCsrf['body']) === 'Invalid or expired request.',
    'st=' . $likeNoCsrf['status']
);

$likeBadOrigin = p5_req('POST', rtrim($base, '/') . '/like.php', [
    'body' => 'file=test-resource.pdf',
    'headers' => ['Origin: https://evil.example'],
]);
P5(
    'like.php rejects cross-origin CSRF-less POST',
    $likeBadOrigin['status'] === 403 || trim($likeBadOrigin['body']) === 'Invalid or expired request.',
    'st=' . $likeBadOrigin['status']
);

P5(
    'legacy registration uses eca_require_public_post',
    str_contains($legacyReg, 'eca_require_public_post')
        && str_contains($legacyReg, 'ECA_ALLOW_LEGACY_TRAINING_REG'),
    'submit_registration gated'
);

$regForm = p5_req('GET', rtrim($base, '/') . '/registration/');
P5(
    'legacy registration form includes csrf_token',
    str_contains($regForm['body'], 'name="csrf_token"'),
    'st=' . $regForm['status']
);

// --- SSO production gate ---
P5(
    'eca_portal_preview_allowed defined',
    str_contains($dbModeSrc, 'function eca_portal_preview_allowed')
        && str_contains($dbModeSrc, 'ECA_ALLOW_PORTAL_PREVIEW')
);

P5(
    'member hub SSO gated',
    str_contains($clientAuth, 'eca_portal_preview_allowed')
);

P5(
    'CPD→hub SSO gated',
    str_contains($adminAuth, 'eca_portal_preview_allowed')
);

P5(
    'CPD signed-in preview SSO gated',
    str_contains($cpdAuth, 'eca_portal_preview_allowed')
);

// Runtime: with APP_ENV=local, preview allowed
require_once $root . '/includes/env.php';
P5('local env is not production', !eca_is_production(), 'env=' . eca_app_env());
P5('local portal preview allowed', eca_portal_preview_allowed());

// --- Error disclosure ---
P5(
    'production error policy wired in env bootstrap',
    str_contains($envPhp, 'eca_apply_production_error_policy')
        && str_contains($dbModeSrc, "ini_set('display_errors', '0')")
);

$home = p5_req('GET', rtrim($base, '/') . '/');
P5(
    'home response has no obvious stack-trace leak',
    $home['status'] > 0
        && !preg_match('/\bStack trace\b/i', $home['body'])
        && !preg_match('/\bFatal error\b/i', $home['body'])
        && !preg_match('/ECA_SMTP_PASS|ECA_DB_PASS|ECA_MOMO_API_TOKEN/', $home['body']),
    'st=' . $home['status']
);

// --- Secret / tool password fallback ---
P5(
    'tool auth refuses production APP_ENV',
    str_contains($toolAuth, 'Refusing: local verification tools')
        && str_contains($toolAuth, 'production')
);

// --- Payment environment separation ---
P5(
    'pay_momo live gate present',
    str_contains($payMomo, 'ECA_MOMO_ALLOW_LIVE')
        && str_contains($payMomo, 'ECA_MOMO_MODE')
        && str_contains($payMomo, 'Live MoMo payments are not enabled')
);

P5(
    'momo_callback uses ECA_MOMO_API_TOKEN',
    str_contains($momoCb, 'ECA_MOMO_API_TOKEN')
        && str_contains($momoCb, 'hash_equals')
);

P5(
    'momo_callback production live gate',
    str_contains($momoCb, 'MoMo live callbacks are not enabled')
);

P5(
    'momo_callback does not rely solely on undefined MOMO_API_TOKEN',
    !preg_match("/if\s*\(\s*!defined\('MOMO_API_TOKEN'\)/", $momoCb)
);

echo PHP_EOL . "P5_SECURITY_REGRESSION: PASS=$pass FAIL=$fail" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
