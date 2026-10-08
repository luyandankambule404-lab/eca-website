<?php

function eca_hub_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function eca_hub_first_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    $parts = preg_split('/\s+/', $name);
    return $parts[0] ?? $name;
}

function eca_hub_hello(string $name): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        $part = 'Good morning';
    } elseif ($hour < 17) {
        $part = 'Good afternoon';
    } else {
        $part = 'Good evening';
    }
    $first = eca_hub_first_name($name);
    return $first !== '' ? $part . ' ' . $first . '!' : $part . '!';
}

function eca_hub_initial(string $name): string
{
    $name = trim($name);
    return $name !== '' ? strtoupper(substr($name, 0, 1)) : 'E';
}

function eca_hub_super_admin_display_name(string $name): string
{
    $name = trim($name);
    if ($name !== '' && preg_match('/^CPD\s+/i', $name)) {
        $name = trim((string) preg_replace('/^CPD\s+/i', '', $name));
    }
    if ($name === '' || strcasecmp($name, 'SuperAdmin') === 0) {
        return 'Super Admin';
    }
    return $name;
}

function eca_hub_request_path(): string
{
    $path = strtolower((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? ''));
    if ($path === '' || $path === '/local-router.php') {
        $path = strtolower(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    }
    if ($path === '' || $path === '/') {
        return '/';
    }
    return rtrim($path, '/') ?: '/';
}

function eca_hub_current_script(): string
{
    return strtolower(basename(eca_hub_request_path()));
}

function eca_hub_is_dashboard_page(?string $href = null): bool
{
    $current = eca_hub_request_path();
    $homes = [
        '/admin',
        '/admin/index.php',
        '/client',
        '/client/dashboard.php',
        '/cpd/contractor/dashboard.php',
        '/cpd/app/dashboard.php',
        '/cpd/admin/dashboard.php',
        '/cpd/officer/dashboard.php',
    ];
    if (in_array($current, $homes, true)) {
        return true;
    }
    if ($href === null || $href === '') {
        return false;
    }
    $target = strtolower((string) (parse_url($href, PHP_URL_PATH) ?? $href));
    $target = rtrim($target, '/') ?: '/';
    return $current === $target
        || $current . '/index.php' === $target
        || $target . '/index.php' === $current;
}

function eca_hub_back_to_dashboard(string $href, string $label = 'Back to dashboard'): void
{
    $href = trim($href);
    if ($href === '' || eca_hub_is_dashboard_page($href)) {
        return;
    }
    ?>
    <p class="hub-back-wrap">
        <a class="hub-back" href="<?= eca_hub_h($href) ?>"><i class="fa fa-arrow-left" aria-hidden="true"></i> <?= eca_hub_h($label) ?></a>
    </p>
    <?php
}

function eca_hub_is_internal_page(): bool
{
    $path = eca_hub_request_path();
    return str_starts_with($path, '/client')
        || str_starts_with($path, '/admin')
        || str_starts_with($path, '/cpd');
}

function eca_staff_dashboard_href(): ?string
{
    require_once __DIR__ . '/session.php';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    if (function_exists('eca_signed_in_portal') && eca_signed_in_portal() !== 'officer') {
        return null;
    }
    if (!empty($_SESSION['eca_admin'])) {
        $origin = eca_origin_dashboard_href();
        return ($origin !== null && $origin !== '') ? $origin : '/admin/index.php';
    }
    if (function_exists('eca_officer_home')) {
        return eca_officer_home((string) ($_SESSION['role'] ?? ''));
    }
    return '/admin/index.php';
}

function eca_public_signed_in_href(): ?string
{
    require_once __DIR__ . '/session.php';
    if (eca_hub_is_internal_page() || eca_hub_is_dashboard_page()) {
        return null;
    }
    $home = eca_origin_dashboard_href();
    return ($home !== null && $home !== '') ? $home : null;
}

function eca_public_signed_in_back(): void
{
    $home = eca_public_signed_in_href();
    if ($home === null) {
        return;
    }
    $label = eca_origin_dashboard_label($home);
    static $cssPrinted = false;
    if (!$cssPrinted) {
        $cssPrinted = true;
        ?>
        <style id="eca-utility-dash-css">
            .eca-utility-tools .eca-utility-dash{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;min-height:32px;padding:0 12px;border-radius:999px;background:#d50d0e;color:#fff!important;font-weight:800;font-size:12px;line-height:1;text-decoration:none!important;white-space:nowrap;box-shadow:none;order:3}
            .eca-utility-tools .eca-utility-dash:hover,.eca-utility-tools .eca-utility-dash:focus{background:#192754;color:#fff!important;outline:1px solid #fff;outline-offset:0}
            .eca-utility-tools .eca-utility-dash .eca-utility-dash-full{display:inline}
            .eca-utility-tools .eca-utility-dash .eca-utility-dash-short{display:none}
            @media (max-width:767.98px){
                .eca-utility-tools .eca-utility-dash .eca-utility-dash-full{display:none}
                .eca-utility-tools .eca-utility-dash .eca-utility-dash-short{display:inline}
            }
        </style>
        <?php
    }
    $GLOBALS['eca_dashboard_back_rendered'] = true;
    ?>
    <a class="hub-back eca-utility-dash" href="<?= eca_hub_h($home) ?>" title="<?= eca_hub_h($label) ?>" aria-label="<?= eca_hub_h($label) ?>">
        <i class="fa fa-arrow-left" aria-hidden="true"></i>
        <span class="eca-utility-dash-full"><?= eca_hub_h($label) ?></span>
        <span class="eca-utility-dash-short">Dashboard</span>
    </a>
    <?php
}

function eca_hub_back_for_current_user(): void
{
    eca_public_signed_in_back();
}

function eca_hub_is_learner_portal_page(): bool
{
    $path = eca_hub_request_path();
    return $path === '/learner-portal.php'
        || str_starts_with($path, '/cpd/contractor')
        || str_starts_with($path, '/cpd/app')
        || $path === '/education-learner.php'
        || str_contains($path, 'education-learner');
}

function eca_origin_dashboard_href(): ?string
{
    require_once __DIR__ . '/session.php';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    if (function_exists('eca_signed_in_destination')) {
        $home = eca_signed_in_destination();
        if (is_string($home) && $home !== '') {
            return $home;
        }
    }
    // Fallbacks when signed-in destination helpers are unavailable.
    if (!empty($_SESSION['eca_admin'])) {
        return '/admin/index.php';
    }
    if (!empty($_SESSION['eca_member'])) {
        return '/client/dashboard.php';
    }
    $role = strtoupper((string) ($_SESSION['role'] ?? ''));
    if (!empty($_SESSION['user_id']) && function_exists('eca_is_cpd_learner_role') && eca_is_cpd_learner_role($role)) {
        return '/cpd/contractor/dashboard.php';
    }
    if (!empty($_SESSION['user_id']) && function_exists('eca_is_cpd_staff_role') && eca_is_cpd_staff_role($role)) {
        return function_exists('eca_officer_home') ? eca_officer_home($role) : '/admin/index.php';
    }
    return null;
}

function eca_origin_dashboard_label(?string $href = null): string
{
    $href = $href ?? eca_origin_dashboard_href();
    if ($href === null) {
        return 'Back to dashboard';
    }
    $path = strtolower((string) (parse_url($href, PHP_URL_PATH) ?? $href));
    if ($path === '/admin/index.php') {
        return 'Back to Admin Hub';
    }
    if ($path === '/client/dashboard.php') {
        return 'Back to Member Hub';
    }
    if ($path === '/cpd/contractor/dashboard.php') {
        return 'Back to Learner Dashboard';
    }
    return 'Back to dashboard';
}

function eca_origin_dashboard_is_current(?string $href = null): bool
{
    $href = $href ?? eca_origin_dashboard_href();
    if ($href === null) {
        return false;
    }
    $target = strtolower((string) (parse_url($href, PHP_URL_PATH) ?? $href));
    $target = rtrim($target, '/') ?: '/';
    $current = eca_hub_request_path();
    return $current === $target
        || $current . '/index.php' === $target
        || $target . '/index.php' === $current;
}

function eca_origin_dashboard_back(string $variant = 'top'): void
{
    $href = eca_origin_dashboard_href();
    if ($href === null || eca_origin_dashboard_is_current($href)) {
        return;
    }
    $label = eca_origin_dashboard_label($href);
    $link = '<a class="hub-origin-back" href="' . eca_hub_h($href) . '"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . eca_hub_h($label) . '</a>';
    $GLOBALS['eca_dashboard_back_rendered'] = true;
    if ($variant === 'nav') {
        echo '<li>' . $link . '</li>';
        return;
    }
    if ($variant === 'item') {
        echo $link;
        return;
    }
    echo $link;
}

/**
 * Fallback back control for signed-in users on pages without hub/public chrome.
 * Anchored bottom-left so it never covers headers, search, or top actions.
 */
function eca_session_dashboard_back_ensure(): void
{
    if (!empty($GLOBALS['eca_dashboard_back_rendered'])) {
        return;
    }
    $href = eca_origin_dashboard_href();
    if ($href === null || eca_origin_dashboard_is_current($href)) {
        return;
    }
    $current = eca_hub_request_path();
    if (preg_match('#/(?:login|logout|admin_login|register)\.php$#', $current)) {
        return;
    }
    $label = eca_origin_dashboard_label($href);
    if (empty($GLOBALS['eca_dashboard_back_float_css'])) {
        $GLOBALS['eca_dashboard_back_float_css'] = true;
        echo '<style id="eca-dashboard-back-float-css">'
            . '.hub-origin-back-float{position:fixed;left:max(16px,env(safe-area-inset-left));'
            . 'bottom:max(16px,env(safe-area-inset-bottom));top:auto;right:auto;z-index:1040;'
            . 'display:inline-flex;align-items:center;gap:7px;min-height:40px;max-width:min(280px,calc(100vw - 32px));'
            . 'padding:0 14px;border-radius:999px;background:#192754;color:#fff!important;font-weight:800;'
            . 'font-size:13px;line-height:1;text-decoration:none!important;white-space:nowrap;'
            . 'overflow:hidden;text-overflow:ellipsis;box-shadow:0 10px 28px rgba(25,39,84,.28);'
            . 'font-family:"Plus Jakarta Sans",system-ui,sans-serif}'
            . '.hub-origin-back-float:hover,.hub-origin-back-float:focus{background:#d50d0e;color:#fff!important}'
            . '@media print{.hub-origin-back-float{display:none!important}}'
            . '</style>';
    }
    echo '<a class="hub-origin-back hub-origin-back-float" href="' . eca_hub_h($href) . '" title="' . eca_hub_h($label) . '">'
        . '<i class="fa fa-arrow-left" aria-hidden="true"></i> '
        . eca_hub_h($label)
        . '</a>';
    $GLOBALS['eca_dashboard_back_rendered'] = true;
}

function eca_hub_assets(): void
{
    ?>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/dashboard-hub.css?v=20261007-back2">
<?php require_once __DIR__ . '/responsive-assets.php'; eca_responsive_assets(); ?>
<script src="/js/hub-table-search.js?v=5" defer></script>
<script src="/js/hub-doc-review.js?v=1" defer></script>
    <?php
}
