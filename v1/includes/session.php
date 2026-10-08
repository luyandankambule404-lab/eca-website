<?php

if (!function_exists('eca_env')) {
    require_once __DIR__ . '/env.php';
}

function eca_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Public HTML-first pages may load config after output; skip rather than warn.
    if (headers_sent()) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || eca_env('ECA_COOKIE_SECURE', '') === '1';
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
    }

    session_start();
}

eca_session_start();

function eca_session_touch(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    $max = (int) eca_env('ECA_SESSION_IDLE', '28800');
    if ($max < 600) {
        $max = 600;
    }
    $now = time();
    $last = (int) ($_SESSION['eca_last_seen'] ?? 0);
    if ($last > 0 && ($now - $last) > $max && eca_is_signed_in()) {
        eca_destroy_auth_session();
        return;
    }
    if (eca_is_signed_in()) {
        $_SESSION['eca_last_seen'] = $now;
    }
}

function eca_auth_no_store(): void
{
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

function eca_destroy_auth_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
    eca_auth_no_store();
}

function eca_public_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    if (empty($_SESSION['eca_public_csrf'])) {
        $_SESSION['eca_public_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['eca_public_csrf'];
}

function eca_public_csrf_ok(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && isset($_SESSION['eca_public_csrf'])
        && hash_equals((string) $_SESSION['eca_public_csrf'], $token);
}

function eca_is_signed_in(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    return !empty($_SESSION['eca_member'])
        || !empty($_SESSION['eca_admin'])
        || !empty($_SESSION['user_id']);
}

function eca_hub_session_admin(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    $admin = $_SESSION['eca_admin'] ?? null;
    return is_array($admin) && $admin !== [] ? $admin : null;
}

function eca_hub_can_open_portals(?array $admin = null): bool
{
    $admin = $admin ?? eca_hub_session_admin();
    if (!$admin) {
        return false;
    }
    $role = strtolower(trim((string) ($admin['role'] ?? '')));
    $role = str_replace([' ', '-'], '_', $role);
    if (function_exists('eca_normalize_role')) {
        $role = eca_normalize_role($role);
    } elseif (in_array($role, ['superadmin', 'supperadmin'], true)) {
        $role = 'super_admin';
    }
    return $role === 'super_admin';
}

function eca_super_admin_can_open_portals(): bool
{
    if (eca_hub_can_open_portals()) {
        return true;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    return strtoupper(trim((string) ($_SESSION['role'] ?? ''))) === 'SUPPERADMIN';
}

function eca_hub_preview_staff_role(): ?string
{
    if (!eca_hub_can_open_portals()) {
        return null;
    }
    $path = strtolower((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? ''));
    if (str_starts_with($path, '/cpd/officer')) {
        return 'OFFICER';
    }
    if (str_starts_with($path, '/cpd/admin')) {
        return 'SUPPERADMIN';
    }
    return null;
}

function eca_leave_other_portal(): bool
{
    $admin = eca_hub_session_admin();
    if (!$admin) {
        // CPD Super Admin previewing portals without a hub row yet — still treat as leave-portal.
        if (strtoupper(trim((string) ($_SESSION['role'] ?? ''))) === 'SUPPERADMIN') {
            unset($_SESSION['eca_member'], $_SESSION['eca_hub_portal_preview']);
            return true;
        }
        return false;
    }
    unset($_SESSION['eca_member']);
    if (!empty($admin['eca_cpd_hub_preview'])) {
        // Keep CPD Super Admin identity; only drop member/portal preview flags.
        $_SESSION['eca_hub_portal_preview'] = 'hub';
        return true;
    }
    unset(
        $_SESSION['user_id'],
        $_SESSION['role'],
        $_SESSION['full_name'],
        $_SESSION['email'],
        $_SESSION['eca_hub_portal_preview']
    );
    return true;
}

function eca_cpd_staff_roles(): array
{
    return ['SUPPERADMIN', 'ADMIN', 'OFFICER'];
}

function eca_cpd_learner_roles(): array
{
    return ['CONTRACTOR', 'LEARNER'];
}

function eca_is_cpd_staff_role(string $role): bool
{
    return in_array(strtoupper(trim($role)), eca_cpd_staff_roles(), true);
}

function eca_is_cpd_learner_role(string $role): bool
{
    return in_array(strtoupper(trim($role)), eca_cpd_learner_roles(), true);
}

function eca_login_url(string $portal, ?string $next = null): string
{
    $pages = [
        'officer' => '/admin/login.php',
        'admin' => '/admin/login.php',
        'member' => '/client/',
        'learner' => '/cpd/login.php',
    ];
    $url = $pages[$portal] ?? '/login.php';
    $safeNext = trim((string) $next);
    if (
        $safeNext !== ''
        && str_starts_with($safeNext, '/')
        && !str_starts_with($safeNext, '//')
        && !str_contains($safeNext, "\n")
        && !str_contains($safeNext, "\r")
    ) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'next=' . rawurlencode($safeNext);
    }
    return $url;
}

function eca_login_portal_for_path(?string $target): string
{
    $path = strtolower((string) (parse_url((string) $target, PHP_URL_PATH) ?? ''));
    if ($path === '') {
        $path = strtolower(trim((string) $target));
    }
    if (
        str_starts_with($path, '/admin/')
        || str_starts_with($path, '/cpd/admin')
        || str_starts_with($path, '/cpd/officer')
        || $path === '/cpd/admin_login.php'
    ) {
        return 'officer';
    }
    if (str_starts_with($path, '/client/') || str_starts_with($path, '/wellness')) {
        return 'member';
    }
    if (str_starts_with($path, '/cpd/') || str_contains($path, 'education-learner')) {
        return 'learner';
    }
    return '';
}

function eca_signed_in_portal(): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    $role = strtoupper((string) ($_SESSION['role'] ?? ''));
    if (!empty($_SESSION['eca_admin'])) {
        return 'officer';
    }
    if (!empty($_SESSION['user_id']) && eca_is_cpd_staff_role($role)) {
        return 'officer';
    }
    if (!empty($_SESSION['eca_member'])) {
        return 'member';
    }
    if (!empty($_SESSION['user_id']) && eca_is_cpd_learner_role($role)) {
        return 'learner';
    }
    return null;
}

