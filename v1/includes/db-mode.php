<?php

require_once __DIR__ . '/env.php';

/**
 * Local development stays on the existing databases.
 * live_readonly reads the production database with a SELECT-only account.
 * Authentication always stays on the local databases.
 */

function eca_app_env(): string
{
    $env = strtolower(trim(eca_env('APP_ENV', 'local')));
    if ($env === 'live_readonly') {
        return 'live_readonly';
    }
    if (in_array($env, ['production', 'prod', 'live'], true)) {
        return 'production';
    }
    return 'local';
}

function eca_is_live_readonly(): bool
{
    return eca_app_env() === 'live_readonly';
}

/**
 * True when APP_ENV is production/prod/live.
 * Fail-closed gates (preview SSO, legacy registration, tool password fallbacks) use this.
 */
function eca_is_production(): bool
{
    return eca_app_env() === 'production';
}

/**
 * Hub→member and CPD→Hub preview/SSO is for local QA only unless explicitly allowed.
 * Production: disabled unless ECA_ALLOW_PORTAL_PREVIEW=1 (not recommended).
 */
function eca_portal_preview_allowed(): bool
{
    if (eca_is_production()) {
        return eca_env('ECA_ALLOW_PORTAL_PREVIEW', '') === '1';
    }
    return true;
}

/**
 * Apply production PHP error display policy (no stack traces to clients).
 */
function eca_apply_production_error_policy(): void
{
    if (!eca_is_production()) {
        return;
    }
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('html_errors', '0');
}

function eca_db_identifier_ok(string $value): bool
{
    return $value !== '' && (bool) preg_match('/^[A-Za-z0-9._:-]+$/', $value);
}

function eca_live_db_config(): array
{
    $port = (int) eca_env('LIVE_DB_PORT', '3306');
    if ($port < 1 || $port > 65535) {
        $port = 3306;
    }
    return [
        'host' => trim(eca_env('LIVE_DB_HOST', '')),
        'port' => $port,
        'name' => trim(eca_env('LIVE_DB_NAME', '')),
        'user' => trim(eca_env('LIVE_DB_USER', '')),
        'pass' => eca_env('LIVE_DB_PASSWORD', ''),
    ];
}

function eca_live_db_ready(): bool
{
    if (!eca_is_live_readonly()) {
        return false;
    }
    $cfg = eca_live_db_config();
    if (!eca_db_identifier_ok($cfg['host']) || !eca_db_identifier_ok($cfg['name']) || $cfg['user'] === '') {
        return false;
    }
    $host = strtolower($cfg['host']);
    $name = strtolower($cfg['name']);
    $loopback = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    if ($loopback && in_array($name, ['eca_local', 'eca_portal_local'], true)) {
        return false;
    }
    return true;
}

function eca_live_db_block_reason(): string
{
    if (!eca_is_live_readonly()) {
        return '';
    }
    $cfg = eca_live_db_config();
    if ($cfg['host'] === '' || $cfg['name'] === '' || $cfg['user'] === '') {
        return 'Production read-only credentials are not configured.';
    }
    if (!eca_db_identifier_ok($cfg['host']) || !eca_db_identifier_ok($cfg['name'])) {
        return 'Production database host or name is not in a safe format.';
    }
    $host = strtolower($cfg['host']);
    $name = strtolower($cfg['name']);
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) && in_array($name, ['eca_local', 'eca_portal_local'], true)) {
        return 'Live read-only mode is pointing at a local development database.';
    }
    return '';
}

function eca_db_mode_status(): array
{
    $live = eca_is_live_readonly();
    $prod = eca_is_production();
    $label = $prod ? 'PRODUCTION' : ($live ? 'LIVE READ-ONLY' : 'LOCAL');
    return [
        'env' => eca_app_env(),
        'label' => $label,
        'database' => $live ? 'Production' : 'Local Development',
        'writes' => $live ? 'DISABLED' : 'ENABLED',
        'ready' => $live ? eca_live_db_ready() : true,
        'reason' => eca_live_db_block_reason(),
    ];
}

function eca_db_mode_banner(): void
{
    $status = eca_db_mode_status();
    $live = $status['env'] === 'live_readonly';
    $class = $live ? 'eca-db-mode is-live' : 'eca-db-mode is-local';
    echo '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" role="status">';
    echo '<strong>' . ($live ? 'LIVE DATA — READ ONLY' : 'LOCAL DEVELOPMENT') . '</strong>';
    echo '<span>Environment: ' . htmlspecialchars($status['label'], ENT_QUOTES, 'UTF-8') . '</span>';
    echo '<span>Database: ' . htmlspecialchars($status['database'], ENT_QUOTES, 'UTF-8') . '</span>';
    echo '<span>Write operations: ' . htmlspecialchars($status['writes'], ENT_QUOTES, 'UTF-8') . '</span>';
    if ($live && $status['reason'] !== '') {
        echo '<span>' . htmlspecialchars($status['reason'], ENT_QUOTES, 'UTF-8') . ' Local records are not being shown as production data.</span>';
    }
    echo '</div>';
}

