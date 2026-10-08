<?php

require_once __DIR__ . '/env.php';

function eca_private_documents_dir(): string
{
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'documents';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function eca_legacy_documents_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents';
}

function eca_upload_mime_map(array $extensions): array
{
    $known = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'mp4' => ['video/mp4'],
    ];
    $map = [];
    foreach ($extensions as $ext) {
        $ext = strtolower((string) $ext);
        if (isset($known[$ext])) {
            $map[$ext] = $known[$ext];
        }
    }
    return $map;
}

function eca_uploaded_file_meta(array $file, array $allowedMime, int $maxSize): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxSize) {
        return null;
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return null;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!isset($allowedMime[$ext])) {
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    if (!in_array($mime, $allowedMime[$ext], true)) {
        return null;
    }
    return ['ext' => $ext === 'jpeg' ? 'jpg' : $ext, 'mime' => $mime];
}

function eca_document_allowed(array $file, int $maxSize = 2097152): ?array
{
    return eca_uploaded_file_meta($file, eca_upload_mime_map(['pdf', 'jpg', 'jpeg', 'png']), $maxSize);
}

function eca_store_private_upload(array $file, int $clientId, string $type, mysqli $conn, int $maxSize = 2097152): ?array
{
    $meta = eca_document_allowed($file, $maxSize);
    if (!$meta) {
        return null;
    }
    $key = bin2hex(random_bytes(16));
    $storedName = $type . '_' . $clientId . '_' . $key . '.' . $meta['ext'];
    $abs = eca_private_documents_dir() . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $abs)) {
        return null;
    }
    $original = basename((string) ($file['name'] ?? 'document'));
    $stmt = $conn->prepare(
        'INSERT INTO tbl_client_documents (client_id, document_type, file_name, file_path, storage_key, original_name)
         VALUES (?,?,?,?,?,?)'
    );
    if (!$stmt) {
        @unlink($abs);
        return null;
    }
    $stmt->bind_param('isssss', $clientId, $type, $storedName, $key, $key, $original);
    $ok = $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    if (!$ok) {
        @unlink($abs);
        return null;
    }
    return ['id' => $id, 'storage_key' => $key, 'file_name' => $storedName];
}

function eca_document_absolute_path(array $row): ?string
{
    $name = basename((string) ($row['file_name'] ?? ''));
    if ($name === '' || str_contains($name, '..')) {
        return null;
    }
    $private = eca_private_documents_dir() . DIRECTORY_SEPARATOR . $name;
    if (is_file($private)) {
        return $private;
    }
    $legacy = eca_legacy_documents_dir() . DIRECTORY_SEPARATOR . $name;
    if (is_file($legacy)) {
        return $legacy;
    }
    $rel = str_replace(['\\', '..'], ['/', ''], (string) ($row['file_path'] ?? ''));
    if ($rel !== '' && !str_contains($rel, '://')) {
        $candidate = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($rel, '/'));
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

function eca_member_owns_client(?array $member, int $clientId, $portal): bool
{
    if (!$member || $clientId < 1 || !$portal) {
        return false;
    }
    $membership = trim((string) ($member['membership'] ?? ''));
    try {
        if ($portal instanceof PDO) {
            if ($membership !== '') {
                $stmt = $portal->prepare('SELECT client_id FROM tbl_client WHERE client_id = ? AND MembershipNumber = ? LIMIT 1');
                $stmt->execute([$clientId, $membership]);
                return (bool) $stmt->fetchColumn();
            }
        }
    } catch (Throwable $e) {
        return false;
    }
    return false;
}

function eca_document_type_label(string $type): string
{
    $key = strtolower(trim($type));
    $labels = [
        'id_copy' => 'ID copy',
        'payment' => 'Proof of payment',
        'proof_payment' => 'Proof of payment',
        'form_j' => 'Form J',
        'form_c' => 'Form C',
        'licence' => 'Trading licence',
        'trading_licence' => 'Trading licence',
        'certificate' => 'Certificate of incorporation',
        'certificate_incorporation' => 'Certificate of incorporation',
    ];
    if (isset($labels[$key])) {
        return $labels[$key];
    }
    if ($key === '') {
        return 'Document';
    }
    return ucwords(str_replace(['_', '-'], ' ', $key));
}

function eca_document_preview_kind(array $doc, ?string $path = null): string
{
    $name = (string) ($path ?: ($doc['file_name'] ?? $doc['original_name'] ?? ''));
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
        return 'image';
    }
    if ($ext === 'pdf') {
        return 'pdf';
    }
    return 'file';
}

function eca_document_is_payment_proof(array $doc): bool
{
    $type = strtolower(trim((string) ($doc['document_type'] ?? '')));
    return in_array($type, ['payment', 'proof_payment', 'proof-of-payment', 'pop'], true);
}

function eca_can_access_client_document(array $doc): bool
{
    $admin = function_exists('eca_admin_user') ? eca_admin_user(true) : null;
    if ($admin && function_exists('eca_can')) {
        $role = (string) ($admin['role'] ?? '');
        foreach (['documents.manage', 'applications.review', 'applications.manage', 'members.manage'] as $perm) {
            if (eca_can($perm, $role)) {
                return true;
            }
        }
        // Finance officers may open payment proof documents only.
        if (eca_can('payments.manage', $role) && eca_document_is_payment_proof($doc)) {
            return true;
        }
    }
    $clientId = (int) ($doc['client_id'] ?? 0);
    $member = function_exists('eca_current_member')
        ? eca_current_member(true)
        : ($_SESSION['eca_member'] ?? null);
    if (!$member || $clientId < 1) {
        return false;
    }
    require_once __DIR__ . '/portal-db.php';
    return eca_member_owns_client($member, $clientId, eca_portal_pdo(false));
}

/**
 * Resolve a payments.proof_file storage_key to a tbl_client_documents row.
 */
function eca_find_payment_proof_document(PDO $conn, string $proofFile): ?array
{
    $key = trim($proofFile);
    if ($key === '') {
        return null;
    }
    try {
        $stmt = $conn->prepare(
            'SELECT id, client_id, document_type, original_name, file_name, file_path, storage_key
             FROM tbl_client_documents
             WHERE storage_key = ? OR file_name = ? OR file_path = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$key, $key, $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function eca_member_document_rows(?PDO $conn, array $member, int $limit = 100): array
{
    $clientId = (int) ($member['client_id'] ?? 0);
    if (!$conn || $clientId < 1 || !eca_member_owns_client($member, $clientId, $conn)) {
        return [];
    }
    try {
        $stmt = $conn->prepare(
            'SELECT id, document_type, original_name, reviewed_at
             FROM tbl_client_documents
             WHERE client_id = ?
             ORDER BY id DESC LIMIT ' . max(1, min(200, $limit))
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}
