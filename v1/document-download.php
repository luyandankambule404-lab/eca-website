<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/documents.php';
require_once __DIR__ . '/includes/authz.php';
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/client/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    eca_not_found('Document not found.');
}

$conn = eca_portal_pdo(false);
if (!$conn) {
    eca_server_error();
}

$stmt = $conn->prepare('SELECT * FROM tbl_client_documents WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) {
    eca_not_found('Document not found.');
}
if (!eca_can_access_client_document($doc)) {
    if (empty($_SESSION['eca_admin']) && empty($_SESSION['eca_member'])) {
        eca_unauthorized('Sign in as the owning member or an administrator to download this document.');
    }
    eca_forbid('You are not allowed to download this document.');
}

$path = eca_document_absolute_path($doc);
if (!$path) {
    eca_not_found('This document is not available in the local copy.');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimes = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
];
if (!isset($mimes[$ext])) {
    eca_forbid();
}

$inline = isset($_GET['view']) && (string) $_GET['view'] === '1';
eca_audit($inline ? 'document.view' : 'document.download', 'tbl_client_documents', (string) $id, [
    'client_id' => $doc['client_id'] ?? '',
]);

$downloadName = (string) ($doc['original_name'] ?? $doc['file_name'] ?? 'document.' . $ext);
$downloadName = basename($downloadName);
header('Content-Type: ' . $mimes[$ext]);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
header('Content-Length: ' . (string) filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
readfile($path);
exit;
