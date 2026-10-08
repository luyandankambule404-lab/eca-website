<?php
require_once "config.php";
require_once "helpers.php";

$err = $ok = "";
$localPreview = php_sapi_name() === 'cli-server';

if (is_post()) {
    cpd_require_csrf();
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
                $stmt = $conn->prepare("INSERT INTO user(role,company_name,full_name,email,phone,password_hash,status) VALUES('CONTRACTOR',?,?,?,?,?,'PENDING')");
                $stmt->bind_param("sssss", $company, $name, $email, $phone, $hash);
                $stmt->execute();
                $ok = "Account submitted for approval. You can sign in after ECA activates it.";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/auth.css?v=23">
    <?php require_once dirname(__DIR__, 2) . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body class="eca-auth eca-public eca-auth--register">
    <?php
    $searchAction = '/directory.php';
    require dirname(__DIR__, 2) . '/includes/utility-bar.php';
    ?>
    <main class="eca-auth-stage">
        <?php
        $authTheme = 'register';
        require dirname(__DIR__, 2) . '/includes/auth-visual.php';
        ?>
        <section class="eca-auth-panel">
            <nav class="eca-auth-nav">
                <a href="/index.php"><img src="/img/ecalogo.png" alt="Eswatini Contractors Association"></a>
                <a class="site-link" href="login.php">Sign in</a>
            </nav>
            <div class="eca-auth-card">
                <p class="eca-kicker">CPD portal</p>
                <h1>Create account</h1>
                <p class="sub">Register as a contractor to apply for training and track CPD points.</p>
                <?php if ($err): ?><div class="error"><?= e($err) ?></div><?php endif; ?>
                <?php if ($ok): ?><div class="ok"><?= e($ok) ?></div><?php endif; ?>
                <form method="post" autocomplete="off">
                    <?= cpd_csrf_input() ?>
                    <div class="eca-field">
                        <label class="form-label">Company name</label>
                        <div class="eca-field-control">
                            <i class="bi bi-building" aria-hidden="true"></i>
                            <input class="form-control" name="company_name" value="<?= e($_POST['company_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="eca-field">
                        <label class="form-label">Full name</label>
                        <div class="eca-field-control">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input class="form-control" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="eca-field">
                        <label class="form-label">Email</label>
                        <div class="eca-field-control">
                            <i class="bi bi-envelope" aria-hidden="true"></i>
                            <input class="form-control" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="eca-field">
                        <label class="form-label">Phone</label>
                        <div class="eca-field-control">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                            <input class="form-control" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="eca-field">
                        <label class="form-label">Password</label>
                        <div class="eca-field-control">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input class="form-control" type="password" name="password" required>
                        </div>
                    </div>
                    <button class="eca-btn" type="submit">Create account</button>
                </form>
                <p class="eca-auth-foot"><a href="login.php">Already have an account? Sign in</a></p>
            </div>
            <?php
            $authPortalSet = 'register';
            require dirname(__DIR__, 2) . '/includes/auth-portals.php';
            ?>
        </section>
    </main>
</body>
</html>
