<?php
require_once __DIR__ . '/auth.php';

if (eca_current_member()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$conn = eca_db();
$remembered = isset($_COOKIE['eca_membership']) ? (string) $_COOKIE['eca_membership'] : '';
$localPreview = php_sapi_name() === 'cli-server';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!eca_csrf_ok($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $membership = trim((string) ($_POST['membership'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        if ($membership === '' || $password === '') {
            $error = 'Enter your membership number and password.';
        } elseif (!$conn) {
            $error = $localPreview
                ? 'This computer cannot reach the membership database. Use Preview dashboard below, or sign in on the live portal.'
                : 'The membership database is not available. Please try again shortly.';
        } else {
            $member = eca_attempt_login($conn, $membership, $password);
            if ($member) {
                session_regenerate_id(true);
                $_SESSION['eca_member'] = $member;
                if ($remember) {
                    setcookie('eca_membership', $member['membership'], time() + (86400 * 30), '/');
                } else {
                    setcookie('eca_membership', '', time() - 3600, '/');
                }
                header('Location: dashboard.php');
                exit;
            }
            $error = 'Invalid membership number or password.';
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
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body class="eca-auth">
    <div class="eca-dash-topbar">
        <div class="eca-dash-topbar-inner">
            <span>Eswatini Contractors Association</span>
            <a href="mailto:info@eca.co.sz"><i class="fa fa-envelope"></i> info@eca.co.sz</a>
        </div>
    </div>

    <nav class="eca-auth-nav">
        <a href="../index.php"><img src="../img/ecalogo.png" alt="Eswatini Contractors Association"></a>
        <a class="site-link" href="../index.php">Website</a>
    </nav>

    <main class="eca-auth-main">
        <div class="eca-auth-card">
            <p class="eca-kicker">Member portal</p>
            <h1>Sign in</h1>
            <p class="sub">Use your membership number and password to open your ECA dashboard.</p>

            <?php if ($error !== ''): ?>
                <div class="error"><?= eca_h($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">

                <div style="margin-bottom:16px;">
                    <label for="membership">Membership number</label>
                    <input type="text" class="form-control" id="membership" name="membership" placeholder="Enter membership number" value="<?= eca_h($remembered) ?>" required>
                </div>

                <div class="password-wrap" style="margin-bottom:8px;">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                    <button type="button" class="toggle" onclick="togglePassword()" aria-label="Show or hide password"><i class="fa fa-eye"></i></button>
                </div>

                <div class="form-row">
                    <label style="margin:0;font-weight:600;color:#667085;">
                        <input type="checkbox" name="remember" id="remember"> Remember me
                    </label>
                    <a href="page-forgot-password.html">Forgot password?</a>
                </div>

                <button type="submit" name="login" class="eca-btn">Sign in</button>
            </form>

            <?php if (!$conn): ?>
                <p class="live-note">
                    <?php if ($localPreview): ?>
                        <a href="dashboard.php">Preview dashboard</a>
                        &nbsp;&middot;&nbsp;
                    <?php endif; ?>
                    <a href="https://eca.co.sz/client/index.php">Open the live portal</a>
                </p>
            <?php endif; ?>
        </div>
    </main>
<script>
function togglePassword() {
    var field = document.getElementById('password');
    field.type = field.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
