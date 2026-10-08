<?php

require_once __DIR__ . '/portal-db.php';
require_once __DIR__ . '/membership.php';

function eca_next_certificate_number(PDO $conn): string
{
    $year = date('Y');
    $prefix = 'ECA-CERT-' . $year . '-';
    $stmt = $conn->prepare(
        'SELECT certificate_number FROM membership_certificates
         WHERE certificate_number LIKE ? ORDER BY certificate_number DESC LIMIT 1'
    );
    $stmt->execute([$prefix . '%']);
    $last = (string) $stmt->fetchColumn();
    $next = 1;
    if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
        $next = ((int) $m[1]) + 1;
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function eca_active_certificate(PDO $conn, int $clientId): ?array
{
    $stmt = $conn->prepare(
        'SELECT * FROM membership_certificates WHERE client_id = ? AND status = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$clientId, 'ACTIVE']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function eca_issue_certificate(PDO $conn, array $client, ?array $year = null): array
{
    $conn->prepare('UPDATE membership_certificates SET status = ? WHERE client_id = ? AND status = ?')
        ->execute(['REVOKED', (int) $client['client_id'], 'ACTIVE']);

    $number = trim((string) ($client['CertificateNumber'] ?? ''));
    if ($number === '' || eca_certificate_number_taken($conn, $number)) {
        $number = eca_next_certificate_number($conn);
    }
    $issued = date('Y-m-d');
    $expiry = (string) ($year['expiry_date'] ?? '');
    if ($expiry === '') {
        $expiry = date('Y-m-d', strtotime('+1 year'));
    }
    $token = bin2hex(random_bytes(8));
    $stmt = $conn->prepare(
        'INSERT INTO membership_certificates
         (client_id, membership_number, certificate_number, classification, company_name, issued_at, expiry_date, status, qr_token)
         VALUES (?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        (int) $client['client_id'],
        (string) ($client['MembershipNumber'] ?? ''),
        $number,
        (string) ($client['Clasification'] ?? ''),
        (string) ($client['TradingName'] ?? $client['CompanyRegistrationName'] ?? ''),
        $issued,
        $expiry,
        'ACTIVE',
        $token,
    ]);
    $upd = $conn->prepare('UPDATE tbl_client SET CertificateNumber = ? WHERE client_id = ?');
    $upd->execute([$number, (int) $client['client_id']]);
    return eca_active_certificate($conn, (int) $client['client_id']) ?? ['certificate_number' => $number];
}

function eca_certificate_number_taken(PDO $conn, string $number): bool
{
    $stmt = $conn->prepare('SELECT id FROM membership_certificates WHERE certificate_number = ? LIMIT 1');
    $stmt->execute([$number]);
    return (bool) $stmt->fetchColumn();
}

function eca_qr_png_data(string $text): string
{
    $lib = dirname(__DIR__) . '/code.jquery.com/cpd/phpqrcode/qrlib.php';
    if (!is_file($lib) || !function_exists('imagecreate')) {
        return '';
    }
    require_once $lib;
    try {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eca-qr-' . bin2hex(random_bytes(4)) . '.png';
        QRcode::png($text, $tmp, QR_ECLEVEL_L, 4, 2);
        $data = is_file($tmp) ? (string) file_get_contents($tmp) : '';
        if (is_file($tmp)) {
            unlink($tmp);
        }
        return $data;
    } catch (Throwable $e) {
        return '';
    }
}

function eca_qr_table_html(string $text): string
{
    $lib = dirname(__DIR__) . '/code.jquery.com/cpd/phpqrcode/qrlib.php';
    if (!is_file($lib)) {
        return '';
    }
    require_once $lib;
    try {
        $rows = QRcode::text($text, false, QR_ECLEVEL_L);
        if (!is_array($rows) || !$rows) {
            return '';
        }
        $module = 2;
        $cols = strlen((string) $rows[0]);
        $px = max($module, $cols * $module);
        $html = '<table class="eca-qr" cellpadding="0" cellspacing="0" border="0" width="' . $px . '" style="width:' . $px . 'px;max-width:' . $px . 'px;border-collapse:collapse;table-layout:fixed;">';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (str_split((string) $row) as $bit) {
                $bg = $bit === '1' ? '#192754' : '#ffffff';
                $html .= '<td width="' . $module . '" height="' . $module . '" bgcolor="' . $bg . '" style="width:' . $module . 'px;height:' . $module . 'px;max-width:' . $module . 'px;background:' . $bg . ';padding:0;margin:0;border:0;line-height:0;font-size:1px;">&nbsp;</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</table>';
    } catch (Throwable $e) {
        return '';
    }
}

function eca_certificate_image_data_uri(string $path): string
{
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }
    $data = (string) file_get_contents($path);
    if ($data === '') {
        return '';
    }
    $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
    $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function eca_certificate_formal_date(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '—';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }
    return date('d F Y', $ts);
}

function eca_certificate_html(array $cert, array $client): string
{
    require_once __DIR__ . '/public-seo.php';
    $numberRaw = trim((string) ($cert['certificate_number'] ?? ''));
    $verifyPath = '/verify.php?cert=' . rawurlencode($numberRaw);
    $verifyUrl = eca_public_url($verifyPath);
    $qr = eca_qr_png_data($verifyUrl);
    $qrImg = $qr !== '' ? 'data:image/png;base64,' . base64_encode($qr) : '';
    $qrTable = $qrImg === '' ? eca_qr_table_html($verifyUrl) : '';
    $logo = '';
    if (function_exists('imagecreate')) {
        $logo = eca_certificate_image_data_uri(dirname(__DIR__) . '/img/ecalogo.png');
    }
    if ($logo === '') {
        $logo = eca_certificate_image_data_uri(dirname(__DIR__) . '/img/ecalogo-cert.jpg');
    }
    $company = htmlspecialchars(
        trim((string) ($cert['company_name'] ?? $client['TradingName'] ?? $client['CompanyRegistrationName'] ?? '')) ?: 'ECA Member',
        ENT_QUOTES,
        'UTF-8'
    );
    $memberNo = htmlspecialchars(trim((string) ($cert['membership_number'] ?? $client['MembershipNumber'] ?? '')) ?: '—', ENT_QUOTES, 'UTF-8');
    $class = htmlspecialchars(trim((string) ($cert['classification'] ?? $client['Clasification'] ?? '')) ?: '—', ENT_QUOTES, 'UTF-8');
    $issued = htmlspecialchars(eca_certificate_formal_date((string) ($cert['issued_at'] ?? '')), ENT_QUOTES, 'UTF-8');
    $expiry = htmlspecialchars(eca_certificate_formal_date((string) ($cert['expiry_date'] ?? '')), ENT_QUOTES, 'UTF-8');
    $number = htmlspecialchars($numberRaw !== '' ? $numberRaw : '—', ENT_QUOTES, 'UTF-8');
    $status = strtoupper(trim((string) ($cert['status'] ?? 'ACTIVE')));
    $statusLabel = htmlspecialchars($status !== '' ? $status : 'ACTIVE', ENT_QUOTES, 'UTF-8');
    $verifySafe = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');
    $qrBlock = '';
    if ($qrImg !== '') {
        $qrBlock = '<img class="qr" src="' . $qrImg . '" alt="Verification QR code">';
    } elseif ($qrTable !== '') {
        $qrBlock = $qrTable;
    }
    $logoBlock = $logo !== ''
        ? '<img class="logo" src="' . $logo . '" width="200" height="87" alt="Eswatini Contractors Association">'
        : '<div class="logo-fallback">Eswatini Contractors Association</div>';

    return '<html><head><meta charset="utf-8"><style>
        @page { margin: 14px; }
        body { font-family: DejaVu Sans, sans-serif; color: #192754; margin: 0; background: #fff; }
        .outer { border: 3px solid #192754; padding: 5px; }
        .mid { border: 1.5px solid #d50d0e; padding: 5px; }
        .inner { border: 1px solid #192754; background: #fffdf8; }
        .head { text-align: center; padding: 12px 24px 7px; }
        .logo { width: 200px; height: 87px; }
        .logo-fallback { font-family: Times-Roman, DejaVu Serif, serif; font-size: 18px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
        .org-meta { margin: 5px 0 0; font-size: 9px; letter-spacing: 1.1px; text-transform: uppercase; color: #5c657c; }
        .bar-red { height: 4px; background: #d50d0e; font-size: 1px; line-height: 4px; }
        .bar-navy { height: 2px; background: #192754; font-size: 1px; line-height: 2px; }
        .main { padding: 8px 36px 4px; }
        .kicker { text-align: center; margin: 0 0 2px; color: #d50d0e; font-size: 9px; letter-spacing: 2.4px; text-transform: uppercase; font-weight: 700; }
        .title { text-align: center; margin: 0 0 2px; font-family: Times-Roman, DejaVu Serif, serif; font-size: 28px; letter-spacing: 2.5px; text-transform: uppercase; font-weight: 700; }
        .ornament { text-align: center; margin: 0 0 6px; color: #d50d0e; font-size: 8px; }
        .lead { text-align: center; margin: 0 0 3px; font-family: Times-Roman, DejaVu Serif, serif; font-size: 13px; font-style: italic; color: #4b556c; }
        .company { text-align: center; margin: 2px 64px 0; padding: 3px 8px 7px; font-family: Times-Roman, DejaVu Serif, serif; font-size: 26px; font-weight: 700; line-height: 1.15; border-bottom: 1.5px solid #192754; }
        .confirm { text-align: center; margin: 8px 68px 10px; font-size: 11px; line-height: 1.5; color: #3d4663; }
        .facts { width: 100%; border-collapse: collapse; margin: 0 0 8px; }
        .facts td { width: 25%; text-align: center; padding: 7px 8px 8px; border-top: 2px solid #192754; border-bottom: 1px solid #d8deea; }
        .lab { font-size: 8px; letter-spacing: 1.1px; text-transform: uppercase; color: #6b7690; font-weight: 700; }
        .val { font-size: 12px; font-weight: 700; padding-top: 3px; }
        .dates { text-align: center; margin: 0 0 8px; font-size: 11px; color: #192754; }
        .dates b { font-size: 12px; }
        .bottom { width: 100%; border-collapse: collapse; }
        .bottom td { vertical-align: middle; padding: 4px 8px 0; }
        .qr-col { width: 124px; }
        .qr { width: 88px; height: 88px; }
        table.eca-qr { width: 86px !important; max-width: 86px !important; }
        table.eca-qr td { width: 2px !important; height: 2px !important; padding: 0 !important; }
        .verify { font-size: 7.5px; color: #6b7690; line-height: 1.35; padding-top: 3px; }
        .sign { text-align: center; }
        .sign-line { border-top: 1px solid #192754; width: 210px; margin: 16px auto 5px; }
        .sign-name { font-size: 10.5px; font-weight: 700; }
        .sign-role { font-size: 8.5px; color: #6b7690; }
        .seal-col { width: 124px; text-align: right; }
        .seal { width: 78px; border: 2px solid #d50d0e; text-align: center; margin-left: auto; }
        .seal-in { margin: 3px; border: 1px solid #192754; padding: 12px 4px 10px; }
        .seal-eca { font-size: 15px; font-weight: 700; letter-spacing: 2px; }
        .seal-word { font-size: 7px; letter-spacing: 1.4px; text-transform: uppercase; color: #d50d0e; font-weight: 700; }
        .foot { margin: 8px 24px 10px; padding-top: 6px; border-top: 1px solid #d8deea; text-align: center; font-size: 8px; color: #6b7690; }
    </style></head><body>
    <div class="outer"><div class="mid"><div class="inner">
        <div class="head">
            ' . $logoBlock . '
            <p class="org-meta">The professional body for the construction industry · Since 1991</p>
        </div>
        <div class="bar-red">&nbsp;</div>
        <div class="bar-navy">&nbsp;</div>
        <div class="main">
            <p class="kicker">Official membership document</p>
            <h1 class="title">Certificate of Membership</h1>
            <p class="ornament">&#9670;</p>
            <p class="lead">This is to certify that</p>
            <div class="company">' . $company . '</div>
            <p class="confirm">is a registered member of the Eswatini Contractors Association and is entitled to the standing conferred by this certificate while it remains current.</p>
            <table class="facts"><tr>
                <td><div class="lab">Membership number</div><div class="val">' . $memberNo . '</div></td>
                <td><div class="lab">Certificate number</div><div class="val">' . $number . '</div></td>
                <td><div class="lab">Classification</div><div class="val">' . $class . '</div></td>
                <td><div class="lab">Status</div><div class="val">' . $statusLabel . '</div></td>
            </tr></table>
            <p class="dates">Issued <b>' . $issued . '</b> &nbsp;&nbsp;&#183;&nbsp;&nbsp; Valid until <b>' . $expiry . '</b></p>
            <table class="bottom"><tr>
                <td class="qr-col">
                    ' . $qrBlock . '
                    <div class="verify">Scan to verify this certificate<br>' . $verifySafe . '</div>
                </td>
                <td class="sign">
                    <div class="sign-line"></div>
                    <div class="sign-name">Eswatini Contractors Association</div>
                    <div class="sign-role">Issued under the authority of the Association</div>
                </td>
                <td class="seal-col">
                    <div class="seal"><div class="seal-in"><div class="seal-eca">ECA</div><div class="seal-word">Member</div></div></div>
                </td>
            </tr></table>
        </div>
        <div class="foot">Suite 40, Cooper Centre, Mbabane, Eswatini · +268 2404 4987 · info@eca.co.sz · Public standing is confirmed on the verification page.</div>
    </div></div></div>
    </body></html>';
}

function eca_certificate_pdf(array $cert, array $client): string
{
    $autoload = dirname(__DIR__) . '/code.jquery.com/cpd/dompdf/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('PDF library is not available.');
    }
    require_once $autoload;
    $dompdf = new Dompdf\Dompdf(['isRemoteEnabled' => false]);
    $dompdf->loadHtml(eca_certificate_html($cert, $client));
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    return $dompdf->output();
}
