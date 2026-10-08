<?php
// helpers.php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect($path){
  header("Location: $path");
  exit();
}

function is_post(){ return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

function cpd_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['cpd_csrf'])) {
        $_SESSION['cpd_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['cpd_csrf'];
}

function cpd_csrf_valid(?string $token): bool
{
    $expected = cpd_csrf_token();
    return is_string($token) && $token !== '' && hash_equals($expected, $token);
}

function cpd_require_csrf(): void
{
    if (!is_post() || !cpd_csrf_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid or expired request token.');
    }
}

function cpd_csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(cpd_csrf_token()) . '">';
}

function cpd_rate_limit_exceeded(string $bucket, string $identity, int $limit, int $windowSeconds): bool
{
    $directory = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'rate-limits';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        return false;
    }
    $safeBucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket) ?: 'request';
    $file = $directory . DIRECTORY_SEPARATOR . $safeBucket . '_' . hash('sha256', $identity) . '.json';
    $handle = @fopen($file, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        if ($handle) fclose($handle);
        return false;
    }
    $raw = stream_get_contents($handle);
    $timestamps = json_decode($raw ?: '[]', true);
    $timestamps = is_array($timestamps) ? $timestamps : [];
    $cutoff = time() - $windowSeconds;
    $timestamps = array_values(array_filter(
        $timestamps,
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $cutoff
    ));
    $blocked = count($timestamps) >= $limit;
    if (!$blocked) {
        $timestamps[] = time();
    }
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($timestamps));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return $blocked;
}

function cpd_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function cpd_render_registration_closed(?mysqli $conn = null): void
{
    header('Location: /cpd/closed.php');
    exit;
}

function cpd_table_columns(mysqli $conn, string $table, bool $refresh = false): array
{
    static $cache = [];
    $key = strtolower($table);
    if ($refresh) {
        unset($cache[$key]);
    }
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $cache[$key] = [];
    $safe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    if ($safe === '') {
        return [];
    }
    try {
        $res = $conn->query('SHOW COLUMNS FROM `' . $safe . '`');
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cache[$key][strtolower((string) $row['Field'])] = true;
            }
        }
    } catch (Throwable $e) {
        return [];
    }
    return $cache[$key];
}

function cpd_table_has_column(mysqli $conn, string $table, string $column): bool
{
    return isset(cpd_table_columns($conn, $table)[strtolower($column)]);
}

