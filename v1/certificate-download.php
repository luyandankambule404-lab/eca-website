<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/certificates.php';
require_once __DIR__ . '/includes/authz.php';
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/documents.php';
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/client/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$conn = eca_portal_pdo(false);
if (!$conn || $id < 1) {
    eca_not_found('Certificate not found.');
}

$stmt = $conn->prepare('SELECT * FROM membership_certificates WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$cert = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cert) {
    eca_not_found('Certificate not found.');
}

$clientStmt = $conn->prepare('SELECT * FROM tbl_client WHERE client_id = ? LIMIT 1');
$clientStmt->execute([(int) $cert['client_id']]);
$client = $clientStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$admin = eca_admin_user(true);
$member = eca_current_member(true);
$adminOk = $admin && eca_can('certificates.manage', (string) ($admin['role'] ?? ''));
$memberOk = eca_member_owns_client($member, (int) $cert['client_id'], $conn);
if (!$adminOk && !$memberOk) {
    if (empty($_SESSION['eca_admin']) && empty($_SESSION['eca_member'])) {
        eca_unauthorized('Sign in to download this certificate.');
    }
    eca_forbid('You are not allowed to download this certificate.');
}

try {
    $pdf = eca_certificate_pdf($cert, $client);
} catch (Throwable $e) {
    eca_server_error('The certificate could not be generated.');
}

eca_audit('certificate.download', 'membership_certificates', (string) $id, [
    'client_id' => $cert['client_id'] ?? '',
]);

$filename = 'ECA-certificate-' . preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($cert['certificate_number'] ?? $id)) . '.pdf';
$inline = isset($_GET['view']) && (string) $_GET['view'] === '1';
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
header('Content-Length: ' . (string) strlen($pdf));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
echo $pdf;
exit;
