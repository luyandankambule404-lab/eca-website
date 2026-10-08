<?php

function eca_project_dir(): string
{
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'projects';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function eca_ensure_project_table(PDO $conn): void
{
    eca_ensure_project_tables($conn);
}

function eca_ensure_project_tables(PDO $conn): void
{
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS member_projects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            membership_number VARCHAR(64) NOT NULL,
            client_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            client_entity VARCHAR(255) DEFAULT NULL,
            contract_type VARCHAR(64) DEFAULT NULL,
            start_date DATE DEFAULT NULL,
            finish_date DATE DEFAULT NULL,
            status VARCHAR(32) NOT NULL DEFAULT \'ACTIVE\',
            progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
            pending_vo INT UNSIGNED NOT NULL DEFAULT 0,
            unapproved_vo_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            eot_date DATE DEFAULT NULL,
            eot_status VARCHAR(64) DEFAULT \'NOT REQUESTED\',
            intervention VARCHAR(32) DEFAULT \'No\',
            assigned_staff VARCHAR(190) DEFAULT NULL,
            dispute_level VARCHAR(190) DEFAULT NULL,
            next_action VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_member_projects_membership (membership_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS member_project_files (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            membership_number VARCHAR(64) NOT NULL,
            client_id INT UNSIGNED DEFAULT NULL,
            project_id INT UNSIGNED DEFAULT NULL,
            title VARCHAR(255) DEFAULT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            mime VARCHAR(128) DEFAULT NULL,
            file_size INT UNSIGNED DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_member_projects_membership (membership_number),
            KEY idx_member_project_files_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    try {
        $col = $conn->query("SHOW COLUMNS FROM member_project_files LIKE 'project_id'");
        if ($col && !$col->fetch(PDO::FETCH_ASSOC)) {
            $conn->exec('ALTER TABLE member_project_files ADD COLUMN project_id INT UNSIGNED DEFAULT NULL AFTER client_id');
            $conn->exec('ALTER TABLE member_project_files ADD KEY idx_member_project_files_project (project_id)');
        }
    } catch (Throwable $e) {
        // Column already present, or the table was created with it.
    }
    if (function_exists('eca_project_folder_schema')) {
        eca_project_folder_schema($conn);
    }
}

function eca_project_allowed(array $file, int $maxSize = 10485760): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxSize) {
        return null;
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowed = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'doc' => ['application/msword'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ],
    ];
    if (!isset($allowed[$ext])) {
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed[$ext], true)) {
        return null;
    }
    return ['ext' => $ext === 'jpeg' ? 'jpg' : $ext, 'mime' => $mime];
}

function eca_project_list(PDO $conn, string $membership, int $limit = 50, int $projectId = 0): array
{
    if ($membership === '') {
        return [];
    }
    eca_ensure_project_tables($conn);
    $sql = 'SELECT id, project_id, title, original_name, mime, file_size, created_at
         FROM member_project_files
         WHERE membership_number = ?';
    $params = [$membership];
    if ($projectId > 0) {
        $sql .= ' AND project_id = ?';
        $params[] = $projectId;
    }
    $sql .= ' ORDER BY id DESC LIMIT ' . (int) $limit;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function eca_project_store(PDO $conn, array $member, array $file, string $title = '', int $projectId = 0): ?array
{
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($membership === '') {
        return null;
    }
    $meta = eca_project_allowed($file);
    if (!$meta) {
        return null;
    }
    eca_ensure_project_tables($conn);
    if ($projectId < 1) {
        $projectId = eca_project_ensure_for_upload($conn, $member, $title);
    } elseif (!eca_project_get($conn, $projectId, $membership)) {
        return null;
    }
    $stored = 'project_' . preg_replace('/[^A-Za-z0-9_-]/', '', $membership) . '_' . bin2hex(random_bytes(8)) . '.' . $meta['ext'];
    $abs = eca_project_dir() . DIRECTORY_SEPARATOR . $stored;
    if (!move_uploaded_file($file['tmp_name'], $abs)) {
        return null;
    }
    $original = basename((string) ($file['name'] ?? 'file'));
    $title = trim($title) !== '' ? trim($title) : pathinfo($original, PATHINFO_FILENAME);
    $clientId = (int) ($member['client_id'] ?? 0);
    $size = (int) ($file['size'] ?? filesize($abs));
    $stmt = $conn->prepare(
        'INSERT INTO member_project_files (membership_number, client_id, project_id, title, original_name, stored_name, mime, file_size)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $membership,
        $clientId > 0 ? $clientId : null,
        $projectId > 0 ? $projectId : null,
        $title,
        $original,
        $stored,
        $meta['mime'],
        $size,
    ]);
    return ['id' => (int) $conn->lastInsertId(), 'stored_name' => $stored, 'project_id' => $projectId];
}

