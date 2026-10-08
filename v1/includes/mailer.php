<?php

require_once __DIR__ . '/portal-db.php';
require_once __DIR__ . '/welcome-pack.php';

function eca_mail_log_dir(): string
{
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'mail-log';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function eca_mail_redact_html(string $html): string
{
    $html = preg_replace(
        '/(<strong>Temporary password:<\/strong>\s*)([^<]+)/i',
        '$1[redacted]',
        $html
    ) ?? $html;
    return $html;
}

function eca_mail_record(string $to, string $subject, string $html, bool $sent, array $meta = []): void
{
    $attachments = $meta['attachments'] ?? [];
    $names = [];
    foreach ((array) $attachments as $item) {
        $names[] = is_string($item) ? basename($item) : '';
    }
    $payload = [
        'at' => date('c'),
        'to' => $to,
        'subject' => $subject,
        'smtp_ready' => eca_smtp_ready(),
        'sent' => $sent,
        'password_included' => str_contains($html, 'Temporary password:'),
        'attachments' => array_values(array_filter($names)),
        'html' => $html,
        'html_redacted' => eca_mail_redact_html($html),
    ];
    $file = eca_mail_log_dir() . DIRECTORY_SEPARATOR . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
    @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $state = $sent ? 'sent' : (eca_smtp_ready() ? 'failed' : 'logged (SMTP not configured)');
    error_log('ECA mail ' . $state . ': ' . $subject . ' to ' . $to);
}

function eca_mail_absolute_url(string $path): string
{
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }
    if (function_exists('eca_is_local_request') && eca_is_local_request()) {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8765');
        if ($host === '') {
            $host = '127.0.0.1:8765';
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        return $scheme . '://' . $host . $path;
    }
    if (php_sapi_name() === 'cli') {
        return 'http://127.0.0.1:8765' . $path;
    }
    require_once __DIR__ . '/public-seo.php';
    $base = rtrim(eca_env('ECA_PUBLIC_URL', 'https://eca.co.sz'), '/');
    return $base . $path;
}

function eca_mail_send(string $to, string $subject, string $html, array $attachments = []): bool
{
    $to = trim($to);
    $sent = false;
    $usable = [];
    foreach ($attachments as $path) {
        if (is_string($path) && is_file($path)) {
            $usable[] = $path;
        }
    }

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        eca_mail_record($to, $subject, $html, false, ['attachments' => $usable]);
        return false;
    }
    if (!eca_smtp_ready()) {
        eca_mail_record($to, $subject, $html, false, ['attachments' => $usable]);
        return false;
    }

    $src = dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php';
    if (!is_file($src)) {
        $src = dirname(__DIR__) . '/PHPMailer-master/src/PHPMailer.php';
    }
    if (!is_file($src)) {
        eca_mail_record($to, $subject, $html, false, ['attachments' => $usable]);
        return false;
    }
    require_once dirname($src) . '/Exception.php';
    require_once dirname($src) . '/PHPMailer.php';
    require_once dirname($src) . '/SMTP.php';
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        if (!eca_configure_smtp($mail)) {
            eca_mail_record($to, $subject, $html, false, ['attachments' => $usable]);
            return false;
        }
        $from = eca_env('ECA_SMTP_FROM', eca_env('ECA_SMTP_USER', 'info@eca.co.sz'));
        $mail->setFrom($from, 'Eswatini Contractors Association');
        $mail->addAddress($to);
        $mail->addReplyTo('info@eca.co.sz', 'ECA Membership Office');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = trim(strip_tags($html));
        foreach ($usable as $path) {
            $mail->addAttachment($path, basename($path));
        }
        $sent = $mail->send();
        eca_mail_record($to, $subject, $html, $sent, ['attachments' => $usable]);
        return $sent;
    } catch (Throwable $e) {
        error_log('ECA mail skipped: ' . $e->getMessage());
        eca_mail_record($to, $subject, $html, false, ['attachments' => $usable]);
        return false;
    }
}

/**
 * Send HTML mail with optional CC list and attachments.
 * @param list<string> $cc
 * @param list<string> $attachments Absolute file paths
 */
