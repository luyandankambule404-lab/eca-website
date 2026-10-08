<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../code.jquery.com/cpd/auth.php';
eca_auth_no_store();

$nextParam = (string) ($_POST['next'] ?? $_GET['next'] ?? '');
$nextPath = strtolower((string) (parse_url($nextParam, PHP_URL_PATH) ?? ''));
$cpdStaffNext = str_starts_with($nextPath, '/cpd/admin') || str_starts_with($nextPath, '/cpd/officer');
if (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals()) {
    if ($cpdStaffNext) {
        header('Location: ' . eca_login_next($nextParam, 'officer'));
        exit;
    }
}
if (!$cpdStaffNext || !empty($_SESSION['user_id'])) {
    eca_redirect_signed_in('officer');
}

$error = '';
$conn = eca_admin_db();
$email = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginIdentity = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($email);
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (eca_rate_limit_exceeded('officer-login', $loginIdentity, 10, 900)) {
        http_response_code(429);
        $error = 'Too many login attempts. Please wait 15 minutes and try again.';
    } elseif ($email === '' || (string) ($_POST['password'] ?? '') === '') {
        $error = 'Enter your staff email and password.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        $destination = '';

        $admin = $conn ? eca_admin_attempt($conn, $email, $password) : null;
        if ($admin) {
            session_regenerate_id(true);
            unset(
                $_SESSION['eca_member'],
                $_SESSION['user_id'],
                $_SESSION['role'],
                $_SESSION['full_name'],
                $_SESSION['email']
            );
            $_SESSION['eca_admin'] = $admin;
            eca_audit('admin.login', 'users', (string) ($admin['id'] ?? ''), [
                'email' => $admin['email'] ?? '',
                'source' => 'officer_login',
            ]);
            $destination = eca_login_next($nextParam, 'officer');
        }

        if ($destination === '') {
            $staff = cpd_authenticate($email, $password, false);
            if ($staff && eca_is_cpd_staff_role((string) $staff['role'])) {
                cpd_start_authenticated_session($staff);
                $destination = eca_login_next($nextParam !== '' ? $nextParam : eca_officer_home($staff['role']), 'officer');
            }
        }

        if ($destination !== '') {
            header('Location: ' . $destination);
            exit;
        }

        eca_audit('admin.login.failed', 'users', '', [
            'email' => $email,
            'source' => 'officer_login',
            'result' => 'denied',
        ]);
        $error = 'Invalid officer email or password.';
    }
}

$csrf = eca_admin_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Officer login | ECA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/auth.css?v=23">
    <?php require_once __DIR__ . '/../includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body class="eca-auth eca-public eca-auth--admin">
    <?php
    $searchAction = '/directory.php';
    require __DIR__ . '/../includes/utility-bar.php';
    ?>

    <main class="eca-auth-stage">
        <?php
        $authTheme = 'officer';
        require __DIR__ . '/../includes/auth-visual.php';
        ?>
        <section class="eca-auth-panel">
            <nav class="eca-auth-nav">
                <a href="/index.php"><img src="/img/ecalogo.png" alt="Eswatini Contractors Association"></a>
                <a class="site-link" href="/index.php">Home</a>
            </nav>
            <div class="eca-auth-card">
                <p class="eca-kicker">Officer access</p>
                <h1>Officer login</h1>
                <p class="sub">For administrators and super administrators. Members and learners cannot sign in here.</p>
                <?php if ($error): ?><div class="error" role="alert"><?= eca_admin_h($error) ?></div><?php endif; ?>
                <form method="post" data-auth-submit>
                    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                    <input type="hidden" name="next" value="<?= eca_admin_h($nextParam) ?>">
                    <div class="eca-field">
                        <label class="form-label" for="email">Email</label>
                        <div class="eca-field-control">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input id="email" class="form-control" name="email" type="email" autocomplete="username" required value="<?= eca_admin_h($email) ?>">
                        </div>
                    </div>
                    <div class="eca-field password-wrap">
                        <label class="form-label" for="password">Password</label>
                        <div class="eca-field-control">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input id="password" class="form-control" name="password" type="password" autocomplete="current-password" required>
                            <button type="button" data-auth-toggle-password="password" aria-label="Show password" aria-pressed="false"><i class="fa fa-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <button class="eca-btn" type="submit" data-auth-busy="Signing in…">Sign in</button>
                </form>
                <p class="eca-auth-foot"><a href="/index.php">Back to website</a></p>
            </div>
            <?php
            $authPortalSet = 'officer';
            require __DIR__ . '/../includes/auth-portals.php';
            ?>
        </section>
    </main>
    <script src="/js/auth-login.js?v=1" defer></script>
</body>
</html>
