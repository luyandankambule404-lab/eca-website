<?php
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
require_once dirname(__DIR__) . '/includes/membership.php';
require_once dirname(__DIR__) . '/includes/welcome-pack.php';
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

$ref = 'ECA-APP-2026-0099';
$membership = 'ECA-1099';
$login = eca_mail_absolute_url('/client/');
$pack = [
    'about' => eca_mail_absolute_url('/about.php'),
    'conduct' => eca_mail_absolute_url('/code-of-conduct.php'),
    'privacy' => eca_mail_absolute_url('/privacy.php'),
    'forgot' => eca_mail_absolute_url('/client/page-forgot-password.html'),
];
$samplePassword = 'local-test-password-not-for-display';
$bodyWithPass = eca_mail_membership_approved_body('Test Contractor', $ref, $membership, $login, $samplePassword, $pack);
$bodyNoPass = eca_mail_membership_approved_body('Test Contractor', $ref, $membership, $login, null, $pack);

expect(str_contains($bodyWithPass, $ref), 'approved email includes application reference');
expect(str_contains($bodyWithPass, $membership), 'approved email includes membership number');
expect(str_contains($bodyWithPass, '/client/'), 'approved email includes member hub login path');
expect(str_contains($bodyWithPass, 'Temporary password:'), 'approved email includes temporary password when issued');
expect(str_contains($bodyWithPass, 'About ECA'), 'approved email includes About ECA');
expect(str_contains($bodyWithPass, 'Code of Conduct'), 'approved email includes Code of Conduct');
expect(str_contains($bodyWithPass, 'Data privacy') || str_contains($bodyWithPass, 'privacy notice'), 'approved email includes data privacy');
expect(str_contains($bodyWithPass, '/about.php'), 'approved email links About ECA page');
expect(str_contains($bodyWithPass, '/code-of-conduct.php'), 'approved email links Code of Conduct page');
expect(str_contains($bodyWithPass, '/privacy.php'), 'approved email links privacy page');
expect(!str_contains($bodyNoPass, 'Temporary password:'), 'existing-account email has no temporary password');
expect(str_contains($bodyNoPass, 'password already set'), 'existing-account email points to existing password');
expect(str_contains(eca_mail_redact_html($bodyWithPass), '[redacted]'), 'mail log redacts temporary password');
expect(!str_contains(eca_mail_redact_html($bodyWithPass), $samplePassword), 'redacted html drops the sample password');

expect(!eca_mail_membership_approved('not-an-email', 'Name', $ref, $membership, $samplePassword), 'invalid applicant email is not sent');
expect(!eca_mail_membership_approved('member@eca.co.sz', 'Name', '', $membership, null), 'empty reference is not emailed');
expect(!eca_mail_membership_approved('member@eca.co.sz', 'Name', $ref, '', null), 'empty membership is not emailed');

$src = file_get_contents(dirname(__DIR__) . '/admin/application-detail.php') ?: '';
expect(str_contains($src, 'eca_mail_membership_approved'), 'application-detail emails login details on approve');
expect(str_contains($src, 'eca_provision_member_hub_login'), 'application-detail provisions member hub login on approve');

$mailer = file_get_contents(dirname(__DIR__) . '/includes/mailer.php') ?: '';
expect(str_contains($mailer, 'eca_mail_record'), 'mailer logs outbound messages when SMTP is missing');

$base = 'http://127.0.0.1:8765';
function approve_http(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => true]);
    $html = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $html];
}

$about = approve_http($base . '/about.php');
expect($about['code'] === 200, 'about page HTTP 200');
expect(str_contains($about['body'], 'Eswatini Contractors Association'), 'about page has ECA copy');

$conduct = approve_http($base . '/code-of-conduct.php');
expect($conduct['code'] === 200, 'code of conduct page HTTP 200');
expect(str_contains($conduct['body'], 'Health'), 'code of conduct page has CoC items');

$privacy = approve_http($base . '/privacy.php');
expect($privacy['code'] === 200, 'privacy page HTTP 200');
expect(str_contains($privacy['body'], 'Data Protection Act'), 'privacy page cites Data Protection Act');

$loginPage = approve_http($base . '/client/');
expect($loginPage['code'] === 200, 'member hub login HTTP 200');
expect(str_contains($loginPage['body'], 'Membership number'), 'member hub login asks for membership number');

echo 'SMTP_CONFIGURED=' . (eca_smtp_ready() ? 'yes' : 'no') . "\n";
echo 'MAIL_LOG_DIR=' . eca_mail_log_dir() . "\n";
echo "Passed={$pass} Failed={$fail}\n";
exit($fail > 0 ? 2 : 0);
