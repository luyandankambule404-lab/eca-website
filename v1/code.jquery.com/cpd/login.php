<?php
require_once "auth.php";
eca_auth_no_store();

$err = "";
$nextParam = (string) ($_POST['next'] ?? $_GET['next'] ?? '');
$localPreview = php_sapi_name() === 'cli-server';
if (eca_is_signed_in()) {
    header('Location: /learner-portal.php');
    exit;
}
eca_redirect_signed_in('learner');

if (is_post()) {
    cpd_require_csrf();
    $email = trim($_POST['email'] ?? '');
    $rateIdentity = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($email);
    if (cpd_rate_limit_exceeded('learner-login', $rateIdentity, 15, 900)) {
        http_response_code(429);
        $err = "Too many login attempts. Please wait 15 minutes and try again.";
    } elseif (empty($conn)) {
        $err = $localPreview
            ? "This computer cannot reach the learner database. Use the preview screens or the live portal."
            : "The learner portal is not available. Please try again shortly.";
    } else {
        $pass = $_POST['password'] ?? '';
        $u = cpd_authenticate($email, $pass, true);

        if (!$u || !eca_is_cpd_learner_role((string) $u['role'])) {
            $err = "Invalid learner email or password.";
        } else {
            cpd_start_authenticated_session($u);
            redirect(eca_login_next($nextParam, 'learner'));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learner login | ECA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/auth.css?v=23">
    <?php require_once dirname(__DIR__, 2) . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body class="eca-auth eca-public eca-auth--learner">
    <?php
    $searchAction = '/directory.php';
    require dirname(__DIR__, 2) . '/includes/utility-bar.php';
    ?>
    <main class="eca-auth-stage">
        <?php
        $authTheme = 'learner';
        require dirname(__DIR__, 2) . '/includes/auth-visual.php';
        ?>
        <section class="eca-auth-panel">
            <nav class="eca-auth-nav">
                <a href="/index.php"><img src="/img/ecalogo.png" alt="Eswatini Contractors Association"></a>
                <a class="site-link" href="/index.php">Home</a>
            </nav>
            <div class="eca-auth-card">
                <p class="eca-kicker">Learner Portal</p>
                <h1>Learner login</h1>
                <p class="sub">Sign in to the Learner Portal for courses, progress, materials and certificates. Officers and members use their own logins.</p>
                <?php if ($err): ?><div class="error"><?= e($err) ?></div><?php endif; ?>
                <form method="post" data-auth-submit>
                    <?= cpd_csrf_input() ?>
                    <input type="hidden" name="next" value="<?= e($nextParam) ?>">
                    <div class="eca-field">
                        <label class="form-label" for="cpdEmail">Email</label>
                        <div class="eca-field-control">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input id="cpdEmail" class="form-control" name="email" type="email" autocomplete="username" required>
                        </div>
                    </div>
                    <div class="eca-field password-wrap">
                        <label class="form-label" for="cpdPassword">Password</label>
                        <div class="eca-field-control">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input id="password" class="form-control" name="password" type="password" autocomplete="current-password" required>
                            <button type="button" data-auth-toggle-password="password" aria-label="Show password" aria-pressed="false"><i class="fa fa-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <button class="eca-btn" type="submit" data-auth-busy="Signing in…">Open Learner Portal</button>
                </form>
                <?php if ($localPreview): ?>
                    <p class="live-note"><a href="preview-dashboards.php">Preview dashboards (local)</a></p>
                <?php endif; ?>
            </div>
            <?php
            $authPortalSet = 'learner';
            require dirname(__DIR__, 2) . '/includes/auth-portals.php';
            ?>
        </section>
    </main>
    <script src="/js/auth-login.js?v=2" defer></script>
</body>
</html>