function eca_officer_home(?string $role = null): string
{
    $role = strtoupper(trim((string) $role));
    if ($role === 'SUPPERADMIN') {
        return '/cpd/admin/dashboard.php';
    }
    if ($role === 'ADMIN') {
        return '/cpd/admin/course_students.php';
    }
    if ($role === 'OFFICER') {
        return '/cpd/admin/applications.php';
    }
    return '/admin/index.php';
}

function eca_signed_in_destination(): ?string
{
    $portal = eca_signed_in_portal();
    if ($portal === 'learner') {
        return '/cpd/contractor/dashboard.php';
    }
    if ($portal === 'officer') {
        if (!empty($_SESSION['eca_admin'])) {
            return '/admin/index.php';
        }
        return eca_officer_home((string) ($_SESSION['role'] ?? ''));
    }
    if ($portal === 'member') {
        return '/client/dashboard.php';
    }
    return null;
}

function eca_redirect_signed_in(?string $portal = null): void
{
    $current = eca_signed_in_portal();
    if ($current === null) {
        return;
    }
    if ($current === 'learner') {
        header('Location: /cpd/contractor/dashboard.php');
        exit;
    }
    if ($portal !== null && $current !== $portal) {
        return;
    }
    $home = eca_signed_in_destination();
    if ($home) {
        header('Location: ' . $home);
        exit;
    }
}

