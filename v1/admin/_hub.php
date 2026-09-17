<?php
require_once __DIR__ . '/../includes/hub.php';

function eca_admin_hub_start(string $title, string $active = 'dashboard'): void
{
    $admin = eca_admin_user() ?? [];
    $name = trim((string) ($admin['name'] ?? ''));
    if ($name === '') {
        $name = (string) ($admin['email'] ?? 'Administrator');
    }
    $role = trim((string) ($admin['role'] ?? 'admin'));
    $roleLabel = $role !== '' ? ucwords(str_replace('_', ' ', $role)) : 'Administrator';
    $items = [
        'dashboard' => ['/admin/index.php', 'fa-gauge-high', 'Dashboard'],
        'companies' => ['/admin/companies.php', 'fa-building', 'Companies'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= eca_admin_h($title) ?> | ECA</title>
    <link rel="stylesheet" href="/css/theme.css?v=7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php eca_hub_assets(); ?>
</head>
<body class="hub-root hub-admin">
<div class="hub-page">
    <div class="hub-shell">
        <header class="hub-topbar">
            <a class="hub-brand" href="/index.php">
                <img src="/img/ecalogo.png" alt="Eswatini Contractors Association">
            </a>
            <h1 class="hub-title">Admin Hub</h1>
            <div class="hub-top-actions">
                <a class="hub-home-link" href="/index.php">Home</a>
                <div class="hub-chip"><?= eca_admin_h($roleLabel) ?></div>
            </div>
        </header>
        <div class="hub-layout">
            <aside class="hub-sidebar">
                <div class="hub-welcome">
                    <div class="hub-avatar"><?= eca_admin_h(eca_hub_initial($name)) ?></div>
                    <div>
                        <p class="hub-welcome-label">Welcome,</p>
                        <p class="hub-welcome-name"><?= eca_admin_h($name) ?></p>
                        <p class="hub-welcome-role"><?= eca_admin_h($roleLabel) ?></p>
                    </div>
                </div>
                <nav class="hub-nav">
                    <?php foreach ($items as $key => $item): ?>
                        <a class="<?= $active === $key ? 'is-active' : '' ?>" href="<?= eca_admin_h($item[0]) ?>"><i class="fa-solid <?= eca_admin_h($item[1]) ?>" aria-hidden="true"></i><span><?= eca_admin_h($item[2]) ?></span></a>
                    <?php endforeach; ?>
                    <a href="/index.php"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Home</span></a>
                    <a href="/admin/logout.php"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Logout</span></a>
                </nav>
            </aside>
            <main class="hub-main">
    <?php
}

function eca_admin_hub_end(): void
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
