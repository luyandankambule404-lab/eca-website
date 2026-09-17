<?php
require_once "config.php";
require_once "helpers.php";

$err = "";
$localPreview = php_sapi_name() === 'cli-server';

if (is_post()) {
    if (empty($conn)) {
        $err = $localPreview
            ? "This computer cannot reach the CPD database. Use the preview screens or the live portal."
            : "The CPD database is not available. Please try again shortly.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';

        $stmt = $conn->prepare("
            SELECT id, role, email, full_name, password_hash, status
            FROM user
            WHERE email=? LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        if (!$u || !cpd_password_ok($u, $pass)) {
            $err = "Invalid login.";
        } elseif ($u['role'] != 'CONTRACTOR') {
            $err = "Please use the staff portal.";
        } else {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['role'] = $u['role'];
            $_SESSION['full_name'] = $u['full_name'];
            $_SESSION['email'] = $u['email'];
            redirect("/cpd/contractor/dashboard.php");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CPD login | ECA</title>
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
        <a href="/index.php"><img src="/img/ecalogo.png" alt="Eswatini Contractors Association"></a>
        <a class="site-link" href="/index.php">Home</a>
    </nav>
    <main class="eca-auth-main">
        <div class="eca-auth-card">
            <p class="eca-kicker">CPD portal</p>
            <h1>Contractor login</h1>
            <p class="sub">Sign in to view courses, applications and your CPD transcript.</p>
            <?php if ($err): ?><div class="error"><?= e($err) ?></div><?php endif; ?>
            <form method="post">
                <div style="margin-bottom:16px;">
                    <label>Email</label>
                    <input class="form-control" name="email" type="email" required>
                </div>
                <div class="password-wrap" style="margin-bottom:22px;">
                    <label>Password</label>
                    <input class="form-control" name="password" type="password" required>
                </div>
                <button class="eca-btn" type="submit">Sign in</button>
            </form>
            <p class="live-note">
                <a href="admin_login.php">Staff login</a>
                &nbsp;&middot;&nbsp;
                <a href="register.php">Create account</a>
                <?php if ($localPreview): ?>
                    &nbsp;&middot;&nbsp;
                    <a href="preview-dashboards.php">Preview dashboards</a>
                <?php endif; ?>
            </p>
        </div>
    </main>
</body>
</html>