function eca_require_login(?string $next = null): void
{
    eca_session_touch();
    eca_auth_no_store();
    if (eca_is_signed_in()) {
        return;
    }
    $next = $next ?? (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $portal = eca_login_portal_for_path($next) ?: 'member';
    header('Location: ' . eca_login_url($portal, $next));
    exit;
}

function eca_safe_return_url(?string $next): ?string
{
    $raw = trim((string) $next);
    if ($raw === '' || str_contains($raw, '\\') || str_contains($raw, "\n") || str_contains($raw, "\r")) {
        return null;
    }
    if (!str_starts_with($raw, '/') || str_starts_with($raw, '//')) {
        return null;
    }

    $path = strtolower((string) (parse_url($raw, PHP_URL_PATH) ?? ''));
    if ($path === '') {
        return null;
    }
    if (
        str_starts_with($path, '/client/')
        || str_starts_with($path, '/cpd/contractor')
        || str_starts_with($path, '/cpd/app')
        || str_contains($path, 'education-learner')
    ) {
        return $raw;
    }
    if ($path === '/education-article.php' || $path === '/education-programme.php') {
        return $raw;
    }
    if (preg_match('#^/education-article-[a-z0-9-]+\.html$#', $path)) {
        return $raw;
    }
    return null;
}

function eca_login_next(?string $next, string $role = 'member'): string
{
    if ($role === 'admin') {
        $role = 'officer';
    }
    $fallbacks = [
        'member' => '/client/dashboard.php',
        'officer' => '/admin/index.php',
        'learner' => '/cpd/contractor/dashboard.php',
    ];
    $fallback = $fallbacks[$role] ?? '/client/dashboard.php';
    $raw = trim((string) $next);
    $path = strtolower((string) (parse_url($raw, PHP_URL_PATH) ?? ''));

    if ($role === 'officer') {
        if ($path === '/client/wellness' || str_starts_with($path, '/client/wellness/')) {
            return '/admin/wellness/';
        }
        if (
            (str_starts_with($path, '/admin/') || str_starts_with($path, '/cpd/admin') || str_starts_with($path, '/cpd/officer'))
            && str_starts_with($raw, '/')
            && !str_starts_with($raw, '//')
            && !str_contains($raw, '\\')
            && !str_contains($raw, "\n")
            && !str_contains($raw, "\r")
        ) {
            return $raw;
        }
        return $fallback;
    }

    if ($role === 'learner') {
        if (
            (str_starts_with($path, '/cpd/contractor') || str_starts_with($path, '/cpd/app') || str_contains($path, 'education-learner'))
            && str_starts_with($raw, '/')
            && !str_starts_with($raw, '//')
            && !str_contains($raw, '\\')
            && !str_contains($raw, "\n")
            && !str_contains($raw, "\r")
        ) {
            return $raw;
        }
        return $fallbacks['learner'];
    }

    return eca_safe_return_url($next) ?? $fallback;
}

function eca_same_origin_request(): bool
{
    $source = (string) ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '');
    $sourceHost = strtolower((string) parse_url($source, PHP_URL_HOST));
    $requestHost = strtolower((string) parse_url(
        'http://' . ($_SERVER['HTTP_HOST'] ?? ''),
        PHP_URL_HOST
    ));
    return $sourceHost !== '' && $requestHost !== '' && hash_equals($requestHost, $sourceHost);
}

function eca_require_public_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(403);
        exit('Invalid or expired request.');
    }

    // Prefer explicit CSRF tokens on all environments.
    if (eca_public_csrf_ok($_POST['csrf_token'] ?? null)) {
        return;
    }

    // Same-origin Origin/Referer fallback: local / live_readonly only.
    // Production requires a valid csrf_token (fail closed).
    $allowSameOriginFallback = !function_exists('eca_is_production') || !eca_is_production();
    if ($allowSameOriginFallback && eca_same_origin_request()) {
        return;
    }

    if (function_exists('eca_audit')) {
        try {
            eca_audit('csrf.rejected', 'public_post', null, [
                'path' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
                'production' => function_exists('eca_is_production') && eca_is_production(),
            ]);
        } catch (Throwable $e) {
            // Never block on audit failure.
        }
    }

    http_response_code(403);
    exit('Invalid or expired request.');
}

function eca_rate_limit_exceeded(string $bucket, string $identity, int $limit, int $windowSeconds): bool
{
    $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'rate-limits';
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
    $timestamps = json_decode(stream_get_contents($handle) ?: '[]', true);
    $timestamps = is_array($timestamps) ? $timestamps : [];
    $cutoff = time() - $windowSeconds;
    $timestamps = array_values(array_filter(
        $timestamps,
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $cutoff
    ));
    $blocked = count($timestamps) >= $limit;
    if (!$blocked) $timestamps[] = time();
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($timestamps));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return $blocked;
}