function eca_mail_send_notice(
    string $to,
    string $subject,
    string $html,
    array $cc = [],
    array $attachments = [],
    array $meta = []
): bool {
    $to = trim($to);
    $usable = [];
    foreach ($attachments as $path) {
        if (is_string($path) && is_file($path)) {
            $usable[] = $path;
        }
    }
    $ccClean = [];
    foreach ($cc as $addr) {
        $addr = strtolower(trim((string) $addr));
        if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL) && !in_array($addr, $ccClean, true)) {
            $ccClean[] = $addr;
        }
    }
    $recordMeta = array_merge($meta, ['attachments' => $usable, 'cc' => $ccClean]);

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        eca_mail_record($to, $subject, $html, false, $recordMeta);
        return false;
    }
    if (!eca_smtp_ready()) {
        // Local / unconfigured: still log for operator review.
        eca_mail_record($to, $subject, $html, false, $recordMeta);
        return false;
    }

    $src = dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php';
    if (!is_file($src)) {
        $src = dirname(__DIR__) . '/PHPMailer-master/src/PHPMailer.php';
    }
    if (!is_file($src)) {
        eca_mail_record($to, $subject, $html, false, $recordMeta);
        return false;
    }
    require_once dirname($src) . '/Exception.php';
    require_once dirname($src) . '/PHPMailer.php';
    require_once dirname($src) . '/SMTP.php';
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        if (!eca_configure_smtp($mail)) {
            eca_mail_record($to, $subject, $html, false, $recordMeta);
            return false;
        }
        $from = eca_env('ECA_SMTP_FROM', eca_env('ECA_SMTP_USER', 'info@eca.co.sz'));
        $mail->setFrom($from, 'Eswatini Contractors Association');
        $mail->addAddress($to);
        foreach ($ccClean as $addr) {
            $mail->addCC($addr);
        }
        $mail->addReplyTo('info@eca.co.sz', 'ECA Membership Office');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));
        foreach ($usable as $path) {
            $mail->addAttachment($path, basename($path));
        }
        $sent = $mail->send();
        eca_mail_record($to, $subject, $html, $sent, $recordMeta);
        return $sent;
    } catch (Throwable $e) {
        error_log('ECA notice mail skipped: ' . $e->getMessage());
        eca_mail_record($to, $subject, $html, false, $recordMeta);
        return false;
    }
}

/**
 * Distinct valid member emails from portal tbl_client.
 * @return list<array{email:string,name:string,client_id:int,membership:string}>
 */
