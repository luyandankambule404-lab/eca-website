<?php
require_once __DIR__ . '/../includes/hub.php';
require_once __DIR__ . '/../includes/admin-export.php';

function eca_admin_nav_groups(): array
{
    return [
        'Overview' => [
            'dashboard' => ['/admin/index.php', 'fa-gauge-high', 'Dashboard', 'hub.access'],
        ],
        'Membership' => [
            'members' => ['/admin/members.php', 'fa-users', 'Members', 'members.manage'],
            'membership_report' => ['/admin/membership-report.php', 'fa-chart-pie', 'Membership Intelligence', 'reports.view'],
            'membership_2026' => ['/admin/membership-report.php?period=2025-2026', 'fa-calendar-check', '2025 / 2026 Report', 'reports.view'],
            'companies' => ['/admin/companies.php', 'fa-building', 'Contractors', 'companies.manage'],
            'owners_report' => ['/admin/owners-report.php', 'fa-user-tie', 'Owners Report', 'companies.manage'],
            'applications' => ['/admin/applications.php', 'fa-file-lines', 'Applications', 'applications.manage'],
            'certificates' => ['/admin/certificates.php', 'fa-award', 'Certificates', 'certificates.manage'],
            'documents' => ['/admin/documents.php', 'fa-folder-open', 'Documents', 'documents.manage'],
        ],
        'Finance' => [
            'payments' => ['/admin/payments.php', 'fa-coins', 'Payments', 'payments.manage'],
            'balances' => ['/admin/balances.php', 'fa-scale-balanced', 'Outstanding balances', 'payments.manage'],
            'receipts' => ['/admin/receipts.php', 'fa-receipt', 'Receipts', 'payments.manage'],
        ],
        'Learning & wellness' => [
            'cpd' => ['/admin/cpd.php', 'fa-certificate', 'CPD', 'cpd.view'],
            'education' => ['/admin/education.php', 'fa-graduation-cap', 'Education', 'education.manage'],
            'wellness' => ['/admin/wellness/', 'fa-heart-pulse', 'Wellness', 'wellness.manage'],
        ],
        'Portals' => [
            'wellness_hub' => ['/wellness/', 'fa-spa', 'Wellness Hub', 'hub.access', 'super_admin'],
            'member_wellness' => ['/client/wellness/', 'fa-heart', 'Member Wellness', 'hub.access', 'super_admin'],
            'learner' => ['/learner-portal.php', 'fa-laptop', 'Learner Portal', 'hub.access', 'super_admin'],
            'member_hub' => ['/client/dashboard.php', 'fa-id-card', 'Member Hub', 'hub.access', 'super_admin'],
            'cpd_admin' => ['/cpd/admin/dashboard.php', 'fa-user-shield', 'Super Admin', 'hub.access', 'super_admin'],
            'officer_hub' => ['/cpd/officer/dashboard.php', 'fa-user-tie', 'Officer Hub', 'hub.access', 'super_admin'],
        ],
        'Content' => [
            'news' => ['/admin/news.php', 'fa-newspaper', 'News', 'content.manage'],
            'tenders' => ['/admin/tenders.php', 'fa-gavel', 'Tenders', 'content.manage'],
            'events' => ['/admin/events.php', 'fa-calendar', 'Events', 'content.manage'],
            'resources' => ['/admin/resources.php', 'fa-download', 'Resources', 'content.manage'],
            'notifications' => ['/admin/notifications.php', 'fa-bell', 'Notifications', 'hub.access'],
            'email_centre' => ['/admin/email-centre.php', 'fa-envelope', 'Email Centre', 'members.manage'],
            'tickets' => ['/admin/tickets.php', 'fa-headset', 'Tickets', 'tickets.manage'],
        ],
        'Management' => [
            'users' => ['/admin/users.php', 'fa-user-shield', 'Users', 'users.view', 'super_admin'],
            'roles' => ['/admin/roles.php', 'fa-key', 'Roles & Permissions', 'roles.view', 'super_admin'],
            'reports' => ['/admin/reports.php', 'fa-chart-column', 'Reports', 'reports.view'],
            'audit' => ['/admin/audit.php', 'fa-clipboard-list', 'Audit logs', 'audit.view'],
        ],
        'System' => [
            'security' => ['/admin/security.php', 'fa-shield-halved', 'Security Centre', 'security.view'],
            'settings' => ['/admin/settings.php', 'fa-gear', 'System settings', 'settings.view'],
        ],
    ];
}

function eca_admin_nav_active_key(string $active): string
{
    $map = [
        'user' => 'users',
        'company' => 'companies',
        'application' => 'applications',
        'certificate' => 'certificates',
        'payment' => 'payments',
        'document' => 'documents',
        'ticket' => 'tickets',
    ];
    return $map[$active] ?? $active;
}

