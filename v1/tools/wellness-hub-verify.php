<?php
/**
 * LOCAL Wellness Hub Phase 1 checks. Does not create business or medical records.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$fail = 0;
$pass = 0;

function wh_expect(bool $ok, string $label): void
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

$topics = eca_wellness_mental_topics();
wh_expect(count($topics) === 7, 'seven mental-health topics');
wh_expect(count(eca_wellness_dimensions()) === 8, 'eight wellness dimensions');
wh_expect(count(eca_wellness_checkin_questions()) === 5, 'five check-in questions');
wh_expect(eca_wellness_checkin_band(1.8) === 'lower', 'check-in lower band');
wh_expect(eca_wellness_checkin_band(2.9) === 'mixed', 'check-in mixed band');
wh_expect(eca_wellness_checkin_band(4.2) === 'higher', 'check-in higher band');
wh_expect(str_contains(eca_wellness_checkin_copy('higher')[1], 'not a diagnosis'), 'higher band is not diagnosis');
wh_expect(eca_wellness_youtube_id('https://www.youtube.com/watch?v=dQw4w9WgXcQ') === 'dQw4w9WgXcQ', 'youtube id from watch url');
wh_expect(eca_wellness_safe_url('javascript:alert(1)') === '', 'javascript url rejected');
wh_expect(eca_wellness_safe_url('https://eca.co.sz/file.pdf') !== '', 'https url accepted');

$conn = eca_wellness_db();
wh_expect($conn instanceof PDO, 'hub database available');
if ($conn) {
    eca_wellness_hub_ensure_schema($conn);
    $cols = $conn->query('SHOW COLUMNS FROM wellness_resources')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['hub_section', 'kind', 'body_text', 'topic_slug'] as $col) {
        wh_expect(in_array($col, $cols, true), 'resources column ' . $col);
    }
    $checkCols = $conn->query('SHOW COLUMNS FROM wellness_checkins')->fetchAll(PDO::FETCH_COLUMN);
    wh_expect(in_array('scores_json', $checkCols, true), 'checkins scores_json');
    wh_expect(in_array('band', $checkCols, true), 'checkins band');
    wh_expect(!in_array('email', $checkCols, true) && !in_array('ip', $checkCols, true), 'checkins has no email or ip');
    $src = file_get_contents(dirname(__DIR__) . '/includes/wellness-hub.php') ?: '';
    wh_expect(!str_contains($src, 'INSERT INTO wellness_checkins') || !str_contains(explode('function eca_wellness_save_checkin', $src)[1] ?? '', 'REMOTE_ADDR'), 'check-in save does not store IP');
}

$base = 'http://127.0.0.1:8765';

function wh_http(string $url, string $method = 'GET', array $fields = [], array $headers = []): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADER => false,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($headers) {
        $opts[CURLOPT_HTTPHEADER] = $headers;
    }
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
    }
    curl_setopt_array($ch, $opts);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

$pages = [
    '/wellness/' => ['STRONG MINDS', 'Wellness Hub', 'Mental Health'],
    '/wellness/mental-health.php' => ['Understanding mental health', 'Burnout'],
    '/wellness/holistic.php' => ['Physical', 'Financial', 'Occupational'],
    '/wellness/library.php' => ['Wellness Library', 'Articles'],
    '/wellness/check-in.php' => ['confidential', 'not a diagnosis', 'csrf_token'],
    '/wellness/toolbox.php' => ['Hard Hat', 'toolbox'],
    '/wellness/support.php' => ['emergency services', 'info@eca.co.sz', '+268 2404 4987'],
    '/wellness/groups.php' => ['moderated', 'Privacy'],
];

foreach ($pages as $path => $needles) {
    $res = wh_http($base . $path);
    wh_expect($res['code'] === 200, $path . ' 200');
    $low = strtolower($res['body']);
    foreach ($needles as $needle) {
        wh_expect(str_contains($low, strtolower($needle)), $path . ' contains ' . $needle);
    }
    wh_expect(!str_contains($low, 'membership_number'), $path . ' no membership leak');
}

$home = wh_http($base . '/');
wh_expect($home['code'] === 200 && str_contains($home['body'], '/wellness/'), 'homepage links to /wellness/');

$missing = wh_http($base . '/wellness/item.php?id=999999');
wh_expect(in_array($missing['code'], [404, 200], true), 'unknown item fails safely');
wh_expect(!str_contains(strtolower($missing['body']), 'scores_json'), 'item 404 does not leak check-in data');

$badPost = wh_http($base . '/wellness/check-in.php', 'POST', [
    'stress' => '5',
    'fatigue' => '5',
    'pressure' => '5',
    'leadership' => '5',
    'wellbeing' => '5',
    'csrf_token' => 'nope',
]);
wh_expect($badPost['code'] === 200, 'check-in bad csrf still 200');
wh_expect(str_contains(strtolower($badPost['body']), 'expired') || str_contains(strtolower($badPost['body']), 'try again'), 'check-in rejects bad csrf');
wh_expect(!str_contains(strtolower($badPost['body']), 'higher current strain'), 'bad csrf does not produce a result');

$support = wh_http($base . '/wellness/support.php');
wh_expect(!str_contains(strtolower($support['body']), 'invented'), 'support page copy is careful');
wh_expect(
    !preg_match('/Lifeline|Samaritans|WHO hotline|fake clinic/i', $support['body']),
    'support page does not invent hotlines'
);

$adminSrc = file_get_contents(dirname(__DIR__) . '/admin/wellness/resource-edit.php') ?: '';
wh_expect(str_contains($adminSrc, 'hub_section'), 'admin resource-edit has hub section');
wh_expect(str_contains($adminSrc, 'body_text'), 'admin resource-edit has body text');
wh_expect(str_contains($adminSrc, 'kind'), 'admin resource-edit has kind');
wh_expect(str_contains($adminSrc, 'accept=".mp4,video/mp4"') || str_contains($adminSrc, '.mp4'), 'admin can upload mp4 videos');

$contentSrc = file_get_contents(dirname(__DIR__) . '/admin/wellness/content.php') ?: '';
wh_expect(str_contains($contentSrc, "typeKey"), 'typed content list exists');
$dashSrc = file_get_contents(dirname(__DIR__) . '/admin/wellness/index.php') ?: '';
foreach (['Articles', 'Videos', 'Documents', 'Toolbox talks', 'Referrals', 'Support groups', 'Usage statistics', 'Wellness resources'] as $cap) {
    wh_expect(str_contains($dashSrc, $cap), 'admin dashboard offers ' . $cap);
}
$kinds = eca_wellness_hub_kinds();
wh_expect(isset($kinds['document']), 'document kind exists');
wh_expect(isset(eca_wellness_admin_content_types()['talk']), 'toolbox talk admin type exists');
wh_expect(isset(eca_wellness_admin_content_types()['referral']), 'referral admin type exists');

$adminPages = [
    '/admin/wellness/' => 302,
    '/admin/wellness/content.php?type=article' => 302,
    '/admin/wellness/reports.php' => 302,
];
foreach ($adminPages as $path => $code) {
    $res = wh_http($base . $path);
    wh_expect(in_array($res['code'], [200, 302], true), $path . ' reachable');
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
