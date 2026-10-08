<?php

function eca_json_ok(array $payload = [], int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
    }
    echo json_encode(['ok' => true] + $payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function eca_json_error(string $message, int $status = 400, array $extra = []): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
    }
    echo json_encode(['ok' => false, 'error' => $message] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

function eca_error_page(int $status, string $title, string $message): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
    }
    $pageTitle = $title;
    $pageMessage = $message;
    $pageStatus = $status;
    require __DIR__ . '/../errors/error.php';
    exit;
}

function eca_unauthorized(string $message = 'Sign in to continue.'): void
{
    static $auditing = false;
    if (!$auditing && function_exists('eca_audit_security_event')) {
        $auditing = true;
        $path = function_exists('eca_audit_request_path')
            ? eca_audit_request_path()
            : (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        // Only log when an authenticated actor hits a protected route without privilege,
        // or when an anonymous caller hits Hub admin paths that still reach 401.
        $hasActor = !empty($_SESSION['eca_admin'])
            || !empty($_SESSION['eca_member'])
            || !empty($_SESSION['user_id']);
        if ($hasActor || str_contains($path, '/admin')) {
            eca_audit_security_event('access.denied', 'route', $path, [
                'reason' => 'unauthenticated',
                'detail' => substr($message, 0, 200),
            ]);
        }
        $auditing = false;
    }
    eca_error_page(401, 'Sign in required', $message);
}

function eca_forbid(string $message = 'You do not have permission to view this page.'): void
{
    static $auditing = false;
    if (!$auditing && function_exists('eca_audit_security_event')) {
        $auditing = true;
        $hasActor = !empty($_SESSION['eca_admin'])
            || !empty($_SESSION['eca_member'])
            || !empty($_SESSION['user_id']);
        $path = function_exists('eca_audit_request_path')
            ? eca_audit_request_path()
            : (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
        // Log authenticated denials, and anonymous denials on Hub/admin routes.
        if ($hasActor || str_contains($path, '/admin')) {
            eca_audit_security_event('access.denied', 'route', $path, [
                'reason' => substr($message, 0, 200),
            ]);
        }
        $auditing = false;
    }
    eca_error_page(403, 'Access denied', $message);
}

function eca_not_found(string $message = 'The page you requested was not found.'): void
{
    eca_error_page(404, 'Page not found', $message);
}

function eca_server_error(string $message = 'The local website could not complete this request.'): void
{
    eca_error_page(500, 'Something went wrong', $message);
}
