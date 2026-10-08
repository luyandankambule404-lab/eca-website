<?php
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/client/auth.php';
require_once __DIR__ . '/code.jquery.com/cpd/auth.php';
eca_auth_no_store();

$nextParam = (string) ($_GET['next'] ?? '');
$portal = strtolower(trim((string) ($_GET['portal'] ?? '')));

if (in_array($portal, ['officer', 'admin', 'member', 'learner'], true)) {
    header('Location: ' . eca_login_url($portal === 'admin' ? 'officer' : $portal, $nextParam));
    exit;
}

if ($home = eca_signed_in_destination()) {
    header('Location: ' . $home);
    exit;
}

$officerHref = eca_login_url('officer', $nextParam);
$memberHref = eca_login_url('member', $nextParam);
$learnerHref = eca_login_url('learner', $nextParam);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in | Eswatini Contractors Association</title>
    <meta name="description" content="Officer, member and learner sign-in for the Eswatini Contractors Association.">
    <link rel="icon" href="/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/auth.css?v=23">
    <?php require_once __DIR__ . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body class="eca-auth eca-public eca-auth--unified">
    <?php
    $searchAction = '/directory.php';
    require __DIR__ . '/includes/utility-bar.php';
    ?>

    <main class="eca-auth-stage">
        <?php
        $authTheme = 'unified';
        require __DIR__ . '/includes/auth-visual.php';
        ?>
        <section class="eca-auth-panel">
            <nav class="eca-auth-nav" aria-label="Sign-in navigation">
                <a href="/" aria-label="ECA home"><img src="/img/ecalogo.png" alt="Eswatini Contractors Association"></a>
                <a class="site-link" href="/">Home</a>
            </nav>
            <div class="eca-auth-card">
                <p class="eca-kicker">Secure ECA access</p>
                <h1>Choose your sign-in</h1>
                <p class="sub">There are three logins. Use the one that matches your role. Learners are always taken to the Learner Portal.</p>

                <div class="eca-login-choose">
                    <a href="<?= eca_h($officerHref) ?>">
                        <span class="eca-login-choose-kicker">Staff</span>
                        <strong>Officer</strong>
                        <span>Administrators and super administrators</span>
                    </a>
                    <a href="<?= eca_h($memberHref) ?>">
                        <span class="eca-login-choose-kicker">Membership</span>
                        <strong>Member</strong>
                        <span>Contractor members and the member hub</span>
                    </a>
                    <a href="<?= eca_h($learnerHref) ?>">
                        <span class="eca-login-choose-kicker">Training</span>
                        <strong>Learner</strong>
                        <span>Courses, progress, materials and certificates</span>
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
