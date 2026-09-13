<?php
require_once __DIR__ . '/auth.php';

function eca_portal_start(string $title, string $active = 'dashboard'): void
{
    $member = $GLOBALS['eca_member'] ?? eca_portal_member();
    $initial = strtoupper(substr((string) ($member['name'] ?? 'M'), 0, 1));
    $items = [
        'dashboard' => ['dashboard.php', 'fa-gauge-high', 'Dashboard'],
        'profile' => ['profile.php', 'fa-id-card', 'Profile'],
        'certificate' => ['certificate.php', 'fa-award', 'Certificate'],
        'cpd' => ['../code.jquery.com/cpd/login.php', 'fa-graduation-cap', 'CPD'],
        'apply' => ['../application.php', 'fa-file-signature', 'Apply'],
        'renew' => ['../renewal.php', 'fa-rotate', 'Renew'],
        'resources' => ['../resources.php', 'fa-folder-open', 'Resources'],
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
</head>
<body>
<div class="wrapper">
    <div class="eca-dash-topbar">
        <div class="eca-dash-topbar-inner">
            <span>Eswatini Contractors Association</span>
            <a href="mailto:info@eca.co.sz"><i class="fa fa-envelope"></i> info@eca.co.sz</a>
        </div>
    </div>

    <header class="main-header">
        <a href="../index.php" class="logo royal-header">
            <img src="../img/ecalogo.png" alt="Eswatini Contractors Association">
        </a>
        <nav class="navbar">
            <a href="#" class="sidebar-toggle" aria-label="Menu"><i class="fa fa-bars"></i></a>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav" style="list-style:none;margin:0;padding:0;display:flex;align-items:center;">
                    <li>
                        <a href="../index.php">Website</a>
                    </li>
                    <li>
                        <a href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <aside class="main-sidebar">
        <section class="sidebar">
            <ul class="sidebar-menu">
                <?php foreach ($items as $key => $item): ?>
                    <li class="<?= $active === $key ? 'active' : '' ?>">
                        <a href="<?= eca_h($item[0]) ?>">
                            <i class="fa-solid <?= eca_h($item[1]) ?>"></i>
                            <span><?= eca_h($item[2]) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="header-icon"><i class="fa-solid fa-user-tie"></i></div>
            <div class="header-title">
                <h1><?= eca_h($title) ?></h1>
                <p>Welcome, <?= eca_h($member['name']) ?></p>
                <ol class="breadcrumb">
                    <li><a href="../index.php">Home</a></li>
                    <li class="active"><?= eca_h($title) ?></li>
                </ol>
            </div>
        </section>
        <section class="content">
    <?php
}

function eca_portal_end(): void
{
    ?>
        </section>
    </div>
    <footer class="main-footer">
        Eswatini Contractors Association &middot; Member portal
    </footer>
</div>
</body>
</html>
    <?php
}
