<?php
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
require_once dirname(__DIR__) . '/includes/portal-db.php';

$fail = 0;
$pass = 0;
function expect(bool $ok, string $label): void
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

$ref = 'ECA-APP-2026-0001';
$body = eca_mail_application_received_body('Banner Electrical', $ref);
expect(str_contains($body, $ref), 'applicant email body includes application reference');
expect(str_contains($body, 'Track your application'), 'applicant email body includes track link');
expect(str_contains($body, 'track.php?ref=' . rawurlencode($ref)), 'applicant email track link uses the reference');
expect(!str_contains($body, 'password'), 'applicant email has no password');
expect(!eca_mail_application_received('not-an-email', 'Name', $ref), 'invalid applicant email is not sent');
expect(!eca_mail_application_received('member@eca.co.sz', 'Name', ''), 'empty reference is not emailed');

$src = file_get_contents(dirname(__DIR__) . '/submit_membership.php') ?: '';
expect(str_contains($src, 'eca_mail_application_received'), 'submit_membership emails the applicant');
expect(str_contains($src, 'success.php?ref='), 'submit_membership redirects with the reference');

$ch = curl_init('http://127.0.0.1:8765/success.php?ref=' . rawurlencode($ref));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
$html = (string) curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
expect($code === 200, 'success page HTTP 200');
expect(str_contains($html, $ref), 'success page shows the application reference');
expect(str_contains($html, 'Application Reference'), 'success page labels the reference');
expect(str_contains($html, 'track.php?ref=' . rawurlencode($ref)), 'success page links to track');
expect(!str_contains($html, 'setTimeout'), 'success page no longer auto-redirects away');

$ch = curl_init('http://127.0.0.1:8765/success.php?ref=not-a-ref');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
$bad = (string) curl_exec($ch);
curl_close($ch);
expect(!str_contains($bad, 'not-a-ref'), 'success page ignores an invalid reference');

echo 'SMTP_CONFIGURED=' . (eca_smtp_ready() ? 'yes' : 'no') . "\n";
echo "Passed={$pass} Failed={$fail}\n";
exit($fail > 0 ? 2 : 0);
