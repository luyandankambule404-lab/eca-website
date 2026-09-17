<?php

function eca_load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eq));
        $value = trim(substr($line, $eq + 1));
        if ($key === '') {
            continue;
        }
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function eca_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === null) {
        $value = $_ENV[$key] ?? false;
    }
    if ($value === false || $value === null) {
        return $default;
    }
    return (string) $value;
}

function eca_is_local_request(): bool
{
    if (php_sapi_name() === 'cli-server') {
        return true;
    }

    $addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $host = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
    if ($host === '' && !empty($_SERVER['HTTP_HOST'])) {
        $host = strtolower((string) (parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST) ?: $_SERVER['HTTP_HOST']));
    }

    $local = ['127.0.0.1', 'localhost', '::1'];
    return in_array($addr, $local, true) || in_array($host, $local, true);
}

function eca_local_password_expected(string $kind = 'portal'): string
{
    $keys = [];
    if ($kind === 'member') {
        $keys[] = 'ECA_LOCAL_MEMBER_PASSWORD';
    } elseif ($kind === 'cpd') {
        $keys[] = 'ECA_LOCAL_CPD_PASSWORD';
    }
    $keys[] = 'ECA_LOCAL_PORTAL_PASSWORD';
    $keys[] = 'ECA_LOCAL_ADMIN_PASSWORD';

    foreach ($keys as $key) {
        $value = eca_env($key, '');
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function eca_local_password_ok(string $password, string $kind = 'portal'): bool
{
    if ($password === '' || !eca_is_local_request()) {
        return false;
    }

    $expected = eca_local_password_expected($kind);
    return $expected !== '' && hash_equals($expected, $password);
}

eca_load_env_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
eca_load_env_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

if (!function_exists('eca_session_start')) {
    require_once __DIR__ . '/session.php';
}
