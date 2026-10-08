<?php
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$base = 'http://127.0.0.1:8765';
require_once __DIR__ . '/_local_tool_auth.php';
$pass = eca_tool_local_admin_password();
$emails = ['officer@eca.co.sz', 'admin@eca.co.sz', 'hub.super@eca.co.sz'];
$outDir = dirname(__DIR__) . '/_layout_check';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

function req(string $method, string $url, array $o = []): array
{
    $h = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 1,
        CURLOPT_FOLLOWLOCATION => $o['follow'] ?? 0,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_COOKIEJAR => $o['jar'] ?? '',
        CURLOPT_COOKIEFILE => $o['jar'] ?? '',
        CURLOPT_HEADERFUNCTION => static function ($ch, $hdr) use (&$h) {
            $p = explode(':', $hdr, 2);
            if (count($p) === 2) {
                $h[strtolower(trim($p[0]))] = trim($p[1]);
            }
            return strlen($hdr);
        },
    ]);
    if (!empty($o['fields'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $o['fields']);
    }
    $raw = curl_exec($ch);
    $st = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $err = curl_error($ch);
    curl_close($ch);
    return [
        'status' => $st,
        'body' => is_string($raw) ? substr($raw, $hs) : '',
        'location' => $h['location'] ?? '',
        'error' => $err,
    ];
}

function csrf(string $html): string
{
    if (preg_match('/name="csrf_token"[^>]*value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/value="([^"]+)"[^>]*name="csrf_token"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

foreach ($emails as $email) {
    $jar = sys_get_temp_dir() . '/eca-cmd-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($email)) . '.jar';
    @unlink($jar);
    $login = req('GET', $base . '/admin/login.php', ['jar' => $jar]);
    $token = csrf($login['body']);
    $post = req('POST', $base . '/admin/login.php', [
        'jar' => $jar,
        'fields' => http_build_query([
            'csrf_token' => $token,
            'email' => $email,
            'password' => $pass,
        ]),
    ]);
    $dash = req('GET', $base . '/admin/index.php', ['jar' => $jar, 'follow' => 1]);
    $file = $outDir . '/admin-dash-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($email)) . '.html';
    file_put_contents($file, $dash['body']);
    $checks = [
        'is-admin-dash' => str_contains($dash['body'], 'is-admin-dash'),
        'command-centre' => str_contains($dash['body'], 'Command Centre'),
        'cmd-hero' => str_contains($dash['body'], 'cmd-hero'),
        'admin-command.css' => str_contains($dash['body'], 'admin-command.css'),
        'Main portal' => str_contains($dash['body'], 'Main portal'),
        'hub-portals' => str_contains($dash['body'], 'hub-portals'),
        'Quick actions' => str_contains($dash['body'], 'Quick actions'),
        'login-form' => str_contains($dash['body'], 'Officer login'),
    ];
    echo $email . PHP_EOL;
    echo '  login_status=' . $login['status'] . ' post_status=' . $post['status'] . ' loc=' . $post['location'] . PHP_EOL;
    echo '  dash_status=' . $dash['status'] . ' bytes=' . strlen($dash['body']) . PHP_EOL;
    foreach ($checks as $k => $ok) {
        echo '  ' . ($ok ? 'YES' : 'NO ') . ' ' . $k . PHP_EOL;
    }
    echo '  file=' . $file . PHP_EOL;
    if ($email === 'hub.super@eca.co.sz' && $dash['status'] === 200) {
        $members = req('GET', $base . '/admin/members.php', ['jar' => $jar, 'follow' => 1]);
        $mfile = $outDir . '/admin-members-super.html';
        file_put_contents($mfile, $members['body']);
        echo '  members is-admin-dash=' . (str_contains($members['body'], 'is-admin-dash') ? 'YES' : 'NO') . PHP_EOL;
        echo '  members Command Centre=' . (str_contains($members['body'], 'Command Centre') ? 'YES' : 'NO') . PHP_EOL;
        echo '  members Admin Hub title=' . (str_contains($members['body'], '>Admin Hub<') ? 'YES' : 'NO') . PHP_EOL;
        echo '  portals=' . (substr_count($dash['body'], 'hub-portal-tile')) . PHP_EOL;
    }
}