function cpd_table_exists(mysqli $conn, string $table): bool
{
    $safe = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    if ($safe === '') {
        return false;
    }
    try {
        $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($safe) . "'");
        return $res instanceof mysqli_result && $res->num_rows > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function cpd_ensure_columns(mysqli $conn, string $table, array $needed): void
{
    if (function_exists('eca_is_live_readonly') && eca_is_live_readonly()) {
        return;
    }
    if (!cpd_table_exists($conn, $table)) {
        return;
    }
    $changed = false;
    foreach ($needed as $column => $definition) {
        if (cpd_table_has_column($conn, $table, $column)) {
            continue;
        }
        $safeCol = preg_replace('/[^A-Za-z0-9_]/', '', $column);
        if ($safeCol === '') {
            continue;
        }
        try {
            $conn->query('ALTER TABLE `' . preg_replace('/[^A-Za-z0-9_]/', '', $table) . '` ADD COLUMN `' . $safeCol . '` ' . $definition);
            $changed = true;
        } catch (Throwable $e) {
            // Pages fall back to the columns that already exist.
        }
    }
    if ($changed) {
        cpd_table_columns($conn, $table, true);
    }
}

function cpd_ensure_support_ticket_schema(mysqli $conn): void
{
    if (!cpd_table_exists($conn, 'support_tickets')) {
        try {
            $conn->query(
                "CREATE TABLE support_tickets (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    ticket_no VARCHAR(64) DEFAULT NULL,
                    user_id INT UNSIGNED DEFAULT NULL,
                    full_name VARCHAR(190) DEFAULT NULL,
                    email VARCHAR(190) DEFAULT NULL,
                    subject VARCHAR(255) DEFAULT NULL,
                    category VARCHAR(128) DEFAULT NULL,
                    priority VARCHAR(32) DEFAULT NULL,
                    message TEXT,
                    admin_reply TEXT,
                    status VARCHAR(32) DEFAULT 'Open',
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY idx_support_status (status),
                    KEY idx_support_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (Throwable $e) {
            return;
        }
        cpd_table_columns($conn, 'support_tickets', true);
        return;
    }
    cpd_ensure_columns($conn, 'support_tickets', [
        'ticket_no' => 'VARCHAR(64) DEFAULT NULL',
        'user_id' => 'INT UNSIGNED DEFAULT NULL',
        'full_name' => 'VARCHAR(190) DEFAULT NULL',
        'email' => 'VARCHAR(190) DEFAULT NULL',
        'category' => 'VARCHAR(128) DEFAULT NULL',
        'priority' => 'VARCHAR(32) DEFAULT NULL',
        'message' => 'TEXT',
        'admin_reply' => 'TEXT',
        'updated_at' => 'DATETIME DEFAULT NULL',
    ]);
}

function cpd_ensure_feedback_table(mysqli $conn): void
{
    if (cpd_table_exists($conn, 'feedback')) {
        cpd_ensure_columns($conn, 'feedback', [
            'user_id' => 'INT UNSIGNED DEFAULT NULL',
            'email' => 'VARCHAR(190) DEFAULT NULL',
            'full_name' => 'VARCHAR(190) DEFAULT NULL',
            'subject' => 'VARCHAR(255) DEFAULT NULL',
            'rating' => 'TINYINT DEFAULT NULL',
            'message' => 'TEXT',
            'course_id' => 'INT UNSIGNED DEFAULT NULL',
            'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        ]);
        return;
    }
    try {
        $conn->query(
            "CREATE TABLE feedback (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED DEFAULT NULL,
                email VARCHAR(190) DEFAULT NULL,
                full_name VARCHAR(190) DEFAULT NULL,
                subject VARCHAR(255) DEFAULT NULL,
                rating TINYINT DEFAULT NULL,
                message TEXT,
                course_id INT UNSIGNED DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_feedback_email (email),
                KEY idx_feedback_rating (rating)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        cpd_table_columns($conn, 'feedback', true);
    } catch (Throwable $e) {
        // Feedback pages show an empty state if the table cannot be created.
    }
}

function cpd_status_equals_sql(string $column, array $values): string
{
    $quoted = [];
    foreach ($values as $value) {
        $quoted[] = "'" . strtolower(trim((string) $value)) . "'";
    }
    return 'LOWER(TRIM(COALESCE(' . $column . ",''))) IN (" . implode(',', $quoted) . ')';
}

function cpd_pending_application_count(mysqli $conn): int
{
    $pendingSql = cpd_status_equals_sql('status', ['pending', 'new', 'submitted', 'open']);
    foreach (['cpd_applications', 'course_applications'] as $table) {
        if (!cpd_table_exists($conn, $table)) {
            continue;
        }
        try {
            $q = $conn->query('SELECT COUNT(*) c FROM `' . $table . '` WHERE ' . $pendingSql);
            if ($q) {
                return (int) ($q->fetch_assoc()['c'] ?? 0);
            }
        } catch (Throwable $e) {
            continue;
        }
    }
    return 0;
}

function cpd_ensure_wallet_transaction_columns(mysqli $conn): void
{
    if (!cpd_table_exists($conn, 'wallet_transactions')) {
        return;
    }
    $needed = [
        'transaction_id' => 'VARCHAR(64) DEFAULT NULL',
        'approved_at' => 'DATETIME DEFAULT NULL',
        'remote_response' => 'TEXT',
    ];
    $changed = false;
    foreach ($needed as $column => $definition) {
        if (cpd_table_has_column($conn, 'wallet_transactions', $column)) {
            continue;
        }
        try {
            $conn->query('ALTER TABLE wallet_transactions ADD COLUMN `' . $column . '` ' . $definition);
            $changed = true;
        } catch (Throwable $e) {
            // Adaptive queries still work if ALTER is denied.
        }
    }
    if ($changed) {
        cpd_table_columns($conn, 'wallet_transactions', true);
    }
}

function cpd_password_ok(?array $user, string $password): bool
{
    if (!$user) {
        return false;
    }

    $hash = (string) ($user['password_hash'] ?? '');
    if ($hash !== '' && password_verify($password, $hash)) {
        return true;
    }

    return false;
}