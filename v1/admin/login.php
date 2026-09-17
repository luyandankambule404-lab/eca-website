<?php
require_once __DIR__ . '/auth.php';

if (eca_admin_user()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$conn = eca_admin_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$conn) {
        $error = 'The local database is not available.';
    } else {
        $admin = eca_admin_attempt(
            $conn,
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? '')
        );
        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['eca_admin'] = $admin;
            eca_audit('admin.login', 'users', (string) ($admin['id'] ?? ''), ['email' => $admin['email'] ?? '']);
            header('Location: /admin/index.php');
            exit;
        }
        $error = 'Invalid administrator email or password.';
    }
}

$csrf = eca_admin_csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator login | ECA</title>
    <link rel="stylesheet" href="/css/theme.css?v=7">
    <style>
        body { margin: 0; font-family: "Plus Jakarta Sans", Arial, sans-serif; background: #f4f6f9; color: #192754; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { width: min(420px, 100%); background: #fff; border: 1px solid #e6eaf0; border-radius: 16px; padding: 32px; }
        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        p { color: #667085; }
        label { display: block; font-weight: 700; margin: 16px 0 6px; }
        input { width: 100%; box-sizing: border-box; padding: 12px 14px; border: 1px solid #d0d5dd; border-radius: 10px; }
        button { margin-top: 20px; width: 100%; padding: 12px 16px; border: 0; border-radius: 10px; background: #192754; color: #fff; font-weight: 700; cursor: pointer; }
        .err { background: #fde8e8; color: #9b1c1c; padding: 10px 12px; border-radius: 8px; }
        a { color: #192754; }
    </style>
</head>
<body>
    <div class="wrap">
        <form class="card" method="post">
            <p style="margin:0 0 8px;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#d50d0e;">ECA administration</p>
            <h1>Sign in</h1>
            <p>This area is for administrators only. Existing member records are not changed by viewing them.</p>
            <?php if ($error): ?><p class="err"><?= eca_admin_h($error) ?></p><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?= eca_admin_h($_POST['email'] ?? 'admin@eca.co.sz') ?>">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
            <button type="submit">Sign in</button>
            <p style="margin-top:18px;"><a href="/index.html">Back to website</a></p>
            <p style="margin-top:10px;font-size:13px;">Other portals:
                <a href="/client/">Member</a> ·
                <a href="/cpd/login.php">CPD contractor</a> ·
                <a href="/cpd/admin_login.php">CPD staff</a>
            </p>
        </form>
    </div>
</body>
</html>
