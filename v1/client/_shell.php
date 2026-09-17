<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/hub.php';

function eca_portal_start(string $title, string $active = 'dashboard'): void
{
    $member = $GLOBALS['eca_member'] ?? eca_portal_member();
    $name = (string) ($member['name'] ?? 'Member');
    $roleLabel = trim((string) ($member['status'] ?? '')) ?: 'Contractor Member';
    $items = [
        'dashboard' => ['dashboard.php', 'Dashboard'],
        'profile' => ['profile.php', 'Profile'],
        'certificate' => ['certificate.php', 'Certificate'],
        'cpd' => ['/cpd/login.php', 'CPD'],
        'apply' => ['../application.php', 'Apply'],
        'renew' => ['../renewal.php', 'Renew'],
        'resources' => ['../resources.php', 'Resources'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= eca_h($title) ?> | ECA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard-theme.css">
    <?php eca_hub_assets(); ?>
</head>
<body class="hub-root">
<div class="hub-page">
    <div class="hub-shell">
        <header class="hub-topbar">
            <a class="hub-brand" href="/index.php">
                <img src="../img/ecalogo.png" alt="Eswatini Contractors Association">
            </a>
            <h1 class="hub-title">Member Hub</h1>
            <div class="hub-top-actions">
                <button class="btn btn-light mobile-toggle" type="button" onclick="document.body.classList.toggle('sidebar-open')" aria-label="Menu"><i class="fa fa-bars"></i></button>
                <a class="hub-home-link" href="/index.php">Home</a>
                <div class="hub-chip">
                    <span class="hub-avatar-sm"><?= eca_h(eca_hub_initial($name)) ?></span>
                    <?= eca_h($roleLabel) ?>
                </div>
            </div>
        </header>
        <div class="hub-layout">
            <aside class="hub-sidebar">
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
                        <a class="<?= $active === $key ? 'is-active' : '' ?>" href="<?= eca_h($item[0]) ?>"><?= eca_h($item[1]) ?></a>
                    <?php endforeach; ?>
                    <a href="/index.php">Home</a>
                    <a href="logout.php">Logout</a>
                </nav>
            </aside>
            <main class="hub-main">
                <div class="hub-hello">
                    <h1 class="hub-hello-title"><?= eca_h(eca_hub_hello($name)) ?></h1>
                    <p>Welcome, <?= eca_h($name) ?></p>
                </div>
    <?php
}

function eca_portal_end(): void
{
    ?>
            </main>
        </div>
    </div>
</div>
</body>
</html>
    <?php
}
