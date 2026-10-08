<?php
require_once __DIR__ . '/auth.php';
eca_auth_no_store();

$nextParam = (string) ($_POST['next'] ?? $_GET['next'] ?? '');

eca_redirect_signed_in('learner');
if (eca_current_member(true) || eca_sso_member_from_hub()) {
    header('Location: ' . eca_login_next($nextParam, 'member'));
    exit;
}

$error = '';
$conn = eca_db();
$remembered = isset($_COOKIE['eca_membership']) ? (string) $_COOKIE['eca_membership'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginIdentity = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        . '|' . strtolower(trim((string) ($_POST['membership'] ?? '')));
    if (!eca_csrf_ok($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (eca_rate_limit_exceeded('member-login', $loginIdentity, 15, 900)) {
        http_response_code(429);
        $error = 'Too many login attempts. Please wait 15 minutes and try again.';
    } else {
        $membership = trim((string) ($_POST['membership'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        if ($membership === '' || $password === '') {
            $error = 'Enter your membership number and password.';
        } elseif (!$conn) {
            $error = 'The membership database is not available. Please try again shortly.';
        } else {
            $member = eca_attempt_login($conn, $membership, $password);
            if ($member) {
                session_regenerate_id(true);
                unset(
                    $_SESSION['eca_admin'],
                    $_SESSION['user_id'],
                    $_SESSION['role'],
                    $_SESSION['full_name'],
                    $_SESSION['email']
                );
                $_SESSION['eca_member'] = $member;
                $cookieSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || eca_env('ECA_COOKIE_SECURE', '') === '1';
                if ($remember) {
                    setcookie('eca_membership', $member['membership'], [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'secure' => $cookieSecure,
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                } else {
                    setcookie('eca_membership', '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'secure' => $cookieSecure,
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }
                header('Location: ' . eca_login_next($nextParam, 'member'));
                exit;
            }
            $error = 'Invalid email/username or password.';
        }
        $remembered = $membership;
    }
}

$csrf = eca_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Member login | ECA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/auth.css?v=23">
    <?php require_once __DIR__ . '/../includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body class="eca-auth eca-public eca-auth--member">
    <?php
    $searchAction = '/directory.php';
    require __DIR__ . '/../includes/utility-bar.php';
    ?>

    <main class="eca-auth-stage">
        <?php
        $authTheme = 'member';
        require __DIR__ . '/../includes/auth-visual.php';
        ?>
        <section class="eca-auth-panel">
            <nav class="eca-auth-nav">
                <a href="/index.php"><img src="/img/ecalogo.png" alt="Eswatini Contractors Association"></a>
                <a class="site-link" href="/index.php">Home</a>
            </nav>
            <div class="eca-auth-card">
                <p class="eca-kicker">Member login</p>
                <h1>Member login</h1>
                <p class="sub">Use your membership number and password to open the member hub. Learners use Learner login.</p>

                <?php if ($error !== ''): ?>
                    <div class="error" role="alert"><?= eca_h($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php" data-auth-submit>
                    <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
                    <input type="hidden" name="next" value="<?= eca_h($nextParam) ?>">

                    <div class="eca-field">
                        <label class="form-label" for="membership">Membership number</label>
                        <div class="eca-field-control">
                            <i class="bi bi-hash" aria-hidden="true"></i>
                            <input type="text" class="form-control" id="membership" name="membership" placeholder="Enter membership number" value="<?= eca_h($remembered) ?>" required>
                        </div>
                    </div>

                    <div class="eca-field password-wrap">
                        <label class="form-label" for="password">Password</label>
                        <div class="eca-field-control">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                            <button type="button" data-auth-toggle-password="password" aria-label="Show password" aria-pressed="false"><i class="fa fa-eye" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>
                            <input type="checkbox" name="remember" id="remember"> Remember me
                        </label>
                        <a href="page-forgot-password.html">Forgot password?</a>
                    </div>

                    <button type="submit" name="login" class="eca-btn" data-auth-busy="Signing in…">Sign in</button>
                </form>

                <?php if (!$conn): ?>
                    <p class="live-note">The membership database is not available on this local copy.</p>
                <?php endif; ?>
                <p class="eca-auth-foot"><a href="/index.php">Back to website</a></p>
            </div>
            <?php
            $authPortalSet = 'member';
            require __DIR__ . '/../includes/auth-portals.php';
            ?>
        </section>
    </main>
    <script src="/js/auth-login.js?v=2" defer></script>
</body>
</html>
