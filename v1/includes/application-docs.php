<?php

require_once __DIR__ . '/documents.php';
require_once __DIR__ . '/notify.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/membership.php';

function eca_application_document_types(): array
{
    return [
        'certificate_incorporation' => 'Certificate of incorporation',
        'trading_licence' => 'Trading licence',
        'form_c' => 'Form C',
        'form_j' => 'Form J',
        'id_copy' => 'ID copy',
        'proof_payment' => 'Proof of payment',
        'payment' => 'Proof of payment',
    ];
}

function eca_ensure_application_doc_requests(PDO $conn): void
{
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS application_doc_requests (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id INT UNSIGNED NOT NULL,
            application_reference VARCHAR(32) NOT NULL,
            document_type VARCHAR(64) NOT NULL,
            reason VARCHAR(16) NOT NULL DEFAULT \'missing\',
            note TEXT,
            status VARCHAR(16) NOT NULL DEFAULT \'open\',
            source_document_id INT UNSIGNED DEFAULT NULL,
            replacement_document_id INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_app_doc_req_client (client_id, status),
            KEY idx_app_doc_req_ref (application_reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function eca_application_doc_requests(PDO $conn, int $clientId, bool $openOnly = false): array
{
    if ($clientId < 1) {
        return [];
    }
    try {
        eca_ensure_application_doc_requests($conn);
        $sql = 'SELECT * FROM application_doc_requests WHERE client_id = ?';
        if ($openOnly) {
            $sql .= " AND status = 'open'";
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_application_open_doc_requests(PDO $conn, int $clientId): array
{
    return eca_application_doc_requests($conn, $clientId, true);
}

function eca_application_doc_request_lines(array $requests): array
{
    $lines = [];
    foreach ($requests as $row) {
        $type = eca_document_type_label((string) ($row['document_type'] ?? ''));
        $reason = strtolower((string) ($row['reason'] ?? 'missing')) === 'improper'
            ? 'improper / replace'
            : 'missing';
        $lines[] = $type . ' (' . $reason . ')';
    }
    return $lines;
}

function eca_notify_application_by_registration(array $app, string $title, string $message, string $link = ''): void
{
    $ref = trim((string) ($app['application_reference'] ?? ''));
    $membership = trim((string) ($app['MembershipNumber'] ?? $app['membership_number'] ?? ''));
    eca_notify([
        'client_id' => (int) ($app['client_id'] ?? 0),
        'membership_number' => $membership,
        'title' => $title,
        'message' => $message,
        'type' => 'APPLICATION',
        'link' => $link !== '' ? $link : ('/application-fix.php?ref=' . rawurlencode($ref)),
    ]);
}

function eca_mail_document_request(string $to, string $name, string $ref, array $lines, string $note = ''): bool
{
    $to = trim($to);
    $ref = trim($ref);
    if ($to === '' || $ref === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $track = htmlspecialchars(eca_application_track_url($ref), ENT_QUOTES, 'UTF-8');
    $fixPage = htmlspecialchars(eca_application_fix_url($ref), ENT_QUOTES, 'UTF-8');
    $list = '';
    foreach ($lines as $line) {
        $list .= '<li>' . htmlspecialchars((string) $line, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $noteHtml = $note !== ''
        ? '<p>' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</p>'
        : '';
    $html = '<p>Dear ' . htmlspecialchars($name !== '' ? $name : 'Applicant', ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>ECA needs a document update for application registration number <strong>'
        . htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
        . $noteHtml
        . ($list !== '' ? '<p>Please upload:</p><ul>' . $list . '</ul>' : '')
        . '<p>Use that registration number to upload the file and resend the application:</p>'
        . '<p><a href="' . $fixPage . '">Upload documents and resend</a></p>'
        . '<p>You can also track it here: <a href="' . $track . '">' . $track . '</a></p>';
    return eca_mail_send($to, 'ECA application ' . $ref . ' needs documents', $html);
}

function eca_application_request_documents(PDO $conn, array $app, array $items, string $note, string $adminEmail = ''): array
{
    $clientId = (int) ($app['client_id'] ?? 0);
    $ref = trim((string) ($app['application_reference'] ?? ''));
    if ($clientId < 1 || $ref === '' || $items === []) {
        return [];
    }
    eca_ensure_application_doc_requests($conn);
    $created = [];
    foreach ($items as $item) {
        $type = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string) ($item['document_type'] ?? '')))) ?? '';
        if ($type === '') {
            continue;
        }
        $reason = strtolower((string) ($item['reason'] ?? 'missing')) === 'improper' ? 'improper' : 'missing';
        $sourceId = (int) ($item['source_document_id'] ?? 0);
        $conn->prepare(
            "UPDATE application_doc_requests SET status = 'cleared', resolved_at = NOW()
             WHERE client_id = ? AND document_type = ? AND status = 'open'"
        )->execute([$clientId, $type]);
        $stmt = $conn->prepare(
            'INSERT INTO application_doc_requests
                (client_id, application_reference, document_type, reason, note, status, source_document_id)
             VALUES (?, ?, ?, ?, ?, \'open\', ?)'
        );
        $stmt->execute([
            $clientId,
            $ref,
            $type,
            $reason,
            $note !== '' ? $note : null,
            $sourceId > 0 ? $sourceId : null,
        ]);
        $created[] = [
            'id' => (int) $conn->lastInsertId(),
            'document_type' => $type,
            'reason' => $reason,
        ];
    }
    if (!$created) {
        return [];
    }
    $conn->prepare('UPDATE tbl_client SET application_status = ? WHERE client_id = ?')
        ->execute(['ADDITIONAL INFORMATION REQUIRED', $clientId]);
    $lines = eca_application_doc_request_lines($created);
    $message = 'Application registration number ' . $ref
        . ' needs documents: ' . implode(', ', $lines) . '.'
        . ($note !== '' ? ' ' . $note : '')
        . ' Upload the file and resend the application.';
    eca_notify_application_by_registration(
        $app + ['client_id' => $clientId],
        'Documents needed for ' . $ref,
        $message
    );
    eca_mail_document_request(
        (string) ($app['EmailAddress'] ?? ''),
        (string) ($app['TradingName'] ?? $app['CompanyRegistrationName'] ?? 'Applicant'),
        $ref,
        $lines,
        $note
    );
    eca_audit('application.returned', 'tbl_client', (string) $clientId, [
        'status' => 'ADDITIONAL INFORMATION REQUIRED',
        'ref' => $ref,
        'documents' => $lines,
        'actor' => $adminEmail,
    ]);
    eca_audit('document.request', 'tbl_client', (string) $clientId, [
        'ref' => $ref,
        'documents' => $lines,
    ]);
    if ($note !== '') {
        try {
            $conn->prepare(
                'INSERT INTO membership_application_notes (client_id, admin_email, note) VALUES (?,?,?)'
            )->execute([
                $clientId,
                $adminEmail !== '' ? $adminEmail : 'system',
                'Document request for ' . $ref . ': ' . implode(', ', $lines) . '. ' . $note,
            ]);
        } catch (Throwable $e) {
            // Notes table is optional for the applicant message.
        }
    }
    return $created;
}

function eca_store_private_upload_pdo(array $file, int $clientId, string $type, PDO $conn, int $maxSize = 2097152): ?array
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
    try {
        $stmt = $conn->prepare(
            'INSERT INTO tbl_client_documents (client_id, document_type, file_name, file_path, storage_key, original_name)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$clientId, $type, $storedName, $key, $key, $original]);
        return [
            'id' => (int) $conn->lastInsertId(),
            'storage_key' => $key,
            'file_name' => $storedName,
        ];
    } catch (Throwable $e) {
        @unlink($abs);
        return null;
    }
}

function eca_application_upload_requested_doc(PDO $conn, int $requestId, int $clientId, array $file): ?array
{
    if ($requestId < 1 || $clientId < 1) {
        return null;
    }
    eca_ensure_application_doc_requests($conn);
    $stmt = $conn->prepare(
        "SELECT * FROM application_doc_requests WHERE id = ? AND client_id = ? AND status = 'open' LIMIT 1"
    );
    $stmt->execute([$requestId, $clientId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$request) {
        return null;
    }
    $type = (string) ($request['document_type'] ?? 'document');
    $stored = eca_store_private_upload_pdo($file, $clientId, $type, $conn);
    if (!$stored) {
        return null;
    }
    $conn->prepare(
        "UPDATE application_doc_requests
         SET status = 'uploaded', replacement_document_id = ?, resolved_at = NOW()
         WHERE id = ? AND client_id = ?"
    )->execute([(int) $stored['id'], $requestId, $clientId]);
    return $stored + ['request_id' => $requestId, 'document_type' => $type];
}

function eca_application_resubmit(PDO $conn, array $app): bool
{
    $clientId = (int) ($app['client_id'] ?? 0);
    $ref = trim((string) ($app['application_reference'] ?? ''));
    if ($clientId < 1 || $ref === '') {
        return false;
    }
    $open = eca_application_open_doc_requests($conn, $clientId);
    if ($open) {
        return false;
    }
    $conn->prepare('UPDATE tbl_client SET application_status = ? WHERE client_id = ?')
        ->execute(['SUBMITTED', $clientId]);
    eca_audit('application.resubmitted', 'tbl_client', (string) $clientId, ['ref' => $ref]);
    eca_notify_application_by_registration(
        $app + ['client_id' => $clientId],
        'Application ' . $ref . ' resent',
        'Application registration number ' . $ref . ' was resent after the requested documents were uploaded. ECA will review it again.',
        '/track.php?ref=' . rawurlencode($ref)
    );
    eca_mail_status_update(
        (string) ($app['EmailAddress'] ?? ''),
        (string) ($app['TradingName'] ?? $app['CompanyRegistrationName'] ?? 'Applicant'),
        $ref,
        'SUBMITTED'
    );
    return true;
}

function eca_application_row_by_reference(PDO $conn, string $ref): ?array
{
    $ref = function_exists('eca_track_normalize_reference')
        ? eca_track_normalize_reference($ref)
        : strtoupper(trim($ref));
    if ($ref === '') {
        return null;
    }
    try {
        $stmt = $conn->prepare('SELECT * FROM tbl_client WHERE application_reference = ? LIMIT 1');
        $stmt->execute([$ref]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function eca_application_fix_allowed(?array $app, ?array $member, string $email = ''): bool
{
    if (!$app) {
        return false;
    }
    $clientId = (int) ($app['client_id'] ?? 0);
    $status = strtoupper(trim((string) ($app['application_status'] ?? '')));
    if ($clientId < 1 || $status !== 'ADDITIONAL INFORMATION REQUIRED') {
        return false;
    }
    if ($member) {
        $memberClient = (int) ($member['client_id'] ?? 0);
        $memberNo = trim((string) ($member['membership'] ?? ''));
        $appNo = trim((string) ($app['MembershipNumber'] ?? ''));
        if ($memberClient > 0 && $memberClient === $clientId) {
            return true;
        }
        if ($memberNo !== '' && $appNo !== '' && strcasecmp($memberNo, $appNo) === 0) {
            return true;
        }
    }
    $email = strtolower(trim($email));
    $appEmail = strtolower(trim((string) ($app['EmailAddress'] ?? '')));
    return $email !== '' && $appEmail !== '' && hash_equals($appEmail, $email);
}
