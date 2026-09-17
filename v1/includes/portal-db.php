<?php

require_once __DIR__ . '/env.php';

function eca_portal_mysqli(bool $failHard = true): ?mysqli
{
    static $conn = false;
    if ($conn !== false) {
        return $conn;
    }

    $host = eca_env('ECA_PORTAL_DB_HOST', eca_env('ECA_DB_HOST', '127.0.0.1'));
    $name = eca_env('ECA_PORTAL_DB_NAME', eca_env('ECA_DB_NAME', 'eca_local'));
    $user = eca_env('ECA_PORTAL_DB_USER', eca_env('ECA_DB_USER', 'root'));
    $pass = eca_env('ECA_PORTAL_DB_PASS', eca_env('ECA_DB_PASS', ''));

    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqli = new mysqli($host, $user, $pass, $name);
        $mysqli->set_charset('utf8mb4');
        $conn = $mysqli;
    } catch (Throwable $e) {
        error_log('Portal database connection error: ' . $e->getMessage());
        $conn = null;
        if ($failHard) {
            http_response_code(500);
            exit('The local membership database is not available.');
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

    $host = eca_env('ECA_PORTAL_DB_HOST', eca_env('ECA_DB_HOST', '127.0.0.1'));
    $name = eca_env('ECA_PORTAL_DB_NAME', eca_env('ECA_DB_NAME', 'eca_local'));
    $user = eca_env('ECA_PORTAL_DB_USER', eca_env('ECA_DB_USER', 'root'));
    $pass = eca_env('ECA_PORTAL_DB_PASS', eca_env('ECA_DB_PASS', ''));

    try {
        $pdo = new PDO(
            'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
            $user,
            $pass,
            [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        $pdo->exec('set names utf8mb4');
    } catch (Throwable $e) {
        error_log('Portal PDO connection error: ' . $e->getMessage());
        $pdo = null;
        if ($failHard) {
            http_response_code(500);
            exit('The local portal database is not available.');
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