function eca_admin_hub_start(string $title, string $active = 'dashboard'): void
{
    $admin = eca_admin_user() ?? [];
    $name = trim((string) ($admin['name'] ?? ''));
    if ($name === '') {
        $name = (string) ($admin['email'] ?? 'Administrator');
    }
    $role = trim((string) ($admin['role'] ?? 'admin'));
    $roleLabel = $role !== '' ? ucwords(str_replace('_', ' ', $role)) : 'Administrator';
    $active = eca_admin_nav_active_key($active);
    $isDashboard = $active === 'dashboard';
    $isSuper = str_contains(strtolower($role), 'super');
    if ($isSuper && function_exists('eca_hub_super_admin_display_name')) {
        $name = eca_hub_super_admin_display_name($name);
    }
    $groups = eca_admin_nav_groups();
    $canSearch = eca_can('hub.access', $role);
    $searchQ = trim((string) ($_GET['q'] ?? ''));
    $homeLinkClass = 'hub-home-link hub-cmd-link';
    $logoutLinkClass = 'hub-home-link hub-cmd-link hub-cmd-link-ghost';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= eca_admin_h($title) ?> | ECA</title>
    <link rel="stylesheet" href="/css/theme.css?v=20260918-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php eca_hub_assets(); ?>
    <link rel="stylesheet" href="/css/admin-command.css?v=20261006-portals3">
</head>
<body class="hub-root hub-admin<?= $isDashboard ? ' is-admin-dash' : ' is-admin-page' ?><?= $isSuper ? ' is-super-admin' : '' ?>">
<div class="hub-page">
    <div class="hub-shell">
        <header class="hub-topbar">
            <a class="hub-brand" href="/index.php">
                <img src="/img/ecalogo.png" alt="Eswatini Contractors Association">
            </a>
            <div class="hub-title-wrap">
                <p class="hub-title-kicker"><?= $isSuper ? 'Super Admin' : 'Officer Hub' ?></p>
                <?php if ($isDashboard): ?>
                <p class="hub-title">Command Centre</p>
                <?php else: ?>
                <h1 class="hub-title"><?= eca_admin_h($title) ?></h1>
                <p class="hub-title-page">Admin Hub</p>
                <?php endif; ?>
            </div>
            <div class="hub-top-actions">
                <button class="mobile-toggle" type="button" aria-label="Open navigation" aria-controls="adminSidebar" aria-expanded="false">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                <?php if ($canSearch): ?>
                <form class="hub-header-search" method="get" action="/admin/search.php" role="search">
                    <label class="visually-hidden" for="hubAdminSearch">Search administration</label>
                    <input id="hubAdminSearch" type="search" name="q" value="<?= $active === 'search' ? eca_admin_h($searchQ) : '' ?>" placeholder="Search hub" autocomplete="off">
                </form>
                <?php endif; ?>
                <a class="<?= eca_admin_h($homeLinkClass) ?>" href="/index.php">Main portal</a>
                <?php eca_origin_dashboard_back('top'); ?>
                <div class="hub-top-user">
                    <span class="hub-top-name"><?= eca_admin_h($name) ?></span>
                    <div class="hub-chip"><?= eca_admin_h($roleLabel) ?></div>
                </div>
                <a class="<?= eca_admin_h($logoutLinkClass) ?>" href="/admin/logout.php">Logout</a>
            </div>
        </header>
        <?php if (function_exists('eca_db_mode_banner')) { eca_db_mode_banner(); } ?>
        <div class="hub-layout">
            <aside class="hub-sidebar" id="adminSidebar">
                <div class="hub-welcome">
                    <div class="hub-avatar"><?= eca_admin_h(eca_hub_initial($name)) ?></div>
                    <div>
                        <p class="hub-welcome-label"><?= $isSuper ? 'Super Admin' : 'Command access' ?></p>
                        <p class="hub-welcome-name"><?= eca_admin_h($name) ?></p>
                        <p class="hub-welcome-role"><?= eca_admin_h($roleLabel) ?></p>
                    </div>
                </div>
                <nav class="hub-nav" aria-label="Administration">
                    <?php foreach ($groups as $groupLabel => $items): ?>
                        <?php
                            $visible = [];
                            $groupOpen = false;
                            foreach ($items as $key => $item) {
                                $need = (string) ($item[3] ?? '');
                                $canItem = eca_can($need, $role)
                                    || ($need === 'wellness.manage' && eca_hub_can_open_portals());
                                if (!$canItem) {
                                    continue;
                                }
                                if (
                                    !empty($item[4])
                                    && $item[4] === 'super_admin'
                                    && !(
                                        eca_hub_can_open_portals()
                                        || (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals())
                                    )
                                ) {
                                    continue;
                                }
                                $visible[$key] = $item;
                                if ($active === $key) {
                                    $groupOpen = true;
                                }
                            }
                            if ($groupLabel === 'Overview' || $groupLabel === 'Portals') {
                                $groupOpen = true;
                            }
                            if (!$visible) {
                                continue;
                            }
                        ?>
                        <details class="hub-nav-group"<?= $groupOpen ? ' open' : '' ?>>
                            <summary><?= eca_admin_h($groupLabel) ?></summary>
                            <?php foreach ($visible as $key => $item): ?>
                                <a class="<?= $active === $key ? 'is-active' : '' ?>" href="<?= eca_admin_h($item[0]) ?>"><i class="fa-solid <?= eca_admin_h($item[1]) ?>" aria-hidden="true"></i><span><?= eca_admin_h($item[2]) ?></span></a>
                            <?php endforeach; ?>
                        </details>
                    <?php endforeach; ?>
                    <a href="/index.php"><i class="fa-solid fa-globe" aria-hidden="true"></i><span>Main portal</span></a>
                    <a href="/admin/logout.php"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Logout</span></a>
                </nav>
                <p class="cmd-side-stamp">ECA Command Centre</p>
            </aside>
            <button class="hub-overlay" type="button" aria-label="Close navigation"></button>
            <main class="hub-main">
    <?php
}

