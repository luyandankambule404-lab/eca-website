<?php
require_once "config.php";
require_once "helpers.php";

$err = $ok = "";
$localPreview = php_sapi_name() === 'cli-server';

if (is_post()) {
    if (empty($conn)) {
        $err = $localPreview
            ? "This computer cannot create accounts without the CPD database."
            : "The CPD database is not available. Please try again shortly.";
    } else {
        $company = trim($_POST['company_name'] ?? '');
        $name    = trim($_POST['full_name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $pass    = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '' || $pass === '') {
            $err = "Full name, email and password are required.";
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            try {
                $stmt = $conn->prepare("INSERT INTO user(role,company_name,full_name,email,phone,password_hash) VALUES('CONTRACTOR',?,?,?,?,?)");
                $stmt->bind_param("sssss", $company, $name, $email, $phone, $hash);
                $stmt->execute();
                $ok = "Account created. Please login.";
            } catch (Exception $e) {
                $err = "Could not register. Email may already exist.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create CPD account | ECA</title>
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
        <a class="site-link" href="login.php">Sign in</a>
    </nav>
    <main class="eca-auth-main">
        <div class="eca-auth-card">
            <p class="eca-kicker">CPD portal</p>
            <h1>Create account</h1>
            <p class="sub">Register as a contractor to apply for training and track CPD points.</p>
            <?php if ($err): ?><div class="error"><?= e($err) ?></div><?php endif; ?>
            <?php if ($ok): ?><div class="error" style="color:#000066;background:#eef1f8;border-color:#e6eaf2;"><?= e($ok) ?></div><?php endif; ?>
            <form method="post" autocomplete="off">
                <div style="margin-bottom:14px;">
                    <label>Company name</label>
                    <input class="form-control" name="company_name" value="<?= e($_POST['company_name'] ?? '') ?>">
                </div>
                <div style="margin-bottom:14px;">
                    <label>Full name</label>
                    <input class="form-control" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
                </div>
                <div style="margin-bottom:14px;">
                    <label>Email</label>
                    <input class="form-control" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div style="margin-bottom:14px;">
                    <label>Phone</label>
                    <input class="form-control" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
                <div style="margin-bottom:22px;">
                    <label>Password</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <button class="eca-btn" type="submit">Create account</button>
            </form>
            <p class="live-note"><a href="login.php">Already have an account? Sign in</a></p>
        </div>
    </main>
</body>
</html>