function eca_member_notice_recipients(?PDO $portal): array
{
    if (!$portal) {
        return [];
    }
    try {
        $rows = $portal->query(
            "SELECT client_id, TradingName, CompanyRegistrationName, MembershipNumber, EmailAddress
             FROM tbl_client
             WHERE EmailAddress IS NOT NULL AND TRIM(EmailAddress) <> ''
             ORDER BY client_id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
    $out = [];
    $seen = [];
    foreach ($rows as $row) {
        $email = strtolower(trim((string) ($row['EmailAddress'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
            continue;
        }
        $seen[$email] = true;
        $name = trim((string) ($row['TradingName'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($row['CompanyRegistrationName'] ?? ''));
        }
        $out[] = [
            'email' => $email,
            'name' => $name !== '' ? $name : $email,
            'client_id' => (int) ($row['client_id'] ?? 0),
            'membership' => trim((string) ($row['MembershipNumber'] ?? '')),
        ];
    }
    return $out;
}

function eca_mail_status_update(string $to, string $name, string $ref, string $status): bool
{
    $html = '<p>Dear ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Your ECA application <strong>' . htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') . '</strong> is now <strong>'
        . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<p>You can track it at /track.php</p>';
    return eca_mail_send($to, 'ECA application update', $html);
}

function eca_mail_ticket(string $to, string $name, string $ref): bool
{
    $html = '<p>Dear ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>We received your message. Ticket number: <strong>' . htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') . '</strong>.</p>';
    return eca_mail_send($to, 'ECA support ' . $ref, $html);
}

function eca_application_fix_url(string $ref): string
{
    return str_replace('/track.php?', '/application-fix.php?', eca_application_track_url($ref));
}

function eca_application_track_url(string $ref): string
{
    return eca_mail_absolute_url('/track.php?ref=' . rawurlencode($ref));
}

function eca_mail_application_received_body(string $name, string $ref): string
{
    $safeName = htmlspecialchars($name !== '' ? $name : 'Applicant', ENT_QUOTES, 'UTF-8');
    $safeRef = htmlspecialchars($ref, ENT_QUOTES, 'UTF-8');
    $track = htmlspecialchars(eca_application_track_url($ref), ENT_QUOTES, 'UTF-8');
    return '<p>Dear ' . $safeName . ',</p>'
        . '<p>We received your ECA membership application.</p>'
        . '<p><strong>Application Reference:</strong> ' . $safeRef . '</p>'
        . '<p>Keep this reference. You will need it to track your application.</p>'
        . '<p><a href="' . $track . '">Track your application</a></p>'
        . '<p>Please allow up to 2 working days for processing.</p>';
}

function eca_mail_application_received(string $to, string $name, string $ref): bool
{
    $to = trim($to);
    $ref = trim($ref);
    if ($to === '' || $ref === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return eca_mail_send($to, 'ECA application received ' . $ref, eca_mail_application_received_body($name, $ref));
}

function eca_mail_certificate_issued(string $to, string $name, string $cert): bool
{
    $html = '<p>Dear ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>Your membership certificate <strong>' . htmlspecialchars($cert, ENT_QUOTES, 'UTF-8') . '</strong> is available in the member portal.</p>'
        . '<p>Verify at /verify.php?cert=' . htmlspecialchars(rawurlencode($cert), ENT_QUOTES, 'UTF-8') . '</p>';
    return eca_mail_send($to, 'ECA certificate ' . $cert, $html);
}

function eca_mail_renewal_reminder(string $to, string $name, string $membership): bool
{
    $html = '<p>Dear ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>This is a reminder about membership <strong>' . htmlspecialchars($membership, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . '<p>You can renew at /renewal.html when ready.</p>';
    return eca_mail_send($to, 'ECA membership renewal reminder', $html);
}

function eca_mail_membership_approved_body(
    string $name,
    string $ref,
    string $membership,
    string $loginUrl,
    ?string $temporaryPassword,
    array $packUrls
): string {
    $safeName = htmlspecialchars($name !== '' ? $name : 'Applicant', ENT_QUOTES, 'UTF-8');
    $safeRef = htmlspecialchars($ref, ENT_QUOTES, 'UTF-8');
    $safeMember = htmlspecialchars($membership, ENT_QUOTES, 'UTF-8');
    $safeLogin = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
    $about = htmlspecialchars((string) ($packUrls['about'] ?? ''), ENT_QUOTES, 'UTF-8');
    $conduct = htmlspecialchars((string) ($packUrls['conduct'] ?? ''), ENT_QUOTES, 'UTF-8');
    $privacy = htmlspecialchars((string) ($packUrls['privacy'] ?? ''), ENT_QUOTES, 'UTF-8');
    $forgot = htmlspecialchars((string) ($packUrls['forgot'] ?? ''), ENT_QUOTES, 'UTF-8');

    $credentials = '<p><strong>Membership number:</strong> ' . $safeMember . '</p>'
        . '<p>Sign in to the member hub at <a href="' . $safeLogin . '">' . $safeLogin . '</a> '
        . 'using your membership number and password.</p>';
    if ($temporaryPassword !== null && $temporaryPassword !== '') {
        $credentials .= '<p><strong>Temporary password:</strong> '
            . htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Keep this password safe. The ECA office can reset it if you need a new one.</p>';
    } else {
        $credentials .= '<p>Use the password already set for this membership. '
            . 'If you need a reset, use <a href="' . $forgot . '">Forgot password</a> '
            . 'or email <a href="mailto:support@eca.co.sz">support@eca.co.sz</a>.</p>';
    }

    return '<p>Dear ' . $safeName . ',</p>'
        . '<p>Your ECA membership application <strong>' . $safeRef . '</strong> has been <strong>APPROVED</strong>.</p>'
        . '<h3>Member hub login</h3>'
        . $credentials
        . '<h3>Welcome package</h3>'
        . '<p>Please read the following as part of your membership:</p>'
        . '<p><strong>About ECA</strong></p>'
        . eca_about_eca_blurb_html()
        . ($about !== '' ? '<p><a href="' . $about . '">Read About ECA</a></p>' : '')
        . '<p><strong>Code of Conduct</strong></p>'
        . '<p>Members are expected to follow the ECA Code of Conduct for contractors.</p>'
        . ($conduct !== '' ? '<p><a href="' . $conduct . '">Read the Code of Conduct</a></p>' : '')
        . '<p><strong>Data privacy</strong></p>'
        . '<p>ECA stores and uses application and membership data in accordance with the Data Protection Act. '
        . 'See the privacy notice for details.</p>'
        . ($privacy !== '' ? '<p><a href="' . $privacy . '">Read the data privacy notice</a></p>' : '')
        . '<p>Copies of these documents are attached when the files are available on the site.</p>'
        . '<p>Regards,<br><strong>ECA Membership Office</strong></p>';
}

function eca_mail_membership_approved(
    string $to,
    string $name,
    string $ref,
    string $membership,
    ?string $temporaryPassword = null
): bool {
    $to = trim($to);
    $ref = trim($ref);
    $membership = trim($membership);
    if ($to === '' || $ref === '' || $membership === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $pages = eca_welcome_pack_page_paths();
    $urls = [
        'about' => eca_mail_absolute_url($pages['about']),
        'conduct' => eca_mail_absolute_url($pages['conduct']),
        'privacy' => eca_mail_absolute_url($pages['privacy']),
        'forgot' => eca_mail_absolute_url($pages['forgot']),
    ];
    $html = eca_mail_membership_approved_body(
        $name,
        $ref,
        $membership,
        eca_mail_absolute_url($pages['login']),
        $temporaryPassword,
        $urls
    );
    return eca_mail_send(
        $to,
        'ECA membership approved — login details',
        $html,
        eca_welcome_pack_attachments()
    );
}