function eca_project_owned_row(PDO $conn, int $id, string $membership): ?array
{
    if ($id < 1 || $membership === '') {
        return null;
    }
    eca_ensure_project_tables($conn);
    $stmt = $conn->prepare(
        'SELECT * FROM member_project_files WHERE id = ? AND membership_number = ? LIMIT 1'
    );
    $stmt->execute([$id, $membership]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function eca_project_delete(PDO $conn, int $id, string $membership): bool
{
    $row = eca_project_owned_row($conn, $id, $membership);
    if (!$row) {
        return false;
    }
    $path = eca_project_dir() . DIRECTORY_SEPARATOR . basename((string) $row['stored_name']);
    $stmt = $conn->prepare('DELETE FROM member_project_files WHERE id = ? AND membership_number = ?');
    $stmt->execute([$id, $membership]);
    if (is_file($path)) {
        @unlink($path);
    }
    return true;
}

function eca_project_size_label(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 1) . ' MB';
}

function eca_project_contract_types(): array
{
    return ['Lump Sum', 'Re-measurable', 'Cost Plus', 'Design & Build', 'Other'];
}

function eca_project_status_options(): array
{
    return ['ACTIVE' => 'Active', 'COMPLETED' => 'Completed', 'ON HOLD' => 'On hold'];
}

function eca_project_eot_options(): array
{
    return ['NOT REQUESTED', 'REQUESTED', 'APPROVED', 'REJECTED'];
}

function eca_project_blank(): array
{
    return [
        'id' => 0,
        'title' => '',
        'description' => '',
        'client_entity' => '',
        'contract_type' => 'Lump Sum',
        'start_date' => '',
        'finish_date' => '',
        'status' => 'ACTIVE',
        'progress' => 0,
        'pending_vo' => 0,
        'unapproved_vo_value' => '0.00',
        'eot_date' => '',
        'eot_status' => 'NOT REQUESTED',
        'intervention' => 'No',
        'assigned_staff' => '',
        'dispute_level' => '',
        'next_action' => '',
        'contract_value' => '0.00',
        'location' => '',
        'current_stage' => '',
        'manager_name' => '',
        'manager_phone' => '',
        'manager_email' => '',
        'health_status' => 'on_track',
        'current_challenge' => '',
        'issue_type' => '',
        'issue_amount' => '0.00',
        'issue_days' => 0,
        'issue_impact' => '',
        'eca_action' => '',
        'eca_action_status' => '',
    ];
}

function eca_project_records(PDO $conn, string $membership): array
{
    if ($membership === '') {
        return [];
    }
    eca_ensure_project_tables($conn);
    $stmt = $conn->prepare(
        'SELECT * FROM member_projects WHERE membership_number = ? ORDER BY updated_at DESC, id DESC'
    );
    $stmt->execute([$membership]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function eca_project_get(PDO $conn, int $id, string $membership): ?array
{
    if ($id < 1 || $membership === '') {
        return null;
    }
    eca_ensure_project_tables($conn);
    $stmt = $conn->prepare(
        'SELECT * FROM member_projects WHERE id = ? AND membership_number = ? LIMIT 1'
    );
    $stmt->execute([$id, $membership]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function eca_project_sanitize_date($value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return $dt ? $dt->format('Y-m-d') : null;
}

function eca_project_save(PDO $conn, array $member, array $input): int
{
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($membership === '') {
        return 0;
    }
    eca_ensure_project_tables($conn);
    $id = (int) ($input['id'] ?? 0);
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        $title = 'Untitled project';
    }
    $types = eca_project_contract_types();
    $contract = trim((string) ($input['contract_type'] ?? 'Lump Sum'));
    if (!in_array($contract, $types, true)) {
        $contract = 'Lump Sum';
    }
    $statusKey = strtoupper(trim((string) ($input['status'] ?? 'ACTIVE')));
    if (!isset(eca_project_status_options()[$statusKey])) {
        $statusKey = 'ACTIVE';
    }
    $eot = strtoupper(trim((string) ($input['eot_status'] ?? 'NOT REQUESTED')));
    if (!in_array($eot, eca_project_eot_options(), true)) {
        $eot = 'NOT REQUESTED';
    }
    $intervention = strcasecmp((string) ($input['intervention'] ?? 'No'), 'Yes') === 0 ? 'Yes' : 'No';
    $progress = max(0, min(100, (int) ($input['progress'] ?? 0)));
    $pendingVo = max(0, (int) ($input['pending_vo'] ?? 0));
    $voValue = preg_replace('/[^0-9.]/', '', (string) ($input['unapproved_vo_value'] ?? '0'));
    $voValue = $voValue === '' ? '0.00' : number_format((float) $voValue, 2, '.', '');
    $clientId = (int) ($member['client_id'] ?? 0);
    $fields = [
        $title,
        trim((string) ($input['description'] ?? '')),
        trim((string) ($input['client_entity'] ?? '')),
        $contract,
        eca_project_sanitize_date($input['start_date'] ?? ''),
        eca_project_sanitize_date($input['finish_date'] ?? ''),
        $statusKey,
        $progress,
        $pendingVo,
        $voValue,
        eca_project_sanitize_date($input['eot_date'] ?? ''),
        $eot,
        $intervention,
        trim((string) ($input['assigned_staff'] ?? '')),
        trim((string) ($input['dispute_level'] ?? '')),
        trim((string) ($input['next_action'] ?? '')),
    ];
    if ($id > 0) {
        if (!eca_project_get($conn, $id, $membership)) {
            return 0;
        }
        $stmt = $conn->prepare(
            'UPDATE member_projects SET
                title = ?, description = ?, client_entity = ?, contract_type = ?,
                start_date = ?, finish_date = ?, status = ?, progress = ?,
                pending_vo = ?, unapproved_vo_value = ?, eot_date = ?, eot_status = ?,
                intervention = ?, assigned_staff = ?, dispute_level = ?, next_action = ?
             WHERE id = ? AND membership_number = ?'
        );
        $stmt->execute(array_merge($fields, [$id, $membership]));
        if (function_exists('eca_project_save_folder_fields')) {
            eca_project_save_folder_fields($conn, $id, $membership, $input);
        }
        return $id;
    }
    $stmt = $conn->prepare(
        'INSERT INTO member_projects (
            membership_number, client_id, title, description, client_entity, contract_type,
            start_date, finish_date, status, progress, pending_vo, unapproved_vo_value,
            eot_date, eot_status, intervention, assigned_staff, dispute_level, next_action
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute(array_merge(
        [$membership, $clientId > 0 ? $clientId : null],
        $fields
    ));
    $id = (int) $conn->lastInsertId();
    if ($id > 0 && function_exists('eca_project_save_folder_fields')) {
        eca_project_save_folder_fields($conn, $id, $membership, $input);
    }
    return $id;
}

function eca_project_delete_record(PDO $conn, int $id, string $membership): bool
{
    $row = eca_project_get($conn, $id, $membership);
    if (!$row) {
        return false;
    }
    $files = eca_project_list($conn, $membership, 500, $id);
    foreach ($files as $file) {
        eca_project_delete($conn, (int) $file['id'], $membership);
    }
    $stmt = $conn->prepare('DELETE FROM member_projects WHERE id = ? AND membership_number = ?');
    $stmt->execute([$id, $membership]);
    return true;
}

function eca_project_cover(PDO $conn, int $projectId, string $membership): ?array
{
    if ($projectId < 1 || $membership === '') {
        return null;
    }
    eca_ensure_project_tables($conn);
    $stmt = $conn->prepare(
        "SELECT * FROM member_project_files
         WHERE membership_number = ? AND project_id = ? AND mime LIKE 'image/%'
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([$membership, $projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function eca_project_ensure_for_upload(PDO $conn, array $member, string $title = ''): int
{
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($membership === '') {
        return 0;
    }
    eca_project_backfill($conn, $membership, $member);
    $rows = eca_project_records($conn, $membership);
    if ($rows) {
        return (int) $rows[0]['id'];
    }
    $label = trim($title) !== '' ? trim($title) : 'Project portfolio';
    return eca_project_save($conn, $member, [
        'title' => $label,
        'status' => 'ACTIVE',
        'progress' => 0,
        'contract_type' => 'Lump Sum',
        'eot_status' => 'NOT REQUESTED',
        'intervention' => 'No',
    ]);
}

function eca_project_backfill(PDO $conn, string $membership, array $member): void
{
    if ($membership === '') {
        return;
    }
    eca_ensure_project_tables($conn);
    $stmt = $conn->prepare(
        'SELECT id, title FROM member_project_files WHERE membership_number = ? AND (project_id IS NULL OR project_id = 0) ORDER BY id ASC'
    );
    $stmt->execute([$membership]);
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$orphans) {
        return;
    }
    $rows = eca_project_records($conn, $membership);
    $projectId = $rows ? (int) $rows[0]['id'] : eca_project_save($conn, $member, [
        'title' => trim((string) ($orphans[0]['title'] ?? '')) ?: 'Project portfolio',
        'status' => 'ACTIVE',
        'progress' => 0,
        'contract_type' => 'Lump Sum',
        'eot_status' => 'NOT REQUESTED',
        'intervention' => 'No',
    ]);
    if ($projectId < 1) {
        return;
    }
    $upd = $conn->prepare(
        'UPDATE member_project_files SET project_id = ? WHERE membership_number = ? AND (project_id IS NULL OR project_id = 0)'
    );
    $upd->execute([$projectId, $membership]);
}

function eca_project_stats(PDO $conn, string $membership): array
{
    $rows = eca_project_records($conn, $membership);
    $completed = [];
    $years = [];
    foreach ($rows as $row) {
        if (strtoupper((string) ($row['status'] ?? '')) === 'COMPLETED') {
            $completed[] = $row;
        }
        foreach (['start_date', 'finish_date'] as $key) {
            $date = (string) ($row[$key] ?? '');
            if ($date !== '' && preg_match('/^(\d{4})/', $date, $m)) {
                $years[] = (int) $m[1];
            }
        }
    }
    $year = (int) date('Y');
    return [
        'total' => count($rows),
        'completed' => count($completed),
        'cycle' => $year . '/' . ($year + 1),
        'range' => $years ? min($years) . '-' . max($years) : (string) $year,
    ];
}

function eca_project_format_date($value): string
{
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00') {
        return 'N/A';
    }
    $ts = strtotime($value);
    return $ts ? date('d M Y', $ts) : 'N/A';
}

function eca_project_na($value, string $fallback = 'N/A'): string
{
    $value = trim((string) $value);
    return $value === '' ? $fallback : $value;
}

function eca_project_money($value): string
{
    return 'E ' . number_format((float) $value, 2, '.', ',');
}

function eca_project_status_label(string $status): string
{
    $status = strtoupper(trim($status));
    return eca_project_status_options()[$status] ?? ($status !== '' ? ucfirst(strtolower($status)) : 'Active');
}

function eca_project_flash(string $message): void
{
    $_SESSION['eca_project_notice'] = $message;
}

require_once __DIR__ . '/projects-folder.php';