function eca_admin_wants_json(): bool
{
    $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return $xhr === 'xmlhttprequest'
        || str_contains($accept, 'application/json')
        || (string) ($_POST['ajax'] ?? $_GET['ajax'] ?? '') === '1';
}

function eca_admin_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

function eca_admin_safe_return(?string $raw, string $fallback = '/admin/documents.php'): string
{
    $raw = trim((string) $raw);
    if ($raw === '' || !str_starts_with($raw, '/admin/') || str_contains($raw, '//') || str_contains($raw, "\n") || str_contains($raw, "\r")) {
        return $fallback;
    }
    $path = (string) (parse_url($raw, PHP_URL_PATH) ?: '');
    if ($path === '' || str_starts_with($path, '/admin/document-view.php')) {
        return $fallback;
    }
    return $raw;
}

function eca_admin_document_status_html(?string $reviewedAt): void
{
    if ($reviewedAt) {
        echo '<span class="hub-doc-status is-reviewed">Reviewed</span>';
        return;
    }
    echo '<span class="hub-doc-status is-pending">Pending review</span>';
}

function eca_admin_document_actions(array $doc, string $csrf, array $opts = []): void
{
    $id = (int) ($doc['id'] ?? 0);
    if ($id < 1) {
        return;
    }
    $viewHref = '/admin/document-view.php?id=' . $id;
    if (!empty($opts['return'])) {
        $viewHref .= '&return=' . rawurlencode((string) $opts['return']);
    }
    $canReview = !empty($opts['can_review']) && empty($doc['reviewed_at']);
    $reviewAction = (string) ($opts['action'] ?? '');
    $idField = (string) ($opts['id_field'] ?? 'id');
    ?>
    <div class="hub-doc-actions">
        <a class="hub-doc-link" href="<?= eca_admin_h($viewHref) ?>">View</a>
        <a class="hub-doc-link hub-doc-link-ghost" href="/document-download.php?id=<?= $id ?>">Download</a>
        <?php if ($canReview): ?>
        <form method="post" class="hub-table-action-form hub-doc-review-form">
            <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
            <?php if ($reviewAction !== ''): ?>
            <input type="hidden" name="action" value="<?= eca_admin_h($reviewAction) ?>">
            <?php endif; ?>
            <input type="hidden" name="<?= eca_admin_h($idField) ?>" value="<?= $id ?>">
            <button class="hub-doc-review" type="submit">Mark reviewed</button>
        </form>
        <?php endif; ?>
    </div>
    <?php
}

function eca_admin_live_search(string $placeholder = 'Type a letter to filter this list'): void
{
    ?>
<div class="hub-card hub-live-search">
    <input type="search" data-hub-search placeholder="<?= eca_admin_h($placeholder) ?>" autocomplete="off" aria-label="<?= eca_admin_h($placeholder) ?>">
    <span data-hub-search-count class="hub-live-search-count"></span>
</div>
    <?php
}

function eca_admin_hub_end(): void
{
    if (function_exists('eca_session_dashboard_back_ensure')) {
        eca_session_dashboard_back_ensure();
    }
    ?>
            </main>
        </div>
    </div>
</div>
<script>
(function () {
    var toggle = document.querySelector('.mobile-toggle');
    var overlay = document.querySelector('.hub-overlay');
    function setOpen(open) {
        document.body.classList.toggle('sidebar-open', open);
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        }
    }
    if (toggle) toggle.addEventListener('click', function () {
        setOpen(!document.body.classList.contains('sidebar-open'));
    });
    if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setOpen(false);
    });
})();
</script>
</body>
</html>
    <?php
}
