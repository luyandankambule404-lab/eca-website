<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/hub.php';

function eca_portal_start(string $title, string $active = 'dashboard', string $bodyClass = '', string $intro = ''): void
{
    $member = $GLOBALS['eca_member'] ?? eca_portal_member();
    $name = (string) ($member['name'] ?? 'Member');
    $roleLabel = trim((string) ($member['status'] ?? '')) ?: 'Contractor Member';
    $items = [
        'dashboard' => ['/client/dashboard.php', 'fa-gauge-high', 'Dashboard'],
        'profile' => ['/client/profile.php', 'fa-user', 'Profile'],
        'applications' => ['/client/applications.php', 'fa-file-lines', 'Applications'],
        'certificate' => ['/client/certificate.php', 'fa-award', 'Certificate'],
        'documents' => ['/client/documents.php', 'fa-folder-open', 'Documents'],
        'payments' => ['/client/payments.php', 'fa-receipt', 'Payments'],
        'notifications' => ['/client/notifications.php', 'fa-bell', 'Notifications'],
        'projects' => ['/client/projects.php', 'fa-diagram-project', 'Projects'],
        'cpd' => ['/client/cpd.php', 'fa-graduation-cap', 'CPD'],
        'learner' => ['/learner-portal.php', 'fa-laptop', 'Learner Portal'],
        'renew' => ['/renewal.php', 'fa-arrows-rotate', 'Renew'],
        'resources' => ['/resources.php', 'fa-book-open', 'Resources'],
        'wellness' => ['/client/wellness/', 'fa-heart-pulse', 'Wellness'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= eca_h($title) ?> | ECA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/client/dashboard-theme.css?v=24">
    <?php eca_hub_assets(); ?>
    <?php if (str_contains($bodyClass, 'is-projects')): ?>
    <link rel="stylesheet" href="/css/projects-folder.css?v=8">
    <?php endif; ?>
</head>
<body class="hub-root<?= $bodyClass !== '' ? ' ' . eca_h($bodyClass) : '' ?>">
<div class="hub-page">
    <div class="hub-shell">
        <header class="hub-topbar">
            <a class="hub-brand" href="/index.php">
                <img src="/img/ecalogo.png" alt="Eswatini Contractors Association">
            </a>
            <h1 class="hub-title">Member Hub</h1>
            <div class="hub-top-actions">
                <button class="mobile-toggle" type="button" aria-label="Open navigation" aria-controls="memberSidebar" aria-expanded="false"><i class="fa fa-bars" aria-hidden="true"></i></button>
                <a class="hub-home-link" href="/index.php">Home</a>
                <?php eca_origin_dashboard_back('top'); ?>
                <div class="hub-chip">
                    <span class="hub-avatar-sm"><?= eca_h(eca_hub_initial($name)) ?></span>
                    <?= eca_h($roleLabel) ?>
                </div>
            </div>
        </header>
        <div class="hub-layout">
            <aside class="hub-sidebar" id="memberSidebar">
                <div class="hub-welcome">
                    <div class="hub-avatar"><?= eca_h(eca_hub_initial($name)) ?></div>
                    <div>
                        <p class="hub-welcome-label">Welcome,</p>
                        <p class="hub-welcome-name"><?= eca_h($name) ?></p>
                        <p class="hub-welcome-role"><?= eca_h($roleLabel) ?></p>
                    </div>
                </div>
                <nav class="hub-nav">
                    <?php foreach ($items as $key => $item): ?>
                        <a class="<?= $active === $key ? 'is-active' : '' ?>" href="<?= eca_h($item[0]) ?>"><i class="fa-solid <?= eca_h($item[1]) ?>" aria-hidden="true"></i><span><?= eca_h($item[2]) ?></span></a>
                    <?php endforeach; ?>
                    <?php if (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals()): ?>
                        <a href="/admin/index.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span>Back to Admin Hub</span></a>
                    <?php endif; ?>
                    <a href="/index.php"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Home</span></a>
                    <a href="/client/logout.php"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span><?= function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals() ? 'Leave portal' : 'Logout' ?></span></a>
                </nav>
            </aside>
            <button class="hub-overlay" type="button" aria-label="Close navigation"></button>
            <main class="hub-main">
                <div class="hub-hello">
                    <h1 class="hub-hello-title"><?= eca_h(eca_hub_hello($name)) ?></h1>
                    <?php if ($intro !== ''): ?>
                        <p><?= eca_h($intro) ?></p>
                    <?php endif; ?>
                </div>
    <?php
}

function eca_portal_end(): void
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
