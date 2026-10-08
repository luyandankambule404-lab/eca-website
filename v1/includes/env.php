<?php

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

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
        $addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return in_array($addr, ['127.0.0.1', '::1'], true);
    }

    $addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return in_array($addr, ['127.0.0.1', '::1'], true);
}

eca_load_env_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
eca_load_env_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

require_once __DIR__ . '/db-mode.php';
if (function_exists('eca_apply_production_error_policy')) {
    eca_apply_production_error_policy();
}
eca_live_readonly_guard_http();