function eca_sql_is_read_only(string $sql): bool
{
    $stripped = preg_replace('/\/\*.*?\*\//s', ' ', $sql) ?? $sql;
    $stripped = preg_replace('/--[^\n]*/', ' ', $stripped) ?? $stripped;
    $stripped = preg_replace('/#[^\n]*/', ' ', $stripped) ?? $stripped;
    $parts = preg_split('/;/', $stripped) ?: [];
    $saw = false;
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $saw = true;
        $allowed = '/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN|WITH|SET\s+NAMES|SET\s+CHARACTER\s+SET|SET\s+SESSION\s+TRANSACTION\s+READ\s+ONLY|USE)\b/i';
        if (!preg_match($allowed, $part)) {
            return false;
        }
        $blocked = '/\b(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE|CREATE|RENAME|GRANT|REVOKE|LOAD|CALL|HANDLER|LOCK|UNLOCK|OPTIMIZE|REPAIR|ANALYZE|FLUSH|KILL|INSTALL|UNINSTALL|PURGE|RESET|CHANGE|OUTFILE|DUMPFILE|UPSERT)\b/i';
        if (preg_match($blocked, $part)) {
            return false;
        }
    }
    return $saw;
}

function eca_read_only_log(string $message): void
{
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private';
    if (!is_dir($dir)) {
        return;
    }
    $line = gmdate('c') . ' ' . $message . PHP_EOL;
    @file_put_contents($dir . DIRECTORY_SEPARATOR . 'db-guard.log', $line, FILE_APPEND | LOCK_EX);
}

function eca_assert_read_only_sql(string $sql): void
{
    if (!eca_is_live_readonly() || eca_sql_is_read_only($sql)) {
        return;
    }
    $preview = preg_replace('/\s+/', ' ', trim($sql)) ?? '';
    $preview = preg_replace("/'[^']*'/", '?', $preview) ?? $preview;
    $preview = preg_replace('/"[^"]*"/', '?', $preview) ?? $preview;
    if (strlen($preview) > 180) {
        $preview = substr($preview, 0, 180) . '…';
    }
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? (PHP_SAPI === 'cli' ? 'cli' : ''));
    eca_read_only_log('Blocked SQL on ' . $script . ': ' . $preview);
    throw new RuntimeException('Write operations are disabled in live read-only mode.');
}

function eca_live_readonly_auth_request(): bool
{
    $path = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $allowed = [
        '/admin/login.php',
        '/client/index.php',
        '/client/login.php',
        '/cpd/login.php',
        '/login.php',
    ];
    foreach ($allowed as $item) {
        if ($path === $item || str_ends_with($path, $item)) {
            return true;
        }
    }
    return false;
}

function eca_live_readonly_guard_http(): void
{
    if (PHP_SAPI === 'cli' || !eca_is_live_readonly()) {
        return;
    }
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }
    if (eca_live_readonly_auth_request()) {
        return;
    }
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    eca_read_only_log('Blocked ' . $method . ' ' . $script);
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Live read-only</title></head><body>';
    echo '<h1>LIVE READ-ONLY MODE</h1>';
    echo '<p>Modifications are disabled while the local site is reading the production database.</p>';
    echo '<p><a href="/admin/index.php">Back to the dashboard</a></p>';
    echo '</body></html>';
    exit;
}

class EcaReadOnlyPdo extends PDO
{
    public function __construct(string $dsn, string $user, string $pass)
    {
        parent::__construct($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        try {
            parent::exec('SET SESSION TRANSACTION READ ONLY');
        } catch (Throwable $e) {
            eca_read_only_log('Session could not be set transaction-read-only.');
        }
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        eca_assert_read_only_sql($query);
        return parent::prepare($query, $options);
    }

    #[\ReturnTypeWillChange]
    public function exec(string $statement): int|false
    {
        eca_assert_read_only_sql($statement);
        return parent::exec($statement);
    }

    #[\ReturnTypeWillChange]
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        eca_assert_read_only_sql($query);
        if ($fetchMode === null) {
            return parent::query($query);
        }
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }
}

class EcaReadOnlyMysqli extends mysqli
{
    public function __construct(string $host, string $user, string $pass, string $name, int $port)
    {
        parent::__construct($host, $user, $pass, $name, $port);
        try {
            parent::query('SET SESSION TRANSACTION READ ONLY');
        } catch (Throwable $e) {
            eca_read_only_log('Session could not be set transaction-read-only.');
        }
    }

    #[\ReturnTypeWillChange]
    public function query($query, $result_mode = MYSQLI_STORE_RESULT)
    {
        eca_assert_read_only_sql((string) $query);
        return parent::query($query, $result_mode);
    }

    public function real_query(string $query): bool
    {
        eca_assert_read_only_sql($query);
        return parent::real_query($query);
    }

    public function multi_query(string $query): bool
    {
        eca_assert_read_only_sql($query);
        return parent::multi_query($query);
    }

    #[\ReturnTypeWillChange]
    public function prepare($query)
    {
        eca_assert_read_only_sql((string) $query);
        return parent::prepare($query);
    }
}
