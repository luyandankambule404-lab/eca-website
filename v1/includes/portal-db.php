<?php

require_once __DIR__ . '/env.php';

function eca_local_portal_config(): array
{
    return [
        'host' => eca_env('ECA_PORTAL_DB_HOST', eca_env('ECA_DB_HOST', '127.0.0.1')),
        'port' => 3306,
        'name' => eca_env('ECA_PORTAL_DB_NAME', eca_env('ECA_DB_NAME', 'eca_local')),
        'user' => eca_env('ECA_PORTAL_DB_USER', eca_env('ECA_DB_USER', 'root')),
        'pass' => eca_env('ECA_PORTAL_DB_PASS', eca_env('ECA_DB_PASS', '')),
    ];
}

function eca_portal_config_for_reads(): ?array
{
    if (!eca_is_live_readonly()) {
        return eca_local_portal_config();
    }
    if (!eca_live_db_ready()) {
        return null;
    }
    return eca_live_db_config();
}

function eca_open_portal_mysqli(array $cfg, bool $readOnly): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    if ($readOnly) {
        $mysqli = new EcaReadOnlyMysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int) $cfg['port']);
    } else {
        $mysqli = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int) $cfg['port']);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function eca_open_portal_pdo(array $cfg, bool $readOnly): PDO
{
    $dsn = 'mysql:host=' . $cfg['host'] . ';port=' . (int) $cfg['port'] . ';dbname=' . $cfg['name'] . ';charset=utf8mb4';
    if ($readOnly) {
        $pdo = new EcaReadOnlyPdo($dsn, $cfg['user'], $cfg['pass']);
    } else {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_TIMEOUT => 2,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
    $pdo->exec('set names utf8mb4');
    return $pdo;
}

function eca_local_portal_mysqli(bool $failHard = true): ?mysqli
{
    static $conn = false;
    if ($conn !== false) {
        return $conn;
    }
    try {
        $conn = eca_open_portal_mysqli(eca_local_portal_config(), false);
    } catch (Throwable $e) {
        error_log('Local portal database connection failed.');
        $conn = null;
        if ($failHard) {
            http_response_code(500);
            exit('The local membership database is not available.');
        }
    }
    return $conn;
}

function eca_local_portal_pdo(bool $failHard = true): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    try {
        $pdo = eca_open_portal_pdo(eca_local_portal_config(), false);
    } catch (Throwable $e) {
        error_log('Local portal PDO connection failed.');
        $pdo = null;
        if ($failHard) {
            http_response_code(500);
            exit('The local portal database is not available.');
        }
    }
    return $pdo;
}

function eca_portal_mysqli(bool $failHard = true): ?mysqli
{
    static $conn = false;
    if ($conn !== false) {
        return $conn;
    }
    $cfg = eca_portal_config_for_reads();
    if ($cfg === null) {
        $conn = null;
        if ($failHard && eca_is_live_readonly()) {
            http_response_code(503);
            exit('The production read-only database is not available.');
        }
        if ($failHard) {
            http_response_code(500);
            exit('The local membership database is not available.');
        }
        return null;
    }
    try {
        $conn = eca_open_portal_mysqli($cfg, eca_is_live_readonly());
    } catch (Throwable $e) {
        error_log(eca_is_live_readonly()
            ? 'Production read-only database connection failed.'
            : 'Portal database connection failed.');
        $conn = null;
        if ($failHard) {
            http_response_code(eca_is_live_readonly() ? 503 : 500);
            exit(eca_is_live_readonly()
                ? 'The production read-only database is not available.'
                : 'The local membership database is not available.');
        }
    }
    return $conn;
}

function eca_portal_pdo(bool $failHard = true): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    $cfg = eca_portal_config_for_reads();
    if ($cfg === null) {
        $pdo = null;
        if ($failHard && eca_is_live_readonly()) {
            http_response_code(503);
            exit('The production read-only database is not available.');
        }
        if ($failHard) {
            http_response_code(500);
            exit('The local portal database is not available.');
        }
        return null;
    }
    try {
        $pdo = eca_open_portal_pdo($cfg, eca_is_live_readonly());
    } catch (Throwable $e) {
        error_log(eca_is_live_readonly()
            ? 'Production read-only PDO connection failed.'
            : 'Portal PDO connection failed.');
        $pdo = null;
        if ($failHard) {
            http_response_code(eca_is_live_readonly() ? 503 : 500);
            exit(eca_is_live_readonly()
                ? 'The production read-only database is not available.'
                : 'The local portal database is not available.');
        }
    }
    return $pdo;
}

function eca_smtp_ready(): bool
{
    return eca_env('ECA_SMTP_HOST') !== ''
        && eca_env('ECA_SMTP_USER') !== ''
        && eca_env('ECA_SMTP_PASS') !== '';
}

function eca_configure_smtp(object $mail): bool
{
    if (!eca_smtp_ready()) {
        return false;
    }
    $port = (int) eca_env('ECA_SMTP_PORT', '587');
    if ($port <= 0) {
        $port = 587;
    }
    $mail->isSMTP();
    $mail->Host = eca_env('ECA_SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = eca_env('ECA_SMTP_USER');
    $mail->Password = eca_env('ECA_SMTP_PASS');
    $mail->Port = $port;
    $mail->SMTPSecure = $port === 465 ? 'ssl' : 'tls';
    return true;
}
