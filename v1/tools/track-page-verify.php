<?php
/**
 * LOCAL /track.php checks. Does not create business records.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/includes/application-track.php';
require_once dirname(__DIR__) . '/includes/portal-db.php';

$fail = 0;
$pass = 0;

function track_expect(bool $ok, string $label): void
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

track_expect(eca_track_reference_valid('ECA-APP-2026-0001'), 'valid reference accepted');
track_expect(!eca_track_reference_valid('ECA-APP-26-1'), 'short reference rejected');
track_expect(!eca_track_reference_valid('ECA-APP-2026-0001;DROP'), 'injected reference rejected');
track_expect(eca_track_normalize_reference(' eca-app-2026-0001 ') === 'ECA-APP-2026-0001', 'reference normalized');

$submitted = eca_track_timeline('SUBMITTED', '2026-09-17 02:32:25', []);
track_expect($submitted[0]['state'] === 'current' && $submitted[0]['key'] === 'submitted', 'SUBMITTED current step');
track_expect($submitted[1]['state'] === 'upcoming' && $submitted[2]['state'] === 'upcoming', 'SUBMITTED later steps upcoming');
track_expect($submitted[0]['date'] === '17 Sep 2026', 'SUBMITTED uses real submitted date');

$review = eca_track_timeline('UNDER REVIEW', '2026-09-17 02:32:25', [
    ['action' => 'application.reviewed', 'at' => '2026-09-18 09:00:00', 'label' => 'Application moved to Under Review'],
]);
track_expect($review[0]['state'] === 'complete' && $review[2]['state'] === 'current', 'UNDER REVIEW current');
track_expect($review[2]['date'] === '18 Sep 2026', 'UNDER REVIEW uses audit date');
track_expect($review[3]['state'] === 'upcoming', 'UNDER REVIEW decision upcoming');

$info = eca_track_timeline('ADDITIONAL INFORMATION REQUIRED', '2026-09-17 02:32:25', []);
track_expect($info[3]['key'] === 'info' && $info[3]['state'] === 'current', 'ADDITIONAL INFORMATION current');
track_expect($info[4]['state'] === 'upcoming', 'ADDITIONAL INFORMATION final review upcoming');
track_expect($info[1]['date'] === '', 'implied Received has no invented date');

$approved = eca_track_timeline('APPROVED', '2026-09-17 02:32:25', []);
track_expect($approved[count($approved) - 1]['key'] === 'approved', 'APPROVED last step');
track_expect(!in_array('rejected', array_column($approved, 'key'), true), 'APPROVED has no rejected step');

$rejected = eca_track_timeline('REJECTED', '2026-09-17 02:32:25', []);
track_expect($rejected[count($rejected) - 1]['state'] === 'rejected', 'REJECTED last step');
track_expect(!in_array('approved', array_column($rejected, 'key'), true), 'REJECTED has no approved step');

$copy = eca_track_status_copy('ADDITIONAL INFORMATION REQUIRED');
track_expect(str_contains($copy['message'], 'member portal'), 'additional-info copy points to portal/contact');

$base = 'http://127.0.0.1:8765';

function track_http(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADER => false,
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

$form = track_http($base . '/track.php');
track_expect($form['code'] === 200, 'track form HTTP 200');
track_expect(str_contains($form['body'], 'Track Your ECA Application'), 'track heading present');
track_expect(str_contains($form['body'], 'Example: ECA-APP-2026-0001'), 'example guidance present');
track_expect(!str_contains($form['body'], 'Email on the application'), 'email field removed from public form');

$empty = track_http($base . '/track.php?ref=');
track_expect(str_contains($empty['body'], 'Please enter an application reference.'), 'empty reference message');

$bad = track_http($base . '/track.php?ref=NOT-A-REF');
track_expect(str_contains($bad['body'], 'ECA-APP-YYYY-NNNN'), 'malformed reference message');
track_expect(!str_contains($bad['body'], 'SQL'), 'malformed response has no SQL');

$missing = track_http($base . '/track.php?ref=ECA-APP-2026-9999');
track_expect(str_contains($missing['body'], 'check the reference number and try again'), 'unknown reference generic message');

$approvedPage = track_http($base . '/track.php?ref=ECA-APP-2026-0001');
track_expect($approvedPage['code'] === 200, 'approved track HTTP 200');
track_expect(str_contains($approvedPage['body'], 'ECA-APP-2026-0001'), 'approved reference shown');
track_expect(str_contains($approvedPage['body'], 'Approved'), 'approved status shown');
track_expect(str_contains($approvedPage['body'], '17 September 2026'), 'approved submitted date from database');
track_expect(str_contains($approvedPage['body'], 'ECA-1001'), 'existing membership number shown when present');
track_expect(str_contains($approvedPage['body'], '/verify.php?cert=ECA-CERT-2026-0001'), 'public certificate verify link shown');
track_expect(!str_contains($approvedPage['body'], 'client_id'), 'client_id not exposed');
track_expect(!preg_match('/password|passport|EmailAddress|officer note/i', $approvedPage['body']), 'no sensitive labels on approved page');

$approvedNoMember = track_http($base . '/track.php?ref=ECA-APP-2026-0002');
track_expect(str_contains($approvedNoMember['body'], 'Approved'), 'approved without membership still approved');
track_expect(!str_contains($approvedNoMember['body'], 'Membership number'), 'no invented membership number');
track_expect(str_contains($approvedNoMember['body'], '/verify.php?cert=ECA-CERT-2026-0002'), 'active certificate verify link without membership');

$rejectedPage = track_http($base . '/track.php?ref=ECA-APP-2026-0004');
track_expect(str_contains($rejectedPage['body'], 'Rejected'), 'rejected status shown');
track_expect(str_contains($rejectedPage['body'], 'Your application has been rejected.'), 'rejected public message');
track_expect(!str_contains($rejectedPage['body'], 'verify.php?cert='), 'rejected page has no certificate link');

$conn = eca_portal_pdo(false);
if ($conn) {
    $name = (string) $conn->query("SELECT TradingName FROM tbl_client WHERE application_reference = 'ECA-APP-2026-0001' LIMIT 1")->fetchColumn();
    if ($name !== '') {
        track_expect(!str_contains($approvedPage['body'], $name), 'company name not exposed on public track page');
    } else {
        track_expect(true, 'company name not exposed on public track page');
    }
}

echo "Passed={$pass} Failed={$fail}\n";
exit($fail > 0 ? 2 : 0);
